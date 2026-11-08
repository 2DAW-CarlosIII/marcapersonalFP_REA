<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RA5_4_VistasTest extends TestCase
{
    private const ID = 7;

    private const COMPONENTES_PLANTILLA = ['layout', 'header', 'main', 'footer', 'seccion', 'box', 'boton', 'icono'];

    protected function setUp(): void
    {
        parent::setUp();
        // Desde 5.7, el alta y la edición exigen sesión: estos tests entran como Ana López (4.2).
        $this->actingAs(new User(['name' => 'Ana López', 'email' => 'ana.lopez@marcapersonalfp.test']));
        // Las vistas se prueban sin los estilos; su compilación se comprueba en test_estructura_de_las_vistas.
        $this->withoutVite();
    }

    private function componente(string $nombre): string
    {
        return (string) @file_get_contents(resource_path("views/components/{$nombre}.blade.php"));
    }

    // Ejercicios 1 a 4: vistas, layout y componentes

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
        $layout = $this->componente('layout');
        // Desde el ejercicio 5, @vite está en el layout de la plantilla.
        $layouts = $layout . $this->componente('dopetrope/layout');

        $this->assertMatchesRegularExpression('/\{\{\s*\$slot\s*\}\}/', $layout, 'El layout (components/layout.blade.php) debe mostrar {{ $slot }}.');
        $this->assertStringContainsString('@vite(', $layouts, 'El layout debe enlazar los estilos con @vite.');
        $this->assertFileExists(resource_path('views/components/navbar.blade.php'));
        $this->assertFileExists(resource_path('views/components/tarjeta-proyecto.blade.php'));
        $this->assertFileExists(public_path('build/manifest.json'), 'Compila los estilos: npm run build.');
    }

    // Ejercicio 5: el layout con la plantilla Dopetrope (5.4.1)

    public function test_existen_los_componentes_de_la_plantilla(): void
    {
        foreach (self::COMPONENTES_PLANTILLA as $componente) {
            $this->assertFileExists(
                resource_path("views/components/dopetrope/{$componente}.blade.php"),
                "Falta el componente dopetrope.{$componente} (components/dopetrope/{$componente}.blade.php)."
            );
        }
    }

    public function test_el_layout_se_apoya_en_el_de_la_plantilla(): void
    {
        $this->assertStringContainsString('<x-dopetrope.layout', $this->componente('layout'), 'components/layout.blade.php debe usar <x-dopetrope.layout>.');

        $plantilla = $this->componente('dopetrope/layout');
        foreach (['@props', '@vite(', '<x-dopetrope.header', '<x-dopetrope.main', '<x-dopetrope.footer'] as $texto) {
            $this->assertStringContainsString($texto, $plantilla, "components/dopetrope/layout.blade.php debe contener {$texto}.");
        }
        $this->assertMatchesRegularExpression('/\{\{\s*\$slot\s*\}\}/', $plantilla, 'components/dopetrope/layout.blade.php debe pasar su {{ $slot }} a <x-dopetrope.main>.');
    }

    public function test_la_cabecera_usa_la_barra_de_navegacion(): void
    {
        $cabecera = $this->componente('dopetrope/header');

        $this->assertStringContainsString('<x-navbar', $cabecera, 'La cabecera debe incluir <x-navbar />.');
        $this->assertStringNotContainsString('Left Sidebar', $cabecera, 'El menú de la plantilla debe sustituirse por <x-navbar />.');
    }

    public function test_main_usa_los_componentes_seccion_y_box(): void
    {
        $main = $this->componente('dopetrope/main');

        $this->assertMatchesRegularExpression('/\{\{\s*\$slot\s*\}\}/', $main, 'components/dopetrope/main.blade.php debe mostrar {{ $slot }}.');
        $this->assertStringContainsString('<x-dopetrope.seccion', $main);
        $this->assertStringContainsString('<x-dopetrope.box', $main);
        $this->assertMatchesRegularExpression('/<x-slot(:footer| name="footer")/', $main, 'El pie de cada caja debe ir en un slot con nombre: <x-slot:footer>.');
    }

    public function test_la_plantilla_no_usa_jquery_ni_su_css(): void
    {
        foreach (File::files(resource_path('views/components/dopetrope')) as $fichero) {
            $contenido = strtolower($fichero->getContents());

            $this->assertStringNotContainsString('jquery', $contenido, "{$fichero->getFilename()} no debe usar jQuery.");
            $this->assertStringNotContainsString('main.css', $contenido, "{$fichero->getFilename()} no debe enlazar el CSS de la plantilla original.");
        }
    }

    public function test_el_tema_tiene_los_colores_y_la_fuente(): void
    {
        // En 5.4, el tema está en el @theme de app.css (Tailwind 4). Desde 5.7, Breeze lo pasa a tailwind.config.js (Tailwind 3).
        $tema = @file_get_contents(resource_path('css/app.css')) . @file_get_contents(base_path('tailwind.config.js'));

        $this->assertMatchesRegularExpression('/--font-dopetrope|dopetrope\s*:/', $tema, 'Falta la fuente dopetrope en el tema de Tailwind.');
        foreach (['red', 'dark', 'gray', 'light'] as $color) {
            $this->assertMatchesRegularExpression(
                "/--color-dope-{$color}\s*:|dope\s*:\s*\{[^}]*\b{$color}\s*:/s",
                $tema,
                "Falta el color dope-{$color} en el tema de Tailwind."
            );
        }
    }

    public function test_componente_boton(): void
    {
        $this->blade('<x-dopetrope.boton href="/destino">Ir</x-dopetrope.boton>')
            ->assertSee('href="/destino"', false)
            ->assertSee('Ir')
            ->assertSee('bg-dope-red', false)
            ->assertDontSee('bg-dope-dark', false);

        $this->blade('<x-dopetrope.boton alt grande>Ir</x-dopetrope.boton>')
            ->assertSee('href="#"', false)
            ->assertSee('bg-dope-dark', false)
            ->assertDontSee('bg-dope-red', false)
            ->assertSee('text-2xl', false);
    }

    public function test_componente_seccion(): void
    {
        $this->blade('<x-dopetrope.seccion titulo="Mi sección">Dentro de la sección</x-dopetrope.seccion>')
            ->assertSeeInOrder(['<h2', 'Mi sección', '</h2>', 'Dentro de la sección'], false);
    }

    public function test_componente_box(): void
    {
        $this->blade(
            '<x-dopetrope.box imagen="https://picsum.photos/seed/test/800/450" titulo="Mi caja" subtitulo="Hace un momento">'
            . 'Texto de la caja'
            . '<x-slot:footer><li>Pie de la caja</li></x-slot:footer>'
            . '</x-dopetrope.box>'
        )
            ->assertSee('src="https://picsum.photos/seed/test/800/450"', false)
            ->assertSeeInOrder(['Mi caja', 'Hace un momento', 'Texto de la caja', '<footer', 'Pie de la caja'], false);

        // Sin subtítulo ni pie
        $this->blade('<x-dopetrope.box imagen="foto.jpg" titulo="Caja sencilla">Solo texto</x-dopetrope.box>')
            ->assertSee('Caja sencilla')
            ->assertDontSee('<footer', false)
            ->assertDontSee('italic', false);
    }

    public function test_componente_icono(): void
    {
        $this->blade('<x-dopetrope.icono nombre="engranaje" class="size-6" />')
            ->assertSee('<svg', false)
            ->assertSee('class="size-6"', false);
    }

    public function test_las_paginas_usan_la_plantilla(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Howdy. This is Dopetrope.')
            ->assertSeeInOrder(['Pantalla principal', 'Bienvenida', 'My Portfolio', 'The Blog'])
            ->assertSee('picsum.photos', false);

        // El contenido de cada vista va antes del portfolio.
        $this->get('/proyectos')
            ->assertOk()
            ->assertSeeInOrder(['Howdy. This is Dopetrope.', 'Tecnologías de la Información', 'My Portfolio']);
    }
}
