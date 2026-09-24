# 3.6. Formularios web: recuperación, procesamiento y validación

## El formulario: una nueva petición

Hasta ahora, los datos de nuestras páginas estaban escritos en el propio script. Con un **formulario**, por fin es el **usuario** quien los aporta. Recuerda el modelo de 2.1: el navegador hace una petición y el servidor devuelve HTML. Un formulario no cambia ese modelo, solo lo aprovecha: al enviarlo, el navegador hace **una nueva petición HTTP** que lleva dentro los datos escritos, y el servidor la procesa y responde con una nueva página.

```html
<form action="RA3_altaCiclo.php" method="post">
  <input name="codCiclo">
  <input name="nombre">
  <button type="submit">Dar de alta</button>
</form>
```

- **`action`**: a qué URL se envía la petición. Puede ser el mismo script que muestra el formulario.
- **`method`**: el método HTTP, `get` o `post`. Decide **dónde viajan** los datos.
- **`name`**: el nombre con el que llega cada campo al servidor. Un campo sin `name` **no se envía**.

## GET y POST

Con **`method="get"`**, los datos viajan **en la URL**, como una _cadena de consulta_ (_query string_) tras el signo `?`:

```
http://localhost/RA3_busqueda.php?ciclo=DAW
```

Con **`method="post"`**, viajan en el **cuerpo** de la petición, y la URL queda limpia. En ambos casos van en texto plano: POST no "oculta" nada a quien intercepte la comunicación (para eso está HTTPS).

| | GET | POST |
|---|---|---|
| Dónde van los datos | En la URL | En el cuerpo de la petición |
| Se pueden guardar en marcadores o compartir | Sí | No |
| Uso adecuado | **Consultar**: buscar, filtrar, paginar | **Modificar**: dar de alta, editar, borrar |

La regla práctica: si la petición solo **lee** datos, GET; si **cambia** algo en el servidor, POST.

## Recuperar los datos: `$_GET`, `$_POST` y `$_SERVER`

En 2.4 presentamos las **superglobales**: arrays que PHP rellena automáticamente en cada petición y que son visibles en cualquier ámbito. Ahora las usamos de verdad:

- **`$_GET`**: los datos de la cadena de consulta de la URL.
- **`$_POST`**: los datos enviados en el cuerpo de una petición POST.
- **`$_SERVER`**: información sobre la petición y el servidor. La que más usaremos es `$_SERVER['REQUEST_METHOD']`, que vale `'GET'` o `'POST'`.

Son arrays asociativos (3.3) cuyas claves son los `name` de los campos:

```php
<?php
  $ciclo = $_GET['ciclo'] ?? '';   // lo que se escribió en <input name="ciclo">
?>
```

Tres cosas que debes tener siempre presentes al leerlos:

1. **Un campo puede no llegar.** Si el usuario llega a la página sin enviar el formulario, o si alguien construye la petición a mano, la clave no existe. Por eso usamos siempre `??` (3.1): sin él, PHP daría un _warning_ (`Undefined array key`).
2. **Todo llega como `string`.** Aunque el campo sea `<input type="number">`, en `$_POST['horas']` recibirás `'2000'`, no `2000`. Convertirlo y comprobarlo es trabajo nuestro (lo veremos enseguida con `filter_var()`).
3. **Casillas de verificación y campos con `[]`.** Una casilla (`checkbox`) sin marcar **no se envía**; y un campo cuyo `name` termina en `[]` (`name="modulos[]"`) llega como un **array** con todos los valores elegidos.

> Existe también `$_REQUEST`, que mezcla `$_GET`, `$_POST` y las _cookies_. No la usaremos: conviene saber siempre de dónde viene cada dato.

### Mostrar y procesar en el mismo script

Es muy habitual que un mismo script **muestre** el formulario (cuando se pide con GET, al llegar a la página) y lo **procese** (cuando se envía con POST). `$_SERVER['REQUEST_METHOD']` nos dice en qué caso estamos:

```php
<?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // se ha enviado el formulario: procesar $_POST
  }
  // en cualquier caso, mostrar la página (con el formulario, los errores o el resultado)
?>
```

