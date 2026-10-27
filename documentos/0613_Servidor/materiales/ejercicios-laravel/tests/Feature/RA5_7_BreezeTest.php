<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RA5_7_BreezeTest extends TestCase
{
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
        $this->withoutVite();
    }

    /** La alumna de 4.2. No se guarda en la base de datos: basta con identificarse con ella. */
    private function ana(): User
    {
        return new User(['name' => 'Ana López', 'email' => 'ana.lopez@marcapersonalfp.test']);
    }

    public function test_el_middleware_propio_anade_la_cabecera(): void
    {
        $this->get('/')->assertHeader('X-Centro', 'CIFP Carlos III');
    }

    public function test_breeze_esta_instalado(): void
    {
        $this->assertTrue(class_exists('App\\Http\\Controllers\\Auth\\AuthenticatedSessionController'), 'Instala Breeze.');
        $this->assertTrue(Route::has('register'), 'Faltan las rutas de Breeze: require __DIR__.\'/auth.php\'.');
        $this->get('/login')->assertOk();
    }

    public function test_un_invitado_no_puede_dar_de_alta_ni_editar(): void
    {
        $this->get('/proyectos/create')->assertRedirect(route('login'));
        $this->post('/proyectos', self::VALIDO)->assertRedirect(route('login'));
        $this->get('/proyectos/edit/3')->assertRedirect(route('login'));
    }

    public function test_el_listado_y_el_detalle_siguen_siendo_publicos(): void
    {
        $this->get('/proyectos')->assertOk();
        $this->get('/proyectos/show/1')->assertOk();
    }

    public function test_un_usuario_identificado_puede_dar_de_alta(): void
    {
        $this->actingAs($this->ana())
            ->get('/proyectos/create')
            ->assertOk()
            ->assertSeeText('Añadir proyecto');

        $this->actingAs($this->ana())
            ->post('/proyectos', self::VALIDO)
            ->assertRedirect(route('proyectos.index'));
    }

    public function test_la_barra_de_navegacion_segun_la_sesion(): void
    {
        $this->get('/')->assertSee(route('login'), false);

        $this->actingAs($this->ana())
            ->get('/')
            ->assertSeeText('Ana López')
            ->assertSee(route('logout'), false);
    }
}
