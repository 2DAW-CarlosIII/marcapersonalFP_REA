<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Issues "Los usuarios del ePortfolio" (5.7): el principal y sus cuatro sub-issues.
 *
 * Cada issue ejecuta solo sus tests, con su grupo:
 *   php artisan test --filter=RA5_7_UsuariosTest --group=principal
 *   php artisan test --filter=RA5_7_UsuariosTest --group=evidencias
 *   php artisan test --filter=RA5_7_UsuariosTest --group=familias-profesionales
 *   php artisan test --filter=RA5_7_UsuariosTest --group=ciclos-formativos
 *   php artisan test --filter=RA5_7_UsuariosTest --group=modulos-formativos
 *
 * Con todo fusionado en main, el test completo: php artisan test --filter=RA5_7_UsuariosTest
 */
class RA5_7_UsuariosTest extends TestCase
{
    private const CONTROLADORES = 'App\\Http\\Controllers\\';

    private const RECURSOS = ['evidencias', 'familias-profesionales', 'ciclos-formativos', 'modulos-formativos'];

    protected function setUp(): void
    {
        parent::setUp();
        // Las vistas usan @vite: el test no necesita los estilos compilados.
        $this->withoutVite();
    }

    /** Un usuario de prueba. No se guarda en la base de datos: basta con identificarse con él. */
    private function usuario(): User
    {
        return new User(['name' => 'Ana López', 'email' => 'ana.lopez@eportfolio.test']);
    }

    private function fichero(string $ruta): string
    {
        return (string) @file_get_contents(base_path($ruta));
    }

    /** La respuesta es una redirección al login. */
    private function assertRedirigeAlLogin($respuesta, string $peticion): void
    {
        $this->assertTrue(
            $respuesta->isRedirect(route('login')),
            "Sin sesión, {$peticion} debe redirigir al login (recibido: {$respuesta->getStatusCode()}). Protege la ruta con el middleware auth."
        );
    }

    /** Sin sesión, el alta y la edición del recurso, y sus envíos, redirigen al login. */
    private function comprobarProtegidas(string $recurso): void
    {
        $this->assertRedirigeAlLogin($this->get("/{$recurso}/create"), "GET /{$recurso}/create");
        $this->assertRedirigeAlLogin($this->post("/{$recurso}", []), "POST /{$recurso}");
        $this->assertRedirigeAlLogin($this->get("/{$recurso}/edit/0"), "GET /{$recurso}/edit/0");
        $this->assertRedirigeAlLogin($this->put("/{$recurso}/edit/0", []), "PUT /{$recurso}/edit/0");

        foreach (['create', 'store', 'edit', 'update'] as $accion) {
            $middleware = Route::getRoutes()->getByName("{$recurso}.{$accion}")?->gatherMiddleware() ?? [];
            $conAuth = collect($middleware)->contains(
                fn ($m) => is_string($m) && ($m === 'auth' || str_starts_with($m, 'auth:') || $m === Authenticate::class)
            );

            $this->assertTrue($conAuth, "La ruta {$recurso}.{$accion} debe tener el middleware auth.");
        }
    }

    /** Sin sesión, el listado y el detalle del recurso siguen respondiendo. */
    private function comprobarPublicas(string $recurso): void
    {
        foreach (["/{$recurso}", "/{$recurso}/show/0"] as $direccion) {
            $this->assertSame(200, $this->get($direccion)->getStatusCode(), "Sin sesión, {$direccion} debe seguir siendo pública: no le apliques auth.");
        }
    }

    /** Con sesión, el alta y la edición del recurso responden. */
    private function comprobarConSesion(string $recurso): void
    {
        $this->actingAs($this->usuario());

        $this->get("/{$recurso}/create")->assertOk();
        $this->get("/{$recurso}/edit/0")->assertOk();
    }

