# 3.3. Arrays y tipos compuestos

**Presentación de apoyo** (_RevealJS_): [RA3_3_arrays_slides.html](./materiales/slides/RA3_3_arrays_slides.html)

## Tipos escalares y tipos compuestos

En 2.3 trabajamos con tipos **escalares** (`int`, `float`, `string`, `bool`): cada variable guarda **un único valor**. Allí anunciamos dos tipos **compuestos**, capaces de agrupar varios valores bajo un mismo nombre:

- **`array`**: una colección ordenada de pares **clave → valor**. Es el protagonista de este apartado.
- **`object`**: una instancia de una clase, que agrupa datos y el comportamiento que opera sobre ellos. Lo veremos en 3.5.

En 2.5 adelantamos lo mínimo de arrays para construir el listado de currículos, y en 3.2 aprendimos a recorrerlos con `foreach`. Ahora los formalizamos.

## Crear y modificar arrays

Un array se crea con corchetes. Cada elemento tiene una **clave**, que puede ser un `int` o un `string`:

```php
<?php
  // Indexado: PHP asigna las claves 0, 1, 2... automáticamente
  $ciclos = ['DAW', 'DAM'];

  // Asociativo: las claves las elegimos nosotros
  $ciclo = ['codCiclo' => 'DAW', 'grado' => 'Superior', 'horas' => 2000];

  // Array vacío, para rellenarlo después
  $modulos = [];
?>
```

> En código antiguo verás también la forma `array('DAW', 'DAM')`. Es equivalente, pero en este REA usaremos siempre los corchetes.

Una vez creado, un array se modifica elemento a elemento:

```php
<?php
  $ciclos[] = 'ASIR';          // añade al final, con la siguiente clave libre (2)
  $ciclo['horas'] = 2000;      // modifica el valor de una clave existente
  $ciclo['familia_id'] = 1;    // si la clave no existe, la crea
  unset($ciclo['familia_id']); // elimina un elemento

  echo count($ciclos);         // 3
?>
```

Para **inspeccionar** un array mientras desarrollas, `var_dump()` (2.3) sigue sirviendo, pero `print_r()` da una salida más compacta. Rodéalo de `<pre>` para que el navegador respete los saltos de línea:

```php
<?php
  echo '<pre>';
  print_r($ciclo);
  echo '</pre>';
?>
```

## Arrays multidimensionales

El valor de un elemento puede ser, a su vez, otro array. Así representamos datos con estructura, como un ciclo con sus módulos:

```php
<?php
  $ciclo = [
    'codCiclo' => 'DAW',
    'nombre'   => 'Desarrollo de Aplicaciones Web',
    'modulos'  => [
      '0612' => 'Desarrollo web en entorno cliente',
      '0613' => 'Desarrollo web en entorno servidor',
      '0614' => 'Despliegue de aplicaciones web',
      '0615' => 'Diseño de interfaces web',
    ],
  ];

  echo $ciclo['modulos']['0613'];   // Desarrollo web en entorno servidor
?>
```

Cada par de corchetes baja un nivel: `$ciclo['modulos']` es el array de módulos y `['0613']` elige uno de ellos. Para recorrer un nivel interior, basta con pasárselo a `foreach`:

```php
<?php
  echo '<ul>';
  foreach ($ciclo['modulos'] as $codigo => $nombre) {
    echo '<li class="modulo">', $codigo, ' — ', $nombre, '</li>';
  }
  echo '</ul>';
?>
```

El array `$curriculos` de 2.5 también era multidimensional: un array indexado cuyos elementos son arrays asociativos. Es la forma más habitual de representar una **lista de registros**, y la que obtendremos cuando leamos filas de una base de datos (Bloque 5).

## Funciones habituales de arrays

PHP incluye decenas de funciones para trabajar con arrays. Estas son las que más usaremos.

### Buscar

```php
<?php
  $ciclos = ['DAW', 'DAM', 'ASIR'];

  in_array('DAM', $ciclos, true);            // true: ¿existe ese valor?
  array_search('ASIR', $ciclos, true);       // 2: ¿en qué clave está? (false si no está)
  array_key_exists('grado', $ciclo);         // ¿existe esa clave?
?>
```

Fíjate en el tercer argumento, `true`, de `in_array()` y `array_search()`: activa la **comparación estricta** (`===`). Sin él comparan con `==`, así que, coherentes con lo acordado en 2.3, lo escribiremos siempre.

> **`isset()` frente a `array_key_exists()`.** También puedes comprobar si una clave existe con `isset($ciclo['grado'])`, pero `isset()` devuelve `false` si la clave existe **y su valor es `null`**. Cuando esa diferencia no importa, `isset()` es lo más habitual (y el operador `??` de 3.1 se comporta igual que `isset()`).

### Extraer y combinar

```php
<?php
  array_keys($ciclo);                          // las claves: ['codCiclo', 'nombre', 'modulos']
  array_values($ciclo['modulos']);             // los valores, reindexados desde 0
  array_column($curriculos, 'alumno');         // de una lista de registros, un solo campo:
                                               //   ['Ana López', 'Marcos Pérez', 'Laura García']
  array_merge(['DAW', 'DAM'], ['ASIR', 'SMR']);  // une arrays: ['DAW', 'DAM', 'ASIR', 'SMR']
?>
```

`array_column()` merece atención: saca una "columna" de una lista de registros. Es justo lo que necesitaremos para, por ejemplo, rellenar un desplegable con los nombres de los ciclos.

