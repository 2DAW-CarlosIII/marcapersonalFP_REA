# 4.7.3. Ejercicios de Bases de Datos

En estos ejercicios vamos a continuar con la gestión de marcapersonalFP.es, que habíamos empezado en sesiones anteriores y le añadiremos todo lo referente a la gestión de la base de datos.

## Ejercicio 1 - Configuración de la base de datos y migraciones.

En primer lugar, vamos a comprobar la correcta configuración de la base de datos. Para esto, tenemos que abrir el fichero `.env` para comprobar que vamos a usar una base de datos tipo _MariaDB_ llamada `marcapersonalfp` junto con el nombre de usuario y contraseña de acceso.

A continuación, abrimos _PHPMyAdmin_ y comprobamos que el usuario llamado `marcapersonalfp` y la base de datos con el mismo nombre están creados. Por último, abriremos un terminal en la carpeta de nuestro proyecto y ejecutamos el comando que traslada las _migraciones_ realizadas hasta este momento.

```bash
php artisan migrate
```

> Si nos diese algún error tendremos que revisar los valores indicados en el fichero `.env`. En caso de ser correctos es posible que también tengamos que reiniciar el servidor o terminal que tengamos abierto.

Ahora vamos a crear la tabla que utilizaremos para almacenar los reconocimientos. Ejecuta el [comando de _Artisan_ para crear la migración](./042_migraciones.md#crear-una-nueva-migración) llamada `create_reconocimientos_table` para la tabla `reconocimientos`.

> Renombra el archivo como _`[año]_[mes]_[día]`_`_000003_create_reconocimientos_table.php`

Una vez creado, edita este fichero para añadir todos los campos necesarios, estos son:

Campo | Tipo | nullable
-----|----|---
estudiante_id | unsignedBigInteger | no
actividad_id | unsignedBigInteger | no
documento | string | sí
docente_validador | unsignedBigInteger | sí

> La fecha no es necesaria porque el atributo `created_at` contendrá la fecha de creación.

> Recuerda que, en el método `down()` de la migración, tienes que deshacer los cambios que has hecho en el método `up()`, en este caso, sería eliminar la tabla.

Por último, ejecutaremos el [comando de _Artisan_ que añade las nuevas migraciones](./042_migraciones.md#ejecutar-migraciones) y comprobaremos que la tabla se ha creado correctamente con los campos que le hemos indicado.

## Ejercicio 2 - Modelo de datos

En este ejercicio vamos a crear el modelo de datos asociado con la tabla `reconocimientos`. Para esto usaremos el [comando apropiado de _Artisan_ para crear el modelo](./044_modelosORM.md#definición-de-un-modelo) llamado `Reconocimiento`.

Una vez creado este fichero, lo abriremos y comprobaremos que el nombre de la clase sea el correcto y que herede de la clase `Model`. Y ya está, no es necesario hacer nada más, el cuerpo de la clase puede estar vacío (`{}`), todo lo demás se hace **automáticamente**!

## Ejercicio 3 - Semillas

Ahora vamos a proceder a rellenar la tabla de la base de datos con los datos iniciales. Para esto, editamos el fichero de _semillas_ situado en `database/seeders/DatabaseSeeder.php` y seguiremos los siguientes pasos:

1. [Creamos un fichero semilla](./045_databaseSeeding.md#crear-ficheros-semilla) llamado `ReconocimientosTableSeeder` que se tendrá que llamar desde el método `run()` de la clase `DatabaseSeeder`:

    ```php
        public function run(): void
        {
            Model::unguard();
            Schema::disableForeignKeyConstraints();

            // llamadas a otros ficheros de seed
            $this->call(ReconocimientosTableSeeder::class);
            // llamadas a otros ficheros de seed

            Model::reguard();

            Schema::enableForeignKeyConstraints();
        }
    ```
2. Movemos el array de reconocimientos que se facilitaba en los materiales y que habíamos copiado dentro del controlador `ReconocimientoController` a la clase de _semillas_ (`ReconocimientosTableSeeder`), guardándolo como variable privada de la clase.

3. Dentro del método `run()` de la clase `ReconocimientosTableSeeder` realizamos las siguientes acciones:

    1. En primer lugar borramos el contenido de la tabla `reconocimientos` con `Reconocimiento::truncate();`.
    1. Y, a continuación, añadimos el siguiente código:
        ```php
            foreach( self::$arrayReconocimientos as $reconocimiento ) {
                $recon = new Reconocimiento;
                $recon->estudiante_id = $reconocimiento['estudiante_id'];
                $recon->actividad_id = $reconocimiento['actividad_id'];
                $recon->documento = $reconocimiento['documento'];
                $recon->docente_validador = $reconocimiento['docente_validador'];
                $recon->save();
            }
        ```

4. Por último, tendremos que ejecutar el comando de _Artisan_ que procesa las _semillas_ y, una vez realizado, comprobaremos que se rellenado la tabla `reconocimientos` con el listado de reconocimientos.

> Si te aparece el error "`Fatal error: Class 'Reconocimiento' not found`" revisa si has indicado el _espacio de nombres_ del modelo que vas a utilizar (`use App\Models\Reconocimiento;`).

## Ejercicio 4 - Uso de la base de datos

En este último ejercicio, vamos a actualizar los métodos del controlador `ReconocimientoController` para que obtengan los datos desde la base de datos. Seguiremos los siguientes pasos:

1. Modificar el método `getIndex()` para que obtenga toda la lista de reconocimientos desde la base de datos usando el modelo Reconocimiento y que se pase a la vista ese listado.
1. Modificar el método `getShow()` para que obtenga el reconocimiento pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho reconocimiento.
1. Modificar el método `getEdit()` para que obtenga el reconocimiento pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho reconocimiento.

> Si al probarlo te aparece el error `Class 'App\Http\Controllers\Reconocimiento' not found` revisa si has indicado el espacio de nombres del modelo que vas a utilizar (`use App\Models\Reconocimiento;`).

Ya no necesitaremos más el _array_ de reconocimientos (`$arrayReconocimientos`) que habíamos puesto en el controlador, así que lo podemos eliminar.

Ahora tendremos que actualizar las vistas para que, en lugar de acceder a los datos del _array_, los obtenga del objeto con el reconocimiento. Para esto, cambiaremos en todos los sitios donde hayamos puesto `$reconocimiento['campo']` por `$reconocimiento->campo`.

Además, en la vista `proyectos/index.blade.php`, en vez de utilizar el índice del _array_ (`$key`) como identificador para crear el enlace a `proyectos/show/{id}`, tendremos que utilizar el campo `id` del reconocimiento (`$reconocimiento->id`). Lo mismo en la vista `proyectos/show.blade.php`, para generar el enlace de editar reconocimiento tendremos que añadir el identificador del reconocimiento a la ruta `proyectos/edit`.