    /** Los enlaces al alta (en el listado) y a la edición (en el detalle) solo se muestran con sesión. */
    private function comprobarEnlaces(string $recurso): void
    {
        $alta = route("{$recurso}.create");
        $edicion = route("{$recurso}.edit", 0);

        $this->assertStringNotContainsString($alta, (string) $this->get("/{$recurso}")->getContent(), "Sin sesión, el listado no debe enlazar con el alta: usa @auth.");
        $this->assertStringNotContainsString($edicion, (string) $this->get("/{$recurso}/show/0")->getContent(), "Sin sesión, el detalle no debe enlazar con la edición: usa @auth.");

        $this->actingAs($this->usuario());
        $this->assertStringContainsString($alta, (string) $this->get("/{$recurso}")->getContent(), "Con sesión, el listado debe enlazar con el alta.");
        $this->assertStringContainsString($edicion, (string) $this->get("/{$recurso}/show/0")->getContent(), "Con sesión, el detalle debe enlazar con la edición.");
    }

    // Issue principal: Breeze, el middleware propio y la barra de navegación.

    #[Group('principal')]
    public function test_breeze_esta_instalado(): void
    {
        $this->assertTrue(class_exists(self::CONTROLADORES . 'Auth\\AuthenticatedSessionController'), 'Instala Breeze: composer require laravel/breeze --dev y php artisan breeze:install.');

        foreach (['login', 'register', 'logout', 'dashboard', 'profile.edit'] as $nombre) {
            $this->assertTrue(Route::has($nombre), "Falta la ruta {$nombre} de Breeze: conserva sus rutas en routes/web.php y el require de routes/auth.php.");
        }

        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->assertRedirigeAlLogin($this->get('/dashboard'), 'GET /dashboard');
        $this->actingAs($this->usuario())->get('/dashboard')->assertOk();
    }

    #[Group('principal')]
    public function test_se_recuperan_las_rutas_del_eportfolio(): void
    {
        $portada = Route::getRoutes()->match(Request::create('/'))->getActionName();
        $this->assertStringStartsWith(self::CONTROLADORES . 'HomeController', $portada, 'La ruta / debe seguir apuntando a HomeController, no a la página de bienvenida de Breeze.');

        foreach (['perfil', 'acerca'] as $nombre) {
            $this->assertTrue(Route::has($nombre), "Breeze ha borrado la ruta {$nombre}: recupérala de git diff routes/web.php.");
        }

        foreach (self::RECURSOS as $recurso) {
            $this->assertTrue(Route::has("{$recurso}.index"), "Faltan las rutas de {$recurso}: recupera en routes/web.php el require de routes/{$recurso}.php.");
        }

        $this->get('/')->assertOk();
        $this->get('/acerca')->assertOk();
    }

    #[Group('principal')]
    public function test_se_mantiene_tailwind_4(): void
    {
        $css = $this->fichero('resources/css/app.css');

        $this->assertFileDoesNotExist(base_path('tailwind.config.js'), 'Borra tailwind.config.js: el ePortfolio sigue con Tailwind 4, que no lo usa.');
        $this->assertMatchesRegularExpression('/@import\s+["\']tailwindcss["\']/', $css, 'Recupera resources/css/app.css con git restore: debe seguir empezando por @import \'tailwindcss\'.');
        $this->assertStringNotContainsString('@tailwind base', $css, 'resources/css/app.css tiene las líneas @tailwind de Tailwind 3: recupéralo con git restore.');
        $this->assertMatchesRegularExpression('/@plugin\s+["\']@tailwindcss\/forms["\']/', $css, 'Las vistas de Breeze necesitan @plugin "@tailwindcss/forms"; en resources/css/app.css.');

        $this->assertStringContainsString('@tailwindcss/vite', $this->fichero('vite.config.js'), 'Recupera vite.config.js con git restore: debe usar el plugin @tailwindcss/vite.');

        $paquetes = $this->fichero('package.json');
        $this->assertMatchesRegularExpression('/"tailwindcss"\s*:\s*"[\^~]?4/', $paquetes, 'package.json debe seguir con tailwindcss 4: recupéralo con git restore.');
        $this->assertStringContainsString('"@tailwindcss/forms"', $paquetes, 'Instala el plugin de formularios: npm install -D @tailwindcss/forms.');
    }

