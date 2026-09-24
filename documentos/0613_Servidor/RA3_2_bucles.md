# 3.2. Bucles

## Repetir sin copiar y pegar

Recuerda cómo terminamos el listado de currículos en 2.5:

```php
<?php
  mostrarCurriculo($curriculos[0]);
  mostrarCurriculo($curriculos[1]);
  mostrarCurriculo($curriculos[2]);
?>
```

Con tres currículos es asumible; con cien, absurdo. Y, sobre todo, **no sabemos de antemano cuántos habrá**: cuando los datos vengan de una base de datos (Bloque 5), la página tendrá que funcionar igual con cero currículos que con mil. Un **bucle** repite un bloque de sentencias mientras se cumpla una condición, o una vez por cada elemento de una colección. Cada repetición se llama **iteración**.

PHP tiene cuatro bucles. Los tres primeros (`while`, `do-while` y `for`) repiten **mientras se cumpla una condición**; el cuarto (`foreach`) recorre **los elementos de un array**, y será, con diferencia, el que más usemos.

## `while`

`while` comprueba la condición **antes** de cada iteración: si vale `true`, ejecuta el bloque y vuelve a comprobarla; si vale `false`, sigue con lo que haya después del bucle. Es el bucle adecuado cuando **no sabemos cuántas veces** habrá que repetir.

Por ejemplo: un ciclo tiene 2000 horas y una alumna matriculada a tiempo parcial cursa 700 horas al año. ¿Cuántos años necesita?

```php
<?php
  $horasCiclo    = 2000;
  $horasPorAnio  = 700;
  $horasCursadas = 0;
  $anios         = 0;

  while ($horasCursadas < $horasCiclo) {
    $horasCursadas += $horasPorAnio;
    $anios++;
  }

  echo '<p>Años necesarios: ', $anios, '</p>';   // 3
?>
```

`$anios++` es el operador de **incremento**, que ya usamos sin detenernos en 2.4: suma 1 a la variable (equivale a `$anios += 1`). Su pareja, `$anios--`, resta 1.

> **Cuidado con los bucles infinitos.** Si dentro del bucle nada hace que la condición llegue a ser `false` (por ejemplo, si olvidas `$horasCursadas += $horasPorAnio;`), el bucle no termina nunca. En el servidor, PHP lo corta al superar un tiempo máximo de ejecución y la petición acaba en error: lo investigarás en el último ejercicio.

## `do-while`

`do-while` es igual, salvo que comprueba la condición **después** de cada iteración. Por tanto, el bloque se ejecuta **al menos una vez**, aunque la condición sea falsa desde el principio:

```php
<?php
  $intentos = 0;

  do {
    $intentos++;
    echo '<p>Intento ', $intentos, '</p>';
  } while ($intentos < 0);   // falsa desde el principio: aun así, se ve "Intento 1"
?>
```

En aplicaciones web se usa poco; conviene reconocerlo cuando lo encuentres.

## `for`

Cuando el bucle se basa en un **contador** que empieza en un valor, avanza de uno en uno y termina en otro, `for` reúne en una sola línea sus tres partes, separadas por `;`:

```php
for (inicialización; condición; actualización) {
    // sentencias
}
```

1. La **inicialización** se ejecuta una sola vez, al principio.
2. La **condición** se comprueba antes de cada iteración, como en `while`.
3. La **actualización** se ejecuta al final de cada iteración.

Un caso útil para nuestros formularios: generar las opciones de un desplegable con los años de promoción, en lugar de escribirlas una a una.

```php
<?php
  echo '<select name="promocion">';
  for ($anio = 2020; $anio <= 2025; $anio++) {
    echo '<option value="', $anio, '">', $anio, '</option>';
  }
  echo '</select>';
?>
```

Seis opciones, de 2020 a 2025, generadas por el servidor. Cambiar el rango es cuestión de tocar un número.

## `foreach`: recorrer un array

`foreach` recorre un array **elemento a elemento**, sin contador y sin condición: repite el bloque una vez por cada elemento y, en cada iteración, deja el elemento en la variable que indiques tras `as`. Es la respuesta al ejercicio de investigación de 2.5:

```php
<?php
  $curriculos = [
    ['alumno' => 'Ana López',    'ciclo' => 'DAW',  'video' => 'https://youtu.be/curriculo1'],
    ['alumno' => 'Marcos Pérez', 'ciclo' => 'DAM',  'video' => 'https://youtu.be/curriculo2'],
    ['alumno' => 'Laura García', 'ciclo' => 'ASIR', 'video' => 'https://youtu.be/curriculo3'],
  ];

  echo '<ul>';
  foreach ($curriculos as $curriculo) {
    echo '<li class="curriculo">', $curriculo['alumno'], ' — ', $curriculo['ciclo'], '</li>';
  }
  echo '</ul>';
?>
```

Da igual que el array tenga tres currículos o trescientos: el código es el mismo. Si el array está vacío, el bloque simplemente no se ejecuta ninguna vez.

Con un array **asociativo** podemos recuperar, además del valor, su **clave**, con la forma `clave => valor`:

```php
<?php
  $curriculo = ['alumno' => 'Ana López', 'ciclo' => 'DAW', 'video' => 'https://youtu.be/curriculo1'];

  echo '<dl>';
  foreach ($curriculo as $campo => $valor) {
    echo '<dt>', $campo, '</dt><dd>', $valor, '</dd>';
  }
  echo '</dl>';
?>
```

En un array indexado, la clave es la posición (`0`, `1`, `2`...). Con esto nos basta para recorrer; en 3.3 veremos los arrays en profundidad.