### Convertir entre arrays y strings

```php
<?php
  echo implode(', ', ['DAW', 'DAM', 'ASIR']);   // "DAW, DAM, ASIR"
  $ciclos = explode(',', 'DAW,DAM,ASIR');       // ['DAW', 'DAM', 'ASIR']
?>
```

`implode()` une los elementos con un separador; `explode()` hace lo contrario: parte un string por un separador.

### Ordenar

```php
<?php
  $ciclos = ['DAW', 'ASIR', 'DAM'];
  sort($ciclos);    // ['ASIR', 'DAM', 'DAW']
?>
```

Ojo: `sort()` **no devuelve** el array ordenado, sino que **modifica** el que le pasas (y devuelve `true`). Existen variantes para ordenar al revés (`rsort()`), para ordenar un array asociativo por valor conservando las claves (`asort()`) o por clave (`ksort()`); las compararás en el ejercicio de investigación.

Hay otras funciones de array muy potentes —`array_map()`, `array_filter()`, `usort()`...— que reciben **otra función** como argumento. Las veremos en 3.4, cuando sepamos escribir funciones con más soltura.

## Desestructuración

La **desestructuración** extrae varios elementos de un array a variables sueltas en una sola sentencia. Con un array indexado, por posición:

```php
<?php
  [$primero, $segundo] = ['DAW', 'DAM', 'ASIR'];
  echo $primero;   // DAW
?>
```

Y con un array asociativo, por clave:

```php
<?php
  ['alumno' => $alumno, 'ciclo' => $codCiclo] = $curriculos[1];
  echo '<p>Segundo currículo: ', $alumno, ' (', $codCiclo, ')</p>';
?>
```

También funciona en la cabecera de un `foreach`, lo que deja el cuerpo del bucle más limpio: `foreach ($curriculos as ['alumno' => $alumno, 'ciclo' => $codCiclo]) { ... }`.

## Los arrays se copian

Un detalle con consecuencias: al asignar un array a otra variable (o al pasarlo a una función), PHP hace una **copia**. Modificar la copia no afecta al original:

```php
<?php
  $copia = $curriculos;
  $copia[] = ['alumno' => 'Pablo Ruiz', 'ciclo' => 'DAW', 'video' => ''];

  echo count($curriculos);   // 3: el original no ha cambiado
  echo count($copia);        // 4
?>
```

Lo recordaremos en 3.5: con los **objetos** no ocurre lo mismo.

> **Puente al Bloque 4.** _Laravel_ envuelve los arrays en **colecciones** (`Collection`), con métodos encadenables: `collect($curriculos)->pluck('alumno')->sort()` hace lo mismo que `array_column()` + `sort()`. Por debajo, sigue habiendo un array de PHP como los de este apartado.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php`; si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

## Ejercicios

Todos los ejercicios van, uno debajo de otro, en el mismo script `vanilla_php/public/RA3_arrays.php`. Compruébalo en `http://localhost/RA3_arrays.php` después de cada uno.

1. **Crear, añadir y buscar.** Crea el script con este código:

    ```php
    <?php
      $ciclos = ['DAW', 'DAM'];
      $ciclos[] = 'ASIR';

      echo '<p>Número de ciclos: ', count($ciclos), '</p>';
      echo '<p>', in_array('SMR', $ciclos, true) ? 'SMR ofertado' : 'SMR no ofertado', '</p>';
    ?>
    ```

2. **Multidimensional.** Añade el array `$ciclo` con sus cuatro módulos y el listado de módulos (`<li class="modulo">`) de la sección [Arrays multidimensionales](#arrays-multidimensionales). Muestra también el array completo con `print_r()` dentro de un `<pre>`.
3. **Extraer, ordenar y unir.** Añade el array `$curriculos` con los tres currículos de 2.5 y, debajo:

    ```php
    <?php
      $alumnos = array_column($curriculos, 'alumno');
      sort($alumnos);
      echo '<p>Alumnos: ', implode(', ', $alumnos), '</p>';
    ?>
    ```

    Antes de recargar, anota en un comentario en qué orden esperas que salgan.
4. **Desestructuración.** Añade el ejemplo de la sección [Desestructuración](#desestructuración) que muestra "Segundo currículo: ..." a partir de `$curriculos[1]`.
5. **Copia.** Termina con el ejemplo de la sección [Los arrays se copian](#los-arrays-se-copian), pero mostrando ambos resultados en un solo párrafo:

    ```php
    <?php
      echo '<p>Original: ', count($curriculos), ' · Copia: ', count($copia), '</p>';
    ?>
    ```

6. **Investiga.** Parte de este array con la nota media de cada alumno: `$notas = ['Marcos Pérez' => 7.25, 'Laura García' => 9, 'Ana López' => 8.5];`. Aplica `sort()`, `asort()` y `ksort()`, cada una sobre una copia distinta (recuerda: asignar un array hace una copia), y muestra cada resultado con `print_r()`. ¿Qué criterio sigue cada una? ¿Qué le ocurre a las claves con `sort()`, y por qué eso es un problema en este caso? Escribe las conclusiones en un comentario del fichero.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 5, copia el archivo [RA3_3_ArraysTest.php](./materiales/ejercicios-vanilla/tests/RA3_3_ArraysTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA3_3_ArraysTest.php
```

Si todo está bien: `OK (2 tests, 9 assertions)`.

---

**Siguiente:** [3.4. Funciones](./RA3_4_funciones.md)
