# 5.7. _Middleware_ y control de usuarios con _Breeze_
<!-- {% raw %} -->

## De `exigirLogin()` al _middleware_

En 4.2, cada página privada de _v0_ empezaba igual:

```php
$usuario = exigirLogin();
```

Funcionaba, pero tenía un punto débil: la protección dependía de que **cada página** recordara llamar a la función. Una página nueva en la que se olvidara esa línea quedaba abierta a cualquiera, y nada avisaba del olvido.

_Laravel_ saca esa comprobación de las páginas y la pone **alrededor** de ellas: en un **_middleware_**. Las rutas declaran qué _middleware_ las protege, y `php artisan route:list -v` muestra de un vistazo qué rutas están protegidas y cuáles no.

## Qué es un _middleware_

En el ciclo de vida de una petición (5.2), el _middleware_ son las capas que la petición atraviesa antes de llegar al controlador, y que la respuesta atraviesa de vuelta. Ya hemos usado varios sin saberlo: el que abre la sesión, el que comprueba el _token_ CSRF (5.6) o el que comparte `$errors` con las vistas.

Cada _middleware_ es una clase con un método `handle()`:

```php
public function handle(Request $request, Closure $next): Response
{
    // ...
    return $next($request);
}
```

- **`$request`** es la petición.
- **`$next`** es "lo que viene después": el siguiente _middleware_ y, al final, el controlador. Llamar a `$next($request)` deja pasar la petición, y devuelve la respuesta que se ha generado.

Con una petición, un _middleware_ puede hacer tres cosas:

| Decisión | Código |
|---|---|
| Dejarla pasar | `return $next($request);` |
| Desviarla a otra página | `return redirect()->route('login');` |
| Cortarla con un error | `abort(403);` |

<!-- DIAGRAMA: la petición atraviesa las capas de middleware (sesión, CSRF, auth) hasta el controlador y la respuesta vuelve por ellas; con auth sin sesión, la capa auth devuelve una redirección a /login sin llegar al controlador -->

### _Middleware_ antes o después de la petición

El código que va **antes** de `$next($request)` se ejecuta antes de que la petición llegue al controlador; el que va **después**, con la respuesta ya generada:

```php
public function handle(Request $request, Closure $next): Response
{
    // Antes: se puede examinar la petición y decidir si continúa.

    $response = $next($request);

    // Después: se puede examinar o modificar la respuesta.

    return $response;
}
```

`exigirLogin()` actuaba antes: si no había usuario, redirigía y la página no llegaba a ejecutarse. El _middleware_ que abre la sesión actúa en los dos momentos: la abre antes y la guarda después.

## Un _middleware_ propio

Se crean con _Artisan_:

```bash
php artisan make:middleware CabeceraCentro
```

