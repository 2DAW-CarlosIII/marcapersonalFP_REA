# 5.5. Controladores
<!-- {% raw %} -->

**Presentación de apoyo** (_RevealJS_): [RA5_5_controladores_slides.html](./materiales/slides/RA5_5_controladores_slides.html)

## Demasiado código en las rutas

Al terminar 5.4, `routes/web.php` ya no solo decía qué dirección lleva a qué página: también contenía datos, el array de proyectos, y preparaba lo que cada vista necesitaba. Con cuatro rutas todavía se lee; con cuarenta sería imposible de mantener.

La solución es la **C** de MVC: el **controlador**. Un controlador es una clase que agrupa las acciones de un mismo recurso. Para cada petición:

1. recibe los datos de la petición, como el `{id}` de la ruta;
2. pide al **modelo** los datos que necesita;
3. elige la **vista** y se los pasa.

El fichero de rutas queda como lo que debe ser: un índice de direcciones, en el que cada una apunta a un método de un controlador.

![Antes, la función de la ruta tenía los datos y elegía la vista. Los datos pasan a una propiedad de ProyectosController y la vista a su acción show. La ruta solo apunta al controlador. Después, GET /proyectos/show/7 llega a la ruta, la ruta llama a show con el 7, show toma el proyecto de $arrayProyectos y devuelve la vista proyectos.show](./images/RA5_5_rutaControlador.svg)

El diagrama muestra el mismo flujo antes y después: los datos y la elección de la vista salen de la función de la ruta y pasan al controlador, y la ruta se queda en una sola línea que apunta a su acción.

## Un controlador básico

Los controladores son clases de la carpeta `app/Http/Controllers/`, con el espacio de nombres `App\Http\Controllers` (5.2) y el sufijo `Controller` en el nombre. Se crean con _Artisan_:

```bash
php artisan make:controller ProyectosController
```

Que genera esta clase vacía, en `app/Http/Controllers/ProyectosController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProyectosController extends Controller
{
    //
}
```

Todos heredan de `Controller`, la clase base que trae el proyecto. Cada **método público** es una **acción**, que devuelve lo mismo que devolvían las funciones de las rutas: una vista, un texto, un array…

```php
use Illuminate\View\View;

class ProyectosController extends Controller
{
    public function index(): View
    {
        return view('proyectos.index', ['proyectos' => $this->arrayProyectos]);
    }
}
```

`View` es el tipo de lo que devuelve `view()`. Declararlo, como en 3.4, documenta el método y deja que PHP compruebe que devuelve lo que promete.

## Rutas que apuntan a controladores

En la ruta, en lugar de una función, se escribe un array con la **clase** del controlador y el **nombre del método**:

```php
use App\Http\Controllers\ProyectosController;

Route::get('proyectos', [ProyectosController::class, 'index'])->name('proyectos.index');
```

`ProyectosController::class` es el nombre completo de la clase, `App\Http\Controllers\ProyectosController`, y el `use` del principio permite escribirlo abreviado.

Cuando todas las rutas de un grupo van al mismo controlador, `Route::controller()` evita repetirlo. Se combina con el prefijo y el nombre de 5.3:

```php
Route::controller(ProyectosController::class)->prefix('proyectos')->name('proyectos.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('show/{id}', 'show')->whereNumber('id')->name('show');
    Route::get('create', 'create')->name('create');
    Route::get('edit/{id}', 'edit')->whereNumber('id')->name('edit');
});
```

### Los nombres de las acciones

Usamos los nombres que _Laravel_ propone para las acciones de un recurso. Cada uno tiene un significado fijo:

| Acción | Para qué | Apartado |
|---|---|---|
| `index` | El listado | 5.5 |
| `show` | El detalle de un elemento | 5.5 |
| `create` | El formulario de alta | 5.5 |
| `store` | Guardar el alta | 5.6 |
| `edit` | El formulario de edición | 5.5 |
| `update` | Guardar la edición | 5.6 |
| `destroy` | Borrar | Bloque 7 |

Coinciden con la segunda parte del nombre de las rutas (`proyectos.index`, `proyectos.show`…), y en el Bloque 7 _Laravel_ generará las siete de golpe.

## Los parámetros llegan al método

