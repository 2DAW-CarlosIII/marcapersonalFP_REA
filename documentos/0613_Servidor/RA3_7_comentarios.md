# 3.7. Comentarios

## Para qué sirve un comentario

Llevamos todo el Bloque 2 y buena parte del 3 escribiendo comentarios sin detenernos en ellos: para anotar lo que esperabas en un ejercicio, para explicar un ejemplo... Un **comentario** es texto que el intérprete **ignora**: no se ejecuta ni produce salida. Existe solo para las personas (y, como veremos, para algunas herramientas) que leen el código.

La regla de oro: un buen comentario explica **por qué**, no **qué**. El "qué" ya lo dice el código; si el código no se entiende, suele ser mejor mejorar los nombres que añadir un comentario.

```php
<?php
  // Mal: repite lo que ya dice el código
  $anios++;   // suma 1 a $anios

  // Bien: explica una decisión que el código no cuenta
  // Solo los ciclos de Grado Superior dan acceso directo a la universidad.
  if ($grado === 'Superior') {
      // ...
  }
?>
```

## Comentarios en PHP

PHP tiene tres formas de escribir un comentario:

```php
<?php
  // Comentario de una línea: desde // hasta el final de la línea

  # También de una línea, al estilo de la consola (poco usado)

  /*
     Comentario de varias líneas:
     desde /* hasta el cierre.
  */

  $horas = 2000;   // también puede ir al final de una sentencia
?>
```

> **Cuidado con `?>`.** Un comentario de una línea (`//` o `#`) termina al final de la línea... **o** al encontrar `?>`, lo que ocurra antes. Lo que haya detrás de `?>` ya no es PHP: se envía al navegador como HTML. Lo comprobarás en el ejercicio 2. Los comentarios de varias líneas (`/* */`) no tienen este problema.

> **`#` no siempre es un comentario.** Desde PHP 8, `#[` inicia un **atributo**, que no es un comentario sino información que PHP sí lee. Ya los has visto sin saberlo: PHPUnit usa atributos como `#[DataProvider]`. Lo investigarás al final.

### Comentar código temporalmente

Un uso habitual al depurar es **desactivar** unas líneas convirtiéndolas en comentario, como hicimos en los ejercicios de 3.5. Es útil para probar algo, pero no dejes código comentado en la versión final: acaba siendo ruido que nadie se atreve a borrar. Si en algún momento necesitas recuperar código antiguo, para eso está el historial de _git_.

## Comentarios PHP frente a comentarios HTML

En un fichero `.php` conviven dos lenguajes, y cada uno tiene sus comentarios:

```php
<?php
  // Comentario PHP: el servidor lo descarta
?>
<!-- Comentario HTML: el servidor lo envía tal cual -->
```

Recuerda la idea clave de 2.1: el navegador solo recibe el HTML resultante. Un comentario **PHP** se queda en el servidor, como el resto del código. Un comentario **HTML**, en cambio, está **fuera** de las etiquetas `<?php ?>`: para PHP es un texto más y lo envía al navegador. No se ve en la página, pero **cualquiera puede leerlo** con "Ver código fuente".

Consecuencia práctica: las notas internas (credenciales, rutas del servidor, "esto hay que arreglarlo", decisiones de negocio...) van **siempre** en comentarios PHP, nunca en comentarios HTML.

## Comentarios de documentación: _docblocks_

Un **_docblock_** es un comentario de varias líneas que empieza por **`/**`** (dos asteriscos) y se coloca justo antes de una función, una clase, un método o una propiedad. Sigue un formato estándar, **PHPDoc**, con etiquetas que empiezan por `@`:

```php
<?php
/**
 * Devuelve los currículos de un ciclo, reindexados desde 0.
 *
 * @param array<int, array{alumno: string, ciclo: string, video: ?string}> $curriculos
 * @param string $codCiclo Código del ciclo, por ejemplo 'DAW'.
 * @return array<int, array{alumno: string, ciclo: string, video: ?string}>
 */
function curriculosDeCiclo(array $curriculos, string $codCiclo): array
{
    // ...
}
?>
```

| Etiqueta | Documenta |
|---|---|
| `@param tipo $nombre descripción` | Un parámetro |
| `@return tipo descripción` | El valor devuelto |
| `@throws Clase descripción` | Un error (excepción) que puede lanzar |
| `@var tipo` | El tipo de una propiedad o variable |

La primera línea es un resumen breve; si hace falta, le sigue una descripción más larga.

### ¿Docblocks, si ya tenemos tipos declarados?

