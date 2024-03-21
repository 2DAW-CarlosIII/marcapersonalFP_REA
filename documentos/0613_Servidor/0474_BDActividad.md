# 4.7.4. Ejercicios de Bases de Datos de Actividades

En estos ejercicios vamos a continuar con la gestión de marcapersonalFP.es, que habíamos empezado en sesiones anteriores y le añadiremos todo lo referente a la gestión de la base de datos.

## Ejercicio 1 - Configuración de la base de datos y migraciones.

En primer lugar, vamos a comprobar la correcta configuración de la base de datos. Para esto, tenemos que abrir el fichero `.env` para comprobar que vamos a usar una base de datos tipo _MySQL_ llamada `marcapersonalfp` junto con el nombre de usuario y contraseña de acceso.

A continuación, abrimos _PHPMyAdmin_ y comprobamos que el usuario llamado `marcapersonalfp` y la base de datos con el mismo nombre están creados. Por último, abriremos un terminal en la carpeta de nuestro proyecto y ejecutamos el comando que traslada las _migraciones_ realizadas hasta este momento.

```bash
php artisan migrate
```

> Si nos diese algún error tendremos que revisar los valores indicados en el fichero `.env`. En caso de ser correctos es posible que también tengamos que reiniciar el servidor o terminal que tengamos abierto.

Ahora vamos a crear la tabla que utilizaremos para almacenar las actividades. Ejecuta el [comando de _Artisan_ para crear la migración](./042_migraciones.md#crear-una-nueva-migración) llamada `create_actividades_table` para la tabla `actividades`.

> Renombra el archivo como _`[año_actual]`_`_11_29_000004_create_actividades_table.php`

Una vez creado, edita este fichero para añadir todos los campos necesarios, estos son:

Campo | Tipo | nullable
-----|----|---
docente_id | unsignedBigInteger | no
insignia | string() | sí

> Recuerda que, en el método `down()` de la migración, tienes que deshacer los cambios que has hecho en el método `up()`, en este caso, sería eliminar la tabla.

Por último, ejecutaremos el [comando de _Artisan_ que añade las nuevas migraciones](./042_migraciones.md#ejecutar-migraciones) y comprobaremos que la tabla se ha creado correctamente con los campos que le hemos indicado.

## Ejercicio 2 - Modelo de datos

En este ejercicio vamos a crear el modelo de datos asociado con la tabla `actividades`. Para esto usaremos el [comando apropiado de _Artisan_ para crear el modelo](./044_modelosORM.md#definición-de-un-modelo) llamado `Actividad`.

Una vez creado este fichero, lo abriremos y comprobaremos que el nombre de la clase sea el correcto y que herede de la clase `Model`.

En el caso del modelo `Actividad`, debemos [ajustar la propiedad `table`](./044_modelosORM.md#nombre) para que busque la tabla `actividades`. Si no ajustáramos esa propiedad, buscaría la tabla `actividads`, ya que el plural lo forma automáticamente añadiendo una 's' al nombre del modelo.

## Ejercicio 3 - Semillas

Ahora vamos a proceder a rellenar la tabla de la base de datos con los datos iniciales. Para esto, editamos el fichero de _semillas_ situado en `database/seeders/DatabaseSeeder.php` y seguiremos los siguientes pasos:

1. [Creamos un fichero semilla](./045_databaseSeeding.md#crear-ficheros-semilla) llamado `ActividadesTableSeeder` que se tendrá que llamar desde el método `run()` de la clase `DatabaseSeeder`:

    ```php
        public function run(): void
        {
            Model::unguard();
            Schema::disableForeignKeyConstraints();

            // llamadas a otros ficheros de seed
            $this->call(ActividadesTableSeeder::class);
            // llamadas a otros ficheros de seed

            Model::reguard();

            Schema::enableForeignKeyConstraints();
        }
    ```
2. Movemos el array de actividades que se facilitaba en los materiales y que habíamos copiado dentro del controlador `ActividadController` a la clase de _semillas_ (`ActividadesTableSeeder`), guardándolo como variable privada de la clase.

3. Dentro del método `run()` de la clase `ActividadesTableSeeder` realizamos las siguientes acciones:

    1. En primer lugar borramos el contenido de la tabla `actividades` con `Actividad::truncate();`.
    1. Y, a continuación, añadimos el siguiente código:
        ```php
            foreach( self::$arrayActividades as $actividad ) {
                $act = new Actividad;
                $act->docente_id = $actividad['title'];
                $act->insignia = $actividad['insignia'];
                $act->save();
            }
        ```

4. Por último, tendremos que ejecutar el comando de _Artisan_ que procesa las _semillas_ y, una vez realizado, comprobaremos que se rellenado la tabla `actividades` con el listado de actividades.

> Si te aparece el error "`Fatal error: Class 'Actividad' not found`" revisa si has indicado el _espacio de nombres_ del modelo que vas a utilizar (`use App\Models\Actividad;`).

## Ejercicio 4 - Uso de la base de datos

En este último ejercicio, vamos a actualizar los métodos del controlador `ActividadController` para que obtengan los datos desde la base de datos. Seguiremos los siguientes pasos:

1. Modificar el método `getIndex()` para que obtenga toda la lista de actividades desde la base de datos usando el modelo Actividad y que se pase a la vista ese listado.
1. Modificar el método `getShow()` para que obtenga el actividad pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho actividad.
1. Modificar el método `getEdit()` para que obtenga el actividad pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho actividad.

> Si al probarlo te aparece el error `Class 'App\Http\Controllers\Actividad' not found` revisa si has indicado el espacio de nombres del modelo que vas a utilizar (`use App\Models\Actividad;`).

Ya no necesitaremos más el _array_ de actividades (`$arrayActividades`) que habíamos puesto en el controlador, así que lo podemos eliminar.

Ahora tendremos que actualizar las vistas para que, en lugar de acceder a los datos del _array_, los obtenga del objeto con el actividad. Para esto, cambiaremos en todos los sitios donde hayamos puesto `$actividad['campo']` por `$actividad->campo`.

Además, en la vista `tfcs/index.blade.php`, en vez de utilizar el índice del _array_ (`$key`) como identificador para crear el enlace a `tfcs/show/{id}`, tendremos que utilizar el campo `id` del actividad (`$actividad->id`). Lo mismo en la vista `tfcs/show.blade.php`, para generar el enlace de editar actividad tendremos que añadir el identificador del actividad a la ruta `tfcs/edit`.