Un parámetro de la ruta llega al método como argumento, por su **nombre**: `{id}` llega a `$id`.

```php
public function show(int $id): View
{
    abort_unless(isset($this->arrayProyectos[$id]), 404);

    return view('proyectos.show', [
        'proyecto' => $this->arrayProyectos[$id],
        'id' => $id,
    ]);
}
```

- **`int $id`**: el `7` llega de la dirección como texto (`'7'`), y PHP lo convierte en entero al llamar al método. `whereNumber('id')` garantiza que la conversión es posible.
- **`abort_unless(condición, 404)`** corta la petición con un error 404 si la condición no se cumple. Sin esa línea, `/proyectos/show/99` daría un error 500 al buscar una posición que no existe en el array. Es lo que en _v0_ hacíamos con `http_response_code(404)` y `exit`, o con `exigirRol()` y el 403 de 4.4.

Para cortar sin condición, `abort(404)`; para el caso contrario, `abort_if(condición, 404)`.

## Datos en el controlador, de momento

Hasta el Bloque 6 no hay base de datos, así que los proyectos viven en una **propiedad** del controlador, como en las clases de 3.5:

```php
class ProyectosController extends Controller
{
    private array $arrayProyectos = [
        [
            'docente_id' => 1,
            'nombre' => 'Tecnologías de la Información',
            // ...
        ],
        // ...
    ];
```

No es su sitio definitivo: guardar los datos es cosa del **modelo**. En 5.8 los sacaremos a una clase propia y, en el Bloque 6, a la base de datos. El controlador apenas cambiará, porque solo **coordina**.

## Controladores de una sola acción

Si un controlador solo tiene una acción, se puede escribir en un único método especial, **`__invoke()`**. Es un **controlador invocable**:

```bash
php artisan make:controller HomeController --invokable
```

```php
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', ['centro' => config('marcapersonalfp.centro')]);
    }
}
```

En la ruta basta con la clase, sin nombre de método:

```php
Route::get('/', HomeController::class);
```

## Un fichero de rutas por recurso

Cuando varias personas trabajan a la vez en el mismo `routes/web.php`, los _pull requests_ chocan. Para evitarlo, las rutas de cada recurso pueden ir en su propio fichero, por ejemplo `routes/proyectos.php`, que `routes/web.php` incluye:

```php
require __DIR__ . '/proyectos.php';
```

`routes/proyectos.php` empieza como `routes/web.php`, con `<?php` y los `use` que necesite, y contiene solo el grupo de proyectos. Así, quien trabaje en otro recurso, como los reconocimientos de los ejercicios opcionales, crea su propio fichero y solo añade una línea a `routes/web.php`.

En `php artisan route:list`, la columna de la acción muestra ahora `ProyectosController@index`, `ProyectosController@show`…

## Preparación del entorno de trabajo

Seguimos en el proyecto `marcapersonalfp`, con las vistas de [5.4](./RA5_4_vistas.md) y la compilación de estilos vigilando (`npm run build -- --watch`). Crea la rama del apartado en el terminal de la máquina virtual:

```bash
git switch main
git pull upstream main
git switch -c RA5_5
```

## Ejercicios

1. **La página principal.** Crea el controlador invocable `HomeController` de este apartado y haz que la ruta `/` lo use.

2. **El controlador de proyectos.** Crea `ProyectosController` con las acciones `index`, `show`, `create` y `edit`:
    - Copia como propiedad de la clase el array de [`array_proyectos.php`](./materiales/ejercicios-laravel/array_proyectos.php), con sus diez proyectos. Ya no hace falta el array de tres proyectos de la ruta de 5.4.
    - `index` pasa a la vista todos los proyectos; `show` y `edit`, el proyecto y su `id`; `create`, nada.
    - `show` y `edit` responden 404 si el `id` no existe en el array.
    - Las vistas son las de 5.4. Si cambias algo de ellas, mantén su título.

3. **Las rutas, a su fichero.** Mueve el grupo de rutas de proyectos a `routes/proyectos.php`, con `Route::controller()`, e inclúyelo desde `routes/web.php`.

