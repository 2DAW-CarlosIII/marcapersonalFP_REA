# 4.4. Proyecto: área privada de _marcapersonalFP v0_

**Presentación de apoyo** (_RevealJS_): [RA4_4_proyectoAreaPrivada_slides.html](./materiales/slides/RA4_4_proyectoAreaPrivada_slides.html)

Cerramos el Bloque 4, y con él _marcapersonalFP v0_, con un **área privada**. Cada alumno, una vez identificado, puede dar de alta **su** currículo y modificarlo. Nadie más puede tocarlo, y un docente no tiene currículo que gestionar.

Como en 3.8, es un proyecto de integración: apenas aparece nada nuevo, y se combina lo de todo el bloque.

| Apartado | Qué aporta al proyecto |
|---|---|
| 3.8 Alta de un currículo | `ValidadorCurriculo`: validar los datos y construir el `Curriculo` |
| 4.1 _Cookies_ y sesiones | Guardar el currículo en `$_SESSION`; POST/Redirect/GET con mensaje _flash_ |
| 4.2 _Login_ | Saber quién es el usuario (`exigirLogin()`) y cuál es su rol |
| 4.3 Xdebug | Depurar la página si algo no cuadra |

La única pieza nueva es la **autorización**: decidir qué puede hacer un usuario que ya sabemos quién es.

## El diseño: "Mi currículo"

Una sola página privada, `RA4_miCurriculo.php`, con estas reglas:

- **Solo para alumnos.** Un docente que entre recibe un error **403**.
- **Un currículo por alumno**, como la relación uno a uno entre `User` y `Curriculo` de la aplicación (la veremos con _Eloquent_ en el Bloque 6). Se guarda en `$_SESSION['curriculo']`.
- **La misma página da de alta y edita.**
  - Si el alumno no tiene currículo, ve el formulario.
  - Si ya lo tiene, lo ve, con un enlace para editarlo. El enlace vuelve a mostrar el formulario, relleno con sus datos.
  - Al guardar: POST/Redirect/GET y un mensaje _flash_, como en 4.1.

## Autorización: 403 frente a redirigir

Hay dos situaciones distintas, y la respuesta HTTP debe distinguirlas:

| Situación | Respuesta | En nuestro código |
|---|---|---|
| **No sabemos quién es** (no ha iniciado sesión) | Redirigir al _login_. En las API se usa el código **401** _Unauthorized_ | `exigirLogin()` (4.2) |
| **Sabemos quién es, pero no puede** (es docente) | **403** _Forbidden_: identificarse de nuevo no cambiaría nada | `exigirRol()` (nueva) |

`exigirRol()` se apoya en `exigirLogin()`. Primero se asegura de que hay un usuario y después comprueba su rol. Si no es el pedido, responde con un 403 mediante **`http_response_code(403)`** y termina.

## No te fíes del formulario: el alumno sale de la sesión

En 3.8 y 4.1, el nombre del alumno era un campo del formulario. Aquí **no puede serlo**: con las herramientas de desarrollo (F12), cualquiera podría añadir al formulario un campo `alumno` con el nombre de otra persona. La página sabe quién es el usuario porque está en la sesión, y es de ahí de donde toma el nombre.

```php
$datos = [
    'alumno' => $usuario['firstName'] . ' ' . $usuario['lastName'],   // de la sesión
    'ciclo'  => $_POST['ciclo'] ?? '',                                 // del formulario
    'video'  => $_POST['video'] ?? '',                                 // del formulario
];
```

Es una regla general: lo que identifica al usuario, o lo que decide qué datos son "suyos", **nunca** se toma de lo que envía el cliente.

## ¿Y si cierro la sesión?

El currículo **desaparece**. El _logout_ de 4.2 destruye la sesión, y con ella todo lo que guardaba. Y al volver a entrar, `session_regenerate_id()` da a la sesión un identificador nuevo y vacío.

Además, Elena Ruiz, la docente, no podría ver los currículos de sus alumnos aunque quisiéramos. Cada sesión pertenece a un navegador, y la de Elena no tiene acceso a las de Ana o Marcos.

Es la conclusión del bloque: **la sesión sirve para recordar a un usuario mientras navega, pero no es un almacén de datos**. Para que el currículo sobreviva al cierre de sesión y lo puedan consultar otros usuarios, necesitamos guardarlo en un sitio compartido y permanente: la **base de datos**, en el Bloque 6.

## Preparación del entorno de trabajo

Seguimos con el proyecto `vanilla_php`, con todo lo de 4.1 y 4.2: el código común de `src/RA4_comun.php`, `Autenticador` y las páginas de _login_, perfil y _logout_. Si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

