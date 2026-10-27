<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RA5_6_FormulariosTest extends TestCase
{
    private const CONTROLADOR = 'App\\Http\\Controllers\\ProyectosController';

    // Un proyecto válido. Durante los tests, Laravel no exige el token CSRF.
    private const VALIDO = [
        'docente_id' => 2,
        'nombre' => 'Robótica',
        'dominio' => 'Robotica',
        'fecha_inicio' => '2024-09-15',
        'fecha_fin' => '2025-06-20',
        'calificacion' => 8.5,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Desde 5.7, el alta y la edición exigen sesión: estos tests entran como Ana López (4.2).
        $this->actingAs(new User(['name' => 'Ana López', 'email' => 'ana.lopez@marcapersonalfp.test']));
        $this->withoutVite();
    }

    public function test_rutas_de_alta_y_edicion(): void
    {
        $update = Route::getRoutes()->getByName('proyectos.update');

        $this->assertSame(self::CONTROLADOR . '@store', (string) Route::getRoutes()->getByName('proyectos.store')?->getActionName());
        $this->assertSame(self::CONTROLADOR . '@update', (string) $update?->getActionName());
        $this->assertContains('PUT', $update?->methods() ?? [], 'La ruta proyectos.update debe responder a PUT.');
    }

    public function test_formulario_de_alta(): void
    {
        $this->get('/proyectos/create')
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('name="docente_id"', false)
            ->assertSee('name="nombre"', false)
            ->assertSee('name="dominio"', false)
            ->assertSee('name="fecha_inicio"', false);
    }

    public function test_formulario_de_edicion_con_los_datos_del_proyecto(): void
    {
        $this->get('/proyectos/edit/3')
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Ingeniería Civil"', false)
            ->assertSee('value="IngenieriaCivil"', false);
    }

    public function test_alta_valida_redirige_al_listado_con_su_mensaje(): void
    {
        $this->post('/proyectos', self::VALIDO)
            ->assertRedirect(route('proyectos.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('mensaje');

        // Siguiendo la redirección, el mensaje aparece en el listado.
        $this->followingRedirects()
            ->post('/proyectos', self::VALIDO)
            ->assertOk()
            ->assertSeeText('Proyecto «Robótica» creado.');
    }

    public function test_alta_no_valida_vuelve_con_errores_y_lo_escrito(): void
    {
        $this->from('/proyectos/create')
            ->post('/proyectos', [
                'docente_id' => 'abc',
                'nombre' => '',
                'dominio' => 'con espacios',
                'fecha_inicio' => '2024-05-01',
                'fecha_fin' => '2024-01-01',
                'calificacion' => 11,
            ])
            ->assertRedirect('/proyectos/create')
            ->assertSessionHasErrors(['docente_id', 'nombre', 'dominio', 'fecha_fin', 'calificacion'])
            ->assertSessionHasInput('dominio', 'con espacios');
    }

    public function test_mensajes_en_espanol(): void
    {
        $this->assertTrue(class_exists('App\\Http\\Requests\\ProyectoRequest'), 'Falta el Form Request ProyectoRequest.');

        $this->from('/proyectos/create')->post('/proyectos', []);
        $this->assertSame('El nombre del proyecto es obligatorio.', session('errors')?->first('nombre'));
    }

    public function test_edicion_valida_redirige_al_detalle(): void
    {
        $this->put('/proyectos/edit/3', self::VALIDO)
            ->assertRedirect(route('proyectos.show', 3))
            ->assertSessionHas('mensaje', 'Proyecto «Robótica» actualizado.');

        $this->put('/proyectos/edit/99', self::VALIDO)->assertNotFound();
    }
}
