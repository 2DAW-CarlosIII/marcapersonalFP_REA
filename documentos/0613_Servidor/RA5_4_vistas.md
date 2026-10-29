# 5.4. Vistas, _Blade_ y _layouts_
<!-- {% raw %} -->

**Presentación de apoyo** (_RevealJS_): [RA5_4_vistas_slides.html](./materiales/slides/RA5_4_vistas_slides.html)

## De HTML con PHP a vistas

En _v0_, cada página mezclaba la lógica y el HTML en el mismo fichero: el `foreach` que recorría los currículos convivía con las etiquetas `<li>`, y cada dato se escribía con `<?= e(...) ?>`. Además, cada página repetía la estructura común (`<!DOCTYPE html>`, `<head>`, la cabecera, el pie), o la sacábamos a funciones como `pie()` en 2.4.

En _Laravel_, el HTML vive en las **vistas**: ficheros de `resources/views/` que solo se ocupan de la **presentación**. Reciben los datos ya preparados y los muestran. Es la **V** de MVC (5.2).

## Devolver una vista

Una ruta devuelve una vista con la función **`view()`**. El primer argumento es el nombre de la vista; el segundo, un array con los datos que recibe:

```php
Route::get('/', function () {
    return view('home', ['centro' => config('marcapersonalfp.centro')]);
});
```

- El nombre `home` corresponde al fichero `resources/views/home.blade.php`. Ni la carpeta ni la extensión se escriben.
- Las subcarpetas se separan con **puntos**: `view('proyectos.index')` es `resources/views/proyectos/index.blade.php`.
- Cada clave del array llega a la vista como una variable: `'centro' => …` se convierte en `$centro`.

Hay otra forma de pasar los datos, dato a dato, con `with()`:

```php
return view('proyectos.show')->with('id', $id);
```

Las vistas se crean con _Artisan_, que pone el fichero en su carpeta:

```bash
php artisan make:view proyectos.index
```

## _Blade_, el motor de plantillas

Los ficheros con extensión **`.blade.php`** usan **_Blade_**, el motor de plantillas de _Laravel_. _Blade_ añade una sintaxis más breve para lo que en _v0_ escribíamos con etiquetas PHP. La primera vez que se pide una vista, _Blade_ la **compila** a PHP normal y guarda el resultado en `storage/framework/views/` (5.2). Las siguientes veces usa directamente esa versión compilada.

![La primera vez que se pide la vista proyectos.index, Blade compila index.blade.php a un fichero PHP de storage/framework/views: las dobles llaves pasan a echo e() y @foreach a foreach. PHP ejecuta ese fichero y genera el HTML. Las siguientes peticiones usan directamente la versión compilada, hasta que cambie el .blade.php](./images/RA5_4_compilacion.svg)

En el diagrama, cada color marca una equivalencia: las dobles llaves se convierten en un `echo` con `e()`, y las directivas, en la sintaxis alternativa de PHP. El `foreach` compilado está simplificado: _Blade_ añade también lo necesario para la variable `$loop`.

### Mostrar datos

| En _v0_ | En _Blade_ |
|---|---|
| `<?= e($alumno) ?>` | `{{ $alumno }}` |
| `<?= e($video ?? 'Sin vídeo') ?>` | `{{ $video ?? 'Sin vídeo' }}` |
| `<?= $html ?>` (sin escapar) | `{!! $html !!}` |
| `<?php // comentario ?>` | `{{-- comentario --}}` |

Las dobles llaves **escapan siempre** el contenido con `e()`, como hacíamos a mano desde 3.6: escapar es lo normal y no escapar hay que pedirlo a propósito con `{!! !!}`. Solo se usa con HTML que hayamos generado nosotros, nunca con datos que vengan del usuario.

Los comentarios de _Blade_ no llegan al navegador. Los comentarios HTML (`<!-- -->`) sí.

### Estructuras de control

Las estructuras de control son **directivas** que empiezan por `@`:

```blade
@if ($proyecto['metadatos']['calificacion'] >= 5)
    <p>Aprobado</p>
@elseif ($proyecto['metadatos']['calificacion'] >= 4)
    <p>Casi</p>
@else
    <p>Suspenso</p>
@endif

@isset($id)
    <p>Visualizar el currículo de {{ $id }}</p>
@endisset

@foreach ($proyectos as $id => $proyecto)
    <li>{{ $proyecto['nombre'] }}</li>
@endforeach
```

Son la sintaxis alternativa de PHP que vimos en 3.2 (`<?php foreach (...): ?> … <?php endforeach; ?>`), sin las etiquetas.

Dentro de un `@foreach`, _Blade_ ofrece la variable **`$loop`**, con información sobre la vuelta actual:

| Propiedad | Valor |
|---|---|
| `$loop->iteration` | Número de vuelta, empezando en 1 |
| `$loop->first` / `$loop->last` | `true` en la primera / la última vuelta |
| `$loop->count` | Número total de elementos |

