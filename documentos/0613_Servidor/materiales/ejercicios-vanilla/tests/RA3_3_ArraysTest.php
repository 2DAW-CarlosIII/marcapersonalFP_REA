<?php

use PHPUnit\Framework\TestCase;

class RA3_3_ArraysTest extends TestCase
{
    public function test_el_script_existe(): void
    {
        $this->assertFileExists(__DIR__ . '/../public/RA3_arrays.php', 'Falta public/RA3_arrays.php');
    }

    public function test_cada_operacion_con_arrays_da_lo_esperado(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA3_arrays.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA3_arrays.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );

        // Ejercicio 1: añadir con [] y buscar con in_array
        $this->assertStringContainsString('Número de ciclos: 3', $html, '$ciclos[] = \'ASIR\' debería dejar el array con 3 ciclos.');
        $this->assertStringContainsString('SMR no ofertado', $html, 'in_array(\'SMR\', $ciclos, true) debería ser false.');

        // Ejercicio 2: recorrer el array interior de un array multidimensional
        $this->assertSame(
            4,
            substr_count($html, '<li class="modulo">'),
            'El foreach sobre $ciclo[\'modulos\'] debería generar un <li class="modulo"> por cada uno de los 4 módulos.'
        );
        $this->assertStringContainsString('0613 — Desarrollo web en entorno servidor', $html, 'Cada módulo debería mostrarse como "código — nombre".');

        // Ejercicio 3: array_column + sort + implode — sin sort() el orden sería el original
        $this->assertStringContainsString(
            'Alumnos: Ana López, Laura García, Marcos Pérez',
            $html,
            'Los alumnos deberían salir en orden alfabético: ¿has llamado a sort() antes de implode()?'
        );

        // Ejercicio 4: desestructuración por clave
        $this->assertStringContainsString('Segundo currículo: Marcos Pérez (DAM)', $html, 'La desestructuración de $curriculos[1] debería dar Marcos Pérez (DAM).');

        // Ejercicio 5: asignar un array hace una copia
        $this->assertStringContainsString('Original: 3 · Copia: 4', $html, 'Añadir a $copia no debería cambiar el número de elementos de $curriculos.');
    }
}
