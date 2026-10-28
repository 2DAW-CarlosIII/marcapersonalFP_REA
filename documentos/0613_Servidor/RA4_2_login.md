# 4.2. Autenticación de usuarios: _login_ básico

**Presentación de apoyo** (_RevealJS_): [RA4_2_login_slides.html](./materiales/slides/RA4_2_login_slides.html)

En 4.1 la aplicación aprendió a **recordar** a un navegador. Pero no sabe **quién** está detrás de ese navegador. Cualquiera puede dar de alta un currículo a nombre de cualquiera, y no hay forma de que un docente vea algo que un alumno no deba ver.

En este apartado añadimos a _marcapersonalFP v0_ un **_login_**: un formulario en el que el usuario demuestra quién es, y una sesión que lo recuerda mientras navega.

## Identificarse, autenticarse, autorizar

Son tres ideas distintas, y conviene no mezclarlas:

- **Identificación**: el usuario dice **quién es**. Por ejemplo, escribe su email.
- **Autenticación**: el usuario **lo demuestra**, con algo que solo él debería tener. Lo más habitual es una contraseña.
- **Autorización**: una vez se sabe quién es, la aplicación decide **qué puede hacer**. Por ejemplo, un docente puede ver todos los currículos y un alumno, solo el suyo.

Este apartado trata de las dos primeras. La autorización aparece al final, con los **roles** (`alumno`, `docente`), y la desarrollaremos en el proyecto 4.4 y, ya con _Laravel_, en el Bloque 7.

## Mecanismos de autenticación

Hay varias formas de que un usuario demuestre quién es en una aplicación web:

| Mecanismo | Cómo funciona | Dónde lo veremos |
|---|---|---|
| **Formulario + sesión** | El usuario envía email y contraseña; si son correctos, la sesión recuerda quién es | Aquí, a mano; con _Breeze_ en el Bloque 5 |
| **HTTP _Basic_** | El navegador pide usuario y contraseña en un cuadro propio y los envía en **cada** petición, en la cabecera `Authorization` | Solo como contraste: no tiene forma de cerrar la sesión, ni una página de _login_ propia |
| **_Tokens_** | Tras autenticarse, el cliente recibe un código y lo envía en cada petición. Es lo habitual en las API | Bloque 7 (_Sanctum_) |
| **Proveedor externo** | "Iniciar sesión con Google": otra aplicación autentica al usuario y nos lo confirma (_OAuth 2_, _OpenID Connect_) | Fuera del alcance del módulo |
| **Segundo factor** | Además de la contraseña, un código de una aplicación del móvil (2FA). Las _passkeys_ sustituyen la contraseña por una clave guardada en el dispositivo | Fuera del alcance del módulo |

Todos ellos acaban en lo mismo: en cada petición, la aplicación tiene que poder saber qué usuario la hace. Con formulario + sesión, ese dato vive en `$_SESSION`.

![Qué demuestra quién eres en cada petición, según el mecanismo: con formulario y sesión, la cookie de sesión; con HTTP Basic, el usuario y la contraseña en cada petición; con un token, la cabecera Authorization Bearer; con un proveedor externo, el paso por Google y después la sesión propia. El segundo factor añade un paso al identificarse](./images/RA4_2_mecanismos.svg)

El diagrama se fija en **lo que viaja** entre el navegador y el servidor:

- la flecha **discontinua** es el paso de identificarse, que ocurre una sola vez;
- la flecha **continua** es lo que acompaña a **cada** petición siguiente.

Fíjate en HTTP _Basic_: lo que viaja siempre son el usuario y la contraseña. Van solo codificados en _Base64_, que cualquiera puede decodificar, así que únicamente tiene sentido sobre HTTPS.

## Las contraseñas no se guardan: se guarda su _hash_

Una aplicación **nunca** debe guardar las contraseñas tal cual. Si alguien consiguiera el fichero o la base de datos de usuarios, tendría las contraseñas de todos. Y, como mucha gente repite contraseña, también tendría acceso a sus otras cuentas.