Y **`@forelse`** recorre una lista, pero muestra otra cosa si está vacía:

```blade
@forelse ($proyectos as $id => $proyecto)
    <li>{{ $proyecto['nombre'] }}</li>
@empty
    <li>Todavía no hay proyectos.</li>
@endforelse
```

### Clases CSS condicionales

La directiva **`@class`** construye el atributo `class` a partir de un array. Las clases sin clave se ponen siempre; las que tienen una condición, solo si se cumple:

```blade
<p @class([
    'font-bold',
    'text-green-700' => $proyecto['metadatos']['calificacion'] >= 5,
    'text-red-700' => $proyecto['metadatos']['calificacion'] < 5,
])>
    {{ $proyecto['metadatos']['calificacion'] }}
</p>
```

## Estilos con _Tailwind CSS_

Para dar estilo a las vistas usaremos **_Tailwind CSS_**, que ya viene configurado en el proyecto. Es también el que usa _Breeze_ (5.7). _Tailwind_ no trae componentes ya diseñados (botones, tarjetas…), sino **clases de utilidad**, cada una con una sola propiedad CSS:

| Clase | Propiedad |
|---|---|
| `p-4` | `padding: 1rem` |
| `mt-2` | `margin-top: 0.5rem` |
| `text-lg`, `font-bold` | Tamaño y grosor de la letra |
| `text-gray-500`, `bg-white` | Color del texto y del fondo |
| `rounded-lg`, `shadow` | Bordes redondeados y sombra |
| `grid`, `sm:grid-cols-2` | Rejilla; dos columnas en pantallas a partir de 640 px |

