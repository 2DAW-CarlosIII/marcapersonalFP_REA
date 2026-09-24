<?php

use PHPUnit\Framework\TestCase;

class RA3_4_FuncionesTest extends TestCase
{
    private const FICHERO_FUNCIONES = __DIR__ . '/../src/RA3_funciones.php';

    private const CURRICULOS = [
        ['alumno' => 'Ana López',    'ciclo' => 'DAW',  'video' => 'https://youtu.be/curriculo1'],
        ['alumno' => 'Marcos Pérez', 'ciclo' => 'DAM',  'video' => 'https://youtu.be/curriculo2'],
        ['alumno' => 'Laura García', 'ciclo' => 'ASIR', 'video' => 'https://youtu.be/curriculo3'],
    ];

    // Se carga el fichero de funciones una sola vez, igual que haría una página
    // con require_once. Si no existe, cada test lo indicará con su propio mensaje.
    public static function setUpBeforeClass(): void
    {
        if (is_file(self::FICHERO_FUNCIONES)) {
            require_once self::FICHERO_FUNCIONES;
        }
    }

    private function requiereFuncion(string $nombre): void
    {
        if (!function_exists($nombre)) {
            $this->fail("Falta la función $nombre() en src/RA3_funciones.php");
        }
    }

    public function test_el_fichero_de_funciones_existe_y_usa_tipos_estrictos(): void
    {
        $this->assertFileExists(self::FICHERO_FUNCIONES, 'Falta src/RA3_funciones.php');
        $this->assertMatchesRegularExpression(
            '/declare\s*\(\s*strict_types\s*=\s*1\s*\)/',
            file_get_contents(self::FICHERO_FUNCIONES),
            'src/RA3_funciones.php debería empezar con declare(strict_types=1);'
        );
    }

    public function test_horasRestantes_declara_sus_tipos_y_devuelve_la_resta(): void
    {
        $this->requiereFuncion('horasRestantes');

        $this->assertSame(650, horasRestantes(2000, 1350), 'horasRestantes(2000, 1350) debería devolver (no mostrar) 650.');

        $funcion = new ReflectionFunction('horasRestantes');
        $this->assertSame(
            ['int', 'int'],
            array_map(fn(ReflectionParameter $parametro): string => (string) $parametro->getType(), $funcion->getParameters()),
            'Los dos parámetros de horasRestantes() deberían declararse como int.'
        );
        $this->assertSame('int', (string) $funcion->getReturnType(), 'horasRestantes() debería declarar el tipo de retorno : int.');
    }

    public function test_titulacion_tiene_valor_por_defecto(): void
    {
        $this->requiereFuncion('titulacion');

        $this->assertSame('Técnico Superior', titulacion(), 'Sin argumentos, titulacion() debería usar el valor por defecto \'Superior\'.');
        $this->assertSame('Técnico', titulacion('Medio'), 'titulacion(\'Medio\') debería devolver \'Técnico\'.');
    }

    public function test_enlaceVideo_acepta_null(): void
    {
        $this->requiereFuncion('enlaceVideo');

        // Si el parámetro no fuera ?string, pasar null produciría un TypeError.
        $this->assertSame('Sin vídeo', enlaceVideo(null), 'enlaceVideo(null) debería devolver \'Sin vídeo\': ¿es el parámetro ?string?');
        $this->assertStringContainsString(
            'href="https://youtu.be/curriculo1"',
            enlaceVideo('https://youtu.be/curriculo1'),
            'Con una URL, enlaceVideo() debería devolver un enlace a ella.'
        );
    }

    public function test_nombresAlumnos_extrae_los_nombres(): void
    {
        $this->requiereFuncion('nombresAlumnos');

        $this->assertSame(
            ['Ana López', 'Marcos Pérez', 'Laura García'],
            nombresAlumnos(self::CURRICULOS),
            'nombresAlumnos() debería devolver, en el mismo orden, el campo \'alumno\' de cada currículo.'
        );
    }

    public function test_curriculosDeCiclo_filtra_y_reindexa(): void
    {
        $this->requiereFuncion('curriculosDeCiclo');

        $curriculos = [...self::CURRICULOS, ['alumno' => 'Pablo Ruiz', 'ciclo' => 'DAW', 'video' => '']];
        $deDaw = curriculosDeCiclo($curriculos, 'DAW');

        $this->assertSame(['Ana López', 'Pablo Ruiz'], array_column($deDaw, 'alumno'), 'curriculosDeCiclo(..., \'DAW\') debería devolver solo los currículos de DAW.');
        $this->assertSame([0, 1], array_keys($deDaw), 'El resultado debería estar reindexado desde 0: ¿has usado array_values()?');
    }

    public function test_ordenarPorAlumno_ordena_sin_modificar_el_original(): void
    {
        $this->requiereFuncion('ordenarPorAlumno');

        $curriculos = self::CURRICULOS;
        $ordenados = ordenarPorAlumno($curriculos);

        $this->assertSame(
            ['Ana López', 'Laura García', 'Marcos Pérez'],
            array_column($ordenados, 'alumno'),
            'ordenarPorAlumno() debería devolver los currículos ordenados por alumno.'
        );
        $this->assertSame('Marcos Pérez', $curriculos[1]['alumno'], 'El array original no debería cambiar: el parámetro se recibe por valor (sin &).');
    }

    public function test_la_pagina_usa_las_funciones(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA3_funciones.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA3_funciones.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );
        $this->assertStringContainsString('Horas restantes: 650', $html, 'La página debería mostrar el resultado de horasRestantes(2000, 1350).');
        $this->assertMatchesRegularExpression(
            '/Ana López.*Laura García.*Marcos Pérez/s',
            $html,
            'La página debería listar los currículos ordenados con ordenarPorAlumno().'
        );
    }
}
