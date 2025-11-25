# 5.1. Datos de entrada

_Laravel_ facilita el acceso a los datos de entrada del usuario a través de solo unos pocos métodos. No importa el tipo de petición que se haya realizado (`POST`, `GET`, `PUT`, `DELETE`), si los datos son de un formulario o si se han añadido a la query string, en todos los casos se obtendrán de la misma forma.

Para conseguir acceso a estos métodos _Laravel_ utiliza inyección de dependencias. Esto es simplemente añadir la clase `Request` al constructor o método del controlador en el que lo necesitemos. _Laravel_ se encargará de inyectar dicha dependencia ya inicializada y directamente podremos usar este parámetro para obtener los datos de entrada. A continuación se incluye un ejemplo:

```php
<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
 
class ProyectosController extends Controller
{
    ...
    public function putEdit(Request $request)
    {
        $nombre = $request->input('nombre');
        return 'El nuevo nombre sería' . $nombre;
    }
}
```


En este ejemplo, como se puede ver, se ha añadido la clase `Request` como parámetro al método `putEdit()`. _Laravel_ automáticamente se encarga de inyectar estas dependencias, por lo que podemos usar la variable `$request` para obtener los datos de entrada.

Para poder probarlo, necesitamos adaptar el `action` del formulario de la vista `proyectos.edit` y añadir una nueva ruta en el archivo `routes/web.php`:

```php
    Route::put('edit/{id}', [ProyectosController::class, 'putEdit'])->where('id', '[0-9]+');
```

Si el método del controlador tuviera más parámetros (como en nuestro caso) simplemente los tendremos que añadir a continuación de las dependencias, por ejemplo:

```php
    public function putEdit(Request $request, $id)
    {
        $nombre = $request->input('nombre');
 
        // Almacenar el proyecto ...
        $proyectos = self::$arrayProyectos;
        $proyectos[$id]['nombre'] = $nombre;
        return view('proyectos.edit')
            ->with('proyecto', $proyectos[$id]);
    }
```

A continuación veremos los métodos y datos que podemos obtener a partir de la variable `$request`.

## Obtener los valores de entrada

Para obtener el valor de una variable de entrada usamos el método `input()` indicando el nombre de la variable:

```php
$name = $request->input('nombre');

// O simplemente....
$name = $request->nombre;
```

También podemos especificar un valor por defecto como segundo parámetro:

```php
$name = $request->input('nombre', 'Pedro');
```

## Comprobar si una variable existe

Si lo necesitamos podemos comprobar si un determinado valor existe en los datos de entrada:

```php
if ($request->has('nombre'))
{
    //...
}
```

## Obtener datos agrupados

O también podemos obtener todos los datos de entrada a la vez (en un _array_) o solo algunos de ellos:

```php
// Obtener todos: 
$input = $request->all();

// Obtener solo los campos indicados: 
$input = $request->only(['username', 'password']);

// Obtener todos excepto los indicados: 
$input = $request->except(['credit_card']);
```

## Obtener datos de un _array_

Si la entrada proviene de un `input` tipo _array_ de un formulario (por ejemplo una lista de _checkbox_), si queremos podremos utilizar la siguiente notación con puntos para acceder a los elementos del _array_ de entrada:

```php
$input = $request->input('products.0.name');
```

## JSON

Si la entrada está codificada formato _JSON_ (es bastante común cuando nos comunicamos a través de una _API_) también podremos acceder a los diferentes campos de los datos de entrada de forma normal (con los métodos que hemos visto, por ejemplo: `$nombre = $request->input('nombre');`).

## Ficheros de entrada

_Laravel_ facilita una serie de clases para trabajar con los ficheros de entrada. Por ejemplo, para obtener un fichero que se ha enviado en el campo con nombre `photo` y guardarlo en una variable, tenemos que hacer:

```php
$file = $request->file('photo');

// O simplemente...
$file = $request->photo;
```

Si queremos podemos comprobar si un determinado campo tiene un fichero asignado:

```php
if ($request->hasFile('photo')) {
    //...
}
```

El objeto que recuperamos con `$request->file()` es una instancia de la clase `Symfony\Component\HttpFoundation\File\UploadedFile`, la cual extiende la clase de _PHP_ [`SplFileInfo`](http://php.net/manual/es/class.splfileinfo.php), por lo tanto, tendremos muchos métodos que podemos utilizar para obtener datos del fichero o para gestionarlo.

Por ejemplo, para comprobar si el fichero que se ha subido es válido:

```php
if ($request->file('photo')->isValid()) {
    //...
}
```

_Laravel_ nos permite manejar, de la misma forma, el almacenamiento en local, en _Amazon S3_ y en _Rackspace Cloud Storage_, simplemente lo tenemos que configurar en `config/filesystems.php` y, posteriormente, los podremos usar de la misma forma. Por ejemplo, para almacenar un fichero subido mediante un formulario tenemos que usar el método `store()` indicando como parámetro la ruta donde queremos almacenar el fichero (sin el nombre del fichero):

```php
$path = $request->photo->store('images');
$path = $request->photo->store('images', 's3');  // Especificar un almacenamiento
```

Estos métodos devolverán el _path_ hasta el fichero almacenado de forma relativa a la raíz de disco configurada. Para el nombre del fichero se generará automáticamente un **UUID** (identificador único universal). Si queremos especificar nosotros el nombre tendríamos que usar el método `storeAs()`:

```php
$path = $request->photo->storeAs('images', 'filename.jpg');
$path = $request->photo->storeAs('images', 'filename.jpg', 's3');
```

Otros métodos que podemos utilizar para recuperar información del fichero son:

```php
// Obtener la ruta:
$path = $request->file('photo')->getRealPath();

// Obtener el nombre original:
$name = $request->file('photo')->getClientOriginalName();

// Obtener la extensión: 
$extension = $request->file('photo')->getClientOriginalExtension();

// Obtener el tamaño: 
$size = $request->file('photo')->getSize();

// Obtener el MIME Type:
$mime = $request->file('photo')->getMimeType();

```
