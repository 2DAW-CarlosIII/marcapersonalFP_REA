# 2.4. Ámbitos de las variables

## Qué es el ámbito de una variable

El **ámbito** (_scope_) de una variable es la parte del script donde esa variable existe y puede usarse. Hasta ahora no le hemos prestado atención porque todos nuestros scripts han sido una única secuencia de sentencias: toda variable que creábamos vivía en el **ámbito global** del fichero y era accesible en cualquier línea posterior.

Eso cambia en cuanto aparecen las **funciones** —bloques de código con nombre que solo se ejecutan cuando se llaman—. Formalizaremos las funciones (parámetros, valores de retorno, etc.) en el Bloque 3; aquí adelantamos solo su sintaxis mínima, la necesaria para entender por qué el ámbito importa:

```php
<?php
  function saludo() {
    echo "Hola";
  }

  saludo();   // así se llama: por su nombre, seguido de ()
?>
```

## Ámbito local vs. ámbito global

Una variable creada **dentro** de una función es **local** a esa función: solo existe mientras la función se está ejecutando, y nada de fuera puede verla.

```php
<?php
  function mostrarCiclo() {
    $nombreCiclo = "Desarrollo de Aplicaciones Web";
    echo '<p>', $nombreCiclo, '</p>';
  }

  mostrarCiclo();

  // echo $nombreCiclo;   // Warning: Undefined variable $nombreCiclo
?>
```

Fíjate en el matiz, propio de PHP y distinto de lo que ocurre en otros lenguajes que quizá conozcas (como JavaScript): **una función no ve automáticamente las variables del ámbito global**, aunque estén definidas antes de llamarla. Cada función tiene su propio ámbito, aislado del resto del script por defecto.

```php
<?php
  $nombreCiclo = "Desarrollo de Aplicaciones Web";

  function mostrarCiclo() {
    echo '<p>', $nombreCiclo, '</p>';   // Warning: Undefined variable $nombreCiclo
  }

  mostrarCiclo();
?>
```

Aunque `$nombreCiclo` existe en el ámbito global justo antes de la llamada, `mostrarCiclo()` no la ve: para PHP son dos ámbitos distintos y separados.

## Acceder a una variable global desde una función: `global`

Si de verdad necesitas que una función lea (o modifique) una variable global, tienes que decírselo explícitamente con la palabra clave `global`:

```php
<?php
  $totalCiclosMostrados = 0;

  function registrarVisita() {
    global $totalCiclosMostrados;
    $totalCiclosMostrados++;
  }

  registrarVisita();
  registrarVisita();
  registrarVisita();

  echo '<p>Ciclos mostrados: ', $totalCiclosMostrados, '</p>';   // 3
?>
```

`global $totalCiclosMostrados;` no crea una variable nueva: **enlaza** el nombre local `$totalCiclosMostrados` dentro de la función con la variable global del mismo nombre, para que ambas sean, a partir de ahí, la misma variable.

> **Constantes: la excepción.** Las constantes que vimos en 2.3 (`define()`, `const`) **no** siguen estas reglas de ámbito: son accesibles en cualquier función sin necesidad de `global`. Es una de las razones por las que son útiles para valores fijos de configuración (el nombre del centro, una clave de API...).

```php
<?php
  define('NOMBRE_CENTRO', 'CIFP Carlos III');

  function pie() {
    // Sin "global": las constantes son accesibles en cualquier ámbito
    echo '<footer>', NOMBRE_CENTRO, '</footer>';
  }

  pie();
?>
```

Más adelante nos encontraremos también con las **variables superglobales** de PHP (`$_GET`, `$_POST`, `$_SERVER`, `$_SESSION`...), que comparten esta misma propiedad: están disponibles en cualquier ámbito sin `global`. Las usaremos con detalle cuando lleguemos a formularios (Bloque 3) y sesiones (Bloque 4).

## Variables estáticas

Normalmente, las variables locales de una función se destruyen al terminar la función y se vuelven a crear —vacías— en la siguiente llamada. La palabra clave `static` cambia eso: hace que una variable local **conserve su valor** entre llamadas sucesivas a la misma función.

```php
<?php
  function contadorVisitas() {
    static $visitas = 0;
    $visitas++;
    echo '<p>Visita número ', $visitas, '</p>';
  }

  contadorVisitas();   // Visita número 1
  contadorVisitas();   // Visita número 2
  contadorVisitas();   // Visita número 3
?>
```

Sin `static`, `$visitas` volvería a valer `0` al principio de cada llamada y el contador nunca pasaría de "Visita número 1".

**Ojo con "entre llamadas":** aquí "llamadas" son las tres invocaciones a `contadorVisitas()` dentro de **una misma ejecución** del script. Si abres `RA2_ambitos.php` en el navegador verás "Visita número 1, 2, 3"; pero si **recargas la página** —una petición HTTP nueva—, el contador empieza otra vez por 1. Esto es coherente con lo que vimos en 2.1: cada petición hace que el servidor ejecute el script **desde cero**, y al terminar de generar la respuesta no queda memoria de nada de lo ocurrido —tampoco de las variables `static`—. Para conservar un dato entre peticiones distintas (por ejemplo, cuántas veces se ha visitado una página en total) hacen falta mecanismos de estado explícitos —sesiones, cookies o una base de datos— que veremos en bloques posteriores.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php`; si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

## Ejercicios

1. **Ámbito local.** Crea `vanilla_php/public/RA2_ambitos.php` con la función `mostrarCiclo()` de esta sección y su llamada. Compruébalo en el navegador. Después, descomenta la línea `echo $nombreCiclo;` de fuera de la función, recarga y observa el aviso de PHP (`Undefined variable`). Explica en un comentario por qué ocurre. Cuando termines, vuelve a comentar esa línea para que el resto de ejercicios funcione sin avisos.
2. **Ámbito global con `global`.** Debajo, añade `$totalCiclosMostrados`, `registrarVisita()` y las tres llamadas de esta sección, terminando con el `echo` del total.
3. **Constantes y ámbito.** Añade la constante `NOMBRE_CENTRO` y la función `pie()` de esta sección, y llama a `pie()`.
4. **Variables estáticas.** Añade `contadorVisitas()` de esta sección y llámala tres veces seguidas. Comprueba el resultado y después **recarga la página varias veces**: ¿por qué el contador vuelve a empezar por 1 en cada recarga en vez de seguir por el 4, el 5...?
5. **Investiga.** ¿Qué son las variables **superglobales** de PHP? Cita al menos dos y explica, en un comentario, en qué se parece su ámbito al de las constantes.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 4, copia el archivo [RA2_4_AmbitosTest.php](./materiales/ejercicios-vanilla/tests/RA2_4_AmbitosTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA2_4_AmbitosTest.php
```

Si todo está bien: `OK (2 tests, 6 assertions)`.

---

**Siguiente:** [2.5. Proyecto: listado de currículos desde un array](./RA2_5_proyectoListado.md)
