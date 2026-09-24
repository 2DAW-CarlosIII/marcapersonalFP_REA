# 3.8. Proyecto: alta y validación de un currículo

Cerramos el Bloque 3 con la segunda pieza de _marcapersonalFP v0_. En 2.5 construimos un **listado** de currículos a partir de un array escrito en el código; ahora construiremos el **alta**: un formulario en el que un alumno introduce los datos de su currículo, el servidor los **valida** y, si son correctos, crea con ellos un objeto `Curriculo` y muestra el resultado.

Es un proyecto de integración: no aparece ningún concepto nuevo de importancia, sino que se combinan los de todo el bloque.

| Apartado | Qué aporta al proyecto |
|---|---|
| 3.1 Decisiones | Validar cada campo; mostrar el formulario o el resultado |
| 3.2 Bucles | Generar las opciones del desplegable y la lista de errores |
| 3.3 Arrays | El catálogo de ciclos, los datos recibidos, los errores |
| 3.4 Funciones | Tipos declarados, `strict_types`, código en `src/` con `require_once` |
| 3.5 POO | `Ciclo` y `Curriculo`, y una clase nueva: `ValidadorCurriculo` |
| 3.6 Formularios | `$_POST`, `REQUEST_METHOD`, `filter_var()`, `e()`, conservar lo escrito |
| 3.7 Comentarios | Docblocks en la clase nueva |

## El diseño

Queremos un formulario con tres campos, que corresponden a lo que necesita el constructor de `Curriculo` (3.5):

- **Alumno** (obligatorio, como mucho 100 caracteres).
- **Ciclo** (obligatorio, uno de los que ofrece el centro), elegido en un `<select>`.
- **Vídeo de presentación** (opcional; si se indica, debe ser una URL `https://`).

¿Dónde ponemos la validación? En 3.6 usamos una **función**, `validarCiclo()`, porque las reglas eran fijas. Ahora hay una regla que depende de **datos**: el ciclo tiene que estar entre los que ofrece el centro. Así que el validador necesita **conocer el catálogo de ciclos**. Es un caso claro para una **clase**: el catálogo se le pasa una vez, en el constructor, y queda guardado en una propiedad privada para que lo usen sus métodos:

```php
$validador = new ValidadorCurriculo($ciclos);
$errores = $validador->validar($_POST);
```

La clase tendrá dos métodos:

- **`validar(array $datos): array`**: devuelve los errores, por campo, igual que `validarCiclo()`.
- **`crearCurriculo(array $datos, int $id): Curriculo`**: construye el objeto `Curriculo` a partir de datos **ya validados**.

Separar "comprobar" de "construir" permite que la página decida qué hacer entre medias (mostrar errores o seguir). Y como `ValidadorCurriculo` es una clase nueva, **no modificamos** `Ciclo` ni `Curriculo`: siguen tal cual las dejamos en 3.5.

## Dos detalles nuevos en la validación

**Contar caracteres, no bytes.** Para limitar el nombre a 100 caracteres no sirve `strlen()`, que cuenta **bytes**: en UTF-8, la `ó` de "López" ocupa 2 bytes, así que `strlen('López')` vale 6. La función que cuenta **caracteres** es **`mb_strlen()`** (_multibyte_): `mb_strlen('López')` vale 5.

**Validar además de escapar.** `e()` impide que un dato se interprete como HTML, pero no basta para una URL que va a ir en un `href`: la cadena `javascript:alert('XSS')` no tiene ningún carácter que escapar y, sin embargo, al hacer clic en el enlace **ejecutaría código**. Por eso, además de `filter_var(..., FILTER_VALIDATE_URL)`, exigimos que el vídeo empiece por `https://` con **`str_starts_with()`**. Escapar y validar no son alternativas: se hacen las dos cosas.

## ¿Y dónde se guarda el currículo?

En **ninguna parte**. Recuerda 2.4: cada petición ejecuta el script desde cero, y todo lo que se crea en ella (variables, arrays, objetos) desaparece al terminar la respuesta. El objeto `Curriculo` existe solo mientras se genera la página que lo muestra; si recargas, ya no está. Para guardarlo de verdad necesitamos una **base de datos**, y para redirigir tras el alta con un mensaje (el patrón POST/Redirect/GET de 3.6), una **sesión**. Nuestro alta termina, por ahora, en **validar y mostrar**; el resto llegará con _Laravel_.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php` y su carpeta `src/`, donde ya están las clases de 3.5 y las funciones de 3.6 (`e()`). Si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

Al terminar, estos son los ficheros que intervienen en el proyecto:

```
vanilla_php/
├── public/
│   └── RA3_altaCurriculo.php   ← la página (nuevo)
└── src/
    ├── Modelo.php              (3.5)
    ├── Publicable.php          (3.5)
    ├── Ciclo.php               (3.5)
    ├── Curriculo.php           (3.5)
    ├── RA3_formularios.php     (3.6, por e())
    └── ValidadorCurriculo.php  ← la clase de validación (nuevo)
