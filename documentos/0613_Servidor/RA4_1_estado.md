# 4.1. Mantenimiento del estado: _cookies_ y sesiones

> **Bloque 4 · Desarrollo de aplicaciones web utilizando código embebido (RA4).** Cerramos _marcapersonalFP v0_, todavía en PHP plano. Al terminar el Bloque 3 teníamos un listado y un alta que **lo olvidaban todo** al acabar cada petición. En este bloque la aplicación aprenderá a **recordar** (_cookies_ y sesiones) y a saber **quién la usa** (autenticación), y nosotros aprenderemos a **depurarla** con _Xdebug_. Terminaremos con un área privada de _v0_. En el Bloque 5 reconstruiremos todo con _Laravel_ y veremos que **el framework automatiza lo que aquí hacemos a mano**.

**Presentación de apoyo** (_RevealJS_): [RA4_1_estado_slides.html](./materiales/slides/RA4_1_estado_slides.html)

## ¿Por qué la aplicación lo olvida todo?

Recuerda 2.4 y 3.8: cada petición ejecuta el script **desde cero**. Las variables, los arrays y los objetos que se crean al atender una petición desaparecen al enviar la respuesta. Por eso el alta de 3.8 no podía guardar nada: el currículo existía solo mientras se generaba la página que lo mostraba.

Además, el protocolo **HTTP no tiene estado** (_stateless_): cada petición llega al servidor sin ninguna relación con las anteriores. Si dos alumnos usan la aplicación a la vez, sus peticiones se mezclan en el servidor, y nada en ellas indica cuáles vienen del mismo navegador.

Para mantener información de un cliente concreto entre peticiones hay dos mecanismos básicos, y en la práctica se usan juntos:

- **_Cookies_**: la información se guarda **en el navegador**, que la devuelve en cada petición.
- **Sesiones**: la información se guarda **en el servidor**, y el navegador solo guarda un identificador.

## _Cookies_: el navegador recuerda

Una **_cookie_** es un par nombre-valor que el servidor pide al navegador que guarde. El intercambio va en las **cabeceras** HTTP:

1. En una respuesta, el servidor envía la cabecera `Set-Cookie: ultimo_ciclo=DAW`.
2. A partir de entonces, en cada petición a ese sitio, el navegador envía la cabecera `Cookie: ultimo_ciclo=DAW`.

En PHP, la cookie se crea con **`setcookie()`** y se lee en la superglobal **`$_COOKIE`**:

```php
<?php
// Guardar: el navegador la conservará 30 días.
setcookie('ultimo_ciclo', 'DAW', [
    'expires'  => time() + 60 * 60 * 24 * 30,
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Leer: llega en las peticiones SIGUIENTES.
$ciclo = $_COOKIE['ultimo_ciclo'] ?? '';
?>
```

Hay tres detalles que conviene tener presentes:

- **`$_COOKIE` refleja la petición, no la respuesta.** Contiene las cookies que **envió el navegador**. La que acabas de crear con `setcookie()` no aparece en `$_COOKIE` hasta la petición siguiente.
- **Cookie persistente o de sesión.** Con `expires` (un instante, en segundos desde 1970, como devuelve `time()`), el navegador la guarda hasta esa fecha aunque se cierre. Sin `expires`, es una **cookie de sesión**: se borra al cerrar el navegador.
- **El valor viene del cliente.** El usuario puede ver y cambiar sus cookies desde las herramientas de desarrollo (F12). Una cookie es un dato de entrada más, como `$_POST`: nunca te fíes de ella sin validarla.

### Las cabeceras, antes que el cuerpo

`setcookie()` no escribe nada en la página: añade una **cabecera** a la respuesta. Y las cabeceras viajan **antes** que el HTML. En cuanto el script envía la primera salida (un `echo`, un trozo de HTML, incluso una línea en blanco antes de `<?php`), PHP envía las cabeceras, y ya no se pueden añadir más. Si después llamas a `setcookie()`, `header()` o `session_start()`, verás el aviso _"Cannot modify header information - headers already sent"_.