El diseño se compone en el propio HTML, combinando clases: `<li class="rounded-lg bg-white p-4 shadow">` es una tarjeta blanca, con relleno, bordes redondeados y sombra. La [documentación de _Tailwind_](https://tailwindcss.com/docs) tiene un buscador para cada propiedad.

### Compilar los estilos con _Vite_

El CSS no se escribe a mano en `public/`. Se **compila** con **_Vite_**, a partir de `resources/css/app.css`:

1. _Vite_ lee `resources/css/app.css`, que importa _Tailwind_.
2. _Tailwind_ busca en las vistas las clases que realmente usas y genera **solo** el CSS de esas clases.
3. El resultado se guarda en `public/build/`, que es lo que descarga el navegador.

Hace falta _Node_ y _npm_, que están en `workspace` ([§6 de la preparación del entorno](./00_instalacionEntorno.md#6-nodejs-y-npm-en-el-contenedor-workspace)). En el terminal de `workspace`:

```bash
npm install
npm run build
```

- **`npm install`** descarga en `node_modules/` las dependencias de `package.json`, entre ellas _Vite_ y _Tailwind_. Es el equivalente a `composer install` para JavaScript.
- **`npm run build`** compila los estilos y los guarda en `public/build/`.

Como _Tailwind_ solo genera las clases que encuentra, **hay que volver a compilar cada vez que uses una clase nueva**. Para no tener que acordarte, deja la compilación vigilando los cambios en un segundo terminal de `workspace`:

```bash
npm run build -- --watch
```

Para salir, <kbd>Ctrl</kbd>+<kbd>C</kbd>. Ni `node_modules/` ni `public/build/` se suben al repositorio (están en el `.gitignore`): cada miembro del equipo los genera en su máquina, igual que `vendor/`.

En la vista, la directiva **`@vite`** enlaza los ficheros compilados:

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

### Ficheros públicos: `asset()`

Las imágenes y los demás ficheros que se sirven tal cual van en `public/`. Para enlazarlos, la función **`asset()`** genera su dirección completa:

```blade
<img src="{{ asset('images/mp-logo.png') }}" alt="marcapersonalFP">
```

## _Layouts_ con componentes

Todas las páginas de la aplicación comparten la misma estructura: el `<head>`, la barra de navegación y el pie. En lugar de repetirla, se escribe una sola vez en un **_layout_**, y cada vista aporta solo su contenido.

En _Laravel_, el _layout_ es un **componente _Blade_**: una vista de `resources/views/components/` que se usa como si fuera una etiqueta HTML con el prefijo `x-`. El fichero `components/layout.blade.php` se usa como `<x-layout>`:

```blade
@props(['titulo' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ? $titulo . ' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-800">
    <x-navbar />

    <main class="mx-auto max-w-5xl p-6">
        @isset($titulo)
            <h1 class="mb-6 text-3xl font-bold">{{ $titulo }}</h1>
        @endisset

        {{ $slot }}
    </main>

    <footer class="py-6 text-center text-sm text-gray-500">
        {{ config('marcapersonalfp.centro') }}
    </footer>
</body>
</html>
```

- **`{{ $slot }}`** es el hueco donde va el contenido de cada vista: lo que se escribe entre `<x-layout>` y `</x-layout>`.
- **`@props`** declara los **atributos** que acepta el componente, con su valor por defecto. Aquí, `titulo`.
- **`<x-navbar />`** es otro componente, `components/navbar.blade.php`, dentro del _layout_.

Una vista que usa el _layout_ queda así de corta:

```blade
<x-layout titulo="Pantalla principal">
    <p>Bienvenida a marcapersonalFP, la red de currículos del {{ $centro }}.</p>
</x-layout>
```

Un atributo normal recibe un texto. Si el valor es una **expresión PHP**, el atributo lleva dos puntos delante:

```blade
<x-layout :titulo="'Vista detalle proyecto ' . $id">
    ...
</x-layout>
```

![La página /proyectos se compone por piezas: el layout pone el esqueleto con la barra, el título, el hueco $slot y el pie; el componente navbar rellena la barra; la vista proyectos.index pasa el título y lo que escribe entre x-layout y /x-layout ocupa el $slot; cada x-tarjeta-proyecto se sustituye por su componente](./images/RA5_4_composicion.svg)

Así se compone la página del listado: el _layout_ (1) pone el esqueleto; `<x-navbar />` (2) se sustituye por su componente; la vista (3) pasa el título y (4) su contenido ocupa el `{{ $slot }}`; dentro, cada `<x-tarjeta-proyecto />` (5) se sustituye también por su componente.

### La barra de navegación

El componente `navbar` usa `route()` (5.3) para los enlaces, y `request()->routeIs()` para destacar la sección actual:

```blade
<nav class="bg-white shadow">
    <div class="mx-auto flex max-w-5xl items-center gap-6 px-6 py-3">
        <a href="{{ url('/') }}">
            <img src="{{ asset('images/mp-logo.png') }}" alt="marcapersonalFP" class="h-12">
        </a>
        <a href="{{ route('proyectos.index') }}" @class([
            'font-semibold',
            'text-blue-700' => request()->routeIs('proyectos.*'),
            'text-gray-600 hover:text-gray-900' => ! request()->routeIs('proyectos.*'),
        ])>Proyectos</a>
        <a href="{{ route('perfil') }}" @class([
            'font-semibold',
            'text-blue-700' => request()->routeIs('perfil'),
            'text-gray-600 hover:text-gray-900' => ! request()->routeIs('perfil'),
        ])>Mi perfil</a>
    </div>
</nav>
```

`routeIs('proyectos.*')` se cumple con cualquier ruta cuyo nombre empiece por `proyectos.`: el listado, el detalle, el alta y la edición.

### Componentes para piezas repetidas

Los componentes no son solo para el _layout_: cualquier trozo de HTML que se repite puede serlo. Por ejemplo, la tarjeta de un proyecto, en `components/tarjeta-proyecto.blade.php`:

```blade
@props(['proyecto', 'id'])

<li class="rounded-lg bg-white p-4 shadow">
    <h2 class="text-lg font-semibold">{{ $proyecto['nombre'] }}</h2>
    <p class="text-sm text-gray-500">
        {{ $proyecto['metadatos']['fecha_inicio'] }} – {{ $proyecto['metadatos']['fecha_fin'] }}
    </p>
    <p @class([
        'mt-2 font-bold',
        'text-green-700' => $proyecto['metadatos']['calificacion'] >= 5,
        'text-red-700' => $proyecto['metadatos']['calificacion'] < 5,
    ])>{{ $proyecto['metadatos']['calificacion'] }}</p>
    <a href="{{ route('proyectos.show', $id) }}" class="mt-3 inline-block text-blue-700 hover:underline">
        Más información
    </a>
</li>
```

Se usa con `<x-tarjeta-proyecto :proyecto="$proyecto" :id="$id" />`. Los dos puntos son necesarios porque los valores son variables, no textos.

## Preparación del entorno de trabajo

Seguimos en el proyecto `marcapersonalfp`, con las rutas de [5.3](./RA5_3_rutas.md) y el terminal de `workspace` abierto como `laradock`. Crea la rama del apartado en el terminal de la máquina virtual:

```bash
git switch main
git pull upstream main
git switch -c RA5_4
```

## Ejercicios

1. **Los estilos.** En el terminal de `workspace`, instala las dependencias de _npm_ y deja la compilación vigilando en un segundo terminal (`npm run build -- --watch`). Copia el logo del módulo a `public/images/`:

    ```bash
    mkdir -p public/images
    curl -o public/images/mp-logo.png https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/mp-logo.png
    ```

2. **El _layout_ y la barra de navegación.** Crea los componentes `components/layout.blade.php` y `components/navbar.blade.php` de este apartado.

3. **Las vistas del sitio.** Crea una vista para cada ruta de 5.3, todas con `<x-layout>`, y cambia cada ruta para que devuelva su vista. El texto que devolvía cada ruta pasa a ser el `titulo` del _layout_ (salvo en `perfil`, que se explica debajo de la tabla).

    | Ruta | Vista | Datos que recibe |
    |---|---|---|
    | `/` | `home` | `centro`, de la configuración de 5.2 |
    | `proyectos.index` | `proyectos.index` | `proyectos` (ejercicio 4) |
    | `proyectos.show` | `proyectos.show` | `id` |
    | `proyectos.create` | `proyectos.create` | — |
    | `proyectos.edit` | `proyectos.edit` | `id` |
    | `perfil` | `perfil` | `id`, que puede ser `null` |

    La vista `perfil` lleva el título "Perfil" y, en el contenido, usa `@isset($id)` para elegir entre "Visualizar el currículo de {id}" y "Visualizar el currículo propio".

4. **El listado de proyectos.** En la ruta `proyectos.index`, pasa a la vista este array con los tres primeros proyectos de [`array_proyectos.php`](./materiales/ejercicios-laravel/array_proyectos.php):

    ```php
    $proyectos = [
        ['docente_id' => 1, 'nombre' => 'Tecnologías de la Información', 'dominio' => 'TecnologiaInformacion',
         'metadatos' => ['fecha_inicio' => '2023-01-15', 'fecha_fin' => '2023-05-30', 'calificacion' => 9.5]],
        ['docente_id' => 1, 'nombre' => 'Diseño Gráfico', 'dominio' => 'DisenyoGrafico',
         'metadatos' => ['fecha_inicio' => '2023-02-10', 'fecha_fin' => '2023-06-20', 'calificacion' => 4.0]],
        ['docente_id' => 2, 'nombre' => 'Electrónica', 'dominio' => 'Electronica',
         'metadatos' => ['fecha_inicio' => '2023-03-05', 'fecha_fin' => '2023-07-15', 'calificacion' => 9.2]],
    ];
    ```

    La vista muestra cada proyecto con el componente `tarjeta-proyecto`, dentro de una rejilla (`<ul class="grid gap-4 sm:grid-cols-2">`). Si el array está vacío, debe mostrar "Todavía no hay proyectos" (`@forelse`). El índice de cada proyecto en el array (0, 1, 2) es su `id`.

    Fíjate en lo largo que se ha vuelto `routes/web.php`. En 5.5 sacaremos esos datos a un **controlador**.

5. **Comprueba el escapado.** Pide `http://marcapersonalfp.test/perfil` y, en la vista `perfil`, cambia temporalmente "Visualizar el currículo propio" por `{{ '<script>alert(1)</script>' }}`. ¿Se ejecuta el script? Mira el código fuente de la página (<kbd>Ctrl</kbd>+<kbd>U</kbd>). Después, prueba con `{!! !!}` y deshaz el cambio.

6. **Investiga.** Responde en tu _pull request_:
    - Abre `storage/framework/views/`. ¿Qué contiene? Busca el fichero compilado de `perfil.blade.php` (su contenido, no su nombre, te dirá cuál es). ¿En qué se ha convertido `{{ $id }}`?
    - Detén el `npm run build -- --watch`, añade a una vista una clase de _Tailwind_ que no hayas usado antes (por ejemplo, `bg-yellow-200`) y recarga. ¿Se aplica? ¿Por qué? ¿Qué cambia tras `npm run build`?

> **Puente.** En _v0_, cada página repetía su estructura y mezclaba el `foreach` con el HTML y el `e()` de cada dato. En _Laravel_, las vistas solo presentan; el _layout_ se escribe una vez; y `{{ }}` escapa sin que haya que acordarse. Esto que hicimos a mano, el framework lo automatiza así.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 4, copia el archivo [RA5_4_VistasTest.php](./materiales/ejercicios-laravel/tests/Feature/RA5_4_VistasTest.php) a la carpeta `tests/Feature/` de tu proyecto y ejecuta, en el terminal de `workspace`, ese test en concreto:

```bash
php artisan test --filter=RA5_4_VistasTest
```

El test comprueba:
- que cada ruta devuelve su vista, con sus datos y su texto;
- que las páginas comparten el _layout_: el logo y el nombre del centro en el pie;
- que existen los tres componentes y que el _layout_ usa `{{ $slot }}` y `@vite`;
- que has compilado los estilos (`public/build/manifest.json`).

Si todo está bien: `Tests: 7 passed (36 assertions)`.

> Los tests de 5.1 y 5.3 siguen pasando: el texto de cada ruta aparece ahora como título de su vista. Eso sí, como el _layout_ usa `@vite`, necesitan los estilos compilados.

---

**Siguiente:** [5.5. Controladores](./RA5_5_controladores.md)
<!-- {% endraw %} -->
