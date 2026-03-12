# 🚀 Instalación del Sistema de Autenticación

## Pasos de Instalación

### 1. Instalar Dependencias de Laravel Sanctum y Socialite

```bash
composer require laravel/sanctum laravel/socialite
```

### 2. Publicar Configuración de Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 3. Configurar Variables de Entorno

Copia las variables del archivo `.env.auth.example` a tu archivo `.env`:

```bash
# Google OAuth
GOOGLE_CLIENT_ID=tu_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu_client_secret
GOOGLE_REDIRECT_URI=${APP_URL}/api/auth/google/callback

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_username
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@gastocontrol.com
MAIL_FROM_NAME="${APP_NAME}"

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gastocontrol
DB_USERNAME=postgres
DB_PASSWORD=tu_password

# Frontend
FRONTEND_URL=http://localhost:3000

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:3000
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
```

### 4. Ejecutar Migraciones

```bash
php artisan migrate
```

### 5. Verificar Configuración de Sanctum en bootstrap/app.php

Abre el archivo `bootstrap/app.php` y asegúrate de que está configurado correctamente:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

### 6. Configurar CORS (Si tu frontend está en otro dominio)

Publica la configuración de CORS:

```bash
php artisan config:publish cors
```

Luego edita `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:3000'),
],

'supports_credentials' => true,
```

### 7. Limpiar Cache y Configuración

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 8. Iniciar el Servidor

```bash
php artisan serve
```

## ✅ Verificación de la Instalación

### Probar con cURL

#### 1. Registro
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

#### 2. Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123!"
  }'
```

#### 3. Obtener Usuario (reemplaza TOKEN con el token recibido)
```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"
```

## 🔧 Configuración de Google OAuth

### Crear Credenciales en Google Cloud Console

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Ve a "APIs & Services" > "Credentials"
4. Click "Create Credentials" > "OAuth client ID"
5. Selecciona "Web application"
6. Configura:
   - **Name:** GastoControl Auth
   - **Authorized JavaScript origins:** 
     - `http://localhost:8000`
     - `http://localhost:3000` (si usas frontend separado)
   - **Authorized redirect URIs:**
     - `http://localhost:8000/api/auth/google/callback`
7. Copia el Client ID y Client Secret a tu `.env`

### Habilitar Google+ API

1. En Google Cloud Console, ve a "APIs & Services" > "Library"
2. Busca "Google+ API"
3. Click "Enable"

## 📧 Configuración de Email para Desarrollo

### Opción 1: Mailtrap (Recomendado para desarrollo)

1. Crea cuenta en [mailtrap.io](https://mailtrap.io/)
2. Ve a tu inbox de prueba
3. Copia las credenciales SMTP
4. Pégalas en tu `.env`

### Opción 2: MailHog (Local)

```bash
# Instalar MailHog
# Windows: Descargar de https://github.com/mailhog/MailHog/releases
# Mac: brew install mailhog
# Linux: apt-get install mailhog

# Ejecutar
mailhog

# Configurar en .env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

Abre http://localhost:8025 para ver los emails capturados.

## 🗄️ Configurar PostgreSQL

### Crear Base de Datos

```sql
CREATE DATABASE gastocontrol;
```

### Verificar Conexión

```bash
php artisan migrate:status
```

## 🧪 Testing con Postman

### Importar Colección

Crea una nueva colección en Postman con estas variables:

- `base_url`: `http://localhost:8000/api`
- `token`: (se llenará automáticamente después del login)

### Requests de Ejemplo

1. **Register**: POST `{{base_url}}/auth/register`
2. **Login**: POST `{{base_url}}/auth/login`
3. **Me**: GET `{{base_url}}/auth/me` (Headers: `Authorization: Bearer {{token}}`)
4. **Logout**: POST `{{base_url}}/auth/logout` (Headers: `Authorization: Bearer {{token}}`)

## 🔍 Solución de Problemas

### Error: "Class HasApiTokens not found"

**Solución:** Instalar Sanctum
```bash
composer require laravel/sanctum
```

### Error: "Class Socialite not found"

**Solución:** Instalar Socialite
```bash
composer require laravel/socialite
```

### Error: "SQLSTATE[08006] Connection refused"

**Solución:** Verificar que PostgreSQL esté corriendo y las credenciales en `.env` sean correctas.

### Error: "419 CSRF token mismatch"

**Solución:** Configurar correctamente CORS y Sanctum domains en `.env`:
```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000
SESSION_DOMAIN=localhost
```

### Emails no se envían

**Solución:** Verificar configuración de email en `.env` y que el servicio SMTP esté activo.

## 📚 Documentación Adicional

- Ver `AUTH_DOCUMENTATION.md` para documentación completa de endpoints
- Ver `.env.auth.example` para todas las variables de entorno necesarias

## ✨ ¡Listo!

Tu sistema de autenticación está configurado y listo para usar.

Para documentación completa de todos los endpoints y ejemplos de uso, consulta `AUTH_DOCUMENTATION.md`.
