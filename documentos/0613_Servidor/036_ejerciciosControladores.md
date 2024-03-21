# 3.6. Ejercicios

En los ejercicios de esta parte vamos a continuar con el sitio Web que empezamos para la gestión de **marcapersonalFP**. Primero, añadiremos los controladores y métodos asociados a cada ruta, y posteriormente también completaremos las vistas usando formularios y el sistema de plantillas _Blade_.

## Ejercicio 1 - Controladores

En este primer ejercicio, vamos a crear los controladores necesarios para gestionar nuestra aplicación y además actualizaremos el fichero de rutas para que los utilice.

Empezamos por añadir los dos controladores que nos van a hacer falta: `TfcsController.php` y `HomeController.php`. Para esto, tenéis que utilizar el comando de _Artisan_ que permite crear un controlador vacío (sin métodos).

A continuación vamos a añadir los métodos de estos controladores. En la siguiente tabla resumen podemos ver un listado de los métodos por controlador y las rutas que tendrán asociadas:

Ruta | Controlador | Método
-----|--|--
/ | HomeController | getHome
tfcs | TfcsController | getIndex
tfcs/show/{id} | TfcsController | getShow
tfcs/create | TfcsController | getCreate
tfcs/edit/{id} | TfcsController | getEdit

Acordaos que los métodos `getShow()` y `getEdit()` tendrán que recibir como parámetro el `$id` del elemento a mostrar o editar y enviar a la vista el proyecto correspondiente, además del id recibido, por lo que la definición del método en el controlador tendrá que ser como la siguiente:

```php
public function getShow($id)
{
        return view('tfcs.show')
            ->with('proyecto', $this->arrayProyectos[$id])
            ->with('id', $id);
}
```

Por último, vamos a cambiar el fichero de rutas `routes/web.php` para que todas las rutas que teníamos definidas (excepto las de _login_ y _logout_ que las dejaremos como están) apunten a los nuevos métodos de los controladores, por ejemplo:

```php
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TfcsController;

Route::get('/', [HomeController::class, 'getHome']);
```

El código que teníamos puesto para cada ruta con el `return` con la generación de la vista lo tenéis que mover al método del controlador correspondiente.

## Ejercicio 2 - Completar las vistas

En este ejercicio vamos a terminar los métodos de los controladores que hemos creado en el ejercicio anterior y además completaremos las vistas asociadas:

### Método HomeController@getHome

En este método, de momento, solo vamos a hacer una redirección a la acción que muestra el listado de proyectos del catálogo: `return redirect()->action([TfcsController::class, 'getIndex']);`. Más adelante tendremos que comprobar si el usuario está logueado o no, y en caso de que no lo este redirigirle al formulario de _login_.

### Método TfcsController@getIndex

Este método tiene que mostrar un listado de todas los proyectos que tiene marcapersonalFP. El listado de proyectos lo podéis obtener del fichero [array_proyectos.php](./materiales/ejercicios-laravel/array_proyectos.php) facilitado con los [materiales](./materiales). Este array de proyectos lo tenéis que copiar como variable miembro de la clase (más adelante las almacenaremos en la base de datos). En el método del controlador simplemente tendremos que modificar la generación de la vista para pasarle este array de proyectos completo (`$this->arrayProyectos`).

Y en la vista correspondiente simplemente tendremos que incluir el siguiente trozo de código en su sección content:

```php
@extends('layouts.master')

@section('content')

<div class="row">

    @foreach ($arrayProyectos as $key => $proyecto)

    <div class="col-4 col-6-medium col-12-small">
        <section class="box">
            <a href="#" class="image featured"><img src="{{ asset('/images/mp-logo.png') }}" alt="" /></a>
            <header>
                <h3>{{ $proyecto['nombre'] }}</h3>
            </header>
            <p>
                <a href="http://github.com/2DAW-CarlosIII/{{ $proyecto['dominio'] }}">
                    http://github.com/2DAW-CarlosIII/{{ $proyecto['dominio'] }}
                </a>
            </p>
            <footer>
                <ul class="actions">
                    <li><a href="{{ action([App\Http\Controllers\TfcsController::class, 'getShow'], ['id' => $key] ) }}" class="button alt">Más info</a></li>
                </ul>
            </footer>
        </section>
    </div>

    @endfor

</div>
@endsection
```

