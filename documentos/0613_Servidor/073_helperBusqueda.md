# Utilizar el cuadro de búsqueda

## Filtrando a través de las búsquedas

El _frontend_ dispone de un cuadro de búsqueda con el que poder filtrar los registros que deben mostrarse. Hasta ahora, no hemos hecho uso de ese parámetro de la petición en el _backend_.

El _frontend_ de React-admin lo tenemos configurado para que envíe el contenido de los cuadros de búsqueda en el parámetro `q`.

## Buscando a usuarios

Vamos a modificar el método `index()` de `UserController` para que, si se recibe el parámetro `q`, se busquen los usuarios cuyo nombre, apellidos, name o email contengan el valor de ese parámetro. Para ello, debemos modificar el método `index()` de `UserController` de la siguiente forma:

```php
    public function index(Request $request)
    {
        $query = User::query();
        if($busqueda) {
            $query->orWhere('name', 'like', '%' .$request->q . '%');
            $query->orWhere('nombre', 'like', '%' .$request->q . '%')
            $query->orWhere('apellidos', 'like', '%' .$request->q . '%')
            $query->orWhere('email', 'like', '%' .$request->q . '%');
        }

        return UserResource::collection(
            $query->orderBy($request->_sort ?? 'id', $request->_order ?? 'asc')
            ->paginate($request->perPage)
        );
    }
```
Para no tener que repetir 4 veces el método `orWhere`, podemos crear un _array_ con los campos que queramos consultar y recorrerlos con un `foreach`:

```php
    public function index(Request $request)
    {
        $campos = ['apellidos', 'nombre', 'name', 'email'];
        $query = User::query();
        foreach($campos as $campo) {
            $query->orWhere($campo, 'like', '%' . $request->q . '%');
        }
        return UserResource::collection(
            $query->orderBy($request->_sort ?? 'id', $request->_order ?? 'asc')
            ->paginate($request->perPage));
    }
```

## Refactorizando con un helper

Como la búsqueda que hemos implementado en `UserController` también la debemos implementar en el resto de controladores de la aplicación de forma parecida, utilizaremos un helper para que todos los controladores lo usen para filtrar. De este modo, podemos adaptar fácilmente el resultado de las búsquedas, en el caso de que los parámetros enviados por el _frontend_ cambien.

Para crear el _helper_ crearemos el fichero `app/Helpers/FilterHelper.php` con el siguiente contenido:

```php
<?php
namespace App\Helpers;

class FilterHelper
{
    public static function applyFilter($query, $filterValue, $filterColumns)
    {
        if ($filterValue) {
            $query->where(function ($query) use ($filterValue, $filterColumns) {
                foreach ($filterColumns as $column) {
                    $query->orWhere($column, 'like', '%' . $filterValue . '%');
                }
            });
        }
    }
}
```

En el método `index()` de `UserController` podemos utilizar el _helper_ de la siguiente forma:

```php
    public function index(Request $request)
    {
        $query = User::query();
        FilterHelper::applyFilter($query, $request->q, ['nombre', 'apellidos', 'name', 'email']);

        return UserResource::collection(
            $query->orderBy($request->_sort ?? 'id', $request->_order ?? 'asc')
            ->paginate($request->perPage)
        );
    }
```

Como podemos observar a continuación, el método `index()` de `CicloController` será muy parecido al anterior:

```php
    public function index(Request $request)
    {
        $query = Ciclo::query();
        FilterHelper::applyFilter($query, $request->q, ['nombre']);

        return CicloResource::collection(
            $query->orderBy($request->_sort ?? 'id', $request->_order ?? 'asc')
            ->paginate($request->perPage)
        );
    }
```

Evidentemente, los métodos `index()` del resto de controladores tan solo se diferenciarán en el **modelo** y el **recurso** que se utiliza para devolver los datos y en el _array_ de campos que se utiliza para filtrar.

## Obteniendo el objeto `$query` en el helper

Todavía podemos refactorizar un poco más:

- obteniendo el objeto `$query` en el helper, utilizando la propiedad $modelClassName que hemos definido en cada controlador.
- pasando el objeto `$request` completo al helper por si se modificara el nombre del parámetro que envía el _frontend_, de forma que pudiéramos modificarlo en un único lugar.

El código del helper quedaría de la siguiente forma:

```php
<?php
namespace App\Helpers;

class FilterHelper
{
    public static function applyFilter($request, $filterColumns)
    {
        $modelClassName = $request->route()->controller->modelclass;
        $query = $modelClassName::query();

        $filterValue = $request->q;
        if ($filterValue) {
            foreach ($filterColumns as $column) {
                    $query->orWhere($column, 'like', '%' . $filterValue . '%');
            }
        }
        return $query;
    }
}
```

mientras que el método `index()` de `CicloController` quedaría de la siguiente forma:

```php
    public function index(Request $request)
    {
        $query = FilterHelper::applyFilter($request, ['nombre']);

        return CicloResource::collection(
            $query->orderBy($request->_sort ?? 'id', $request->_order ?? 'asc')
            ->paginate($request->perPage)
        );
    }
```

## Ejercicios 

Se propone como ejercicios:

1. Utilizar el _helper_ desde todos los controladores.
2. Crear un nuevo método en la clase `FilterHelper` en la que se centralice la utilización de los parámetros `_sort` y `_order`.
3. Desarrollar otro método para obtener el valor que se debe asignar a la cabecera `X-Total-Count` de las respuestas, que contabilice únicamente los registros filtrados.
