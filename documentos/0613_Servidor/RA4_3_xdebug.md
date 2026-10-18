# 4.3. Depuración con Xdebug

**Presentación de apoyo** (_RevealJS_): [RA4_3_xdebug_slides.html](./materiales/slides/RA4_3_xdebug_slides.html)

Hasta ahora, cuando algo no funcionaba, teníamos dos pistas:
- el **mensaje de error** de PHP, que podemos ver en el navegador gracias al ajuste de `display_errors` de 2.2;
- los **tests**, que nos dicen qué resultado no es el esperado.

Pero hay errores que no producen ningún mensaje. El programa se ejecuta sin quejarse y, simplemente, **calcula mal**. Para encontrarlos hay que ver qué hace el código **mientras se ejecuta**: qué valor tiene cada variable, por qué rama de un `if` pasa, cuántas veces da vueltas un bucle. Esa es la tarea de un **depurador** (_debugger_).

## Depurar con trazas o con un depurador

La forma más rápida de mirar dentro de un script es escribir **trazas**: instrucciones que muestran un valor en un punto concreto.

```php
<?php
var_dump($curriculos);   // tipo y valor, con todo detalle
print_r($cuenta);        // más legible para arrays
error_log('cuenta: ' . print_r($cuenta, true));   // al registro de errores, no a la página
?>
```

Funcionan, pero tienen inconvenientes:
- hay que **modificar el código** para añadirlas, y acordarse de quitarlas después;
- solo muestran lo que decidiste mirar **antes** de ejecutar;
- en una página que redirige (como el alta de 4.1), la salida ni siquiera llega a verse.

Un **depurador** permite, en cambio:
- **detener** la ejecución en una línea, con un **punto de ruptura** (_breakpoint_);
- avanzar **paso a paso**;
- consultar en cada momento el valor de **todas** las variables, sin tocar el código.

## Cómo funciona Xdebug

**Xdebug** es una extensión de PHP que añade al intérprete la capacidad de ser depurado. El reparto de papeles es este:

1. El **editor** (VS Code, con la extensión _PHP Debug_) se queda **escuchando** en el puerto **9003**.
2. Llega al servidor una petición que lleva un **disparador** (_trigger_).
3. Xdebug, desde dentro de `php-fpm`, **se conecta al editor**. Fíjate en que es el servidor quien llama al editor, y no al revés.
4. A partir de ahí, el editor controla la ejecución: dónde pararse, cuándo avanzar, qué valores mostrar.

