# 3.1. Controladores

Hasta el momento, hemos visto solamente cómo devolver una cadena para una ruta y cómo asociar una vista a una ruta directamente en el fichero de rutas. Pero, en general, la forma recomendable de trabajar será **asociar dichas rutas a un método de un controlador**. Esto nos permitirá separar mucho mejor el código y crear clases (_controladores_) que agrupen toda la funcionalidad de un determinado recurso. Por ejemplo, _podemos crear un controlador para gestionar toda la lógica asociada al control de usuarios o cualquier otro tipo de recurso_.

Como ya vimos en la [sección de introducción](./01_introduccion.md), los _controladores_ son el punto de entrada de las peticiones de los usuarios y son los que **deben contener toda la lógica asociada al procesamiento de una petición**, encargándose de realizar las consultas necesarias a la base de datos, de preparar los datos y de llamar a la vista correspondiente con dichos datos.

## Controlador básico

Los controladores se almacenan en _ficheros PHP_ en la carpeta `app/Http/Controllers` y normalmente se les añade el sufijo _Controller_, por ejemplo `UserController.php` o `ProyectosController.php`. A continuación se incluye un ejemplo básico de un controlador almacenado en el fichero `app/Http/Controllers/UserController.php`:

```php
<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Mostrar información de un usuario.
     * @param  string  $nombre
     * @return Response
     */
    public function showProfile($nombre)
    {
        return view('user.profile', ['user' => $nombre]);
    }
}
```

Todos los controladores tienen que extender la clase base `Controller`. Esta clase viene ya creada por defecto con la instalación de _Laravel_, encontrándose en la carpeta `app/Http/Controllers`. Se utiliza para centralizar toda la lógica que se vaya a utilizar de forma compartida por los controladores de nuestra aplicación. Por defecto, solo carga código para _validación_ y _autorización_, pero podemos añadir en la misma todos los métodos que necesitemos.

El código del ejemplo anterior, el método `showProfile($nombre)` únicamente se encarga de obtener los datos de un usuario, generar la vista `user/profile.blade.php` a partir de los datos obtenidos y devolverla como valor de retorno para que se muestre por pantalla.

Una vez definido un _controlador_, ya podremos asociarlo a una _ruta_. Para esto tenemos que modificar el fichero de rutas `routes.php` de la forma:

```php
use App\Http\Controllers\UserController;

Route::get('user/{nombre}', [UserController::class, 'showProfile']);
```

En lugar de pasar una función como segundo parámetro, tenemos que escribir un _array_ que contenga la **_clase del controlador_** y el **_nombre del método_** que queremos asociar. No es necesario añadir nada más, ni los parámetros que recibe el método en cuestión, todo esto se hace de forma automática.

## Crear un nuevo controlador

Como hemos visto, los controladores se almacenan dentro de la carpeta `app/Http/Controllers` como ficheros _PHP_. Para crear uno nuevo, bien lo podemos hacer a mano y rellenar nosotros todo el código, o podemos utilizar el siguiente comando de _Artisan_ que nos facilitará todo el trabajo:

```bash
php artisan make:controller ProyectosController
```

Este comando creará el controlador `ProyectosController` dentro de la carpeta `app/Http/Controllers` y lo completará con el código básico que hemos visto antes.

## Controladores y espacios de nombres

También podemos crear _sub-carpetas_ dentro de la carpeta `Controllers` para organizarnos mejor. En este caso, la estructura de carpetas que creemos no tendrá nada que ver con la ruta asociada a la petición y, de hecho, a la hora de hacer referencia al controlador únicamente tendremos que hacerlo a través de su espacio de nombres.

Como hemos visto al referenciar el _controlador_ en el fichero de rutas únicamente tenemos que indicar su nombre y no toda la ruta ni el espacio de nombres `App\Http\Controllers`. Esto es porque el servicio encargado de cargar las rutas añade automáticamente el espacio de nombres raíz para los controladores. Si metemos todos nuestros controladores dentro del mismo espacio de nombres no tendremos que añadir nada más. Pero si decidimos crear sub-carpetas y organizar nuestros controladores en sub-espacios de nombres, entonces sí que tendremos que añadir esa parte.

Por ejemplo, si creamos un controlador en `App\Http\Controllers\Photos\AdminController`, entonces para registrar una ruta hasta dicho controlador tendríamos que hacer:

```php
use App\Http\Controllers\Photos\AdminController;

Route::get('foo', [AdminController::class, 'method']);
```

## Generar una URL a una acción

Para generar la URL que apunte a una acción de un controlador podemos usar el método action de la forma:

```php
$url = action([FooController::class, 'method']);
```

Por ejemplo, para crear en una plantilla con Blade un enlace que apunte a una acción haríamos:

{% raw %}
```php
<a href="{{ action([FooController::class, 'method']) }}">¡Aprieta aquí!</a>
```
{% endraw %}

## Caché de rutas

Si definimos todas nuestras rutas para que utilicen controladores podemos aprovechar la funcionalidad para crear una _caché de las rutas_. Es importante que estén basadas en controladores porque si definimos respuestas directas desde el fichero de rutas (como vimos en el capítulo anterior) la caché no funcionará.

Gracias a la caché Laravel indican que se puede acelerar el proceso de registro de rutas hasta 100 veces. Para generar la caché simplemente tenemos que ejecutar el comando de Artisan:

```bash
php artisan route:cache
```

Si creamos más rutas y queremos añadirlas a la caché simplemente tenemos que volver a lanzar el mismo comando. Para borrar la caché de rutas y no generar una nueva caché tenemos que ejecutar:

```bash
php artisan route:clear
```

La caché se recomienda crearla solo cuando ya vayamos a pasar a producción nuestra web. Cuando estamos trabajando en la web es posible que añadamos nuevas rutas y sino nos acordamos de regenerar la caché la ruta no funcionará.
