<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Issues "Los formularios del ePortfolio" (5.6): el principal y sus cuatro sub-issues.
 *
 * Cada issue ejecuta solo sus tests, con su grupo:
 *   php artisan test --filter=RA5_6_ValidacionTest --group=principal
 *   php artisan test --filter=RA5_6_ValidacionTest --group=evidencias
 *   php artisan test --filter=RA5_6_ValidacionTest --group=familias-profesionales
 *   php artisan test --filter=RA5_6_ValidacionTest --group=ciclos-formativos
 *   php artisan test --filter=RA5_6_ValidacionTest --group=modulos-formativos
 *
 * Con todo fusionado en main, el test completo: php artisan test --filter=RA5_6_ValidacionTest
 */
class RA5_6_ValidacionTest extends TestCase
{
    private const CONTROLADORES = 'App\\Http\\Controllers\\';

    private const REQUESTS = 'App\\Http\\Requests\\';

    // Datos válidos de cada tabla. Durante los tests, Laravel no exige el token CSRF.

    private const EVIDENCIA = [
        'estudiante_id' => 5,
        'tarea_id' => 4,
        'url' => 'https://example.com/evidencias/9',
        'descripcion' => 'Formularios con validación',
        'estado_validacion' => 'pendiente',
    ];

    private const FAMILIA = [
        'codigo' => 'DEP',
        'nombre' => 'DEPORTES Y OCIO',
        'descripcion' => 'Familia profesional de prueba',
    ];

    private const CICLO = [
        'codigo_familia' => 'IFC',
        'grado' => 'superior',
        'codigo' => 'DAW3',
        'nombre' => 'Técnico Superior en Desarrollo de Aplicaciones Web',
        'descripcion' => 'Ciclo formativo de prueba',
    ];

    private const MODULO = [
        'codigo_ciclo' => 'DAPW3',
        'nombre' => 'Desarrollo Web en Entorno Servidor',
        'codigo' => '0613',
        'horas_totales' => 180,
        'curso_escolar' => 'SEGUNDO',
        'centro' => 'CIFP Carlos III',
        'descripcion' => 'Módulo formativo de prueba',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Desde 5.7, el alta y la edición exigen sesión: los tests entran como un usuario identificado.
        $this->actingAs(new User(['name' => 'Ana López', 'email' => 'ana.lopez@eportfolio.test']));
        // Las vistas usan @vite: el test no necesita los estilos compilados.
        $this->withoutVite();
    }

    /** Las rutas store (POST) y update (PUT) están en el grupo del recurso y apuntan a su controlador. */
    private function comprobarRutas(string $recurso, string $controlador): void
    {
        foreach (['store' => 'POST', 'update' => 'PUT'] as $accion => $metodo) {
            $ruta = Route::getRoutes()->getByName("{$recurso}.{$accion}");

            $this->assertNotNull($ruta, "Falta la ruta {$recurso}.{$accion}.");
            $this->assertSame(self::CONTROLADORES . "{$controlador}@{$accion}", $ruta->getActionName(), "La ruta {$recurso}.{$accion} debe apuntar a {$controlador}@{$accion}.");
            $this->assertContains($metodo, $ruta->methods(), "La ruta {$recurso}.{$accion} debe responder a {$metodo}.");
            $this->assertSame($recurso, trim((string) $ruta->getPrefix(), '/'), "La ruta {$recurso}.{$accion} debe estar en el grupo de routes/{$recurso}.php.");
        }

        $this->assertSame(url("/{$recurso}"), route("{$recurso}.store"), "La ruta {$recurso}.store debe llevar a /{$recurso}.");
        $this->assertSame(url("/{$recurso}/edit/0"), route("{$recurso}.update", 0), "La ruta {$recurso}.update debe llevar a /{$recurso}/edit/{id}.");
    }

