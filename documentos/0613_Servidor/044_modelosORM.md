# 4.4. Modelos de datos mediante ORM

El _mapeado objeto-relacional_ (más conocido por su nombre en inglés, _Object-Relational Mapping_, o por sus siglas **ORM**) es una técnica de programación para convertir datos entre un lenguaje de programación orientado a objetos y una base de datos relacional como motor de persistencia. Esto posibilita el uso de las características propias de la orientación a objetos, podremos acceder directamente a los campos de un objeto para leer los datos de una base de datos o para insertarlos o modificarlos.

_Laravel_ incluye su propio sistema de **ORM** llamado _Eloquent_, el cual nos proporciona una manera elegante y fácil de interactuar con la base de datos. Para cada tabla de la base datos tendremos que definir su correspondiente modelo, el cual se utilizará para interactuar desde código con la tabla.

## Definición de un modelo

Por defecto los modelos se guardarán como clases _PHP_ dentro de la carpeta `app/Models`.

Para definir un modelo que use _Eloquent_ únicamente tenemos que crear una clase que herede de la clase `Model`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciclo extends Model
{
    use HasFactory;
}
```

Sin embargo es mucho más fácil y rápido crear los modelos usando el comando `make:model` de _Artisan_:

```bash
php artisan make:model Ciclo
```

Este comando creará el fichero `Ciclo.php` dentro de la carpeta `app/Models` con el código básico de un modelo que hemos visto en el ejemplo anterior.

## Convenios en Eloquent

### Nombre

En general, el nombre de los modelos se pone en singular, con la primera letra en mayúscula, mientras que el nombre de las tablas suele estar en plural. Gracias a esto, al definir un modelo no es necesario indicar el nombre de la tabla asociada, sino que _Eloquent_ automáticamente buscará la tabla transformando el nombre del modelo a minúsculas y buscando su plural (en inglés). En el ejemplo anterior, que hemos creado el modelo `Ciclo` buscará la tabla de la base de datos llamada `ciclos` y en caso de no encontrarla daría un error.

Si la tabla tuviese otro nombre lo podemos indicar usando la propiedad protegida `$table` del modelo:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciclo extends Model
{
    use HasFactory;

    protected $table = 'ciclos_formativos';
    // como, en nuestro caso, la tabla es ciclos, conviene no definir esta propiedad
    // ni las propiedades que se mostrarán a continuación
}
```

### Clave primaria

_Laravel_ también asume que cada tabla tiene declarada una _clave primaria_ con el nombre `id`. En el caso de que no sea así y queramos cambiarlo tendremos que sobrescribir el valor de la propiedad protegida `$primaryKey` del modelo, por ejemplo: `protected $primaryKey = 'codCiclo';`.

    Es importante definir correctamente este valor ya que se utiliza en determinados métodos de _Eloquent_, como por ejemplo para buscar registros o para crear las relaciones entre modelos.

### Timestamps

Otra propiedad que, en ocasiones, tendremos que establecer son los _timestamps_ automáticos. Por defecto, _Eloquent_ asume que todas las tablas contienen los campos `updated_at` y `created_at` (los cuales los podemos añadir muy fácilmente con `Schema` añadiendo `$table->timestamps()` en la migración). Estos campos se actualizarán automáticamente cuando se cree un nuevo registro o se modifique. En el caso de que no queramos utilizarlos (y que no estén añadidos a la tabla) tendremos que indicarlo en el modelo o de otra forma nos daría un error. Para indicar que no los actualice automáticamente tendremos que modificar el valor de la propiedad pública `$timestamps` a `false`, por ejemplo: `public $timestamps = false;`.

A continuación se muestra un ejemplo de un modelo de Eloquent en el que se añadirían todas las especificaciones que hemos visto:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciclo extends Model
{
    use HasFactory;

    protected $table = 'ciclos_formativos';
    protected $primaryKey = 'codCiclo'
    public $timestamps = false;
    // Recuerda que en nuestro modelo Ciclo no hay que redefinir estas propiedades
}
```

## Uso de un modelo de datos

Una vez creado el modelo, ya podemos empezar a utilizarlo para recuperar datos de la base de datos, para insertar nuevos datos o para actualizarlos. **El sitio correcto donde realizar estas acciones es en el controlador**, el cual se los tendrá que pasar a la vista ya preparados para su visualización.

Es importante que para su utilización indiquemos al inicio de la clase el _espacio de nombres_ del modelo o modelos a utilizar. Por ejemplo, si vamos a usar los modelos `User` y `Ciclo` tendríamos que añadir:

```php
use App\Models\User;
use App\Models\Ciclo;
```

Si queremos tener algunos registros en la tabla `ciclos`, podemos ejecutar el script _SQL_ que encontraremos en el fichero [`datosCiclos.sql`](./materiales/ejercicios-laravel/datosCiclos.sql) en la pestaña SQL de [phpMyAdmin](http://localhost:8081).

> Para que la clave ajena de `proyectos` hacia `ciclos` no genere un error, debemos deactivar la opción **Habilite la revisión de las claves foráneas** de phpMyAdmin.

Debemos tener en cuenta que la utilización de los modelos se realizará habitualmente desde los controladores. No obstante, con el único objetivo de hacer pruebas de utilización de los modelos, crearemos una ruta, en cuyo _Closure_ pondremos el código que utiliza el modelo.

Crea, por lo tanto, la siguiente ruta en el fichero `routes/web.php`:

```php
Route::get('pruebaDB', function () {
    // aquí irán el código que utiliza el modelo
});

