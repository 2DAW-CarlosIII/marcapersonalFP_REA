# 5.6. Formularios, datos de entrada, validación y redirecciones
<!-- {% raw %} -->

## Lo que ya sabemos hacer a mano

En _v0_ resolvimos un formulario completo, el alta de un currículo, en tres pasos:

1. **Leer los datos** de `$_POST`, con `?? ''` por si faltaba algún campo (3.6).
2. **Validarlos** con la clase `ValidadorCurriculo` y, si había errores, volver a mostrar el formulario con lo que el usuario había escrito: `value="<?= e($datos['alumno'] ?? '') ?>"` (3.8).
3. Si todo era correcto, **redirigir** a otra página con un mensaje guardado en la sesión, el patrón POST/Redirect/GET (4.1).

Al cerrar 3.8 y 4.4 anotamos cómo lo automatiza _Laravel_. Es lo que hacemos en este apartado:

| En _v0_ (a mano) | En _Laravel_ |
|---|---|
| `$_POST['nombre'] ?? ''` | `$request->input('nombre', '')` |
| `ValidadorCurriculo::validar()` | `$request->validate([...])` o un _Form Request_ |
| `value="<?= e($datos['alumno'] ?? '') ?>"` | `value="{{ old('alumno') }}"` |
| `$_SESSION['mensaje'] = '…'` + `header('Location: …')` | `redirect()->route(…)->with('mensaje', '…')` |
| Mostrar el mensaje y hacer `unset()` | `@session('mensaje')`: se borra solo |

## El objeto `Request`

En _Laravel_ no se leen directamente `$_GET` ni `$_POST`. Toda la petición está en un objeto de la clase **`Illuminate\Http\Request`** (5.2). Para usarlo en una acción, basta con declararlo como parámetro:

```php
use Illuminate\Http\Request;

public function store(Request $request)
{
    $nombre = $request->input('nombre');
    // ...
}
```

Nadie llama a `store()` pasándole ese objeto: lo hace _Laravel_, que ve el tipo `Request` en la firma del método y le entrega la petición actual. Es la **inyección de dependencias**, que veremos con más calma en 5.8.

Si la acción tiene también parámetros de la ruta, van **después** de las dependencias: `update(Request $request, int $id)`.

### Leer los datos

Los mismos métodos sirven para los datos de un formulario (`POST`), los de la dirección (`?ciclo=DAW`) o los de un JSON:

| Método | Devuelve |
|---|---|
| `$request->input('nombre')` | El valor del campo, o `null` si no llegó |
| `$request->input('nombre', 'Sin nombre')` | Igual, con un valor por defecto |
| `$request->has('nombre')` | Si el campo llegó, aunque esté vacío |
| `$request->filled('nombre')` | Si llegó **y** no está vacío |
| `$request->only(['nombre', 'dominio'])` | Un array solo con esos campos |
| `$request->all()` | Un array con todos los campos |
| `$request->method()` | `GET`, `POST`… (en _v0_, `$_SERVER['REQUEST_METHOD']`) |

## Formularios en _Blade_

Un formulario de _Laravel_ es un formulario HTML normal, con tres particularidades.

**1. La dirección de envío, con `route()`.** Para el alta, la ruta `proyectos.store`:

```php
// routes/proyectos.php, dentro del grupo de 5.5
Route::post('/', 'store')->name('store');
Route::put('edit/{id}', 'update')->whereNumber('id')->name('update');
```

`POST /proyectos` guarda un proyecto nuevo y `PUT /proyectos/edit/{id}` guarda los cambios de uno existente. Son las acciones `store` y `update` de la tabla de 5.5.

**2. El _token_ CSRF.** Todo formulario que no sea `GET` lleva la directiva **`@csrf`**, que añade un campo oculto con un _token_ guardado en la sesión:

```blade
<form action="{{ route('proyectos.store') }}" method="POST">
    @csrf
    ...
</form>
```

Un _middleware_ comprueba en cada `POST`, `PUT` o `DELETE` que el _token_ recibido coincide con el de la sesión. Una página ajena no puede conocerlo, así que no puede enviar formularios a nuestra aplicación en nombre del usuario: es la protección contra **CSRF** que mencionamos en 3.6 y que en 4.1 solo pudimos frenar con `SameSite`. Sin `@csrf`, el formulario devuelve un error **419** (_Page Expired_).