Que genera `app/Http/Middleware/CabeceraCentro.php`. Este, por ejemplo, actúa **después**: añade a cada respuesta una cabecera HTTP con el nombre del centro de la configuración de 5.2.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CabeceraCentro
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Centro', config('marcapersonalfp.centro'));

        return $response;
    }
}
```

### Dónde se aplica

Un _middleware_ no hace nada hasta que se aplica. Hay tres formas:

1. **A todas las peticiones** (_middleware_ global), en `bootstrap/app.php`.
2. **A un grupo de _middleware_**. Todas las rutas de `routes/web.php`, y las de los ficheros que este incluye, pertenecen al grupo **`web`**. Se añade también en `bootstrap/app.php`:

    ```php
    use App\Http\Middleware\CabeceraCentro;

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            CabeceraCentro::class,
        ]);
    })
    ```

3. **A rutas concretas**, con `->middleware()`, sobre una ruta o un grupo:

    ```php
    Route::get('perfil/{id?}', PerfilController::class)->middleware(CabeceraCentro::class);
    ```

El grupo `web` ya trae estos _middleware_, que se ejecutan en este orden:

| _Middleware_ | Qué hace |
|---|---|
| `EncryptCookies` | Cifra las _cookies_ con la `APP_KEY` (5.1) |
| `AddQueuedCookiesToResponse` | Añade a la respuesta las _cookies_ pendientes |
| `StartSession` | Abre la sesión y la guarda al final (5.2) |
| `ShareErrorsFromSession` | Pasa `$errors` a las vistas (5.6) |
| `PreventRequestForgery` | Comprueba el _token_ CSRF (5.6). Hasta _Laravel_ 12 se llamaba `ValidateCsrfToken` |
| `SubstituteBindings` | Prepara los parámetros de las rutas para el controlador |

### Alias

Los _middleware_ de uso frecuente tienen un **alias**, un nombre corto que se usa en las rutas en lugar de la clase. Estos son los que usaremos:

| Alias | Qué comprueba |
|---|---|
| `auth` | Que haya un usuario identificado. Si no, redirige al _login_ |
| `guest` | Lo contrario: que **no** lo haya. Para las páginas de _login_ y registro |
| `verified` | Que el usuario haya verificado su correo electrónico |
| `can` | Que el usuario tenga permiso para algo. Lo veremos en el Bloque 7 |

Algunos aceptan **parámetros**, después de dos puntos: `->middleware('can:update,proyecto')`.

## _Breeze_: el control de usuarios, ya hecho

En 4.2 programamos a mano el _login_ de _v0_: un array de usuarios, `password_hash()`, una clase `Autenticador`, `session_regenerate_id()` y el _logout_ por `POST`. Una aplicación real necesita además registrarse, recuperar la contraseña olvidada, verificar el correo, limitar los intentos fallidos… Escribirlo todo, y sin errores de seguridad, es mucho trabajo.

**_Laravel Breeze_** es un paquete oficial que lo genera por nosotros: rutas, controladores, vistas y tests del control de usuarios, con _Blade_ y _Tailwind_. Lo usaremos como una **caja negra**: lo instalamos, comprobamos qué hace por nosotros y aprendemos dónde está cada pieza. Los usuarios se guardan en la tabla `users`, que ya existe en nuestra base _SQLite_ desde 5.1.

> **Antes de instalarlo, haz un _commit_.** `breeze:install` **sobrescribe** varios ficheros del proyecto, entre ellos `routes/web.php`, `resources/css/app.css` y la configuración de _Vite_. Con todo guardado en _git_, `git diff` te mostrará qué ha cambiado y podrás recuperar lo tuyo.

### Instalación

En el terminal de `workspace`:

```bash
composer require laravel/breeze:^2.4 --dev
php artisan breeze:install
```

`breeze:install` hace tres preguntas. Respóndelas así:

| Pregunta | Respuesta |
|---|---|
| _Which Breeze stack would you like to install?_ | _Blade with Alpine_ |
| _Would you like dark mode support?_ | _No_ |
| _Which testing framework do you prefer?_ | _PHPUnit_ |

Al terminar, compila los estilos y comprueba las tablas:

```bash
npm install
npm run build
php artisan migrate
```

`migrate` no debería crear nada: la tabla `users` ya existía. Si dejaste `npm run build -- --watch` en otro terminal, detenlo y vuelve a lanzarlo, porque _Breeze_ ha cambiado la configuración de _Tailwind_.

### Recuperar nuestras rutas

_Breeze_ ha sustituido `routes/web.php` por el suyo. Compáralo con el que teníamos:

```bash
git diff routes/web.php
```

Las líneas en rojo son nuestras y las verdes, de _Breeze_. El fichero tiene que quedar con las dos cosas: nuestras rutas, y las de _Breeze_ salvo su ruta `/`, que mostraba la página de bienvenida. Queda así:

```php
<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Nuestras rutas (5.3 a 5.6)
Route::get('/', HomeController::class);
// ... /hola, /centro, perfil ...
require __DIR__ . '/proyectos.php';

