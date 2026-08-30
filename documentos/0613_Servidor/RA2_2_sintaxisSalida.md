# 2.2. Sintaxis, sentencias y salida (`echo`/`print`)

## Sentencias: la unidad básica de un script PHP

Un script PHP —lo que va dentro de `<?php ?>`— es una secuencia de **sentencias** (_statements_): instrucciones que el servidor ejecuta una tras otra, en orden. Cada sentencia **termina en punto y coma** (`;`):

```php
<?php
  $tituloCiclo = "Desarrollo de Aplicaciones Web";
  echo $tituloCiclo;
?>
```

Aquí hay dos sentencias: una **asignación** (`$tituloCiclo = "...";`) y una de **salida** (`echo $tituloCiclo;`). De momento usamos `$tituloCiclo` de forma intuitiva —un nombre que guarda un valor—; en el próximo apartado (2.3) veremos con detalle qué son las variables, sus tipos y las constantes.

Un par de reglas de sintaxis que conviene tener presentes desde ya:

- **Los espacios en blanco y los saltos de línea no importan** para el intérprete: puedes formatear tu código como prefieras (aunque, por legibilidad, seguiremos una indentación consistente). Lo que sí importa es el `;` al final de cada sentencia.
- **Las variables son sensibles a mayúsculas/minúsculas**: `$tituloCiclo` y `$TituloCiclo` son dos variables distintas. Las palabras clave y los nombres de función, en cambio, **no** lo son: `echo`, `Echo` y `ECHO` son la misma instrucción (por convención, se escriben siempre en minúscula).
- Cuando más adelante (Bloque 3) agrupemos varias sentencias bajo una condición o un bucle, se encierran entre llaves `{ }`. De momento, nuestros scripts son secuencias simples de sentencias, sin agrupar.

## Sentencias simples y su efecto

Cada sentencia produce un **efecto**: hace que algo ocurra. Los dos efectos con los que trabajaremos más a menudo en este bloque son:

- **Asignar**: guardar un valor en una variable (`$horas = 2000;`). No genera ninguna salida visible; solo cambia el estado interno del script.
- **Emitir salida**: enviar texto a la respuesta HTTP que recibirá el navegador (`echo`, `print`). Es la única forma de que algo calculado en el servidor llegue a verse en la página.

Es fácil olvidarlo cuando se empieza: escribir `$horas = 2000;` **no muestra nada**. Si quieres verlo en la página, necesitas una sentencia de salida aparte:

```php
<?php
  $horas = 2000;      // asignación: sin efecto visible
  echo $horas;        // salida: esto sí llega al navegador
?>
```

## Las directivas de salida: `echo` y `print`

`echo` y `print` no son funciones en el sentido estricto de PHP —son **directivas del lenguaje** (_language constructs_)—, pero a efectos prácticos las usamos igual: para enviar texto a la salida. Existen ligeras diferencias entre ellas:

| | `echo` | `print` |
|---|---|---|
| Argumentos | Uno o **varios**, separados por comas | Solo **uno** |
| Valor devuelto | Ninguno | `1` (por eso puede usarse dentro de una expresión) |
| Paréntesis | Opcionales | Opcionales |

Con `echo` puedes concatenar varias piezas separándolas por comas —PHP las va emitiendo una tras otra, sin nada entre medias—:

```php
<?php
  $nombreCiclo = "Desarrollo de Aplicaciones Web";
  $familia     = "Informática y Comunicaciones";
  $horas       = 2000;

  echo '<p>El ciclo <strong>', $nombreCiclo, '</strong> pertenece a la familia de ', $familia, ' y tiene ', $horas, ' horas.</p>';
?>
```

Con `print`, al aceptar un único argumento, necesitamos el **operador de concatenación** `.` para construir la misma frase pegando trozos de texto:

```php
<?php
  print '<p>El ciclo <strong>' . $nombreCiclo . '</strong> pertenece a la familia de ' . $familia . ' y tiene ' . $horas . ' horas.</p>';
?>
```

Ambas sentencias producen exactamente el mismo HTML. La elección entre `echo` y `print` es, casi siempre, cuestión de estilo; en este REA usaremos sobre todo `echo`, por ser la más habitual y la que admite varios argumentos sin necesidad de concatenar.

> **Curiosidad.** Como `print` devuelve `1`, es la única de las dos que puede aparecer dentro de otra expresión, por ejemplo `$resultado = print "Hola";`. No es un patrón que vayamos a usar en este REA —resulta confuso—, pero explica por qué el manual de PHP la describe como una expresión y `echo` no.

## Preparación del entorno de trabajo

Seguimos trabajando sobre el mismo proyecto `vanilla_php` de la sección anterior. Si aún no lo tienes levantado, revisa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo) o, si partes de cero, [0. Instalación y preparación del entorno](./00_instalacionEntorno.md).

## Ejercicios

1. **Ficha de ciclo con `echo`.** Crea `vanilla_php/public/RA2_sintaxisSalida.php` con las tres variables (`$nombreCiclo`, `$familia`, `$horas`) y la sentencia `echo` de esta sección. Ábrelo en `http://localhost/RA2_sintaxisSalida.php` y comprueba el resultado.
2. **La misma ficha con `print`.** En el mismo fichero, debajo del `echo` anterior, añade un segundo párrafo con la sentencia `print` + concatenación que construye la misma frase. El resultado debe verse dos veces en la página, una por cada directiva.
3. **`print` como expresión.** Añade al final del fichero: `$resultado = print "Hola";` seguido de `echo $resultado;`. Observa el resultado en el navegador y anota, en un comentario, qué valor imprime `$resultado` y por qué. Prueba después a sustituir `print` por `echo` en esa misma línea (`$resultado = echo "Hola";`) y fíjate en el error que da PHP: ¿qué te dice eso sobre la diferencia entre ambas?
4. **Investiga.** ¿Qué ventaja práctica citan las fuentes oficiales de PHP a favor de `echo` frente a `print`? Escribe la respuesta en un comentario del fichero.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 y 2, copia el archivo [RA2_2_SintaxisSalidaTest.php](./materiales/ejercicios-vanilla/tests/RA2_2_SintaxisSalidaTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA2_2_SintaxisSalidaTest.php
```

Si todo está bien: `OK (2 tests, 6 assertions)`.

---

**Siguiente:** [2.3. Tipos de datos, variables y constantes, operadores](./RA2_3_tiposVariablesOperadores.md)