Lo que se guarda es un **_hash_**: el resultado de aplicar a la contraseña una función que **no se puede invertir**. Para comprobar una contraseña, no se "descifra" el _hash_: se vuelve a calcular con la contraseña recibida y se compara.

PHP lo resuelve con dos funciones:

```php
<?php
$hash = password_hash('ana-secreta', PASSWORD_DEFAULT);
// $2y$12$Wq3... (60 caracteres, distinto cada vez)

password_verify('ana-secreta', $hash);   // true
password_verify('otra-cosa', $hash);     // false
?>
```

- **`password_hash()`** usa un algoritmo diseñado para contraseñas (por defecto, _bcrypt_). Es **lento a propósito**, para que probar millones de contraseñas sea costoso.
- Cada vez añade una **sal** (_salt_) aleatoria, que queda guardada dentro del propio _hash_. Por eso, si lo ejecutas dos veces con la misma contraseña, obtienes dos _hashes_ distintos. Y por eso dos usuarios con la misma contraseña no tienen el mismo _hash_.
- **`password_verify()`** extrae la sal del _hash_, rehace el cálculo y compara.

Funciones como `md5()` o `sha1()` **no sirven** para contraseñas: son rapidísimas (justo lo contrario de lo que queremos) y no llevan sal.

> **Cuidado con las comillas.** Un _hash_ empieza por `$2y$`. Si lo escribes entre comillas **dobles**, PHP intentará sustituir `$2y` y lo que sigue como si fueran variables, y el _hash_ quedará destrozado. Los _hashes_ van siempre entre comillas **simples**.

## Los usuarios de _v0_

Todavía no tenemos base de datos. Guardaremos los usuarios en un array, en `src/RA4_comun.php`, indexado por email, con los campos del modelo `User` de la aplicación (`firstName`, `lastName`), un **rol** y el _hash_ de la contraseña:

| Email | Nombre | Rol | Contraseña |
|---|---|---|---|
| `ana.lopez@marcapersonalfp.test` | Ana López | `alumno` | `ana-secreta` |
| `elena.ruiz@marcapersonalfp.test` | Elena Ruiz | `docente` | `docente-secreta` |

La contraseña de la tabla **no** aparece en el código: solo su _hash_. Son contraseñas de prueba, cómodas para el curso; en una aplicación real deberían ser largas y difíciles de adivinar.

La comprobación de las credenciales la hará una clase, **`Autenticador`**, con el mismo patrón que `ValidadorCurriculo` (3.8). Recibe los usuarios en el constructor, y su método `autenticar()` devuelve los datos del usuario si el email y la contraseña son correctos, o `null` si no lo son. Lo que devuelve **no incluye el _hash_**, porque no tiene que salir de ahí.

## El _login_, paso a paso

Cuando el formulario llega con datos correctos, la página de _login_:

1. **Regenera el identificador de sesión** con `session_regenerate_id(true)`.
2. Guarda el usuario en `$_SESSION['usuario']`.
3. Deja un mensaje _flash_ y redirige (303) al área del usuario, como en 4.1.

¿Por qué regenerar el identificador? Imagina que un atacante consigue que tu navegador use un `PHPSESSID` que él conoce. Por ejemplo, con un enlace preparado, o porque PHP acepta por defecto identificadores que le llegan del cliente. Si al hacer _login_ ese identificador no cambia, el atacante **ya está dentro de tu sesión** autenticada. A este ataque se le llama **fijación de sesión**. Con `session_regenerate_id(true)`, la sesión autenticada tiene un identificador nuevo, y el antiguo, que el atacante conocía, se borra (por eso el `true`).

![El login de v0 paso a paso: el atacante consigue que el navegador de Ana use el identificador abc; Ana envía email y contraseña con esa cookie; el servidor comprueba la contraseña con password_verify, regenera la sesión con un identificador nuevo, xyz, borra abc y responde 303 con la cookie nueva; Ana entra en su área con xyz; el atacante prueba con abc y no le sirve](./images/RA4_2_login.svg)