Por eso todo lo que toca cabeceras va **al principio del script**, antes de cualquier HTML. Es la misma estructura que ya usábamos en 3.6 y 3.8: primero la lógica en PHP y después la página.

## Sesiones: el servidor recuerda

Una cookie solo debería guardar datos pequeños y que no importe que el usuario vea o cambie. Para todo lo demás está la **sesión**: un almacén de datos **en el servidor**, distinto para cada navegador.

```php
<?php
session_start();

$_SESSION['visitas'] = ($_SESSION['visitas'] ?? 0) + 1;
?>
<p>Has visitado esta página <?= $_SESSION['visitas'] ?> veces.</p>
```

Esto es lo que ocurre:

1. **`session_start()`** busca en la petición la cookie **`PHPSESSID`**.
   - Si no llega, es una **sesión nueva**: PHP genera un identificador aleatorio y lo envía al navegador con `Set-Cookie: PHPSESSID=…`.
   - Si llega, PHP carga los datos que guardó para ese identificador.
2. Durante el script, **`$_SESSION`** es un array normal: lees y escribes en él como en cualquier otro.
3. Al terminar el script, PHP **guarda `$_SESSION`** en el servidor (por defecto, en un fichero) hasta la siguiente petición con ese identificador.

El navegador solo conoce el identificador; los datos nunca salen del servidor. Cada navegador tiene su propia sesión. Por eso dos alumnos usando la aplicación a la vez no se mezclan, y una ventana privada empieza con una sesión vacía.

> **Datos, no objetos.** Para guardar `$_SESSION`, PHP lo **serializa**: lo convierte en un texto del que después puede reconstruirlo. Los arrays y los valores simples se serializan sin problemas. Los objetos también se pueden guardar, pero para reconstruirlos su clase tiene que estar cargada **antes** de `session_start()`. Por eso en este apartado guardaremos en la sesión los **datos** de cada currículo, y la página que los muestre volverá a construir los objetos. Es la misma idea que seguiremos con la base de datos: se guardan filas de datos, y la aplicación las convierte en objetos.

## ¿_Cookie_ o sesión?

| | _Cookie_ | Sesión |
|---|---|---|
| ¿Dónde se guarda? | En el navegador | En el servidor (el navegador solo guarda `PHPSESSID`) |
| ¿Puede el usuario verla o cambiarla? | Sí | No |
| Tamaño | Pequeño (unos 4 KB por cookie) | Lo que necesites |
| ¿Cuánto dura? | Hasta `expires`, o hasta cerrar el navegador | Hasta que caduca por inactividad o se destruye |
| Ejemplos | Preferencias: idioma, último ciclo elegido | Lo dado de alta, el usuario identificado (4.2), mensajes entre páginas |

Además, las cookies viajan en **cada petición**, también en la de cada imagen y cada hoja de estilos del sitio: cuantas más y más grandes, más tráfico.

## Proteger la cookie de sesión

Quien conozca el valor de `PHPSESSID` de otra persona puede presentarlo como suyo y **suplantar su sesión**. Por eso conviene configurar la cookie de sesión con dos atributos que PHP, por defecto, no activa:

- **`HttpOnly`**: el JavaScript de la página no puede leer la cookie (`document.cookie` no la muestra). Si un ataque XSS (3.6) consiguiera ejecutar código en la página, no podría robar el identificador.
- **`SameSite=Lax`**: el navegador no envía la cookie en peticiones POST que se originan en **otro sitio**. Así se dificulta que una página ajena envíe formularios a nuestra aplicación en nombre del usuario (el ataque **CSRF**, que mencionamos en 3.6).

Se indican al abrir la sesión:

```php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);
```

Existe un tercer atributo, **`Secure`**, que hace que la cookie solo viaje por HTTPS. En producción debe activarse siempre, pero en nuestro entorno local trabajamos con HTTP, así que con `Secure` la cookie no viajaría.

## Por fin: POST/Redirect/GET con un mensaje

