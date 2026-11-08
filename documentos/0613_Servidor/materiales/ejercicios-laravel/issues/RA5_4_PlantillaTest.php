<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Issue "El layout con una plantilla" del ePortfolio.
 *
 * Sirve para cualquier plantilla: busca en resources/views/components/ la subcarpeta
 * que tiene un layout.blade.php y toma su nombre como nombre de la plantilla.
 *
 * Ejecutar con: php artisan test --filter=RA5_4_PlantillaTest
 */
class RA5_4_PlantillaTest extends TestCase
{
    private const PIEZAS = ['layout', 'header', 'main', 'footer'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Nombre de la subcarpeta de components/ con la plantilla (por ejemplo, "daiva"). */
    private function plantilla(): string
    {
        $candidatas = collect(File::directories(resource_path('views/components')))
            ->filter(fn ($carpeta) => File::exists("{$carpeta}/layout.blade.php"))
            ->map(fn ($carpeta) => basename($carpeta))
            ->values();

        $this->assertCount(
            1,
            $candidatas,
            'Debe haber una (y solo una) subcarpeta de resources/views/components/ con un layout.blade.php: la de la plantilla. Encontradas: '
                . ($candidatas->isEmpty() ? 'ninguna' : $candidatas->implode(', '))
        );

        return $candidatas->first();
    }

    private function componente(string $nombre): string
    {
        return (string) @file_get_contents(resource_path("views/components/{$nombre}.blade.php"));
    }

    /** @return \Symfony\Component\Finder\SplFileInfo[] */
    private function vistasDeLaPlantilla(): array
    {
        return File::allFiles(resource_path('views/components/' . $this->plantilla()));
    }

    public function test_la_plantilla_tiene_sus_cuatro_piezas(): void
    {
        $plantilla = $this->plantilla();

        foreach (self::PIEZAS as $pieza) {
            $this->assertFileExists(
                resource_path("views/components/{$plantilla}/{$pieza}.blade.php"),
                "Falta el componente {$plantilla}.{$pieza}."
            );
        }
    }

    public function test_el_layout_de_la_plantilla_compone_la_pagina(): void
    {
        $plantilla = $this->plantilla();
        $layout = $this->componente("{$plantilla}/layout");

        $this->assertStringContainsString('@vite(', $layout, "components/{$plantilla}/layout.blade.php debe enlazar los estilos con @vite.");
        foreach (['header', 'main', 'footer'] as $pieza) {
            $this->assertStringContainsString("<x-{$plantilla}.{$pieza}", $layout, "components/{$plantilla}/layout.blade.php debe usar <x-{$plantilla}.{$pieza}>.");
        }
        $this->assertMatchesRegularExpression('/\{\{\s*\$slot\s*\}\}/', $layout . $this->componente("{$plantilla}/main"), 'El contenido de cada vista ({{ $slot }}) debe llegar a la plantilla.');
    }

    public function test_x_layout_se_apoya_en_la_plantilla(): void
    {
        $plantilla = $this->plantilla();
        $layout = $this->componente('layout');

        $this->assertStringContainsString("<x-{$plantilla}.layout", $layout, "components/layout.blade.php debe usar <x-{$plantilla}.layout>.");
        $this->assertMatchesRegularExpression('/\{\{\s*\$slot\s*\}\}/', $layout, 'components/layout.blade.php debe pasar su {{ $slot }} a la plantilla.');

        $this->blade('<x-layout>Contenido de prueba del issue</x-layout>')
            ->assertSee('Contenido de prueba del issue');
    }

    public function test_la_cabecera_usa_la_barra_de_navegacion(): void
    {
        if (! File::exists(resource_path('views/components/navbar.blade.php'))) {
            $this->markTestSkipped('El proyecto no tiene components/navbar.blade.php.');
        }

        $plantilla = $this->plantilla();
        $this->assertStringContainsString('<x-navbar', $this->componente("{$plantilla}/header"), "components/{$plantilla}/header.blade.php debe usar <x-navbar />.");
    }

    public function test_hay_componentes_propios_con_atributos_y_slots_con_nombre(): void
    {
        $plantilla = $this->plantilla();
        $vistas = $this->vistasDeLaPlantilla();

        $propios = collect($vistas)
            ->reject(fn ($fichero) => in_array($fichero->getFilenameWithoutExtension(), array_map(fn ($p) => "{$p}.blade", self::PIEZAS)))
            ->filter(fn ($fichero) => str_contains($fichero->getContents(), '@props'));
        $this->assertNotEmpty($propios, "Además de layout, header, main y footer, components/{$plantilla}/ debe tener al menos un componente con @props para una pieza que se repite.");

        $todo = collect($vistas)->map->getContents()->implode("\n");
        $this->assertMatchesRegularExpression('/<x-slot(:[\w-]+|\s+name=)/', $todo, "Algún componente de components/{$plantilla}/ debe usarse con un slot con nombre (<x-slot:nombre>).");
    }

    public function test_tailwind_se_compila_con_vite(): void
    {
        foreach (File::allFiles(resource_path('views')) as $fichero) {
            $this->assertDoesNotMatchRegularExpression(
                '#cdn\.tailwindcss\.com|@tailwindcss/browser|unpkg\.com/tailwindcss|tailwind(\.min)?\.css"#',
                $fichero->getContents(),
                "{$fichero->getRelativePathname()} no debe cargar Tailwind desde un CDN: se compila con Vite."
            );
        }
    }

    public function test_alpine_se_carga_con_npm(): void
    {
        foreach (File::allFiles(resource_path('views')) as $fichero) {
            $this->assertDoesNotMatchRegularExpression(
                '#<script[^>]+src="[^"]*alpinejs#',
                $fichero->getContents(),
                "{$fichero->getRelativePathname()} no debe cargar Alpine desde un CDN: instálalo con npm e impórtalo en resources/js/app.js."
            );
        }

        $usaAlpine = collect($this->vistasDeLaPlantilla())->contains(fn ($f) => preg_match('/\sx-data\b/', $f->getContents()));
        if (! $usaAlpine) {
            $this->markTestSkipped('La plantilla no usa Alpine.');
        }

        $this->assertStringContainsString('"alpinejs"', (string) @file_get_contents(base_path('package.json')), 'La plantilla usa Alpine (x-data): instálalo con npm install alpinejs.');
        $this->assertStringContainsString('alpinejs', (string) @file_get_contents(resource_path('js/app.js')), 'Importa Alpine en resources/js/app.js.');
    }

    public function test_los_ficheros_de_la_plantilla_se_enlazan_con_asset(): void
    {
        foreach ($this->vistasDeLaPlantilla() as $fichero) {
            preg_match_all('/\s(?:src|href)="(?!https?:|\/\/|\{\{|#|\/"|mailto:|tel:|data:|javascript:)([^"]+)"/', $fichero->getContents(), $relativas);

            $this->assertEmpty(
                $relativas[1],
                "{$fichero->getRelativePathname()} enlaza ficheros con rutas relativas (" . implode(', ', array_slice($relativas[1], 0, 3)) . '): usa asset() o route().'
            );
        }
    }

    public function test_se_conserva_la_licencia(): void
    {
        $plantilla = $this->plantilla();
        $licencias = glob(public_path("{$plantilla}/LICENSE*")) ?: [];

        $this->assertNotEmpty($licencias, "Copia el fichero LICENSE de la plantilla a public/{$plantilla}/.");
    }

    public function test_la_portada_responde(): void
    {
        $this->get('/')->assertOk();
    }
}
