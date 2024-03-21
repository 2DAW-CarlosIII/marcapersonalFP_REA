# 5.2. Control de usuarios

_Laravel_ incluye una serie de métodos y clases que harán que la implementación del control de usuarios sea muy rápida y sencilla. De hecho, casi todo el trabajo ya está hecho, solo tendremos que indicar dónde queremos utilizarlo y algunos pequeños detalles de configuración.

Por defecto, al crear un nuevo proyecto de _Laravel_, ya se incluye todo lo necesario:

- La configuración predeterminada en `config/auth.php`.
- La _migración_ para la base de datos de la tabla de usuarios con todos los campos necesarios.
- El modelo de datos de usuario (`User.php`) dentro de la carpeta `app/Models` con toda la implementación necesaria.
- Los controladores para gestionar todas las acciones relacionadas con el control de usuarios (dentro de `App\Http\Controllers\Auth`).

Además de esto, tendremos que ejecutar los siguientes comandos para generar las rutas y vistas necesarias para realizar el _login_, registro y para recuperar la contraseña con **Laravel/Breeze**.

```bash
composer update
composer require laravel/breeze --dev
```

Una vez instalado el paquete breeze, ejecutamos el siguiente comando _Artisan_ para publicar todos los recursos necesarios para la autenticación:

```bash
php artisan breeze:install
```

En el momento de la ejecución del comando anterior, se nos preguntará por nuestra elección de _frameworks_ para el frontend o los tests.

Más adelante trabajaremos con React pero, para esta práctica, nuestra selección será la siguiente:

```
    ┌ Which Breeze stack would you like to install? ───────────────┐
    │ › ● Blade with Alpine                                           │
    │   ○ Livewire (Volt Class API) with Alpine                       │
    │   ○ Livewire (Volt Functional API) with Alpine                  │
    │   ○ React with Inertia                                          │
    │   ○ Vue with Inertia                                            │
    │   ○ API only                                                    │
    └───────────────────────────────────────────────────────┘

    ┌ Would you like dark mode support? ─────────────────────────┐
    │ ○ Yes / ● No                                                    │
    └───────────────────────────────────────────────────────┘

    ┌ Which testing framework do you prefer? ─────────────────────┐
    │ › ● PHPUnit                                                     │
    │   ○ Pest                                                        │
    └───────────────────────────────────────────────────────┘

```

Para finalizar y probarlo, ejecutamos los siguientes comandos:

```bash
php artisan migrate
```

En los siguientes apartados vamos a ver en detalle cada uno de estos puntos, desde la configuración hasta los módulos, rutas y vistas por los que está compuesto. En las últimas secciones revisaremos también cómo utilizar este sistema para proteger nuestro sitio web.

## Configuración inicial

La configuración del sistema de autenticación se puede encontrar en el fichero `config/auth.php`, el cual contiene varias opciones (bien documentadas) que nos permitirán, por ejemplo: **cambiar el sistema de autenticación** (que por defecto es a través de _Eloquent_), **cambiar el modelo de datos usado para los usuarios** (por defecto será `User`) y **cambiar la tabla de usuarios** (que por defecto será `users`). Si vamos a utilizar estos valores no será necesario que realicemos ningún cambio.

La migración de la tabla de usuarios (llamada `users`) también está incluida (ver carpeta `database/migrations`). Por defecto, incluye todos los campos necesarios (ver el código siguiente), pero si necesitamos alguno más lo podemos añadir para guardar por ejemplo la dirección o el teléfono del usuario. A continuación se incluye el código de la función up de la migración:

```php
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
```

Como se puede ver el nombre de la tabla es `users`, con un índice `id` _autoincremental_, y los campos de `name`, `email`, `password`, donde el campo `email` se establece como único para que no se puedan almacenar _emails_ repetidos. Además, se añaden los _timestamps_ que usa _Eloquent_ para almacenar automáticamente la fecha de registro y actualización, y el campo `remember_token` para que no sea necesario solicitar credenciales al usuario cuando ha expirado su sesión.

En la carpeta `app/Models` se encuentra el modelo de datos (llamado `User.php`) para trabajar con los usuarios. Esta clase ya incluye toda la implementación necesaria y por defecto no tendremos que modificar nada. No obstante, deberemos ampliar esta clase si queremos podemos modificar esta clase para añadirle más métodos o relaciones con otras tablas, etc.

