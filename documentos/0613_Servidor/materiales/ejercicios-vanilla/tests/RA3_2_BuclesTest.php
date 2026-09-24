<?php

use PHPUnit\Framework\TestCase;

class RA3_2_BuclesTest extends TestCase
{
    public function test_el_script_existe(): void
    {
        $this->assertFileExists(__DIR__ . '/../public/RA3_bucles.php', 'Falta public/RA3_bucles.php');
    }

    public function test_cada_bucle_genera_lo_esperado(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA3_bucles.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA3_bucles.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );

        // Ejercicio 1: for — seis opciones, de 2020 a 2025 incluidos
        $this->assertSame(
            6,
            substr_count($html, '<option value='),
            'El for debería generar 6 opciones (de 2020 a 2025, ambos incluidos).'
        );
        $this->assertStringContainsString('<option value="2025">2025</option>', $html, 'Falta la última opción: ¿la condición del for es <= 2025?');

        // Ejercicio 2: while — 700 + 700 + 700 >= 2000 tras tres iteraciones
        $this->assertStringContainsString('Años necesarios: 3', $html, 'El while debería hacer 3 iteraciones (700 horas al año hasta llegar a 2000).');

        // Ejercicio 3: foreach — una línea por currículo
        $this->assertSame(
            3,
            substr_count($html, '<li class="curriculo">'),
            'El foreach debería generar un <li class="curriculo"> por cada uno de los tres currículos.'
        );

        // Ejercicio 4: foreach con clave => valor
        $this->assertStringContainsString('<dt>video</dt>', $html, 'El foreach con $campo => $valor debería mostrar también las claves (<dt>video</dt>).');

        // Ejercicio 5: continue — de los cuatro currículos, solo tres tienen vídeo
        $this->assertSame(
            3,
            substr_count($html, '<tr class="con-video">'),
            'La tabla debería tener 3 filas con vídeo: ¿salta continue el currículo sin vídeo?'
        );
        $this->assertStringNotContainsString('Pablo Ruiz', $html, 'Pablo Ruiz no tiene vídeo: continue debería saltarlo.');
    }
}