// Las rutas de Breeze
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
```

La última línea incluye `routes/auth.php`, el fichero con las rutas del _login_, el registro y el _logout_. Compruébalas con `php artisan route:list --path=login`.

### Qué ha añadido _Breeze_

| Pieza | Dónde |
|---|---|
| Rutas del control de usuarios | `routes/auth.php` |
| Controladores | `app/Http/Controllers/Auth/` y `ProfileController.php` |
| _Form Requests_ | `app/Http/Requests/Auth/LoginRequest.php` y `ProfileUpdateRequest.php` |
| Vistas | `resources/views/auth/` (_login_, registro…), `profile/` y `dashboard.blade.php` |
| _Layouts_ y componentes | `resources/views/layouts/` y varios componentes en `components/` |
| Tests | `tests/Feature/Auth/` y `ProfileTest.php` |

Las vistas de _Breeze_ usan sus propios _layouts_, `<x-app-layout>` y `<x-guest-layout>`, construidos igual que nuestro `<x-layout>` de 5.4. Para que muestren nuestro logo, cambia el contenido de `resources/views/components/application-logo.blade.php` por:

```blade
<img src="{{ asset('images/mp-logo.png') }}" alt="marcapersonalFP" {{ $attributes }}>
```

## Lo que _Breeze_ automatiza

Cada pieza de nuestro _login_ de 4.2 tiene su equivalente en el código que ha generado _Breeze_:

| En _v0_ (4.2) | Con _Breeze_ | Dónde |
|---|---|---|
| El array de usuarios | La tabla `users` y el modelo `User` | `app/Models/User.php` |
| `password_hash()` | `Hash::make()` | `RegisteredUserController::store()` |
| `Autenticador::autenticar()` | `Auth::attempt()` | `LoginRequest::authenticate()` |
| `session_regenerate_id()` | `$request->session()->regenerate()` | `AuthenticatedSessionController::store()` |
| `usuarioActual()` | `Auth::user()` o `$request->user()` | En cualquier controlador o vista |
| _Logout_ por `POST` | `Auth::guard('web')->logout()`, más invalidar la sesión | `AuthenticatedSessionController::destroy()` |
| `exigirLogin()` | El _middleware_ `auth` | En las rutas |

Y lo que en _v0_ no llegamos a hacer:

- **Registro** de usuarios nuevos y **recuperación de la contraseña** por correo electrónico.
- **Límite de intentos**: tras cinco contraseñas incorrectas seguidas, el _login_ se bloquea durante un minuto.
- **Recordarme**: una _cookie_ que mantiene la sesión abierta aunque se cierre el navegador.
- **Volver a donde ibas**: si una página protegida te manda al _login_, al entrar vuelves a esa página y no al principio (`redirect()->intended()`).

Los roles (alumno o docente) y la propiedad de los datos (que cada alumno solo edite su currículo, como `exigirRol()` en 4.4) son **autorización**, y llegarán en el Bloque 7. Los campos propios de la tabla `users`, como los apellidos, en el Bloque 6.

## Proteger rutas con `auth`

El _middleware_ `auth` se aplica como cualquier otro. Sobre una ruta:

```php
Route::get('proyectos/create', [ProyectosController::class, 'create'])->middleware('auth');
```

O, mejor, sobre un grupo, para no olvidar ninguna ruta. En `routes/proyectos.php`, las rutas de consulta pueden seguir siendo públicas, y las que crean o modifican quedan protegidas:

```php
Route::controller(ProyectosController::class)->prefix('proyectos')->name('proyectos.')->group(function () {
    // Públicas
    Route::get('/', 'index')->name('index');
    Route::get('show/{id}', 'show')->whereNumber('id')->name('show');

    // Solo para usuarios identificados
    Route::middleware('auth')->group(function () {
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('edit/{id}', 'edit')->whereNumber('id')->name('edit');
        Route::put('edit/{id}', 'update')->whereNumber('id')->name('update');
    });
});
```

Si alguien sin sesión pide una de esas rutas, `auth` lo redirige a `/login`. Es lo que hacía `exigirLogin()`, pero declarado en un único sitio.

## El usuario en las vistas

En _Blade_, las directivas **`@auth`** y **`@guest`** muestran su contenido solo si hay un usuario identificado, o si no lo hay. Y `auth()->user()` es el usuario, o `null`:

```blade
@auth
    <span class="text-gray-600">{{ auth()->user()->name }}</span>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="text-gray-600 hover:text-gray-900">Salir</button>
    </form>
@endauth

@guest
    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Entrar</a>
    <a href="{{ route('register') }}" class="text-gray-600 hover:text-gray-900">Registrarse</a>