En nuestro entorno, todo esto ya quedó configurado al instalarlo, en [§7 de la preparación del entorno](./00_instalacionEntorno.md#7-depuración-errores-en-pantalla-y-xdebug). De ahí nos interesan tres líneas de `xdebug.ini`:

- **`xdebug.mode=debug`**: Xdebug está en modo de depuración paso a paso.
- **`xdebug.start_with_request=trigger`**: solo se depura una petición si lleva el disparador. Si no, PHP se ejecuta con normalidad.
- **`xdebug.client_host="dockerhost"`** y **`xdebug.client_port=9003`**: dónde está el editor visto desde el contenedor.

### El disparador

Una petición "pide" ser depurada si lleva una variable llamada **`XDEBUG_TRIGGER`** (con cualquier valor):

- **En la URL**: `http://localhost/RA4_resumen.php?XDEBUG_TRIGGER=1`. Es lo más sencillo para una petición GET.
- **Como cookie**: así viaja en **todas** las peticiones, incluidos los envíos de formularios por POST. Puedes crearla desde la consola de las herramientas de desarrollo (F12):

    ```js
    document.cookie = 'XDEBUG_TRIGGER=1; path=/';
    ```

    Y borrarla cuando termines, desde **Aplicación** (Chrome) o **Almacenamiento** (Firefox) → **Cookies**.

- **Con una extensión del navegador**, como _Xdebug Helper_ (de _JetBrains_) en Chrome: crea y borra la cookie con un botón. En sus opciones, el **Desencadenador de depuración** admite cualquier valor (por ejemplo, `vsc`); los de rastreo y perfilado no se usan, porque nuestro `xdebug.mode` solo incluye `debug`. Esta extensión no crea `XDEBUG_TRIGGER`, sino **`XDEBUG_SESSION`**, el nombre que usaban las versiones anteriores de Xdebug y que la actual sigue aceptando para la depuración.

## VS Code: la extensión y `launch.json`

En VS Code necesitas la extensión **PHP Debug** (identificador `xdebug.php-debug`). Instálala desde la vista de extensiones (<kbd>Ctrl</kbd>+<kbd>Mayús</kbd>+<kbd>X</kbd>).

Abre la carpeta `~/Documentos/laravel/vanilla_php` en VS Code (**Archivo → Abrir carpeta**). Después crea en ella el fichero `.vscode/launch.json`:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Xdebug (vanilla_php)",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/vanilla_php": "${workspaceFolder}"
            }
        }
    ]
}
```

La clave es **`pathMappings`**. Xdebug comunica las rutas de los ficheros tal como las ve **dentro del contenedor** (`/var/www/vanilla_php/public/RA4_resumen.php`), mientras que VS Code los tiene en la máquina virtual (`~/Documentos/laravel/vanilla_php/public/RA4_resumen.php`). Esta línea indica cómo traducir unas en otras. Sin ella, la conexión se establece, pero el editor nunca se detiene en tus puntos de ruptura.

Para empezar a escuchar, abre la vista **Ejecutar y depurar** (<kbd>Ctrl</kbd>+<kbd>Mayús</kbd>+<kbd>D</kbd>), elige "Xdebug (vanilla_php)" y pulsa <kbd>F5</kbd>. La barra inferior de VS Code cambia de color mientras escucha.

## El depurador, en marcha

Con VS Code escuchando, haz clic a la izquierda del número de una línea para poner un **punto de ruptura** (aparece un punto rojo). Después pide la página **con el disparador**. La ejecución se detiene en esa línea **antes de ejecutarla**, y desde ahí:

| Acción | Tecla | Qué hace |
|---|---|---|
| Continuar | <kbd>F5</kbd> | Sigue hasta el siguiente punto de ruptura (o hasta el final) |
| Paso a paso por encima | <kbd>F10</kbd> | Ejecuta la línea actual entera, aunque llame a funciones |
| Paso a paso por dentro | <kbd>F11</kbd> | Si la línea llama a una función tuya, entra en ella |
| Salir de la función | <kbd>Mayús</kbd>+<kbd>F11</kbd> | Termina la función actual y vuelve a quien la llamó |
| Detener | <kbd>Mayús</kbd>+<kbd>F5</kbd> | Deja de escuchar |

En el panel lateral tienes:
- **Variables**: las **locales** de la función en la que estás y las **superglobales** (`$_GET`, `$_POST`, `$_SESSION`, `$_COOKIE`…);
- **Inspección** (_Watch_): expresiones que quieres vigilar, por ejemplo `$cuenta['DAW']`;
- **Pila de llamadas** (_Call Stack_): qué función llamó a cuál hasta llegar a la línea actual.

En la **consola de depuración** puedes además evaluar cualquier expresión con los valores del momento.

Un punto de ruptura puede ser también **condicional**: con clic derecho → **Editar punto de interrupción**, se detiene solo si se cumple una expresión, por ejemplo `$codigo === 'DAW'`. Es muy útil dentro de un bucle largo.

> **Si no se detiene.** Comprueba, por este orden:
> 1. que VS Code está escuchando (barra inferior coloreada);
> 2. que la petición lleva el disparador;
> 3. que el punto de ruptura está en una línea que de verdad se ejecuta;
> 4. que `pathMappings` coincide con tu carpeta.
>
> El mensaje _"Could not connect to debugging client"_ en el registro de PHP significa que Xdebug intentó conectar y nadie escuchaba en el 9003. Para un diagnóstico completo, una página con solo `<?php xdebug_info();` muestra la configuración de Xdebug y el resultado del último intento de conexión.

## Depurar también los tests

Xdebug está instalado igualmente en el contenedor `workspace`, donde se ejecuta PHPUnit, con la misma configuración. Para depurar un test, pasa el disparador como **variable de entorno** con la opción `-e` de `docker compose exec`:

```bash
docker compose exec -e XDEBUG_TRIGGER=1 --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA4_3_XdebugTest.php
```

Con un punto de ruptura en el test o en tu código de `src/`, VS Code se detendrá igual que con una página.

## Pruebas y depuración

Los tests y el depurador se complementan:
- un test **automatiza** la comprobación: dice **que** algo falla, y lo dirá cada vez que lo ejecutes;
- el depurador ayuda a encontrar **por qué** falla, mirando dentro de la ejecución.

El flujo habitual es:
1. un test (o un usuario) detecta un fallo;
2. con el depurador se localiza la causa;
3. se corrige;
4. el test confirma que ya funciona y, a partir de entonces, vigila que no vuelva a fallar.

## Preparación del entorno de trabajo

Seguimos con el proyecto `vanilla_php`. Xdebug y el ajuste de `display_errors` están configurados desde la [preparación del entorno](./00_instalacionEntorno.md#7-depuración-errores-en-pantalla-y-xdebug). Necesitas, además, VS Code con la extensión _PHP Debug_ y el `launch.json` de este apartado.

Al terminar, estos son los ficheros nuevos:

```
vanilla_php/
├── .vscode/
│   └── launch.json           ← configuración de depuración (nuevo)
├── public/
│   ├── RA4_xdebug.php        ← ¿está Xdebug activo? (nuevo)
│   └── RA4_resumen.php       ← resumen de currículos por ciclo (nuevo)
└── src/
    └── RA4_depuracion.php    ← dos funciones con errores que encontrar (nuevo)