**3. Los métodos `PUT`, `PATCH` y `DELETE`.** HTML solo envía `GET` y `POST` (5.3). Para los demás, el formulario se envía por `POST` con la directiva **`@method`**, que añade un campo oculto `_method`:

```blade
<form action="{{ route('proyectos.update', $id) }}" method="POST">
    @csrf
    @method('PUT')
    ...
</form>
```

_Laravel_ lee ese campo y trata la petición como `PUT`, así que llega a la ruta `Route::put()`.

## Validación

Los datos de un formulario nunca son de fiar (3.6). En _Laravel_, se validan con **`$request->validate()`**, que recibe un array con las **reglas** de cada campo:

```php
public function store(Request $request)
{
    $datos = $request->validate([
        'docente_id' => ['required', 'integer', 'min:1'],
        'nombre' => ['required', 'string', 'max:100'],
        'dominio' => ['required', 'alpha_dash', 'max:60'],
        'fecha_inicio' => ['required', 'date'],
        'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        'calificacion' => ['nullable', 'numeric', 'between:0,10'],
    ]);

    // Aquí, $datos solo contiene los campos validados.
}
```

| Regla | Exige |
|---|---|
| `required` | Que el campo llegue y no esté vacío |
| `nullable` | Nada: el campo puede estar vacío, pero si tiene valor, se comprueba el resto |
| `string`, `integer`, `numeric`, `date` | El tipo de dato |
| `min:1`, `max:100`, `between:0,10` | Un valor mínimo o máximo; en los textos, su longitud |
| `alpha_dash` | Solo letras, números, guiones y guiones bajos |
| `after_or_equal:fecha_inicio` | Una fecha igual o posterior a la de otro campo |

La [documentación de _Laravel_](https://laravel.com/docs/validation#available-validation-rules) recoge todas las reglas disponibles.

¿Y si algún dato no es válido? `validate()` **no deja continuar**:

1. Guarda en la sesión los **errores** de cada campo.
2. Guarda también lo que el usuario había escrito (el _old input_).
3. **Redirige** de vuelta al formulario.

Todo eso es lo que en 3.8 hacíamos a mano. Si los datos son válidos, `validate()` devuelve un array **solo con los campos validados**: cualquier otro campo que alguien añada al formulario con las herramientas de desarrollo (como el truco de 4.4) se queda fuera.

<!-- DIAGRAMA: el viaje de un formulario: GET create → formulario; POST store → validate(); si falla, redirect back con errores y old input → formulario relleno con mensajes; si pasa, redirect a index con mensaje flash → @session -->

### Mostrar los errores y recuperar lo escrito

Tras la redirección, la vista tiene dos ayudas:

- **`old('nombre')`** devuelve lo que el usuario había escrito en ese campo. En un formulario de edición, `old('nombre', $proyecto['nombre'])` usa el valor del proyecto si no hay nada anterior.
- **`$errors`**, una variable que _Laravel_ pasa a **todas** las vistas, con los errores de cada campo. La directiva **`@error('nombre')`** muestra su bloque solo si ese campo tiene errores, con el primero en la variable `$message`.

```blade
<div>
    <label for="nombre" class="block font-semibold">Nombre</label>
    <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}"
           @class(['mt-1 w-full rounded border p-2', 'border-red-500' => $errors->has('nombre')])>
    @error('nombre')
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
```

Para mostrar un resumen de todos los errores, `$errors->any()` y `$errors->all()`.

### Los mensajes, en español

Aunque en 5.2 pusimos `APP_LOCALE=es`, el proyecto solo trae los mensajes de validación en inglés ("_The nombre field is required._"). Hay dos soluciones:

- Instalar las traducciones de la comunidad. Es lo que haremos en el Bloque 8, al tratar la internacionalización.
- Escribir nuestros propios mensajes. Es lo que haremos ahora, en un _Form Request_.

## _Form Requests_: la validación, en su propia clase

Cuando las reglas crecen, o se repiten en `store` y en `update`, conviene sacarlas del controlador a una clase propia: un **_Form Request_**. Es exactamente lo que hicimos en 3.8 con `ValidadorCurriculo`.

```bash
php artisan make:request ProyectoRequest
```

_Artisan_ crea `app/Http/Requests/ProyectoRequest.php`. Lo completamos así:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProyectoRequest extends FormRequest
{
    /** ¿Puede el usuario hacer esta petición? La autorización llegará en el Bloque 7. */
    public function authorize(): bool
    {
        return true;
    }

    /** Las reglas de validación de cada campo. */
    public function rules(): array
    {
        return [
            'docente_id' => ['required', 'integer', 'min:1'],
            'nombre' => ['required', 'string', 'max:100'],
            // ... el resto de reglas
        ];
    }

    /** Mensajes propios, por campo y regla. */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proyecto es obligatorio.',
            // ...
        ];
    }

    /** Cómo se llama cada campo en los mensajes que no personalizamos. */
    public function attributes(): array
    {
        return [
            'docente_id' => 'docente',
            'fecha_fin' => 'fecha de fin',
        ];
    }
}
```

Para usarlo, la acción declara el _Form Request_ en lugar de `Request`:

```php
use App\Http\Requests\ProyectoRequest;