## Escapar la salida: `htmlspecialchars()`

En 3.2 avisamos de que `<?= ?>` escribe los valores **tal cual**. Mientras los datos los escribíamos nosotros no importaba; ahora vienen del usuario, y el usuario puede escribir **HTML**. Imagina que en el buscador alguien escribe:

```
<script>alert('XSS')</script>
```

Si la página hace `<?= $ciclo ?>`, el navegador recibe esa etiqueta `<script>` dentro del HTML y **la ejecuta**. Es un ataque de **_Cross-Site Scripting_ (XSS)**: en vez de un inofensivo `alert()`, el código inyectado podría robar la sesión del usuario o modificar la página. Y como los datos pueden ir en la URL (GET), basta con que alguien haga clic en un enlace malicioso.

La defensa es **escapar** todo dato externo al escribirlo en el HTML, convirtiendo los caracteres especiales (`<`, `>`, `&`, `"`, `'`) en sus entidades HTML (`&lt;`, `&gt;`...), que el navegador **muestra** en lugar de **interpretar**. Es lo que hace `htmlspecialchars()`. Como la usaremos constantemente, la envolvemos en una función de nombre corto:

```php
<?php
  function e(string $texto): string
  {
      return htmlspecialchars($texto);
  }
?>
<p>Resultados para: <?= e($ciclo) ?></p>
```

Desde PHP 8.1, `htmlspecialchars()` escapa por defecto también las comillas simples y trabaja en UTF-8, así que no hacen falta más argumentos.

> **Regla:** todo dato que venga del exterior (formularios, URL, y más adelante base de datos) se **escapa al mostrarlo**, con `e()`. Cuesta poco y cierra la puerta al XSS.

> **Puente al Bloque 4.** _Laravel_ tiene precisamente un _helper_ global llamado `e()` que hace lo mismo, y las dobles llaves de _Blade_ (`{{ $ciclo }}`) lo llaman automáticamente: por eso en _Blade_ escapar es lo normal y no escapar exige escribirlo a propósito (`{!! $ciclo !!}`).

## Validar en el servidor

**Validar** es comprobar que los datos recibidos cumplen las reglas antes de usarlos: que los obligatorios no estén vacíos, que un número sea un número y esté en su rango, que un valor esté entre los permitidos...

HTML5 también valida (atributos `required`, `type="number"`, `min`, `max`, `pattern`...), y conviene usarlo porque avisa al usuario sin esperar al servidor. Pero **no es una defensa**: se desactiva con las herramientas de desarrollo del navegador, y cualquiera puede construir una petición HTTP a mano sin pasar por nuestro formulario (lo harás en el ejercicio de investigación). La validación que de verdad cuenta es **la del servidor**.

### `filter_var()`

PHP incluye la función `filter_var()` para validar valores habituales. Devuelve el valor **ya convertido** si es válido, o `false` si no lo es:

```php
<?php
  filter_var('2000', FILTER_VALIDATE_INT);    // 2000 (int)
  filter_var('dos mil', FILTER_VALIDATE_INT); // false

  // con rango
  filter_var('3000', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => 2000]]);  // false

  filter_var('https://youtu.be/curriculo1', FILTER_VALIDATE_URL);  // la URL: es válida
  filter_var('ana@example.com', FILTER_VALIDATE_EMAIL);           // el email: es válido
?>
```

Existe también `filter_input(INPUT_POST, 'horas', FILTER_VALIDATE_INT)`, que lee y valida directamente de la petición. Nosotros usaremos `filter_var()` sobre un array de datos, porque así la función de validación sirve para cualquier array (y se puede probar con un test sin necesidad de una petición real).

### Una función de validación

Reunimos las reglas en una función que recibe los datos (por ejemplo, `$_POST`) y devuelve un array de **errores**, con el nombre del campo como clave. Si el array está vacío, los datos son válidos:

