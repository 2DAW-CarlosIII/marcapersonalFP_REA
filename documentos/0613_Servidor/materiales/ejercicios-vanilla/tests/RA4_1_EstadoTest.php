<?php

use PHPUnit\Framework\TestCase;

class RA4_1_EstadoTest extends TestCase
{
    private const SRC = __DIR__ . '/../src/';

    // Desde "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
    private const BASE = 'http://nginx/';

    private const DATOS_VALIDOS = [
        'alumno' => 'Ana López',
        'ciclo'  => 'DAW',
        'video'  => 'https://youtu.be/curriculo1',
    ];

    /**
     * Hace una petición HTTP real, como el navegador, pero sin seguir las
     * redirecciones: así se puede comprobar la respuesta 303 del alta.
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

        // La primera línea es el estado, p. ej. "HTTP/1.1 303 See Other".
        $estado = isset($respuesta[0]) ? (int) explode(' ', $respuesta[0])[1] : 0;

        return ['estado' => $estado, 'cabeceras' => $respuesta, 'html' => $html];
    }

    /** Devuelve la cabecera Set-Cookie completa de la cookie indicada, o null. */
    private function setCookie(array $cabeceras, string $nombre): ?string
    {
        foreach ($cabeceras as $cabecera) {
            if (stripos($cabecera, 'Set-Cookie: ' . $nombre . '=') === 0) {
                return $cabecera;
            }
        }

        return null;
    }

    /** Extrae el valor de una cabecera Set-Cookie ("Set-Cookie: nombre=valor; path=/..."). */
    private function valor(string $setCookie): string
    {
        $par = explode(';', substr($setCookie, strlen('Set-Cookie: ')))[0];

        return explode('=', $par, 2)[1];
    }

    /** Da de alta un currículo válido en una sesión nueva y devuelve su identificador de sesión. */
    private function altaEnSesionNueva(): string
    {
        $alta      = $this->peticion('RA4_altaCurriculo.php', 'POST', self::DATOS_VALIDOS);
        $setCookie = $this->setCookie($alta['cabeceras'], 'PHPSESSID');
        if ($setCookie === null) {
            $this->fail('El alta no ha enviado la cookie PHPSESSID: ¿llamas a session_start()? ¿Están levantados nginx y php-fpm?');
        }

        return $this->valor($setCookie);
    }

    public function test_existe_el_codigo_comun(): void
    {
        $this->assertFileExists(self::SRC . 'RA4_comun.php', 'Falta src/RA4_comun.php');
    }

    public function test_el_alta_valida_redirige_al_listado(): void
    {
        $alta = $this->peticion('RA4_altaCurriculo.php', 'POST', self::DATOS_VALIDOS);

        $this->assertSame(303, $alta['estado'], 'Con datos válidos, el alta debería responder con un 303 (header(..., true, 303)). ¿Están levantados nginx y php-fpm?');
        $this->assertMatchesRegularExpression('/^Location: .*RA4_curriculos\.php$/mi', implode("\n", $alta['cabeceras']), 'El alta debería redirigir a RA4_curriculos.php con la cabecera Location.');
        $this->assertNotNull($this->setCookie($alta['cabeceras'], 'PHPSESSID'), 'El alta debería abrir una sesión y enviar la cookie PHPSESSID.');
    }

    public function test_el_listado_recuerda_el_alta_y_muestra_el_mensaje_una_vez(): void
    {
        $sesion = ['PHPSESSID' => $this->altaEnSesionNueva()];

        $primera = $this->peticion('RA4_curriculos.php', 'GET', [], $sesion);
        $this->assertStringContainsString('Ana López (DAW)', $primera['html'], 'El listado debería mostrar el currículo dado de alta en esta sesión.');
        $this->assertStringContainsString('Currículo dado de alta:', $primera['html'], 'Tras el alta, el listado debería mostrar el mensaje flash.');

        $segunda = $this->peticion('RA4_curriculos.php', 'GET', [], $sesion);
        $this->assertStringContainsString('Ana López (DAW)', $segunda['html'], 'Al recargar, el currículo debería seguir en el listado: está en la sesión.');
        $this->assertStringNotContainsString('Currículo dado de alta:', $segunda['html'], 'Al recargar, el mensaje flash ya no debería aparecer: ¿lo borras con unset()?');
    }

    public function test_otra_sesion_empieza_con_la_lista_vacia(): void
    {
        // Sin enviar ninguna cookie: para el servidor, un navegador nuevo.
        $listado = $this->peticion('RA4_curriculos.php');

        $this->assertStringContainsString('Todavía no has dado de alta ningún currículo.', $listado['html'], 'Una sesión nueva debería ver la lista vacía. ¿Están levantados nginx y php-fpm?');
    }

    public function test_el_alta_invalida_no_redirige_y_muestra_los_errores(): void
    {
        $alta = $this->peticion('RA4_altaCurriculo.php', 'POST', [...self::DATOS_VALIDOS, 'ciclo' => 'FP']);

        $this->assertSame(200, $alta['estado'], 'Con datos no válidos no debería haber redirección: el formulario se vuelve a mostrar.');
        $this->assertStringContainsString('Elige uno de los ciclos de la lista.', $alta['html'], 'Con un ciclo que no existe, el alta debería mostrar el error.');
    }

    public function test_recuerda_el_ultimo_ciclo_en_una_cookie(): void
    {
        $alta      = $this->peticion('RA4_altaCurriculo.php', 'POST', [...self::DATOS_VALIDOS, 'ciclo' => 'SMR']);
        $setCookie = $this->setCookie($alta['cabeceras'], 'ultimo_ciclo');

        $this->assertNotNull($setCookie, 'Tras un alta válida, la respuesta debería crear la cookie ultimo_ciclo con setcookie().');
        $this->assertStringContainsString('ultimo_ciclo=SMR', $setCookie, 'La cookie ultimo_ciclo debería guardar el código del ciclo elegido.');
        $this->assertStringContainsStringIgnoringCase('expires=', $setCookie, 'La cookie ultimo_ciclo debería ser persistente (con expires), no de sesión.');

        $formulario = $this->peticion('RA4_altaCurriculo.php', 'GET', [], ['ultimo_ciclo' => 'SMR']);
        $this->assertMatchesRegularExpression('/<option value="SMR"\s+selected/', $formulario['html'], 'Con la cookie ultimo_ciclo=SMR, el desplegable debería tener SMR elegido.');
    }

    public function test_la_cookie_de_sesion_es_httponly_y_samesite(): void
    {
        $formulario = $this->peticion('RA4_altaCurriculo.php');
        $setCookie  = $this->setCookie($formulario['cabeceras'], 'PHPSESSID');

        $this->assertNotNull($setCookie, 'La página de alta debería abrir una sesión y enviar la cookie PHPSESSID. ¿Están levantados nginx y php-fpm?');
        $this->assertStringContainsStringIgnoringCase('HttpOnly', $setCookie, 'La cookie de sesión debería llevar el atributo HttpOnly (cookie_httponly).');
        $this->assertStringContainsStringIgnoringCase('SameSite=Lax', $setCookie, 'La cookie de sesión debería llevar SameSite=Lax (cookie_samesite).');
    }
}
