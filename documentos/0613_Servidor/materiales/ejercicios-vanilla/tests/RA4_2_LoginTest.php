<?php

use PHPUnit\Framework\TestCase;

class RA4_2_LoginTest extends TestCase
{
    private const SRC = __DIR__ . '/../src/';

    // Desde "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
    private const BASE = 'http://nginx/';

    // Credenciales de la tabla de 4.2: tus hashes deben corresponder a estas contraseñas.
    private const EMAIL_ANA    = 'ana.lopez@marcapersonalfp.test';
    private const PASSWORD_ANA = 'ana-secreta';

    public static function setUpBeforeClass(): void
    {
        if (is_file(self::SRC . 'Autenticador.php')) {
            require_once self::SRC . 'Autenticador.php';
        }
    }

    /**
     * Hace una petición HTTP real, como el navegador, pero sin seguir las redirecciones.
     *
     * @param array<string, string> $datos   Campos del formulario (solo en POST).
     * @param array<string, string> $cookies Cookies que se envían en la cabecera Cookie.
     * @return array{estado: int, cabeceras: list<string>, html: string}
     */
    private function peticion(string $pagina, string $metodo = 'GET', array $datos = [], array $cookies = []): array
    {
        $cabeceras = [];
        if ($metodo === 'POST') {
            $cabeceras[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        if ($cookies !== []) {
            $pares = [];
            foreach ($cookies as $nombre => $valor) {
                $pares[] = $nombre . '=' . $valor;
            }
            $cabeceras[] = 'Cookie: ' . implode('; ', $pares);
        }

        $contexto = stream_context_create(['http' => [
            'method'          => $metodo,
            'header'          => implode("\r\n", $cabeceras),
            'content'         => http_build_query($datos),
            'ignore_errors'   => true,
            'follow_location' => 0,
        ]]);

        $html      = (string) @file_get_contents(self::BASE . $pagina, false, $contexto);
        $respuesta = http_get_last_response_headers() ?? [];
        $estado    = isset($respuesta[0]) ? (int) explode(' ', $respuesta[0])[1] : 0;

        return ['estado' => $estado, 'cabeceras' => $respuesta, 'html' => $html];
    }

    /**
     * Valor de la cookie indicada en las cabeceras Set-Cookie de la respuesta, o null.
     * Si aparece varias veces (sesión nueva y después regenerada), vale la última.
     */
    private function cookie(array $cabeceras, string $nombre): ?string
    {
        $valor = null;
        foreach ($cabeceras as $cabecera) {
            if (stripos($cabecera, 'Set-Cookie: ' . $nombre . '=') === 0) {
                $par   = explode(';', substr($cabecera, strlen('Set-Cookie: ')))[0];
                $valor = explode('=', $par, 2)[1];
            }
        }

        return $valor;
    }

    /** Inicia sesión como Ana López y devuelve el identificador de la sesión autenticada. */
    private function login(): string
    {
        $login  = $this->peticion('RA4_login.php', 'POST', ['email' => self::EMAIL_ANA, 'password' => self::PASSWORD_ANA]);
        $sesion = $this->cookie($login['cabeceras'], 'PHPSESSID');
        if ($login['estado'] !== 303 || $sesion === null) {
            $this->fail('No se pudo iniciar sesión como Ana López (ana-secreta): ¿corresponde tu hash a esa contraseña? ¿Están levantados nginx y php-fpm?');
        }

        return $sesion;
    }

    /** @return array<string, array{firstName: string, lastName: string, rol: string, password: string}> */
    private function usuariosDePrueba(): array
    {
        return [
            'ana.lopez@marcapersonalfp.test' => [
                'firstName' => 'Ana',
                'lastName'  => 'López',
                'rol'       => 'alumno',
                'password'  => password_hash('clave-de-prueba', PASSWORD_DEFAULT),
            ],
        ];
    }

    private function autenticador(): Autenticador
    {
        if (!class_exists('Autenticador')) {
            $this->fail('Falta la clase Autenticador (¿existe src/Autenticador.php?)');
        }

        return new Autenticador($this->usuariosDePrueba());
    }

    public function test_existe_la_clase_autenticador(): void
    {
        $this->assertFileExists(self::SRC . 'Autenticador.php', 'Falta src/Autenticador.php');
    }

    public function test_autentica_con_las_credenciales_correctas(): void
    {
        $usuario = $this->autenticador()->autenticar('ana.lopez@marcapersonalfp.test', 'clave-de-prueba');

        $this->assertNotNull($usuario, 'Con el email y la contraseña correctos, autenticar() debería devolver los datos del usuario.');
        $this->assertSame('Ana', $usuario['firstName'], 'Los datos devueltos deberían incluir el firstName del usuario.');
        $this->assertArrayNotHasKey('password', $usuario, 'Los datos devueltos no deberían incluir el hash de la contraseña.');
    }

    public function test_rechaza_las_credenciales_incorrectas(): void
    {
        $autenticador = $this->autenticador();

        $this->assertNull($autenticador->autenticar('ana.lopez@marcapersonalfp.test', 'otra-clave'), 'Con una contraseña incorrecta, autenticar() debería devolver null.');
        $this->assertNull($autenticador->autenticar('nadie@marcapersonalfp.test', 'clave-de-prueba'), 'Con un email que no existe, autenticar() debería devolver null (sin warnings: ¿usas ??).');
    }

    public function test_las_contrasenas_no_estan_en_claro(): void
    {
        $codigo = (string) @file_get_contents(self::SRC . 'RA4_comun.php');

        $this->assertStringContainsString('$2y$', $codigo, 'src/RA4_comun.php debería contener los hashes de las contraseñas (empiezan por $2y$).');
        $this->assertStringNotContainsString(self::PASSWORD_ANA, $codigo, 'La contraseña no debería aparecer en claro en el código, ni siquiera en un comentario.');
    }

    public function test_el_login_correcto_regenera_la_sesion_y_redirige(): void
    {
        $formulario = $this->peticion('RA4_login.php');
        $anterior   = $this->cookie($formulario['cabeceras'], 'PHPSESSID');
        $this->assertNotNull($anterior, 'La página de login debería abrir una sesión (iniciarSesion()). ¿Están levantados nginx y php-fpm?');

        $login = $this->peticion('RA4_login.php', 'POST', ['email' => self::EMAIL_ANA, 'password' => self::PASSWORD_ANA], ['PHPSESSID' => $anterior]);
        $nueva = $this->cookie($login['cabeceras'], 'PHPSESSID');

        $this->assertSame(303, $login['estado'], 'Con credenciales correctas, el login debería redirigir con un 303. ¿Corresponde tu hash a "ana-secreta"?');
        $this->assertMatchesRegularExpression('/^Location: .*RA4_perfil\.php$/mi', implode("\n", $login['cabeceras']), 'El login debería redirigir a RA4_perfil.php.');
        $this->assertNotNull($nueva, 'Al iniciar sesión, la respuesta debería traer un identificador de sesión nuevo: ¿llamas a session_regenerate_id(true)?');
        $this->assertNotSame($anterior, $nueva, 'El identificador de sesión debería cambiar al iniciar sesión (fijación de sesión).');
    }

    public function test_la_pagina_privada_exige_login(): void
    {
        $perfil = $this->peticion('RA4_perfil.php');

        $this->assertContains($perfil['estado'], [302, 303], 'Sin sesión iniciada, RA4_perfil.php debería redirigir (exigirLogin()). ¿Están levantados nginx y php-fpm?');
        $this->assertMatchesRegularExpression('/^Location: .*RA4_login\.php$/mi', implode("\n", $perfil['cabeceras']), 'Sin sesión iniciada, RA4_perfil.php debería redirigir a RA4_login.php.');
    }

    public function test_la_pagina_privada_muestra_al_usuario(): void
    {
        $perfil = $this->peticion('RA4_perfil.php', 'GET', [], ['PHPSESSID' => $this->login()]);

        $this->assertStringContainsString('Ana López', $perfil['html'], 'Con la sesión iniciada, RA4_perfil.php debería mostrar el nombre del usuario.');
        $this->assertStringContainsString('Rol: alumno', $perfil['html'], 'RA4_perfil.php debería mostrar el rol del usuario.');
    }

    public function test_el_login_incorrecto_no_revela_que_falla(): void
    {
        $malaPassword = $this->peticion('RA4_login.php', 'POST', ['email' => self::EMAIL_ANA, 'password' => 'no-es-esta']);
        $malEmail     = $this->peticion('RA4_login.php', 'POST', ['email' => 'nadie@marcapersonalfp.test', 'password' => self::PASSWORD_ANA]);

        $this->assertSame(200, $malaPassword['estado'], 'Con credenciales incorrectas no debería haber redirección: se vuelve a mostrar el formulario.');
        $this->assertStringContainsString('Email o contraseña incorrectos.', $malaPassword['html'], 'Con una contraseña incorrecta, debería aparecer "Email o contraseña incorrectos."');
        $this->assertStringContainsString('Email o contraseña incorrectos.', $malEmail['html'], 'Con un email que no existe, debería aparecer el mismo mensaje.');
    }

    public function test_el_logout_cierra_la_sesion(): void
    {
        $sesion = ['PHPSESSID' => $this->login()];

        $logout = $this->peticion('RA4_logout.php', 'POST', [], $sesion);
        $perfil = $this->peticion('RA4_perfil.php', 'GET', [], $sesion);

        $this->assertSame(303, $logout['estado'], 'El logout debería redirigir al login con un 303.');
        $this->assertContains($perfil['estado'], [302, 303], 'Tras el logout, la página privada debería volver a redirigir al login.');
    }
}
