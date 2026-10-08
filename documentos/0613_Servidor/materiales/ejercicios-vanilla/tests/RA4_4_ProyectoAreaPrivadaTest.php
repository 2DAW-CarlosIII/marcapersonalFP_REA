<?php

use PHPUnit\Framework\TestCase;

class RA4_4_ProyectoAreaPrivadaTest extends TestCase
{
    // Desde "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
    private const BASE = 'http://nginx/';

    private const PAGINA = 'RA4_miCurriculo.php';

    // Usuarios de 4.2: tus hashes deben corresponder a estas contraseñas.
    private const ANA   = ['email' => 'ana.lopez@marcapersonalfp.test', 'password' => 'ana-secreta'];
    private const ELENA = ['email' => 'elena.ruiz@marcapersonalfp.test', 'password' => 'docente-secreta'];

    private const CURRICULO_DAW = ['ciclo' => 'DAW', 'video' => 'https://youtu.be/curriculo1'];

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

    /** Valor de la última cabecera Set-Cookie de la cookie indicada, o null. */
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

    /**
     * Inicia sesión con las credenciales indicadas, en una sesión nueva.
     *
     * @param array{email: string, password: string} $credenciales
     * @return array<string, string> La cookie de la sesión autenticada, lista para peticion().
     */
    private function login(array $credenciales): array
    {
        $login  = $this->peticion('RA4_login.php', 'POST', $credenciales);
        $sesion = $this->cookie($login['cabeceras'], 'PHPSESSID');
        if ($login['estado'] !== 303 || $sesion === null) {
            $this->fail('No se pudo iniciar sesión como ' . $credenciales['email'] . ': ¿funciona el login de 4.2 con su contraseña? ¿Están levantados nginx y php-fpm?');
        }

        return ['PHPSESSID' => $sesion];
    }

    public function test_sin_sesion_redirige_al_login(): void
    {
        $pagina = $this->peticion(self::PAGINA);

        $this->assertContains($pagina['estado'], [302, 303], 'Sin sesión iniciada, "Mi currículo" debería redirigir. ¿Existe public/RA4_miCurriculo.php? ¿Están levantados nginx y php-fpm?');
        $this->assertMatchesRegularExpression('/^Location: .*RA4_login\.php$/mi', implode("\n", $pagina['cabeceras']), 'Sin sesión iniciada, "Mi currículo" debería redirigir a RA4_login.php.');
    }

    public function test_un_docente_recibe_un_403(): void
    {
        $pagina = $this->peticion(self::PAGINA, 'GET', [], $this->login(self::ELENA));

        $this->assertSame(403, $pagina['estado'], 'Un docente no tiene currículo: la página debería responder 403 (exigirRol()).');
        $this->assertStringContainsString('No tienes permiso para ver esta página.', $pagina['html'], 'Con el 403 debería enviarse el mensaje de exigirRol().');
    }

    public function test_un_alumno_sin_curriculo_ve_el_formulario(): void
    {
        $pagina = $this->peticion(self::PAGINA, 'GET', [], $this->login(self::ANA));

        $this->assertStringContainsString('Todavía no tienes currículo', $pagina['html'], 'Un alumno sin currículo debería ver el aviso y el formulario de alta.');
        $this->assertStringContainsString('Ana López', $pagina['html'], 'El formulario debería mostrar el nombre del alumno, tomado de la sesión.');
    }

    public function test_guardar_redirige_y_muestra_el_curriculo_y_el_mensaje_una_vez(): void
    {
        $sesion  = $this->login(self::ANA);
        $guardar = $this->peticion(self::PAGINA, 'POST', self::CURRICULO_DAW, $sesion);

        $this->assertSame(303, $guardar['estado'], 'Con datos válidos, guardar debería redirigir con un 303.');
        $this->assertMatchesRegularExpression('/^Location: .*RA4_miCurriculo\.php$/mi', implode("\n", $guardar['cabeceras']), 'Tras guardar, debería redirigir a la propia RA4_miCurriculo.php.');

        $primera = $this->peticion(self::PAGINA, 'GET', [], $sesion);
        $this->assertStringContainsString('Ana López (DAW)', $primera['html'], 'Tras guardar, la página debería mostrar el currículo del alumno.');
        $this->assertStringContainsString('Currículo guardado.', $primera['html'], 'Tras guardar, debería aparecer el mensaje flash.');

        $segunda = $this->peticion(self::PAGINA, 'GET', [], $sesion);
        $this->assertStringNotContainsString('Currículo guardado.', $segunda['html'], 'Al recargar, el mensaje flash ya no debería aparecer.');
    }

    public function test_el_nombre_sale_de_la_sesion_y_no_del_formulario(): void
    {
        $sesion = $this->login(self::ANA);
        $this->peticion(self::PAGINA, 'POST', [...self::CURRICULO_DAW, 'alumno' => 'Otra Persona'], $sesion);
        $pagina = $this->peticion(self::PAGINA, 'GET', [], $sesion);

        $this->assertStringContainsString('Ana López (DAW)', $pagina['html'], 'El currículo debería guardarse a nombre del usuario de la sesión.');
        $this->assertStringNotContainsString('Otra Persona', $pagina['html'], 'Un campo "alumno" enviado en el formulario no debería tenerse en cuenta.');
    }

    public function test_los_datos_no_validos_muestran_los_errores(): void
    {
        $guardar = $this->peticion(self::PAGINA, 'POST', [...self::CURRICULO_DAW, 'ciclo' => 'FP'], $this->login(self::ANA));

        $this->assertSame(200, $guardar['estado'], 'Con datos no válidos no debería haber redirección: se vuelve a mostrar el formulario.');
        $this->assertStringContainsString('Elige uno de los ciclos de la lista.', $guardar['html'], 'Con un ciclo que no existe, debería mostrarse el error de ValidadorCurriculo.');
    }

    public function test_editar_parte_de_lo_guardado_y_lo_cambia(): void
    {
        $sesion = $this->login(self::ANA);
        $this->peticion(self::PAGINA, 'POST', self::CURRICULO_DAW, $sesion);

        $edicion = $this->peticion(self::PAGINA . '?editar=1', 'GET', [], $sesion);
        $this->assertMatchesRegularExpression('/<option value="DAW"\s+selected/', $edicion['html'], 'Al editar, el formulario debería tener elegido el ciclo guardado (DAW).');

        $this->peticion(self::PAGINA, 'POST', [...self::CURRICULO_DAW, 'ciclo' => 'SMR'], $sesion);
        $pagina = $this->peticion(self::PAGINA, 'GET', [], $sesion);
        $this->assertStringContainsString('Ana López (SMR)', $pagina['html'], 'Tras editar, la página debería mostrar el currículo con el ciclo nuevo.');
    }

    public function test_el_curriculo_se_pierde_al_cerrar_la_sesion(): void
    {
        $sesion = $this->login(self::ANA);
        $this->peticion(self::PAGINA, 'POST', self::CURRICULO_DAW, $sesion);
        $this->peticion('RA4_logout.php', 'POST', [], $sesion);

        $pagina = $this->peticion(self::PAGINA, 'GET', [], $this->login(self::ANA));

        $this->assertStringContainsString('Todavía no tienes currículo', $pagina['html'], 'Tras cerrar la sesión y volver a entrar, el currículo ya no debería estar: la sesión no es un almacén.');
    }
}
