<?php

use PHPUnit\Framework\TestCase;

class RA2_2_SintaxisSalidaTest extends TestCase
{
    public function test_el_script_existe(): void
    {
        $this->assertFileExists(__DIR__ . '/../public/RA2_sintaxisSalida.php', 'Falta public/RA2_sintaxisSalida.php');
    }

    public function test_muestra_la_ficha_del_ciclo_con_echo_y_con_print(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA2_sintaxisSalida.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA2_sintaxisSalida.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );

        // La ficha debe aparecer dos veces: una generada con echo (ejercicio 1)
        // y otra con print + concatenación (ejercicio 2).
        $this->assertSame(
            2,
            substr_count($html, 'Desarrollo de Aplicaciones Web'),
            'El nombre del ciclo debería aparecer dos veces (una por echo, otra por print).'
        );
        $this->assertSame(
            2,
            substr_count($html, 'Informática y Comunicaciones'),
            'La familia profesional debería aparecer dos veces (una por echo, otra por print).'
        );
        $this->assertSame(
            2,
            substr_count($html, '2000 horas'),
            'Las horas del ciclo deberían aparecer dos veces (una por echo, otra por print).'
        );
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($html, '<strong>'),
            'Cada ficha debería resaltar el nombre del ciclo con <strong>.'
        );
    }
}