public function store(ProyectoRequest $request)
{
    $datos = $request->validated();
    // ...
}
```

La validación ocurre **antes** de que empiece a ejecutarse `store()`: si los datos no son válidos, la acción ni siquiera llega a ejecutarse. Y `$request->validated()` devuelve solo los campos validados.

## Redirecciones

Después de procesar un formulario con éxito, la respuesta no es una vista, sino una **redirección** a otra página: el patrón POST/Redirect/GET de 3.6 y 4.1. Así, si el usuario recarga la página, no vuelve a enviar el formulario.

| Función | Redirige a |
|---|---|
| `redirect()->route('proyectos.show', $id)` | Una ruta con nombre (lo habitual) |
| `to_route('proyectos.index')` | Lo mismo, más breve |
| `redirect('/proyectos')` | Una dirección escrita a mano |
| `back()` | La página anterior |

La respuesta es un código **302** con la cabecera `Location`: lo mismo que hacía `header('Location: …')` en 4.1.

### Mensajes _flash_

Una redirección puede llevar un **mensaje _flash_**: un dato que se guarda en la sesión y que dura **una sola petición**, la siguiente:

```php
return redirect()->route('proyectos.index')
    ->with('mensaje', "Proyecto «{$datos['nombre']}» creado.");
```

En la vista, la directiva **`@session`** lo muestra si existe, en la variable `$value`. Como todas las páginas pueden recibir mensajes, su sitio natural es el _layout_, justo encima de `{{ $slot }}`:

```blade
@session('mensaje')
    <div class="mb-6 rounded border border-green-300 bg-green-50 p-4 text-green-800">
        {{ $value }}
    </div>
