# 4.6. Constructor de consultas (Query Builder)

_Laravel_ incluye una serie de clases que nos facilita la construcción de consultas y otro tipo de operaciones con la base de datos. Además, al utilizar estas clases, creamos una notación mucho más legible, compatible con todos los tipos de bases de datos soportados por _Laravel_ y que nos previene de cometer errores o de ataques por **inyección de código SQL**.

## Consultas

Para realizar una `SELECT` que devuelva todas las filas de una tabla utilizaremos el siguiente código:

```php
$estudiantes = DB::table('estudiantes')->get();

foreach ($estudiantes as $estudiante)
{
    echo $estudiante->nombre;
}
```

En el ejemplo anterior se utiliza el constructor `DB::tabla` indicando el nombre de la tabla sobre la que se va a realizar la consulta, y por último se llama al método `get()` para obtener todas las filas de la misma.

Si queremos obtener un solo elemento podemos utilizar `first()` en lugar de `get()`, de la forma:

```php
$estudiante = DB::table('estudiantes')->first();

echo $estudiante->nombre;
```

## Método `where()`

Para filtrar los datos usamos el método `where()`, indicando el nombre de la columna y el valor a filtrar:

```php
$estudiantes = DB::table('estudiantes')->where('nombre', 'Pedro')->get();

foreach ($estudiantes as $estudiante)
{
    echo $estudiante->nombre;
}
```

En este ejemplo, el método `where()` filtrará todas las filas cuya columna `nombre` sea igual a `Pedro`. Si queremos realizar otro tipo de filtrados, como columnas que tengan un valor mayor (`>`), mayor o igual (`>=`), menor (`<`), menor o igual (`<=`), distinto del indicado (`<>`) o usar el operador `like`, lo podemos indicar como segundo parámetro de la forma:

```php
$estudiantes = DB::table('estudiantes')->where('votos', '>', 100)->get();

$estudiantes = DB::table('estudiantes')->where('confirmado', '<>', true)->get();

$estudiantes = DB::table('estudiantes')->where('nombre', 'like', 'T%')->get();
```
Si añadimos más clausulas where a la consulta por defecto se unirán mediante el operador lógico `AND`. En caso de que queramos utilizar el operador lógico `OR` lo tendremos que realizar usando `orWhere` de la forma:
```php
$estudiantes = DB::table('estudiantes')
                    ->where('votos', '>', 100)
                    ->orWhere('nombre', 'Pedro')
                    ->get();
```

## orderBy / groupBy / having_

También podemos utilizar los métodos `orderBy`, `groupBy` y `having` en las consultas:
```php
$estudiantes = DB::table('estudiantes')
                    ->orderBy('nombre', 'desc')
                    ->groupBy('count')
                    ->having('count', '>', 100)
                    ->get();
```

## Offset / Limit

Si queremos indicar un `offset` o `limit` lo realizaremos mediante los métodos `skip` (para el `offset`) y `take` (para `limit`), por ejemplo:

```php
$estudiantes = DB::table('estudiantes')->skip(10)->take(5)->get();
```

## Transacciones

_Laravel_ también permite crear transacciones sobre un conjunto de operaciones:

```php
DB::transaction(function()
{
    DB::table('estudiantes')->update(array('votos' => 1));

    DB::table('users')->delete();
});
```

En caso de que se produzca cualquier excepción en las operaciones que se realizan en la transacción se desharían todos los cambios aplicados hasta ese momento de forma automática.

## Más informacion

Para más información sobre la construcción de _Querys_ (`join`, `insert`, `update`, `delete`, _agregados_, etc.) podéis consultar la documentación de _Laravel_ en su sitio web:

[http://laravel.com/docs/queries](http://laravel.com/docs/queries)