El diagrama recorre el ataque completo. Sin `session_regenerate_id(true)` (paso 4), la sesión autenticada seguiría siendo `abc`, y en el paso 7 el atacante entraría como Ana.

Si los datos no son correctos, el mensaje de error es **el mismo** tanto si el email no existe como si la contraseña está mal. Así la página no revela qué emails tienen cuenta. Tampoco se vuelve a escribir la contraseña en el formulario: solo el email.

## Páginas privadas y cierre de sesión

Una página **privada** comprueba, antes de nada, que en la sesión hay un usuario. Si no lo hay, redirige al _login_. Como lo harán varias páginas, lo convertimos en una función, **`exigirLogin()`**, que devuelve el usuario o, si no hay ninguno, redirige y termina el script.

**Cerrar la sesión** (_logout_) consiste en:
- vaciar `$_SESSION`;
- borrar la cookie `PHPSESSID` del navegador (`setcookie()` con una fecha de caducidad ya pasada);
- destruir la sesión en el servidor con `session_destroy()`.

El _logout_ se hace con un formulario **POST**, no con un enlace. Un enlace (GET) podría dispararlo cualquier otra página sin que el usuario lo pida, por ejemplo con una imagen cuya dirección fuera la del _logout_. Y `SameSite=Lax` (4.1) no protege las peticiones GET.

Por último, como ya son varias las páginas que abren la sesión con las mismas opciones, también llevamos `session_start([...])` a una función, **`iniciarSesion()`**.

## Preparación del entorno de trabajo

Seguimos con el proyecto `vanilla_php` y el código común de 4.1. Si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

Al terminar, estos son los ficheros nuevos o modificados:

```
vanilla_php/
├── public/
│   ├── RA4_login.php      ← formulario de login (nuevo)
│   ├── RA4_perfil.php     ← área del usuario, privada (nuevo)
│   └── RA4_logout.php     ← cierre de sesión (nuevo)
└── src/
    ├── Autenticador.php   ← comprueba las credenciales (nuevo)
    └── RA4_comun.php      ← + usuarios, iniciarSesion(), exigirLogin() (modificado)
```

## Ejercicios

1. **La clase `Autenticador`.** Crea `src/Autenticador.php`:

    ```php
    <?php
    declare(strict_types=1);

    /**
     * Comprueba las credenciales (email y contraseña) de los usuarios.
     */
    class Autenticador
    {
        /**
         * @param array<string, array{firstName: string, lastName: string, rol: string, password: string}> $usuarios
         *        Usuarios indexados por su email; 'password' es el hash de la contraseña.
         */
        public function __construct(
            private readonly array $usuarios,
        ) {
        }

        /**
         * @return array{email: string, firstName: string, lastName: string, rol: string}|null
         *         Los datos del usuario (sin el hash) si las credenciales son correctas; null si no.
         */
        public function autenticar(string $email, string $password): ?array
        {
            $usuario = $this->usuarios[$email] ?? null;

            if ($usuario === null || !password_verify($password, $usuario['password'])) {
                return null;
            }

            return [
                'email'     => $email,
                'firstName' => $usuario['firstName'],
                'lastName'  => $usuario['lastName'],
                'rol'       => $usuario['rol'],
            ];
        }
    }
    ```

