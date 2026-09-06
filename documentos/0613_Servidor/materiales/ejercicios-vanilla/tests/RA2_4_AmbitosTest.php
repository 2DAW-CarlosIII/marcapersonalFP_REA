<?php

use PHPUnit\Framework\TestCase;

class RA2_4_AmbitosTest extends TestCase
{
    public function test_el_script_existe(): void
    {
        $this->assertFileExists(__DIR__ . '/../public/RA2_ambitos.php', 'Falta public/RA2_ambitos.php');
    }

    public function test_muestra_los_resultados_de_cada_ambito(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA2_ambitos.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA2_ambitos.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );

        // Ejercicio 1: ámbito local
        $this->assertStringContainsString('Desarrollo de Aplicaciones Web', $html, 'Falta la llamada a mostrarCiclo().');

        // Ejercicio 2: ámbito global con "global" — si falta la palabra clave,
        // $totalCiclosMostrados no llegaría a incrementarse y el texto no aparecería.
        $this->assertStringContainsString('Ciclos mostrados: 3', $html, 'registrarVisita() debería incrementar la variable global en cada llamada (global $totalCiclosMostrados;).');

        // Ejercicio 3: una constante es visible dentro de una función sin "global"
        $this->assertStringContainsString('<footer>CIFP Carlos III</footer>', $html, 'pie() debería mostrar NOMBRE_CENTRO sin usar global.');

        // Ejercicio 4: static — si faltara "static", nunca se llegaría a "número 3"
        $this->assertStringContainsString('Visita número 3', $html, 'contadorVisitas() debería usar static para conservar el valor entre llamadas.');
    }
}