    #[Group('principal')]
    public function test_el_middleware_propio_anade_el_centro(): void
    {
        $this->assertTrue(class_exists('App\\Http\\Middleware\\CabeceraCentro'), 'Falta el middleware CabeceraCentro: php artisan make:middleware CabeceraCentro.');

        $this->get('/')->assertHeader('X-Centro', (string) config('eportfolio.centro'));
        $this->get('/evidencias')->assertHeader('X-Centro', (string) config('eportfolio.centro'));

        // El valor debe salir de la configuración, no estar escrito en el middleware.
        config(['eportfolio.centro' => 'Centro de prueba']);
        $this->assertSame(
            'Centro de prueba',
            $this->get('/')->headers->get('X-Centro'),
            "La cabecera X-Centro debe leerse con config('eportfolio.centro')."
        );
    }

    #[Group('principal')]
    public function test_la_barra_de_navegacion_segun_la_sesion(): void
    {
        $invitado = (string) $this->get('/evidencias')->getContent();
        $this->assertStringContainsString(route('login'), $invitado, 'Sin sesión, la barra de navegación debe enlazar con el login.');
        $this->assertStringContainsString(route('register'), $invitado, 'Sin sesión, la barra de navegación debe enlazar con el registro.');
        $this->assertStringNotContainsString(route('logout'), $invitado, 'Sin sesión, la barra de navegación no debe mostrar el botón de salir: usa @auth.');

        $this->actingAs($this->usuario());
        $identificado = (string) $this->get('/evidencias')->getContent();
        $this->assertStringContainsString('Ana López', $identificado, 'Con sesión, la barra de navegación debe mostrar el nombre del usuario.');
        $this->assertStringContainsString(route('logout'), $identificado, 'Con sesión, la barra de navegación debe tener el formulario de salir, enviado a route(\'logout\').');
        $this->assertStringNotContainsString(route('login'), $identificado, 'Con sesión, la barra de navegación no debe enlazar con el login: usa @guest.');
    }

    // Sub-issue 1: evidencias.

    #[Group('evidencias')]
    public function test_evidencias_un_invitado_no_puede_dar_de_alta_ni_editar(): void
    {
        $this->comprobarProtegidas('evidencias');
    }

    #[Group('evidencias')]
    public function test_evidencias_el_listado_y_el_detalle_siguen_siendo_publicos(): void
    {
        $this->comprobarPublicas('evidencias');
    }

    #[Group('evidencias')]
    public function test_evidencias_un_usuario_identificado_puede_dar_de_alta_y_editar(): void
    {
        $this->comprobarConSesion('evidencias');
    }

    #[Group('evidencias')]
    public function test_evidencias_los_enlaces_de_alta_y_edicion_solo_con_sesion(): void
    {
        $this->comprobarEnlaces('evidencias');
    }

    // Sub-issue 2: familias profesionales.

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_un_invitado_no_puede_dar_de_alta_ni_editar(): void
    {
        $this->comprobarProtegidas('familias-profesionales');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_el_listado_y_el_detalle_siguen_siendo_publicos(): void
    {
        $this->comprobarPublicas('familias-profesionales');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_un_usuario_identificado_puede_dar_de_alta_y_editar(): void
    {
        $this->comprobarConSesion('familias-profesionales');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_los_enlaces_de_alta_y_edicion_solo_con_sesion(): void
    {
        $this->comprobarEnlaces('familias-profesionales');
    }

    // Sub-issue 3: ciclos formativos.

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_un_invitado_no_puede_dar_de_alta_ni_editar(): void
    {
        $this->comprobarProtegidas('ciclos-formativos');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_el_listado_y_el_detalle_siguen_siendo_publicos(): void
    {
        $this->comprobarPublicas('ciclos-formativos');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_un_usuario_identificado_puede_dar_de_alta_y_editar(): void
    {
        $this->comprobarConSesion('ciclos-formativos');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_los_enlaces_de_alta_y_edicion_solo_con_sesion(): void
    {
        $this->comprobarEnlaces('ciclos-formativos');
    }

    // Sub-issue 4: módulos formativos.

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_un_invitado_no_puede_dar_de_alta_ni_editar(): void
    {
        $this->comprobarProtegidas('modulos-formativos');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_el_listado_y_el_detalle_siguen_siendo_publicos(): void
    {
        $this->comprobarPublicas('modulos-formativos');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_un_usuario_identificado_puede_dar_de_alta_y_editar(): void
    {
        $this->comprobarConSesion('modulos-formativos');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_los_enlaces_de_alta_y_edicion_solo_con_sesion(): void
    {
        $this->comprobarEnlaces('modulos-formativos');
    }
}
