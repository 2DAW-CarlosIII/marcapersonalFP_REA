<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Issue "La configuración del ePortfolio" (5.2).
 *
 * Ejecutar con: php artisan test --filter=RA5_2_ConfiguracionTest
 */
class RA5_2_ConfiguracionTest extends TestCase
{
    private const CENTRO  = 'CIFP Carlos III';
    private const COMANDO = 'eportfolio:centro';

    private function ejemplo(): string
    {
        return (string) @file_get_contents(base_path('.env.example'));
    }

    public function test_env_example_tiene_la_configuracion_regional(): void
    {
        $ejemplo = $this->ejemplo();

        $this->assertMatchesRegularExpression('/^APP_LOCALE=es\s*$/m', $ejemplo, 'En .env.example, APP_LOCALE debe ser es.');
        $this->assertMatchesRegularExpression('/^APP_FAKER_LOCALE=es_ES\s*$/m', $ejemplo, 'En .env.example, APP_FAKER_LOCALE debe ser es_ES.');
    }

    // Grupo 'entorno': APP_LOCALE y APP_FAKER_LOCALE del .env dependen de la máquina de cada integrante.
    #[Group('entorno')]
    public function test_configuracion_regional_del_env(): void
    {
        $this->assertSame('es', config('app.locale'), 'En tu .env, APP_LOCALE debe ser es.');
        $this->assertSame('es_ES', config('app.faker_locale'), 'En tu .env, APP_FAKER_LOCALE debe ser es_ES.');
    }

    public function test_fichero_de_configuracion_propio(): void
    {
        $this->assertFileExists(config_path('eportfolio.php'), 'Falta config/eportfolio.php.');
        $this->assertSame(self::CENTRO, config('eportfolio.centro'), 'config(\'eportfolio.centro\') debe valer ' . self::CENTRO . '.');
    }

    public function test_el_centro_sale_del_env(): void
    {
        $config = (string) @file_get_contents(config_path('eportfolio.php'));

        $this->assertMatchesRegularExpression('/[\'"]centro[\'"]\s*=>\s*env\(\s*[\'"]NOMBRE_CENTRO[\'"]/', $config, 'La opción centro debe leer NOMBRE_CENTRO con env().');
        $this->assertMatchesRegularExpression('/^NOMBRE_CENTRO=/m', $this->ejemplo(), 'Añade NOMBRE_CENTRO también a .env.example.');
    }

    public function test_comando_propio(): void
    {
        $comandos = Artisan::all();
        $this->assertArrayHasKey(self::COMANDO, $comandos, 'Falta el comando eportfolio:centro en routes/console.php.');
        $this->assertNotSame('', $comandos[self::COMANDO]->getDescription(), 'Falta la descripción del comando: purpose().');

        $this->assertSame(0, Artisan::call(self::COMANDO));
        $this->assertStringContainsString(self::CENTRO, Artisan::output());

        // El nombre debe salir de la configuración, no estar escrito en el comando.
        config(['eportfolio.centro' => 'Centro de prueba']);
        Artisan::call(self::COMANDO);
        $this->assertStringContainsString('Centro de prueba', Artisan::output(), 'El comando debe leer el nombre con config(\'eportfolio.centro\').');
    }

    public function test_gitignore_excluye_vscode(): void
    {
        $this->assertMatchesRegularExpression(
            '#^/?\.vscode/?\s*$#m',
            (string) @file_get_contents(base_path('.gitignore')),
            'El .gitignore debe excluir /.vscode: es configuración del editor de cada uno.'
        );
    }
}
