# 5.3. Ejercicios

En los ejercicios de esta sección vamos a completar la gestión de proyectos, terminando el procesamiento de los formularios y añadiendo el sistema de autenticación de usuarios.

## Instalación de Breeze

En este momento, deberíamos tener instalado el _**Starter Kit de Laravel Breeze**_, que incluye el sistema de autenticación de usuarios. En caso contrario, regresa al capítulo [Control de usuarios](./052_Autenticacion.md) y sigue las instrucciones para instalarlo.

## Migración de la tabla usuarios

En primer lugar, vamos a comprobar la existencia de la tabla de la base de datos para almacenar los usuarios que tendrán acceso a la plataforma de gestión de proyectos.

Como hemos visto en la teoría, _Laravel_ ya incluye una migración con el nombre `create_users_table` para la tabla `users` con todos los campos necesarios. Vamos a abrir esta migración y a comprobar que los campos incluidos coinciden con los de la siguiente tabla, añadiendo los que no existan:

Campo | Tipo | Modificador
--|--|--
id | Autoincremental | 
name | String | 
email | String | unique
email_verified_at | timestamp | nullable
password | String | 
remember_token | Campo remember_token | 
timestamps | Timestamps de Eloquent |  

Crearemos una nueva migración para añadir los campos `nombre` y `apellidos`:

Campo | Tipo | Modificador
--|--|--
nombre | String | 50 nullable
apellidos | String | 100 nullable

Para esto usamos el comando de _Artisan_ que crea las migraciones y editamos el fichero creado en `database/migrations`.

```bash
php artisan make:migration add_nombre_apellidos_to_users_table --table=users
```

> Renombra el fichero como _`[año]_[mes]_[día]`_`_000001_add_nombre_apellidos_to_users_table.php`.

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('nombre', 50)->after('name')->nullable();
        $table->string('apellidos', 100)->after('nombre')->nullable();
    });
}
```

Comprueba también que en el método `down()` de la migración se deshagan los cambios que se hacen en el método `up()`, en este caso sería eliminar los campos añadidos.

```php
public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('nombre');
        $table->dropColumn('apellidos');
    });
}
```

Por último, usamos el comando de Artisan que añade las nuevas migraciones y comprobamos con PHPMyAdmin que la tabla contiene todos los campos indicados.

```bash
php artisan migrate
```
> Podríamos haber sustituido el atributo `name` por `nombre`y haber añadido únicamente el atributo `apellidos` pero, por simplicidad, se ha considerado más conveniente añadir los dos como nuevos atributos de la tabla `users`.

## Registro de `users`

Como hemos añadido los campos `nombre` y `apellidos` a la tabla de `users`, tendremos que actualizar el controlador `RegisteredUserController`: `validate` y `create`. En la llamada a `validate()` simplemente tendremos que añadir `nombre` y `apellidos` al _array_ de validaciones como campos requeridos, mientras que en la invocación del método `create()` tendremos que añadir esos campos para almacenarlos. También habrá que modificar el _array_ que devuelve el método `rules()` de `app/Http/Requests/ProfileUpdateRequest.php`.

También tendremos que modificar el formulario de registro (`resources/views/auth/register.blade.php`) para añadir los campos `nombre` y `apellidos`:

```php
        <!-- Nombre -->
        <div>
            <x-input-label for="nombre" :value="__('Nombre')" />
            <x-text-input id="nombre" class="block mt-1 w-full" type="text" name="nombre" :value="old('nombre')" required autofocus autocomplete="nombre" />
            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
        </div>
        <!-- Apellidos -->
        <div>
            <x-input-label for="apellidos" :value="__('Apellidos')" />
            <x-text-input id="apellidos" class="block mt-1 w-full" type="text" name="apellidos" :value="old('apellidos')" required autofocus autocomplete="apellidos" />
            <x-input-error :messages="$errors->get('apellidos')" class="mt-2" />
        </div>

        <!-- Email Address -->
```

Añade también esos campos a la propiedad `$fillable` del modelo `User`.

> Trata de modificar también el formulario de edición de un usuario (`resources/views/profile/partials/update-profile-information-form.blade.php`).

## Seeder de usuarios

Ahora vamos a proceder a rellenar la tabla `users` con los datos iniciales. Para esto, editamos el fichero de _semillas_ situado en `database/seeders/DatabaseSeeder.php` y seguiremos los siguientes pasos:

1. [Creamos un fichero semilla](./045_databaseSeeding.md#crear-ficheros-semilla) llamado `UsersTableSeeder` que se tendrá que llamar desde el método `run()` de la clase `DatabaseSeeder`:

    ```php
        public function run(): void
        {
            Model::unguard();
            Schema::disableForeignKeyConstraints();

            // llamadas a otros ficheros de seed
            $this->call(UsersTableSeeder::class);
            // llamadas a otros ficheros de seed

            Model::reguard();

            Schema::enableForeignKeyConstraints();
        }
    ```
2. Modificaremos el fichero _factory_ de users en el que añadiremos los métodos `fake()` para el nombre y los apellidos:

    atributo | fake()
    --|--
    nombre | firstName()
    apellidos | lastName()

3. Dentro del método `run()` de la clase `UsersTableSeeder` realizamos las siguientes acciones:

    1. En primer lugar borramos el contenido de la tabla `users` con `User::truncate();`.
    1. Trasladamos la creación del usuario administrador y la creación de los 10 usuarios que hay en el fichero `database/seeders/DatabaseSeeder.php`.

4. Por último, tendremos que ejecutar el comando de _Artisan_ que procesa las _semillas_ y, una vez realizado, comprobaremos que se rellenado la tabla `users` con un listado de 10 usuarios.

> Si te aparece el error "`Fatal error: Class 'User' not found`" revisa si has indicado el _espacio de nombres_ del modelo que vas a utilizar (`use App\Models\User;`).

Por último tendremos que ejecutar el comando de Artisan que procesa las semillas. Una vez realizado esto comprobamos en PHPMyAdmin que se han añadido los usuarios a la tabla users.

## Añadir y editar proyectos

Protegeremos las rutas de creación y de edición de las diferentes tablas con el **middleware** `auth`.