2. **Los usuarios y las funciones comunes.** Primero, genera el _hash_ de cada contraseña de la tabla. Desde la carpeta `laradock/`:

    ```bash
    docker compose exec workspace php -r "echo password_hash('ana-secreta', PASSWORD_DEFAULT), PHP_EOL;"
    ```

    Ejecútalo dos veces y compara los resultados. Después repítelo con `docente-secreta`. Luego añade a `src/RA4_comun.php`, debajo de los `require_once` que ya tiene, un `require_once` más y las cuatro funciones siguientes. Pega tus _hashes_ (entre comillas **simples**) en lugar de los puntos suspensivos:

    ```php
    require_once __DIR__ . '/Autenticador.php';

    /**
     * Usuarios de la aplicación, indexados por su email.
     * La contraseña se guarda solo como hash (password_hash()).
     *
     * @return array<string, array{firstName: string, lastName: string, rol: string, password: string}>
     */
    function usuarios(): array
    {
        return [
            'ana.lopez@marcapersonalfp.test' => [
                'firstName' => 'Ana',
                'lastName'  => 'López',
                'rol'       => 'alumno',
                'password'  => '$2y$...',
            ],
            'elena.ruiz@marcapersonalfp.test' => [
                'firstName' => 'Elena',
                'lastName'  => 'Ruiz',
                'rol'       => 'docente',
                'password'  => '$2y$...',
            ],
        ];
    }

    /** Abre (o retoma) la sesión con la cookie protegida, como en 4.1. */
    function iniciarSesion(): void
    {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }

    /** @return array{email: string, firstName: string, lastName: string, rol: string}|null */
    function usuarioActual(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    /**
     * Devuelve el usuario de la sesión o, si no hay ninguno, redirige al login y termina.
     *
     * @return array{email: string, firstName: string, lastName: string, rol: string}
     */
    function exigirLogin(): array
    {
        $usuario = usuarioActual();
        if ($usuario === null) {
            header('Location: RA4_login.php');
            exit;
        }

        return $usuario;
    }
    ```

    Con `header('Location: …')` sin código, PHP responde con un **302**, la redirección "normal". Al venir de una petición GET, no hace falta forzar el 303.

