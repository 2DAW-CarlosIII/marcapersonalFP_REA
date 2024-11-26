# 3.2. Middleware o filtros

Los componentes llamados **Middleware** son un mecanismo proporcionado por _Laravel_ para filtrar las peticiones HTTP que se realizan a una aplicación. Un **filtro** o **middleware** se define como una _clase PHP_ almacenada en un fichero dentro de la carpeta `app/Http/Middleware`. Cada **middleware** se encargará de aplicar un tipo concreto de filtro y de decidir que realizar con la petición realizada:

- permitir su ejecución,
- dar un error
- redireccionar a otra página en caso de no permitirla.

_Laravel_ incluye varios filtros por defecto, uno de ellos es el encargado de realizar la **autenticación de los usuarios**. Este filtro lo podemos aplicar sobre una ruta, un conjunto de rutas o sobre un controlador en concreto. Este middleware se encargará de filtrar las peticiones a dichas rutas: _en caso de estar logueado y tener permisos de acceso le permitirá continuar con la petición, y en caso de no estar autenticado lo redireccionará al formulario de login_.

_Laravel_ incluye middleware para gestionar la **autenticación**, el **modo mantenimiento**, la **protección contra CSRF**, y algunos más. Todos estos filtros los podemos encontrar en la carpeta `app/Http/Middleware`, y los podremos modificar o ampliar su funcionalidad. Pero además de estos podemos crear nuestros propios Middleware como veremos a continuación.

## Definir un nuevo Middleware

Para crear un nuevo Middleware podemos utilizar el comando de Artisan:

```bash
php artisan make:middleware MyMiddleware
```

Este comando creará la clase `MyMiddleware` dentro de la carpeta `app/Http/Middleware` con el siguiente contenido por defecto:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
```

El código generado por Artisan ya viene preparado para que podamos escribir directamente la implementación del filtro a realizar dentro de la función `handle()`. Como podemos ver, esta función solo incluye el valor de retorno con una llamada a `return $next($request);`, que lo que hace es continuar con la petición y ejecutar el método que tiene que procesarla. Como entrada la función `handle()` recibe dos parámetros:

- `$request`: En la cual nos vienen todos los parámetros de entrada de la petición.
- `$next`: El método o función que tiene que procesar la petición.

Por ejemplo, podríamos crear un filtro que redirija al `home` si se solicita una ruta que contenga un parámetro `id` con un valor superior a 9. En cualquier otro caso que le permita acceder a la ruta:

```php
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route()->hasParameter('id') && $request->route()->parameter('id') > 9) {
            return redirect('/');
        }

        return $next($request);
    }
```

Como hemos dicho antes, podemos hacer tres cosas con una petición:

- Si todo es correcto permitir que la petición continúe devolviendo: `return $next($request);`
- Realizar una redirección a otra ruta para no permitir el acceso con: `return redirect('/');`
- Lanzar una excepción o llamar al método abort para mostrar una página de error: `abort(403, 'Unauthorized action.');`

## Middleware antes o después de la petición

Para hacer que el código de un Middleware se ejecute antes o después de la petición HTTP simplemente tenemos que poner nuestro código antes o después de la llamada a `$next($request);`. Por ejemplo, el siguiente _Middleware_ realizaría la acción antes de la petición:

```php
public function handle($request, Closure $next)
{
    // Código a ejecutar antes de la petición

    return $next($request);
}
```

Mientras que el siguiente Middleware ejecutaría el código después de la petición:

```php
public function handle($request, Closure $next)
{
    $response = $next($request);

    // Código a ejecutar después de la petición

    return $response;
}
```

## Uso de Middleware

De momento, hemos visto para que vale y como se define un Middleware, en esta sección veremos como utilizarlos. Laravel permite la utilización de Middleware de cuatro formas distintas: 

1. [asociado a grupos](#middleware-asociado-a-grupos),
1. [global](#middleware-global),
1. [asociado a rutas o grupos de rutas](#middleware-asociado-a-rutas), y
1. [asociado a un controlador o a un método de un controlador](#middleware-dentro-de-controladores). 

En los cuatro casos será necesario registrar primero el Middleware en el fichero `bootstrap/app.php`.

### Middleware global

Para hacer que un Middleware se ejecute con todas las peticiones HTTP realizadas a una aplicación simplemente lo tenemos que añadir a la pila global de middlewares de la aplicación en el fichero `bootstrap/app.php`. Por ejemplo:

```php
use App\Http\Middleware\MyMiddleware;
 
