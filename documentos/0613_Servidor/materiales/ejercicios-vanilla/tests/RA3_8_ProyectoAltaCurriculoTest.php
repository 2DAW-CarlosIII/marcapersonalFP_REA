<?php

use PHPUnit\Framework\TestCase;

class RA3_8_ProyectoAltaCurriculoTest extends TestCase
{
    private const SRC = __DIR__ . '/../src/';

    private const FICHEROS = ['Modelo.php', 'Publicable.php', 'Ciclo.php', 'Curriculo.php', 'ValidadorCurriculo.php'];

    private const DATOS_VALIDOS = [
        'alumno' => 'Ana López',
        'ciclo'  => 'DAW',
        'video'  => 'https://youtu.be/curriculo1',
    ];

    private const URL_PAGINA = 'http://nginx/RA3_altaCurriculo.php';

    // Se cargan las clases solo si existen todos los ficheros: si faltara
    // alguno, el require_once de otro que dependa de él detendría PHPUnit.
    public static function setUpBeforeClass(): void
    {
        foreach (self::FICHEROS as $fichero) {
            if (!is_file(self::SRC . $fichero)) {
                return;
            }
        }
        require_once self::SRC . 'ValidadorCurriculo.php';
    }

    /** @return array<string, Ciclo> */
    private function ciclos(): array
    {
        return [
            'DAW' => new Ciclo(1, 'DAW', 'Desarrollo de Aplicaciones Web', 'Superior'),
            'SMR' => new Ciclo(4, 'SMR', 'Sistemas Microinformáticos y Redes', 'Medio'),
        ];
    }

    private function validador(): ValidadorCurriculo
    {
        if (!class_exists('ValidadorCurriculo')) {
            $this->fail('Falta la clase ValidadorCurriculo (¿existen src/ValidadorCurriculo.php y las clases de 3.5?)');
        }

        return new ValidadorCurriculo($this->ciclos());
    }

    // Envía una petición POST real, como lo haría el navegador al enviar el formulario.
    private function post(array $datos): string
    {
        $contexto = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => 'Content-Type: application/x-www-form-urlencoded',
            'content'       => http_build_query($datos),
            'ignore_errors' => true,
        ]]);

        return (string) @file_get_contents(self::URL_PAGINA, false, $contexto);
    }

    public function test_existe_la_clase_de_validacion(): void
    {
        $this->assertFileExists(self::SRC . 'ValidadorCurriculo.php', 'Falta src/ValidadorCurriculo.php');
    }

    public function test_acepta_datos_validos_con_y_sin_video(): void
    {
        $validador = $this->validador();

        $this->assertSame([], $validador->validar(self::DATOS_VALIDOS), 'Con datos válidos, validar() debería devolver un array vacío.');
        $this->assertSame([], $validador->validar([...self::DATOS_VALIDOS, 'video' => '']), 'El vídeo es opcional: sin él, los datos siguen siendo válidos.');
    }

    public function test_valida_el_nombre_del_alumno(): void
    {
        $validador = $this->validador();

        $this->assertArrayHasKey('alumno', $validador->validar([...self::DATOS_VALIDOS, 'alumno' => '   ']), 'Un nombre de solo espacios debería dar error: ¿usas trim()?');
        $this->assertArrayHasKey('alumno', $validador->validar([...self::DATOS_VALIDOS, 'alumno' => str_repeat('a', 101)]), 'Un nombre de más de 100 caracteres debería dar error.');
        // 100 eñes son 100 caracteres, pero 200 bytes: con strlen() en vez de mb_strlen() daría error.
        $this->assertArrayNotHasKey('alumno', $validador->validar([...self::DATOS_VALIDOS, 'alumno' => str_repeat('ñ', 100)]), 'Un nombre de 100 caracteres con tildes o eñes debería ser válido: ¿usas mb_strlen()?');
    }

    public function test_el_ciclo_debe_estar_en_el_catalogo(): void
    {
        $validador = $this->validador();

        $this->assertArrayHasKey('ciclo', $validador->validar([...self::DATOS_VALIDOS, 'ciclo' => 'FP']), 'Un ciclo que no está en el catálogo debería dar error.');
        $this->assertArrayHasKey('ciclo', $validador->validar(['alumno' => 'Ana López']), 'Si no llega el ciclo, debería dar error (sin warnings: ¿usas ??).');
    }

    public function test_el_video_debe_ser_una_url_https(): void
    {
        $validador = $this->validador();

        $this->assertArrayHasKey('video', $validador->validar([...self::DATOS_VALIDOS, 'video' => 'no es una url']), 'Un vídeo que no es una URL debería dar error.');
        $this->assertArrayHasKey('video', $validador->validar([...self::DATOS_VALIDOS, 'video' => "javascript:alert('XSS')"]), 'Una URL javascript: debería dar error.');
        $this->assertArrayHasKey('video', $validador->validar([...self::DATOS_VALIDOS, 'video' => 'http://youtu.be/curriculo1']), 'Una URL que no empieza por https:// debería dar error.');
    }

    public function test_crearCurriculo_construye_el_objeto(): void
    {
        $ciclos = $this->ciclos();
        $this->validador();   // comprueba que la clase existe
        $validador = new ValidadorCurriculo($ciclos);

        $curriculo = $validador->crearCurriculo([...self::DATOS_VALIDOS, 'alumno' => '  Ana López  ', 'video' => ''], 1);

        $this->assertInstanceOf(Curriculo::class, $curriculo, 'crearCurriculo() debería devolver un objeto Curriculo.');
        $this->assertSame($ciclos['DAW'], $curriculo->ciclo, 'El currículo debería tener el mismo objeto Ciclo del catálogo.');
        $this->assertSame('Ana López', $curriculo->alumno, 'El nombre del alumno debería guardarse sin los espacios de los extremos.');
        $this->assertNull($curriculo->videoCurriculum, 'Sin vídeo, videoCurriculum debería ser null, no un string vacío.');
    }

    public function test_la_pagina_muestra_el_formulario_con_los_ciclos(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = (string) @file_get_contents(self::URL_PAGINA);

        // Cuatro ciclos del catálogo más la opción vacía "— Elige un ciclo —".
        $this->assertSame(
            5,
            substr_count($html, '<option value='),
            'El desplegable debería tener la opción vacía y una por cada uno de los 4 ciclos. ¿Están levantados nginx y php-fpm?'
        );
    }

    public function test_la_pagina_da_de_alta_con_datos_validos(): void
    {
        $html = $this->post(self::DATOS_VALIDOS);

        $this->assertNotSame(
            '',
            $html,
            'No se pudo acceder a ' . self::URL_PAGINA . '. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );
        $this->assertStringContainsString('Ana López (DAW)', $html, 'Con datos válidos, la página debería mostrar la descripción del currículo.');
        $this->assertStringContainsString('Técnico Superior', $html, 'La página debería mostrar la titulación del ciclo del currículo.');
    }

    public function test_la_pagina_muestra_errores_y_conserva_los_datos_escapados(): void
    {
        $html = $this->post([...self::DATOS_VALIDOS, 'alumno' => '<b>Ana</b>', 'ciclo' => 'FP']);

        $this->assertStringContainsString('Elige uno de los ciclos de la lista.', $html, 'Con un ciclo que no existe, la página debería mostrar el error.');
        $this->assertStringContainsString('value="&lt;b&gt;Ana&lt;/b&gt;"', $html, 'El formulario debería conservar el nombre escrito, escapado con e().');
        $this->assertStringNotContainsString('<b>Ana</b>', $html, 'El nombre escrito no debería llegar al HTML sin escapar.');
    }
}
