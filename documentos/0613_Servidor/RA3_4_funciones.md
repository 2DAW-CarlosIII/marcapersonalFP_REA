# 3.4. Funciones

## Qué es una función

En 2.4 adelantamos la sintaxis mínima de una función —un bloque de código con nombre que solo se ejecuta cuando se llama— para entender el ámbito de las variables, y en 2.5 escribimos `mostrarCurriculo(array $curriculo)` para no repetir el mismo `echo` tres veces. Ahora las formalizamos. Una función sirve para:

- **Reutilizar** código: se escribe una vez y se llama tantas veces como haga falta.
- **Dar nombre** a una operación: `horasRestantes(2000, 1350)` se entiende mejor que la resta suelta.
- **Aislar**: como vimos en 2.4, cada función tiene su propio ámbito; lo que ocurre dentro no interfiere con el resto del script.

## Parámetros y valor de retorno

Una función recibe datos a través de sus **parámetros** y entrega un resultado con **`return`**:

```php
<?php
  function horasRestantes(int $horasTotales, int $horasCursadas): int
  {
      return $horasTotales - $horasCursadas;
  }

  $pendientes = horasRestantes(2000, 1350);   // 650
  echo '<p>Horas restantes: ', $pendientes, '</p>';
?>
```

Al llamarla, los **argumentos** (`2000`, `1350`) se asignan a los parámetros **por orden**. `return` termina la función y devuelve el valor a quien la llamó, que puede guardarlo en una variable, mostrarlo o pasárselo a otra función.

### Devolver en vez de mostrar

Compara con `mostrarCurriculo()` de 2.5, que hacía `echo` dentro. Una función que **devuelve** su resultado, en lugar de mostrarlo, es mucho más útil: quien la llama decide qué hacer con él (mostrarlo en un `<li>`, en una tabla, guardarlo, compararlo...), y además se puede **comprobar automáticamente**, porque un test puede llamarla y examinar lo que devuelve. A partir de ahora, salvo que su propósito sea precisamente generar salida, nuestras funciones **devolverán** valores.

### Valores por defecto y argumentos con nombre

Un parámetro puede tener un **valor por defecto**, que se usa si no se pasa ese argumento. Los parámetros con valor por defecto van siempre al final:

```php
<?php
  function titulacion(string $grado = 'Superior'): string
  {
      return match ($grado) {
          'Superior' => 'Técnico Superior',
          'Medio'    => 'Técnico',
          default    => 'Titulación desconocida',
      };
  }

  echo titulacion();          // Técnico Superior
  echo titulacion('Medio');   // Técnico
?>
```

Desde PHP 8 también se pueden pasar **argumentos con nombre**, indicando a qué parámetro va cada valor, sin depender del orden: `horasRestantes(horasCursadas: 1350, horasTotales: 2000)`. Son útiles cuando una función tiene muchos parámetros opcionales.

### Paso por valor y por referencia

Los argumentos se pasan **por valor**: la función recibe una **copia**. Es lo mismo que vimos en 3.3 al asignar un array: si la función modifica un array recibido, el original no cambia. Si anteponemos `&` al parámetro, se pasa **por referencia** y la función trabaja sobre la variable original. Así está declarada `sort(array &$array)`: por eso, como vimos en 3.3, `sort()` modifica el array que le pasas en vez de devolver uno nuevo. En nuestras funciones **no usaremos referencias**: preferimos devolver el resultado.

## Tipos declarados

Fíjate en `int $horasTotales` y en el `: int` tras los paréntesis: son **tipos declarados** para los parámetros y para el valor de retorno. En 2.3 vimos que PHP tiene tipado dinámico y que las variables sueltas no se pueden tipar; en 2.5 declaramos por primera vez un tipo (`array $curriculo`). Los tipos disponibles son los de 2.3 y 3.3, más algunas formas especiales:

| Declaración | Significa |
|---|---|
| `int`, `float`, `string`, `bool`, `array` | Ese tipo exactamente |
| `?string` | Tipo **nullable**: un `string` o `null` |
| `int\|float` | Tipo **unión**: cualquiera de los dos |
| `: void` | (solo retorno) La función no devuelve nada |
| `mixed` | Cualquier tipo (evítalo si puedes concretar) |

Un ejemplo con tipo nullable: un currículo puede no tener vídeo todavía.

```php
<?php
  function enlaceVideo(?string $video): string
  {
      if ($video === null || $video === '') {
          return 'Sin vídeo';
      }
      return '<a href="' . $video . '">vídeo</a>';
  }
?>
```

### ¿Por qué declarar tipos?

- **Los errores aparecen antes y en su sitio**: si alguien llama a `horasRestantes()` con un array, PHP falla en la llamada, en vez de producir un resultado absurdo varias líneas después.
- **La firma documenta la función**: con leer `enlaceVideo(?string $video): string` sabes qué recibe y qué devuelve, sin leer su código.
- **Las herramientas lo aprovechan**: los editores autocompletan y avisan de errores antes de ejecutar, los analizadores estáticos (como _PHPStan_) detectan fallos en todo el proyecto y los **asistentes de programación con IA** generan código más correcto cuando los tipos les dicen qué espera cada función.