->withMiddleware(function (Middleware $middleware) {
     $middleware->append(MyMiddleware::class);
})
```

Modifiquemos el código :

```php
    public function handle(Request $request, Closure $next): Response
    {
        $parametros = explode("/", $request->path());
        if (intval($parametros[count($parametros) - 1]) > 9) {
            // if ($request->route()->query('id') && $request->route()->parameter('id') > 9) {
            return redirect('/');
        }
        return $next($request);
    }
```
### Middleware asociado a grupos

_Laravel_ incluye los grupos de middlewares predefinidos `web` y `api` para facilitar la aplicación de los middlewares a las rutas definidas en el archivo `routes/web.php` y `routes/api.php` respectivamente. Estos grupos se definen en el fichero `bootstrap/app.php` y se pueden modificar o ampliar. Los middleware incluidos en cada uno de los grupso son los siguientes:

| The `web` Middleware Group |
| --- |
| `Illuminate\Cookie\Middleware\EncryptCookies` |
| `Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse` |
| `Illuminate\Session\Middleware\StartSession` |
| `Illuminate\View\Middleware\ShareErrorsFromSession` |
| `Illuminate\Foundation\Http\Middleware\ValidateCsrfToken` |
| `Illuminate\Routing\Middleware\SubstituteBindings` |


| The `api` Middleware Group |
| --- |
| `Illuminate\Routing\Middleware\SubstituteBindings` |

Si quisiéramos añadir a estos grupos, podríamos usar el método `appendToGroup` en el archivo `bootstrap/app.php` asociándolo al grupo `web` o `api`:

```php
    use App\Http\Middleware\MyMiddleware;

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', MyMiddleware::class);
    })
```

En este ejemplo hemos registrado la clase `MyMiddleware` como un middleware adicional al grupo `web`.

Al asociarlo a rutas, podemos buscar el valor del parámetro `id` con el objeto que devuelve el método `$request->route()`:

```php
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route()->hasParameter('id') && $request->route()->parameter('id') > 9) {
            return redirect('/');
        }
        return $next($request);
    }
```

### Middleware asociado a rutas

En el caso de querer que nuestro middleware se ejecute solo cuando se llame a una ruta o a un grupo de rutas, podremos invocar al método `middleware` cuando definimos la ruta. Por ejemplo:

```php
use App\Http\Middleware\MyMiddleware;
 
Route::get('/proyectos/show/{id}', function ($id) {
    // ...
})->middleware(MyMiddleware::class);
```

### Alias de Middleware

Para facilitar el uso de los middleware en las rutas, Laravel permite definir un alias o clave para cada uno de los middleware. Para ello, en el fichero `bootstrap/app.php` tendremos que registrar el alias asociado a la clase del Middleware:

```php
use App\Http\Middleware\MyMiddleware;
 
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'id_mayor_de_10' => MyMiddleware::class
    ]);
})
```

Una vez asignado el alias a nuestro middleware ya lo podemos utilizar en el fichero de rutas `routes\web.php` mediante la clave o nombre asignado, por ejemplo:

```php
use App\Http\Middleware\MyMiddleware;
 