@endsession
```

En 4.1 había que leer el mensaje de `$_SESSION` y hacer `unset()` para que no volviera a salir. Aquí, _Laravel_ lo borra solo después de esa petición.

### ¿Dónde se guarda el proyecto?

En ningún sitio, todavía. Los proyectos están en un array del controlador (5.5), y cada petición empieza de cero (2.4): lo que añadamos se perdería al terminar la petición. En este apartado, `store` y `update` **validan y redirigen con un mensaje**. En el Bloque 6, guardarán los datos en la base de datos.

## Ficheros

Un formulario también puede enviar ficheros: necesita `enctype="multipart/form-data"`, y el fichero se lee con `$request->file('foto')`. Subirlos, validarlos y guardarlos es parte del Bloque 7, en [Manejo de ficheros](./11_manejarFicheros.md).

## Preparación del entorno de trabajo

Seguimos en el proyecto `marcapersonalfp`, con el controlador de [5.5](./RA5_5_controladores.md) y la compilación de estilos vigilando. Crea la rama del apartado en el terminal de la máquina virtual:

```bash
git switch main
git pull upstream main
git switch -c RA5_6
```

## Ejercicios

1. **Las rutas.** Añade a `routes/proyectos.php` las rutas `proyectos.store` (`POST /proyectos`) y `proyectos.update` (`PUT /proyectos/edit/{id}`), con sus acciones `store` y `update` en `ProyectosController`.

2. **El mensaje _flash_.** Añade al _layout_ el bloque `@session('mensaje')` de este apartado.

3. **El _Form Request_.** Crea `ProyectoRequest` con estas reglas:

    | Campo | Reglas |
    |---|---|
    | `docente_id` | `required`, `integer`, `min:1` |
    | `nombre` | `required`, `string`, `max:100` |
    | `dominio` | `required`, `alpha_dash`, `max:60` |
    | `fecha_inicio` | `required`, `date` |
    | `fecha_fin` | `nullable`, `date`, `after_or_equal:fecha_inicio` |
    | `calificacion` | `nullable`, `numeric`, `between:0,10` |

    Escribe mensajes en español al menos para `required` en todos los campos que lo llevan. El de `nombre` debe ser exactamente `El nombre del proyecto es obligatorio.`

4. **El formulario de alta.** En `proyectos/create.blade.php`, crea el formulario con un campo por cada regla del ejercicio 3, con `old()` y `@error` en cada uno:

    | Etiqueta | `name` | Tipo |
    |---|---|---|
    | Docente | `docente_id` | `number` |
    | Nombre | `nombre` | `text` |
    | Dominio | `dominio` | `text`, precedido del texto `github.com/2DAW-CarlosIII/` |
    | Fecha de inicio | `fecha_inicio` | `date` |
    | Fecha de fin | `fecha_fin` | `date` |
    | Calificación | `calificacion` | `number`, con `step="0.1"` |

    El botón de envío dice "Añadir proyecto". La acción `store` recibe el `ProyectoRequest` y redirige al listado con el mensaje `Proyecto «nombre» creado.`, con el nombre del proyecto entre las comillas.

5. **El formulario de edición.** En `proyectos/edit.blade.php`, el mismo formulario, ahora con `@method('PUT')`, enviado a `proyectos.update`, con los valores del proyecto (`old('nombre', $proyecto['nombre'])`…) y el botón "Modificar proyecto". La acción `update` responde 404 si el proyecto no existe y, si existe, redirige a su detalle con el mensaje `Proyecto «nombre» actualizado.`

    Los dos formularios son casi iguales. Si quieres evitar repetirlo, sácalo a un componente, `components/formulario-proyecto.blade.php`, que reciba el proyecto (o `null` en el alta), la dirección de envío y el texto del botón.

6. **Investiga.** Responde en tu _pull request_:
    - Quita temporalmente `@csrf` del formulario de alta y envíalo. ¿Qué código de estado ves en la pestaña **Red**? ¿Por qué?
    - Envía el alta con el nombre vacío y una fecha de fin anterior a la de inicio. ¿Qué campos conservan lo que escribiste? ¿Qué mensajes ves? Busca en **Aplicación** → **Cookies** la cookie de sesión: ¿dónde están guardados los errores mientras se redirige?
    - Con las herramientas de desarrollo, añade al formulario un campo `<input name="admin" value="1">` y envíalo. En `store`, compara temporalmente `dd($request->all())` con `dd($request->validated())`. ¿Qué diferencia hay? ¿Qué relación tiene con el nombre del alumno de 4.4?

### Ejercicios colaborativos opcionales

Si hiciste alguno de los opcionales de 5.5 ([Reconocimientos](./0363_reconocimientoController.md) o [Actividades](./0364_actividadController.md)), completa sus formularios de alta y edición, con los campos que allí se indican, su propio _Form Request_ y sus acciones `store` y `update`.

> **Puente.** En _v0_, el formulario de alta necesitaba leer `$_POST` campo a campo, una clase validadora, rellenar de nuevo cada `value` y guardar y borrar a mano el mensaje de la sesión. En _Laravel_, el _Form Request_ valida antes de entrar en la acción, `old()` y `$errors` rellenan el formulario de vuelta, y `with()` deja un mensaje que se borra solo. Esto que hicimos a mano, el framework lo automatiza así.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 5, copia el archivo [RA5_6_FormulariosTest.php](./materiales/ejercicios-laravel/tests/Feature/RA5_6_FormulariosTest.php) a la carpeta `tests/Feature/` de tu proyecto y ejecuta, en el terminal de `workspace`, ese test en concreto:

```bash
php artisan test --filter=RA5_6_FormulariosTest
```

El test comprueba:
- las rutas `store` y `update`, y su método HTTP;
- los campos de los dos formularios, el _token_ CSRF y los valores del proyecto al editar;
- que un alta válida redirige al listado con su mensaje, y que este aparece en la página;
- que un alta no válida vuelve al formulario con un error por cada campo incorrecto, lo escrito y el mensaje en español;
- que una edición válida redirige al detalle con su mensaje, y el 404 si el proyecto no existe.

Si todo está bien: `Tests: 7 passed (33 assertions)`.

---

**Siguiente:** [5.7. _Middleware_ y control de usuarios con _Breeze_](./RA5_7_breeze.md)
<!-- {% endraw %} -->