En 3.4 vimos que los tipos declarados documentan la función por sí solos. Entonces, ¿para qué repetirlos? Porque los tipos de PHP tienen un límite: `array` no dice **qué contiene** el array. El docblock sí puede decirlo:

- `array<int, string>`: un array indexado de strings (como el que devuelve `nombresAlumnos()`).
- `array{alumno: string, ciclo: string}`: un array asociativo con **esas claves** y esos tipos (lo que se llama un _array shape_).
- `array<int, array{...}>`: una lista de esos arrays asociativos, como nuestros `$curriculos`.

PHP **no comprueba** los docblocks al ejecutar: son comentarios. Pero los leen el editor (para autocompletar `$curriculo['...']`), los analizadores estáticos como _PHPStan_ (que sí detectan si pasas algo que no encaja), los generadores de documentación y los asistentes de programación con IA. Por eso la práctica habitual es: **tipos declarados siempre que se pueda, y docblock cuando aportan algo que los tipos no pueden expresar**, o cuando hay que explicar un comportamiento que no se deduce de la firma. Un docblock que solo repite `@param string $codCiclo` sin añadir nada es, como los comentarios del principio, ruido.

> **Puente al Bloque 4.** El código de _Laravel_ está lleno de docblocks: al pasar el ratón por encima de un método del framework en tu editor, lo que ves es su docblock. Y en el Bloque 6, la documentación de la API con _OpenAPI/Swagger_ llevará esta misma idea —describir formalmente qué recibe y qué devuelve algo— a los servicios web.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php` y su carpeta `src/` ([3.4](./RA3_4_funciones.md#preparación-del-entorno-de-trabajo)). Si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

## Ejercicios

1. **Comentarios PHP y HTML.** Crea `public/RA3_comentarios.php`:

    ```php
    <?php
    declare(strict_types=1);

    // Comentario de una línea: no llega al navegador
    # También de una línea, estilo consola
    /*
     * Comentario de varias líneas: tampoco llega al navegador.
     * Nota interna: revisar el texto de bienvenida.
     */
    $titulo = 'Currículos del CIFP Carlos III';
    ?>
    <!-- Comentario HTML: este sí llega al navegador -->
    <h1><?= $titulo ?></h1>
    ```

    Ábrela en `http://localhost/RA3_comentarios.php` y mira el código fuente (`Ctrl+U`): ¿cuál de los cuatro comentarios aparece?
2. **El `?>` dentro de un comentario.** Añade temporalmente, justo después de la línea de `$titulo`, este comentario: `// Si escribo ?> en un comentario, lo que sigue sale en la página`. Recarga: ¿qué aparece en la página y por qué? Cámbialo por un comentario `/* ... */` con el mismo texto y comprueba la diferencia. Bórralo al terminar.
3. **Docblocks.** En `src/RA3_funciones.php` (de 3.4), añade el docblock de esta sección justo antes de `curriculosDeCiclo()`, y este otro antes de `ordenarPorAlumno()`:

    ```php
    /**
     * Devuelve los currículos ordenados alfabéticamente por alumno.
     *
     * No modifica el array recibido: como se pasa por valor, usort() ordena una copia.
     *
     * @param array<int, array{alumno: string, ciclo: string, video: ?string}> $curriculos
     * @return array<int, array{alumno: string, ciclo: string, video: ?string}>
     */
    ```

    Si tu editor lo permite, pasa el ratón por encima de una llamada a esas funciones en `public/RA3_funciones.php` y comprueba qué te muestra.
4. **Investiga.** Busca en la documentación de PHP qué son los **atributos** (_attributes_, `#[...]`), introducidos en PHP 8. ¿Por qué `#[DataProvider('ciclos')]` no es un comentario aunque empiece por `#`? ¿Qué diferencia hay entre la información que da un atributo y la que da un docblock? Escribe la respuesta en un comentario de `RA3_comentarios.php` (¿de qué tipo, PHP o HTML?).

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 y 3, copia el archivo [RA3_7_ComentariosTest.php](./materiales/ejercicios-vanilla/tests/RA3_7_ComentariosTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA3_7_ComentariosTest.php
```

El test comprueba qué comentarios llegan al navegador y, cargando `src/RA3_funciones.php`, lee los docblocks de tus funciones (PHP permite obtenerlos con _Reflection_, como hicimos con los tipos en 3.4).

Si todo está bien: `OK (4 tests, 10 assertions)`.

---

**Siguiente:** [3.8. Proyecto: alta y validación de un currículo](./RA3_8_proyectoAltaCurriculo.md)