_Laravel_ también incluye varios controladores para la autenticación de usuarios, que puedes encontrar en `App/Http/Controllers/Auth`. `AuthenticatedSessionController` y `RegisteredUserController` incluyen métodos para ayudarnos en el proceso de autenticación, registro y cierre de sesión; mientras que `PasswordResetLinkController` contienen la lógica para ayudarnos en el proceso de restaurar una contraseña. Para la mayoría de aplicaciones con estos métodos será suficiente y no tendremos que añadir nada más.

## Rutas

Por defecto, _Laravel_ no incluye las rutas para el control de usuarios. No obstante, al instalar _Laravel/Breeze_, podemos observar que se modifica el contenido del fichero `routes/web.php`. Entre otros cambios, el fichero incluye la siguiente línea:

```php
require __DIR__.'/auth.php';
```

Esa línea incluye todas las rutas definidas en el fichero `routes/auth.php`, las cuales puedes comprobar con el comando

```bash
php artisan route:list
```

Como se puede ver, estas rutas ya están enlazadas con los controladores y métodos que incorpora el propio _Laravel_.

Como la instalación de _Laravel/Breeze_ ha regenerado el fichero `routes/web.php`, debemos volver a incorporar las rutas eliminadas. Para ello:

1. Mostramos los cambios provocados en el archivo `routes/web.php`
    ```bash
    git diff routes/web.php
    ```
1. Volvemos a la versión anterior del archivo `routes/web.php`
    ```bash
    git restore routes/web.php
    ```
1. Añadimos las líneas verdes obtenidas con el comando `git diff` anterior, excepto las correspondientes a la ruta `/`, al fichero `routes/web.php`. A esas líneas, debemos eliminar el carácter `+`.
1. Eliminar las rutas correspondientes a `login` y `logout` del fichero `routes/web.php`.

## Vistas

Al instalar _Laravel/Breeze_, también se generarán todas las vistas necesarias para realizar el _login_, _registro_ y para _recuperar la contraseña_. Todas estas vistas las podremos encontrar en la carpeta `resources/views/auth` con los nombres `login.blade.php` para el formulario de login, `register.blade.php` , etcétera. Estos nombres y rutas son obligatorios ya que los controladores que incluye _Laravel_ accederán a ellos, por lo que no deberemos cambiarlos.

> Aprovecha para incluir el logo de MarcaPersonalFP en las vistas de autenticación. Para ello, modifica el archivo `resources/views/components/application-logo.blade.php` con el siguiente código:
>
>```html
><img src="/images/mp-logo.png" width="64px">
>```

## Autenticación de un usuario

Una vez configurado todo el sistema, añadidas las rutas y las vistas para realizar el control de usuarios ya podemos utilizarlo. Si accedemos a la ruta _login_ nos aparecerá la vista con el formulario de login, solicitando nuestro email y contraseña para acceder. El campo tipo _checkbox_ llamado `remember` nos permitirá indicar si deseamos que la sesión permanezca abierta hasta que se cierre manualmente. Es decir, aunque se cierre el navegador y pasen varios días el usuario seguiría estando autorizado.

Si los datos introducidos son correctos se creará la sesión del usuario y se le redirigirá a la ruta `/dashboard`. Si queremos cambiar esta ruta tenemos que definir la constante `HOME` en el fichero `app/Providers/RouteServiceProvider.php`, por ejemplo:

```php
    public const HOME = '/proyectos';
```

## Registro de un usuario

Si accedemos a la ruta `register` nos aparecerá la vista con el formulario de registro, solicitándonos los campos nombre, email y contraseña. Al pulsar el botón de envío del formulario se llamará a la ruta `register` por `POST` y se almacenará el nuevo usuario en la base de datos.

Si no hemos añadido ningún campo más en la migración no tendremos que configurar nada más. **Sin embargo, si hemos añadido algún campo más a la tabla de usuarios, tendremos que actualizar el controlador `RegisteredUserController`: `validate` y `create`**. En la llamada a `validate()` simplemente tendremos que añadir dicho campo al _array_ de validaciones (solo en el caso que necesitemos validarlo). Y en la llamada al método `create()` tendremos que añadir los campos adicionales que deseemos almacenar. El código de este método es el siguiente:

```php
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
```

> También habrá que modificar el _array_ que devuelve el método `rules()` de `app/Http/Requests/ProfileUpdateRequest.php` si queremos poder modificar esos datos en el perfil del usuario.

Como podemos ver, utiliza el modelo de datos `User` para crear el usuario y almacenar las variables que recibe en el array de datos `$request`. En este _array_ de datos nos llegarán todos los valores de los campos del formulario, por lo tanto, si añadimos más campos al formulario y a la tabla de usuarios simplemente tendremos que añadirlos también en este método.