3. **El _login_.** Crea `public/RA4_login.php`:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA4_comun.php';

    iniciarSesion();

    // Si ya hay alguien identificado, no tiene sentido volver a pedirle las credenciales.
    if (usuarioActual() !== null) {
        header('Location: RA4_perfil.php', true, 303);
        exit;
    }

    $email = '';
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $usuario = (new Autenticador(usuarios()))->autenticar($email, $password);

        if ($usuario === null) {
            // El mismo mensaje tanto si falla el email como si falla la contraseña.
            $error = 'Email o contraseña incorrectos.';
        } else {
            // Identificador de sesión nuevo al iniciar sesión: evita la fijación de sesión.
            session_regenerate_id(true);
            $_SESSION['usuario'] = $usuario;
            $_SESSION['mensaje'] = 'Hola, ' . $usuario['firstName'] . '. Has iniciado sesión.';

            header('Location: RA4_perfil.php', true, 303);
            exit;
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Iniciar sesión — <?= NOMBRE_CENTRO ?></title>
    </head>
    <body>
      <h1><?= NOMBRE_CENTRO ?> — Iniciar sesión</h1>

      <?php if ($error !== null): ?>
        <p class="error"><?= e($error) ?></p>
      <?php endif; ?>

      <form action="RA4_login.php" method="post">
        <p>
          <label>Email
            <input name="email" type="email" value="<?= e($email) ?>">
          </label>
        </p>
        <p>
          <label>Contraseña
            <input name="password" type="password">
          </label>
        </p>
        <button type="submit">Entrar</button>
      </form>
    </body>
    </html>
    ```

4. **El área del usuario.** Crea `public/RA4_perfil.php`, una página **privada**:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA4_comun.php';

    iniciarSesion();
    $usuario = exigirLogin();

    $mensaje = $_SESSION['mensaje'] ?? null;
    unset($_SESSION['mensaje']);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Mi área — <?= NOMBRE_CENTRO ?></title>
    </head>
    <body>
      <h1><?= NOMBRE_CENTRO ?> — Mi área</h1>

      <?php if ($mensaje !== null): ?>
        <p class="exito"><?= e($mensaje) ?></p>
      <?php endif; ?>

      <p>Usuario: <?= e($usuario['firstName'] . ' ' . $usuario['lastName']) ?></p>
      <p>Email: <?= e($usuario['email']) ?></p>
      <p>Rol: <?= e($usuario['rol']) ?></p>

      <form action="RA4_logout.php" method="post">
        <button type="submit">Cerrar sesión</button>
      </form>
    </body>
    </html>
    ```

5. **El _logout_.** Crea `public/RA4_logout.php`. No tiene HTML: hace su trabajo y redirige al _login_.

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/RA4_comun.php';

    iniciarSesion();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Vaciar los datos de la sesión.
        $_SESSION = [];

        // 2. Borrar la cookie del navegador: misma ruta, fecha ya pasada.
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $parametros['path'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // 3. Destruir la sesión en el servidor.
        session_destroy();
    }

    header('Location: RA4_login.php', true, 303);
    exit;
    ```

    `session_name()` devuelve el nombre de la cookie de sesión (`PHPSESSID`), y `session_get_cookie_params()`, sus parámetros: para que el navegador borre una cookie, hay que indicarle la misma ruta con la que se creó.

6. **Pruébalo.** Con las herramientas de desarrollo (F12) abiertas, anota en un comentario PHP qué observas en cada caso:
    - Abre `http://localhost/RA4_perfil.php` sin haber iniciado sesión. ¿Qué código de estado tiene la respuesta y adónde te lleva?
    - Intenta entrar con una contraseña incorrecta, y después con un email que no existe. ¿Cambia el mensaje? ¿Qué queda escrito en el formulario?
    - Antes de entrar con los datos correctos, apunta el valor de la cookie `PHPSESSID`. Entra, y compáralo con el nuevo. ¿Por qué ha cambiado?
    - Ya dentro, vuelve a `RA4_login.php`. ¿Qué ocurre?
    - Cierra la sesión y pulsa el botón **Atrás** del navegador para volver a `RA4_perfil.php`. Si ves la página, recárgala con F5: ¿qué pasa ahora? (El navegador puede mostrar una copia guardada, pero el servidor ya no te reconoce.)
    - Entra como Elena Ruiz. ¿Qué rol aparece?
7. **Investiga.** Muchas aplicaciones tienen una casilla **"Recordarme"**, que mantiene al usuario identificado durante semanas aunque cierre el navegador. ¿Por qué no se hace guardando el email y la contraseña en una cookie? Busca cómo se implementa de forma segura (pista: un _token_ aleatorio guardado en una cookie y, en el servidor, solo su _hash_). Responde en un comentario.

> **Puente al Bloque 5.** _Laravel_ trae todo esto hecho:
> - `Hash::make()` y `Hash::check()` envuelven `password_hash()` y `password_verify()`;
> - `Auth::attempt(['email' => …, 'password' => …])` comprueba las credenciales y, si son correctas, guarda el usuario en la sesión y regenera el identificador;
> - `Auth::user()` hace lo que nuestro `usuarioActual()`;
> - el _middleware_ `auth` hace lo que nuestro `exigirLogin()`.
>
> _Breeze_ añade, además, las páginas de _login_, registro, recuperación de contraseña y "Recordarme".

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 5, copia el archivo [RA4_2_LoginTest.php](./materiales/ejercicios-vanilla/tests/RA4_2_LoginTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA4_2_LoginTest.php
```

El test hace dos tipos de comprobaciones:
- Prueba `Autenticador` con sus propios usuarios, cuyos _hashes_ genera él mismo.
- Inicia sesión en tu aplicación como Ana López (`ana-secreta`), así que tus _hashes_ tienen que corresponder a las contraseñas de la tabla. Después comprueba la regeneración del identificador, el acceso a la página privada y el cierre de sesión.

Necesita `nginx` y `php-fpm` levantados.

Si todo está bien: `OK (9 tests, 22 assertions)`.

---

**Siguiente:** [4.3. Depuración con Xdebug](./RA4_3_xdebug.md)