```

## Ejercicios

1. **¿Está Xdebug activo?** Crea `public/RA4_xdebug.php` y ábrelo en `http://localhost/RA4_xdebug.php`. Debe mostrar "cargado" y el modo `debug`:

    ```php
    <?php
    declare(strict_types=1);
    ?>
    <p>Xdebug: <?= extension_loaded('xdebug') ? 'cargado' : 'no cargado' ?></p>
    <p>Modo: <?= ini_get('xdebug.mode') ?: '(ninguno)' ?></p>
    <p>Inicio de la depuración: <?= ini_get('xdebug.start_with_request') ?: '(no definido)' ?></p>
    ```

2. **Prepara VS Code.** Comprueba que la extensión _PHP Debug_ está instalada, abre la carpeta `vanilla_php` y crea `.vscode/launch.json` como en este apartado. Pon un punto de ruptura en la primera línea de `<p>` de `RA4_xdebug.php`, empieza a escuchar (<kbd>F5</kbd>) y pide `http://localhost/RA4_xdebug.php?XDEBUG_TRIGGER=1`. Si VS Code se detiene, todo está listo. Si no, sigue la lista de "Si no se detiene".

3. **Un resumen que calcula mal.** Crea `src/RA4_depuracion.php` **exactamente así**, con sus errores:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/Curriculo.php';

    /**
     * Cuenta cuántos currículos hay de cada ciclo.
     *
     * @param list<Curriculo> $curriculos
     * @return array<string, int> Código del ciclo => número de currículos, en orden de aparición.
     */
    function contarPorCiclo(array $curriculos): array
    {
        $cuenta = [];
        foreach ($curriculos as $curriculo) {
            $codigo = $curriculo->ciclo->codCiclo;
            $cuenta[$codigo] = isset($cuenta[$codigo]) ? $cuenta[$codigo]++ : 1;
        }

        return $cuenta;
    }

    /** Porcentaje (redondeado a entero) que representa $parte sobre $total. */
    function porcentaje(int $parte, int $total): int
    {
        return intdiv($parte, $total) * 100;
    }
    ```

    Y la página `public/RA4_resumen.php`, que los usa con cuatro currículos:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA4_comun.php';
    require_once __DIR__ . '/../src/RA4_depuracion.php';

    $ciclos = catalogoCiclos();
    $curriculos = [
        new Curriculo(1, 'Ana López', $ciclos['DAW'], 'https://youtu.be/curriculo1'),
        new Curriculo(2, 'Marcos Pérez', $ciclos['DAM'], 'https://youtu.be/curriculo2'),
        new Curriculo(3, 'Laura García', $ciclos['ASIR'], 'https://youtu.be/curriculo3'),
        new Curriculo(4, 'Lucía Martín', $ciclos['DAW']),
    ];

    $cuenta = contarPorCiclo($curriculos);
    $total  = count($curriculos);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Resumen — <?= NOMBRE_CENTRO ?></title>
    </head>
    <body>
      <h1><?= NOMBRE_CENTRO ?> — Currículos por ciclo</h1>
      <ul>
        <?php foreach ($cuenta as $codigo => $numero): ?>
          <li><?= e($codigo) ?>: <?= $numero ?> (<?= porcentaje($numero, $total) ?> %)</li>
        <?php endforeach; ?>
      </ul>
      <p>Total: <?= $total ?> currículos.</p>
    </body>
    </html>
    ```

    Ábrela: debería mostrar `DAW: 2 (50 %)`, `DAM: 1 (25 %)` y `ASIR: 1 (25 %)`, pero no es eso lo que sale. No hay ningún mensaje de error.

