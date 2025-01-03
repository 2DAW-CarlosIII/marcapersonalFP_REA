# 4.7.1. Ejercicios de Bases de Datos de Familias Profesionales

En estos ejercicios vamos a continuar con la gestión de _marcapersonalFP.es_, que habíamos empezado en sesiones anteriores y le añadiremos todo lo referente a la gestión de la base de datos.

## Ejercicio 1 - Configuración de la base de datos y migraciones.

En primer lugar, vamos a comprobar la correcta configuración de la base de datos. Para esto, tenemos que abrir el fichero `.env` para comprobar que vamos a usar una base de datos tipo _MariaDB_ llamada `marcapersonalfp` junto con el nombre de usuario y contraseña de acceso.

A continuación, abrimos _PHPMyAdmin_ y comprobamos que el usuario llamado `marcapersonalfp` y la base de datos con el mismo nombre están creados. Por último, abriremos un terminal en la carpeta de nuestro proyecto y ejecutamos el comando que traslada las _migraciones_ realizadas hasta este momento.

```bash
php artisan migrate
```

> Si nos diese algún error tendremos que revisar los valores indicados en el fichero `.env`. En caso de ser correctos es posible que también tengamos que reiniciar el servidor o terminal que tengamos abierto.

Ahora vamos a crear la tabla que utilizaremos para almacenar las familias profesionales. Ejecuta el [comando de _Artisan_ para crear la migración](./042_migraciones.md#crear-una-nueva-migración) llamada `create_familias_profesionales_table` para la tabla `familias_profesionales`.

> Renombra el archivo como _`[año]_[mes]_[día]`_`_000001_create_familias_profesionales_table.php`

Una vez creado, edita este fichero para añadir todos los campos necesarios, estos son:

Campo | Tipo | nullable
-----|----|---
codigo | char(4) | no
nombre | string(200) | no

> Recuerda que, en el método `down()` de la migración, tienes que deshacer los cambios que has hecho en el método `up()`, en este caso, sería eliminar la tabla.

Por último, ejecutaremos el [comando de _Artisan_ que añade las nuevas migraciones](./042_migraciones.md#ejecutar-migraciones) y comprobaremos que la tabla se ha creado correctamente con los campos que le hemos indicado.

## Ejercicio 2 - Modelo de datos

En este ejercicio vamos a crear el modelo de datos asociado con la tabla `familias_profesionales`. Para esto usaremos el [comando apropiado de _Artisan_ para crear el modelo](./044_modelosORM.md#definición-de-un-modelo) llamado `FamiliaProfesional`.

Una vez creado este fichero, lo abriremos y comprobaremos que el nombre de la clase sea el correcto y que herede de la clase `Model`.

En el caso del modelo `FamiliaProfesional` será necesario especificar el nombre de la tabla a la que está asociado, ya que dicho nombre no se forma con el plural del modelo.

```php
    protected $table = 'familias_profesionales';
```

## Ejercicio 3 - Semillas

Ahora vamos a proceder a rellenar la tabla de la base de datos con los datos iniciales. Para esto, editamos el fichero de _semillas_ situado en `database/seeders/DatabaseSeeder.php` y seguiremos los siguientes pasos:

1. [Creamos un fichero semilla](./045_databaseSeeding.md#crear-ficheros-semilla) llamado `FamiliasProfesionalesTableSeeder` que se tendrá que llamar desde el método `run()` de la clase `DatabaseSeeder`:

    ```php
        public function run(): void
        {
            Model::unguard();
            Schema::disableForeignKeyConstraints();

            // llamadas a otros ficheros de seed
            $this->call(FamiliasProfesionalesTableSeeder::class);
            // llamadas a otros ficheros de seed

            Model::reguard();

            Schema::enableForeignKeyConstraints();
        }
    ```
2. Para poder trabajar con datos reales de las familias profesionales, el contenido del archivo [FamiliasProfesionalesTableSeeder](./materiales/ejercicios-laravel/FamiliasProfesionalesTableSeeder.php) está disponible en los materiales.

3. Por último, tendremos que ejecutar el comando de _Artisan_ que procesa las _semillas_ y, una vez realizado, comprobaremos que se rellenado la tabla `familias_profesionales` con las 26 familias profesionales existentes.

> Si te aparece el error "`Fatal error: Class 'FamiliaProfesional' not found`" revisa si has indicado el _espacio de nombres_ del modelo que vas a utilizar (`use App\Models\FamiliaProfesional;`).

## Ejercicio 4 - Uso de la base de datos

### Ciclos Formativos

Para poder utilizar los datos de _ciclos formativos_, debemos generar los controladores, rutas y vistas correspondientes. Para ello, sigue los pasos de los ejercicios de la [sección 3 ](./036_ejerciciosControladores.md) adaptándolos para los datos ciclos formativos.

Los datos que los controladores anteriores deben suministrar a las vistas, ya no estarán almacenados en _arrays_ sino que se obtendrán de la base de datos según las siguientes directrices:

1. Adapta el método `getIndex()` para que obtenga toda la lista de ciclos desde la base de datos usando el modelo `Ciclo` y que se pase a la vista ese listado.
1. Adapta el método `getShow()` para que obtenga el ciclo pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho ciclo.
1. Adapta el método `getEdit()` para que obtenga el ciclo pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho ciclo.

> Si al probarlo te aparece el error `Class 'App\Http\Controllers\Ciclo' not found` revisa si has indicado el espacio de nombres del modelo que vas a utilizar (`use App\Models\Ciclo;`).

También deberás actualizar las vistas para que, en lugar de acceder a los datos del _array_, los obtenga del objeto con el ciclo utilizando `$ciclo->campo`.

Además, en la vista `ciclos/index.blade.php`, en vez de utilizar el índice del _array_ (`$key`) como identificador para crear el enlace a `ciclos/show/{id}`, tendremos que utilizar el campo `id` del ciclo (`$ciclo->id`). Lo mismo en la vista `ciclos/show.blade.php`, para generar el enlace de editar ciclo tendremos que añadir el identificador del ciclo a la ruta `ciclos/edit`.

### Familias Profesionales

Para poder utilizar los datos de las _familias profesionales_, debemos generar los controladores, rutas y vistas correspondientes. Para ello, sigue los pasos de los ejercicios de la [sección 3 ](./036_ejerciciosControladores.md) adaptándolos para los datos familias profesionales.

Los datos que los controladores anteriores deben suministrar a las vistas, ya no estarán almacenados en _arrays_ sino que se obtendrán de la base de datos según las siguientes directrices:

1. Adapta el método `getIndex()` para que obtenga toda la lista de familias profesionales desde la base de datos usando el modelo `FamiliaProfesional` y que se pase a la vista ese listado.
1. Adapta el método `getShow()` para que obtenga la familia profesional pasada por parámetro usando el método `findOrFail()` y se pase a la vista dicha familia profesional.
1. Adapta el método `getEdit()` para que obtenga la familia profesional pasada por parámetro usando el método `findOrFail()` y se pase a la vista dicha familia profesional.

> Si al probarlo te aparece el error `Class 'App\Http\Controllers\FamiliaProfesional' not found` revisa si has indicado el espacio de nombres del modelo que vas a utilizar (`use App\Models\FamiliaProfesional;`).

También deberás actualizar las vistas para que, en lugar de acceder a los datos del _array_, los obtenga del objeto con la familia profesional utilizando `$familiaProfesional->campo`.

Además, en la vista `familias_profesionales/index.blade.php`, en vez de utilizar el índice del _array_ (`$key`) como identificador para crear el enlace a `familias_profesionales/show/{id}`, tendremos que utilizar el campo `id` de la familia profesional (`$familiaProfesional->id`). Lo mismo en la vista `familias_profesionales/show.blade.php`, para generar el enlace de editar familia profesional tendremos que añadir el identificador de la familia profesional a la ruta `familias_profesionales/edit`.
