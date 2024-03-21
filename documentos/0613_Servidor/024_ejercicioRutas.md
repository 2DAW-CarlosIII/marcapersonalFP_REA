# 2.4 Ejercicio de rutas

A estas alturas ya tendríamos que ser capaces de añadir contenido estático a nuestra web, simplemente modificando el fichero de rutas y devolviendo todo el contenido desde ese fichero. Para evitar tener que mantener un inmenso fichero `routes/web.php` con todo el código mezclado en el mismo archivo, en las siguientes secciones separaremos el código de las vistas y más adelante añadiremos los controladores.

En este ejercicio vamos a definir las rutas principales que va a tener nuestro sitio web.

Necesitaremos crear las vistas para poder realizar las operaciones **CRUD** sobre cada una de las tablas. De momento, las vistas únicamente deben devolver el texto con la _operación/visualización_ que deberán realizar en un futuro.

Las siguientes son las pantallas principales y un ejemplo del resultado del **CRUD** sobre la tabla 

Método | Ruta | Texto a mostrar
-------|------|-------
GET | `/` | Pantalla principal
GET | `login` | Login usuario
GET | `logout` | Logout usuario
GET | `tfcs` | Listado proyectos
GET | `tfcs/show/{id}` | Vista detalle proyecto {id}
GET | `tfcs/create` | Añadir proyecto
GET | `tfcs/edit/{id}` | Modificar proyecto {id}
GET | `perfil/{id}` | Visualizar el currículo de {id}

Debemos asegurarnos de que todos los parámetros `{id}` sean números naturales.

El parámetro `{id}` es opcional. En el caso de que exista debe mostrar _Visualizar el currículo de_ y el número enviado, mientras que en caso de no enviar ningún valor para ese parámetro se debería mostrar _Visualizar el currículo propio_

## Comprobar el ejercicio

Para comprobar que la solución desarrollada cumple con los requisitos, puedes copiar el archivo [RouteExerciseTest.php](./materiales/ejercicios-laravel/tests/Feature/RouteExerciseTest.php) a la carpeta `tests/Feature` de tu proyecto y, posteriormente, ejecutar el siguiente comando artisan:

```bash
php artisan test
```

El resultado debería ser similar al que se muestra a continuación:
<small>
<pre>
[alumno@vm1:~/Documentos/laravel/marcapersonalFP]$ php artisan test

<span style="background-color: lightgreen">PASS</span>  Tests\Unit\ExampleTest
<span style="color: lightgreen">✓</span> <span style="color: gray">that true is true</span>

<span style="background-color: lightgreen">PASS</span>  Tests\Feature\ExampleTest
<span style="color: lightgreen">✓</span> <span style="color: gray">the application returns a successful response</span>

<span style="background-color: lightgreen">PASS</span>  Tests\Feature\RouteExerciseTest
<span style="color: lightgreen">✓</span> <span style="color: gray">rutas</span>

<span style="color: gray">Tests:    <span style="color: lightgreen">3 passed</span> (24 assertions)</span>
</span>
</pre>
</small>

En el caso de obtener un resultado diferente, habrá que investigar cuál es la la condición `assert` que no se cumple e intentar reparar el error.