```php
<?php
function validarCiclo(array $datos): array
{
    $errores = [];

    $codCiclo = trim($datos['codCiclo'] ?? '');
    if ($codCiclo === '') {
        $errores['codCiclo'] = 'El código del ciclo es obligatorio.';
    } elseif (!ctype_upper($codCiclo) || strlen($codCiclo) > 5) {
        $errores['codCiclo'] = 'El código debe tener como máximo 5 letras mayúsculas.';
    }

    $nombre = trim($datos['nombre'] ?? '');
    if ($nombre === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    }

    $grado = $datos['grado'] ?? '';
    if (!in_array($grado, ['Medio', 'Superior'], true)) {
        $errores['grado'] = 'El grado debe ser Medio o Superior.';
    }

    $horas = filter_var($datos['horas'] ?? '', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1000, 'max_range' => 2000],
    ]);
    if ($horas === false) {
        $errores['horas'] = 'Las horas deben ser un número entero entre 1000 y 2000.';
    }

    return $errores;
}
?>
```

- **`trim()`** elimina los espacios del principio y del final: sin él, un nombre de solo espacios pasaría por "no vacío".
- **`ctype_upper()`** comprueba que todos los caracteres son letras mayúsculas.
- Para el grado, comprobamos que el valor está **entre los permitidos**: aunque en el formulario sea un `<select>`, nada impide enviar otra cosa.

### Mostrar los errores y conservar lo escrito

Cuando hay errores, la página vuelve a mostrar el formulario con la lista de errores y, para no obligar al usuario a escribirlo todo de nuevo, **rellena cada campo con lo que envió** (escapado, por supuesto):

```php
<input name="nombre" value="<?= e($datos['nombre'] ?? '') ?>">
```

Lo verás completo en el ejercicio 4.

### ¿Y después de validar?

Si los datos son válidos, lo normal sería **guardarlos** (en una base de datos, Bloque 5) y **redirigir** a otra página con una petición GET. Esa redirección, llamada patrón **POST/Redirect/GET**, evita que al recargar la página (F5) el navegador vuelva a enviar el formulario y se dé de alta dos veces lo mismo. Pero para mostrar un mensaje de "dado de alta" en la página a la que redirigimos necesitamos recordar algo **entre dos peticiones**, y ya vimos en 2.4 que cada petición empieza de cero: hace falta una **sesión**, que llega en el Bloque 4. Por ahora, tras validar, nos limitaremos a **mostrar el resultado** en la misma respuesta.

> **Puente al Bloque 4.** En _Laravel_, leer datos será `$request->input('nombre')`; validar, `$request->validate(['nombre' => 'required', 'horas' => 'integer|between:1000,2000', ...])`; conservar lo escrito, `old('nombre')`; y redirigir con un mensaje, `redirect()->with(...)`. Además, protegerá cada formulario POST frente a otro ataque, el **CSRF**, con un _token_ (`@csrf`) que también depende de las sesiones. Todo lo que aquí hacemos a mano.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php` y su carpeta `src/` ([3.4](./RA3_4_funciones.md#preparación-del-entorno-de-trabajo)). Si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

## Ejercicios

1. **Funciones de formulario.** Crea `src/RA3_formularios.php` (con `declare(strict_types=1);` y sin salida) con las funciones `e()` y `validarCiclo()` de este apartado.
2. **Búsqueda con GET.** Crea `public/RA3_busqueda.php`. Reutiliza `curriculosDeCiclo()` de 3.4:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA3_funciones.php';
    require_once __DIR__ . '/../src/RA3_formularios.php';

    $curriculos = [
        ['alumno' => 'Ana López',    'ciclo' => 'DAW',  'video' => 'https://youtu.be/curriculo1'],
        ['alumno' => 'Marcos Pérez', 'ciclo' => 'DAM',  'video' => 'https://youtu.be/curriculo2'],
        ['alumno' => 'Laura García', 'ciclo' => 'ASIR', 'video' => 'https://youtu.be/curriculo3'],
        ['alumno' => 'Pablo Ruiz',   'ciclo' => 'DAW',  'video' => ''],
    ];

    $ciclo = trim($_GET['ciclo'] ?? '');
    $resultados = $ciclo === '' ? $curriculos : curriculosDeCiclo($curriculos, $ciclo);
    ?>
    <form action="RA3_busqueda.php" method="get">
      <label>Ciclo <input name="ciclo" value="<?= e($ciclo) ?>"></label>
      <button type="submit">Buscar</button>
    </form>

    <?php if ($ciclo !== ''): ?>
      <p>Resultados para: <?= e($ciclo) ?></p>
    <?php endif; ?>
    <ul>
      <?php foreach ($resultados as $curriculo): ?>
        <li class="curriculo"><?= e($curriculo['alumno']) ?> — <?= e($curriculo['ciclo']) ?></li>
      <?php endforeach; ?>
    </ul>
    ```

    Busca `DAW` y observa cómo cambia la URL. Después escribe directamente en la barra de direcciones `http://localhost/RA3_busqueda.php?ciclo=ASIR`: no hace falta el formulario para hacer la petición.