    /** El Form Request existe, y las acciones store y update lo reciben. */
    private function comprobarFormRequest(string $controlador, string $request): void
    {
        $clase = self::REQUESTS . $request;

        $this->assertTrue(class_exists($clase), "Falta el Form Request {$request}: php artisan make:request {$request}.");
        $this->assertTrue(is_subclass_of($clase, FormRequest::class), "{$request} debe heredar de FormRequest.");

        foreach (['store', 'update'] as $accion) {
            $this->assertTrue(method_exists(self::CONTROLADORES . $controlador, $accion), "Falta la acción {$accion} en {$controlador}.");

            $tipos = array_map(
                fn ($parametro) => ltrim((string) $parametro->getType(), '?'),
                (new ReflectionMethod(self::CONTROLADORES . $controlador, $accion))->getParameters()
            );
            $this->assertContains($clase, $tipos, "La acción {$accion} de {$controlador} debe recibir un {$request}, no un Request.");
        }
    }

    /** El formulario de alta se envía a store, con el token CSRF y un campo por regla, y el listado enlaza con él. */
    private function comprobarFormularioDeAlta(string $recurso, array $campos, string $boton): void
    {
        $html = (string) $this->get("/{$recurso}/create")->assertOk()->getContent();

        $this->assertStringContainsString('action="' . route("{$recurso}.store") . '"', $html, "El formulario de alta debe enviarse a route('{$recurso}.store').");
        $this->assertMatchesRegularExpression('/method=["\']post["\']/i', $html, 'El formulario de alta debe enviarse por POST.');
        $this->assertStringContainsString('name="_token"', $html, 'Falta @csrf en el formulario de alta.');
        foreach ($campos as $campo) {
            $this->assertStringContainsString("name=\"{$campo}\"", $html, "Al formulario de alta le falta el campo {$campo}.");
        }
        $this->assertStringContainsString($boton, $html, "El botón del formulario de alta debe decir «{$boton}».");

        $listado = (string) $this->get("/{$recurso}")->assertOk()->getContent();
        $this->assertStringContainsString(route("{$recurso}.create"), $listado, "El listado debe enlazar con el formulario de alta: route('{$recurso}.create').");
    }

    /** El formulario de edición se envía a update por PUT y muestra los valores actuales. */
    private function comprobarFormularioDeEdicion(string $recurso, int $id, array $valores): void
    {
        $html = (string) $this->get("/{$recurso}/edit/{$id}")->assertOk()->getContent();

        $this->assertStringContainsString('action="' . route("{$recurso}.update", $id) . '"', $html, "El formulario de edición debe enviarse a route('{$recurso}.update', \$id).");
        $this->assertStringContainsString('name="_method" value="PUT"', $html, "Falta @method('PUT') en el formulario de edición.");
        $this->assertStringContainsString('name="_token"', $html, 'Falta @csrf en el formulario de edición.');
        foreach ($valores as $valor) {
            $this->assertStringContainsString('value="' . e($valor) . '"', $html, "El formulario de edición debe mostrar el valor actual «{$valor}»: old('campo', valor actual).");
        }
    }

    /** Un alta válida redirige al listado con su mensaje, que aparece en la página. */
    private function comprobarAltaValida(string $recurso, array $datos, string $mensaje): void
    {
        $this->post("/{$recurso}", $datos)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route("{$recurso}.index"))
            ->assertSessionHas('mensaje', $mensaje);