4. **Encuentra los errores con el depurador.** No añadas `var_dump()`: usa Xdebug.
    - Pon un punto de ruptura en la línea `$cuenta[$codigo] = …` y pide `RA4_resumen.php?XDEBUG_TRIGGER=1`. Avanza con <kbd>F10</kbd> vuelta a vuelta, vigilando `$cuenta` en el panel de variables (o añade `$cuenta` a **Inspección**). ¿Qué ocurre en la vuelta de Lucía Martín?
    - Prueba a poner un punto de ruptura **condicional** con `$codigo === 'DAW'`.
    - Para el porcentaje, pon un punto de ruptura dentro de `porcentaje()` y llega hasta él con <kbd>F5</kbd>. En la consola de depuración, evalúa `intdiv($parte, $total)` y `$parte / $total`. ¿Qué diferencia hay?
    - Corrige las dos funciones y explica en un comentario, junto a cada corrección, cuál era el error.
5. **Depura un test.** Con un punto de ruptura dentro de `contarPorCiclo()`, ejecuta el test de este apartado con el disparador (`-e XDEBUG_TRIGGER=1`, ver [Depurar también los tests](#depurar-también-los-tests)). ¿Qué muestra la **Pila de llamadas**: desde dónde se ha llamado a tu función?
6. **Investiga.** ¿Qué devuelve tu `porcentaje()` corregida si `$total` vale 0? Pruébalo en la consola de depuración y busca en la documentación de PHP qué es `DivisionByZeroError`. ¿Cómo protegerías la función? Responde en un comentario (el test no lo comprueba).

> **Puente al Bloque 5.** En _Laravel_ se depura igual: Xdebug no sabe nada del framework, solo ve PHP. Como todas las peticiones entran por `public/index.php`, el `pathMappings` apuntará a la carpeta del proyecto _Laravel_. Para trazas rápidas, _Laravel_ añade `dump()` y `dd()` ("_dump and die_"), versiones más legibles de `var_dump()`.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1, 3 y 4, copia el archivo [RA4_3_XdebugTest.php](./materiales/ejercicios-vanilla/tests/RA4_3_XdebugTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA4_3_XdebugTest.php
```

El test comprueba que Xdebug está activo en `php-fpm`, en modo `debug`, y que las dos funciones de `src/RA4_depuracion.php` y la página del resumen dan los resultados correctos. Mientras no corrijas los dos errores, **fallará**: es lo esperado.

Necesita `nginx` y `php-fpm` levantados.

Si todo está bien: `OK (6 tests, 10 assertions)`.

---

**Siguiente:** [4.4. _Proyecto:_ área privada de _marcapersonalFP v0_](./RA4_4_proyectoAreaPrivada.md)
