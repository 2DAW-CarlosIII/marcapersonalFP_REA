<?php

use PHPUnit\Framework\TestCase;

class RA3_5_PooTest extends TestCase
{
    private const SRC = __DIR__ . '/../src/';

    private const FICHEROS = ['Modelo.php', 'Publicable.php', 'Ciclo.php', 'Curriculo.php'];

    // Se cargan las clases una sola vez, igual que haría una página con
    // require_once. Solo si existen los cuatro ficheros: si faltara alguno,
    // el require_once de otro fichero que dependa de él detendría PHPUnit.
    public static function setUpBeforeClass(): void
    {
        foreach (self::FICHEROS as $fichero) {
            if (!is_file(self::SRC . $fichero)) {
                return;
            }
        }
        require_once self::SRC . 'Curriculo.php';
    }

    private function requiereClases(): void
    {
        foreach (['Modelo', 'Ciclo', 'Curriculo'] as $clase) {
            if (!class_exists($clase)) {
                $this->fail("Falta la clase $clase (¿existen los cuatro ficheros de src/ y se cargan entre sí con require_once?)");
            }
        }
        if (!interface_exists('Publicable')) {
            $this->fail('Falta la interfaz Publicable en src/Publicable.php');
        }
    }

    private function daw(): Ciclo
    {
        return new Ciclo(1, 'DAW', 'Desarrollo de Aplicaciones Web', 'Superior');
    }

    public function test_existen_los_ficheros_de_las_clases(): void
    {
        foreach (self::FICHEROS as $fichero) {
            $this->assertFileExists(self::SRC . $fichero, "Falta src/$fichero");
        }
    }

    public function test_los_metodos_de_ciclo_usan_sus_propiedades(): void
    {
        $this->requiereClases();
        $daw = $this->daw();

        $this->assertSame('DAW — Desarrollo de Aplicaciones Web', $daw->descripcion(), 'descripcion() debería devolver "código — nombre".');
        $this->assertSame('Técnico Superior', $daw->titulacion(), 'titulacion() debería devolver "Técnico Superior" para un ciclo de grado Superior.');
    }

    public function test_ciclo_define_constantes_de_clase(): void
    {
        $this->requiereClases();

        $this->assertSame('Medio', Ciclo::GRADO_MEDIO, 'Falta la constante de clase Ciclo::GRADO_MEDIO.');
        $this->assertSame('Superior', Ciclo::GRADO_SUPERIOR, 'Falta la constante de clase Ciclo::GRADO_SUPERIOR.');
    }

    public function test_desdeArray_es_un_metodo_de_fabrica(): void
    {
        $this->requiereClases();

        $dam = Ciclo::desdeArray([
            'id'       => 2,
            'codCiclo' => 'DAM',
            'nombre'   => 'Desarrollo de Aplicaciones Multiplataforma',
            'grado'    => 'Superior',
        ]);

        $this->assertInstanceOf(Ciclo::class, $dam, 'Ciclo::desdeArray() debería devolver un objeto Ciclo.');
        $this->assertSame('DAM', $dam->codCiclo, 'El Ciclo creado con desdeArray() debería tomar su codCiclo del array.');
    }

    public function test_codCiclo_es_de_solo_lectura(): void
    {
        $this->requiereClases();

        $this->assertTrue(
            (new ReflectionProperty(Ciclo::class, 'codCiclo'))->isReadOnly(),
            'La propiedad codCiclo debería ser readonly.'
        );
    }

    public function test_ciclo_hereda_de_la_clase_abstracta_modelo(): void
    {
        $this->requiereClases();
        $daw = $this->daw();

        $this->assertInstanceOf(Modelo::class, $daw, 'Ciclo debería heredar de Modelo (class Ciclo extends Modelo).');
        $this->assertTrue((new ReflectionClass(Modelo::class))->isAbstract(), 'Modelo debería ser una clase abstracta.');
        $this->assertSame(1, $daw->id, 'El id debería inicializarse en Modelo a través de parent::__construct($id).');
    }

    public function test_curriculo_encapsula_su_estado_e_implementa_publicable(): void
    {
        $this->requiereClases();
        $curriculo = new Curriculo(1, 'Ana López', $this->daw(), 'https://youtu.be/curriculo1');

        $this->assertInstanceOf(Publicable::class, $curriculo, 'Curriculo debería implementar la interfaz Publicable.');
        $this->assertFalse($curriculo->estaPublicado(), 'Un currículo recién creado debería estar en borrador.');

        $curriculo->publicar();
        $this->assertTrue($curriculo->estaPublicado(), 'Tras publicar(), estaPublicado() debería devolver true.');

        $this->assertTrue(
            (new ReflectionProperty(Curriculo::class, 'publicado'))->isPrivate(),
            'La propiedad publicado debería ser private: solo se cambia a través de publicar().'
        );
    }

    public function test_curriculo_contiene_un_ciclo(): void
    {
        $this->requiereClases();
        $dam = new Ciclo(2, 'DAM', 'Desarrollo de Aplicaciones Multiplataforma', 'Superior');

        $this->assertSame(
            'Ana López (DAW)',
            (new Curriculo(1, 'Ana López', $this->daw()))->descripcion(),
            'descripcion() debería devolver "alumno (código del ciclo)", tomando el código del objeto Ciclo.'
        );
        $this->assertNull(
            (new Curriculo(2, 'Marcos Pérez', $dam))->videoCurriculum,
            'Sin cuarto argumento, videoCurriculum debería valer null por defecto.'
        );
    }

    public function test_la_pagina_usa_las_clases(): void
    {
        // Petición HTTP real, tal y como la vería el navegador — pero desde
        // "workspace" se usa el nombre del servicio ("nginx"), no "localhost".
        $html = @file_get_contents('http://nginx/RA3_poo.php');

        $this->assertNotFalse(
            $html,
            'No se pudo acceder a http://nginx/RA3_poo.php. ¿Están levantados nginx y php-fpm? (docker compose up -d nginx php-fpm)'
        );
        $this->assertStringContainsString('Titulación: Técnico Superior', $html, 'La página debería mostrar $daw->titulacion().');
        // publicar() se llamó a través de $mismoCurriculo: si fuera una copia, saldría "en borrador".
        $this->assertStringContainsString('Ana López (DAW): publicado', $html, 'Las dos variables apuntan al mismo objeto: el currículo debería aparecer publicado.');
    }
}