Es importante destacar que la contraseña se cifra usando el método `bcrypt()`, por lo tanto las contraseñas se almacenaran cifradas en la base de datos. Este cifrado se basa en la clave _hash_ que se genera al crear un nuevo proyecto de _Laravel_ (ver capítulo de "Instalación") y que se encuentra almacenada en el fichero `.env` en la variable `APP_KEY`. Es importante que este _hash_ se haya establecido al inicio (que no esté vacío o se uno por defecto) y que además no se modifique una vez la aplicación se suba a producción.

## Registro manual de un usuario

Si queremos añadir un usuario manualmente lo podemos hacer de forma normal usando el modelo `User` de _Eloquent_, replicando el código anterior.

## Acceder a los datos del usuario autenticado

Una vez que el usuario está autenticado podemos acceder a los datos del mismo a través del método `Auth::user()`, por ejemplo:

```php
$user = Auth::user();
```

Este método nos devolverá `null` en caso de que no esté autenticado. Si estamos seguros de que el usuario está autenticado (porque estamos en una ruta protegida) podremos acceder directamente a sus propiedades:

```php
$email = Auth::user()->email;
```

> Importante: para utilizar la clase `Auth` tenemos que añadir el espacio de nombres use `Illuminate\Support\Facades\Auth`, de otra forma nos aparecerá un error indicando que no puede encontrar la clase.

El usuario también se inyecta en los parámetros de entrada de la petición (en la clase `Request`). Por lo tanto, si en un método de un controlador usamos la inyección de dependencias también podremos acceder a los datos del usuario:

```php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }
...
```

## Cerrar la sesión

Si accedemos a la ruta `logout` por `POST` se cerrará la sesión y se redirigirá a la ruta `/`. Todo esto lo hará automáticamente el método `destroy()` del controlador AuthenticatedSessionController.

Para cerrar manualmente la sesión del usuario actualmente autenticado tenemos que utilizar el método:

```php
Auth::logout();
```

Posteriormente, podremos hacer una redirección a una página principal para usuarios no autenticados.

> **Importante:** para utilizar la clase `Auth` tenemos que añadir el espacio de nombres use `Illuminate\Support\Facades\Auth`, de otra forma nos aparecerá un error indicando que no puede encontrar la clase.

## Comprobar si un usuario está autenticado

Para comprobar si el usuario actual se ha autenticado en la aplicación podemos utilizar el método `Auth::check()` de la forma:

```php
if (Auth::check()) {
    // El usuario está correctamente autenticado
}
```

Sin embargo, lo recomendable es utilizar **Middleware** (como veremos a continuación) para realizar esta comprobación antes de permitir el acceso a determinadas rutas.

> **Importante:** Recuerda que para utilizar la clase `Auth` tenemos que añadir el espacio de nombres use `Illuminate\Support\Facades\Auth`, de otra forma nos aparecerá un error indicando que no puede encontrar la clase.

## Proteger rutas

El sistema de autenticación de _Laravel_ también incorpora una serie de filtros o _Middleware_ (ver carpeta `app/Http/Middleware` y el fichero `app/Http/Kernel.php`) para comprobar que el usuario que accede a una determinada ruta o grupo de rutas esté autenticado. En concreto, para proteger el acceso a rutas y solo permitir su visualización por usuarios correctamente autenticados usaremos el middleware `\Illuminate\Auth\Middleware\Authenticate.php` cuyo alias es `auth`. Para utilizar este _middleware_ tenemos que editar el fichero `routes/web.php` y modificar las rutas que queramos proteger, por ejemplo:

```php
// Para proteger una clausula:
Route::get('admin/proyectos', function() {
    // Solo se permite el acceso a usuarios autenticados
})->middleware('auth');

// Para proteger una acción de un controlador:
Route::get('profile', [ProfileController::class, 'show'])->middleware('auth');
```

Si el usuario que accede no está validado se generará una excepción que le redirigirá a la ruta `login`. Si deseamos cambiar esta dirección tendremos que modificar el método que gestiona la excepción por no estar autenticado.

Si deseamos proteger el acceso a toda una zona de nuestro sitio web (por ejemplo, la parte de administración o la gestión de un recurso), lo más cómodo es crear un grupo con todas esas rutas que utilice el middleware `auth`, por ejemplo:

```php
Route::group(['middleware' => 'auth'], function() {
    Route::get('proyectos', [ProyectosController::class, 'getIndex']);
    Route::get('proyectos/create', [ProyectosController::class, 'getCreate']);
    // ...
});
```
