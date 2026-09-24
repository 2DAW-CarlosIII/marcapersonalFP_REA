<?php

use PHPUnit\Framework\TestCase;

class RA3_1_DecisionesTest extends TestCase
{
    public function test_el_script_existe(): void
    {
        $this->assertFileExists(__DIR__ . '/../public/RA3_decisiones.php', 'Falta public/RA3_decisiones.php');
    }

    public function test_cada_decision_elige_la_rama_correcta(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA3_decisiones.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA3_decisiones.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );

        // Ejercicio 1: if/elseif/else con $grado = 'Superior'
        $this->assertStringContainsString('Ciclo de Grado Superior', $html, 'Con $grado = \'Superior\', el if debería mostrar "Ciclo de Grado Superior".');

        // Ejercicio 2: con vídeo pero sin texto, ni && (completo) ni el else (vacío)
        $this->assertStringContainsString('Currículo incompleto', $html, 'Con vídeo y sin texto, la rama del || debería mostrar "Currículo incompleto".');

        // Ejercicio 3: match
        $this->assertStringContainsString('Informática y Comunicaciones', $html, 'match debería devolver "Informática y Comunicaciones" para \'INF\'.');

        // Ejercicio 4: ternario y ??
        $this->assertStringContainsString('Técnico Superior', $html, 'El ternario debería elegir "Técnico Superior".');
        $this->assertStringContainsString('Sin vídeo de presentación', $html, 'El operador ?? debería mostrar el valor por defecto cuando falta la clave \'video\'.');

        // Ejercicio 5: sintaxis alternativa — solo debe llegar al navegador una de las dos ramas
        $this->assertStringContainsString('Currículo publicado', $html, 'Con $publicado = true debería mostrarse "Currículo publicado".');
        $this->assertStringNotContainsString(
            'Currículo en borrador',
            $html,
            'La rama else no debería llegar al navegador: ¿están los dos párrafos dentro de if (): / else: / endif;?'
        );
    }
}
