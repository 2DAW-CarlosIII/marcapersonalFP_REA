# 4.5. Inicialización de la base de datos (Database Seeding)

_Laravel_ también facilita la inserción de datos iniciales o _datos semilla_ en la base de datos. Esta opción es muy útil para tener datos de prueba cuando estamos desarrollando una web o para crear tablas que ya tienen que contener una serie de datos en producción.

Los ficheros de _semillas_ se encuentran en la carpeta `database/seeders`. Por defecto _Laravel_ incluye el fichero `DatabaseSeeder.php` con el siguiente contenido:

```php
<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
```

Al lanzar la incialización se llamará por defecto al método `run()` de la clase `DatabaseSeeder`. Desde aquí podemos crear las semillas de varias formas:

1. Escribir el código para insertar los datos dentro del propio método `run()`.
1. Crear otros métodos dentro de la clase `DatabaseSeeder` y llamarlos desde el método `run()`. De esta forma podemos separar mejor las inicializaciones.
1. Crear otros ficheros `Seeder` y llamarlos desde el método `run()` de la clase principal.

Según lo que vayamos a hacer nos puede interesar una opción u otra. Por ejemplo, si el código que vamos a escribir es poco nos puede sobrar con las opciones 1 o 2, sin embargo si necesitamos realizar bastantes inicializaciones, quizás lo mejor es la opción 3.

Un primer ejemplo puede ser descomentar las líneas comentadas en el método `run()` de la clase `DatabaseSeeder`:

```php
<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
```

El código anterior genería 11 registros del modelo `User`. Para ello, hace uso de los `factories`, que veremos en una sección posterior.

Para insertar datos en una tabla también podríamos utilizar el "Constructor de consultas" y "Eloquent ORM":

```php
<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $estudiante = new \App\Models\Estudiante;
        $estudiante->nombre = 'Juan';
        $estudiante->apellidos = 'Martínez';
        $estudiante->direccion = 'Dirección de Juan';
        $estudiante->votos = 130;
        $estudiante->confirmado = true;
        $estudiante->ciclo = 'DAW';
        $estudiante->save();
    }
}
```

## Crear ficheros semilla

Como hemos visto en el apartado anterior, podemos crear más ficheros o clases _semilla_ para modularizar mejor el código de las inicializaciones. De esta forma podemos crear un fichero de semillas para cada una de las tablas o modelos de datos que tengamos.

En la carpeta `database/seeders` podemos añadir más ficheros _PHP_ con clases que extiendan de `Seeder` para definir nuestros propios ficheros de _semillas_. El nombre de los ficheros suele seguir el mismo patrón `<nombre-tabla>TableSeeder`, por ejemplo `EstudiantesTableSeeder`. _Artisan_ incluye un comando que nos facilitará crear los ficheros de semillas y que además incluirán las estructura base de la clase. Por ejemplo, para crear el fichero de inicialización de la tabla de `estudiantes` haríamos:

```bash
php artisan make:seeder EstudiantesTableSeeder
```

Para que esta nueva clase se ejecute tenemos que llamarla desde el método `run()` de la clase principal `DatabaseSeeder` de la forma:

```php
<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Model::unguard();
        Schema::disableForeignKeyConstraints();

        $this->call(EstudiantesTableSeeder::class);

        Model::reguard();

        Schema::enableForeignKeyConstraints();
    }
}
```

De este modo, podemos trasladar la creación de un `Estudiante` a la clase `EstudiantesTableSeeder`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstudiantesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estudiante = new \App\Models\Estudiante;
        $estudiante->nombre = 'Juan';
        $estudiante->apellidos = 'Martínez';
        $estudiante->direccion = 'Dirección de Juan';
        $estudiante->votos = 130;
        $estudiante->confirmado = true;
        $estudiante->ciclo = 'DAW';
        $estudiante->save();
    }
}
```

El método `call()` de `DatabaseSeeder` lo que hace es llamar al método `run()` de la clase indicada. Además, en el ejemplo hemos añadido las llamadas a `unguard()` y a `reguard()`, que lo que hacen es desactivar y volver a activar (respectivamente) la inserción de datos masiva o por lotes.

## Ejecutar la inicialización de datos

Una vez definidos los ficheros de semillas, cuando queramos ejecutarlos para rellenar de datos la base de datos tendremos que usar el siguiente comando de Artisan:

```bash
php artisan db:seed
```

## Factories

En _Laravel_, las _factories_ son herramientas poderosas para generar datos de prueba de manera fácil y eficiente. Facilitan la creación de registros ficticios para tus modelos.

### Creación de la Factory

Para la creación de las _factories_ también utilizaremos _Artisan_, que almacenará el fichero en el directorio `database/factories`:

```bash
php artisan make:factory EstudianteFactory --model=Estudiante
```

Las _factories_, a menudo, hacen uso de la librería _Faker_ de _PHP_ para generar datos ficticios.

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory
 */
class EstudianteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'direccion' => fake()->address(),
            'votos' => fake()->numberBetween(50, 150),
            'confirmado' => fake()->boolean(),
            'ciclo' => fake()->randomElement(['ASIR', 'DAW', 'DAM']),
            'user_id' => null,
        ];
    }
}
```

### Uso de la Factory

Habitualmente, las _factories_ las utilizaremos desde los test, aunque en esta ocasión las vamos a utilizar como ficheros para el _seeding_. Para ello, modificaremos el archivo `database/EstudiantesTableSeeder.php` para invocar la creación de 10 estudiantes.

```php
    public function run(): void
    {
        \App\Models\Estudiante::factory(10)->create();
    }
```