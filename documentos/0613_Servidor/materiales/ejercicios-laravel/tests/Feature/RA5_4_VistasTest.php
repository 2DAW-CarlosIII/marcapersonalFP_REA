<?php

namespace Tests\Feature;

use Tests\TestCase;

class RA5_4_VistasTest extends TestCase
{
    private const ID = 7;

    protected function setUp(): void
    {
        parent::setUp();
        // Las vistas se prueban sin los estilos; su compilación se comprueba en test_estructura_de_las_vistas.
        $this->withoutVite();
    }

    public function test_pantalla_principal(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('home')
            ->assertViewHas('centro', 'CIFP Carlos III')
            ->assertSeeText('Pantalla principal');
    }

    public function test_listado_de_proyectos(): void
    {
        $this->get('/proyectos')
            ->assertOk()
            ->assertViewIs('proyectos.index')
            ->assertViewHas('proyectos')
            ->assertSeeText('Tecnologías de la Información')
            ->assertSeeText('Diseño Gráfico');
    }

    public function test_detalle_y_edicion_de_un_proyecto(): void
    {
        $this->get('/proyectos/show/' . self::ID)
            ->assertOk()
            ->assertViewIs('proyectos.show')
            ->assertViewHas('id', self::ID)
            ->assertSeeText('Vista detalle proyecto ' . self::ID);

        $this->get('/proyectos/edit/' . self::ID)
            ->assertOk()
            ->assertViewIs('proyectos.edit')
            ->assertViewHas('id', self::ID)
            ->assertSeeText('Modificar proyecto ' . self::ID);
    }

    public function test_alta_de_un_proyecto(): void
    {
        $this->get('/proyectos/create')
            ->assertOk()
            ->assertViewIs('proyectos.create')
            ->assertSeeText('Añadir proyecto');
    }

    public function test_perfil_con_y_sin_id(): void
    {
        $this->get('/perfil/3')
            ->assertOk()
            ->assertViewIs('perfil')
            ->assertSeeText('Visualizar el currículo de 3');

        $this->get('/perfil')
            ->assertOk()
            ->assertSeeText('Visualizar el currículo propio');
    }

    public function test_las_paginas_comparten_el_layout(): void
    {
        foreach (['/', '/proyectos', '/proyectos/create'] as $url) {
            $this->get($url)
                ->assertSee('images/mp-logo.png', false)
                ->assertSeeText('CIFP Carlos III');
        }
    }

    public function test_estructura_de_las_vistas(): void
    {
        $layout = (string) @file_get_contents(resource_path('views/components/layout.blade.php'));

        $this->assertMatchesRegularExpression('/\{\{\s*\$slot\s*\}\}/', $layout, 'El layout (components/layout.blade.php) debe mostrar {{ $slot }}.');
        $this->assertStringContainsString('@vite(', $layout, 'El layout debe enlazar los estilos con @vite.');
        $this->assertFileExists(resource_path('views/components/navbar.blade.php'));
        $this->assertFileExists(resource_path('views/components/tarjeta-proyecto.blade.php'));
        $this->assertFileExists(public_path('build/manifest.json'), 'Compila los estilos: npm run build.');
    }
}