En 3.6 vimos que, tras procesar un formulario con éxito, lo correcto es **redirigir** a otra página con una petición GET. Así, si el usuario pulsa F5, el navegador recarga esa página en lugar de volver a enviar el formulario. Entonces nos faltaba la forma de mostrar un mensaje en la página de destino, porque había que recordar algo entre dos peticiones. Con la sesión ya la tenemos:

```php
$_SESSION['mensaje'] = 'Currículo dado de alta: Ana López (DAW)';
header('Location: RA4_curriculos.php', true, 303);
exit;
```

- **`header('Location: …', true, 303)`** responde con el código **303 _See Other_**, que indica al navegador que pida la otra página **con GET**.
- **`exit`** detiene el script: tras la redirección no tiene sentido generar ningún HTML, porque el navegador no lo mostraría.

En la página de destino, el mensaje se lee y **se borra** de la sesión, para que se muestre una sola vez. A este tipo de mensaje se le llama **mensaje _flash_**:

```php
$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);
```

![El navegador envía el alta por POST; el script la valida, guarda el currículo y un mensaje en la sesión y responde 303 con la cabecera Location, sin HTML. El navegador pide el listado con GET; el script lee el mensaje de la sesión, lo borra y lo muestra. Si el usuario pulsa F5, solo se repite el GET: el formulario no se reenvía y el mensaje ya no aparece](./images/RA4_1_prg.svg)

El diagrama sigue el patrón completo. La respuesta al `POST` (paso 2) no lleva HTML, solo la redirección, así que la página que el usuario tiene delante es la del `GET` (paso 3). Por eso F5 (paso 4) repite ese `GET`, que no cambia nada, y el mensaje ya no sale porque se borró al mostrarlo.

## El alta de _v0_, ahora con memoria

Con todo esto, el alta de 3.8 se convierte en dos páginas que comparten la sesión:

- **`RA4_altaCurriculo.php`**: el formulario de 3.8.
  - Si los datos son válidos, añade el currículo a `$_SESSION['curriculos']`, deja un mensaje _flash_, guarda el ciclo elegido en la cookie `ultimo_ciclo` y redirige al listado.
  - Si no son válidos, vuelve a mostrar el formulario con los errores, como en 3.8.
  - El desplegable propone el ciclo de la cookie.
- **`RA4_curriculos.php`**: el listado de 2.5, que ahora muestra **los currículos dados de alta en esta sesión** y el mensaje _flash_.

Las dos necesitan el catálogo de ciclos. En lugar de copiarlo en cada página, lo llevamos a una función en `src/RA4_comun.php`, junto con la constante del centro y los `require_once` que ambas comparten.

Fíjate en el **identificador** de cada currículo. En 3.8 usábamos siempre 1, porque no había quien lo asignara. Ahora lo calculamos a partir de su posición en la lista de la sesión, de modo que el primero recibe el 1, el segundo el 2, etc. Hasta que llegue la base de datos (Bloque 6), con eso nos basta.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php` de los Bloques 2 y 3, con sus clases de 3.5 y 3.8 en `src/`. Si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

Al terminar, estos son los ficheros que intervienen:

```
vanilla_php/
├── public/
│   ├── RA4_altaCurriculo.php   ← el alta, con sesión y cookie (nuevo)
│   └── RA4_curriculos.php      ← el listado de la sesión (nuevo)
└── src/
    ├── Modelo.php              (3.5)
    ├── Publicable.php          (3.5)
    ├── Ciclo.php               (3.5)
    ├── Curriculo.php           (3.5)
    ├── RA3_formularios.php     (3.6, por e())
    ├── ValidadorCurriculo.php  (3.8)
    └── RA4_comun.php           ← catálogo y código común (nuevo)
