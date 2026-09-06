<?php

use PHPUnit\Framework\TestCase;

class RA2_3_TiposVariablesOperadoresTest extends TestCase
{
    public function test_el_script_existe(): void
    {
        $this->assertFileExists(__DIR__ . '/../public/RA2_tiposVariablesOperadores.php', 'Falta public/RA2_tiposVariablesOperadores.php');
    }

    public function test_muestra_tipos_constantes_y_operadores(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA2_tiposVariablesOperadores.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA2_tiposVariablesOperadores.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );

        // Ejercicio 2: constantes
        $this->assertStringContainsString('CIFP Carlos III', $html, 'Falta el echo de la constante NOMBRE_CENTRO.');
        $this->assertStringContainsString('30012345', $html, 'Falta el echo de la constante CODIGO_CENTRO.');

        // Ejercicio 1: var_dump muestra tipo y valor exactos
        $this->assertStringContainsString('string(3) "DAW"', $html, '$codCiclo debería ser un string y mostrarse con var_dump.');
        $this->assertStringContainsString('int(2000)', $html, '$horas debería ser un int y mostrarse con var_dump.');
        $this->assertStringContainsString('float(8.75)', $html, '$notaCorte debería ser un float y mostrarse con var_dump.');

        // Ejercicio 3: operadores aritméticos y de comparación
        $this->assertStringContainsString('650', $html, 'Falta el resultado de horasTotales - horasCursadas (2000 - 1350 = 650).');
        $this->assertSame(
            1,
            substr_count($html, 'bool(false)'),
            'La comparación estricta "10" === 10 debería dar bool(false) (los tipos no coinciden).'
        );
    }
}