```

## Ejercicios

1. **La clase `ValidadorCurriculo`.** Crea `src/ValidadorCurriculo.php`:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/Curriculo.php';

    /**
     * Valida los datos del formulario de alta de un currículo y, si son
     * válidos, construye con ellos el objeto Curriculo.
     */
    class ValidadorCurriculo
    {
        /**
         * @param array<string, Ciclo> $ciclos Ciclos que ofrece el centro, indexados por su código.
         */
        public function __construct(
            private readonly array $ciclos,
        ) {
        }

        /**
         * @param array<string, mixed> $datos Datos recibidos, normalmente $_POST.
         * @return array<string, string> Errores por campo; vacío si los datos son válidos.
         */
        public function validar(array $datos): array
        {
            $errores = [];

            $alumno = trim($datos['alumno'] ?? '');
            if ($alumno === '') {
                $errores['alumno'] = 'El nombre del alumno es obligatorio.';
            } elseif (mb_strlen($alumno) > 100) {
                $errores['alumno'] = 'El nombre del alumno no puede superar los 100 caracteres.';
            }

            $codCiclo = $datos['ciclo'] ?? '';
            if (!array_key_exists($codCiclo, $this->ciclos)) {
                $errores['ciclo'] = 'Elige uno de los ciclos de la lista.';
            }

            $video = trim($datos['video'] ?? '');
            if ($video !== ''
                && (filter_var($video, FILTER_VALIDATE_URL) === false || !str_starts_with($video, 'https://'))) {
                $errores['video'] = 'El vídeo debe ser una URL que empiece por https://';
            }

            return $errores;
        }

        /**
         * Construye el currículo a partir de datos que ya han pasado validar().
         *
         * @param array<string, mixed> $datos
         */
        public function crearCurriculo(array $datos, int $id): Curriculo
        {
            $video = trim($datos['video'] ?? '');

            return new Curriculo(
                $id,
                trim($datos['alumno']),
                $this->ciclos[$datos['ciclo']],
                $video === '' ? null : $video,
            );
        }
    }
    ```

    Fíjate en `private readonly array $ciclos`: el catálogo se fija al crear el validador y ni se ve ni se puede cambiar desde fuera. Y en que `crearCurriculo()` no busca el ciclo por su código en ninguna lista nueva: usa el **mismo objeto** `Ciclo` del catálogo.
