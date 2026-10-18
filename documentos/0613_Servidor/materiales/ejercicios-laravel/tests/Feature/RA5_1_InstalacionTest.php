<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PDO;
use Tests\TestCase;

class RA5_1_InstalacionTest extends TestCase
{
    // Repositorio base del curso: "upstream" apunta a él; "origin", a tu fork.
    private const REPO_BASE = '2DAW-CarlosIII/marcapersonalfp2627';

    // Desde "workspace" se usa el nombre del servicio ("nginx"); la cabecera Host elige el sitio.
    private const NGINX = 'http://nginx/';
    private const SITIO = 'marcapersonalfp.test';

    /** URL del remoto indicado, leída de .git/config, o cadena vacía si no existe. */
    private function urlRemoto(string $remoto): string
    {
        $config = (string) @file_get_contents(base_path('.git/config'));
        $patron = '/\[remote "' . preg_quote($remoto, '/') . '"\][^\[]*?url\s*=\s*(\S+)/';

        return preg_match($patron, $config, $coincidencia) ? $coincidencia[1] : '';
    }

    public function test_upstream_apunta_al_repositorio_base(): void
    {
        $this->assertFileExists(base_path('.git/config'), 'El proyecto no es un clon de git.');
        $this->assertMatchesRegularExpression(
            '#' . preg_quote(self::REPO_BASE, '#') . '(\.git)?$#i',
            $this->urlRemoto('upstream'),
            'El remoto "upstream" debe apuntar al repositorio base.'
        );
    }

    public function test_origin_apunta_a_tu_fork(): void
    {
        $url = $this->urlRemoto('origin');

        $this->assertStringContainsStringIgnoringCase('/marcapersonalfp2627', $url, 'El remoto "origin" debe ser tu fork de marcapersonalfp2627.');
        $this->assertStringNotContainsStringIgnoringCase('2DAW-CarlosIII/', $url, '"origin" apunta al repositorio base, no a tu fork.');
    }

    public function test_clave_de_aplicacion_generada(): void
    {
        $this->assertMatchesRegularExpression('/^base64:.{40,}$/', (string) config('app.key'), 'Falta APP_KEY: ejecuta php artisan key:generate.');
    }

    public function test_base_sqlite_migrada(): void
    {
        // Se abre el fichero directamente: durante los tests, phpunit.xml usa otra base.
        $fichero = database_path('database.sqlite');
        $this->assertFileExists($fichero, 'Falta database/database.sqlite: ejecuta php artisan migrate.');

        $tablas = (new PDO('sqlite:' . $fichero))
            ->query("SELECT name FROM sqlite_master WHERE type = 'table'")
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach (['migrations', 'users', 'sessions'] as $tabla) {
            $this->assertContains($tabla, $tablas, "Falta la tabla $tabla: ejecuta php artisan migrate.");
        }
    }

    public function test_configuracion_del_ejercicio_4(): void
    {
        $this->assertSame('marcapersonalFP', config('app.name'));
        $this->assertSame('http://marcapersonalfp.test', config('app.url'));
    }

    public function test_el_sitio_de_nginx_sirve_laravel(): void
    {
        // Petición real, como la del navegador: pasa por nginx y php-fpm, con tu .env.
        $respuesta = Http::withHeaders(['Host' => self::SITIO])->get(self::NGINX);
        $cookies   = implode("\n", $respuesta->toPsrResponse()->getHeader('Set-Cookie'));

        $this->assertSame(200, $respuesta->status(), 'http://marcapersonalfp.test no responde 200: revisa el sitio de nginx y los permisos (--user=laradock).');
        // La cookie de sesión de Laravel se llama <app>-session (APP_NAME en minúsculas); la de PHP plano, PHPSESSID.
        $this->assertStringContainsString('-session=', $cookies, 'Quien responde no es Laravel: revisa server_name y root en marcapersonalfp.conf.');
    }
}
