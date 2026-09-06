# 2.5. Proyecto: listado de currículos desde un array

Cerramos el Bloque 2 con un pequeño proyecto que integra lo visto hasta ahora —salida con `echo`, tipos y constantes, funciones y ámbito— en una primera pieza real de _marcapersonalFP v0_: una página que lista los currículos de varios alumnos.

## Antes de nada: arrays, en avance

Para listar varios currículos necesitamos guardarlos en **una sola estructura**, no en variables sueltas. Para eso sirven los **arrays**: una variable que contiene una colección de valores. Los formalizaremos —recorrerlos con bucles, arrays multidimensionales, funciones de array...— en el Bloque 3 (CE c); aquí solo necesitamos lo mínimo para guardar y leer datos.

Un array **indexado** guarda valores en orden, accesibles por posición (empezando en `0`):

```php
<?php
  $ciclos = ['DAW', 'DAM', 'ASIR'];
  echo $ciclos[0];   // DAW
  echo count($ciclos);  // 3
?>
```

Un array **asociativo** usa claves con nombre en vez de posiciones —ideal para agrupar los datos de un mismo currículo—:

```php
<?php
  $curriculo = ['alumno' => 'Ana López', 'ciclo' => 'DAW'];
  echo $curriculo['alumno'];   // Ana López
?>
```

Y, combinando ambos, un array indexado **de** arrays asociativos nos da justo lo que necesitamos: una lista de currículos, cada uno con sus propios campos.

```php
<?php
  $curriculos = [
    ['alumno' => 'Ana López',    'ciclo' => 'DAW',  'video' => 'https://youtu.be/curriculo1'],
    ['alumno' => 'Marcos Pérez', 'ciclo' => 'DAM',  'video' => 'https://youtu.be/curriculo2'],
    ['alumno' => 'Laura García', 'ciclo' => 'ASIR', 'video' => 'https://youtu.be/curriculo3'],
  ];

  echo $curriculos[0]['alumno'];   // Ana López
?>
```

## Construyendo el listado, currículo a currículo

Con una función —recuerda 2.4— evitamos repetir la misma sentencia `echo` con distintos campos cada vez:

```php
<?php
  function mostrarCurriculo(array $curriculo) {
    echo '<li>', $curriculo['alumno'], ' — ', $curriculo['ciclo'],
         ' — <a href="', $curriculo['video'], '">vídeo</a></li>';
  }
?>
```

Fíjate en `array $curriculo`: es un **tipo declarado en un parámetro de función**, algo que sí existe en PHP —a diferencia de las variables sueltas, que vimos en 2.3 que no se pueden tipar—. Le dice a PHP (y a cualquiera que lea la función) que espera recibir un array; si le pasas otra cosa, PHP avisa con un error. Lo retomaremos con más tipos (`string`, `int`...) en el Bloque 3, al formalizar las funciones.

Con el array de currículos y la función ya listos, montamos la página llamando a la función una vez por cada currículo:

```php
<?php
  echo '<h1>', NOMBRE_CENTRO, ' — Currículos</h1>';
  echo '<ul>';
  mostrarCurriculo($curriculos[0]);
  mostrarCurriculo($curriculos[1]);
  mostrarCurriculo($curriculos[2]);
  echo '</ul>';
?>
```

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php`; si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

## Ejercicios

1. **El array de currículos.** Crea `vanilla_php/public/RA2_proyectoListado.php` con la constante `NOMBRE_CENTRO` (`'CIFP Carlos III'`, como en 2.3-2.4) y el array `$curriculos` de esta sección, con sus tres currículos.
2. **La función `mostrarCurriculo()`.** Añade la función de esta sección.
3. **El listado.** Añade el bloque `<h1>`/`<ul>`/llamadas de esta sección y compruébalo en `http://localhost/RA2_proyectoListado.php`.
4. **Investiga (opcional, sin test).** Con solo 3 currículos, repetir la llamada a `mostrarCurriculo()` a mano es asumible; con 100 sería absurdo. Busca en la documentación de PHP qué hace un bucle `foreach` y, si te animas, reescribe el ejercicio 3 con un `foreach` en vez de las tres llamadas sueltas. No hace falta que te salga perfecto: lo veremos formalmente —bucles y arrays— en el Bloque 3. Esto es solo para abrir boca.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 3, copia el archivo [RA2_5_ProyectoListadoTest.php](./materiales/ejercicios-vanilla/tests/RA2_5_ProyectoListadoTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA2_5_ProyectoListadoTest.php
```

Si todo está bien: `OK (2 tests, 7 assertions)`.

### Todos los tests del Bloque 2 a la vez

Si has ido copiando a `vanilla_php/tests/` el test de cada apartado según avanzabas por el bloque, ya tienes ahí los cinco. Ahora sí tiene sentido ejecutar `phpunit` **sin indicar un fichero**, para lanzarlos todos juntos:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit
```

Si todo el Bloque 2 está resuelto: `OK (10 tests, 32 assertions)`.

---

**Siguiente:** Bloque 3 — Programación basada en lenguajes de marcas con código embebido (RA3).
