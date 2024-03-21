# 2.7 Ejercicios Vistas

## _Layout_ principal de las vistas con Bootstrap

En este ejercicio vamos a crear el _layout_ base que van a utilizar el resto de vistas del sitio web y además incluiremos la librería _Bootstrap_ para utilizarla como estilo base.

En primer lugar nos tenemos que descargar la plantilla para la barra de navegación principal ([navbar.blade.php](./materiales/ejercicios-laravel/navbar.blade.php)) y almacenarla en la carpeta `resources/views/partials`.

También debemos copiar el [logo de marcapersonalfp](./materiales/ejercicios-laravel/mp-logo.png) a la carpeta `public/images`.

A continuación vamos a crear el layout principal de nuestro sitio. Para eso, creamos el fichero `resources/views/layouts/master.blade.php`:

```bash
php artisan make:view layouts.master
```

A continuación le añadimos, como contenido, la plantilla base que propone _Bootstrap_ en su [documentación](https://getbootstrap.com/docs/5.3/getting-started/introduction/):

```php
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  </head>
  <body>
    <h1>Hello, world!</h1>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  </body>
</html>
```

Después, modificaremos los siguientes elementos:

- Dentro de la sección `<body>` del _HTML_, eliminamos el texto que viene de ejemplo (`<h1>Hello, world!</h1>`) e incluimos la barra de navegación que hemos guardado antes utilizando el siguiente código:

  ```php
  @include('partials.navbar')
  ```

- A continuación de la barra de navegación añadimos la sección principal donde aparecerá el contenido de la web:

  ```php
  <div class="container">
      @yield('content')
  </div>
  ```

Con esto ya hemos definido el _layout_ principal, sin embargo todavía no podemos probarlo ya que no está asociado a ninguna ruta. En los siguientes apartados realizaremos los cambios necesarios para poder verlo y además añadiremos el resto de vistas hijas.

## Crear el resto de vistas

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
return view(' | proyectos.index');
return view(' | proyectos.show', array('id'=>$id));
```

Una vez hechos estos cambios ya podemos probarlo en el navegador, el cual debería mostrar en todos los casos la plantilla base con la barra de navegación principal y los estilos de _Bootstrap_ aplicados. En la sección principal de contenido de momento solo podremos ver los textos que hemos puesto de ejemplo.

## Comprobar el ejercicio

Para comprobar que la solución desarrollada cumple con los requisitos, puedes copiar el archivo [ViewsExerciseTest.php](./materiales/ejercicios-laravel/tests/Feature/ViewsExerciseTest.php) a la carpeta `tests/Feature` de tu proyecto y, posteriormente, ejecutar el siguiente comando artisan:

```bash
php artisan test
```

Como en el caso del ejercicio de rutas, la ejecución de los test debería devolver <span style="background-color: lightgreen">PASS</span> en color verde para cada uno de los tests.

En el caso de obtener un resultado diferente, habrá que investigar cuál es la la condición `assert` que no se cumple e intentar reparar el error.