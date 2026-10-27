# 5.5 (opcional). Controlador de actividades
<!-- {% raw %} -->

Ejercicio colaborativo opcional de [5.5. Controladores](./RA5_5_controladores.md). Cada miembro del equipo puede hacer uno de estos ejercicios en su rama: como cada recurso tiene su propio controlador, sus vistas y su fichero de rutas, los _pull requests_ apenas chocan.

Vamos a crear el controlador, las rutas y las vistas para la tabla `actividades` del [esquema relacional de la aplicación](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/marcapersonalFP.drawio.png), siguiendo los pasos que dimos con los proyectos.

## 1. El controlador y sus rutas

Crea el controlador `ActividadController` con _Artisan_ y añádele estas acciones:

| Ruta | Nombre | Acción | Vista |
|---|---|---|---|
| `actividades` | `actividades.index` | `index` | `actividades.index` |
| `actividades/show/{id}` | `actividades.show` | `show` | `actividades.show` |
| `actividades/create` | `actividades.create` | `create` | `actividades.create` |
| `actividades/edit/{id}` | `actividades.edit` | `edit` | `actividades.edit` |

- Las rutas van en su propio fichero, `routes/actividades.php`, en un grupo con `Route::controller()`, prefijo y prefijo de nombre, como `routes/proyectos.php`. Inclúyelo desde `routes/web.php`.
- Todos los `{id}` son números (`whereNumber`).
- `show` y `edit` reciben el `$id`, pasan a la vista el elemento y su `id`, y responden 404 si no existe en el array (`abort_unless`).

## 2. Los datos

Hasta el Bloque 6, los datos viven en una propiedad del controlador. Copia este array como `private array $arrayActividades`:

```php
private array $arrayActividades = [
    [
        'docente_id' => 1,
        'insignia' => 'https://marcapersonalFP.es/badge?v=u54uern',
    ],
    [
        'docente_id' => 2,
        'insignia' => 'https://marcapersonalFP.es/badge?v=v87dfg2',
    ],
    [
        'docente_id' => 3,
        'insignia' => 'https://marcapersonalFP.es/badge?v=frt32qe',
    ],
    [
        'docente_id' => 4,
        'insignia' => 'https://marcapersonalFP.es/badge?v=wtrh2we',
    ],
    [
        'docente_id' => 5,
        'insignia' => 'https://marcapersonalFP.es/badge?v=qwer123',
    ],
    [
        'docente_id' => 6,
        'insignia' => 'https://marcapersonalFP.es/badge?v=ytgfd32',
    ],
    [
        'docente_id' => 7,
        'insignia' => 'https://marcapersonalFP.es/badge?v=zxvbn23',
    ],
    [
        'docente_id' => 8,
        'insignia' => 'https://marcapersonalFP.es/badge?v=asdf456',
    ],
    [
        'docente_id' => 9,
        'insignia' => 'https://marcapersonalFP.es/badge?v=qwerty78',
    ],
    [
        'docente_id' => 10,
        'insignia' => 'https://marcapersonalFP.es/badge?v=mnbvc90',
    ],
];
```

## 3. Las vistas

Todas usan el _layout_ de 5.4, `<x-layout>`.

- **`actividades.index`** muestra un elemento por tarjeta, en una rejilla (`<ul class="grid gap-4 sm:grid-cols-2">`), con `@forelse` y un mensaje si no hay ninguno. Puedes partir de esta tarjeta:

    ```blade
    <li class="rounded-lg bg-white p-4 shadow">
        <h2 class="text-lg font-semibold">Actividad {{ $id }}</h2>
        <p class="text-sm text-gray-500">Docente {{ $actividad['docente_id'] }}</p>
        <a href="{{ $actividad['insignia'] }}" class="text-sm text-blue-700 hover:underline">Insignia</a>
        <a href="{{ route('actividades.show', $id) }}" class="mt-3 block text-blue-700 hover:underline">
            Más información
        </a>
    </li>
    ```

    Si te resulta cómodo, conviértela en un componente, como `tarjeta-proyecto`.

- **`actividades.show`**, adaptando `proyectos.show`: todos los datos del elemento, un botón para editarlo y otro para volver al listado.
- **`actividades.create`** y **`actividades.edit`**, de momento, solo con su título: "Añadir actividad" y "Modificar actividad {id}". Sus formularios, con estos campos, llegarán con los de proyectos en [5.6](./RA5_6_formularios.md):

    | Etiqueta | `name` | Tipo de campo |
    |---|---|---|
    | Docente | `docente_id` | numérico |
    | Insignia | `insignia` | url |

Añade también un enlace a `actividades.index` en la barra de navegación (`components/navbar.blade.php`), destacado con `request()->routeIs('actividades.*')`.

## Comprobar el ejercicio

Crea un test con _Artisan_:

```bash
php artisan make:test ActividadControllerTest
```

Copia en él, desde [RA5_5_ControladoresTest.php](./materiales/ejercicios-laravel/tests/Feature/RA5_5_ControladoresTest.php), el método `setUp()`, el método auxiliar `accion()` y los tests de las rutas, el listado, el detalle y el 404. Adáptalos para que pidan los datos de `actividades` y comprueben los valores de tu array. Después, ejecútalo:

```bash
php artisan test --filter=ActividadControllerTest
```
<!-- {% endraw %} -->
