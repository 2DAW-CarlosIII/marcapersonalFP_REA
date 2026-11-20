<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Issues "Los controladores del ePortfolio" (5.5): el principal y sus cuatro sub-issues.
 *
 * Cada issue ejecuta solo sus tests, con su grupo:
 *   php artisan test --filter=RA5_5_RecursosTest --group=principal
 *   php artisan test --filter=RA5_5_RecursosTest --group=evidencias
 *   php artisan test --filter=RA5_5_RecursosTest --group=familias-profesionales
 *   php artisan test --filter=RA5_5_RecursosTest --group=ciclos-formativos
 *   php artisan test --filter=RA5_5_RecursosTest --group=modulos-formativos
 *
 * Con todo fusionado en main, el test completo: php artisan test --filter=RA5_5_RecursosTest
 */
class RA5_5_RecursosTest extends TestCase
{
    private const CONTROLADORES = 'App\\Http\\Controllers\\';

    protected function setUp(): void
    {
        parent::setUp();
        // Las vistas usan @vite: el test no necesita los estilos compilados.
        $this->withoutVite();
    }

    /** Acción (Clase@método) de la ruta con ese nombre, o cadena vacía si no existe. */
    private function accion(string $nombre): string
    {
        return (string) Route::getRoutes()->getByName($nombre)?->getActionName();
    }

    /** Las cuatro rutas del recurso apuntan a las acciones de su controlador. */
    private function comprobarControlador(string $recurso, string $controlador): void
    {
        foreach (['index', 'show', 'create', 'edit'] as $accion) {
            $this->assertSame(
                self::CONTROLADORES . "{$controlador}@{$accion}",
                $this->accion("{$recurso}.{$accion}"),
                "La ruta {$recurso}.{$accion} debe apuntar a {$controlador}@{$accion}."
            );
        }
    }

    /** Las rutas del recurso están en routes/recurso.php, incluido desde routes/web.php. */
    private function comprobarFicheroDeRutas(string $recurso): void
    {
        $this->assertFileExists(base_path("routes/{$recurso}.php"), "Las rutas deben estar en routes/{$recurso}.php.");
        $this->assertStringContainsString(
            "{$recurso}.php",
            (string) file_get_contents(base_path('routes/web.php')),
            "Incluye routes/{$recurso}.php desde routes/web.php con require."
        );
    }

    /** El alta y la edición del primer elemento responden, con su título. */
    private function comprobarAltaYEdicion(string $recurso, string $tituloAlta, string $tituloEdicion): void
    {
        $this->get("/{$recurso}/create")->assertOk()->assertSeeText($tituloAlta);
        $this->get("/{$recurso}/edit/0")->assertOk()->assertSeeText("{$tituloEdicion} 0");
    }

    /** Un id que no existe en los datos, o que no es un número, da 404. */
    private function comprobarNoEncontrado(string $recurso, int $inexistente): void
    {
        $this->get("/{$recurso}/show/{$inexistente}")->assertNotFound();
        $this->get("/{$recurso}/edit/{$inexistente}")->assertNotFound();
        $this->get("/{$recurso}/show/abc")->assertNotFound();
    }

    // Issue principal: los controladores invocables.

    #[Group('principal')]
    public function test_portada_con_controlador_invocable(): void
    {
        $accion = Route::getRoutes()->match(Request::create('/'))->getActionName();

        $this->assertStringStartsWith(self::CONTROLADORES . 'HomeController', $accion, 'La ruta / debe apuntar al controlador invocable HomeController.');
        $this->get('/')->assertOk();
    }

    #[Group('principal')]
    public function test_perfil_con_controlador_invocable(): void
    {
        $this->assertStringStartsWith(self::CONTROLADORES . 'PerfilController', $this->accion('perfil'), 'La ruta perfil debe apuntar al controlador invocable PerfilController.');
        $this->get('/perfil/3')->assertOk()->assertSeeText('Perfil del usuario 3');
        $this->get('/perfil')->assertOk()->assertSeeText('Mi perfil');
    }

    // Sub-issue 1: evidencias.

    #[Group('evidencias')]
    public function test_evidencias_usan_su_controlador(): void
    {
        $this->comprobarControlador('evidencias', 'EvidenciaController');
    }

    #[Group('evidencias')]
    public function test_evidencias_en_su_fichero_de_rutas(): void
    {
        $this->comprobarFicheroDeRutas('evidencias');
    }

    #[Group('evidencias')]
    public function test_evidencias_listado(): void
    {
        $this->get('/evidencias')
            ->assertOk()
            ->assertSeeText('Listado de evidencias')
            ->assertSeeText('Instalación de Laravel y desarrollo colaborativo con GitHub')
            ->assertSeeText('Layout con la plantilla del grupo');
    }

