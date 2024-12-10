# 4.7.1. Ejercicios de Bases de Datos de Docentes

En estos ejercicios vamos a continuar con la gestión de marcapersonalFP.es, que habíamos empezado en sesiones anteriores y le añadiremos todo lo referente a la gestión de la base de datos.

## Ejercicio 1 - Configuración de la base de datos y migraciones.

En primer lugar, vamos a comprobar la correcta configuración de la base de datos. Para esto, tenemos que abrir el fichero `.env` para comprobar que vamos a usar una base de datos tipo _MariaDB_ llamada `marcapersonalfp` junto con el nombre de usuario y contraseña de acceso.

A continuación, abrimos _PHPMyAdmin_ y comprobamos que el usuario llamado `marcapersonalfp` y la base de datos con el mismo nombre están creados. Por último, abriremos un terminal en la carpeta de nuestro proyecto y ejecutamos el comando que traslada las _migraciones_ realizadas hasta este momento.

```bash
php artisan migrate
```

> Si nos diese algún error tendremos que revisar los valores indicados en el fichero `.env`. En caso de ser correctos es posible que también tengamos que reiniciar el servidor o terminal que tengamos abierto.

Ahora vamos a crear la tabla que utilizaremos para almacenar los docentes. Ejecuta el [comando de _Artisan_ para crear la migración](./042_migraciones.md#crear-una-nueva-migración) llamada `create_docentes_table` para la tabla `docentes`.

> Renombra el archivo como _`[año]_[mes]_[día]`_`_000001_create_docentes_table.php`

Una vez creado, edita este fichero para añadir todos los campos necesarios, estos son:

Campo | Tipo | nullable
-----|----|---
nombre | string(32) | no
apellidos | string(32) | sí
direccion | string() | sí
departamento | enum ['Administración', 'Comercio, Informática', 'Relaciones con las empresas', 'DIOP', 'Innovación'] | sí

> Recuerda que, en el método `down()` de la migración, tienes que deshacer los cambios que has hecho en el método `up()`, en este caso, sería eliminar la tabla.

Por último, ejecutaremos el [comando de _Artisan_ que añade las nuevas migraciones](./042_migraciones.md#ejecutar-migraciones) y comprobaremos que la tabla se ha creado correctamente con los campos que le hemos indicado.

## Ejercicio 2 - Modelo de datos

En este ejercicio vamos a crear el modelo de datos asociado con la tabla `docentes`. Para esto usaremos el [comando apropiado de _Artisan_ para crear el modelo](./044_modelosORM.md#definición-de-un-modelo) llamado `Docente`.

Una vez creado este fichero, lo abriremos y comprobaremos que el nombre de la clase sea el correcto y que herede de la clase `Model`. Y ya está, no es necesario hacer nada más, el cuerpo de la clase puede estar vacío (`{}`), todo lo demás se hace **automáticamente**!

## Ejercicio 3 - Semillas

Ahora vamos a proceder a rellenar la tabla de la base de datos con los datos iniciales. Para esto, editamos el fichero de _semillas_ situado en `database/seeders/DatabaseSeeder.php` y seguiremos los siguientes pasos:

1. [Creamos un fichero semilla](./045_databaseSeeding.md#crear-ficheros-semilla) llamado `DocentesTableSeeder` que se tendrá que llamar desde el método `run()` de la clase `DatabaseSeeder`:

    ```php
        public function run(): void
        {
            Model::unguard();
            Schema::disableForeignKeyConstraints();

            // llamadas a otros ficheros de seed
            $this->call(DocentesTableSeeder::class);
            // llamadas a otros ficheros de seed

            Model::reguard();

            Schema::enableForeignKeyConstraints();
        }
    ```
2. [Crearemos un fichero _factory_](./045_databaseSeeding.md#creación-de-la-factory) en el que utilizaremos los siguientes métodos `fake()`

    atributo | fake()
    --|--
    nombre | firstName()
    apellidos | lastName()
    direccion | address()
    ciclo | randomElement(['Administración', 'Comercio', Informática', 'Relaciones con las empresas', 'DIOP', 'Innovación'])

3. Dentro del método `run()` de la clase `DocentesTableSeeder` realizamos las siguientes acciones:

    1. En primer lugar borramos el contenido de la tabla `docentes` con `Docente::truncate();`.
    1. Y, a continuación, añadimos el [código necesario para la creación de 10 registros de docentes, con la utilización del factory](./045_databaseSeeding.md#uso-de-la-factory), creado en el punto anterior.

4. Por último, tendremos que ejecutar el comando de _Artisan_ que procesa las _semillas_ y, una vez realizado, comprobaremos que se rellenado la tabla `docentes` con un listado de 10 docentes.

> Si te aparece el error "`Fatal error: Class 'Docente' not found`" revisa si has indicado el _espacio de nombres_ del modelo que vas a utilizar (`use App\Models\Docente;`).

## Ejercicio 4 - Uso de la base de datos

### Estudiantes

Para poder utilizar los datos de _estudiantes_, debemos generar los controladores, rutas y vistas correspondientes. Para ello, sigue los pasos de los ejercicios de la [sección 3 ](./036_ejerciciosControladores.md) adaptándolos para los datos estudiantes.

Los datos que los controladores anteriores deben suministrar a las vistas, ya no estarán almacenados en _arrays_ sino que se obtendrán de la base de datos según las siguientes directrices:

1. Adapta el método `getIndex()` para que obtenga toda la lista de estudiantes desde la base de datos usando el modelo `Estudiante` y que se pase a la vista ese listado.
1. Adapta el método `getShow()` para que obtenga el estudiante pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho estudiante.
1. Adapta el método `getEdit()` para que obtenga el estudiante pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho estudiante.

> Si al probarlo te aparece el error `Class 'App\Http\Controllers\Estudiante' not found` revisa si has indicado el espacio de nombres del modelo que vas a utilizar (`use App\Models\Estudiante;`).

También deberás actualizar las vistas para que, en lugar de acceder a los datos del _array_, los obtenga del objeto con el estudiante utilizando `$estudiante->campo`.

Además, en la vista `estudiantes/index.blade.php`, en vez de utilizar el índice del _array_ (`$key`) como identificador para crear el enlace a `estudiantes/show/{id}`, tendremos que utilizar el campo `id` del estudiante (`$estudiante->id`). Lo mismo en la vista `estudiantes/show.blade.php`, para generar el enlace de editar estudiante tendremos que añadir el identificador del docente a la ruta `estudiantes/edit`.

### Docentes

Para poder utilizar los datos de _docentes_, debemos generar los controladores, rutas y vistas correspondientes. Para ello, sigue los pasos de los ejercicios de la [sección 3 ](./036_ejerciciosControladores.md) adaptándolos para los datos docentes.

Los datos que los controladores anteriores deben suministrar a las vistas, ya no estarán almacenados en _arrays_ sino que se obtendrán de la base de datos según las siguientes directrices:

1. Adapta el método `getIndex()` para que obtenga toda la lista de docentes desde la base de datos usando el modelo `Docente` y que se pase a la vista ese listado.
1. Adapta el método `getShow()` para que obtenga el docente pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho docente.
1. Adapta el método `getEdit()` para que obtenga el docente pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho docente.

> Si al probarlo te aparece el error `Class 'App\Http\Controllers\Docente' not found` revisa si has indicado el espacio de nombres del modelo que vas a utilizar (`use App\Models\Docente;`).

También deberás actualizar las vistas para que, en lugar de acceder a los datos del _array_, los obtenga del objeto con el docente utilizando `$docente->campo`.

Además, en la vista `docentes/index.blade.php`, en vez de utilizar el índice del _array_ (`$key`) como identificador para crear el enlace a `docentes/show/{id}`, tendremos que utilizar el campo `id` del docente (`$docente->id`). Lo mismo en la vista `docentes/show.blade.php`, para generar el enlace de editar docente tendremos que añadir el identificador del docente a la ruta `docentes/edit`.