Route::get('/proyectos/show/{id}', function ($id) {
    // ...
})->middleware(id_mayor_de_10);
```

> _Si hemos distribuido las rutas de los distintos controladores en ficheros diferenciados de rutas, deberemos buscar la ruta del ejemplo en el fichero de rutas correspondiente._

En el ejemplo anterior hemos asignado el middleware con clave `id_mayor_de_10` al grupo de rutas cuyo prefijo es `proyectos`. Si la petición supera el filtro entonces se ejecutara la función asociada.

Si queremos asociar varios middleware con una ruta simplemente tenemos que añadir un _array_ con los respectivos alias. Los filtros se ejecutarán en el orden indicado en dicho _array_:

```php
Route::prefix('proyectos')->middleware(['auth', 'id_mayor_de_10'])->group(function () {
    Route::get('/', ...
});
```

O sobre un controlador: 

```php
Route::get('profile', 'UserController@showProfile')->middleware('auth');
```

La siguiente es una lista de los alias de los middleware que vienen por defecto en Laravel:

| Alias | Middleware |
| --- | --- |
| `auth` | `Illuminate\Auth\Middleware\Authenticate` |
| `auth.basic` | `Illuminate\Auth\Middleware\AuthenticateWithBasicAuth` |
| `auth.session` | `Illuminate\Session\Middleware\AuthenticateSession` |
| `cache.headers` | `Illuminate\Http\Middleware\SetCacheHeaders` |
| `can` | `Illuminate\Auth\Middleware\Authorize` |
| `guest` | `Illuminate\Auth\Middleware\RedirectIfAuthenticated` |
| `password.confirm` | `Illuminate\Auth\Middleware\RequirePassword` |
| `precognitive` | `Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests` |
| `signed` | `Illuminate\Routing\Middleware\ValidateSignature` |
| `subscribed` | `\Spark\Http\Middleware\VerifyBillableIsSubscribed` |
| `throttle` | `Illuminate\Routing\Middleware\ThrottleRequests` or `Illuminate\Routing\Middleware\ThrottleRequestsWithRedis` |
| `verified` | `Illuminate\Auth\Middleware\EnsureEmailIsVerified` |


### Middleware dentro de controladores

También es posible indicar el middleware a utilizar desde dentro de un controlador. En este caso los filtros también tendrán que estar registrador en el fichero `bootstrap/app.php`. Para utilizarlos se recomienda realizar la asignación en el método `middleware()` del controlador. Podremos indicar que se filtren todos los métodos, solo algunos, o todos excepto los indicados, por ejemplo:

```php
class UserController extends Controller
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('log', only: ['index']),
            new Middleware('subscribed', except: ['store']),
        ];
    }
}
```

## Revisar los filtros asignados

Al crear una aplicación Web es importante asegurarse de que todas las rutas definidas son correctas y que las partes privadas realmente están protegidas. Para esto Laravel incluye el siguiente método de Artisan:

```bash
php artisan route:list -v
```

Este método muestra una tabla con todas las rutas, métodos y acciones. Ademas para cada ruta indica los filtros asociados, tanto si están definidos desde el fichero de rutas como desde dentro de un controlador. Por lo tanto es muy útil para comprobar que todas las rutas y filtros que hemos definido se hayan creado correctamente.

## Paso de parámetros

Un Middleware también puede recibir parámetros. Por ejemplo, podemos modificar nuestro filtro para definir el valor máximo que puede tener el `id`. Para esto, lo primero que tenemos que hacer es añadir un tercer parámetro a la función handle del Middleware:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $maximo): Response
    {
        if ($request->route()->hasParameter('id') && $request->route()->parameter('id') > $maximo) {
            return redirect('/');
        }
        return $next($request);
    }
}

```

En el código anterior de ejemplo se ha añadido el tercer parámetro `$maximo` a la función. Si nuestro filtro necesita recibir más parámetros simplemente tendríamos que añadirlos de la misma forma a esta función.

Para pasar un parámetro a un middleware en la definición de una ruta lo tendremos que añadir a continuación del nombre del filtro separado por dos puntos, por ejemplo:

```php
Route::get('proyectos/show/{id}', [ProyectosController::class, 'getShow'])
->middleware('id_mayor_de_10'.':5')
->where('id', '[0-9]+');
```

Si tenemos que pasar más de un parámetro al filtro los separaremos por comas.

Por ejemplo, imaginemos que modificamos el método `handle()` de nuestro middleware para que acepte un valor **mínimo** y **máximo** del parámetro `id`:

```php
    public function handle(Request $request, Closure $next, $minimo, $maximo): Response
    {
        if ($request->route()->hasParameter('id')
            && (
                $request->route()->parameter('id') < $minimo
                ||
                $request->route()->parameter('id') > $maximo
            )
        ) {
            return redirect('/');
        }
        return $next($request);
    }
```

En ese caso, enviaremos el valor de los dos parámetros separados por `,`:

```php
Route::get('proyectos/show/{id}', [ProyectosController::class, 'getShow'])
->middleware('id_mayor_de_10'.':2,6')
->where('id', '[0-9]+');
```
