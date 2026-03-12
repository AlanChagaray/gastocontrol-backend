# Sistema de Autenticación - API REST Laravel

Sistema completo de autenticación para la API de control de gastos usando Laravel Sanctum y Laravel Socialite.

## 📋 Características

- ✅ Registro de usuarios con validación
- ✅ Login con email y contraseña
- ✅ Autenticación con tokens (Laravel Sanctum)
- ✅ Verificación de email obligatoria
- ✅ Recuperación de contraseña
- ✅ Login con Google OAuth
- ✅ Rate limiting en endpoints sensibles
- ✅ Logout con invalidación de tokens

## 🚀 Instalación

### 1. Instalar dependencias

```bash
composer require laravel/sanctum laravel/socialite
```

### 2. Publicar configuración de Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 3. Configurar variables de entorno

Agregar al archivo `.env`:

```env
# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gastocontrol
DB_USERNAME=postgres
DB_PASSWORD=tu_password

# Google OAuth
GOOGLE_CLIENT_ID=tu_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu_client_secret
GOOGLE_REDIRECT_URI=${APP_URL}/api/auth/google/callback

# Mail Configuration (para verificación de email y reset password)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_password_aplicacion
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Frontend URL (para redireccionamientos)
FRONTEND_URL=http://localhost:3000
```

### 4. Ejecutar migraciones

```bash
php artisan migrate
```

### 5. Configurar Sanctum en bootstrap/app.php

Agregar el middleware de Sanctum para rutas API:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->statefulApi();
})
```

## 📝 Endpoints de la API

### Autenticación con Email/Password

#### 1. Registro de Usuario

```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

**Respuesta exitosa (201):**
```json
{
  "message": "Usuario registrado exitosamente. Por favor verifica tu email.",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "email_verified_at": null,
    "created_at": "2024-03-06T10:00:00.000000Z",
    "updated_at": "2024-03-06T10:00:00.000000Z"
  },
  "token": "1|laravel_sanctum_token_here"
}
```

#### 2. Login

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "juan@example.com",
  "password": "Password123!"
}
```

**Rate Limit:** 5 intentos por minuto

**Respuesta exitosa (200):**
```json
{
  "message": "Login exitoso.",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "email_verified_at": "2024-03-06T10:05:00.000000Z"
  },
  "token": "2|laravel_sanctum_token_here"
}
```

#### 3. Obtener Usuario Autenticado

```http
GET /api/auth/me
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "email_verified_at": "2024-03-06T10:05:00.000000Z"
  }
}
```

#### 4. Logout

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "message": "Sesión cerrada exitosamente."
}
```

### Verificación de Email

#### 5. Verificar Email

```http
GET /api/email/verify/{id}/{hash}
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
  "message": "Email verificado exitosamente."
}
```

#### 6. Reenviar Email de Verificación

```http
POST /api/email/resend
Authorization: Bearer {token}
```

**Rate Limit:** 3 intentos por minuto

**Respuesta exitosa (200):**
```json
{
  "message": "Enlace de verificación enviado."
}
```

### Recuperación de Contraseña

#### 7. Solicitar Reset de Contraseña

```http
POST /api/auth/forgot-password
Content-Type: application/json

{
  "email": "juan@example.com"
}
```

**Rate Limit:** 3 intentos por minuto

**Respuesta exitosa (200):**
```json
{
  "message": "Enlace de recuperación enviado a tu email."
}
```

#### 8. Restablecer Contraseña

