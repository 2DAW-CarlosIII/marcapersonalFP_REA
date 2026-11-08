# El _layout_ del _ePortfolio_ con una plantilla
<!-- {% raw %} -->

En el apartado [5.4.1. El _layout_ con una plantilla](https://github.com/2DAW-CarlosIII/marcapersonalFP_REA/blob/master/documentos/0613_Servidor/RA5_4_1_plantilla.md) repartiste la plantilla _Dopetrope_ en componentes, siguiendo los pasos uno a uno. En este _issue_ vas a hacer lo mismo en tu _ePortfolio_, pero con **otra plantilla** y **sin que nadie te dé el código**: tú decides qué piezas se repiten y qué atributos y _slots_ necesita cada una.

## La plantilla de tu grupo

Cada grupo trabaja con una plantilla **distinta** de esta tabla. Antes de empezar, acordad con el profesor cuál os corresponde. Todas tienen licencia MIT y están hechas con _Tailwind CSS_.

| Plantilla | Código | _Tailwind_ | Ten en cuenta |
|---|---|---|---|
| _Daiva_ | [lbegey/tw-daiva-tpl](https://github.com/lbegey/tw-daiva-tpl) (`daiva.html`) | 4, desde un CDN | Usa _Alpine_ e iconos de _Bootstrap_. Sus imágenes están en la raíz del repositorio. |
| _BookMe_ | [lbegey/bookme-tpl](https://github.com/lbegey/bookme-tpl) (`bookme.html`) | 3, desde un CDN | Usa _Alpine_, iconos de _Bootstrap_ y las animaciones de _AOS_. |
| _SendIt_ | [lbegey/tw-sendit-tpl](https://github.com/lbegey/tw-sendit-tpl) (`sendit.html`) | 3, desde un CDN | Usa _Alpine_ e iconos de _Bootstrap_. |
| _Landwind_ | [themesberg/landwind](https://github.com/themesberg/landwind) (`index.html`) | 3, compilado | Sus colores están en `tailwind.config.js` y usa _Flowbite_ para el menú. |
| _Play_ | [TailGrids/play-tailwind](https://github.com/TailGrids/play-tailwind) (`index.html`) | 4, compilado | Su tema está en `src/input.css`, junto con sus `@utility` y `@layer`, que también hay que copiar. Usa _Swiper_ y _WOW.js_. Es la más larga: busca bien qué se repite. |

En adelante, `[plantilla]` es el nombre de la vuestra, en minúsculas: `daiva`, `bookme`, `sendit`, `landwind` o `play`.

## Qué hay que conseguir

Al terminar, **todas las vistas del _ePortfolio_ se muestran dentro de la plantilla, sin haber cambiado ninguna**: siguen usando `<x-layout>`, y es `<x-layout>` quien se apoya en la plantilla, como en 5.4.1.

### 1. Descarga la plantilla y sus ficheros

- Clona o descarga el repositorio de la plantilla **fuera** de tu proyecto, y abre su página principal en el navegador para ver cómo es.
- Copia a `public/[plantilla]/` las imágenes y los demás ficheros que la página necesita (por ejemplo, `public/[plantilla]/images/`).
- Copia también su fichero `LICENSE` a `public/[plantilla]/LICENSE`. La licencia MIT solo pone una condición: conservar el aviso de copyright.

### 2. El tema y las librerías

- **_Tailwind_ se compila con _Vite_**, como en 5.4. Quita de la plantilla el `<script>` o el `<link>` que carga _Tailwind_ desde un CDN, o su `output.css` compilado.
- Si la plantilla define colores, fuentes u otros valores propios, llévalos al bloque `@theme` de `resources/css/app.css`. Según la plantilla, están en:
    - un bloque `tailwind.config = { … }` dentro del HTML o en `tailwind.config.js` (_Tailwind_ 3): cada color de `theme.extend.colors` se convierte en una variable `--color-…`;
    - un bloque `@theme` (_Tailwind_ 4): se copia tal cual.
- **_Alpine_ se instala con _npm_**, no desde un CDN: `npm install alpinejs` y, en `resources/js/app.js`:

    ```js
    import Alpine from 'alpinejs';

    window.Alpine = Alpine;
    Alpine.start();
    ```

    El motivo: en 5.7, _Breeze_ instalará _Alpine_ de esta misma forma, y si también se carga desde un CDN, se ejecutaría dos veces.
- El resto de librerías (iconos, _AOS_, _Swiper_, _WOW.js_, _Flowbite_…) pueden seguir enlazadas desde su CDN, o copiarse a `public/[plantilla]/` y enlazarse con `asset()`.

### 3. Trocea la plantilla en componentes

Crea los componentes en `resources/views/components/[plantilla]/`:

| Componente | Qué contiene |
|---|---|
| `layout.blade.php` | El `<head>` (con `@vite`), y `<x-[plantilla].header />`, `<x-[plantilla].main>` y `<x-[plantilla].footer />`. Recibe el contenido de la vista en `{{ $slot }}`. |
| `header.blade.php` | La cabecera de la plantilla. Si tu _ePortfolio_ ya tiene `components/navbar.blade.php`, sustituye por `<x-navbar />` el menú de la plantilla. |
| `main.blade.php` | El cuerpo de la página. El `{{ $slot }}` va en el sitio que te parezca más natural para el contenido de cada vista. |
| `footer.blade.php` | El pie. |

Además, busca en la plantilla las **piezas que se repiten** (tarjetas, servicios, precios, testimonios, preguntas frecuentes, botones…) y conviértelas en componentes de la misma carpeta. Como mínimo:

- **un componente con `@props`** que se use varias veces con datos distintos (mejor aún si los datos salen de un array recorrido con `@foreach`, como el _portfolio_ de 5.4.1);
- **un componente con un _slot_ con nombre** (`<x-slot:nombre>`), como el pie de las cajas de 5.4.1.

Recuerda lo que viste en 5.4:

- Los atributos sin valor llegan como `true`, y los que no están en `@props` se recogen en `$attributes`.
- Comenta con `{{-- --}}`: _Blade_ procesa las etiquetas `<x-…>` aunque estén dentro de un comentario `<!-- -->`.

### 4. Enlaces a ficheros con `asset()`

Ningún `src` ni `href` de la plantilla puede quedarse con una ruta relativa, como `src="images/hero.png"`: dejaría de funcionar en cuanto la página no esté en la raíz. Usa `asset()`:

```blade
<img src="{{ asset('[plantilla]/images/hero.png') }}" alt="">
```

Para los enlaces entre páginas de tu _ePortfolio_, usa `route()` o `url()`. Los enlaces a `#` (anclas de la misma página) y a sitios externos (`https://…`) se quedan como están.

### 5. Usa la plantilla desde `<x-layout>`

Cambia `components/layout.blade.php` para que se apoye en la plantilla, como en 5.4.1. Mantén los atributos que ya aceptaba (por ejemplo, `titulo`) y pásaselos a la plantilla si los necesita:

```blade
@props(['titulo' => null])

<x-[plantilla].layout :titulo="$titulo">
    {{ $slot }}
</x-[plantilla].layout>
```

### Si la plantilla es de _Tailwind_ 3

_BookMe_, _SendIt_ y _Landwind_ están hechas con _Tailwind_ 3, y tu proyecto usa _Tailwind_ 4. Casi todas las clases son iguales, pero algunas cambiaron de nombre. Si algo no se ve como en el original, revisa esta tabla:

| _Tailwind_ 3 | _Tailwind_ 4 |
|---|---|
| `shadow-sm` / `shadow` | `shadow-xs` / `shadow-sm` |
| `rounded-sm` / `rounded` | `rounded-xs` / `rounded-sm` |
| `blur-sm` / `blur` | `blur-xs` / `blur-sm` |
| `outline-none` | `outline-hidden` |
| `ring` | `ring-3` |
| `flex-grow`, `flex-shrink` | `grow`, `shrink` |
| `bg-opacity-50` (con `bg-black`) | `bg-black/50` |

Además, en _Tailwind_ 4 el borde (`border`) toma por defecto el color del texto, y no un gris: añade `border-gray-200` donde haga falta. La lista completa está en la [guía de actualización de _Tailwind_](https://tailwindcss.com/docs/upgrade-guide).

## Comprobación automática

Copia el test [RA5_4_PlantillaTest.php](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_4_PlantillaTest.php) a la carpeta `tests/Feature/` de tu _ePortfolio_ y ejecútalo en el terminal de `workspace`:

```bash
curl -o tests/Feature/RA5_4_PlantillaTest.php https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_4_PlantillaTest.php
php artisan test --filter=RA5_4_PlantillaTest
```

El test sirve para cualquiera de las plantillas: busca la subcarpeta de `components/` que tiene un `layout.blade.php`. Comprueba:

- que la plantilla tiene sus cuatro piezas (`layout`, `header`, `main` y `footer`), y que el _layout_ las usa junto con `@vite` y `{{ $slot }}`;
- que `<x-layout>` se apoya en la plantilla y muestra el contenido que recibe;
- que la cabecera usa `<x-navbar />`, si el proyecto tiene ese componente;
- que hay al menos un componente propio con `@props` y algún _slot_ con nombre;
- que _Tailwind_ no se carga desde un CDN, y _Alpine_ tampoco;
- que ningún `src` ni `href` de la plantilla usa una ruta relativa;
- que se conserva la licencia en `public/[plantilla]/`;
- que la portada responde.

El test no puede saber si la página **se ve** como la plantilla original. Eso lo comprueba tu compañero.

## Revisión por un compañero

Cuando abras el _pull request_, un compañero de tu grupo lo revisa y deja su valoración como comentario. Para ello:

1. Descarga la rama del _pull request_ (5.1, "Probar un _pull request_ localmente"), compila los estilos (`npm install` y `npm run build`) y ejecuta el test.
2. Abre la portada y otras dos páginas del _ePortfolio_, y compáralas con la plantilla original abierta en otra pestaña: colores, fuentes, imágenes, iconos, menús desplegables y animaciones. Estrecha la ventana para comprobar que también se adapta al móvil.
3. Revisa el código:
    - ¿Los componentes son piezas que de verdad se repiten? ¿Queda HTML repetido que debería ser un componente o un `@foreach`?
    - ¿Los nombres de los componentes, atributos y _slots_ se entienden sin abrir el fichero?
    - ¿Se ha cambiado alguna vista para que se vea en la plantilla? No debería: todo pasa por `<x-layout>`.
4. Deja en el _pull request_ un comentario con el resultado del test, lo que funciona bien y lo que habría que mejorar, señalando cada línea con un comentario de revisión.
<!-- {% endraw %} -->