    #[Group('evidencias')]
    public function test_evidencias_detalle(): void
    {
        $this->get('/evidencias/show/0')
            ->assertOk()
            ->assertSeeText('Evidencia 0')
            ->assertSee('https://example.com/evidencias/1')
            ->assertSeeText('Instalación de Laravel y desarrollo colaborativo con GitHub')
            ->assertSeeText('validada');

        $this->get('/evidencias/show/4')->assertOk()->assertSeeText('Fichero de configuración propio')->assertSeeText('rechazada');
    }

    #[Group('evidencias')]
    public function test_evidencias_alta_y_edicion(): void
    {
        $this->comprobarAltaYEdicion('evidencias', 'Nueva evidencia', 'Modificar evidencia');
    }

    #[Group('evidencias')]
    public function test_evidencia_inexistente_da_404(): void
    {
        $this->comprobarNoEncontrado('evidencias', 8);
    }

    // Sub-issue 2: familias profesionales (FamiliaProfesionalSeeder del ePortfolio).

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_usan_su_controlador(): void
    {
        $this->comprobarControlador('familias-profesionales', 'FamiliaProfesionalController');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_en_su_fichero_de_rutas(): void
    {
        $this->comprobarFicheroDeRutas('familias-profesionales');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_listado(): void
    {
        $this->get('/familias-profesionales')
            ->assertOk()
            ->assertSeeText('Familias profesionales')
            ->assertSeeText('ADMINISTRACIÓN Y GESTIÓN')
            ->assertSeeText('INFORMÁTICA Y COMUNICACIONES');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_detalle(): void
    {
        $this->get('/familias-profesionales/show/0')->assertOk()->assertSeeText('ADG')->assertSeeText('ADMINISTRACIÓN Y GESTIÓN');
        $this->get('/familias-profesionales/show/25')->assertOk()->assertSeeText('VIC')->assertSeeText('VIDRIO Y CERÁMICA');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_alta_y_edicion(): void
    {
        $this->comprobarAltaYEdicion('familias-profesionales', 'Nueva familia profesional', 'Modificar familia profesional');
    }

    #[Group('familias-profesionales')]
    public function test_familia_profesional_inexistente_da_404(): void
    {
        // Las 26 familias tienen los id del 0 al 25.
        $this->comprobarNoEncontrado('familias-profesionales', 26);
    }

    // Sub-issue 3: ciclos formativos (CicloFormativoSeeder del ePortfolio).

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_usan_su_controlador(): void
    {
        $this->comprobarControlador('ciclos-formativos', 'CicloFormativoController');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_en_su_fichero_de_rutas(): void
    {
        $this->comprobarFicheroDeRutas('ciclos-formativos');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_listado(): void
    {
        $this->get('/ciclos-formativos')
            ->assertOk()
            ->assertSeeText('Ciclos formativos')
            ->assertSeeText('Técnico en Actividades Ecuestres')
            ->assertSeeText('Técnico Superior en Desarrollo de Aplicaciones Web');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_detalle_con_el_codigo_de_su_familia(): void
    {
        $this->get('/ciclos-formativos/show/0')
            ->assertOk()
            ->assertSeeText('ACEC2')
            ->assertSeeText('Técnico en Actividades Ecuestres')
            ->assertSeeText('AFD');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_alta_y_edicion(): void
    {
        $this->comprobarAltaYEdicion('ciclos-formativos', 'Nuevo ciclo formativo', 'Modificar ciclo formativo');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclo_formativo_inexistente_da_404(): void
    {
        $this->comprobarNoEncontrado('ciclos-formativos', 9999);
    }

    // Sub-issue 4: módulos formativos (ModuloFormativoSeeder del ePortfolio).

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_usan_su_controlador(): void
    {
        $this->comprobarControlador('modulos-formativos', 'ModuloFormativoController');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_en_su_fichero_de_rutas(): void
    {
        $this->comprobarFicheroDeRutas('modulos-formativos');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_listado(): void
    {
        $this->get('/modulos-formativos')
            ->assertOk()
            ->assertSeeText('Módulos formativos')
            ->assertSeeText('Sistemas Informáticos')
            ->assertSeeText('Desarrollo Web en Entorno Servidor');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_detalle_con_el_codigo_de_su_ciclo(): void
    {
        $this->get('/modulos-formativos/show/0')
            ->assertOk()
            ->assertSeeText('0483')
            ->assertSeeText('Sistemas Informáticos')
            ->assertSeeText('DAPW3');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_alta_y_edicion(): void
    {
        $this->comprobarAltaYEdicion('modulos-formativos', 'Nuevo módulo formativo', 'Modificar módulo formativo');
    }

    #[Group('modulos-formativos')]
    public function test_modulo_formativo_inexistente_da_404(): void
    {
        $this->comprobarNoEncontrado('modulos-formativos', 9999);
    }
}
