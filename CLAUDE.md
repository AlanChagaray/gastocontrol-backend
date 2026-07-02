# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Descripción general

Backend de GastoControl: una API REST en Laravel 12 (PHP 8.2+) para el control de gastos personales. Es solo API — no hay interfaz renderizada en el servidor; un frontend separado (por defecto `http://localhost:3000`) consume los endpoints JSON. El dominio son gastos agrupados por categorías propias de cada usuario, más un sistema completo de autenticación (email/contraseña + Google OAuth).

## Comandos

```bash
# Levantar todo el stack de desarrollo (server + queue + logs + vite) en paralelo
composer run dev

# O solo el servidor de la API
php artisan serve                       # http://localhost:8000

# Tests (limpia la config primero y luego corre PHPUnit)
composer test
php artisan test                        # equivalente
php artisan test --filter=SomeTest      # un solo test / método
php artisan test tests/Feature/ExampleTest.php   # un solo archivo

# Lint / formato (Laravel Pint, preset "laravel" de StyleCI)
./vendor/bin/pint                       # corregir
./vendor/bin/pint --test                # solo verificar

# Base de datos
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed --class=CategoriesSeeder
```

Docker: `docker-compose up` construye la app (php:8.4-fpm) y un contenedor PostgreSQL 16. El compose lee las variables `DB_*` desde `.env`.

## Bases de datos

- **Local/producción**: PostgreSQL (`DB_CONNECTION=pgsql`, base de datos `gastocontrol`).
- **Tests**: SQLite en memoria — fijado en `phpunit.xml`, por lo que los tests nunca tocan la base real. Ahí se define `BCRYPT_ROUNDS=4` y drivers array/sync para mayor velocidad.

CI (`.github/workflows/tests.yml`) corre `php artisan test` contra SQLite en PHP 8.2/8.3/8.4 en cada push/PR a `master` y de forma nocturna. `fail-fast` está activado.

## Arquitectura

Estructura estándar de Laravel. Las rutas son solo de API — todo vive en `routes/api.php` bajo el prefijo `/api`; `routes/web.php` está prácticamente vacío.

**Grupos de rutas en `routes/api.php`** (este es el mapa de la API):
- `auth/*` **público**: register, login (throttle 5/min), forgot/reset password, redirect/callback de Google OAuth.
- `auth:sanctum` **protegido**: `auth/me`, `auth/logout`, listado de usuarios, `categories` (apiResource), `expenses` (apiResource + `expenses/byMonth`), verificación/reenvío de email.
- Existe un tercer grupo protegido por `auth:sanctum` + `verified` pero actualmente está vacío (reservado para rutas que requieran email verificado).

**Autenticación** usa Laravel Sanctum (tokens bearer, sin expiración por defecto) y Socialite (Google). `bootstrap/app.php` habilita `statefulApi()`. La lógica de auth está repartida entre `AuthController`, `GoogleAuthController`, `PasswordController` y `UserController` (verificación de email). Ver `AUTH_DOCUMENTATION.md` para la referencia completa de endpoints e `INSTALLATION.md` para la configuración. El modelo `User` implementa `MustVerifyEmail`.

**La multi-tenencia por `user_id` se aplica manualmente en cada controlador**, no mediante global scopes ni policies. Este es el patrón más importante a preservar:
- Toda lectura/escritura se acota con `->where('user_id', $request->user()->id)` y usa `find($id)` (no `findOrFail`), devolviendo un 404 en JSON con mensaje en español (`'Gasto no encontrado'`, `'Categoría no encontrada'`) cuando el registro no existe o no pertenece al usuario.
- `StoreExpensesRequest`/`StoreCategoryRequest` inyectan `user_id` desde el usuario autenticado después de validar — nunca confiar en un `user_id` provisto por el cliente.
- `StoreExpensesRequest` además valida que `category_id` pertenezca al usuario actual mediante un `Rule::exists` acotado.
- **`ExpensesPolicy` devuelve `false` en todos sus métodos y NO está conectada** — la autorización es el acotamiento manual por `user_id` de arriba, así que no dependas ni enrutes a través de la policy sin registrarla e implementarla también.

**Modelos de dominio** (`app/Models`): `User` hasMany `Category` y `Expenses`; `Category` hasMany `Expenses`; `Expenses` belongsTo ambos. Nota: el modelo se llama `Expenses` (nombre de clase en plural, tabla `expenses`). Eliminar un usuario propaga (cascade) a sus gastos (FK `onDelete('cascade')`); la FK de categoría no tiene cascade.

**Convenciones**:
- Los mensajes de la API hacia el usuario están en **español**; mantené la consistencia en los mensajes nuevos.
- La validación vive en clases Form Request (`app/Http/Requests`) con `messages()` personalizados en español. Agregá reglas ahí, no inline en los controladores.
- Los controladores devuelven `response()->json($data, $status)` explícitamente con códigos de estado (200/201/404).
- Los endpoints de listado/detalle seleccionan columnas explícitas y hacen eager-load de `category:id,name,icon,color` para evitar traer datos de más — seguí este patrón al agregar endpoints.

## Notas

- Los tests contienen actualmente solo el scaffolding por defecto de Laravel (`ExampleTest`) — todavía no hay cobertura de tests del dominio.
- `GoogleAuthController` tiene una ruta `google/debug` aún registrada; tratala como solo para desarrollo.
- El grupo de middleware `verified` está conectado pero sin usar; la verificación de email está disponible pero no se exige en las rutas de gastos/categorías.
