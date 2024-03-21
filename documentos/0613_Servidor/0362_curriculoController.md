# 3.6.1 Ejercicio Controlador CurriculoController

## Ejercicio 1 - Controlador

En este primer ejercicio, vamos a crear el controlador necesario para gestionar la tabla `curriculos` del [esquema relacional de la aplicación](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/marcapersonalFP.drawio.png) y además actualizaremos el fichero de rutas para que los utilice.

Empezaremos por añadir el controlador que nos va a hacer falta: `CurriculoController.php`. Para esto, tenéis que utilizar el comando de _Artisan_ que permite crear un controlador vacío (sin métodos).

A continuación, vamos a añadir los métodos de este controlador. En la siguiente tabla resumen podemos ver un listado de los métodos por controlador y las rutas y vistas que tendrán asociadas:

Ruta | Controlador | Método | Vista
-----|--|----|--
curriculos | CurriculoController | getIndex | curriculos.index
curriculos/show/{id} | CurriculoController | getShow | curriculos.show
curriculos/create | CurriculoController | getCreate | curriculos.create
curriculos/edit/{id} | CurriculoController | getEdit | curriculos.edit

Acordaos que los métodos `getShow()` y `getEdit()` tendrán que recibir como parámetro el `$id` del elemento a mostrar o editar y enviar a la vista el `curriculo` correspondiente, además del id recibido.

Por último, añadid al fichero `routes/web.php` las rutas de la tabla anterior que apuntarán a los métodos del controlador `CurriculoController`.

## Ejercicio 2 - Completar las vistas

En este ejercicio vamos a terminar los métodos de los controladores que hemos creado en el ejercicio anterior y además completaremos las vistas asociadas:

### Método CurriculoController@getIndex

Este método tiene que mostrar un listado de todas los curriculos de los usuarios que tiene _marcapersonalFP_. El listado de `curriculos` será el siguiente:

```php
<?php
    private $arrayCurriculos = [
        [
            'user_id' => 1,
            'video_curriculum' => 'https://www.youtube.com/watch?v=u54uern',
            'texto_curriculum' => "Experiencia en desarrollo web con enfoque en tecnologías front-end.",
        ],
        [
            'user_id' => 2,
            'video_curriculum' => 'https://www.youtube.com/watch?v=v87dfg2',
            'texto_curriculum' => "Habilidades avanzadas en HTML, CSS y JavaScript.",
        ],
        [
            'user_id' => 3,
            'video_curriculum' => 'https://www.youtube.com/watch?v=frt32qe',
            'texto_curriculum' => "Amplia experiencia en el diseño y desarrollo de interfaces de usuario.",
        ],
        [
            'user_id' => 4,
            'video_curriculum' => 'https://www.youtube.com/watch?v=wtrh2we',
            'texto_curriculum' => "Conocimientos sólidos en frameworks front-end como React y Vue.",
        ],
        [
            'user_id' => 5,
            'video_curriculum' => 'https://www.youtube.com/watch?v=qwer123',
            'texto_curriculum' => "Experiencia en integración de API y consumo de servicios web.",
        ],
        [
            'user_id' => 6,
            'video_curriculum' => 'https://www.youtube.com/watch?v=ytgfd32',
            'texto_curriculum' => "Habilidades de resolución de problemas y pensamiento lógico.",
        ],
        [
            'user_id' => 7,
            'video_curriculum' => 'https://www.youtube.com/watch?v=zxvbn23',
            'texto_curriculum' => "Colaborador proactivo y eficiente en entornos de trabajo en equipo.",
        ],
        [
            'user_id' => 8,
            'video_curriculum' => 'https://www.youtube.com/watch?v=asdf456',
            'texto_curriculum' => "Capacidad para aprender rápidamente nuevas tecnologías y conceptos.",
        ],
        [
            'user_id' => 9,
            'video_curriculum' => 'https://www.youtube.com/watch?v=qwerty78',
            'texto_curriculum' => "Comprometido con la mejora continua y el desarrollo profesional.",
        ],
        [
            'user_id' => 10,
            'video_curriculum' => 'https://www.youtube.com/watch?v=mnbvc90',
            'texto_curriculum' => "Comunicación efectiva y habilidades interpersonales.",
        ],
    ];

```

Este array de `curriculos` lo tenéis que copiar como variable miembro de la clase (más adelante las almacenaremos en la base de datos). En el método del controlador simplemente tendremos que modificar la generación de la vista para pasarle este array de `curriculos` completo (`$this->arrayCurriculos`).

Y en la vista correspondiente tendremos que adaptar el siguiente trozo de código en su sección content:

