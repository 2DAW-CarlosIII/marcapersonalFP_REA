# 2.3. Tipos de datos, variables y constantes, operadores

## Tipos de datos

PHP tiene **tipado dinámico**: no declaras el tipo de una variable, lo determina automáticamente el valor que le asignas. Los tipos **escalares** (un único valor) que usaremos en este bloque son:

| Tipo | Ejemplo | Significa |
|---|---|---|
| `int` (entero) | `2000` | Un número sin decimales |
| `float` (o `double`) | `8.75` | Un número con decimales |
| `string` (cadena) | `"DAW"` | Texto |
| `bool` (booleano) | `true` / `false` | Verdadero o falso |

A estos se suman dos tipos **compuestos** que veremos con detalle en el Bloque 3: `array` (colecciones de valores) y `object` (instancias de una clase). Y un tipo especial, `null`, que representa "ausencia de valor".

Para inspeccionar el tipo y el valor exacto de una variable durante el desarrollo, PHP ofrece `var_dump()`:

```php
<?php
  $horas = 2000;
  var_dump($horas);   // int(2000)
?>
```

`var_dump()` es una herramienta de depuración —no la usarás en el HTML final que ve un usuario real—, pero es muy útil mientras aprendes a distinguir un `"2000"` (string) de un `2000` (int).

## Variables

Ya usamos variables de forma intuitiva en el apartado anterior; formalicemos las reglas:

- El nombre empieza siempre por `$`, seguido de una letra o `_`, y continúa con letras, dígitos o `_` (`$codCiclo`, `$horas2024`, `$_temp`).
- Son sensibles a mayúsculas/minúsculas (`$codCiclo` ≠ `$CodCiclo`), como ya vimos en 2.2.
- Al ser de tipado dinámico, **una misma variable puede cambiar de tipo** a lo largo del script si le reasignas un valor de otro tipo:

```php
<?php
  $dato = "DAW";   // ahora mismo es un string
  $dato = 10;      // ahora es un int: no hay ningún error
?>
```

Esto es cómodo, pero también es la causa de bastantes errores sutiles: en cuanto veamos los operadores de comparación, entenderás por qué conviene tenerlo presente.

## Constantes

Una **constante** es un valor con nombre que, a diferencia de una variable, **no puede cambiar** una vez definido. No lleva `$` y, por convención, se escribe en `MAYÚSCULAS_CON_GUIONES_BAJOS`. Hay dos formas de definirla:

```php
<?php
  define('NOMBRE_CENTRO', 'CIFP Carlos III');   // función: válida en cualquier punto del script
  const CODIGO_CENTRO = '30012345';         // palabra clave: se resuelve al cargar el script

  echo '<p>Centro: ', NOMBRE_CENTRO, ' (código ', CODIGO_CENTRO, ')</p>';
?>
```

Para nuestros scripts sueltos ambas formas son equivalentes; cuando lleguemos a la Programación Orientada a Objetos (Bloque 3), verás que `const` puede usarse también dentro de una clase y `define()` no.

## Operadores

Un **operador** combina uno o varios valores para producir otro. Los que más usaremos:

**Aritméticos**

| Operador | Significado |
|---|---|
| `+` `-` `*` `/` | suma, resta, multiplicación, división |
| `%` | resto de la división entera (módulo) |

**De asignación**

`=` asigna; se puede combinar con un operador aritmético o con la concatenación para "modificar y reasignar" en un solo paso: `+=`, `-=`, `*=`, `/=`, `.=`. Por ejemplo, `$horas += 20;` equivale a `$horas = $horas + 20;`.

**De comparación**

| Operador | Compara |
|---|---|
| `==` | el **valor**, convirtiendo tipos si hace falta |
| `===` | el **valor y el tipo**, sin convertir nada |
| `!=` / `!==` | las versiones negadas de las anteriores |
| `<` `>` `<=` `>=` | orden |

La diferencia entre `==` y `===` importa precisamente por el tipado dinámico que acabamos de ver: `"10" == 10` es `true` (PHP convierte el string a número antes de comparar), pero `"10" === 10` es `false` (los tipos no coinciden). En este REA usaremos **siempre `===`** salvo que tengamos una razón concreta para lo contrario: es más predecible y evita errores difíciles de rastrear.

**Lógicos**

`&&` (y), `||` (o), `!` (no) combinan condiciones booleanas. Los usaremos sobre todo a partir del Bloque 3, cuando aparezcan las tomas de decisión (`if`).

## Ejercicios

1. **Tipos y variables.** Crea `vanilla_php/public/RA2_tiposVariablesOperadores.php` con estas variables y muéstralas con `echo` y con `var_dump()`:

    ```php
    <?php
      $codCiclo  = "DAW";     // string
      $horas     = 2000;      // int
      $notaCorte = 8.75;      // float
      $activo    = true;      // bool

      echo '<p>', $codCiclo, ' — ', $horas, ' horas — nota de corte ', $notaCorte, '</p>';

      echo '<pre>';
      var_dump($codCiclo, $horas, $notaCorte, $activo);
      echo '</pre>';
    ?>
    ```

2. **Constantes.** Debajo, añade las dos constantes de esta sección (`NOMBRE_CENTRO` con `define()`, `CODIGO_CENTRO` con `const`) y su `echo`.
3. **Operadores.** Añade, también debajo:

    ```php
    <?php
      $horasTotales  = 2000;
      $horasCursadas = 1350;
      echo '<p>Horas restantes: ', $horasTotales - $horasCursadas, '</p>';

      $codCiclo = "10";   // string
      $idCiclo  = 10;     // int

      echo '<pre>';
      var_dump($codCiclo == $idCiclo);    // ¿qué esperas?
      var_dump($codCiclo === $idCiclo);   // ¿y aquí?
      echo '</pre>';
    ?>
    ```

    Antes de ejecutarlo, anota en un comentario qué resultado esperas para cada `var_dump`. Después compruébalo en el navegador.
4. **Investiga.** Busca qué hace el operador `<=>` (_spaceship_) y con qué lo compararías de lo visto en esta sección. Escribe la respuesta en un comentario del fichero.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1, 2 y 3, copia el archivo [RA2_3_TiposVariablesOperadoresTest.php](./materiales/ejercicios-vanilla/tests/RA2_3_TiposVariablesOperadoresTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA2_3_TiposVariablesOperadoresTest.php
```

Si todo está bien: `OK (2 tests, 9 assertions)`.

---

**Siguiente:** [2.4. Ámbitos de las variables](./RA2_4_ambitos.md)