```http
POST /api/auth/reset-password
Content-Type: application/json

{
  "token": "reset_token_from_email",
  "email": "juan@example.com",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

**Respuesta exitosa (200):**
```json
{
  "message": "Contraseña restablecida exitosamente."
}
```

### Autenticación con Google OAuth

#### 9. Redirigir a Google

```http
GET /api/auth/google/redirect
```

Redirige al usuario a la página de autorización de Google.

#### 10. Callback de Google

```http
GET /api/auth/google/callback
```

Google redirige aquí después de la autorización. Este endpoint procesa la respuesta y retorna el token.

**Respuesta exitosa (200):**
```json
{
  "message": "Login con Google exitoso.",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@gmail.com",
    "email_verified_at": "2024-03-06T10:00:00.000000Z"
  },
  "token": "3|laravel_sanctum_token_here"
}
```

## 🔒 Seguridad

### Middleware Aplicado

- **auth:sanctum**: Protege rutas que requieren autenticación
- **verified**: Exige que el email esté verificado
- **throttle**: Rate limiting en endpoints sensibles

### Rate Limiting

| Endpoint | Límite |
|----------|--------|
| POST /api/auth/login | 5 por minuto |
| POST /api/auth/forgot-password | 3 por minuto |
| POST /api/email/resend | 3 por minuto |

### Validación de Contraseñas

Las contraseñas deben cumplir:
- Mínimo 8 caracteres
- Al menos una letra mayúscula
- Al menos una letra minúscula
- Al menos un número
- Al menos un símbolo especial

## 🗄️ Estructura de Base de Datos

### Tabla: users

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único del usuario |
| name | string | Nombre completo |
| email | string | Email único |
| email_verified_at | timestamp | Fecha de verificación del email |
| password | string | Contraseña hasheada |
| remember_token | string | Token de sesión |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

### Tabla: auth_providers

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| user_id | bigint | FK a users |
| provider | string | Nombre del provider (google, facebook, etc) |
| provider_user_id | string | ID del usuario en el provider |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

**Índice único:** (provider, provider_user_id)

### Tabla: password_reset_tokens

| Campo | Tipo | Descripción |
|-------|------|-------------|
| email | string | Email del usuario (PK) |
| token | string | Token de reset |
| created_at | timestamp | Fecha de creación |

## 📁 Estructura de Archivos Creados

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── GoogleAuthController.php
│   │   ├── PasswordController.php
│   │   └── UserController.php
│   └── Requests/
│       ├── ForgotPasswordRequest.php
│       ├── LoginRequest.php
│       ├── RegisterRequest.php
│       └── ResetPasswordRequest.php
└── Models/
    ├── AuthProvider.php
    └── User.php

config/
└── services.php (actualizado)

database/
└── migrations/
    ├── 0001_01_01_000000_create_users_table.php (existente)
    └── 2024_03_06_000001_create_auth_providers_table.php

routes/
└── api.php
```

## 🔧 Configuración de Google OAuth

### 1. Crear proyecto en Google Cloud Console

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita la API de Google+ (Google+ API)

### 2. Crear credenciales OAuth 2.0

1. Ve a "APIs & Services" > "Credentials"
2. Click en "Create Credentials" > "OAuth client ID"
3. Selecciona "Web application"
4. Agrega estas URIs autorizadas:
   - **Authorized JavaScript origins:** `http://localhost:8000`
   - **Authorized redirect URIs:** `http://localhost:8000/api/auth/google/callback`
5. Guarda el Client ID y Client Secret en tu `.env`

## 💡 Uso desde el Frontend

### Ejemplo con JavaScript (Fetch API)

```javascript
// 1. Registro
const register = async (userData) => {
  const response = await fetch('http://localhost:8000/api/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(userData)
  });
  const data = await response.json();
  // Guardar token en localStorage
  localStorage.setItem('token', data.token);
  return data;
};

// 2. Login
const login = async (email, password) => {
  const response = await fetch('http://localhost:8000/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ email, password })
  });
  const data = await response.json();
  localStorage.setItem('token', data.token);
  return data;
};

// 3. Obtener usuario autenticado
const getUser = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch('http://localhost:8000/api/auth/me', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  return await response.json();
};

// 4. Logout
const logout = async () => {
  const token = localStorage.getItem('token');
  await fetch('http://localhost:8000/api/auth/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  localStorage.removeItem('token');
};

// 5. Login con Google
const loginWithGoogle = () => {
  window.location.href = 'http://localhost:8000/api/auth/google/redirect';
};
```

## 🧪 Testing

Puedes usar herramientas como Postman o Insomnia para probar los endpoints.

### Colección de Postman

Importa esta colección base:

```json
{
  "info": {
    "name": "GastoControl API - Auth",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8000/api"
    },
    {
      "key": "token",
      "value": ""
    }
  ]
}
```

## 📧 Configuración de Email

Para desarrollo local, puedes usar [Mailtrap](https://mailtrap.io/) o [MailHog](https://github.com/mailhog/MailHog).

### Configuración con Mailtrap

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_username
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
```

## ⚠️ Consideraciones Importantes

1. **Verificación de Email:** Los usuarios deben verificar su email antes de poder usar los endpoints protegidos con el middleware `verified`.

2. **CORS:** Si tu frontend está en un dominio diferente, configura CORS en Laravel:
   ```bash
   php artisan config:publish cors
   ```

3. **Sanctum SPA Authentication:** Para SPAs en el mismo dominio, configura Sanctum correctamente en el archivo `config/sanctum.php`.

4. **Producción:** En producción, asegúrate de:
   - Usar HTTPS
   - Configurar correctamente las URLs de Google OAuth
   - Usar un servicio de email confiable
   - Configurar límites de rate limiting apropiados

## 🚀 Próximos Pasos

1. Ejecutar las migraciones: `php artisan migrate`
2. Configurar las variables de entorno
3. Probar los endpoints con Postman
4. Implementar el frontend que consuma la API

## 📝 Notas

- Los tokens de Sanctum no expiran por defecto. Puedes configurar la expiración en `config/sanctum.php`
- Los usuarios que se registran con Google tienen su email verificado automáticamente
- Los passwords se hashean automáticamente usando bcrypt
