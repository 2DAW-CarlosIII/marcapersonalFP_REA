# 3.1. Tomas de decisión

> **Bloque 3 · Programación basada en lenguajes de marcas con código embebido (RA3).** Seguimos construyendo _marcapersonalFP v0_ en PHP plano. En el Bloque 2 nuestros scripts eran secuencias de sentencias que se ejecutaban siempre, de arriba abajo; en este bloque aprenderemos a **decidir** y **repetir**, a organizar los datos en **arrays**, **funciones** y **clases**, y a **recibir datos del usuario** mediante formularios. Terminaremos con un formulario de alta de currículos que valida lo que recibe. En el Bloque 4 reconstruiremos esa misma pieza con _Laravel_ y veremos que **el framework automatiza lo que aquí hacemos a mano**.

**Presentación de apoyo** (_RevealJS_): [RA3_1_decisiones_slides.html](./materiales/slides/RA3_1_decisiones_slides.html)

## Decidir qué se ejecuta

Hasta ahora, cada sentencia de nuestros scripts se ejecutaba siempre, una detrás de otra. Una **toma de decisión** (o **estructura condicional**) hace que un grupo de sentencias se ejecute **solo si se cumple una condición**. Es la primera vez que la página que genera el servidor puede ser **distinta según los datos**: recuerda de 2.1 que el navegador solo recibe el HTML resultante, así que el código de la rama que no se ejecuta ni siquiera llega al cliente.

## `if`, `elseif` y `else`

La forma básica es `if`: si la condición entre paréntesis vale `true`, se ejecutan las sentencias del bloque. Como anunciamos en 2.2, ese grupo de sentencias se encierra entre llaves `{ }`:

```php
<?php
  $grado = 'Superior';

  if ($grado === 'Superior') {
    echo '<p>Ciclo de Grado Superior: da acceso a la universidad.</p>';
  }
?>
```

Con `else` indicamos qué hacer cuando la condición **no** se cumple, y con `elseif` encadenamos más condiciones, que se comprueban en orden hasta que una se cumple (las siguientes ya no se evalúan):

```php
<?php
  if ($grado === 'Superior') {
    echo '<p>Ciclo de Grado Superior: da acceso a la universidad.</p>';
  } elseif ($grado === 'Medio') {
    echo '<p>Ciclo de Grado Medio: da acceso a un Grado Superior.</p>';
  } else {
    echo '<p>Grado desconocido.</p>';
  }
?>
```

Fíjate en que comparamos con `===`, como acordamos en 2.3: así, si `$grado` no fuera exactamente el string `'Superior'`, no entraríamos en esa rama por una conversión de tipos inesperada.

> **Detalle.** Cuando el bloque tiene una sola sentencia, PHP permite omitir las llaves. En este REA las escribiremos **siempre**: es más fácil de leer y evita errores al añadir después una segunda sentencia que creías dentro del `if`.

### Condiciones compuestas: `&&`, `||` y `!`

Los operadores lógicos que presentamos en 2.3 cobran aquí todo su sentido: permiten combinar varias comparaciones en una sola condición.

| Operador | La condición vale `true` si... |
|---|---|
| `$a && $b` | ...**ambas** son `true` |
| `$a \|\| $b` | ...**al menos una** es `true` |
| `!$a` | ...`$a` es `false` |

Un currículo de _marcapersonalFP_ tiene, entre otros campos, un vídeo (`video_curriculum`) y un documento de texto (`texto_curriculum`). Podemos decidir cómo de completo está:

```php
<?php
  $videoCurriculum = 'https://youtu.be/curriculo1';
  $textoCurriculum = '';

  if ($videoCurriculum !== '' && $textoCurriculum !== '') {
    echo '<p>Currículo completo</p>';
  } elseif ($videoCurriculum !== '' || $textoCurriculum !== '') {
    echo '<p>Currículo incompleto</p>';
  } else {
    echo '<p>Currículo vacío</p>';
  }
?>
```

El orden de las ramas importa: la primera condición es la más exigente (los dos campos rellenos); si no se cumple, la segunda basta con que haya uno de ellos.

### ¿Qué pasa si la condición no es un `bool`?

PHP convierte a `bool` lo que pongas en la condición. Por eso `if ($textoCurriculum)` es válido: un string vacío se convierte en `false` y uno con texto, en `true`. Es cómodo, pero esconde alguna sorpresa (lo verás en el ejercicio de investigación), así que, igual que con `===`, preferiremos escribir la comparación **explícita** (`$textoCurriculum !== ''`): deja claro qué estamos comprobando.

## `switch` y `match`

Cuando comparamos **una misma expresión** con muchos valores posibles, una cadena de `elseif` se vuelve larga. PHP ofrece dos alternativas. La clásica es `switch`:

```php
<?php
  $codFamilia = 'INF';

  switch ($codFamilia) {
    case 'INF':
      $familia = 'Informática y Comunicaciones';
      break;
    case 'ADG':
      $familia = 'Administración y Gestión';
      break;
    default:
      $familia = 'Familia desconocida';
  }
?>
```

Cada `case` necesita su `break`: si lo olvidas, la ejecución **continúa** en el `case` siguiente. Y, lo más importante para nosotros, `switch` compara con `==`, no con `===`: con `$codFamilia = '1'`, un `case 1:` se cumpliría, aunque uno sea string y el otro int.

Desde PHP 8 existe `match`, más compacto y más seguro:

```php
<?php
  $codFamilia = 'INF';

  $familia = match ($codFamilia) {
    'INF'   => 'Informática y Comunicaciones',
    'ADG'   => 'Administración y Gestión',
    'COM'   => 'Comercio y Marketing',
    default => 'Familia desconocida',
  };

  echo '<p>Familia profesional: ', $familia, '</p>';
?>
```

Las diferencias con `switch`:

| | `switch` | `match` |
|---|---|---|
| Comparación | `==` (convierte tipos) | `===` (estricta) |
| `break` | Necesario en cada `case` | No existe: cada rama es una sola expresión |
| Devuelve un valor | No: es una sentencia | Sí: es una **expresión** y se puede asignar |
| Sin coincidencia ni `default` | No hace nada | Error (`UnhandledMatchError`) |

Varios valores pueden compartir resultado separándolos por comas (`'DAW', 'DAM', 'ASIR' => 'Informática y Comunicaciones'`). En este REA, coherentes con la política de `===`, usaremos **`match`** cuando se trate de elegir un valor entre varios.

## El operador ternario y el operador `??`

Cuando la decisión solo sirve para elegir **entre dos valores**, el operador ternario `condición ? valorSiTrue : valorSiFalse` lo resuelve en una sola expresión, sin `if`:

```php
<?php
  $grado = 'Superior';
  $titulo = $grado === 'Superior' ? 'Técnico Superior' : 'Técnico';
  echo '<p>Titulación: ', $titulo, '</p>';
?>
```

Úsalo para casos cortos como este; si necesitas anidar un ternario dentro de otro, es mejor un `if` o un `match`.

El operador `??` (_null coalescing_) devuelve el valor de la izquierda si existe y no es `null`; si no, el de la derecha. Su gran ventaja es que **no da aviso** si la variable o la clave del array no existen:

```php
<?php
  $curriculo = ['alumno' => 'Ana López', 'ciclo' => 'DAW'];

  echo '<p>Vídeo: ', $curriculo['video'] ?? 'Sin vídeo de presentación', '</p>';
?>
```

Sin `??`, acceder a `$curriculo['video']` produciría un _warning_ (`Undefined array key "video"`). Este operador será imprescindible en 3.6, cuando leamos los datos de un formulario: nunca podemos dar por hecho que el usuario ha enviado un campo.

## Decisiones embebidas en el HTML: la sintaxis alternativa

Cuando lo que depende de la condición es **un fragmento grande de HTML**, meterlo entero en un `echo` resulta incómodo. PHP ofrece una **sintaxis alternativa** en la que la llave de apertura se sustituye por `:` y la de cierre por `endif;`. Así podemos abrir y cerrar `<?php ?>` y dejar el HTML fuera, tal cual:

```php
<?php $publicado = true; ?>

<?php if ($publicado): ?>
  <p class="estado">Currículo publicado</p>
<?php else: ?>
  <p class="estado">Currículo en borrador</p>
<?php endif; ?>
```

Es la forma natural de "decidir qué HTML se envía" en una página con código embebido: el servidor envía uno u otro párrafo, nunca los dos. Existe también para `elseif:`, y veremos sus equivalentes para los bucles en 3.2. Retén la idea: en el Bloque 4, las plantillas _Blade_ de _Laravel_ tienen `@if ... @else ... @endif`, que el framework traduce precisamente a esta sintaxis alternativa.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php`; si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

## Ejercicios

Todos los ejercicios van, uno debajo de otro, en el mismo script `vanilla_php/public/RA3_decisiones.php`. Compruébalo en `http://localhost/RA3_decisiones.php` después de cada uno.

1. **`if`/`elseif`/`else`.** Crea el script con la variable `$grado` (valor `'Superior'`) y el `if`/`elseif`/`else` completo de la sección [`if`, `elseif` y `else`](#if-elseif-y-else). Cambia después el valor a `'Medio'` y a `'Básico'`, recarga para ver cómo cambia la página, y **déjalo al final en `'Superior'`**.
2. **Condiciones compuestas.** Añade el ejemplo del currículo completo/incompleto/vacío con los valores de la sección (`$videoCurriculum` con URL y `$textoCurriculum` vacío). Antes de recargar, anota en un comentario qué mensaje esperas.
3. **`match`.** Añade el ejemplo de `match` con `$codFamilia = 'INF'` y su `echo`.
4. **Ternario y `??`.** Añade los dos ejemplos de la sección [El operador ternario y el operador `??`](#el-operador-ternario-y-el-operador-): el de la titulación (con `$grado = 'Superior'`) y el del currículo sin clave `'video'`.
5. **Sintaxis alternativa.** Cierra el script con el ejemplo de `$publicado` y la sintaxis alternativa. Mira el **código fuente** de la página en el navegador (`Ctrl+U`): ¿aparece en algún sitio el párrafo "Currículo en borrador"?
6. **Investiga.** Busca en la documentación de PHP ("Converting to boolean") qué valores se convierten en `false` cuando se usan como condición. ¿Qué mostraría `if ('0') { echo 'sí'; } else { echo 'no'; }`? ¿Y `if ('0.0')`? Escribe las respuestas en un comentario del fichero y explica por qué en este REA preferimos comparar explícitamente.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 5, copia el archivo [RA3_1_DecisionesTest.php](./materiales/ejercicios-vanilla/tests/RA3_1_DecisionesTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA3_1_DecisionesTest.php
```

Si todo está bien: `OK (2 tests, 9 assertions)`.

---

**Siguiente:** [3.2. Bucles](./RA3_2_bucles.md)
