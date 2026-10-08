<?php

use PHPUnit\Framework\TestCase;

class RA4_3_XdebugTest extends TestCase
{
    private const SRC = __DIR__ . '/../src/';

    // Desde "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
    private const BASE = 'http://nginx/';

    // Se cargan las funciones solo si existen todos los ficheros: si faltara
    // alguno, el require_once de otro que dependa de él detendría PHPUnit.
    public static function setUpBeforeClass(): void
    {
        foreach (['Modelo.php', 'Publicable.php', 'Ciclo.php', 'Curriculo.php', 'RA4_depuracion.php'] as $fichero) {
            if (!is_file(self::SRC . $fichero)) {
                return;
            }
        }
        require_once self::SRC . 'RA4_depuracion.php';
    }

    private function requiereFunciones(): void
    {
        if (!function_exists('contarPorCiclo') || !function_exists('porcentaje')) {
            $this->fail('Faltan las funciones contarPorCiclo() y porcentaje() (¿existe src/RA4_depuracion.php?)');
        }
    }

    // Petición HTTP real, como la del navegador.
    private function get(string $pagina): string
    {
        return (string) @file_get_contents(self::BASE . $pagina);
    }

    public function test_xdebug_esta_activo_en_modo_debug(): void
    {
        $html = $this->get('RA4_xdebug.php');

        $this->assertStringContainsString('Xdebug: cargado', $html, 'Xdebug debería estar cargado en php-fpm. ¿Existe public/RA4_xdebug.php? ¿Están levantados nginx y php-fpm? (Repasa §7 de la preparación del entorno.)');
        $this->assertStringContainsString('Modo: debug', $html, 'Xdebug debería estar en modo debug (xdebug.mode=debug en php-fpm/xdebug.ini).');
    }

    public function test_existe_el_fichero_de_depuracion(): void
    {
        $this->assertFileExists(self::SRC . 'RA4_depuracion.php', 'Falta src/RA4_depuracion.php');
    }

    public function test_contarPorCiclo_cuenta_cada_ciclo(): void
    {
        $this->requiereFunciones();
        $daw = new Ciclo(1, 'DAW', 'Desarrollo de Aplicaciones Web', 'Superior');
        $dam = new Ciclo(2, 'DAM', 'Desarrollo de Aplicaciones Multiplataforma', 'Superior');

        $curriculos = [
            new Curriculo(1, 'Ana López', $daw),
            new Curriculo(2, 'Marcos Pérez', $dam),
            new Curriculo(3, 'Lucía Martín', $daw),
            new Curriculo(4, 'Pablo Sanz', $daw),
        ];

        $this->assertSame(['DAW' => 3, 'DAM' => 1], contarPorCiclo($curriculos), 'contarPorCiclo() debería contar 3 de DAW y 1 de DAM. Depura la línea que actualiza $cuenta: ¿qué devuelve $cuenta[$codigo]++?');
    }

    public function test_contarPorCiclo_sin_curriculos(): void
    {
        $this->requiereFunciones();

        $this->assertSame([], contarPorCiclo([]), 'Sin currículos, contarPorCiclo() debería devolver un array vacío.');
    }

    public function test_porcentaje_redondea_a_entero(): void
    {
        $this->requiereFunciones();

        $this->assertSame(50, porcentaje(2, 4), 'porcentaje(2, 4) debería ser 50. Evalúa intdiv(2, 4) en la consola de depuración.');
        $this->assertSame(25, porcentaje(1, 4), 'porcentaje(1, 4) debería ser 25.');
        $this->assertSame(33, porcentaje(1, 3), 'porcentaje(1, 3) debería ser 33 (redondeado a entero).');
    }

    public function test_la_pagina_de_resumen_muestra_los_datos_correctos(): void
    {
        $html = $this->get('RA4_resumen.php');

        $this->assertStringContainsString('DAW: 2 (50 %)', $html, 'El resumen debería mostrar "DAW: 2 (50 %)". ¿Existe public/RA4_resumen.php? ¿Has corregido las dos funciones?');
        $this->assertStringContainsString('DAM: 1 (25 %)', $html, 'El resumen debería mostrar "DAM: 1 (25 %)".');
    }
}