@endguest
```

El _logout_ sigue siendo un formulario `POST` con `@csrf`, como en 4.2: un enlace `GET` podría dispararlo cualquier otra página.

## Preparación del entorno de trabajo

Seguimos en el proyecto `marcapersonalfp`, con los formularios de [5.6](./RA5_6_formularios.md). Crea la rama del apartado en el terminal de la máquina virtual:

```bash
git switch main
git pull upstream main
git switch -c RA5_7
```

## Ejercicios

1. **Un _middleware_ propio.** Crea el _middleware_ `CabeceraCentro` de este apartado y añádelo al grupo `web` en `bootstrap/app.php`. Comprueba en la pestaña **Red** de las herramientas de desarrollo que todas las respuestas llevan la cabecera `X-Centro`.

2. **Instala _Breeze_.** Haz un _commit_ con todo lo anterior, instala _Breeze_ y recupera tus rutas. Cambia el logo de `application-logo.blade.php`. Comprueba que siguen funcionando tus páginas y que existen `/login` y `/register`.

3. **Ana López se registra.** En `http://marcapersonalfp.test/register`, registra a la alumna de 4.2: Ana López, `ana.lopez@marcapersonalfp.test`, con la contraseña `ana-secreta`. Cierra la sesión y vuelve a entrar. Después, en `php artisan tinker`, busca su contraseña guardada:

    ```php
    App\Models\User::first()->password
    ```

    ¿Empieza igual que los _hashes_ que generaste en 4.2? ¿Por qué?

4. **Protege las altas y las ediciones.** En `routes/proyectos.php`, aplica `auth` a las rutas `create`, `store`, `edit` y `update`, como en este apartado. El listado y el detalle siguen siendo públicos.

5. **La barra de navegación.** Añade a `components/navbar.blade.php`, alineados a la derecha (`ml-auto`), los enlaces "Entrar" y "Registrarse" para los invitados, y el nombre del usuario y el botón "Salir" para los usuarios identificados, como en este apartado.

6. **Investiga.** Responde en tu _pull request_:
    - Sin sesión, pide `/proyectos/edit/3`. ¿A dónde te lleva? Entra como Ana: ¿a qué página vuelves? Busca en `AuthenticatedSessionController` la línea responsable.
    - Escribe mal la contraseña de Ana seis veces seguidas. ¿Qué mensaje aparece? Busca en `LoginRequest` dónde se decide.
    - Ejecuta `php artisan route:list -v --path=proyectos`. ¿Qué rutas tienen el _middleware_ `auth`?

> **Puente.** En _v0_, cada página privada tenía que acordarse de llamar a `exigirLogin()`, y el _login_, el _hash_ de la contraseña y la regeneración de la sesión los escribimos nosotros. En _Laravel_, el _middleware_ `auth` protege grupos enteros de rutas desde un solo sitio, y _Breeze_ genera el control de usuarios completo, con registro, recuperación de contraseña y límite de intentos. Esto que hicimos a mano, el framework lo automatiza así.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1, 2, 4 y 5, copia el archivo [RA5_7_BreezeTest.php](./materiales/ejercicios-laravel/tests/Feature/RA5_7_BreezeTest.php) a la carpeta `tests/Feature/` de tu proyecto y ejecuta, en el terminal de `workspace`, ese test en concreto:

```bash
php artisan test --filter=RA5_7_BreezeTest
```

El test comprueba:
- la cabecera `X-Centro` de tu _middleware_;
- que _Breeze_ está instalado, con sus rutas de _login_ y registro;
- que un invitado no puede dar de alta ni editar proyectos, pero sí ver el listado y el detalle;
- que un usuario identificado sí puede;
- la barra de navegación, con y sin sesión.

Si todo está bien: `Tests: 6 passed (20 assertions)`.

> Los tests de 5.3 a 5.6 entran a las páginas de alta y edición como un usuario identificado, así que siguen pasando con las rutas protegidas.

---

**Siguiente:** [5.8. Proyecto: _marcapersonalFP_ en _Laravel_. POO y patrones de diseño](./RA5_8_proyecto.md)
<!-- {% endraw %} -->