3. **XSS.** En el buscador, escribe `<b>DAW</b>` y busca. Mira el código fuente (`Ctrl+U`): ¿cómo ha llegado al HTML? Ahora cambia **temporalmente** `<?= e($ciclo) ?>` del párrafo "Resultados para" por `<?= $ciclo ?>` y repite la búsqueda: ¿qué ves ahora? Restaura `e()` al terminar.
4. **Alta con POST.** Crea `public/RA3_altaCiclo.php`, que muestra y procesa el formulario:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA3_formularios.php';

    $datos   = [];
    $errores = [];
    $enviado = $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($enviado) {
        $datos   = $_POST;
        $errores = validarCiclo($datos);
    }
    ?>
    <h1>Alta de ciclo</h1>

    <?php if ($enviado && $errores === []): ?>
      <p class="exito">Ciclo dado de alta: <?= e($datos['codCiclo']) ?> — <?= e($datos['nombre']) ?></p>
    <?php else: ?>
      <?php if ($errores !== []): ?>
        <ul class="errores">
          <?php foreach ($errores as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form action="RA3_altaCiclo.php" method="post">
        <label>Código <input name="codCiclo" value="<?= e($datos['codCiclo'] ?? '') ?>"></label>
        <label>Nombre <input name="nombre" value="<?= e($datos['nombre'] ?? '') ?>"></label>
        <label>Grado
          <select name="grado">
            <?php foreach (['Medio', 'Superior'] as $grado): ?>
              <option value="<?= $grado ?>" <?= ($datos['grado'] ?? '') === $grado ? 'selected' : '' ?>><?= $grado ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Horas <input name="horas" type="number" value="<?= e($datos['horas'] ?? '') ?>"></label>
        <button type="submit">Dar de alta</button>
      </form>
    <?php endif; ?>
    ```

    Pruébalo en `http://localhost/RA3_altaCiclo.php`: envíalo vacío, con horas `3000`, con un código en minúsculas... y comprueba que los campos conservan lo que escribiste. Por último, envíalo con datos válidos (`DAW`, `Desarrollo de Aplicaciones Web`, `Superior`, `2000`) y pulsa F5: ¿qué te pregunta el navegador, y por qué?
5. **Investiga.** Añade el atributo `required` al campo `nombre` y comprueba que el navegador ya no deja enviar el formulario vacío. Ahora sáltate esa validación de dos formas: (a) quitando el atributo con las herramientas de desarrollo del navegador (F12) antes de enviar; (b) sin navegador, haciendo la petición POST a mano con `curl` desde el contenedor `workspace` (ejecútalo en la carpeta `laradock/`): `docker compose exec workspace curl -d "codCiclo=DAW&nombre=&grado=Superior&horas=2000" http://nginx/RA3_altaCiclo.php`. ¿Qué responde el servidor en cada caso? Escribe en un comentario por qué la validación de HTML5 no basta.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1, 2 y 4, copia el archivo [RA3_6_FormulariosTest.php](./materiales/ejercicios-vanilla/tests/RA3_6_FormulariosTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA3_6_FormulariosTest.php
```

El test llama directamente a `e()` y a `validarCiclo()` con distintos datos, y además hace peticiones reales a tus páginas: una GET con `?ciclo=...` en la URL y dos POST, como si se enviara el formulario (una con datos válidos y otra con errores).

Si todo está bien: `OK (8 tests, 15 assertions)`.

---

**Siguiente:** [3.7. Comentarios](./RA3_7_comentarios.md)
