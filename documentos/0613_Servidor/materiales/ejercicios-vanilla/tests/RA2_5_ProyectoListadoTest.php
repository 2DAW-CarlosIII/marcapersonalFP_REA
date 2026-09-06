<?php

use PHPUnit\Framework\TestCase;

class RA2_5_ProyectoListadoTest extends TestCase
{
    public function test_el_script_existe(): void
    {
        $this->assertFileExists(__DIR__ . '/../public/RA2_proyectoListado.php', 'Falta public/RA2_proyectoListado.php');
    }

    public function test_lista_los_tres_curriculos(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA2_proyectoListado.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA2_proyectoListado.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );

        $this->assertStringContainsString('CIFP Carlos III', $html, 'Falta el título con NOMBRE_CENTRO.');
        $this->assertStringContainsString('Ana López', $html, 'Falta el currículo de Ana López.');
        $this->assertStringContainsString('Marcos Pérez', $html, 'Falta el currículo de Marcos Pérez.');
        $this->assertStringContainsString('Laura García', $html, 'Falta el currículo de Laura García.');
        $this->assertSame(
            3,
            substr_count($html, '<li>'),
            'mostrarCurriculo() debería llamarse una vez por cada uno de los tres currículos.'
        );
    }
}