2. **La página.** Crea `public/RA3_altaCurriculo.php`:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA3_formularios.php';
    require_once __DIR__ . '/../src/ValidadorCurriculo.php';

    const NOMBRE_CENTRO = 'CIFP Carlos III';

    // Catálogo de ciclos del centro. En el Bloque 5 saldrá de la base de datos.
    $ciclos = [
        'DAW'  => new Ciclo(1, 'DAW', 'Desarrollo de Aplicaciones Web', Ciclo::GRADO_SUPERIOR),
        'DAM'  => new Ciclo(2, 'DAM', 'Desarrollo de Aplicaciones Multiplataforma', Ciclo::GRADO_SUPERIOR),
        'ASIR' => new Ciclo(3, 'ASIR', 'Administración de Sistemas Informáticos en Red', Ciclo::GRADO_SUPERIOR),
        'SMR'  => new Ciclo(4, 'SMR', 'Sistemas Microinformáticos y Redes', Ciclo::GRADO_MEDIO),
    ];

    $validador = new ValidadorCurriculo($ciclos);
    $datos     = [];
    $errores   = [];
    $curriculo = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos   = $_POST;
        $errores = $validador->validar($datos);

        if ($errores === []) {
            // Sin base de datos no hay quien asigne el id: usamos 1 (Bloque 5).
            $curriculo = $validador->crearCurriculo($datos, 1);
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Alta de currículo — <?= NOMBRE_CENTRO ?></title>
    </head>
    <body>
      <h1><?= NOMBRE_CENTRO ?> — Alta de currículo</h1>

      <?php if ($curriculo !== null): ?>
        <section class="resultado">
          <h2>Currículo validado</h2>
          <p class="exito"><?= e($curriculo->descripcion()) ?></p>
          <p>Ciclo: <?= e($curriculo->ciclo->nombre) ?> — titulación de <?= e($curriculo->ciclo->titulacion()) ?></p>
          <p>Vídeo:
            <?php if ($curriculo->videoCurriculum !== null): ?>
              <a href="<?= e($curriculo->videoCurriculum) ?>">ver vídeo</a>
            <?php else: ?>
              sin vídeo
            <?php endif; ?>
          </p>
          <p><a href="RA3_altaCurriculo.php">Dar de alta otro currículo</a></p>
        </section>
      <?php else: ?>
        <?php if ($errores !== []): ?>
          <ul class="errores">
            <?php foreach ($errores as $error): ?>
              <li><?= e($error) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <form action="RA3_altaCurriculo.php" method="post">
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
                  <option value="<?= e($codigo) ?>" <?= ($datos['ciclo'] ?? '') === $codigo ? 'selected' : '' ?>><?= e($ciclo->descripcion()) ?></option>
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
      <?php endif; ?>
    </body>
    </html>
    ```

    La opción "— Elige un ciclo —" tiene `value=""`: si el usuario no elige, llega un string vacío, que no está en el catálogo, y `validar()` lo rechaza.
3. **Pruébalo.** Abre `http://localhost/RA3_altaCurriculo.php` y comprueba, uno por uno, estos casos. En cada uno, anota en un comentario PHP de la página qué error aparece y qué regla de `validar()` lo produce:
    - Enviar el formulario vacío.
    - Un nombre con espacios al principio y al final (`   Ana López   `): ¿cómo aparece en el resultado?
    - Vídeo `http://youtu.be/curriculo1` (sin la _s_) y vídeo `javascript:alert('XSS')`. Para el segundo tendrás que quitar antes `type="url"` con las herramientas de desarrollo (F12), porque el navegador ya lo rechaza; ¿por qué no basta con eso?
    - Con las herramientas de desarrollo, cambia el `value` de una opción del desplegable por `FP` y envía.
    - Datos correctos, con vídeo y sin vídeo.
4. **Investiga: del PHP plano a _Laravel_.** En _Laravel_, las reglas de validación se escriben como texto. Busca en la documentación de _Laravel_ ("Validation", "Available Validation Rules") qué reglas usarías para expresar cada una de las de `validar()` (pistas: `required`, `max`, `in`, `nullable`, `url`, `starts_with`). Escribe en un comentario el array de reglas que le pasarías a `$request->validate([...])`. En el Bloque 4 lo comprobarás.

## De _v0_ a _Laravel_

Con este proyecto, _marcapersonalFP v0_ tiene ya un listado (2.5) y un alta. En el Bloque 4 reconstruiremos ambos con _Laravel_, y cada pieza que aquí hemos hecho a mano tendrá su equivalente automatizado:

| En _v0_ (a mano) | En _Laravel_ |
|---|---|
| Un script en `public/` por página | **Rutas** y **controladores** |
| HTML con `<?php if (): ?>`, `foreach:` y `<?= e() ?>` | Plantillas **Blade**: `@if`, `@foreach`, `{{ }}` |
| `require_once` de cada fichero de `src/` | **Autoload** de _Composer_ con espacios de nombres |
| `class Curriculo extends Modelo` | **Modelos Eloquent**: `class Curriculo extends Model` |
| El catálogo `$ciclos` escrito en el código | `Ciclo::all()`, desde la **base de datos** (Bloque 5) |
| `ValidadorCurriculo::validar()` | `$request->validate([...])` o una clase _Form Request_ |
| `value="<?= e($datos['alumno'] ?? '') ?>"` | `old('alumno')` |
| El currículo se pierde al terminar la petición | Se **guarda** (Bloque 5) y se **redirige** con un mensaje de **sesión** (Bloque 4) |

Esto que hicimos a mano, el framework lo automatiza así.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 y 2, copia el archivo [RA3_8_ProyectoAltaCurriculoTest.php](./materiales/ejercicios-vanilla/tests/RA3_8_ProyectoAltaCurriculoTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA3_8_ProyectoAltaCurriculoTest.php
```

El test crea un `ValidadorCurriculo` con su propio catálogo de ciclos y lo prueba con datos válidos e inválidos, y después envía el formulario de tu página por POST, como lo haría el navegador.

Si todo está bien: `OK (9 tests, 22 assertions)`.

### Todos los tests del Bloque 3 a la vez

Si has ido copiando a `vanilla_php/tests/` el test de cada apartado, ya tienes ahí los ocho del Bloque 3 (y los cinco del Bloque 2). Para lanzar **solo** los del Bloque 3, usa la opción `--filter`, que ejecuta únicamente los tests cuyo nombre (incluido el de su clase) contiene el texto indicado:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit --filter RA3_
```

Si todo el Bloque 3 está resuelto: `OK (44 tests, 114 assertions)`.

Y si quieres comprobar que no has roto nada del Bloque 2 por el camino, ejecuta `phpunit` sin opciones, como en 2.5: se lanzarán los tests de los dos bloques.

---

**Siguiente:** Bloque 4 — Desarrollo de aplicaciones web con código embebido: introducción a _Laravel_ (RA4 + RA5).