        // Siguiendo la redirección, el mensaje aparece en el listado.
        $this->followingRedirects()
            ->post("/{$recurso}", $datos)
            ->assertOk()
            ->assertSeeText($mensaje);
    }

    /** Un alta no válida vuelve al formulario con un error por cada campo incorrecto y lo escrito. */
    private function comprobarAltaNoValida(string $recurso, array $datos, array $errores, string $conservado): void
    {
        $this->from("/{$recurso}/create")
            ->post("/{$recurso}", $datos)
            ->assertRedirect("/{$recurso}/create")
            ->assertSessionHasErrors($errores)
            ->assertSessionHasInput($conservado, $datos[$conservado]);
    }

    /** Los campos obligatorios tienen su mensaje en español, y uno de ellos es exactamente el pedido. */
    private function comprobarMensajes(string $recurso, array $obligatorios, array $datos, string $campo, string $mensaje): void
    {
        $this->from("/{$recurso}/create")->post("/{$recurso}", $datos);
        $this->assertSame($mensaje, session('errors')?->first($campo), "El mensaje de error de {$campo} debe ser exactamente «{$mensaje}».");

        $this->from("/{$recurso}/create")->post("/{$recurso}", []);
        foreach ($obligatorios as $obligatorio) {
            $texto = (string) session('errors')?->first($obligatorio);

            $this->assertNotSame('', $texto, "El campo {$obligatorio} debe ser obligatorio (required).");
            $this->assertStringNotContainsString('field is required', $texto, "Escribe en messages() el mensaje de {$obligatorio}.required en español, no «{$texto}».");
        }
    }

    /** Una edición válida redirige al detalle con su mensaje; una no válida vuelve al formulario; un id inexistente da 404. */
    private function comprobarEdicion(string $recurso, int $id, array $datos, string $mensaje, int $inexistente): void
    {
        $this->put("/{$recurso}/edit/{$id}", $datos)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route("{$recurso}.show", $id))
            ->assertSessionHas('mensaje', $mensaje);

        $this->from("/{$recurso}/edit/{$id}")
            ->put("/{$recurso}/edit/{$id}", [])
            ->assertRedirect("/{$recurso}/edit/{$id}")
            ->assertSessionHasErrors();

        $this->put("/{$recurso}/edit/{$inexistente}", $datos)->assertNotFound();
    }

    // Issue principal: el mensaje flash, en el layout.

    #[Group('principal')]
    public function test_el_layout_muestra_el_mensaje_flash(): void
    {
        $this->get('/evidencias')->assertOk()->assertDontSeeText('Mensaje de prueba del issue');

        // El mensaje debe salir en todas las páginas: va en <x-layout>, no en cada vista.
        $this->withSession(['mensaje' => 'Mensaje de prueba del issue'])
            ->get('/evidencias')
            ->assertOk()
            ->assertSeeText('Mensaje de prueba del issue');

        $this->withSession(['mensaje' => 'Otro mensaje de prueba'])
            ->get('/familias-profesionales')
            ->assertOk()
            ->assertSeeText('Otro mensaje de prueba');
    }

    // Sub-issue 1: evidencias.

    #[Group('evidencias')]
    public function test_evidencias_rutas_de_alta_y_edicion(): void
    {
        $this->comprobarRutas('evidencias', 'EvidenciaController');
    }

    #[Group('evidencias')]
    public function test_evidencias_form_request(): void
    {
        $this->comprobarFormRequest('EvidenciaController', 'EvidenciaRequest');
    }

    #[Group('evidencias')]
    public function test_evidencias_formulario_de_alta(): void
    {
        $this->comprobarFormularioDeAlta('evidencias', array_keys(self::EVIDENCIA), 'Añadir evidencia');
    }

    #[Group('evidencias')]
    public function test_evidencias_formulario_de_edicion(): void
    {
        $this->comprobarFormularioDeEdicion('evidencias', 0, ['https://example.com/evidencias/1']);
    }

    #[Group('evidencias')]
    public function test_evidencias_alta_valida(): void
    {
        $this->comprobarAltaValida('evidencias', self::EVIDENCIA, 'Evidencia «Formularios con validación» creada.');
    }

    #[Group('evidencias')]
    public function test_evidencias_alta_no_valida(): void
    {
        $this->comprobarAltaNoValida(
            'evidencias',
            ['estudiante_id' => 'abc', 'tarea_id' => 0, 'url' => 'no es una url', 'descripcion' => '', 'estado_validacion' => 'aprobada'],
            ['estudiante_id', 'tarea_id', 'url', 'descripcion', 'estado_validacion'],
            'url'
        );
    }

    #[Group('evidencias')]
    public function test_evidencias_mensajes_en_espanol(): void
    {
        $this->comprobarMensajes(
            'evidencias',
            array_keys(self::EVIDENCIA),
            ['url' => 'no es una url'] + self::EVIDENCIA,
            'url',
            'La dirección de la evidencia no es una URL válida.'
        );
    }

    #[Group('evidencias')]
    public function test_evidencias_edicion(): void
    {
        $this->comprobarEdicion('evidencias', 3, self::EVIDENCIA, 'Evidencia «Formularios con validación» actualizada.', 8);
    }

    // Sub-issue 2: familias profesionales.

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_rutas_de_alta_y_edicion(): void
    {
        $this->comprobarRutas('familias-profesionales', 'FamiliaProfesionalController');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_form_request(): void
    {
        $this->comprobarFormRequest('FamiliaProfesionalController', 'FamiliaProfesionalRequest');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_formulario_de_alta(): void
    {
        $this->comprobarFormularioDeAlta('familias-profesionales', array_keys(self::FAMILIA), 'Añadir familia profesional');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_formulario_de_edicion(): void
    {
        $this->comprobarFormularioDeEdicion('familias-profesionales', 0, ['ADG', 'ADMINISTRACIÓN Y GESTIÓN']);
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_alta_valida(): void
    {
        $this->comprobarAltaValida('familias-profesionales', self::FAMILIA, 'Familia profesional «DEPORTES Y OCIO» creada.');
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_alta_no_valida(): void
    {
        $this->comprobarAltaNoValida(
            'familias-profesionales',
            ['codigo' => 'con espacios', 'nombre' => '', 'descripcion' => 'Familia profesional de prueba'],
            ['codigo', 'nombre'],
            'descripcion'
        );
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_mensajes_en_espanol(): void
    {
        $this->comprobarMensajes(
            'familias-profesionales',
            ['codigo', 'nombre'],
            ['codigo' => ''] + self::FAMILIA,
            'codigo',
            'El código de la familia profesional es obligatorio.'
        );
    }

    #[Group('familias-profesionales')]
    public function test_familias_profesionales_edicion(): void
    {
        // Las 26 familias tienen los id del 0 al 25.
        $this->comprobarEdicion('familias-profesionales', 10, self::FAMILIA, 'Familia profesional «DEPORTES Y OCIO» actualizada.', 26);
    }

    // Sub-issue 3: ciclos formativos.

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_rutas_de_alta_y_edicion(): void
    {
        $this->comprobarRutas('ciclos-formativos', 'CicloFormativoController');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_form_request(): void
    {
        $this->comprobarFormRequest('CicloFormativoController', 'CicloFormativoRequest');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_formulario_de_alta(): void
    {
        $this->comprobarFormularioDeAlta('ciclos-formativos', array_keys(self::CICLO), 'Añadir ciclo formativo');

        // El grado es una lista cerrada: un <select> con sus tres valores.
        $html = (string) $this->get('/ciclos-formativos/create')->getContent();
        foreach (['basico', 'medio', 'superior'] as $grado) {
            $this->assertStringContainsString("value=\"{$grado}\"", $html, "El campo grado debe ofrecer la opción {$grado}.");
        }
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_formulario_de_edicion(): void
    {
        $this->comprobarFormularioDeEdicion('ciclos-formativos', 0, ['AFD', 'ACEC2', 'Técnico en Actividades Ecuestres']);
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_alta_valida(): void
    {
        $this->comprobarAltaValida('ciclos-formativos', self::CICLO, 'Ciclo formativo «Técnico Superior en Desarrollo de Aplicaciones Web» creado.');
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_alta_no_valida(): void
    {
        $this->comprobarAltaNoValida(
            'ciclos-formativos',
            ['codigo_familia' => 'I F C', 'grado' => 'master', 'codigo' => 'DAW 3', 'nombre' => '', 'descripcion' => 'Ciclo formativo de prueba'],
            ['codigo_familia', 'grado', 'codigo', 'nombre'],
            'descripcion'
        );
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_mensajes_en_espanol(): void
    {
        $this->comprobarMensajes(
            'ciclos-formativos',
            ['codigo_familia', 'grado', 'codigo', 'nombre'],
            ['grado' => 'master'] + self::CICLO,
            'grado',
            'El grado debe ser básico, medio o superior.'
        );
    }

    #[Group('ciclos-formativos')]
    public function test_ciclos_formativos_edicion(): void
    {
        $this->comprobarEdicion('ciclos-formativos', 5, self::CICLO, 'Ciclo formativo «Técnico Superior en Desarrollo de Aplicaciones Web» actualizado.', 9999);
    }

    // Sub-issue 4: módulos formativos.

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_rutas_de_alta_y_edicion(): void
    {
        $this->comprobarRutas('modulos-formativos', 'ModuloFormativoController');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_form_request(): void
    {
        $this->comprobarFormRequest('ModuloFormativoController', 'ModuloFormativoRequest');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_formulario_de_alta(): void
    {
        $this->comprobarFormularioDeAlta('modulos-formativos', array_keys(self::MODULO), 'Añadir módulo formativo');

        // El curso es una lista cerrada: un <select> con sus dos valores.
        $html = (string) $this->get('/modulos-formativos/create')->getContent();
        foreach (['PRIMERO', 'SEGUNDO'] as $curso) {
            $this->assertStringContainsString("value=\"{$curso}\"", $html, "El campo curso_escolar debe ofrecer la opción {$curso}.");
        }
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_el_centro_sale_de_la_configuracion(): void
    {
        $centro = (string) config('eportfolio.centro');
        if ($centro !== '') {
            $this->get('/modulos-formativos/create')->assertSee('value="' . e($centro) . '"', false);
        }

        // El valor debe salir de la configuración, no estar escrito en la vista.
        config(['eportfolio.centro' => 'Centro de prueba']);
        $html = (string) $this->get('/modulos-formativos/create')->getContent();
        $this->assertStringContainsString('value="Centro de prueba"', $html, "En el alta, el campo centro debe proponer el centro de la configuración: old('centro', config('eportfolio.centro')).");
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_formulario_de_edicion(): void
    {
        $this->comprobarFormularioDeEdicion('modulos-formativos', 0, ['DAPW3', '0483', 'Sistemas Informáticos']);
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_alta_valida(): void
    {
        $this->comprobarAltaValida('modulos-formativos', self::MODULO, 'Módulo formativo «Desarrollo Web en Entorno Servidor» creado.');
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_alta_no_valida(): void
    {
        $this->comprobarAltaNoValida(
            'modulos-formativos',
            ['codigo_ciclo' => 'DAPW3', 'nombre' => '', 'codigo' => '0613', 'horas_totales' => 'muchas', 'curso_escolar' => 'TERCERO'],
            ['nombre', 'horas_totales', 'curso_escolar'],
            'codigo'
        );
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_mensajes_en_espanol(): void
    {
        $this->comprobarMensajes(
            'modulos-formativos',
            ['codigo_ciclo', 'nombre', 'codigo', 'horas_totales'],
            ['horas_totales' => 'muchas'] + self::MODULO,
            'horas_totales',
            'Las horas totales deben ser un número entero.'
        );
    }

    #[Group('modulos-formativos')]
    public function test_modulos_formativos_edicion(): void
    {
        $this->comprobarEdicion('modulos-formativos', 0, self::MODULO, 'Módulo formativo «Desarrollo Web en Entorno Servidor» actualizado.', 9999);
    }
}