Al terminar, estos son los ficheros nuevos o modificados:

```
vanilla_php/
├── public/
│   ├── RA4_miCurriculo.php   ← el área privada del alumno (nuevo)
│   └── RA4_perfil.php        ← + enlace a "Mi currículo" (modificado)
└── src/
    └── RA4_comun.php         ← + exigirRol() (modificado)
```

## Ejercicios

1. **`exigirRol()`.** Añade al final de `src/RA4_comun.php`:

    ```php
    /**
     * Como exigirLogin(), pero además exige un rol. Si el usuario no lo tiene,
     * responde 403 (prohibido) y termina.
     *
     * @return array{email: string, firstName: string, lastName: string, rol: string}
     */
    function exigirRol(string $rol): array
    {
        $usuario = exigirLogin();
        if ($usuario['rol'] !== $rol) {
            http_response_code(403);
            exit('No tienes permiso para ver esta página.');
        }

        return $usuario;
    }
    ```

    `exit()` admite un texto, que se envía como cuerpo de la respuesta antes de terminar.

2. **"Mi currículo".** Crea `public/RA4_miCurriculo.php`:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA4_comun.php';

    iniciarSesion();
    $usuario = exigirRol('alumno');

    $ciclos    = catalogoCiclos();
    $validador = new ValidadorCurriculo($ciclos);
    $nombre    = $usuario['firstName'] . ' ' . $usuario['lastName'];
    $errores   = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // El alumno sale de la sesión, nunca del formulario: cada uno, solo su currículo.
        $datos = [
            'alumno' => $nombre,
            'ciclo'  => $_POST['ciclo'] ?? '',
            'video'  => $_POST['video'] ?? '',
        ];
        $errores = $validador->validar($datos);

        if ($errores === []) {
            $guardado = $validador->crearCurriculo($datos, 1);
            $_SESSION['curriculo'] = [
                'alumno' => $guardado->alumno,
                'ciclo'  => $guardado->ciclo->codCiclo,
                'video'  => $guardado->videoCurriculum ?? '',
            ];
            $_SESSION['mensaje'] = 'Currículo guardado.';

            header('Location: RA4_miCurriculo.php', true, 303);
            exit;
        }
    } else {
        // Al editar, el formulario parte de lo guardado.
        $datos = $_SESSION['curriculo'] ?? ['alumno' => $nombre, 'ciclo' => '', 'video' => ''];
    }

    // Uno por alumno, como User y Curriculo en la aplicación: el id siempre es 1.
    $curriculo = isset($_SESSION['curriculo'])
        ? $validador->crearCurriculo($_SESSION['curriculo'], 1)
        : null;

    // Formulario si no hay currículo, si se ha pedido editar o si hay errores que mostrar.
    $editando = $curriculo === null || isset($_GET['editar']) || $errores !== [];

    $mensaje = $_SESSION['mensaje'] ?? null;
    unset($_SESSION['mensaje']);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Mi currículo — <?= NOMBRE_CENTRO ?></title>
    </head>
    <body>
      <h1><?= NOMBRE_CENTRO ?> — Mi currículo</h1>

      <?php if ($mensaje !== null): ?>
        <p class="exito"><?= e($mensaje) ?></p>
      <?php endif; ?>

      <?php if (!$editando): ?>
        <p><?= e($curriculo->descripcion()) ?></p>
        <p>Ciclo: <?= e($curriculo->ciclo->nombre) ?> — titulación de <?= e($curriculo->ciclo->titulacion()) ?></p>
        <p>Vídeo:
          <?php if ($curriculo->videoCurriculum !== null): ?>
            <a href="<?= e($curriculo->videoCurriculum) ?>">ver vídeo</a>
          <?php else: ?>
            sin vídeo
          <?php endif; ?>
        </p>
        <p><a href="RA4_miCurriculo.php?editar=1">Editar mi currículo</a></p>
      <?php else: ?>
        <?php if ($curriculo === null): ?>
          <p>Todavía no tienes currículo: rellena el formulario para darlo de alta.</p>
        <?php endif; ?>

        <?php if ($errores !== []): ?>
          <ul class="errores">
            <?php foreach ($errores as $error): ?>
              <li><?= e($error) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <form action="RA4_miCurriculo.php" method="post">
          <p>Alumno: <?= e($nombre) ?></p>
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
          <button type="submit">Guardar</button>
        </form>
      <?php endif; ?>

      <p><a href="RA4_perfil.php">Volver a mi área</a></p>
    </body>
    </html>
    ```

    Fíjate en que el formulario **no tiene** campo `alumno`: el nombre solo se muestra.

3. **Un enlace desde el perfil.** En `public/RA4_perfil.php` (4.2), añade justo antes del formulario de cierre de sesión un enlace que solo vean los alumnos:

    ```php
    <?php if ($usuario['rol'] === 'alumno'): ?>
      <p><a href="RA4_miCurriculo.php">Mi currículo</a></p>
    <?php endif; ?>
    ```

    Ocultar el enlace a los docentes es **comodidad, no seguridad**: quien escriba la dirección a mano llega igual a la página. Lo que la protege es `exigirRol()`.

4. **Pruébalo.** Con las herramientas de desarrollo (F12) abiertas, anota en un comentario PHP qué observas:
    - Entra como Ana López: da de alta tu currículo, pulsa F5 en la página de resultado y después edítalo para cambiar de ciclo.
    - Al editar, añade con F12 dentro del formulario un campo `<input name="alumno" value="Otra Persona">` y guarda. ¿Qué nombre aparece? ¿Por qué?
    - Cierra la sesión, vuelve a entrar como Ana y abre "Mi currículo". ¿Qué ha pasado?
    - Entra como Elena Ruiz y escribe a mano la dirección `RA4_miCurriculo.php`. ¿Qué código de estado aparece en la pestaña **Red**? ¿Ves el enlace en su perfil?
5. **Investiga.** Responde en un comentario de la página:
    - ¿Qué diferencia hay entre los códigos de estado **401** y **403**? ¿Por qué una página web redirige al _login_ en lugar de responder 401?
    - Para que Elena pudiera ver los currículos de todos sus alumnos, ¿dónde tendrían que guardarse? ¿Qué datos haría falta guardar junto a cada currículo para saber de qué alumno es? (Pista: el campo `user_id` del modelo `Curriculo`.)

## De _v0_ a _Laravel_: el Bloque 4

_marcapersonalFP v0_ está terminada:
- un listado (2.5);
- un alta validada (3.8);
- estado entre peticiones (4.1);
- usuarios, con _login_ y roles (4.2);
- un área privada (4.4).

En el Bloque 5 la reconstruiremos con _Laravel_. Además de lo que ya recogía la tabla de 3.8, esto es lo que el framework automatiza de este bloque:

| En _v0_ (a mano) | En _Laravel_ |
|---|---|
| `session_start()` con sus opciones | Lo hace el framework en cada petición (`config/session.php`) |
| `$_SESSION['mensaje']` + `unset()` | `redirect()->with('mensaje', '…')` |
| `password_hash()` / `password_verify()` | `Hash::make()` / `Hash::check()` |
| `Autenticador` + `session_regenerate_id()` | `Auth::attempt([...])` (Bloque 5, con _Breeze_) |
| `exigirLogin()` | _Middleware_ `auth` |
| `exigirRol()` + `http_response_code(403)` | _Gates_ y _Policies_, `abort(403)` (Bloque 7) |
| `$_SESSION['curriculo']`, que se pierde | Tabla `curriculos`, con `user_id` (Bloque 6) |

Esto que hicimos a mano, el framework lo automatiza así.

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 y 2, copia el archivo [RA4_4_ProyectoAreaPrivadaTest.php](./materiales/ejercicios-vanilla/tests/RA4_4_ProyectoAreaPrivadaTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA4_4_ProyectoAreaPrivadaTest.php
```

El test inicia sesión como Ana López (`ana-secreta`) y como Elena Ruiz (`docente-secreta`), con los usuarios de 4.2. Comprueba:
- el acceso: sin sesión, redirige al _login_; como docente, responde 403;
- el alta, la edición y el mensaje _flash_;
- que el nombre se toma de la sesión y no del formulario;
- que el currículo se pierde al cerrar la sesión.

Necesita `nginx` y `php-fpm` levantados.

Si todo está bien: `OK (8 tests, 18 assertions)`.

### Todos los tests del Bloque 4 a la vez

Si has ido copiando a `vanilla_php/tests/` el test de cada apartado, ya tienes ahí los cuatro del Bloque 4. Para lanzar **solo** esos, usa `--filter`, como en 3.8:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit --filter RA4_
```

Si todo el Bloque 4 está resuelto: `OK (30 tests, 68 assertions)`.

Y `phpunit` sin opciones lanza los de los Bloques 2, 3 y 4 juntos.

---

**Siguiente:** Bloque 5 — [5.1. Instalación de _Laravel_ y desarrollo colaborativo](./RA5_1_instalacion.md)