El logo lo debemos recoger también de la carpeta de [materiales](./materiales) y colocarlo en la carpeta `public/images`.

Como se puede ver en el código, en primer lugar se crea una fila (usando el sistema de rejilla de Bootstrap) y a continuación se realiza un bucle `foreach` utilizando la notación de _Blade_ para iterar por todos los proyectos. Para cada proyecto obtenemos su posición en el array y sus datos asociados, y generamos una columna para mostrarlos. Es importante que nos fijemos en como se itera por los elementos de un array de datos y en la forma de acceder a los valores. Además, se ha incluido un enlace para que, al pulsar sobre un proyecto, nos lleve a la dirección `/tfcs/show/{$key}`, siendo `key` la posición de ese proyecto en el array.

### Método TfcsController@getShow

Este método se utiliza para mostrar la vista detalle de un proyecto. Hemos de tener en cuenta que el método correspondiente recibe un identificador que, de momento, se refiere a la posición del proyecto en el array. Por lo tanto, tendremos que coger dicho proyecto del array (`$this->arrayProyectos[$id]`) y pasársela a la vista.

En esta vista vamos a crear dos columnas, la primera columna para mostrar la imagen del proyecto y la segunda para incluir todos los detalles. A continuación se incluye la estructura _HTML_ que tendría que tener esta pantalla:

```php
<div class="row m-4">

    <div class="col-sm-4">

        {{-- TODO: Imagen del proyecto --}}

    </div>
    <div class="col-sm-8">

        {{-- TODO: Datos del proyecto --}}

    </div>
</div>
```

En la columna de la izquierda insertamos la imagen del proyecto, mientras que en la columna de la derecha se tendrán que mostrar todos los datos del proyecto: `docente_id`, `nombre`, `url_github` y `metadatos`.

Para mostrar el estado del proyecto consultaremos el valor `calificacion` del array, el cual podrá tener dos casos:

- En caso de estar **suspenso** (calificacion < 5) aparecerá el estado "Proyecto suspenso" y un botón azul para "Aprobar proyecto".
- En caso de estar **aprobado** (calificacion >= 5) aparecerá el estado "Proyecto aprobado" y un botón rojo para "Suspender proyecto".

Además tenemos que incluir dos botones más, un botón que nos llevará a editar el proyecto y otro para volver al listado de proyectos.

