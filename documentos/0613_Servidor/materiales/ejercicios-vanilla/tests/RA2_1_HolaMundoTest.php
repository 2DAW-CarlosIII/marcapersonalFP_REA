<?php

use PHPUnit\Framework\TestCase;

class RA2_1_HolaMundoTest extends TestCase
{
    public function test_el_script_existe(): void
    {
        $this->assertFileExists(__DIR__ . '/../public/RA2_holaMundo.php', 'Falta public/RA2_holaMundo.php');
    }

    public function test_responde_con_saludo_y_fecha(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA2_holaMundo.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA2_holaMundo.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );
        $this->assertStringContainsStringIgnoringCase('hola', $html, 'La página no contiene ningún saludo.');
        $this->assertMatchesRegularExpression(
            '/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}/',
            $html,
            'No se encuentra una fecha con formato d/m/Y H:i:s generada por PHP.'
        );
    }
}
