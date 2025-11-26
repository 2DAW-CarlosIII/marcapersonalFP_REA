# 2.7 Ejercicios Vistas

## Crear las vistas asociadas a las rutas definidas

En este apartado, vamos terminar una primera versión estable de la web. En primer lugar, crearemos las vistas asociadas a cada ruta, las cuales tendrán que extender del _layout_ que hemos hecho en el apartado anterior y mostrar (en la sección `content` del _layout_) el texto de ejemplo que habíamos definido [para cada ruta](./023_rutas.md#ejercicios). En general, todas las vistas tendrán un código similar al siguiente (variando únicamente la sección `content`):

```php
@extends('layouts.master')

@section('content')

    Pantalla principal

@stop
```

Para organizar mejor las vistas las vamos a agrupar en sub-carpetas dentro de la carpeta `resources/views` siguiendo la siguiente estructura:

Vista | Carpeta | Ruta asociada
------|---------|--------------
home.blade.php | resources/views/ | /
login.blade.php | resources/views/auth/ | login
index.blade.php | resources/views/proyectos/ | /proyectos
show.blade.php | resources/views/proyectos/ | /proyectos/show/{id}
create.blade.php | resources/views/proyectos/ | /proyectos/create
edit.blade.php | resources/views/proyectos/ | /proyectos/edit/{id}

Creamos una vista separada para cada una de las rutas excepto para la ruta `logout`, la cual no tendrá ninguna vista. _Podemos utilizar artisan para crear cada una de las vistas._

Por último, vamos a actualizar las rutas del fichero `routes/web.php` para que se carguen las vistas que acabamos de crear. Acordaos que para referenciar las vistas que están dentro de carpetas, la barra `/` de separación se transforma en un _punto_ (`.`), y que, además, como segundo parámetro, podemos pasar datos a la vista. A continuación se incluyen algunos ejemplos:

```php
return view('home');
return view('proyectos.index');
return view('proyectos.show', array('id'=>$id));
```

Una vez hechos estos cambios ya podemos probarlo en el navegador, el cual debería mostrar en todos los casos la plantilla base con la barra de navegación principal y los estilos de _Bootstrap_ aplicados. En la sección principal de contenido de momento solo podremos ver los textos que hemos puesto de ejemplo.

## Comprobar el ejercicio

Para comprobar que la solución desarrollada cumple con los requisitos, puedes copiar el archivo [ViewsExerciseTest.php](./materiales/ejercicios-laravel/tests/Feature/ViewsExerciseTest.php) a la carpeta `tests/Feature` de tu proyecto y, posteriormente, ejecutar el siguiente comando artisan:

```bash
php artisan test
```

Como en el caso del ejercicio de rutas, la ejecución de los test debería devolver <span style="background-color: lightgreen">PASS</span> en color verde para cada uno de los tests.

En el caso de obtener un resultado diferente, habrá que investigar cuál es la la condición `assert` que no se cumple e intentar reparar el error.
