# 2.6.1 Plantillas mediante Blade

_Laravel_ utiliza _Blade_ para la definición de plantillas en las vistas. Esta librería permite realizar todo tipo de operaciones con los datos, además de la sustitución de secciones de las plantillas por otro contenido, herencia entre plantillas, definición de _layouts_ o plantillas base, etc.

Los ficheros de vistas que utilizan el sistema de plantillas _Blade_ tienen que tener la extensión `.blade.php`. Esta extensión tampoco se tendrá que incluir a la hora de referenciar una vista desde el fichero de rutas o desde un controlador. Es decir, utilizaremos `view('home')` tanto si el fichero se llama `home.php` como `home.blade.php`.

En general el código que incluye _Blade_ en una vista empezará por los símbolos `@` o `{{`, el cual posteriormente será procesado y preparado para mostrarse por pantalla. _Blade_ no añade sobrecarga de procesamiento, ya que todas las vistas son preprocesadas y cacheadas, por el contrario nos brinda utilidades que nos ayudarán en el diseño y modularización de las vistas.

## Mostrar datos

El método más básico que tenemos en _Blade_ es el de mostrar datos, para esto utilizaremos las llaves dobles (`{{ }}`) y dentro de ellas escribiremos la variable o función con el contenido a mostrar:

```
Hola {{ $nombre }}.
La hora actual es {{ time() }}.
```
Como hemos visto podemos mostrar el contenido de una variable o incluso llamar a una función para mostrar su resultado. _Blade_ se encarga de escapar el resultado llamando a `htmlentities` para prevenir errores y ataques de tipo **XSS**. Si en algún caso no queremos escapar los datos tendremos que llamar a:

`Hola {!! $nombre !!}`.

_**Nota:** En general siempre tendremos que usar las llaves dobles, en especial si vamos a mostrar datos que son proporcionados por los usuarios de la aplicación. Esto evitará que inyecten símbolos que produzcan errores o inyecten código javascript que se ejecute sin que nosotros queramos. Por lo tanto, este último método solo tenemos que utilizarlo si estamos seguros de que no queremos que se escape el contenido._

## Mostrar un dato solo si existe

Para comprobar que una variable existe o tiene un determinado valor podemos utilizar el operador ternario de la forma:

`{{ isset($nombre) ? $nombre : 'colega' }}`

O simplemente usar la notación que incluye _Blade_ para este fin:

`{{ $nombre ?? 'colega' }}`

## Comentarios

Para escribir comentarios en _Blade_ se utilizan los símbolos `{{--` y `--}}`, por ejemplo:

`{{-- Este comentario no se mostrará en HTML --}}`

## Estructuras de control

_Blade_ nos permite utilizar la estructura `if` de las siguientes formas:

```
@if( count($users) === 1 )
    Solo hay un usuario!
@elseif (count($users) > 1)
    Hay muchos usuarios!
@else
    No hay ningún usuario :(
@endif
```

En los siguientes ejemplos se puede ver como realizar bucles tipo `for`, `while` o `foreach`:

```
@for ($i = 0; $i < 10; $i++)
    El valor actual es {{ $i }}
@endfor
```
```
@while (true)
    <p>Soy un bucle while infinito!</p>
@endwhile
```
```
@foreach ($users as $user)
    <p>Usuario {{ $user->name }} con identificador: {{ $user->id }}</p>
@endforeach
```

Esta son las estructuras de control más utilizadas. Ademas de estas _Blade_ define algunas más que podemos ver directamente en su documentación: [Directivas _Blade_](https://laravel.com/docs/blade#blade-directives)

## Incluir una plantilla dentro de otra plantilla

En _Blade_ podemos indicar que se incluya una plantilla dentro de otra plantilla, para esto disponemos de la instrucción `@include`:

`@include('view_name')`

Ademas podemos pasarle un `array` de datos a la vista a cargar usando el segundo parámetro del método `include`:

`@include('view_name', array('some'=>'data'))`

Esta opción es muy útil para crear vistas que sean reutilizables o para separar el contenido de una vista en varios ficheros.

### Components y slots

`@include` no es la única manera de incorporar el contenido de una vista en otra.

La utilización de _[components](https://laravel.com/docs/blade#components)_ también permiten la incorporación de vistas, dotándolas, incluso, de cierta capacidad de lógica.    

## Layouts

_Blade_ también nos permite la definición de _layouts_ para crear una estructura _HTML_ base con secciones que serán rellenadas por otras plantillas o vistas hijas. Por ejemplo, podemos crear un _layout_ con el contenido principal o común de nuestra web (`head`, `body`, etc.) y definir una serie de secciones que serán rellenados por otras plantillas para completar el código. Este _layout_ puede ser utilizado para todas las pantallas de nuestro sitio web, lo que nos permite que en el resto de plantillas no tengamos que repetir todo este código.

Para nuestra página web, vamos a crear un _layout_ almacenado en el fichero `resources/views/layouts/master.blade.php`:

```bash
php artisan make:view layouts.master
```

El contenido de ese archivo, de momento, será el siguiente:

```
<html>
    <head>
        <title>Mi Web</title>
    </head>
    <body>
        @section('menu')
            Contenido del menu
        @show

        <div class="container">
            @yield('content')
        </div>
    </body>
</html>
```

Posteriormente, en otra plantilla o vista, podemos indicar que extienda el _layout_ que hemos creado (con `@extends('layouts.master')`) y que complete las dos secciones de contenido que habíamos definido en el mismo:
```
@extends('layouts.master')

@section('menu')
    @parent
    <p>Este condenido es añadido al menú principal.</p>
@endsection

@section('content')
    <p>Este apartado aparecerá en la sección "content".</p>
@endsection
```

Como se puede ver, las vistas que extienden un _layout_ simplemente tienen que sobrescribir las secciones del _layout_. La directiva `@section` permite ir añadiendo contenido en las plantillas hijas, mientras que `@yield` será sustituido por el contenido que se indique. El método `@parent` carga en la posición indicada el contenido definido por el padre para dicha sección.

El método `@yield` también permite establecer un contenido por defecto mediante su segundo parámetro:

`@yield('section', 'Contenido por defecto')`
