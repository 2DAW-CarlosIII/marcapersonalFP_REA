# 4.7. Ejercicios de Bases de Datos

En estos ejercicios vamos a continuar con la gestión de los proyectos que habíamos empezado en sesiones anteriores y le añadiremos todo lo referente a la gestión de la base de datos.

## Ejercicio 1 - Configuración de la base de datos y migraciones.

En primer lugar, vamos a comprobar la correcta configuración de la base de datos. Para esto, tenemos que abrir el fichero `.env` para comprobar que vamos a usar una base de datos tipo _MariaDB_ llamada `marcapersonalfp` junto con el nombre de usuario y contraseña de acceso.

A continuación, abrimos _PHPMyAdmin_ y comprobamos que el usuario llamado `marcapersonalfp` y la base de datos con el mismo nombre están creados. Por último, abriremos un terminal en la carpeta de nuestro proyecto y ejecutamos el comando que traslada las _migraciones_ realizadas hasta este momento.

```bash
php artisan migrate:fresh
```

Si todo va bien podremos actualizar desde _PHPMyAdmin_ y comprobar que se ha creado la tabla `migrations` dentro de nuestra nueva base de datos.

> Si nos diese algún error tendremos que revisar los valores indicados en el fichero `.env`. En caso de ser correctos es posible que también tengamos que reiniciar el servidor o terminal que tengamos abierto.

Ahora vamos a crear la tabla que utilizaremos para almacenar los proyectos. Ejecuta el [comando de _Artisan_ para crear la migración](./042_migraciones.md#crear-una-nueva-migración) llamada `create_proyectos_table` para la tabla `proyectos`.

> Renombra el archivo como _`[año]_[mes]_[día]`_`_000000_create_proyectos_table.php`

Una vez creado, edita este fichero para añadir todos los campos necesarios, estos son:

Campo | Tipo | nullable
-----|----|---
docente_id | unsignedBigInteger | sí
nombre | string(120) | no
dominio | string(30) | sí
metadatos | text | sí

> Recuerda que, en el método `down()` de la migración, tienes que deshacer los cambios que has hecho en el método `up()`, en este caso, sería eliminar la tabla.

Por último, ejecutaremos el [comando de _Artisan_ que añade las nuevas migraciones](./042_migraciones.md#ejecutar-migraciones) y comprobaremos que la tabla se ha creado correctamente con los campos que le hemos indicado.

## Ejercicio 2 - Modelo de datos

En este ejercicio vamos a crear el modelo de datos asociado con la tabla `proyectos`. Para esto usaremos el [comando apropiado de _Artisan_ para crear el modelo](./044_modelosORM.md#definición-de-un-modelo) llamado `Proyecto`.

Una vez creado este fichero, lo abriremos y comprobaremos que el nombre de la clase sea el correcto y que herede de la clase `Model`. Y ya está, no es necesario hacer nada más, el cuerpo de la clase puede estar vacío (`{}`), todo lo demás se hace **automáticamente**!

## Ejercicio 3 - Semillas

Ahora vamos a proceder a rellenar la tabla de la base de datos con los datos iniciales. Para esto, editamos el fichero de _semillas_ situado en `database/seeders/DatabaseSeeder.php` y seguiremos los siguientes pasos:

1. Creamos un método privado de clase llamado `seedProyectos()` que se tendrá que llamar desde el método `run()` de la forma:
```php
    public function run()
    {
    self::seedProyectos();
    $this->command->info('Tabla catálogo inicializada con datos!');
    }
```
2. Movemos el array de proyectos que se facilitaba en los materiales y que habíamos copiado dentro del controlador `ProyectosController` a la clase de _semillas_ (`DatabaseSeeder.php`), guardándolo como variable privada de la clase.
3. Dentro del nuevo método `seedProyectos()` realizamos las siguientes acciones:
    1. En primer lugar borramos el contenido de la tabla `proyectos` con `Proyecto::truncate();`.
    1. Y, a continuación, añadimos el siguiente código:
```php
    foreach( self::$arrayProyectos as $proyecto ) {
        $p = new Proyecto;
        $p->docente_id = $proyecto['docente_id'];
        $p->nombre = $proyecto['nombre'];
        $p->dominio = $proyecto['dominio'];
        $p->metadatos = serialize($proyecto['metadatos']);
        $p->save();
    }
```

>> Como se puede observar, a la hora de persistir el _array_ de metadatos, utilizamos la función `serialize()`, que lo convierte en un _String_. Cuando queramos volver a recrear el _array_, deberemos utilizar la función inversa `unserialize()`. 

4. Por último, tendremos que ejecutar el comando de _Artisan_ que procesa las _semillas_ y, una vez realizado, comprobaremos que se rellenado la tabla `proyectos` con el listado de proyectos.

> Si te aparece el error "`Fatal error: Class 'Proyecto' not found`" revisa si has indicado el _espacio de nombres_ del modelo que vas a utilizar (`use App\Models\Proyecto;`).

## Ejercicio 4 - Uso de la base de datos

En este último ejercicio, vamos a actualizar los métodos del controlador `ProyectosController` para que obtengan los datos desde la base de datos. Seguiremos los siguientes pasos:

1. Modificar el método `getIndex()` para que obtenga toda la lista de proyectos desde la base de datos usando el modelo Proyecto y que se pase a la vista ese listado.
1. Modificar el método `getShow()` para que obtenga el proyecto pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho proyecto.
1. Modificar el método `getEdit()` para que obtenga el proyecto pasado por parámetro usando el método `findOrFail()` y se pase a la vista dicho proyecto.

> Si al probarlo te aparece el error `Class 'App\Http\Controllers\Proyecto' not found` revisa si has indicado el espacio de nombres del modelo que vas a utilizar (`use App\Models\Proyecto;`).

Ya no necesitaremos más el _array_ de proyectos (`$arrayProyectos`) que habíamos puesto en el controlador, así que lo podemos eliminar.

Ahora tendremos que actualizar las vistas para que, en lugar de acceder a los datos del _array_, los obtenga del objeto con el proyecto. Para esto, cambiaremos en todos los sitios donde hayamos puesto `$proyecto['campo']` por `$proyecto->campo`.

Además, en la vista `proyectos/index.blade.php`, en vez de utilizar el índice del _array_ (`$key`) como identificador para crear el enlace a `proyectos/show/{id}`, tendremos que utilizar el campo `id` del proyecto (`$proyecto->id`). Lo mismo en la vista `proyectos/show.blade.php`, para generar el enlace de editar proyecto tendremos que añadir el identificador del proyecto a la ruta `proyectos/edit`.

## Comprobar el ejercicio

Para comprobar que la solución desarrollada cumple con los requisitos, se puede copiar el archivo [ProyectosDBTest.php](./materiales/ejercicios-laravel/tests/Feature/ProyectosDBTest.php) a la carpeta `tests/Feature` de tu proyecto y, posteriormente, ejecutar el siguiente comando artisan:

```bash
php artisan test
```

Aunque faltarían algunos aspectos para comprobar, la ejecución de los test debería devolver <span style="background-color: lightgreen">PASS</span> en color verde para cada uno de los tests.

En el caso de obtener un resultado diferente, habrá que investigar cuál es la la condición `assert` que no se cumple e intentar reparar el error.

# Otros ejercicios de Bases de Datos

- [FamiliasProfesionales](./0471_BDFamiliaProfesional.md)
- [Currículos](./0472_BDCurriculo.md)
- [Reconocimientos](./0473_BDReconocimiento.md)
- [Actividades](./0474_BDActividad.md)
