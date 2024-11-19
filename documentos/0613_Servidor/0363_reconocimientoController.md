# 3.6.1 Ejercicio Controlador ReconocimientoController

## Ejercicio 1 - Controlador

En este primer ejercicio, vamos a crear el controlador necesario para gestionar la tabla `reconocimientos` del [esquema relacional de la aplicación](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/marcapersonalFP.drawio.png) y además actualizaremos el fichero de rutas para que los utilice.

Empezaremos por añadir el controlador que nos va a hacer falta: `ReconocimientoController.php`. Para esto, tenéis que utilizar el comando de _Artisan_ que permite crear un controlador vacío (sin métodos).

A continuación, vamos a añadir los métodos de este controlador. En la siguiente tabla resumen podemos ver un listado de los métodos por controlador y las rutas y vistas que tendrán asociadas:

Ruta | Controlador | Método | Vista
-----|--|----|--
reconocimientos | ReconocimientoController | getIndex | reconocimientos.index
reconocimientos/show/{id} | ReconocimientoController | getShow | reconocimientos.show
reconocimientos/create | ReconocimientoController | getCreate | reconocimientos.create
reconocimientos/edit/{id} | ReconocimientoController | getEdit | reconocimientos.edit

Acordaos que los métodos `getShow()` y `getEdit()` tendrán que recibir como parámetro el `$id` del elemento a mostrar o editar y enviar a la vista el `reconocimiento` correspondiente, además del id recibido.

Por último, añadid al fichero `routes/web.php` las rutas de la tabla anterior que apuntarán a los métodos del controlador `ReconocimientoController`.

## Ejercicio 2 - Completar las vistas

En este ejercicio vamos a terminar los métodos de los controladores que hemos creado en el ejercicio anterior y además completaremos las vistas asociadas:

### Método ReconocimientoController@getIndex

Este método tiene que mostrar un listado de todas los reconocimientos de los usuarios que tiene _marcapersonalFP_. El listado de `reconocimientos` será el siguiente:

```php
<?php

    private $arrayReconocimientos = [
    [
        'estudiante_id' => 1,
        'actividad_id' => 2,
        'documento' => 'https://drive.google.com/document/d/KPkTFrB1nub',
        'fecha' => '05/12/2022',
        'docente_validador' => 2
    ],
    [
        'estudiante_id' => 2,
        'actividad_id' => 3,
        'documento' => 'https://drive.google.com/document/d/Cov6riILdN0',
        'fecha' => '10/10/2022',
        'docente_validador' => 3
    ],
    [
        'estudiante_id' => 3,
        'actividad_id' => 4,
        'documento' => 'https://drive.google.com/document/d/H5LzJpVKnR9',
        'fecha' => '15/11/2022',
        'docente_validador' => 4
    ],
    [
        'estudiante_id' => 4,
        'actividad_id' => 5,
        'documento' => 'https://drive.google.com/document/d/Z1lNhHOFru6',
        'fecha' => '20/01/2023',
        'docente_validador' => 5
    ],
    [
        'estudiante_id' => 5,
        'actividad_id' => 6,
        'documento' => 'https://drive.google.com/document/d/sV7S5dTPgpN',
        'fecha' => '25/02/2023',
        'docente_validador' => 6
    ],
    [
        'estudiante_id' => 6,
        'actividad_id' => 7,
        'documento' => 'https://drive.google.com/document/d/od4HPCv58Um',
        'fecha' => '03/04/2023',
        'docente_validador' => 7
    ],
    [
        'estudiante_id' => 7,
        'actividad_id' => 8,
        'documento' => 'https://drive.google.com/document/d/IKGrcD4NOAU',
        'fecha' => '08/05/2023',
        'docente_validador' => 8
    ],
    [
        'estudiante_id' => 8,
        'actividad_id' => 9,
        'documento' => 'https://drive.google.com/document/d/SLf3GPgs1CN',
        'fecha' => '13/06/2023',
        'docente_validador' => 9
    ],
    [
        'estudiante_id' => 9,
        'actividad_id' => 10,
        'documento' => 'https://drive.google.com/document/d/EUV2MlPzwZ0',
        'fecha' => '18/07/2023',
        'docente_validador' => 10
    ],
    [
        'estudiante_id' => 10,
        'actividad_id' => 1,
        'documento' => 'https://drive.google.com/document/d/bDO6iEqkWuK',
        'fecha' => '23/08/2023',
        'docente_validador' => 1
    ],
];

```

Este array de `reconocimientos` y la función que lo acompaña lo tenéis que copiar como variable miembro de la clase (más adelante las almacenaremos en la base de datos). En el método del controlador simplemente tendremos que modificar la generación de la vista para pasarle este array de `reconocimientos` completo (`$this->arrayreconocimientos`).

Y en la vista correspondiente tendremos que adaptar el siguiente trozo de código en su sección content:

{% raw %}
```php
@extends('layouts.master')

@section('content')

<div class="row">

    @for ($i=0; $i<count($arrayReconocimientos); $i++)

    <div classUcol- col-6-medium col-12-small">
        <section class="box">
            <a href="#" class="image featured" title="Sakatsp, CC BY-SA 4.0 &lt;https://creativecommons.org/licenses/by-sa/4.0&gt;, via Wikimedia Commons"><img width="256" alt="Award icon" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Award_icon.png"></a>
            <header>
                <h3>Estudiante {{ $arrayReconocimientos[$i]['estudiante_id'] }}</h3>
            </header>
            <p>
            <!--
                El siguiente código debe ser adaptado.
                Una vez adaptado, elimina este comentario.
            -->
                <a href="http://github.com/2DAW-CarlosIII/{{ $arrayReconocimientos[$i]['dominio'] }}"> 
                    http://github.com/2DAW-CarlosIII/{{ $arrayReconocimientos[$i]['dominio'] }}
                </a>
            </p>
            <footer>
                <ul class="actions">
                    <li><a href="{{ action([App\Http\Controllers\ReconocimientoController::class, 'getShow'], ['id' => $i] ) }}" class="button alt">Más info</a></li>
                </ul>
            </footer>
        </section>
    </div>

    @endfor

</div>
@endsection
```
{% endraw %}

### Método ReconocimientoController@getShow

Este método se utiliza para mostrar la vista detalle de un `reconocimiento`. Hemos de tener en cuenta que el método correspondiente recibe un identificador que, de momento, se refiere a la posición del `reconocimiento` en el array. Por lo tanto, tendremos que coger dicho `reconocimiento` del array (`$this->arrayreconocimientos[$id]`) y pasárselo a la vista.

En esta vista vamos a crear dos columnas:

- en la columna de la izquierda insertamos la imagen del `reconocimiento`, que será la misma que la utilizada en la vista `reconocimientos.index`,
- en la columna de la derecha se tendrán que mostrar todos los datos del `reconocimiento`: `estudiante_id`, `actividad_id`, `documento`, `fecha` y `docente_validador`.

También incluiremos dos botones:

- uno que nos llevará a editar el `reconocimiento`,
- otro para volver al listado de `reconocimientos`.

Para realizar lo anterior, adapta la vista `proyectos.show`.

### Método ReconocimientoController@getCreate

Este método devuelve la vista `reconocimientos.create` para añadir una nuevo `reconocimiento`. Para crear este formulario en la vista correspondiente nos podemos basar en el contenido de la vista `proyectos.create`. En el caso de `reconocimiento`, tendrá que tener los siguientes campos:

Label | Name | Tipo de campo
------|------|--------------
Estudiante | estudiante_id | numérico
Actividad | actividad_id | numérico
URL del documento | documento | url
Fecha | fecha | date
Docente Validador | docente_validador | numérico

Además tendrá un botón al final con el texto "Añadir Reconocimiento".

    De momento el formulario no funcionará. Más adelante lo terminaremos.

### Método ReconocimientoController@getEdit

Este método permitirá modificar el contenido de un `reconocimiento`. El formulario será exactamente igual al de añadir `reconocimiento`, así que lo podemos copiar y pegar en esta vista y simplemente cambiar los siguientes puntos:

    - El título por "Modificar Reconocimiento".
    - El valor del `action` del formulario debería ser:`action([App\Http\Controllers\ReconocimientoController::class, 'getEdit'], ['id' => $id])`
    - Añadir justo debajo de la apertura del formulario el campo oculto para indicar que se va a enviar por PUT. Recordad que Laravel incluye el método `@method('PUT')` que nos ayudará a hacer esto.
    - El texto del botón de envío por "Modificar Reconocimiento".

De momento, no tendremos que hacer nada más. Más adelante lo completaremos para que se rellene con los datos del `reconocimiento` a editar.

## Comprobar el ejercicio

Para comprobar que la solución desarrollada cumple con los requisitos, crearemos un test con el siguiente comando _Artisan_

```bash
php artisan make:test ReconocimientoControllerTest
```

Copia y pega en ese archivo el contenido del archivo [ControllersExerciseTest.php](./materiales/ejercicios-laravel/tests/Feature/ControllersExerciseTest.php), las líneas que van desde la 41 (_proyectos index test._) a la 106 (el final de _proyectos edit test._).
Adapta esas líneas para que solicite datos de `reconocimientos` y controle que se devuelven correctamente.

posteriormente, ejecutar el siguiente comando artisan:

```bash
php artisan test
```

Como en el caso del ejercicio de rutas, la ejecución de los test debería devolver <span style="background-color: lightgreen">PASS</span> en color verde para cada uno de los tests.

En el caso de obtener un resultado diferente, habrá que investigar cuál es la la condición `assert` que no se cumple e intentar reparar el error.
