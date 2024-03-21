# 3.6.1 Ejercicio Controlador UserController

## Ejercicio 1 - Controlador

En este primer ejercicio, vamos a crear el controlador necesario para gestionar la tabla `users` del [esquema relacional de la aplicación](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/marcapersonalFP.drawio.png) y además actualizaremos el fichero de rutas para que los utilice.

Empezaremos por añadir el controlador que nos va a hacer falta: `UserController.php`. Para esto, tenéis que utilizar el comando de _Artisan_ que permite crear un controlador vacío (sin métodos).

A continuación, vamos a añadir los métodos de este controlador. En la siguiente tabla resumen podemos ver un listado de los métodos por controlador y las rutas que tendrán asociadas:

Ruta | Controlador | Método | Vista
-----|--|----|--
users | UserController | getIndex | users.index
users/show/{id} | UserController | getShow | users.show
users/create | UserController | getCreate | users.create
users/edit/{id} | UserController | getEdit | users.edit

Acordaos que los métodos `getShow()` y `getEdit()` tendrán que recibir como parámetro el `$id` del elemento a mostrar o editar y enviar a la vista el `user` correspondiente, además del id recibido.

Por último, añadid al fichero `routes/web.php` las rutas de la tabla anterior que apuntarán a los métodos del controlador `UserController`.

## Ejercicio 2 - Completar las vistas

En este ejercicio vamos a terminar los métodos de los controladores que hemos creado en el ejercicio anterior y además completaremos las vistas asociadas:

### Método UserController@getIndex

Este método tiene que mostrar un listado de todas los usuarios que tiene _marcapersonalFP_. El listado de `users` será el siguiente:

```php
<?php
        private $arrayUsers = [
            [
                'email' => 'user0@marcapersonalFP.es',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => 'password0',
                'linkedin' => 'https://www.linkedin.com/in/user0'
            ],
            [
                'email' => 'user1@marcapersonalFP.es',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'password' => 'password1',
                'linkedin' => 'https://www.linkedin.com/in/user1'
            ],
            [
                'email' => 'user2@marcapersonalFP.es',
                'first_name' => 'Alice',
                'last_name' => 'Johnson',
                'password' => 'password2',
                'linkedin' => 'https://www.linkedin.com/in/user2'
            ],
            [
                'email' => 'user3@marcapersonalFP.es',
                'first_name' => 'Bob',
                'last_name' => 'Williams',
                'password' => 'password3',
                'linkedin' => 'https://www.linkedin.com/in/user3'
            ],
            [
                'email' => 'user4@marcapersonalFP.es',
                'first_name' => 'Eva',
                'last_name' => 'Brown',
                'password' => 'password4',
                'linkedin' => 'https://www.linkedin.com/in/user4'
            ],
            [
                'email' => 'user5@marcapersonalFP.es',
                'first_name' => 'Michael',
                'last_name' => 'Taylor',
                'password' => 'password5',
                'linkedin' => 'https://www.linkedin.com/in/user5'
            ],
            [
                'email' => 'user6@marcapersonalFP.es',
                'first_name' => 'Sophie',
                'last_name' => 'Miller',
                'password' => 'password6',
                'linkedin' => 'https://www.linkedin.com/in/user6'
            ],
            [
                'email' => 'user7@marcapersonalFP.es',
                'first_name' => 'David',
                'last_name' => 'Davis',
                'password' => 'password7',
                'linkedin' => 'https://www.linkedin.com/in/user7'
            ],
            [
                'email' => 'user8@marcapersonalFP.es',
                'first_name' => 'Emily',
                'last_name' => 'White',
                'password' => 'password8',
                'linkedin' => 'https://www.linkedin.com/in/user8'
            ],
            [
                'email' => 'user9@marcapersonalFP.es',
                'first_name' => 'Tom',
                'last_name' => 'Wilson',
                'password' => 'password9',
                'linkedin' => 'https://www.linkedin.com/in/user9'
            ],
        ];

```

Este array de `users` lo tenéis que copiar como variable miembro de la clase (más adelante las almacenaremos en la base de datos). En el método del controlador simplemente tendremos que modificar la generación de la vista para pasarle este array de `users` completo (`$this->arrayUsers`).

Y en la vista correspondiente tendremos que adaptar el siguiente trozo de código en su sección content:

```php
@extends('layouts.master')

@section('content')

<div class="row">

    @for ($i=0; $i<count($arrayUsers); $i++)

    <div classUcol- col-6-medium col-12-small">
        <section class="box">
            <a href="#" class="image featured" title="Nice and Serious, CC0, via Wikimedia Commons"><img width="256" alt="User (89041) - The Noun Project" src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f9/User_%2889041%29_-_The_Noun_Project.svg/256px-User_%2889041%29_-_The_Noun_Project.svg.png"></a>
            <header>
                <h3>{{ $arrayUsers[$i]['first_name'] }} {{ $arrayUsers[$i]['last_name'] }}</h3>
            </header>
            <p>
            <!--
                El siguiente código debe ser adaptado.
                Una vez adaptado, elimina este comentario.
            -->
                <a href="http://github.com/2DAW-CarlosIII/{{ $arrayUsers[$i]['dominio'] }}"> 
                    http://github.com/2DAW-CarlosIII/{{ $arrayUsers[$i]['dominio'] }}
                </a>
            </p>
            <footer>
                <ul class="actions">
                    <li><a href="{{ action([App\Http\Controllers\UserController::class, 'getShow'], ['id' => $i] ) }}" class="button alt">Más info</a></li>
                </ul>
            </footer>
        </section>
    </div>

    @endfor

</div>
@endsection
```

### Método UserController@getShow

Este método se utiliza para mostrar la vista detalle de un `user`. Hemos de tener en cuenta que el método correspondiente recibe un identificador que, de momento, se refiere a la posición del `user` en el array. Por lo tanto, tendremos que coger dicho `user` del array (`$this->arrayUsers[$id]`) y pasárselo a la vista.

En esta vista vamos a crear dos columnas:

- en la columna de la izquierda insertamos la imagen del `user`, que será la misma que la utilizada en la vista `users.index`,
- en la columna de la derecha se tendrán que mostrar todos los datos del `user`: `email`, `first_name`, `last_name` y `linkedin`.

También incluiremos dos botones:

- uno que nos llevará a editar el `user`,
- otro para volver al listado de `users`.

Para realizar lo anterior, adapta la vista `tfcs.show`.

### Método UserController@getCreate

Este método devuelve la vista `users.create` para añadir una nuevo `user`. Para crear este formulario en la vista correspondiente nos podemos basar en el contenido de la vista `tfcs.create`. En el caso de `user`, tendrá que tener los siguientes campos:

Label | Name | Tipo de campo
------|------|--------------
Nombre | first_name | texto
Apellidos | last_name | texto
Email | email | email
Contraseña | password1 | password
Repita contraseña | password2 | password
Perfil Linkedin | linkedin | url

Además tendrá un botón al final con el texto "Añadir Usuario".

> De momento el formulario no funcionará. Más adelante lo terminaremos.

### Método UserController@getEdit

Este método permitirá modificar el contenido de un `user`. El formulario será exactamente igual al de añadir `user`, así que lo podemos copiar y pegar en esta vista y simplemente cambiar los siguientes puntos:

- El título por "Modificar Usuario".
- El valor del `action` del formulario debería ser:`action([App\Http\Controllers\UserController::class, 'getEdit'], ['id' => $id])`
- Añadir justo debajo de la apertura del formulario el campo oculto para indicar que se va a enviar por PUT. Recordad que Laravel incluye el método `@method('PUT')` que nos ayudará a hacer esto.
- El texto del botón de envío por "Modificar Usuario".

De momento, no tendremos que hacer nada más. Más adelante lo completaremos para que se rellene con los datos del `user` a editar.

## Comprobar el ejercicio

Para comprobar que la solución desarrollada cumple con los requisitos, crearemos un test con el siguiente comando _Artisan_

```bash
php artisan make:test UserControllerTest
```

Copia y pega en ese archivo el contenido del archivo [ControllersExerciseTest.php](./materiales/ejercicios-laravel/tests/Feature/ControllersExerciseTest.php), las líneas que van desde la 41 (_proyectos index test._) a la 106 (el final de _proyectos edit test._).
Adapta esas líneas para que solicite datos de `users` y controle que se devuelven correctamente.

posteriormente, ejecutar el siguiente comando artisan:

```bash
php artisan test
```

Como en el caso del ejercicio de rutas, la ejecución de los test debería devolver <span style="background-color: lightgreen">PASS</span> en color verde para cada uno de los tests.

En el caso de obtener un resultado diferente, habrá que investigar cuál es la la condición `assert` que no se cumple e intentar reparar el error.