4. **La vista de detalle.** Completa `proyectos/show.blade.php` para que muestre el proyecto. Puedes partir de este código:

    ```blade
    <x-layout :titulo="'Vista detalle proyecto ' . $id">
        <div class="grid gap-6 rounded-lg bg-white p-6 shadow sm:grid-cols-3">
            <img src="{{ asset('images/mp-logo.png') }}" alt="" class="h-40 object-contain">

            <div class="space-y-3 sm:col-span-2">
                <h2 class="text-2xl font-semibold">{{ $proyecto['nombre'] }}</h2>
                <p>
                    <strong>Repositorio:</strong>
                    <a href="https://github.com/2DAW-CarlosIII/{{ $proyecto['dominio'] }}" class="text-blue-700 hover:underline">
                        github.com/2DAW-CarlosIII/{{ $proyecto['dominio'] }}
                    </a>
                </p>
                <p><strong>Docente:</strong> {{ $proyecto['docente_id'] }}</p>
                <ul class="list-inside list-disc">
                    @foreach ($proyecto['metadatos'] as $clave => $valor)
                        <li>{{ $clave }}: {{ $valor }}</li>
                    @endforeach
                </ul>

                {{-- TODO: el estado, "Proyecto aprobado" o "Proyecto suspenso" según la calificación --}}

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('proyectos.edit', $id) }}" class="rounded bg-yellow-500 px-4 py-2 font-semibold text-white hover:bg-yellow-600">
                        Editar proyecto
                    </a>
                    <a href="{{ route('proyectos.index') }}" class="rounded border px-4 py-2 hover:bg-gray-50">
                        Volver al listado
                    </a>
                </div>
            </div>
        </div>
    </x-layout>
    ```

    En la vista de edición, muestra de momento el nombre del proyecto debajo del título. El formulario llegará en 5.6.

5. **El perfil.** Crea el controlador invocable `PerfilController` para la ruta `perfil`, que sigue recibiendo un `{id}` opcional. Pista: `public function __invoke(?int $id = null): View`.

6. **Investiga.** Responde en tu _pull request_:
    - Ejecuta `php artisan route:list`. ¿Qué muestra ahora la columna de la acción? ¿Cómo aparece la ruta de `HomeController`?
    - Quita temporalmente el `abort_unless()` de `show` y pide `/proyectos/show/99`. ¿Qué ves con `APP_DEBUG=true`? ¿Qué vería un usuario en producción? Vuelve a ponerlo.
    - En 3.4 vimos `declare(strict_types=1)`. Si lo añades al principio de `ProyectosController.php`, ¿siguen funcionando `show(int $id)` y `/proyectos/show/7`? Busca en la documentación de PHP a qué llamadas afecta `strict_types`: ¿a las que se hacen **desde** el fichero que lo declara, o a las que se hacen **hacia** sus funciones?

### Ejercicios colaborativos opcionales

Para practicar en equipo, cada miembro puede crear el controlador, las rutas y las vistas de otro recurso de _marcapersonalFP_, con su propio fichero de rutas:

- [Reconocimientos](./0363_reconocimientoController.md)
- [Actividades](./0364_actividadController.md)

> **Puente.** En _v0_, cada script de `public/` hacía de todo: leía la petición, preparaba los datos, decidía qué responder y escribía el HTML. En _Laravel_, el controlador **coordina**: recibe la petición, pide los datos y elige la vista. Y `abort()` sustituye a la pareja `http_response_code()` y `exit`. Esto que hicimos a mano, el framework lo automatiza así.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 5, copia el archivo [RA5_5_ControladoresTest.php](./materiales/ejercicios-laravel/tests/Feature/RA5_5_ControladoresTest.php) a la carpeta `tests/Feature/` de tu proyecto y ejecuta, en el terminal de `workspace`, ese test en concreto:

```bash
php artisan test --filter=RA5_5_ControladoresTest
```

El test comprueba:
- que cada ruta apunta a la acción de su controlador, y que las de proyectos están en `routes/proyectos.php`;
- el listado completo, el detalle con su estado y la edición;
- el 404 cuando el proyecto no existe;
- el perfil, ahora con su controlador.

Si todo está bien: `Tests: 8 passed (30 assertions)`.

---

**Siguiente:** [5.6. Formularios, datos de entrada, validación y redirecciones](./RA5_6_formularios.md)
<!-- {% endraw %} -->