```


## Consultar datos

Para obtener todas las filas de la tabla asociada a un modelo usaremos el método `all()`:

```php
    $ciclos = Ciclo::all();

    foreach( $ciclos as $ciclo ) {
        echo $ciclo->nombre . '<br />';
    }

```

Este método nos devolverá un _array_ de resultados, donde cada item del _array_ será una instancia del modelo `Ciclo`. Gracias a esto al obtener un elemento del _array_ podemos acceder a los campos o columnas de la tabla como si fueran propiedades del objeto ($ciclo->nombre).

    Nota: Todos los métodos que se describen en la sección de "Constructor de consultas" y en la documentación de _Laravel_ sobre "Query Builder" también se pueden utilizar en los modelos _Eloquent_. Por lo tanto podremos utilizar `where`, `orWhere`, `first`, `get`, `orderBy`, `groupBy`, `having`, `skip`, `take`, etc. para elaborar las consultas.

_Eloquent_ también incorpora el método find($id) para buscar un elemento a partir del identificador único del modelo, por ejemplo:

```php
$ciclo = Ciclo::find(1);
echo $ciclo->nombre;
```

Si queremos que se lance una excepción cuando no se encuentre un modelo podemos utilizar los métodos `findOrFail()` o `firstOrFail()`. Esto nos permite capturar las excepciones y mostrar un error `404` cuando sucedan.

```php
$ciclo = Ciclo::findOrFail(1001);
echo $ciclo->nombre;
```

```php
$ciclo = Ciclo::where('id', '>', 93)->firstOrFail();
echo $ciclo->nombre;
```

A continuación se incluyen otros ejemplos de consultas usando _Eloquent_ con algunos de los métodos que ya habíamos visto en la sección "Constructor de consultas":

```php
// Obtener 10 ciclos siguientes al de id 93
$ciclos = Ciclo::where('id', '>', 93)->take(10)->get();

    foreach( $ciclos as $ciclo ) {
        echo $ciclo->nombre . '<br />';
    }
```

```php
// Obtener el primer ciclo posterior al de id 93
$ciclo = Ciclo::where('votos', '>', 93)->first();
echo $ciclo->nombre;
```

También podemos utilizar los métodos agregados para calcular el **total** de registros obtenidos, o el **máximo**, **mínimo**, **media** o **suma** de una determinada columna. Por ejemplo:
```php
$count = Ciclo::where('id', '>', 93)->count();
$max = Ciclo::max('id');
$min = Ciclo::min('id');
$media = Ciclo::avg('id');
$total = Ciclo::sum('id');
echo "Total: $total<br />Media: $media<br />Mínimo: $min<br />Máximo: $max<br />Count: $count";
```

## Insertar datos

Para añadir una entrada en la tabla de la base de datos asociada con un modelo simplemente tenemos que crear una nueva instancia de dicho modelo, asignar los valores que queramos y por último guardarlos con el método `save()`:

```php
$count = Ciclo::where('id', '>', 93)->count();
echo 'Antes: ' . $count . '<br />';

$ciclo = new Ciclo;
$ciclo->nombre = 'Técnico Superior en Desarrollo d eAplicaciones Laravel';
$ciclo->codCiclo = 'DAPL3';
$ciclo->codFamilia = 'IFC';
$ciclo->grado = 'G.S.';
$ciclo->save();

$count = Ciclo::where('id', '>', 93)->count();
echo 'Después: ' . $count . '<br />';

```

Para obtener el identificador asignado en la base de datos después de guardar (cuando se trate de tablas con índice _auto-incremental_), lo podremos recuperar simplemente accediendo al campo `id` del objeto que habíamos creado, por ejemplo:

```php
$insertedId = $user->id;
```

## Actualizar datos

Para actualizar una instancia de un modelo es muy sencillo, solo tendremos que recuperar en primer lugar la instancia que queremos actualizar, a continuación modificarla y por último guardar los datos:

```php
$ciclo = Ciclo::find(1);
$ciclo->grado = 'G.M.';
$ciclo->save();
```

## Borrar datos

Para borrar una instancia de un modelo en la base de datos simplemente tenemos que usar su método `delete()`:

```php
$count = Ciclo::count();
echo 'Antes: ' . $count . '<br />';
$ciclo = Ciclo::find(1);
$ciclo->delete();

$count = Ciclo::count();
echo 'Después: ' . $count . '<br />';
```

Si, por ejemplo, queremos borrar un conjunto de resultados también podemos usar el método `delete()` de la forma:

```php
$affectedRows = Ciclo::where('id', '>', 100)->delete();
echo 'Ciclos eliminados: ' . $affectedRows . '<br />';
```

## Más información

Para más información sobre como crear relaciones entre modelos, _eager loading_, etc. podéis consultar directamente la documentación de Laravel en:

[http://laravel.com/docs/eloquent](http://laravel.com/docs/eloquent)
