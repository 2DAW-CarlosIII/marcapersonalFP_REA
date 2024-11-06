{% raw %}
# 3.6.1 Ejercicio Controlador ActividadController

## Ejercicio 1 - Controlador

En este primer ejercicio, vamos a crear el controlador necesario para gestionar la tabla `actividades` del [esquema relacional de la aplicación](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/marcapersonalFP.drawio.png) y además actualizaremos el fichero de rutas para que los utilice.

Empezaremos por añadir el controlador que nos va a hacer falta: `ActividadController.php`. Para esto, tenéis que utilizar el comando de _Artisan_ que permite crear un controlador vacío (sin métodos).

A continuación, vamos a añadir los métodos de este controlador. En la siguiente tabla resumen podemos ver un listado de los métodos por controlador y las rutas y vistas que tendrán asociadas:

Ruta | Controlador | Método | Vista
-----|--|----|--
actividades | ActividadController | getIndex | actividades.index
actividades/show/{id} | ActividadController | getShow | actividades.show
actividades/create | ActividadController | getCreate | actividades.create
actividades/edit/{id} | ActividadController | getEdit | actividades.edit

Acordaos que los métodos `getShow()` y `getEdit()` tendrán que recibir como parámetro el `$id` del elemento a mostrar o editar y enviar a la vista el `actividad` correspondiente, además del id recibido.

Por último, añadid al fichero `routes/web.php` las rutas de la tabla anterior que apuntarán a los métodos del controlador `ActividadController`.

## Ejercicio 2 - Completar las vistas

En este ejercicio vamos a terminar los métodos de los controladores que hemos creado en el ejercicio anterior y además completaremos las vistas asociadas:

### Método ActividadController@getIndex

Este método tiene que mostrar un listado de todas los actividades de los usuarios que tiene _marcapersonalFP_. El listado de `actividades` será el siguiente:

```php
<?php
    private $arrayActividades = [
        [
            'docente_id' => 1,
            'insignia' => 'https://marcapersonalFP.es/badge?v=u54uern',
        ],
        [
            'docente_id' => 2,
            'insignia' => 'https://marcapersonalFP.es/badge?v=v87dfg2',
        ],
        [
            'docente_id' => 3,
            'insignia' => 'https://marcapersonalFP.es/badge?v=frt32qe',
        ],
        [
            'docente_id' => 4,
            'insignia' => 'https://marcapersonalFP.es/badge?v=wtrh2we',
        ],
        [
            'docente_id' => 5,
            'insignia' => 'https://marcapersonalFP.es/badge?v=qwer123',
        ],
        [
            'docente_id' => 6,
            'insignia' => 'https://marcapersonalFP.es/badge?v=ytgfd32',
        ],
        [
            'docente_id' => 7,
            'insignia' => 'https://marcapersonalFP.es/badge?v=zxvbn23',
        ],
        [
            'docente_id' => 8,
            'insignia' => 'https://marcapersonalFP.es/badge?v=asdf456',
        ],
        [
            'docente_id' => 9,
            'insignia' => 'https://marcapersonalFP.es/badge?v=qwerty78',
        ],
        [
            'docente_id' => 10,
            'insignia' => 'https://marcapersonalFP.es/badge?v=mnbvc90',
        ],
    ];

```

Este array de `actividades` lo tenéis que copiar como variable miembro de la clase (más adelante las almacenaremos en la base de datos). En el método del controlador simplemente tendremos que modificar la generación de la vista para pasarle este array de `actividades` completo (`$this->arrayActividades`).

Y en la vista correspondiente tendremos que adaptar el siguiente trozo de código en su sección content:

