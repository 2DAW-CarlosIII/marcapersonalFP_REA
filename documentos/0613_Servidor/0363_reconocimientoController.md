# 5.5 (opcional). Controlador de reconocimientos
<!-- {% raw %} -->

Ejercicio colaborativo opcional de [5.5. Controladores](./RA5_5_controladores.md). Cada miembro del equipo puede hacer uno de estos ejercicios en su rama: como cada recurso tiene su propio controlador, sus vistas y su fichero de rutas, los _pull requests_ apenas chocan.

Vamos a crear el controlador, las rutas y las vistas para la tabla `reconocimientos` del [esquema relacional de la aplicación](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/marcapersonalFP.drawio.png), siguiendo los pasos que dimos con los proyectos.

## 1. El controlador y sus rutas

Crea el controlador `ReconocimientoController` con _Artisan_ y añádele estas acciones:

| Ruta | Nombre | Acción | Vista |
|---|---|---|---|
| `reconocimientos` | `reconocimientos.index` | `index` | `reconocimientos.index` |
| `reconocimientos/show/{id}` | `reconocimientos.show` | `show` | `reconocimientos.show` |
| `reconocimientos/create` | `reconocimientos.create` | `create` | `reconocimientos.create` |
| `reconocimientos/edit/{id}` | `reconocimientos.edit` | `edit` | `reconocimientos.edit` |

- Las rutas van en su propio fichero, `routes/reconocimientos.php`, en un grupo con `Route::controller()`, prefijo y prefijo de nombre, como `routes/proyectos.php`. Inclúyelo desde `routes/web.php`.
- Todos los `{id}` son números (`whereNumber`).
- `show` y `edit` reciben el `$id`, pasan a la vista el elemento y su `id`, y responden 404 si no existe en el array (`abort_unless`).

## 2. Los datos

Hasta el Bloque 6, los datos viven en una propiedad del controlador. Copia este array como `private array $arrayReconocimientos`:

```php
private array $arrayReconocimientos = [
    [
        'estudiante_id' => 1,
        'actividad_id' => 2,
        'documento' => 'https://drive.google.com/document/d/KPkTFrB1nub',
        'fecha' => '05/12/2022',
        'docente_validador' => 2
    ],
    [
        'estudiante_id' => 2,
        'actividad_id' => 3,
        'documento' => 'https://drive.google.com/document/d/Cov6riILdN0',
        'fecha' => '10/10/2022',
        'docente_validador' => 3
    ],
    [
        'estudiante_id' => 3,
        'actividad_id' => 4,
        'documento' => 'https://drive.google.com/document/d/H5LzJpVKnR9',
        'fecha' => '15/11/2022',
        'docente_validador' => 4
    ],
    [
        'estudiante_id' => 4,
        'actividad_id' => 5,
        'documento' => 'https://drive.google.com/document/d/Z1lNhHOFru6',
        'fecha' => '20/01/2023',
        'docente_validador' => 5
    ],
    [
        'estudiante_id' => 5,
        'actividad_id' => 6,
        'documento' => 'https://drive.google.com/document/d/sV7S5dTPgpN',
        'fecha' => '25/02/2023',
        'docente_validador' => 6
    ],
    [
        'estudiante_id' => 6,
        'actividad_id' => 7,
        'documento' => 'https://drive.google.com/document/d/od4HPCv58Um',
        'fecha' => '03/04/2023',
        'docente_validador' => 7
    ],
    [
        'estudiante_id' => 7,
        'actividad_id' => 8,
        'documento' => 'https://drive.google.com/document/d/IKGrcD4NOAU',
        'fecha' => '08/05/2023',
        'docente_validador' => 8
    ],
    [
        'estudiante_id' => 8,
        'actividad_id' => 9,
        'documento' => 'https://drive.google.com/document/d/SLf3GPgs1CN',
        'fecha' => '13/06/2023',
        'docente_validador' => 9
    ],
    [
        'estudiante_id' => 9,
        'actividad_id' => 10,
        'documento' => 'https://drive.google.com/document/d/EUV2MlPzwZ0',
        'fecha' => '18/07/2023',
        'docente_validador' => 10
    ],
    [
        'estudiante_id' => 10,
        'actividad_id' => 1,
        'documento' => 'https://drive.google.com/document/d/bDO6iEqkWuK',
        'fecha' => '23/08/2023',
        'docente_validador' => 1
    ],
];
```

## 3. Las vistas

Todas usan el _layout_ de 5.4, `<x-layout>`.

- **`reconocimientos.index`** muestra un elemento por tarjeta, en una rejilla (`<ul class="grid gap-4 sm:grid-cols-2">`), con `@forelse` y un mensaje si no hay ninguno. Puedes partir de esta tarjeta:

    ```blade
    <li class="rounded-lg bg-white p-4 shadow">
        <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Award_icon.png" alt="" class="h-16"
             title="Sakatsp, CC BY-SA 4.0, via Wikimedia Commons">
        <h2 class="mt-2 text-lg font-semibold">Estudiante {{ $reconocimiento['estudiante_id'] }}</h2>
        <p class="text-sm text-gray-500">{{ $reconocimiento['fecha'] }}</p>
        <a href="{{ route('reconocimientos.show', $id) }}" class="mt-3 inline-block text-blue-700 hover:underline">
            Más información
        </a>
    </li>
    ```

    Si te resulta cómodo, conviértela en un componente, como `tarjeta-proyecto`.

- **`reconocimientos.show`**, adaptando `proyectos.show`: todos los datos del elemento, un botón para editarlo y otro para volver al listado.
- **`reconocimientos.create`** y **`reconocimientos.edit`**, de momento, solo con su título: "Añadir reconocimiento" y "Modificar reconocimiento {id}". Sus formularios, con estos campos, llegarán con los de proyectos en [5.6](./RA5_6_formularios.md):

    | Etiqueta | `name` | Tipo de campo |
    |---|---|---|
    | Estudiante | `estudiante_id` | numérico |
    | Actividad | `actividad_id` | numérico |
    | URL del documento | `documento` | url |
    | Fecha | `fecha` | date |
    | Docente validador | `docente_validador` | numérico |

Añade también un enlace a `reconocimientos.index` en la barra de navegación (`components/navbar.blade.php`), destacado con `request()->routeIs('reconocimientos.*')`.

## Comprobar el ejercicio

Crea un test con _Artisan_:

```bash
php artisan make:test ReconocimientoControllerTest
```

Copia en él, desde [RA5_5_ControladoresTest.php](./materiales/ejercicios-laravel/tests/Feature/RA5_5_ControladoresTest.php), el método `setUp()`, el método auxiliar `accion()` y los tests de las rutas, el listado, el detalle y el 404. Adáptalos para que pidan los datos de `reconocimientos` y comprueben los valores de tu array. Después, ejecútalo:

```bash
php artisan test --filter=ReconocimientoControllerTest
```
<!-- {% endraw %} -->