```php
@extends('layouts.master')

@section('content')

<div class="row">

    @for ($i=0; $i<count($arrayCurriculos); $i++)

    <div classUcol- col-6-medium col-12-small">
        <section class="box">
            <a href="#" class="image featured" title="SleaY, CC BY 4.0 &lt;https://creativecommons.org/licenses/by/4.0&gt;, via Wikimedia Commons"><img width="256" alt="Curriculum-vitae-warning-icon" src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9f/Curriculum-vitae-warning-icon.svg/256px-Curriculum-vitae-warning-icon.svg.png"></a>
            <header>
                <h3>Usuario {{ $arrayCurriculos[$i]['user_id'] }}</h3>
            </header>
            <p>
            <!--
                El siguiente código debe ser adaptado.
                Una vez adaptado, elimina este comentario.
            -->
                <a href="http://github.com/2DAW-CarlosIII/{{ $arrayCurriculos[$i]['dominio'] }}"> 
                    http://github.com/2DAW-CarlosIII/{{ $arrayCurriculos[$i]['dominio'] }}
                </a>
            </p>
            <footer>
                <ul class="actions">
                    <li><a href="{{ action([App\Http\Controllers\CurriculoController::class, 'getShow'], ['id' => $i] ) }}" class="button alt">Más info</a></li>
                </ul>
            </footer>
        </section>
    </div>

    @endfor

</div>
@endsection
```

### Método CurriculoController@getShow

Este método se utiliza para mostrar la vista detalle de un `curriculo`. Hemos de tener en cuenta que el método correspondiente recibe un identificador que, de momento, se refiere a la posición del `curriculo` en el array. Por lo tanto, tendremos que coger dicho `curriculo` del array (`$this->arrayCurriculos[$id]`) y pasárselo a la vista.

En esta vista vamos a crear dos columnas:

- en la columna de la izquierda insertamos la imagen del `curriculo`, que será la misma que la utilizada en la vista `curriculos.index`,
- en la columna de la derecha se tendrán que mostrar todos los datos del `curriculo`: `user_id`, `video_curricuum` y `texto_curriculum`.

También incluiremos dos botones:

- uno que nos llevará a editar el `curriculo`,
- otro para volver al listado de `curriculos`.

Para realizar lo anterior, adapta la vista `tfcs.show`.

### Método CurriculoController@getCreate

Este método devuelve la vista `curriculos.create` para añadir una nuevo `curriculo`. Para crear este formulario en la vista correspondiente nos podemos basar en el contenido de la vista `tfcs.create`. En el caso de `curriculo`, tendrá que tener los siguientes campos:

Label | Name | Tipo de campo
------|------|--------------
Estudiante | user_id | numérico
URL Videocurrículo | video_currículum | url
Texto del currículo | texto_curriculum | textarea

Además tendrá un botón al final con el texto "Añadir Currículum".

    De momento el formulario no funcionará. Más adelante lo terminaremos.

### Método CurriculoController@getEdit

Este método permitirá modificar el contenido de un `curriculo`. El formulario será exactamente igual al de añadir `curriculo`, así que lo podemos copiar y pegar en esta vista y simplemente cambiar los siguientes puntos:

    - El título por "Modificar Currículum".
    - El valor del `action` del formulario debería ser:`action([App\Http\Controllers\CurriculoController::class, 'getEdit'], ['id' => $id])`
    - Añadir justo debajo de la apertura del formulario el campo oculto para indicar que se va a enviar por PUT. Recordad que Laravel incluye el método `@method('PUT')` que nos ayudará a hacer esto.
    - El texto del botón de envío por "Modificar Currículum".

De momento, no tendremos que hacer nada más. Más adelante lo completaremos para que se rellene con los datos del `curriculo` a editar.

## Comprobar el ejercicio

Para comprobar que la solución desarrollada cumple con los requisitos, crearemos un test con el siguiente comando _Artisan_

```bash
php artisan make:test CurriculoControllerTest
```

Copia y pega en ese archivo el contenido del archivo [ControllersExerciseTest.php](./materiales/ejercicios-laravel/tests/Feature/ControllersExerciseTest.php), las líneas que van desde la 41 (_proyectos index test._) a la 106 (el final de _proyectos edit test._).
Adapta esas líneas para que solicite datos de `curriculos` y controle que se devuelven correctamente.

posteriormente, ejecutar el siguiente comando artisan:

```bash
php artisan test
```

Como en el caso del ejercicio de rutas, la ejecución de los test debería devolver <span style="background-color: lightgreen">PASS</span> en color verde para cada uno de los tests.

En el caso de obtener un resultado diferente, habrá que investigar cuál es la la condición `assert` que no se cumple e intentar reparar el error.