```php
@extends('layouts.master')

@section('content')

<div class="row">

    @for ($i=0; $i<count($arrayActividades); $i++)

    <div classUcol- col-6-medium col-12-small">
        <section class="box">
            <a href="#" class="image featured" title="SleaY, CC BY 4.0 &lt;https://creativecommons.org/licenses/by/4.0&gt;, via Wikimedia Commons"><img width="256" alt="Curriculum-vitae-warning-icon" src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9f/Curriculum-vitae-warning-icon.svg/256px-Curriculum-vitae-warning-icon.svg.png"></a>
            <header>
                <h3>Docente {{ $arrayActividades[$i]['docente_id] }}</h3>
            </header>
            <p>
            <!--
                El siguiente código debe ser adaptado.
                Una vez adaptado, elimina este comentario.
            -->
                <a href="http://github.com/2DAW-CarlosIII/{{ $arrayActividades[$i]['dominio'] }}"> 
                    http://github.com/2DAW-CarlosIII/{{ $arrayActividades[$i]['dominio'] }}
                </a>
            </p>
            <footer>
                <ul class="actions">
                    <li><a href="{{ action([App\Http\Controllers\ActividadController::class, 'getShow'], ['id' => $i] ) }}" class="button alt">Más info</a></li>
                </ul>
            </footer>
        </section>
    </div>

    @endfor

</div>
@endsection
```

### Método ActividadController@getShow

Este método se utiliza para mostrar la vista detalle de un `actividad`. Hemos de tener en cuenta que el método correspondiente recibe un identificador que, de momento, se refiere a la posición del `actividad` en el array. Por lo tanto, tendremos que coger dicho `actividad` del array (`$this->arrayActividades[$id]`) y pasárselo a la vista.

En esta vista vamos a crear dos columnas:

- en la columna de la izquierda insertamos la imagen del `actividad`, que será la misma que la utilizada en la vista `actividades.index`,
- en la columna de la derecha se tendrán que mostrar todos los datos del `actividad`: `docente_id` e `insignia`.

También incluiremos dos botones:

- uno que nos llevará a editar el `actividad`,
- otro para volver al listado de `actividades`.

Para realizar lo anterior, adapta la vista `proyectos.show`.

### Método ActividadController@getCreate

Este método devuelve la vista `actividades.create` para añadir una nuevo `actividad`. Para crear este formulario en la vista correspondiente nos podemos basar en el contenido de la vista `proyectos.create`. En el caso de `actividad`, tendrá que tener los siguientes campos:

Label | Name | Tipo de campo
------|------|--------------
Docente | docente_id | numérico
Insignia | insignia | url

Además tendrá un botón al final con el texto "Añadir Actividad".

    De momento el formulario no funcionará. Más adelante lo terminaremos.

### Método ActividadController@getEdit

Este método permitirá modificar el contenido de un `actividad`. El formulario será exactamente igual al de añadir `actividad`, así que lo podemos copiar y pegar en esta vista y simplemente cambiar los siguientes puntos:

    - El título por "Modificar Actividad".
    - El valor del `action` del formulario debería ser:`action([App\Http\Controllers\ActividadController::class, 'getEdit'], ['id' => $id])`
    - Añadir justo debajo de la apertura del formulario el campo oculto para indicar que se va a enviar por PUT. Recordad que Laravel incluye el método `@method('PUT')` que nos ayudará a hacer esto.
    - El texto del botón de envío por "Modificar Actividad".

De momento, no tendremos que hacer nada más. Más adelante lo completaremos para que se rellene con los datos del `actividad` a editar.

## Comprobar el ejercicio

Para comprobar que la solución desarrollada cumple con los requisitos, crearemos un test con el siguiente comando _Artisan_

```bash
php artisan make:test ActividadControllerTest
```

Copia y pega en ese archivo el contenido del archivo [ControllersExerciseTest.php](./materiales/ejercicios-laravel/tests/Feature/ControllersExerciseTest.php), las líneas que van desde la 41 (_proyectos index test._) a la 106 (el final de _proyectos edit test._).
Adapta esas líneas para que solicite datos de `actividades` y controle que se devuelven correctamente.

posteriormente, ejecutar el siguiente comando artisan:

```bash
php artisan test
```

Como en el caso del ejercicio de rutas, la ejecución de los test debería devolver <span style="background-color: lightgreen">PASS</span> en color verde para cada uno de los tests.

En el caso de obtener un resultado diferente, habrá que investigar cuál es la la condición `assert` que no se cumple e intentar reparar el error.
{% endraw %}
