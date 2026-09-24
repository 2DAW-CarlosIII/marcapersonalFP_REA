<?php

use PHPUnit\Framework\TestCase;

class RA3_7_ComentariosTest extends TestCase
{
    private const FICHERO_FUNCIONES = __DIR__ . '/../src/RA3_funciones.php';

    public static function setUpBeforeClass(): void
    {
        if (is_file(self::FICHERO_FUNCIONES)) {
            require_once self::FICHERO_FUNCIONES;
        }
    }

    private function docblockDe(string $funcion): string
    {
        if (!function_exists($funcion)) {
            $this->fail("Falta la función $funcion() en src/RA3_funciones.php (ejercicios de 3.4)");
        }

        // getDocComment() devuelve el docblock /** ... */ que precede a la función, o false si no lo hay.
        return (string) (new ReflectionFunction($funcion))->getDocComment();
    }

    public function test_el_script_existe(): void
    {
        $this->assertFileExists(__DIR__ . '/../public/RA3_comentarios.php', 'Falta public/RA3_comentarios.php');
    }

    public function test_solo_el_comentario_html_llega_al_navegador(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA3_comentarios.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA3_comentarios.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );
        $this->assertStringContainsString('Currículos del CIFP Carlos III', $html, 'Falta el título de la página.');
        $this->assertStringContainsString('<!-- Comentario HTML', $html, 'El comentario HTML debería llegar al navegador (está fuera de <?php ?>).');
        $this->assertStringNotContainsString('Comentario de una línea', $html, 'El comentario PHP de una línea no debería llegar al navegador. ¿Queda algún ?> dentro de un comentario?');
        $this->assertStringNotContainsString('Nota interna', $html, 'El comentario PHP de varias líneas no debería llegar al navegador.');
    }

    public function test_curriculosDeCiclo_tiene_docblock(): void
    {
        $docblock = $this->docblockDe('curriculosDeCiclo');

        $this->assertStringContainsString('@param', $docblock, 'curriculosDeCiclo() debería tener un docblock /** ... */ con @param.');
        $this->assertStringContainsString('@return', $docblock, 'El docblock de curriculosDeCiclo() debería incluir @return.');
    }

    public function test_ordenarPorAlumno_tiene_docblock(): void
    {
        $docblock = $this->docblockDe('ordenarPorAlumno');

        $this->assertStringContainsString('@param', $docblock, 'ordenarPorAlumno() debería tener un docblock /** ... */ con @param.');
        $this->assertStringContainsString('@return', $docblock, 'El docblock de ordenarPorAlumno() debería incluir @return.');
    }
}
