<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RA5_5_ControladoresTest extends TestCase
{
    private const CONTROLADORES = 'App\\Http\\Controllers\\';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Acción (Clase@método) de la ruta con ese nombre, o cadena vacía si no existe. */
    private function accion(string $nombre): string
    {
        return (string) Route::getRoutes()->getByName($nombre)?->getActionName();
    }

    public function test_las_rutas_de_proyectos_usan_su_controlador(): void
    {
        foreach (['index', 'show', 'create', 'edit'] as $metodo) {
            $this->assertSame(self::CONTROLADORES . "ProyectosController@$metodo", $this->accion("proyectos.$metodo"));
        }
    }

    public function test_las_rutas_de_proyectos_estan_en_su_fichero(): void
    {
        $this->assertFileExists(base_path('routes/proyectos.php'));
        $this->assertStringContainsString('proyectos.php', (string) file_get_contents(base_path('routes/web.php')), 'Incluye routes/proyectos.php desde routes/web.php.');
    }

    public function test_pagina_principal_con_controlador_invocable(): void
    {
        $accion = Route::getRoutes()->match(Request::create('/'))->getActionName();

        $this->assertStringStartsWith(self::CONTROLADORES . 'HomeController', $accion);
        $this->get('/')->assertOk()->assertViewIs('home');
    }

    public function test_listado_completo(): void
    {
        $this->get('/proyectos')
            ->assertOk()
            ->assertViewIs('proyectos.index')
            ->assertViewHas('proyectos', fn ($proyectos) => count($proyectos) === 10)
            ->assertSeeText('Tecnologías de la Información')
            ->assertSeeText('Turismo');
    }

    public function test_detalle_con_su_estado(): void
    {
        $this->get('/proyectos/show/1')
            ->assertOk()
            ->assertViewIs('proyectos.show')
            ->assertViewHas('proyecto', fn ($proyecto) => $proyecto['nombre'] === 'Diseño Gráfico')
            ->assertSeeText('Diseño Gráfico')
            ->assertSeeText('Proyecto suspenso');

        $this->get('/proyectos/show/2')
            ->assertSeeText('Electrónica')
            ->assertSeeText('Proyecto aprobado');
    }

    public function test_edicion(): void
    {
        $this->get('/proyectos/edit/3')
            ->assertOk()
            ->assertViewIs('proyectos.edit')
            ->assertViewHas('proyecto', fn ($proyecto) => $proyecto['nombre'] === 'Ingeniería Civil')
            ->assertSeeText('Ingeniería Civil');
    }

    public function test_un_proyecto_inexistente_da_404(): void
    {
        $this->get('/proyectos/show/99')->assertNotFound();
        $this->get('/proyectos/edit/99')->assertNotFound();
    }

    public function test_perfil_con_controlador_invocable(): void
    {
        $this->assertStringStartsWith(self::CONTROLADORES . 'PerfilController', $this->accion('perfil'));
        $this->get('/perfil/3')->assertOk()->assertSeeText('Visualizar el currículo de 3');
    }
}