> **Nota:** los botones de aprobar/suspender de momento no tienen que funcionar. Acordaos que en _Bootstrap_ podemos transformar un enlace en un botón, simplemente aplicando las clases "btn btn-default" (más info en: http://getbootstrap.com/css/#buttons).

Esta pantalla finalmente tendría el siguiente código:

```php
@section('content')


    <div class="row m-4">

        <div class="col-sm-4">

            <img src="/images/mp-logo.png" style="height:200px"/>

        </div>
        <div class="col-sm-8">

            <h3><strong>Nombre: </strong>{{ $proyecto['nombre'] }}</h3>
            <h4><strong>Dominio: </strong>
                <a href="http://github.com/2DAW-CarlosIII/{{ $proyecto['dominio'] }}">
                    http://github.com/2DAW-CarlosIII/{{ $proyecto['dominio'] }}
                </a>
            </h4>
            <h4><strong>Docente: </strong>{{ $proyecto['docente_id'] }}</h4>
            <p><strong>Metadatos: </strong>
                <ul>
                    @foreach ($proyecto['metadatos'] as $indice => $metadato)
                        <li>{{ $indice }}: {{ $metadato }}</li>
                    @endforeach
                </ul>
            </p>
            <p><strong>Estado: </strong>
                @if($proyecto['metadatos']['calificacion'] >= 5)
                    Proyecto aprobado
                @else
                    Proyecto suspenso
                @endif
            </p>

            @if($proyecto['metadatos']['calificacion'] >= 5)
                <a class="btn btn-danger" href="#">Suspender proyecto</a>
            @else
                <a class="btn btn-primary" href="#">Aprobar proyecto</a>
            @endif
            <a class="btn btn-warning" href="{{ action([App\Http\Controllers\TfcsController::class, 'getEdit'], ['id' => $id]) }}">
                <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                Editar proyecto
            </a>
            <a class="btn btn-outline-info" href="{{ action([App\Http\Controllers\TfcsController::class, 'getIndex']) }}">
                Volver al listado
            </a>


        </div>
    </div>

@endsection
```

### Método TfcsController@getCreate

Este método devuelve la vista `tfcs.create` para añadir una nuevo proyecto. Para crear este formulario en la vista correspondiente nos podemos basar en el contenido de la plantilla [tfcs_create.php](./materiales/ejercicios-laravel/tfcs_create.php). Esta plantilla tiene una serie de `TODO`s que hay que completar. En total tendrá que tener los siguientes campos:

Label | Name | Tipo de campo
------|------|--------------
Docente | docente_id | numérico
Nombre | nombre | texto
Dominio | dominio | texto
Metadatos | metadatos | textarea

Además, tendrá un botón al final con el texto "Añadir proyecto".

```php
@section('content')

<div class="row" style="margin-top:40px">
   <div class="offset-md-3 col-md-6">
      <div class="card">
         <div class="card-header text-center">
            Añadir proyecto
         </div>
         <div class="card-body" style="padding:30px">

            <form action="{{ url('/tfcs/create') }}" method="POST">

	            @csrf

	            <div class="form-group">
	               <label for="nombre">Nombre</label>
	               <input type="text" name="nombre" id="nombre" class="form-control">
	            </div>

	            <div class="form-group">
	            	<label for="docente_id">Docente</label>
	               <input type="number" name="docente_id" id="docente_id">
	            </div>

	            <div class="form-group">
	            	<label for="dominio">Dominio</label><br />
                    https://github.com/2DAW-CarlosIII/ 
	               <input type="text" name="dominio" id="dominio" class="form-control">
	            </div>

	            <div class="form-group">
	               <label for="metadatos">Metadatos</label>
	               <textarea name="metadatos" id="metadatos" class="form-control" rows="3"></textarea>
                   <br /><small>Cada metadato irá separado del siguiente por una línea <br />
                   y la clave irá separada por : del valor</small>
	            </div>

	            <div class="form-group text-center">
	               <button type="submit" class="btn btn-primary" style="padding:8px 100px;margin-top:25px;">
	                   Añadir proyecto
	               </button>
	            </div>

            </form>

         </div>
      </div>
   </div>
</div>

@endsection
```

> De momento el formulario no funcionará. Más adelante lo terminaremos.

### Método TfcsController@getEdit

Este método permitirá modificar el contenido de un proyecto. El formulario será exactamente igual al de añadir proyecto, así que lo podemos copiar y pegar en esta vista y simplemente cambiar los siguientes puntos:

- El título por "Modificar proyecto".
- El valor del `action` del formulario debería ser:`action([App\Http\Controllers\TfcsController::class, 'getEdit'], ['id' => $id])`
- Añadir justo debajo de la apertura del formulario el campo oculto para indicar que se va a enviar por PUT. Recordad que Laravel incluye el método `@method('PUT')` que nos ayudará a hacer esto.
- El texto del botón de envío por "Modificar proyecto".

De momento, no tendremos que hacer nada más. Más adelante lo completaremos para que se rellene con los datos del proyecto a editar.

## Comprobar el ejercicio

Para comprobar que la solución desarrollada cumple con los requisitos, en primer lugar, **vamos a eliminar los ficheros de test que teníamos en la carpeta `tests/Feature`**. A continuación, copiaremos el archivo [ControllersExerciseTest.php](./materiales/ejercicios-laravel/tests/Feature/ControllersExerciseTest.php) a la carpeta `tests/Feature` de tu proyecto y, posteriormente, ejecutar el siguiente comando artisan:

```bash
php artisan test
```

Como en el caso del ejercicio de rutas, la ejecución de los test debería devolver <span style="background-color: lightgreen">PASS</span> en color verde para cada uno de los tests.

En el caso de obtener un resultado diferente, habrá que investigar cuál es la la condición `assert` que no se cumple e intentar reparar el error.

# Otros ejercicios de controladores

- [Usuarios](0361_controladorUsers.md)
- [Currículos](./0362_curriculoController.md)
- [Reconocimientos](./0363_reconocimientoController.md)
- [Actividades](./0364_actividadController.md)
