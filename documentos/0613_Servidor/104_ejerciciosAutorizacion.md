# Ejercicios autorización

## Autorización de controladores

Se debe crear políticas para cada uno de los modelos que manejamos, de acuerdo a la siguiente tabla:

Modelo| index | show | store | update | destroy
-|-|-|-|-|-
Actividad | anónimo | anónimo | docente | propietario | propietario
Ciclo | anónimo | anónimo | admin | admin | admin
Competencia | anónimo | anónimo | admin | admin | admin
Competencias_Actividades | anónimo | anónimo | docente | propietario | propietario
Curriculo | anónimo | anónimo | estudiante | propietario | propietario
Empresa | anónimo | anónimo | docente | docente | propietario
FamiliaProfesional | anónimo | anónimo | admin | admin | admin
Idioma | anónimo | anónimo | admin | admin | admin
ParticipanteProyecto | anónimo | anónimo | docente | propietario (tutor) | propietario (tutor)
ProyectoCiclo | anónimo | anónimo | docente | propietario (tutor) | propietario (tutor)
Proyecto | anónimo | anónimo | docente | propietario (tutor) | propietario (tutor)
Reconocimiento | anónimo | anónimo | estudiante | propietario | propietario
User_ciclo | anónimo | anónimo | estudiante | propietario | propietario
UserCompetencia | anónimo | anónimo | estudiante | propietario | propietario
User | anónimo | anónimo | anónimo | propietario | propietario
UsersIdioma | anónimo | anónimo | estudiante | propietario | propietario

Ten en cuenta que el middleware de autenticación lo debemos incluir en el constructor del controlador, para que se aplique a todos los métodos, a excepción de los que permiten un acceso anónimo.

## Asignación automática del propietario

En el caso de los modelos que tienen alguna referencia al propietario, debemos asignar automáticamente, como propietario, al usuario autenticado, en el momento de crear el recurso. Para ello, debemos modificar el método `store` de cada controlador, de forma que se asigne el propietario al crear el recurso.

## Test para comprobar los permisos de los Currículos

A continuación, se muestra un test para comprobar que los permisos para el modelo `Curriculo` son correctos:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Curriculo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;

class AutorizacionCurriculoTest extends TestCase
{
    // use RefreshDatabase;

    private static $apiurl_curriculo = '/api/v1/curriculos';

    public function curriculoIndex() : TestResponse
    {
        return $this->get(self::$apiurl_curriculo);
    }

    public function curriculoShow() : TestResponse
    {
        $curriculo = Curriculo::inRandomOrder()->first();
        return $this->get(self::$apiurl_curriculo . "/{$curriculo->id}");
    }

    public function curriculoStore() : TestResponse
    {
        $data = [
            'user_id' => 1,
            'video_curriculo' => '123456',
        ];
        return $this->postJson(self::$apiurl_curriculo, $data);
    }

    public function curriculoUpdate($propio = false) : TestResponse
    {
        $curriculo = $propio 
        ? Curriculo::create(['user_id' => Auth::user()->id, 'video_curriculum' => '123456'])
            : Curriculo::inRandomOrder()->first();
        $data = [
            'user_id' => 1,
            'video_curriculo' => '123456',
        ];
        return $this->putJson(self::$apiurl_curriculo . "/{$curriculo->id}", $data);
    }

    public function curriculoDelete($propio = false) : TestResponse
    {
        $curriculo = $propio 
            ? Curriculo::create(['user_id' => Auth::user()->id, 'video_curriculum' => '123456'])
            : Curriculo::inRandomOrder()->first();
        return $this->delete(self::$apiurl_curriculo . "/{$curriculo->id}");
    }

    public function test_anonymous_can_access_curriculo_list_and_view()
    {
        $this->assertGuest();

        $response = $this->curriculoIndex();
        $response->assertStatus(200);

        $response = $this->curriculoShow();
        $response->assertStatus(200);

        $response = $this->curriculoStore();
        $response->assertUnauthorized();

        $response = $this->curriculoUpdate();
        $response->assertUnauthorized();

        $response = $this->curriculoDelete();
        $response->assertFound();

    }

    public function test_admin_can_CRUD_curriculo()
    {
        $admin = User::where('email', env('ADMIN_EMAIL'))->first();
        $this->actingAs($admin);

        $response = $this->curriculoIndex();
        $response->assertSuccessful();

        $response = $this->curriculoShow();
        $response->assertSuccessful();

        $response = $this->curriculoStore();
        $response->assertSuccessful();

        $response = $this->curriculoUpdate();
        $response->assertSuccessful();

        $response = $this->curriculoDelete();
        $response->assertSuccessful();
    }

    public function test_docente_can_access_curriculo_list_and_view()
    {
        $docente = User::where([
            ['email', 'like', '%@' . env('TEACHER_EMAIL_DOMAIN')],
            ['email', '!=', env('ADMIN_EMAIL')],
        ])->first();
        $this->actingAs($docente);

        $response = $this->curriculoIndex();
        $response->assertSuccessful();

        $response = $this->curriculoShow();
        $response->assertSuccessful();

        $response = $this->curriculoStore();
        $response->assertForbidden();

        $response = $this->curriculoUpdate();
        $response->assertForbidden();

        $response = $this->curriculoDelete();
        $response->assertForbidden();
    }