```

## Ejercicios

1. **El código común.** Crea `src/RA4_comun.php`:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/RA3_formularios.php';
    require_once __DIR__ . '/ValidadorCurriculo.php';

    const NOMBRE_CENTRO = 'CIFP Carlos III';

    /**
     * Ciclos que ofrece el centro, indexados por su código.
     * En el Bloque 6 saldrán de la base de datos.
     *
     * @return array<string, Ciclo>
     */
    function catalogoCiclos(): array
    {
        return [
            'DAW'  => new Ciclo(1, 'DAW', 'Desarrollo de Aplicaciones Web', Ciclo::GRADO_SUPERIOR),
            'DAM'  => new Ciclo(2, 'DAM', 'Desarrollo de Aplicaciones Multiplataforma', Ciclo::GRADO_SUPERIOR),
            'ASIR' => new Ciclo(3, 'ASIR', 'Administración de Sistemas Informáticos en Red', Ciclo::GRADO_SUPERIOR),
            'SMR'  => new Ciclo(4, 'SMR', 'Sistemas Microinformáticos y Redes', Ciclo::GRADO_MEDIO),
        ];
    }
    ```

2. **El alta.** Crea `public/RA4_altaCurriculo.php`. La parte de PHP es nueva. El HTML es el de 3.8 sin la sección de resultado, porque ahora el resultado se ve en el listado:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA4_comun.php';

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);

    $ciclos    = catalogoCiclos();
    $validador = new ValidadorCurriculo($ciclos);
    $datos     = [];
    $errores   = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos   = $_POST;
        $errores = $validador->validar($datos);

        if ($errores === []) {
            $curriculos = $_SESSION['curriculos'] ?? [];
            $curriculo  = $validador->crearCurriculo($datos, count($curriculos) + 1);

            // En la sesión guardamos los datos, no el objeto: el listado lo reconstruirá.
            $curriculos[] = [
                'alumno' => $curriculo->alumno,
                'ciclo'  => $curriculo->ciclo->codCiclo,
                'video'  => $curriculo->videoCurriculum ?? '',
            ];
            $_SESSION['curriculos'] = $curriculos;
            $_SESSION['mensaje']    = 'Currículo dado de alta: ' . $curriculo->descripcion();

            // Recordamos el ciclo elegido durante 30 días para proponerlo la próxima vez.
            setcookie('ultimo_ciclo', $curriculo->ciclo->codCiclo, [
                'expires'  => time() + 60 * 60 * 24 * 30,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            // POST/Redirect/GET: el navegador pedirá el listado con GET.
            header('Location: RA4_curriculos.php', true, 303);
            exit;
        }
    }

    // Ciclo que aparece elegido: el que se acaba de enviar o, si no, el de la cookie.
    $cicloElegido = $datos['ciclo'] ?? $_COOKIE['ultimo_ciclo'] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Alta de currículo — <?= NOMBRE_CENTRO ?></title>
    </head>
    <body>
      <h1><?= NOMBRE_CENTRO ?> — Alta de currículo</h1>

      <?php if ($errores !== []): ?>
        <ul class="errores">
          <?php foreach ($errores as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form action="RA4_altaCurriculo.php" method="post">
        <p>
          <label>Alumno
            <input name="alumno" value="<?= e($datos['alumno'] ?? '') ?>">
          </label>
        </p>
        <p>
          <label>Ciclo
            <select name="ciclo">
              <option value="">— Elige un ciclo —</option>
              <?php foreach ($ciclos as $codigo => $ciclo): ?>
                <option value="<?= e($codigo) ?>" <?= $cicloElegido === $codigo ? 'selected' : '' ?>><?= e($ciclo->descripcion()) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </p>
        <p>
          <label>Vídeo de presentación (opcional)
            <input name="video" type="url" value="<?= e($datos['video'] ?? '') ?>">
          </label>
        </p>
        <button type="submit">Dar de alta</button>
      </form>

      <p><a href="RA4_curriculos.php">Ver los currículos dados de alta</a></p>
    </body>
    </html>
    ```

    Fíjate en cómo se usa la cookie. Su valor solo se **compara** (`$cicloElegido === $codigo`) con los códigos del catálogo, nunca se escribe en la página. Si alguien la manipula con un código que no existe, simplemente no aparece ningún ciclo elegido.

3. **El listado.** Crea `public/RA4_curriculos.php`:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA4_comun.php';

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);

    $validador = new ValidadorCurriculo(catalogoCiclos());

    // Reconstruimos cada currículo de la sesión; su id es su posición + 1.
    $curriculos = [];
    foreach ($_SESSION['curriculos'] ?? [] as $posicion => $datos) {
        $curriculos[] = $validador->crearCurriculo($datos, $posicion + 1);
    }

    // Mensaje flash: se muestra una vez y se borra de la sesión.
    $mensaje = $_SESSION['mensaje'] ?? null;
    unset($_SESSION['mensaje']);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Currículos — <?= NOMBRE_CENTRO ?></title>
    </head>
    <body>
      <h1><?= NOMBRE_CENTRO ?> — Currículos</h1>

      <?php if ($mensaje !== null): ?>
        <p class="exito"><?= e($mensaje) ?></p>
      <?php endif; ?>

      <?php if ($curriculos === []): ?>
        <p>Todavía no has dado de alta ningún currículo.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($curriculos as $curriculo): ?>
            <li>
              <?= e($curriculo->id . '. ' . $curriculo->descripcion()) ?>
              <?php if ($curriculo->videoCurriculum !== null): ?>
                — <a href="<?= e($curriculo->videoCurriculum) ?>">ver vídeo</a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <p><a href="RA4_altaCurriculo.php">Dar de alta un currículo</a></p>
    </body>
    </html>
    ```

4. **Pruébalo.** Abre `http://localhost/RA4_altaCurriculo.php`, con las herramientas de desarrollo (F12) abiertas. En cada caso, anota en un comentario PHP de la página qué observas:
    - Da de alta dos currículos de ciclos distintos. En la pestaña **Red**, busca la petición POST: ¿qué código de estado tiene y qué cabeceras `Set-Cookie` y `Location` lleva su respuesta? ¿Qué petición viene justo después, y con qué método?
    - En el listado, pulsa F5. ¿Vuelve a aparecer el mensaje? ¿Te pregunta el navegador si quieres reenviar el formulario?
    - Vuelve al alta: ¿qué ciclo aparece elegido? En **Aplicación** (Chrome) o **Almacenamiento** (Firefox) → **Cookies**, mira las dos cookies del sitio. ¿Cuál tiene fecha de caducidad y cuál es "de sesión"?
    - Abre el listado en una **ventana privada**: ¿qué currículos ves, y por qué?
    - Borra la cookie `PHPSESSID` desde las herramientas de desarrollo y recarga el listado. ¿Qué ha pasado con los currículos? ¿Siguen en el servidor?
    - Cambia el valor de `ultimo_ciclo` por `FP` y recarga el alta. ¿Se produce algún error?
5. **Investiga.** Además de las cookies, el navegador ofrece otro almacén en el cliente: **_Web Storage_** (`localStorage` y `sessionStorage`), que se usa desde JavaScript. Busca en la documentación de MDN en qué se diferencia de una cookie. ¿Recibe el servidor su contenido en cada petición? ¿Serviría para guardar el identificador de sesión de forma segura, teniendo en cuenta lo que has leído sobre `HttpOnly`? Responde en un comentario de la página.

> **Puente al Bloque 5.** En _Laravel_, `session_start()` lo hace el framework en cada petición; la sesión se usa con `session('clave')`, y `redirect(...)->with('mensaje', '…')` deja un mensaje _flash_ que desaparece solo tras mostrarse. Incluso `old('alumno')` funciona así: el framework guarda lo escrito en la sesión antes de redirigir. Y las cookies de _Laravel_ van **cifradas**, de modo que el usuario no puede leerlas ni cambiarlas como has hecho en el ejercicio 4.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 3, copia el archivo [RA4_1_EstadoTest.php](./materiales/ejercicios-vanilla/tests/RA4_1_EstadoTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA4_1_EstadoTest.php
```

El test hace lo mismo que un navegador:
- envía el formulario por POST y comprueba la redirección;
- guarda las cookies que recibe y las devuelve en las peticiones siguientes, para comprobar que el listado recuerda el alta y que el mensaje se muestra una sola vez;
- revisa los atributos de las dos cookies.

Necesita `nginx` y `php-fpm` levantados.

Si todo está bien: `OK (7 tests, 18 assertions)`.

---

**Siguiente:** [4.2. Autenticación de usuarios: _login_ básico](./RA4_2_login.md)
