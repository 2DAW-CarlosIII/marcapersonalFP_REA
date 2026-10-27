<?php

namespace Tests\Feature;

use App\Dominio\Ciclo;
use App\Dominio\Curriculo;
use App\Models\User;
use App\Repositorios\ArrayCurriculoRepository;
use App\Repositorios\CurriculoRepository;
use App\Repositorios\JsonCurriculoRepository;
use Tests\TestCase;

class RA5_8_ProyectoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Sustituye el repositorio de la aplicación por uno en memoria, vacío: el test no toca curriculos.json. */
    private function repositorioEnMemoria(): ArrayCurriculoRepository
    {
        $repositorio = new ArrayCurriculoRepository();
        $this->app->instance(CurriculoRepository::class, $repositorio);

        return $repositorio;
    }

    /** Ana López (4.2), con un id, pero sin guardarla en la base de datos. */
    private function ana(): User
    {
        return (new User())->forceFill(['id' => 7, 'name' => 'Ana López', 'email' => 'ana.lopez@marcapersonalfp.test']);
    }

    public function test_clases_del_dominio_y_del_repositorio(): void
    {
        $this->assertTrue(class_exists(Ciclo::class), 'Falta app/Dominio/Ciclo.php.');
        $this->assertTrue(class_exists(Curriculo::class), 'Falta app/Dominio/Curriculo.php.');
        $this->assertTrue(interface_exists(CurriculoRepository::class), 'Falta la interfaz app/Repositorios/CurriculoRepository.php.');
        $this->assertContains(CurriculoRepository::class, class_implements(ArrayCurriculoRepository::class));
        $this->assertContains(CurriculoRepository::class, class_implements(JsonCurriculoRepository::class));
    }

    public function test_el_contenedor_entrega_siempre_el_mismo_repositorio_json(): void
    {
        $repositorio = $this->app->make(CurriculoRepository::class);

        $this->assertInstanceOf(JsonCurriculoRepository::class, $repositorio);
        $this->assertSame($repositorio, $this->app->make(CurriculoRepository::class), 'Regístralo con singleton().');
    }

    public function test_listado_publico_de_curriculos(): void
    {
        $catalogo = Ciclo::catalogo();
        $repositorio = $this->repositorioEnMemoria();
        $repositorio->guardar(new Curriculo(1, 'Ana López', $catalogo['DAW'], 'https://youtu.be/curriculo1'));
        $repositorio->guardar(new Curriculo(2, 'Marcos Pérez', $catalogo['DAM']));
        $repositorio->guardar(new Curriculo(3, 'Laura García', $catalogo['ASIR'], 'https://youtu.be/curriculo3'));

        $this->get('/curriculos')
            ->assertOk()
            ->assertViewIs('curriculos.index')
            ->assertSeeText('Marcos Pérez')
            ->assertSeeText('Desarrollo de Aplicaciones Multiplataforma');
    }

    public function test_mi_curriculo_exige_sesion(): void
    {
        $this->get('/mi-curriculo')->assertRedirect(route('login'));
    }

    public function test_alta_de_mi_curriculo_con_el_nombre_de_la_sesion(): void
    {
        $repositorio = $this->repositorioEnMemoria();

        $this->actingAs($this->ana())
            ->put('/mi-curriculo', ['ciclo' => 'DAW', 'video' => 'https://youtu.be/curriculo1', 'alumno' => 'Otra Persona'])
            ->assertRedirect(route('mi-curriculo'))
            ->assertSessionHasNoErrors();

        $curriculo = $repositorio->delUsuario(7);
        $this->assertNotNull($curriculo, 'El currículo no se ha guardado en el repositorio.');
        $this->assertSame('Ana López', $curriculo->alumno, 'El nombre debe salir de la sesión, no del formulario.');
        $this->assertSame('DAW', $curriculo->ciclo->codCiclo);

        // Al volver, el formulario muestra lo guardado.
        $this->actingAs($this->ana())
            ->get('/mi-curriculo')
            ->assertSee('value="https://youtu.be/curriculo1"', false);
    }

    public function test_validacion_con_los_mensajes_de_v0(): void
    {
        $repositorio = $this->repositorioEnMemoria();

        $this->actingAs($this->ana())
            ->from('/mi-curriculo')
            ->put('/mi-curriculo', ['ciclo' => 'FP', 'video' => 'http://youtu.be/curriculo1'])
            ->assertSessionHasErrors(['ciclo', 'video']);

        $this->assertSame('Elige uno de los ciclos de la lista.', session('errors')?->first('ciclo'));
        $this->assertNull($repositorio->delUsuario(7), 'Con datos no válidos no se guarda nada.');
    }
}