> **¿`for` o `foreach` para un array?** Podrías recorrer `$curriculos` con `for ($i = 0; $i < count($curriculos); $i++)` y `$curriculos[$i]`, pero `foreach` es más corto, no necesita contador y funciona también con arrays asociativos, que no tienen posiciones. Para recorrer arrays, usa siempre `foreach`.

## `break` y `continue`

Dos sentencias alteran el recorrido normal de cualquier bucle:

- **`continue`** salta **el resto de la iteración actual** y pasa a la siguiente.
- **`break`** **termina el bucle** por completo y sigue con lo que haya después.

```php
<?php
  // continue: listar solo los currículos que tienen vídeo
  foreach ($curriculos as $curriculo) {
    if ($curriculo['video'] === '') {
      continue;
    }
    echo '<li>', $curriculo['alumno'], '</li>';
  }

  // break: parar en cuanto encontramos el primer currículo de DAM
  foreach ($curriculos as $curriculo) {
    if ($curriculo['ciclo'] === 'DAM') {
      echo '<p>Primer currículo de DAM: ', $curriculo['alumno'], '</p>';
      break;
    }
  }
?>
```

Con `break` evitamos seguir recorriendo cuando ya tenemos lo que buscábamos. Es la primera vez que combinamos un bucle y una decisión: lo haremos constantemente.

## Bucles embebidos en el HTML: la sintaxis alternativa

Como vimos en 3.1 con `if (): ... endif;`, los bucles también tienen **sintaxis alternativa**: `foreach (...): ... endforeach;` (y `for:`/`endfor;`, `while:`/`endwhile;`). Permite repetir un **fragmento de HTML** sin meterlo en un `echo`:

```php
<table>
  <tr><th>Alumno</th><th>Ciclo</th></tr>
  <?php foreach ($curriculos as $curriculo): ?>
    <tr>
      <td><?= $curriculo['alumno'] ?></td>
      <td><?= $curriculo['ciclo'] ?></td>
    </tr>
  <?php endforeach; ?>
</table>
```

Aquí aparece además la **etiqueta corta de salida** `<?= ... ?>`, que equivale a `<?php echo ...; ?>`: pensada justo para insertar un valor en medio del HTML. Con ella y la sintaxis alternativa, la página se lee como HTML con "huecos" que rellena el servidor. En el Bloque 4, _Blade_ lo hará con `@foreach ... @endforeach` y `{{ $curriculo['alumno'] }}`: de nuevo, la misma idea que aquí escribimos a mano.

> **Aviso.** `<?= ... ?>` escribe el valor **tal cual** en el HTML. Mientras los datos los escribamos nosotros no hay problema, pero en cuanto vengan del usuario (3.6) habrá que **escaparlos** para evitar ataques. _Blade_, con `{{ }}`, lo hace automáticamente.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php`; si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

## Ejercicios

Todos los ejercicios van, uno debajo de otro, en el mismo script `vanilla_php/public/RA3_bucles.php`. Compruébalo en `http://localhost/RA3_bucles.php` después de cada uno.

1. **`for`.** Crea el script con el desplegable de años de promoción (2020 a 2025) de la sección [`for`](#for).
2. **`while`.** Añade el cálculo de los años necesarios a tiempo parcial de la sección [`while`](#while). Antes de recargar, calcula a mano cuántas iteraciones hará el bucle y anótalo en un comentario.
3. **`foreach`.** Añade el array `$curriculos` con los tres currículos de 2.5 y el listado con `foreach` (los `<li class="curriculo">`) de la sección [`foreach`: recorrer un array](#foreach-recorrer-un-array).
4. **`foreach` con clave.** Añade el ejemplo de la lista de definiciones (`<dl>`) que muestra cada campo de un currículo con su valor.
5. **`continue` y sintaxis alternativa.** Añade al final este array, con un cuarto currículo **sin vídeo**, y una tabla que muestre solo los currículos con vídeo usando la sintaxis alternativa y `continue`:

    ```php
    <?php
      $curriculosPublicados = [
        ['alumno' => 'Ana López',    'ciclo' => 'DAW',  'video' => 'https://youtu.be/curriculo1'],
        ['alumno' => 'Marcos Pérez', 'ciclo' => 'DAM',  'video' => 'https://youtu.be/curriculo2'],
        ['alumno' => 'Pablo Ruiz',   'ciclo' => 'DAW',  'video' => ''],
        ['alumno' => 'Laura García', 'ciclo' => 'ASIR', 'video' => 'https://youtu.be/curriculo3'],
      ];
    ?>
    <table>
      <tr><th>Alumno</th><th>Vídeo</th></tr>
      <?php foreach ($curriculosPublicados as $curriculo): ?>
        <?php if ($curriculo['video'] === '') { continue; } ?>
        <tr class="con-video">
          <td><?= $curriculo['alumno'] ?></td>
          <td><a href="<?= $curriculo['video'] ?>">vídeo</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
    ```

    Mira el código fuente de la página (`Ctrl+U`): ¿cuántas filas `<tr class="con-video">` ha generado el servidor? ¿Aparece Pablo Ruiz?
6. **Investiga.** Busca qué es la directiva `max_execution_time` y averigua su valor en tu entorno con `echo ini_get('max_execution_time');` (en un script aparte, que puedes borrar después). ¿Qué le ocurriría a una petición cuyo script entra en un bucle infinito? ¿Por qué el servidor necesita ese límite? Escribe las respuestas en un comentario del fichero. **No** dejes un bucle infinito en `RA3_bucles.php`.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 5, copia el archivo [RA3_2_BuclesTest.php](./materiales/ejercicios-vanilla/tests/RA3_2_BuclesTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA3_2_BuclesTest.php
```

Si todo está bien: `OK (2 tests, 9 assertions)`.

---

**Siguiente:** 3.3. Arrays y tipos compuestos