### Modo estricto: `declare(strict_types=1)`

Por defecto, PHP **convierte** los argumentos cuando puede (modo **coercitivo**): `horasRestantes('2000', 1350)` funciona, porque el string `'2000'` se convierte en el int `2000`. Es el mismo problema que vimos con `==`: conversiones silenciosas que esconden errores. Para desactivarlas, se escribe como **primera sentencia** del fichero:

```php
<?php
declare(strict_types=1);
```

Con esa línea, `horasRestantes('2000', 1350)` lanza un error `TypeError`. Hay un matiz importante: el modo estricto afecta a las **llamadas que se hacen desde el fichero** que lo declara, no a las funciones definidas en él. Por eso, en este REA, **todos los ficheros PHP que escribamos a partir de ahora empezarán con `declare(strict_types=1);`**: el de las funciones y también las páginas que las llaman. Comprobarás el matiz en el último ejercicio.

## Funciones anónimas y funciones flecha

Algunas funciones de PHP reciben **otra función** como argumento (un _callback_) y la aplican a cada elemento de un array. Son las que dejamos pendientes en 3.3:

| Función | Qué hace con el callback |
|---|---|
| `array_map($callback, $array)` | Lo aplica a cada elemento y devuelve un array con los resultados |
| `array_filter($array, $callback)` | Devuelve solo los elementos para los que el callback devuelve `true` |
| `usort($array, $callback)` | Ordena el array (por referencia) comparando pares de elementos con el callback |

Para no tener que definir con nombre una función que solo se usa una vez, PHP permite escribirla en el mismo lugar donde se pasa. La forma más breve es la **función flecha** (`fn`), cuyo cuerpo es una única expresión que se devuelve automáticamente:

```php
<?php
  // array_map: de cada currículo, solo el nombre del alumno
  $alumnos = array_map(fn(array $curriculo): string => $curriculo['alumno'], $curriculos);
  // ['Ana López', 'Marcos Pérez', 'Laura García']
?>
```

Una función flecha **ve automáticamente** las variables del ámbito donde se escribe, como `$codCiclo` aquí:

```php
<?php
  $codCiclo = 'DAW';
  $deDaw = array_filter(
      $curriculos,
      fn(array $curriculo): bool => $curriculo['ciclo'] === $codCiclo
  );
?>
```

Ojo: `array_filter()` **conserva las claves** originales; si filtras `[0 => ..., 1 => ..., 2 => ...]` y solo queda el elemento 2, el resultado es `[2 => ...]`, no `[0 => ...]`. Para reindexar desde `0`, envuélvelo en `array_values()` (3.3).

Para ordenar con `usort()`, el callback recibe dos elementos y devuelve un número negativo, cero o positivo según cuál deba ir antes. Es exactamente lo que hace el operador **`<=>`** (_spaceship_) que investigaste en 2.3:

```php
<?php
  usort($curriculos, fn(array $a, array $b): int => $a['alumno'] <=> $b['alumno']);
?>
```

Cuando el cuerpo necesita varias sentencias, se usa una **función anónima** clásica, `function (...) { ... }`. A diferencia de la flecha, **no ve** las variables de fuera (recuerda el ámbito de 2.4) salvo que las importes con `use`:

```php
<?php
  $deDaw = array_filter($curriculos, function (array $curriculo) use ($codCiclo): bool {
      return $curriculo['ciclo'] === $codCiclo;
  });
?>
```

## Organizar las funciones en ficheros

Si cada página define sus propias funciones, acabaremos copiando las mismas en varios sitios. Lo habitual es reunirlas en un fichero aparte y **cargarlo** desde cada página que las necesite:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/RA3_funciones.php';
?>
```

- **`require_once`** incluye el fichero y ejecuta su contenido, pero **solo la primera vez**: si se vuelve a pedir, no hace nada. Así evitamos el error de definir dos veces la misma función.
- Existen también `require` (sin la comprobación de "una sola vez"), `include` e `include_once`. La diferencia es qué pasa si el fichero no existe: `require` detiene el script con un error; `include` solo da un aviso y continúa. Para cargar código del que depende la página, usaremos `require_once`.
- **`__DIR__`** es una constante que contiene la carpeta del fichero que se está ejecutando. Construir la ruta a partir de ella evita depender de desde dónde se lance el script.

Dos reglas para ese fichero de funciones:

1. **Va fuera de `public/`**, en una carpeta **`vanilla_php/src/`**. _nginx_ solo sirve lo que hay en `public/` (2.1): el código de `src/` se puede cargar desde PHP, pero nadie puede pedirlo desde el navegador.
2. **Solo define, no ejecuta.** No contiene `echo` ni HTML: solo `declare`, funciones (y, en 3.5, clases). Cargarlo no produce ningún efecto; es la página quien decide qué llamar. Gracias a eso, un test puede cargarlo también y llamar a las funciones directamente, sin petición HTTP.

> **Puente al Bloque 4.** En un proyecto _Laravel_ no escribirás `require_once`: _Composer_ genera un **cargador automático** (_autoload_) que incluye cada fichero la primera vez que se usa lo que define. Es la versión automatizada de lo que aquí hacemos a mano.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php`; si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo). Como novedad, necesitas la carpeta `src/` junto a `public/` y `tests/`. Desde `~/Documentos/laravel/`:

```bash
mkdir -p vanilla_php/src
```

```
vanilla_php/
├── public/   ← lo que sirve nginx (páginas)
├── src/      ← funciones y clases (no accesible desde el navegador)
└── tests/
```

## Ejercicios

Los ejercicios 1 a 5 van en el fichero de funciones `vanilla_php/src/RA3_funciones.php`, que debe empezar por `<?php` y `declare(strict_types=1);` y **no debe producir ninguna salida**. El ejercicio 6 es la página que las usa.

1. **Parámetros, retorno y valor por defecto.** Escribe en `src/RA3_funciones.php` las funciones `horasRestantes()` y `titulacion()` de este apartado, con sus tipos declarados.
2. **Tipo nullable.** Añade `enlaceVideo(?string $video): string`.
3. **`array_map`.** Añade una función que devuelva los nombres de los alumnos usando una función flecha:

    ```php
    function nombresAlumnos(array $curriculos): array
    {
        return array_map(fn(array $curriculo): string => $curriculo['alumno'], $curriculos);
    }
    ```

4. **`array_filter`.** Añade una función que devuelva solo los currículos de un ciclo, **reindexados desde 0**:

    ```php
    function curriculosDeCiclo(array $curriculos, string $codCiclo): array
    {
        return array_values(array_filter(
            $curriculos,
            fn(array $curriculo): bool => $curriculo['ciclo'] === $codCiclo
        ));
    }
    ```

5. **`usort`.** Añade una función que devuelva los currículos ordenados por alumno. Como el array se recibe **por valor**, `usort()` ordena la copia local y el array de quien llama no cambia:

    ```php
    function ordenarPorAlumno(array $curriculos): array
    {
        usort($curriculos, fn(array $a, array $b): int => $a['alumno'] <=> $b['alumno']);
        return $curriculos;
    }
    ```

6. **La página.** Crea `vanilla_php/public/RA3_funciones.php`, que carga las funciones y las usa con sintaxis alternativa y `<?= ?>` (3.2). Compruébala en `http://localhost/RA3_funciones.php`:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA3_funciones.php';

    $curriculos = [
        ['alumno' => 'Marcos Pérez', 'ciclo' => 'DAM',  'video' => 'https://youtu.be/curriculo2'],
        ['alumno' => 'Laura García', 'ciclo' => 'ASIR', 'video' => null],
        ['alumno' => 'Ana López',    'ciclo' => 'DAW',  'video' => 'https://youtu.be/curriculo1'],
    ];
    ?>
    <p>Horas restantes: <?= horasRestantes(2000, 1350) ?></p>
    <p>Titulación: <?= titulacion() ?></p>
    <ul>
      <?php foreach (ordenarPorAlumno($curriculos) as $curriculo): ?>
        <li class="curriculo"><?= $curriculo['alumno'] ?> — <?= enlaceVideo($curriculo['video']) ?></li>
      <?php endforeach; ?>
    </ul>
    ```

    Fíjate en que `<?php` es lo **primero** del fichero: `declare` tiene que ser la primera sentencia, y no puede haber HTML (ni siquiera una línea en blanco) antes.
7. **Investiga.** En la página, añade temporalmente `<?= horasRestantes('2000', 1350) ?>` y recarga: ¿qué error aparece? Ahora quita (también temporalmente) la línea `declare(strict_types=1);` **de la página**, dejándola en `src/RA3_funciones.php`, y recarga otra vez. ¿Qué ocurre y por qué? Busca en la documentación de PHP ("Strict typing") a qué llamadas afecta `strict_types` y anota la conclusión en un comentario de la página. Deja la página como estaba al terminar.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 6, copia el archivo [RA3_4_FuncionesTest.php](./materiales/ejercicios-vanilla/tests/RA3_4_FuncionesTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA3_4_FuncionesTest.php
```

A diferencia de los tests anteriores, este **no** pide la página a _nginx_ para comprobar las funciones: carga `src/RA3_funciones.php` con `require_once` —igual que hace tu página— y llama a cada función directamente, comprobando lo que devuelve y los tipos que declara. Solo el último test hace una petición HTTP, para comprobar la página del ejercicio 6.

Si todo está bien: `OK (8 tests, 17 assertions)`.

---

**Siguiente:** 3.5. Programación Orientada a Objetos en PHP
