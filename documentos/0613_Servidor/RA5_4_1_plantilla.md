# 5.4.1. El _layout_ con una plantilla
<!-- {% raw %} -->

Este es el **ejercicio 5** de [5.4. Vistas, _Blade_ y _layouts_](./RA5_4_vistas.md). Se hace en la misma rama, `RA5_4`, cuando ya funcionen los ejercicios 1 a 4: el _layout_, la barra de navegación y las vistas de cada ruta.

## La plantilla

En internet hay sitios que ofrecen plantillas gratuitas para nuestros proyectos, como [HTML5 UP](https://html5up.net/). De entre todas ellas, hemos elegido **_Dopetrope_**.

La plantilla original trae su propia hoja de estilos (`main.css`) y varios ficheros _JavaScript_ con _jQuery_. Nosotros vamos a usar una **versión convertida a _Tailwind CSS_**, sin _jQuery_ ni _Font Awesome_:

- [dopetrope.html](./materiales/ejercicios-laravel/dopetrope.html)

Descárgala y ábrela directamente en el navegador. Para poder verla sin compilar nada, carga _Tailwind_ desde un CDN, pero eso es solo para esta vista previa: en _Laravel_ los estilos se compilan con _Vite_, como en 5.4. Las imágenes son de relleno, de [picsum.photos](https://picsum.photos), así que no hay que descargar ninguna.

Fíjate en cómo está organizada. Tiene tres partes:

- `header`: el logo, el menú, el _banner_ y la introducción con los tres iconos.
- `main`: el _portfolio_ y el _blog_, formados por **cajas** casi idénticas.
- `footer`: el pie, con las fechas, los enlaces, las redes sociales y el _copyright_.

En lugar de copiar el HTML en una sola vista, vamos a **trocearlo en componentes**, en la carpeta `resources/views/components/dopetrope/`. El resultado será que todas las vistas que ya tienes, sin tocar ninguna, se mostrarán dentro de la plantilla.

| Componente | Atributos (`@props`) | Huecos (_slots_) |
|---|---|---|
| `<x-dopetrope.layout>` | `titulo` | `$slot` |
| `<x-dopetrope.header />` | — | — |
| `<x-dopetrope.main>` | `titulo` | `$slot` |
| `<x-dopetrope.footer />` | — | — |
| `<x-dopetrope.seccion>` | `titulo` | `$slot` |
| `<x-dopetrope.box>` | `imagen`, `titulo`, `subtitulo` | `$slot`, `$footer` |
| `<x-dopetrope.boton>` | `href`, `alt`, `grande` | `$slot` |
| `<x-dopetrope.icono />` | `nombre` | — |

Una subcarpeta de `components/` se indica con un **punto** en el nombre de la etiqueta: `components/dopetrope/header.blade.php` es `<x-dopetrope.header />`. Puedes crear cada componente con _Artisan_:

```bash
php artisan make:component dopetrope/header --view
```

La opción `--view` crea solo la vista, sin clase: un componente **anónimo**, como los de 5.4.

## Los colores y la fuente en el tema de _Tailwind_

La plantilla usa unos colores y una fuente concretos. En lugar de repetir el código del color (`#d52349`) en cada clase, los definimos **una vez** en el bloque `@theme` de `resources/css/app.css`. Añade estas líneas dentro del `@theme` que ya tienes:

```css
@theme {
    /* ... lo que ya había ... */

    /* Plantilla Dopetrope */
    --font-dopetrope: 'Source Sans 3', 'Source Sans Pro', ui-sans-serif, system-ui, sans-serif;

    --color-dope-red: #d52349;
    --color-dope-red-hover: #e53359;
    --color-dope-dark: #252122;
    --color-dope-dark-hover: #353132;
    --color-dope-gray: #827a7c;
    --color-dope-text: #5d5d5d;
    --color-dope-light: #f7f7f7;
    --color-dope-footer: #858484;
    --color-dope-footer-link: #c5c4c4;
}
```

Cada variable se convierte en clases de utilidad:

- `--color-dope-red` genera `bg-dope-red`, `text-dope-red`, `border-dope-red`…
- `--font-dopetrope` genera `font-dopetrope`.

Es el mismo bloque que hay al principio de `dopetrope.html`. Si mañana cambia el rojo de la plantilla, se cambia en un solo sitio.

> En 5.7, _Breeze_ sustituirá `app.css` por el suyo y pasará el proyecto a _Tailwind_ 3. Allí veremos cómo llevar estos colores a `tailwind.config.js`.

Recuerda que _Tailwind_ solo genera las clases que encuentra en tus vistas: deja `npm run build -- --watch` funcionando en un terminal de `workspace` mientras haces este _issue_.

## El _layout_ de la plantilla

Crea `components/dopetrope/layout.blade.php`. Es el esqueleto de la página: el `<head>` y las tres partes de la plantilla.

```blade
@props(['titulo' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ? $titulo . ' · ' : '' }}{{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=source-sans-3:300,300i,400,700,900" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-dope-dark font-dopetrope text-lg leading-[1.75] font-light text-dope-text">

    <x-dopetrope.header />

    <x-dopetrope.main :titulo="$titulo">
        {{ $slot }}
    </x-dopetrope.main>

    <x-dopetrope.footer />

</body>
</html>
```

Fíjate en que el `{{ $slot }}` no se muestra aquí, sino que se **pasa** a `<x-dopetrope.main>`, que será quien decida dónde colocarlo. Lo mismo ocurre con el título: `:titulo="$titulo"` lleva dos puntos porque el valor es una variable.

## Utilizar el _layout_ de la plantilla

Ahora, el _layout_ de la aplicación, `components/layout.blade.php`, pasa a apoyarse en el de la plantilla. Sustituye **todo** su contenido por:

```blade
@props(['titulo' => null])

<x-dopetrope.layout :titulo="$titulo">
    {{ $slot }}
</x-dopetrope.layout>
```

Con este cambio, todas las vistas que usan `<x-layout>` se muestran ahora con la plantilla, y ninguna ha tenido que cambiar. Es la misma idea que el `@extends` de un _layout_ que extiende de otro, pero con componentes: `<x-layout>` recibe el contenido de la vista y se lo pasa a `<x-dopetrope.layout>`.

Todavía no funciona: faltan los componentes que usa el _layout_ de la plantilla.

## Componentes pequeños: botón e icono

### El botón

La plantilla tiene botones rojos y negros (`alt`), de dos tamaños (`grande`). Crea `components/dopetrope/boton.blade.php`:

```blade
@props(['href' => '#', 'alt' => false, 'grande' => false])

<a href="{{ $href }}" @class([
    'inline-flex items-center justify-center gap-2 rounded-[5px] font-bold text-white transition-colors',
    'px-6 py-2.5' => ! $grande,
    'min-w-[12em] px-9 py-4 text-2xl' => $grande,
    'bg-dope-red hover:bg-dope-red-hover' => ! $alt,
    'bg-dope-dark hover:bg-dope-dark-hover' => $alt,
])>{{ $slot }}</a>
```

Un atributo escrito **sin valor** llega al componente como `true`:

```blade
<x-dopetrope.boton>Find out more</x-dopetrope.boton>               {{-- rojo, normal --}}
<x-dopetrope.boton alt>Find out more</x-dopetrope.boton>           {{-- negro, normal --}}
<x-dopetrope.boton grande alt>Learn More</x-dopetrope.boton>       {{-- negro, grande --}}
<x-dopetrope.boton :href="route('proyectos.index')">Ir</x-dopetrope.boton>
```

### Los iconos

Los iconos de la plantilla original eran una fuente (_Font Awesome_). En la versión _Tailwind_ son dibujos SVG dentro del HTML. Para no copiar esos dibujos en cada sitio, los tienes reunidos en un componente ya hecho, [icono.blade.php](./materiales/ejercicios-laravel/dopetrope/icono.blade.php). Descárgalo en `components/dopetrope/`:

```bash
mkdir -p resources/views/components/dopetrope
curl -o resources/views/components/dopetrope/icono.blade.php https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/dopetrope/icono.blade.php
```

Se usa así:

```blade
<x-dopetrope.icono nombre="engranaje" class="mx-auto size-[4.5rem]" />
```

`nombre` es un atributo declarado con `@props`, pero `class` no lo es. Los atributos que no están en `@props` se recogen en la variable **`$attributes`**, y el componente los escribe en la etiqueta `<svg>` con `{{ $attributes }}`. Así, cada icono puede tener su propio tamaño sin que el componente lo sepa de antemano.

Los nombres disponibles son: `engranaje`, `rayo`, `estrella`, `documento`, `comentario`, `facebook`, `twitter`, `dribbble`, `tumblr` y `linkedin`.

## La cabecera

Crea `components/dopetrope/header.blade.php` y mueve a él la sección `<section id="header">` de `dopetrope.html`, con estos cambios:

- **Elimina** el logo (`<h1>`) y el menú (`<nav id="nav">`) de la plantilla, y pon en su lugar **`<x-navbar />`**, la barra de navegación de 5.4. Quita también las clases `pt-16 md:pt-[4.5em]` del `<section id="header">`, que dejaban sitio para el logo, y cambia el `mt-16` del _banner_ por `mt-12`.
- Sustituye cada `<svg>…</svg>` de los tres iconos de la introducción por el componente `icono`:

    ```blade
    <x-dopetrope.icono nombre="engranaje" class="mx-auto size-[4.5rem]" />
    ```

- Sustituye los dos enlaces "Get Started" y "Learn More" por el componente `boton`, con el atributo `grande`.

## Secciones y cajas

### La sección

Tanto el _portfolio_ como el _blog_ empiezan con un título centrado entre dos líneas. Crea `components/dopetrope/seccion.blade.php`:

```blade
@props(['titulo'])

<section class="mt-16">
    <header class="relative mt-4 mb-12 border-t border-[#ccc] text-center">
        <h2 class="relative -top-[0.65em] inline bg-dope-light px-4 text-3xl text-dope-dark">{{ $titulo }}</h2>
    </header>

    {{ $slot }}
</section>
```

### La caja

Las seis cajas del _portfolio_ y las dos del _blog_ tienen la misma estructura: una imagen, un título, un texto y, abajo, uno o dos botones. Crea `components/dopetrope/box.blade.php`:

```blade
@props(['imagen', 'titulo', 'subtitulo' => null])

<section class="border-b border-[#ccc] bg-white px-7 pb-11">
    <a href="#" class="-mx-7 mb-10 block"><img src="{{ $imagen }}" alt="" class="w-full"></a>

    <header class="mb-6">
        <h3 class="text-2xl text-dope-dark">{{ $titulo }}</h3>
        @if ($subtitulo)
            <p class="mt-2 italic">{{ $subtitulo }}</p>
        @endif
    </header>

    {{ $slot }}

    @isset($footer)
        <footer class="mt-9">
            <ul class="flex flex-wrap gap-4">
                {{ $footer }}
            </ul>
        </footer>
    @endisset
</section>
```

La caja tiene **dos huecos**:

- `{{ $slot }}`, el de siempre, para el texto.
- `{{ $footer }}`, un **_slot_ con nombre**, para los botones. Se rellena con `<x-slot:footer>`.

```blade
<x-dopetrope.box titulo="Ipsum feugiat et dolor" imagen="https://picsum.photos/seed/dopetrope-1/800/450">
    <p>Lorem ipsum dolor sit amet…</p>

    <x-slot:footer>
        <li><x-dopetrope.boton alt>Find out more</x-dopetrope.boton></li>
    </x-slot:footer>
</x-dopetrope.box>
```

Todo lo que no está dentro de un `<x-slot>` va a `$slot`. Si no se pasa el _slot_ `footer`, la variable `$footer` no existe, y `@isset` evita que se pinte un `<footer>` vacío.

## El contenido principal: `main`

Crea `components/dopetrope/main.blade.php`. Aquí se coloca el **contenido de cada vista** (el `$slot` que viene desde `<x-layout>`), justo antes del _portfolio_:

```blade
@props(['titulo' => null])

@php
    $portfolio = [
        'Ipsum feugiat et dolor',
        'Sed etiam lorem nulla',
        'Consequat et tempus',
        'Blandit sed adipiscing',
        'Etiam nisl consequat',
        'Dolore nisl feugiat',
    ];
@endphp

<main id="main" class="bg-dope-light py-16">
    <div class="mx-auto max-w-[1200px] px-6">

        {{-- Content: el contenido propio de cada vista --}}
        <section>
            <header class="relative mt-4 mb-12 border-t border-[#ccc] text-center">
                <h1 class="relative -top-[0.65em] inline bg-dope-light px-4 text-3xl text-dope-dark">{{ $titulo ?? config('app.name') }}</h1>
            </header>

            {{ $slot }}
        </section>

        {{-- Portfolio --}}
        <x-dopetrope.seccion titulo="My Portfolio">
            <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($portfolio as $titular)
                    <x-dopetrope.box :titulo="$titular" :imagen="'https://picsum.photos/seed/dopetrope-' . $loop->iteration . '/800/450'">
                        <p>Lorem ipsum dolor sit amet sit veroeros sed amet blandit consequat veroeros lorem blandit adipiscing et feugiat phasellus tempus dolore ipsum lorem dolore.</p>

                        <x-slot:footer>
                            <li><x-dopetrope.boton alt>Find out more</x-dopetrope.boton></li>
                        </x-slot:footer>
                    </x-dopetrope.box>
                @endforeach
            </div>
        </x-dopetrope.seccion>

        {{-- Blog --}}

    </div>
</main>
```

El título de cada vista pasa a ser el encabezado de la sección de contenido, con el mismo estilo que "My Portfolio". Las seis cajas del _portfolio_ salen de un solo `@foreach`; `$loop->iteration` sirve para que cada caja tenga una imagen distinta.

Completa tú el **_blog_**, debajo del comentario `{{-- Blog --}}`: una `<x-dopetrope.seccion>` con título "The Blog", una rejilla de dos columnas (`grid gap-12 lg:grid-cols-2`) y las dos cajas de `dopetrope.html`. Cada caja lleva el `subtitulo` "Posted 45 minutes ago" y, en su pie, dos botones con icono:

```blade
<x-dopetrope.boton>
    <x-dopetrope.icono nombre="documento" class="size-4" /> Continue Reading
</x-dopetrope.boton>
```

> **Recuerda el aviso de 5.4 sobre los comentarios.** _Blade_ procesa las etiquetas `<x-…>` aunque estén dentro de `<!-- -->`. En los componentes de la plantilla, comenta con `{{-- --}}`.

## El pie: `footer`

Crea `components/dopetrope/footer.blade.php` y mueve a él la sección `<section id="footer">` de `dopetrope.html`. Cámbiale la etiqueta `<section>` exterior por `<footer>`, y haz estos cambios:

- Sustituye el texto del _copyright_ "Untitled. All rights reserved." por el nombre del centro, como en el pie de 5.4:

    ```blade
    <li class="px-4">&copy; {{ config('marcapersonalfp.centro') }}</li>
    ```

    Mantén el crédito "Design: HTML5 UP": la licencia de la plantilla (_Creative Commons Attribution_) obliga a citar al autor.

- Sustituye los SVG de las redes sociales por el componente `icono` (`facebook`, `twitter`, `dribbble`, `tumblr` y `linkedin`), con `class="size-6"`.
- Sustituye el botón "Find out more" por el componente `boton`.

**Opcional:** el pie repite mucho HTML (cinco fechas, dos listas de enlaces idénticas, cinco redes sociales). Prueba a guardar esos datos en un array dentro de un bloque `@php` y a recorrerlos con `@foreach`, como en el _portfolio_.

## Comprueba el resultado

Recarga `http://marcapersonalfp.test/` y `http://marcapersonalfp.test/proyectos`. Debes ver la plantilla completa, con tu barra de navegación arriba y el contenido de cada vista justo antes de "My Portfolio". Si alguna parte no tiene estilos, comprueba que `npm run build -- --watch` sigue funcionando.

Prueba también a estrechar la ventana del navegador: las rejillas pasan de tres columnas a dos y a una, gracias a los prefijos `md:` y `lg:`.

## Comprueba tu solución automáticamente (opcional)

Este ejercicio lo comprueba el mismo test de 5.4, [RA5_4_VistasTest.php](./materiales/ejercicios-laravel/tests/Feature/RA5_4_VistasTest.php). Si ya lo tenías copiado, vuelve a descargarlo y ejecútalo en el terminal de `workspace`:

```bash
php artisan test --filter=RA5_4_VistasTest
```

Además de lo que comprobaba para los ejercicios 1 a 4, el test comprueba:

- que existen los ocho componentes de `components/dopetrope/`;
- que `<x-layout>` se apoya en `<x-dopetrope.layout>`, y que este usa `@vite`, la cabecera, el contenido principal y el pie;
- que la cabecera usa `<x-navbar />` en lugar del menú de la plantilla;
- que ningún componente usa _jQuery_ ni el CSS original;
- que el tema de `app.css` define los colores y la fuente;
- que los componentes `boton`, `seccion`, `box` e `icono` generan el HTML esperado con sus atributos y _slots_;
- que la portada y el listado de proyectos se muestran dentro de la plantilla, con el contenido antes del _portfolio_.

Si todo está bien: `Tests: 18 passed`.

---

**Anterior:** [5.4. Vistas, _Blade_ y _layouts_](./RA5_4_vistas.md) · **Siguiente:** [5.5. Controladores](./RA5_5_controladores.md)
<!-- {% endraw %} -->
