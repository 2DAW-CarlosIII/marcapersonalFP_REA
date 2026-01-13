# Breeze API

En el capítulo dedicado a la [autenticación](./051_Autenticacion.md) ya instalamos el paquete [`laravel/breeze`](https://laravel.com/docs/starter-kits#laravel-breeze), que nos facilitó la gestión de usuarios.

En este capítulo, vamos a reinstalar `laravel/breeze` pero desechando el código referente a las vistas, ya que nuestro objetivo es crear una _API_ que envíe y reciba datos sin ningún código referente a las vistas.

## Instalación de Breeze API

Como `laravel/breeze` ya lo habíamos instalado, tan solo es necesaria la ejecución del siguiente comando para indicarle que trabajaremos en el entorno de una API:

```bash
php artisan breeze:install api
```

La posterior ejecución de un `git status` nos devolvera numerosos cambios en el código:
    
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
	modificado:     app/Http/Requests/Auth/LoginRequest.php
	modificado:     app/Providers/AppServiceProvider.php
	modificado:     bootstrap/app.php
	modificado:     composer.json
	modificado:     composer.lock
	borrado:        package.json
	borrado:        resources/css/app.css
	borrado:        resources/js/app.js
	borrado:        resources/js/bootstrap.js
	borrado:        resources/views/welcome.blade.php
	modificado:     routes/auth.php
	modificado:     routes/web.php
	modificado:     tests/Feature/Auth/AuthenticationTest.php
	modificado:     tests/Feature/Auth/EmailVerificationTest.php
	borrado:        tests/Feature/Auth/PasswordConfirmationTest.php
	modificado:     tests/Feature/Auth/PasswordResetTest.php
	modificado:     tests/Feature/Auth/RegistrationTest.php
	borrado:        vite.config.js

Archivos sin seguimiento:
  (usa "git add <archivo>..." para incluirlo a lo que se será confirmado)
	app/Http/Middleware/
	config/cors.php
	config/sanctum.php
	database/migrations/2025_01_07_172506_create_personal_access_tokens_table.php
	resources/views/.gitkeep
	routes/api.php
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

El paquete _CORS_ nos permite configurar el acceso a nuestra API desde otros dominios y puertos. Para ello, el fichero `config/cors.php` incluye una línea que nos permite configurar los dominios que tendrán acceso a nuestra API:

```php
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
```

Como vemos, por defecto, se permite el acceso desde `http://localhost:3000`, que es el puerto por defecto de _React JS_. Si el dominio desde el que accederemos es diferente a este, debemos modificar el valor de la variable de entorno `FRONTEND_URL` en el fichero `.env`.

## Configuración de Sanctum

El paquete _Sanctum_ nos permite gestionar la seguridad de nuestra API. Para ello, el fichero `config/sanctum.php` incluye una línea que nos permite configurar los dominios y hosts que recibirán cookies de autenticación con mantenimiento del estado:

```php
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:3000,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
        env('FRONTEND_URL') ? ','.parse_url(env('FRONTEND_URL'), PHP_URL_HOST) : ''
    ))),
```

Como vemos, por defecto, se permite el acceso desde `http://localhost:3000`, que es el puerto por defecto de _React JS_. Si el dominio desde el que accederemos es diferente a este, debemos modificar el valor de la variable de entorno `FRONTEND_URL` en el fichero `.env`.

Para fines de prueba, no es necesario realizar ningún cambio en el archivo `config/sanctum.php`, aunque es muy probable que tengamos que hacer algún cambio en un entorno de prducción.

Vamos a aprovechar para hacer algún cambio adicional en el fichero `.env`:

```bash
APP_NAME="Marca Personal FP"
```

y

```bash
APP_URL=http://marcapersonalfp.test
```

## Nuevo fichero de rutas

Se ha creado un nuevo fichero `routes/api.php`. Este fichero es el que se utilizará para gestionar las rutas de nuestra API.

De momento, el único contenido es el siguiente:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
```

Ese contenido genera una única ruta `GET /api/user` que devuelve los datos del usuario autenticado, si se ha autenticado previamente o una redirección al login si no está autenticado.
