<?php

use PHPUnit\Framework\TestCase;

class RA3_6_FormulariosTest extends TestCase
{
    private const FICHERO_FUNCIONES = __DIR__ . '/../src/RA3_formularios.php';

    private const CICLO_VALIDO = [
        'codCiclo' => 'DAW',
        'nombre'   => 'Desarrollo de Aplicaciones Web',
        'grado'    => 'Superior',
        'horas'    => '2000',   // como llegaría en $_POST: siempre string
    ];

    public static function setUpBeforeClass(): void
    {
        if (is_file(self::FICHERO_FUNCIONES)) {
            require_once self::FICHERO_FUNCIONES;
        }
    }

    private function requiereFuncion(string $nombre): void
    {
        if (!function_exists($nombre)) {
            $this->fail("Falta la función $nombre() en src/RA3_formularios.php");
        }
    }

    // Envía una petición POST real, como lo haría el navegador al enviar el formulario.
    private function post(string $url, array $datos): string|false
    {
        $contexto = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => 'Content-Type: application/x-www-form-urlencoded',
            'content'       => http_build_query($datos),
            'ignore_errors' => true,
        ]]);

        return @file_get_contents($url, false, $contexto);
    }

    public function test_el_fichero_de_funciones_existe(): void
    {
        $this->assertFileExists(self::FICHERO_FUNCIONES, 'Falta src/RA3_formularios.php');
    }

    public function test_e_escapa_el_html(): void
    {
        $this->requiereFuncion('e');

        $this->assertSame(
            '&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;',
            e("<script>alert('XSS')</script>"),
            'e() debería convertir <, > y las comillas en entidades HTML (htmlspecialchars).'
        );
    }

    public function test_validarCiclo_acepta_datos_validos(): void
    {
        $this->requiereFuncion('validarCiclo');

        $this->assertSame([], validarCiclo(self::CICLO_VALIDO), 'Con datos válidos, validarCiclo() debería devolver un array vacío.');
    }

    public function test_validarCiclo_exige_los_campos_obligatorios(): void
    {
        $this->requiereFuncion('validarCiclo');

        $this->assertSame(
            ['codCiclo', 'nombre', 'grado', 'horas'],
            array_keys(validarCiclo([])),
            'Sin datos, validarCiclo() debería devolver un error por cada campo (sin warnings: ¿usas ?? con cada clave?).'
        );
        $this->assertArrayHasKey(
            'nombre',
            validarCiclo([...self::CICLO_VALIDO, 'nombre' => '   ']),
            'Un nombre de solo espacios debería dar error: ¿usas trim()?'
        );
    }

    public function test_validarCiclo_solo_admite_los_grados_permitidos(): void
    {
        $this->requiereFuncion('validarCiclo');

        $this->assertArrayHasKey('grado', validarCiclo([...self::CICLO_VALIDO, 'grado' => 'Básico']), 'Un grado distinto de Medio o Superior debería dar error.');
    }

    public function test_validarCiclo_comprueba_que_las_horas_son_un_entero_en_rango(): void
    {
        $this->requiereFuncion('validarCiclo');

        $this->assertArrayHasKey('horas', validarCiclo([...self::CICLO_VALIDO, 'horas' => 'dos mil']), 'Unas horas no numéricas deberían dar error.');
        $this->assertArrayHasKey('horas', validarCiclo([...self::CICLO_VALIDO, 'horas' => '3000']), 'Unas horas fuera del rango 1000-2000 deberían dar error.');
    }

    public function test_la_busqueda_lee_get_y_escapa_la_salida(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA3_busqueda.php?ciclo=DAW');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA3_busqueda.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );
        $this->assertSame(
            2,
            substr_count($html, '<li class="curriculo">'),
            'Con ?ciclo=DAW deberían listarse solo los 2 currículos de DAW: ¿lees $_GET[\'ciclo\']?'
        );

        $html = @file_get_contents('http://nginx/RA3_busqueda.php?ciclo=' . urlencode('<b>DAW</b>'));
        $this->assertStringContainsString(
            'Resultados para: &lt;b&gt;DAW&lt;/b&gt;',
            (string) $html,
            'El texto buscado debería mostrarse escapado con e(), no como HTML.'
        );
    }

    public function test_el_alta_procesa_post_valida_y_conserva_los_datos(): void
    {
        $html = $this->post('http://nginx/RA3_altaCiclo.php', self::CICLO_VALIDO);

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA3_altaCiclo.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );
        $this->assertStringContainsString('Ciclo dado de alta: DAW', $html, 'Con datos válidos, la página debería confirmar el alta.');

        $html = (string) $this->post('http://nginx/RA3_altaCiclo.php', [...self::CICLO_VALIDO, 'nombre' => '']);
        $this->assertStringContainsString('El nombre es obligatorio.', $html, 'Con el nombre vacío, la página debería mostrar el error de validarCiclo().');
        $this->assertStringContainsString('value="DAW"', $html, 'Al mostrar errores, el formulario debería conservar lo escrito (value="DAW" en el código).');
    }
}
