<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RA5_3_RutasTest extends TestCase
{
    private const ID = 7;

    protected function setUp(): void
    {
        parent::setUp();
        // Desde 5.7, el alta y la edición exigen sesión: estos tests entran como Ana López (4.2).
        $this->actingAs(new User(['name' => 'Ana López', 'email' => 'ana.lopez@marcapersonalfp.test']));
    }

    public function test_saludo_con_y_sin_nombre(): void
    {
        $this->get('/hola')->assertOk()->assertSeeText('¡Hola, mundo!');
        $this->get('/hola/Ana')->assertOk()->assertSeeText('¡Hola, Ana!');
    }

    public function test_pantalla_principal(): void
    {
        $this->get('/')->assertOk()->assertSeeText('Pantalla principal');
    }

    public function test_listado_y_alta_de_proyectos(): void
    {
        $this->get('/proyectos')->assertOk()->assertSeeText('Listado proyectos');
        $this->get('/proyectos/create')->assertOk()->assertSeeText('Añadir proyecto');
    }

    public function test_detalle_y_edicion_de_un_proyecto(): void
    {
        $this->get('/proyectos/show/' . self::ID)->assertOk()->assertSeeText('Vista detalle proyecto ' . self::ID);
        $this->get('/proyectos/edit/' . self::ID)->assertOk()->assertSeeText('Modificar proyecto ' . self::ID);
    }

    public function test_un_id_no_numerico_da_404(): void
    {
        $this->get('/proyectos/show/abc')->assertNotFound();
        $this->get('/proyectos/edit/abc')->assertNotFound();
    }

    public function test_perfil_con_y_sin_id(): void
    {
        $this->get('/perfil/3')->assertOk()->assertSeeText('Visualizar el currículo de 3');
        $this->get('/perfil')->assertOk()->assertSeeText('Visualizar el currículo propio');
        $this->get('/perfil/abc')->assertNotFound();
    }

    public function test_rutas_con_nombre(): void
    {
        // route() falla con "Route [...] not defined" si falta algún nombre.
        $this->assertSame(url('/proyectos'), route('proyectos.index'));
        $this->assertSame(url('/proyectos/show/' . self::ID), route('proyectos.show', self::ID));
        $this->assertSame(url('/proyectos/create'), route('proyectos.create'));
        $this->assertSame(url('/proyectos/edit/' . self::ID), route('proyectos.edit', self::ID));
        $this->assertSame(url('/perfil'), route('perfil'));
        $this->assertSame(url('/perfil/3'), route('perfil', 3));
    }

    public function test_centro_en_json(): void
    {
        $this->getJson('/centro')->assertOk()->assertJsonPath('centro', 'CIFP Carlos III');
    }
}