    public function test_estudiante_can_CRUD_curriculo_if_owner()
    {
        $estudiante = User::where('email', 'like', '%@' . env('STUDENT_EMAIL_DOMAIN'))->first();
        $this->actingAs($estudiante);

        $response = $this->curriculoIndex();
        $response->assertSuccessful();

        $response = $this->curriculoShow();
        $response->assertSuccessful();

        $response = $this->curriculoStore();
        $response->assertSuccessful();

        $response = $this->curriculoUpdate($propio = true);
        $response->assertSuccessful();

        $response = $this->curriculoUpdate($propio = false);
        $response->assertForbidden();

        $response = $this->curriculoDelete($propio = true);
        $response->assertSuccessful();

        $response = $this->curriculoDelete($propio = false);
        $response->assertForbidden();
    }

}
```

y un test para los reconocimientos:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Reconocimiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;

class AutorizacionReconocimientoTest extends TestCase
{
    // use RefreshDatabase;

    private static $apiurl_reconocimiento = '/api/v1/reconocimientos';

    public function reconocimientoIndex() : TestResponse
    {
        return $this->get(self::$apiurl_reconocimiento);
    }

    public function reconocimientoShow() : TestResponse
    {
        $reconocimiento = Reconocimiento::inRandomOrder()->first();
        return $this->get(self::$apiurl_reconocimiento . "/{$reconocimiento->id}");
    }

    public function reconocimientoStore() : TestResponse
    {
        $data = [
            'actividad_id' => 1,
            'estudiante_id' => 1,
            'documento' => '123456',
            'fecha' => '2021-10-10',
            'docente_validador' => 35,
        ];
        return $this->postJson(self::$apiurl_reconocimiento, $data);
    }

    public function reconocimientoUpdate($propio = false) : TestResponse
    {
        $data = [
            'actividad_id' => 1,
            'estudiante_id' => $propio ? Auth::user()->id : 100,
            'documento' => '123456',
            'fecha' => '2021-10-10',
            'docente_validador' => 35,
        ];

        $reconocimiento = $propio
            ? Reconocimiento::create($data)
            : Reconocimiento::inRandomOrder()->first();
        return $this->putJson(self::$apiurl_reconocimiento . "/{$reconocimiento->id}", $data);
    }

    public function reconocimientoValidar() : TestResponse
    {
        $reconocimiento = Reconocimiento::inRandomOrder()->first();
        return $this->putJson(self::$apiurl_reconocimiento . "/validar/{$reconocimiento->id}");
    }

    public function reconocimientoDelete($propio = false) : TestResponse
    {
        $data = [
            'actividad_id' => 1,
            'estudiante_id' => $propio ? Auth::user()->id : 100,
            'documento' => '123456',
            'fecha' => '2021-10-10',
            'docente_validador' => 35,
        ];
        $reconocimiento = $propio
            ? Reconocimiento::create($data)
            : Reconocimiento::inRandomOrder()->first();
        return $this->delete(self::$apiurl_reconocimiento . "/{$reconocimiento->id}");
    }

    public function test_anonymous_can_access_reconocimiento_list_and_view()
    {
        $this->assertGuest();

        $response = $this->reconocimientoIndex();
        $response->assertStatus(200);

        $response = $this->reconocimientoShow();
        $response->assertStatus(200);

        $response = $this->reconocimientoStore();
        $response->assertUnauthorized();

        $response = $this->reconocimientoUpdate();
        $response->assertUnauthorized();

        $response = $this->reconocimientoDelete();
        $response->assertFound();

    }

    public function test_admin_can_CRUD_reconocimiento()
    {
        $admin = User::where('email', env('ADMIN_EMAIL'))->first();
        $this->actingAs($admin);

        $response = $this->reconocimientoIndex();
        $response->assertSuccessful();

        $response = $this->reconocimientoShow();
        $response->assertSuccessful();

        $response = $this->reconocimientoStore();
        $response->assertSuccessful();

        $response = $this->reconocimientoUpdate();
        $response->assertSuccessful();

        $response = $this->reconocimientoDelete();
        $response->assertSuccessful();

        $response = $this->reconocimientoValidar();
        $response->assertSuccessful();
    }

    public function test_docente_can_access_reconocimiento_list_and_view()
    {
        $docente = User::where('email', 'like', '%@' . env('TEACHER_EMAIL_DOMAIN'))->first();
        $this->actingAs($docente);

        $response = $this->reconocimientoIndex();
        $response->assertSuccessful();

        $response = $this->reconocimientoShow();
        $response->assertSuccessful();

        $response = $this->reconocimientoStore();
        $response->assertForbidden();

        $response = $this->reconocimientoUpdate();
        $response->assertForbidden();

        $response = $this->reconocimientoDelete();
        $response->assertForbidden();

        $response = $this->reconocimientoValidar();
        $response->assertSuccessful();
    }


    public function test_estudiante_can_CRUD_reconocimiento_if_owner()
    {
        $estudiante = User::where('email', 'like', '%@' . env('STUDENT_EMAIL_DOMAIN'))->first();
        $this->actingAs($estudiante);

        $response = $this->reconocimientoIndex();
        $response->assertSuccessful();

        $response = $this->reconocimientoShow();
        $response->assertSuccessful();

        $response = $this->reconocimientoStore();
        $response->assertSuccessful();

        $response = $this->reconocimientoUpdate($propio = true);
        $response->assertSuccessful();

        $response = $this->reconocimientoUpdate($propio = false);
        $response->assertForbidden();

        $response = $this->reconocimientoDelete($propio = true);
        $response->assertSuccessful();

        $response = $this->reconocimientoDelete($propio = false);
        $response->assertForbidden();

        $response = $this->reconocimientoValidar();
        $response->assertForbidden();
    }

}
```