<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Issue "Las rutas del ePortfolio" (5.3).
 *
 * Ejecutar con: php artisan test --filter=RA5_3_DireccionesTest
 */
class RA5_3_DireccionesTest extends TestCase
{
    private const ID = 7;

    private const EVIDENCIAS = ['evidencias.index', 'evidencias.show', 'evidencias.create', 'evidencias.edit'];

    protected function setUp(): void
    {
        parent::setUp();
        // Si la portada ya devuelve una vista con @vite (5.4), el test no necesita los estilos compilados.
        $this->withoutVite();
    }

    public function test_portada(): void
    {
        $this->get('/')->assertOk()->assertSeeText('Portada del ePortfolio');
    }

    public function test_listado_y_alta_de_evidencias(): void
    {
        $this->get('/evidencias')->assertOk()->assertSeeText('Listado de evidencias');
        $this->get('/evidencias/create')->assertOk()->assertSeeText('Nueva evidencia');
    }

    public function test_detalle_y_edicion_de_una_evidencia(): void
    {
        $this->get('/evidencias/show/' . self::ID)->assertOk()->assertSeeText('Evidencia ' . self::ID);
        $this->get('/evidencias/edit/' . self::ID)->assertOk()->assertSeeText('Modificar evidencia ' . self::ID);
    }

    public function test_perfil_con_y_sin_id(): void
    {
        $this->get('/perfil/3')->assertOk()->assertSeeText('Perfil del usuario 3');
        $this->get('/perfil')->assertOk()->assertSeeText('Mi perfil');
    }

    public function test_un_id_no_numerico_da_404(): void
    {
        foreach (['/evidencias/show/abc', '/evidencias/edit/abc', '/perfil/abc'] as $direccion) {
            $this->get($direccion)->assertNotFound();
        }
    }

    public function test_rutas_con_nombre(): void
    {
        $esperadas = [
            'evidencias.index' => [[], '/evidencias'],
            'evidencias.show' => [[self::ID], '/evidencias/show/' . self::ID],
            'evidencias.create' => [[], '/evidencias/create'],
            'evidencias.edit' => [[self::ID], '/evidencias/edit/' . self::ID],
            'perfil' => [[], '/perfil'],
            'acerca' => [[], '/acerca'],
        ];

        foreach ($esperadas as $nombre => [$parametros, $direccion]) {
            $this->assertTrue(Route::has($nombre), "Falta el nombre de ruta {$nombre}.");
            $this->assertSame(url($direccion), route($nombre, $parametros), "La ruta {$nombre} debe llevar a {$direccion}.");
        }
    }

    public function test_las_rutas_de_evidencias_van_en_un_grupo(): void
    {
        foreach (self::EVIDENCIAS as $nombre) {
            $ruta = Route::getRoutes()->getByName($nombre);
            $this->assertSame(
                'evidencias',
                trim((string) $ruta?->getPrefix(), '/'),
                "La ruta {$nombre} debe estar en un grupo con prefix('evidencias')."
            );
        }
    }

    public function test_acerca_sale_de_la_configuracion(): void
    {
        $respuesta = $this->getJson('/acerca');
        $respuesta->assertOk();
        $this->assertSame(config('app.name'), $respuesta->json('aplicacion'), '/acerca debe devolver en aplicacion el nombre de la aplicación.');
        $this->assertSame(config('eportfolio.centro'), $respuesta->json('centro'), '/acerca debe devolver en centro el nombre del centro.');

        // Los valores deben salir de la configuración, no estar escritos en la ruta.
        config(['app.name' => 'Aplicación de prueba', 'eportfolio.centro' => 'Centro de prueba']);

        $respuesta = $this->getJson('/acerca');
        $this->assertSame('Aplicación de prueba', $respuesta->json('aplicacion'), "En /acerca, aplicacion debe leerse con config('app.name').");
        $this->assertSame('Centro de prueba', $respuesta->json('centro'), "En /acerca, centro debe leerse con config('eportfolio.centro').");
    }
}
