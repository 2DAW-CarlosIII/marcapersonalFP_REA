<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RA5_2_EstructuraTest extends TestCase
{
    private const CENTRO  = 'CIFP Carlos III';
    private const COMANDO = 'marcapersonalfp:centro';

    public function test_configuracion_regional(): void
    {
        $this->assertSame('es', config('app.locale'), 'APP_LOCALE debe ser es.');
        $this->assertSame('es_ES', config('app.faker_locale'), 'APP_FAKER_LOCALE debe ser es_ES.');
    }

    public function test_fichero_de_configuracion_propio(): void
    {
        $this->assertFileExists(config_path('marcapersonalfp.php'), 'Falta config/marcapersonalfp.php.');
        $this->assertSame(self::CENTRO, config('marcapersonalfp.centro'));
    }

    public function test_el_centro_sale_del_env(): void
    {
        $config  = (string) @file_get_contents(config_path('marcapersonalfp.php'));
        $ejemplo = (string) @file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/env\(\s*[\'"]NOMBRE_CENTRO[\'"]/', $config, 'La opción centro debe leer NOMBRE_CENTRO con env().');
        $this->assertMatchesRegularExpression('/^NOMBRE_CENTRO=/m', $ejemplo, 'Añade NOMBRE_CENTRO también a .env.example.');
    }

    public function test_comando_propio(): void
    {
        $comandos = Artisan::all();
        $this->assertArrayHasKey(self::COMANDO, $comandos, 'Falta el comando marcapersonalfp:centro en routes/console.php.');
        $this->assertNotSame('', $comandos[self::COMANDO]->getDescription(), 'Falta la descripción: purpose().');

        $codigo = Artisan::call(self::COMANDO);
        $this->assertSame(0, $codigo);
        $this->assertStringContainsString(self::CENTRO, Artisan::output());
    }
}
