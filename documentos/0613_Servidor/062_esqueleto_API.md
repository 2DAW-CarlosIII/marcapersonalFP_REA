# Breeze API

En el capítulo dedicado a la [autenticación](./052_Autenticacion.md) ya instalamos el paquete `[laravel/breeze]`(https://laravel.com/docs/10.x/starter-kits#laravel-breeze), que nos facilitó la gestión de usuarios.

En este capítulo, vamos a reinstalar `laravel/breeze` pero desechando el código referente a las vistas, ya que nuestro objetivo es crear una _API_ que que envíe y reciba datos sin ningún código referente a las vistas.

## Instalación de Breeze API

Como `laravel/breeze` ya lo habíamos instalado, tan solo es necesaria la ejecución del siguiente comando para indicarle que trabajaremos en el entorno de una API:

```bash
php artisan breeze:install api
```

La posteerior ejecución de un `git status` nos devolvera numerosos cambios en el código:
    
```bash
Cambios no rastreados para el commit:
  (usa "git add/rm <archivo>..." para actualizar a lo que se le va a hacer commit)
  (usa "git restore <archivo>..." para descartar los cambios en el directorio de trabajo)
	modificado:     app/Http/Controllers/Auth/AuthenticatedSessionController.php
	modificado:     app/Http/Controllers/Auth/EmailVerificationNotificationController.php
	modificado:     app/Http/Controllers/Auth/NewPasswordController.php
	modificado:     app/Http/Controllers/Auth/PasswordResetLinkController.php
	modificado:     app/Http/Controllers/Auth/RegisteredUserController.php
	modificado:     app/Http/Controllers/Auth/VerifyEmailController.php
	modificado:     app/Http/Kernel.php
	modificado:     app/Http/Requests/Auth/LoginRequest.php
	modificado:     app/Providers/AuthServiceProvider.php
	modificado:     config/app.php
	modificado:     config/cors.php
	modificado:     config/sanctum.php
	borrado:        package.json
	borrado:        resources/css/app.css
	borrado:        resources/js/app.js
	borrado:        resources/js/bootstrap.js
   	borrado:        resources/views/welcome.blade.php
	modificado:     routes/api.php
	modificado:     routes/auth.php
	modificado:     routes/web.php
	modificado:     routes/api.php
	modificado:     routes/auth.php
	modificado:     tests/Feature/Auth/AuthenticationTest.php
	modificado:     tests/Feature/Auth/EmailVerificationTest.php
	borrado:        tests/Feature/Auth/PasswordConfirmationTest.php
	modificado:     tests/Feature/Auth/PasswordResetTest.php
	modificado:     tests/Feature/Auth/RegistrationTest.php
	borrado:        vite.config.js

Archivos sin seguimiento:
  (usa "git add <archivo>..." para incluirlo a lo que se será confirmado)
	app/Http/Middleware/EnsureEmailIsVerified.php
	resources/views/.gitkeep
```

Como vemos, se han incluido ficheros de configuración de _CORS_ y _Sanctum_, que nos permitirán gestionar la seguridad de nuestra API. También se han modificado los ficheros de rutas, para que se utilicen las rutas de la API, y se han modificado los controladores de autenticación para que no se utilicen las vistas.

No obstante, de esa lista hay dos ficheros que no queremos modificar y que debemos recuperar antes de confirmar los cambios:

- `resources/views/welcome.blade.php`
- `routes/web.php`

Para ello, ejecutaremos el siguiente comando:

```bash
git restore resources/views/welcome.blade.php routes/web.php
```

## Configuración de CORS

El paquete _CORS_ nos permite configurar el acceso a nuestra API desde otros dominios. Para ello, el fichero `config/cors.php` incluye una línea que nos permite configurar los dominios que tendrán acceso a nuestra API:

```php
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
```

Como vemos, por defecto, se permite el acceso desde `http://localhost:3000`, que es el puerto por defecto de _React JS_. Si el dominio desde el que accederemos es diferente a este, debemos modificar el valor de la variable de entorno `FRONTEND_URL` en el fichero `.env`.

## Configuración de Sanctum

El paquete _Sanctum_ nos permite gestionar la seguridad de nuestra API. Para ello, el fichero `config/sanctum.php` incluye una línea que nos permite configurar los dominios que tendrán acceso a nuestra API:

```php
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : '',
        env('FRONTEND_URL') ? ','.parse_url(env('FRONTEND_URL'), PHP_URL_HOST) : ''
    ))),

```

Como vemos, por defecto, se permite el acceso desde `http://localhost:3000`, que es el puerto por defecto de _React JS_. Si el dominio desde el que accederemos es diferente a este, debemos modificar el valor de la variable de entorno `FRONTEND_URL` en el fichero `.env`.

El aspecto crucial aquí es que este comando agrega las rutas para el frontend y el backend al archivo .env. La ruta del frontend está relacionada con el archivo ‘config / sanctum.php’, donde se declaran las rutas estatales del backend. Para fines de prueba, no es necesario realizar ningún cambio en el archivo ‘config / sanctum.php’. Sin embargo, si desea implementar su aplicación en un entorno de producción, debemos agregar las variables ‘SANCTUM_STATEFUL_DOMAINS’ en el archivo ‘.env’ y en el `.env.example`.

```bash
APP_URL=http://marcapersonalfp.test
FRONTEND_URL=http://localhost:3000
SANCTUM_STATEFUL_DOMAINS=localhost:3000,marcaPersonalFP.test
```

