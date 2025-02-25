# Roles y permisos.

Son numerosas las aplicaciones que combinan la autenticación de usuario con la gestión de roles y sus permisos. Esta gestión se puede simplificar con la utilización de librerías como _Laravel-permission_. Un explicación del uso básico de esa librería la podemos encontrar [aquí](https://spatie.be/docs/laravel-permission/v6/basic-usage/basic-usage).

Aunque la utilización de *permisos* tiene sus ventajas, para nuestra aplicación vamos a combinar las `policies` con los tipos de usuario o roles que pueden utilizar la aplicación:

- **usuarios anónimos**,
- **administrador**: aquel cuyo email coincide con `ADMIN_EMAIL`,
- **docentes**: todos aquellos que tienen un email del dominio `TEACHER_EMAIL_DOMAIN`,
- **estudiantes**:  todos aquellos que tienen un email del dominio `STUDENT_EMAIL_DOMAIN`,
- **propietario de un recurso**: si un recurso tiene una propiedad user_id, aquel usuario cuyo id coincide con el valor anterior,
- **empresas**: acceden mediante un token cuya gestión definiremos más adelante.

> Las variables de entorno `TEACHER_EMAIL_DOMAIN` y `STUDENT_EMAIL_DOMAIN` habrá que definirlas con los dominios asignados por la institución a docentes y estudiantes, tanto en el fichero `.env.example` como en el archivo `.env`.

Para gestionar estos **_roles_**, vamos a crear sendos métodos en el modelo `User`, como se muestra a continuación:

```php
    // Gestión de roles
    public function esAdmin(): bool
    {
        return $this->email === env('ADMIN_EMAIL');
    }

    public function esDocente(): bool
    {
        return $this->getEmailDomain() === env('TEACHER_EMAIL_DOMAIN');
    }

    public function esEstudiante(): bool
    {
        return $this->getEmailDomain() === env('STUDENT_EMAIL_DOMAIN');
    }

    public function esPropietario($recurso, $propiedad = 'user_id'): bool
    {
        return $recurso && $recurso->$propiedad === $this->id;
    }

    private function getEmailDomain(): string
    {
        $dominio = explode('@', $this->email)[1];
        return $dominio;
    }  
```

## Generar usuarios con roles

Además, queremos modificar la inicialización de los usuarios para tener 10 docentes y 30 estudiantes, por lo que debemos modificar los archivos correspondientes

```diff
// en database/seeders/UsersTableSeeder.php
    public function run(): void
             'password' => env('ADMIN_PASSWORD', 'password'),
         ]);
 
-        User::factory(10)->create();
+        // Crear 10 usuarios con el estado docente
+        User::factory(10)->docente()->create();
+        // Crear 30 usuarios con el estado estudiante
+        User::factory(30)->estudiante()->create();
 
 
    }
```

```diff
// en database/factories/UserFactory.php
     public function definition(): array
     {
         return [
-            'name' => fake()->name(),
+            'name' => fake()->unique()->userName(),
             'email' => fake()->unique()->safeEmail(),
             'email_verified_at' => now(),
             'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
             'remember_token' => Str::random(10),
@@ -37,4 +36,24 @@ public function unverified(): static
             'email_verified_at' => null,
         ]);
     }
+
+    /**
+     * Indicate that the model's email  should be student.
+     */
+    public function estudiante(): static
+    {
+        return $this->state(fn (array $attributes) => [
+            'email' => $attributes['name'] . '@' . env('STUDENT_EMAIL_DOMAIN', 'student.com'),
+        ]);
+    }
+
+    /**
+     * Indicate that the model's email  should be teacher.
+     */
+    public function docente(): static
+    {
+        return $this->state(fn (array $attributes) => [
+            'email' => $attributes['name'] . '@' . env('TEACHER_EMAIL_DOMAIN', 'teacher.com'),
+        ]);
+    }
 }
```
## Adaptar las `policies` a los roles anteriores

Después de los cambios anteriores, podríamos modificar el fichero de _políticas_ asociadas a `currículo` de la siguiente forma:

```diff
// en app/Providers/AppServiceProvider.php
  Gate::before(function (User $user, string $ability) {
      if ($user->esAdmin()) {
          return true;
      }
  });

// en app/Policies/CurriculoPolicy.php
     /**
     * Determine whether the user can create models.
     */
     public function create(User $user): bool
     {
-        return str_ends_with($user->email, env('STUDENT_DOMAIN', '@alu.murciaeduca.es'));
+        return $user->esEstudiante();
     }
 
     /**
     * Determine whether the user can update the model.
     */
     public function update(User $user, Curriculo $curriculo): bool
     {
-        return $user->id === $curriculo->user_id;
+        return $user->esPropietario($curriculo);
     } 
```

## Permitir el acceso a algunos métodos a usuarios anónimos

Existen varias soluciones para permitir el acceso a usuarios anónimos a algunos métodos de la API. Las que vamos a ver a continuación pasan, en cualquier caso, por indicar al fichero de _policy_ que algunos de esos métodos se ejecutarán sin la existencia de un usuario autenticado. Para ello, vamos a declarar que el argumento `$user` será opcional para aquellos métodos que permitan el acceso anónimo.

```diff
     /**
      * Determine whether the user can view any models.
      */
-    public function viewAny(User $user): bool
+    public function viewAny(?User $user): bool
     {
-        //
+        return true;
     }
 
     /**
      * Determine whether the user can view the model.
      */
-    public function view(User $user, Curriculo $curriculo): bool
+    public function view(?User $user, Curriculo $curriculo): bool
     {
-        //
+        return true;
     }

```

### Sacar las rutas index y show fuera del middleware de autenticación

Una solución es sacar las rutas `index` y `show` fuera del middleware de autenticación, de forma que no se compruebe la existencia de un usuario autenticado. Para ello, debemos modificar el fichero `routes/api.php` de la siguiente forma:

```diff
// en routes/api.php
 Route::prefix('v1')->group(function () {
+    Route::get('curriculos', [CurriculoController::class, 'index']);
+    Route::get('curriculos/{curriculo}', [CurriculoController::class, 'show']);
     Route::middleware('auth:sanctum')->group(function () {
         Route::get('/user', function (Request $request) {
             $user = $request->user();
@@ -45,7 +47,8 @@
         Route::apiResource('familias_profesionales', FamiliaProfesionalController::class)->parameters([
             'familias_profesionales' => 'familiaProfesional'
         ]);
-        Route::apiResource('curriculos', CurriculoController::class);
+        Route::apiResource('curriculos', CurriculoController::class)
+            ->except(['index', 'show']);
         Route::apiResource('actividades', ActividadController::class)->parameters([
             'actividades' => 'actividad'
         ]);
```

El problema de esta solución es que, como habría que aplicarla a todos los controladores, el fichero de rutas perdería legibilidad.

### Trasladar el _middleware_ de autenticación a los controladores

Otra solución es trasladar el _middleware_ de autenticación a los controladores, de forma que se pueda aplicar a todos los métodos excepto a los que queramos que sean accesibles a usuarios anónimos. Para ello, debemos

- sacar todas las rutas `apiResource` fuera del middleware de autenticación,

```diff
// en routes/api.php
 Route::prefix('v1')->group(function () {
     Route::middleware('auth:sanctum')->group(function () {
         Route::get('/user', function (Request $request) {
             $user = $request->user();
             $user->fullName = $user->nombre . ' ' . $user->apellidos;
             return $user;
         });
-
-        Route::apiResource('ciclos', CicloController::class);
-        Route::apiResource('reconocimientos', ReconocimientoController::class);
-        Route::apiResource('users', UserController::class);
-        Route::apiResource('proyectos', ProyectoController::class);
-        Route::apiResource('empresas', EmpresaController::class);
-        Route::apiResource('familias_profesionales', FamiliaProfesionalController::class)->parameters([
-            'familias_profesionales' => 'familiaProfesional'
-        ]);
-        Route::apiResource('curriculos', CurriculoController::class)
-            ->except(['index', 'show']);
-        Route::apiResource('actividades', ActividadController::class)->parameters([
-            'actividades' => 'actividad'
-        ]);
-        Route::apiResource('competencias', CompetenciaController::class);
-        Route::apiResource('idiomas', IdiomaController::class);
     });
 
+    Route::apiResource('ciclos', CicloController::class);
+    Route::apiResource('reconocimientos', ReconocimientoController::class);
+    Route::apiResource('users', UserController::class);
+    Route::apiResource('proyectos', ProyectoController::class);
+    Route::apiResource('empresas', EmpresaController::class);
+    Route::apiResource('familias_profesionales', FamiliaProfesionalController::class)->parameters([
+        'familias_profesionales' => 'familiaProfesional'
+    ]);
+    Route::apiResource('curriculos', CurriculoController::class);
+    Route::apiResource('actividades', ActividadController::class)->parameters([
+        'actividades' => 'actividad'
+    ]);
+    Route::apiResource('competencias', CompetenciaController::class);
+    Route::apiResource('idiomas', IdiomaController::class);
+
     Route::get('{tabla}/count', [CountController::class, 'count']);
```
- especificar el middleware en los controladores correspondientes. Para ello, el controlador debe implementar la interfaz `HasMiddleware` y definir un método estático `middleware` que devuelva un array con los middleware que se aplicarán a los métodos del controlador. Por ejemplo, para el controlador `CurriculoController`: 

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
 
class CurriculoController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show']),
        ];
    }
 
    // ...
}
```

## Autorizar mediante _policies_

La forma más sencilla de autorizar mediante _policies_ es utilizando el método `authorize()` de la clase `Gate`.

Se muestra la llamada a `authorize()`del método `create()` del controlador de `Curriculos`:

```
    public function store(Request $request)
    {
        Gate::authorize('create', Curriculo::class);

        $curriculo = json_decode($request->getContent(), true);
        if (!$request->user()->esAdmin()) {
            $curriculo['user_id'] = $request->user()->id;
        }
        $curriculo = Curriculo::create($curriculo);

        return new CurriculoResource($curriculo);
    }
```
