# 5.8. Proyecto: _marcapersonalFP_ en _Laravel_. POO y patrones de diseño
<!-- {% raw %} -->

**Presentación de apoyo** (_RevealJS_): [RA5_8_proyecto_slides.html](./materiales/slides/RA5_8_proyecto_slides.html)

## El hilo `Curriculo`, de vuelta

Hasta ahora, los proyectos han sido el ejemplo guiado del bloque. En este apartado de cierre volvemos al hilo de _v0_, los **currículos**, y reconstruimos con _Laravel_ lo que hicimos a mano en los Bloques 2 a 4:

- el **listado** de currículos (2.5);
- el **alta validada** (3.8);
- el **área privada** "Mi currículo", donde cada alumno da de alta o edita el suyo, con su nombre tomado de la sesión y no del formulario (4.4).

Lo haremos aplicando, además, varios **patrones de diseño**: soluciones probadas a problemas que se repiten en cualquier aplicación. Uno ya lo conocemos, **MVC**. Los nuevos son el **repositorio**, la **inyección de dependencias** y el **_Singleton_**. Al final los compararemos con otros dos que encontrarás a menudo, el **DAO** y el **_Active Record_**.

## MVC, por fin completo

En 5.2 presentamos el patrón **Modelo-Vista-Controlador**, y desde entonces hemos ido completando sus piezas:

| Pieza | Responsabilidad | En _marcapersonalFP_ |
|---|---|---|
| **Modelo** | Los datos y sus reglas | Hasta ahora, un array dentro de `ProyectosController` (5.5) |
| **Vista** | La presentación | `resources/views/`, con _Blade_ (5.4) |
| **Controlador** | Coordinar: recibir la petición, pedir los datos, elegir la vista | `app/Http/Controllers/` (5.5) |

El modelo es la pieza que nos falta: un array de datos dentro del controlador no es un modelo, sino un apaño. En este apartado, los currículos tendrán un modelo de verdad, con dos partes:

- **Las clases del dominio**, `Ciclo` y `Curriculo`: los mismos objetos de 3.5, que ya sabían representar un ciclo o un currículo.
- **Un repositorio**, que sabe **guardarlos y recuperarlos**.

![GET /mi-curriculo llega a MiCurriculoController, que solo conoce la interfaz CurriculoRepository y le pide el currículo del usuario 7. El contenedor le entregó un JsonCurriculoRepository, que lee curriculos.json y devuelve un objeto Curriculo con su Ciclo. El controlador pasa los datos a la vista curriculos.mio y el HTML vuelve al navegador. En el Bloque 6, un EloquentCurriculoRepository guardará en la base de datos sin cambiar el controlador](./images/RA5_8_mvcRepositorio.svg)

El diagrama resume a dónde vamos en este apartado. El controlador solo conoce la **interfaz** del repositorio; qué implementación recibe lo decide el contenedor de servicios. Lo que vuelve del repositorio no son arrays, sino objetos del dominio, y la vista recibe esos objetos. Las piezas se explican, una a una, en las secciones siguientes.

## Las clases de _v0_, en `app/`

Las clases de `vanilla_php/src/` pasan a `app/Dominio/`, con espacio de nombres y sin un solo `require_once` (5.2). No van en `app/Models/` a propósito: allí vivirán, en el Bloque 6, los modelos de _Eloquent_, y un `App\Models\Curriculo` chocaría con el nuestro.

`app/Dominio/Ciclo.php`:

```php
<?php

namespace App\Dominio;

final class Ciclo
{
    public const GRADO_MEDIO = 'Medio';
    public const GRADO_SUPERIOR = 'Superior';

    public function __construct(
        public readonly string $codCiclo,
        public readonly string $nombre,
        public readonly string $grado,
    ) {
    }

    public function titulacion(): string
    {
        return $this->grado === self::GRADO_SUPERIOR ? 'Técnico Superior' : 'Técnico';
    }

    /**
     * Los ciclos que ofrece el centro, indexados por su código.
     * En el Bloque 6 saldrán de la base de datos.
     *
     * @return array<string, Ciclo>
     */
    public static function catalogo(): array
    {
        return [
            'DAW' => new self('DAW', 'Desarrollo de Aplicaciones Web', self::GRADO_SUPERIOR),
            'DAM' => new self('DAM', 'Desarrollo de Aplicaciones Multiplataforma', self::GRADO_SUPERIOR),
            'ASIR' => new self('ASIR', 'Administración de Sistemas Informáticos en Red', self::GRADO_SUPERIOR),
            'SMR' => new self('SMR', 'Sistemas Microinformáticos y Redes', self::GRADO_MEDIO),
        ];
    }
}
```

`app/Dominio/Curriculo.php`, con un dato nuevo, el **`userId`** del alumno al que pertenece, como anticipaba 4.4:

```php
<?php

namespace App\Dominio;

final class Curriculo
{
    public function __construct(
        public readonly int $userId,
        public readonly string $alumno,
        public readonly Ciclo $ciclo,
        public readonly ?string $videoCurriculum = null,
    ) {
    }
}
```

`final` impide que otra clase herede de ellas: no lo necesitan, y así nadie puede cambiar su comportamiento heredando.

## El patrón repositorio

Un **repositorio** es un objeto que se encarga de **guardar y recuperar** los objetos de un tipo, como si fueran una colección en memoria. Quien lo usa no sabe, ni le importa, **dónde** se guardan de verdad: en un array, en un fichero o en una base de datos.

Para que ese "no saber" sea real, el repositorio se define primero como una **interfaz** (3.5): qué sabe hacer, sin decir cómo. `app/Repositorios/CurriculoRepository.php`:

```php
<?php

namespace App\Repositorios;

use App\Dominio\Curriculo;

interface CurriculoRepository
{
    /** @return list<Curriculo> */
    public function todos(): array;

    public function delUsuario(int $userId): ?Curriculo;

    /** Da de alta el currículo, o sustituye el que ya tuviera ese usuario. */
    public function guardar(Curriculo $curriculo): void;
}
```

Después, cada forma de guardar los datos es una **implementación** de esa interfaz. La más sencilla los guarda en un array, `app/Repositorios/ArrayCurriculoRepository.php`:

```php
<?php

namespace App\Repositorios;

use App\Dominio\Curriculo;

final class ArrayCurriculoRepository implements CurriculoRepository
{
    /** @var array<int, Curriculo> Los currículos, indexados por el id de su usuario. */
    private array $curriculos = [];

    public function todos(): array
    {
        return array_values($this->curriculos);
    }

    public function delUsuario(int $userId): ?Curriculo
    {
        return $this->curriculos[$userId] ?? null;
    }

    public function guardar(Curriculo $curriculo): void
    {
        $this->curriculos[$curriculo->userId] = $curriculo;
    }
}
```

Los controladores dependerán solo de la **interfaz**. Así, cambiar dónde se guardan los datos consistirá en escribir otra implementación, sin tocar ni una línea de los controladores.

## Inyección de dependencias

¿Cómo consigue el controlador un repositorio, si solo conoce la interfaz? No lo crea él: lo **pide** en su constructor, y _Laravel_ se lo entrega. Es la **inyección de dependencias**, la misma que en 5.6 nos daba el objeto `Request`:

```php
class CurriculoController extends Controller
{
    public function __construct(
        private readonly CurriculoRepository $curriculos,
    ) {
    }

    public function index(): View
    {
        return view('curriculos.index', ['curriculos' => $this->curriculos->todos()]);
    }
}
```

Quien construye los objetos y resuelve sus dependencias es el **contenedor de servicios** de _Laravel_. Pero a una interfaz no se le puede hacer `new`: hay que decirle al contenedor **qué implementación** entregar cuando alguien pida la interfaz. Eso se hace en el método `register()` de `app/Providers/AppServiceProvider.php` (5.2):

```php
use App\Repositorios\ArrayCurriculoRepository;
use App\Repositorios\CurriculoRepository;

public function register(): void
{
    $this->app->singleton(CurriculoRepository::class, ArrayCurriculoRepository::class);
}
```

Se lee: "cuando alguien pida un `CurriculoRepository`, entrégale un `ArrayCurriculoRepository`… y siempre **el mismo**".

## El patrón _Singleton_

Ese "siempre el mismo" es el patrón **_Singleton_**: una clase de la que solo existe **una instancia**, compartida por todos los que la usan. Tiene sentido para un repositorio: si el controlador y otra clase guardaran en dos repositorios distintos, cada uno vería solo sus propios datos.

La forma clásica de programarlo es que la propia clase lo impida, con un constructor privado y un método estático:

```php
final class Configuracion
{
    private static ?Configuracion $instancia = null;

    private function __construct()      // nadie puede hacer new Configuracion()
    {
    }

    public static function instancia(): self
    {
        return self::$instancia ??= new self();
    }
}
```

En _Laravel_ apenas se escribe así. Es preferible dejar que el **contenedor** decida que hay una sola instancia, con `singleton()`:

| _Singleton_ clásico | _Singleton_ del contenedor |
|---|---|
| La clase obliga a que haya una sola instancia | La clase es normal; es el contenedor quien la comparte |
| Quien la usa llama a `Configuracion::instancia()` y queda atado a esa clase | Quien la usa pide la interfaz y no sabe qué clase recibe |
| En los tests no se puede sustituir por otra | En los tests se sustituye con una línea |

Recuerda que PHP empieza de cero en **cada petición** (2.4): una instancia "única" lo es durante una petición, no para siempre.

## Un repositorio que recuerda

Con `ArrayCurriculoRepository`, el área privada parece funcionar: el alumno envía su currículo, el repositorio lo guarda… y en la petición siguiente ha desaparecido. Es la misma lección de 4.4 con la sesión: un array vive lo que dura una petición.

Hasta el Bloque 6 no tenemos base de datos, pero sí un sitio donde escribir que sobrevive a las peticiones y es compartido por todos los usuarios: un **fichero**. `app/Repositorios/JsonCurriculoRepository.php` guarda los currículos en un fichero JSON, con las funciones de PHP que ya conocemos:

```php
<?php

namespace App\Repositorios;

use App\Dominio\Ciclo;
use App\Dominio\Curriculo;

final class JsonCurriculoRepository implements CurriculoRepository
{
    public function __construct(
        private readonly string $fichero,
    ) {
    }

    public function todos(): array
    {
        return array_values(array_map(fn (array $datos) => $this->aCurriculo($datos), $this->leer()));
    }

    public function delUsuario(int $userId): ?Curriculo
    {
        $datos = $this->leer()[$userId] ?? null;

        return $datos === null ? null : $this->aCurriculo($datos);
    }

    public function guardar(Curriculo $curriculo): void
    {
        $todos = $this->leer();
        $todos[$curriculo->userId] = [
            'user_id' => $curriculo->userId,
            'alumno' => $curriculo->alumno,
            'ciclo' => $curriculo->ciclo->codCiclo,
            'video' => $curriculo->videoCurriculum,
        ];

        // LOCK_EX: si dos peticiones escriben a la vez, la segunda espera a la primera.
        file_put_contents($this->fichero, json_encode($todos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /** @return array<int, array<string, mixed>> */
    private function leer(): array
    {
        if (! is_file($this->fichero)) {
            return [];
        }

        return json_decode((string) file_get_contents($this->fichero), true) ?? [];
    }

    /** @param array<string, mixed> $datos */
    private function aCurriculo(array $datos): Curriculo
    {
        return new Curriculo($datos['user_id'], $datos['alumno'], Ciclo::catalogo()[$datos['ciclo']], $datos['video']);
    }
}
```

Para usarlo, basta con cambiar **una línea** del contenedor. Como su constructor necesita la ruta del fichero, se le pasa una función que lo construye:

```php
$this->app->singleton(
    CurriculoRepository::class,
    fn () => new JsonCurriculoRepository(storage_path('app/curriculos.json')),
);
```

Los controladores no cambian. Y en el Bloque 6 volveremos a hacer lo mismo: un `EloquentCurriculoRepository` que guarde en la base de datos, y otra línea cambiada en el contenedor.

## Repositorio, DAO y _Active Record_

Hay más patrones para separar los datos del resto de la aplicación. Estos dos aparecen a menudo:

- **DAO** (_Data Access Object_): un objeto por **tabla** que ofrece las operaciones básicas sobre ella (insertar, buscar por clave, actualizar, borrar). Habla el idioma de la base de datos: filas y columnas.
- **_Active Record_**: cada objeto **se guarda a sí mismo**: `$curriculo->save()`. Es el patrón de los modelos de _Eloquent_, en el Bloque 6.

| | Repositorio | DAO | _Active Record_ |
|---|---|---|---|
| Quién guarda | El repositorio | El DAO | El propio objeto |
| Piensa en | Objetos del dominio, como una colección | Filas de una tabla | Objetos que conocen su tabla |
| Se cambia el almacenamiento | Con otra implementación de la interfaz | Con otro DAO | Difícilmente: el objeto está atado a la base de datos |
| En _Laravel_ | Lo escribimos nosotros (este apartado) | Se parece al _Query Builder_ (Bloque 6) | Los modelos de _Eloquent_ (Bloque 6) |

El repositorio y el DAO se parecen mucho, y a veces se usan como sinónimos. La diferencia está en el punto de vista: el DAO se organiza según las **tablas**; el repositorio, según los **objetos** con los que piensa la aplicación.

## El proyecto: currículos en _Laravel_

Con todas las piezas, el hilo `Curriculo` queda así:

| Ruta | Nombre | Acción | Acceso |
|---|---|---|---|
| `GET /curriculos` | `curriculos.index` | `CurriculoController@index` | Público |
| `GET /mi-curriculo` | `mi-curriculo` | `MiCurriculoController@edit` | Solo con sesión |
| `PUT /mi-curriculo` | `mi-curriculo.update` | `MiCurriculoController@update` | Solo con sesión |

Como en 4.4, **la misma página da de alta y edita**: si el alumno aún no tiene currículo, el formulario sale vacío; si ya lo tiene, sale con sus datos. Y el nombre del alumno se toma de la **sesión**, no del formulario:

```php
public function update(CurriculoRequest $request): RedirectResponse
{
    $datos = $request->validated();

    $this->curriculos->guardar(new Curriculo(
        $request->user()->id,
        $request->user()->name,
        Ciclo::catalogo()[$datos['ciclo']],
        $datos['video'] ?? null,
    ));

    return to_route('mi-curriculo')->with('mensaje', 'Currículo guardado.');
}
```

`CurriculoRequest` hace el papel de `ValidadorCurriculo` de 3.8, con sus mismas reglas y mensajes:

```php
use App\Dominio\Ciclo;
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'ciclo' => ['required', Rule::in(array_keys(Ciclo::catalogo()))],
        'video' => ['nullable', 'url', 'starts_with:https://', 'max:255'],
    ];
}

public function messages(): array
{
    return [
        'ciclo.required' => 'Elige uno de los ciclos de la lista.',
        'ciclo.in' => 'Elige uno de los ciclos de la lista.',
        'video.url' => 'El vídeo debe ser una URL que empiece por https://',
        'video.starts_with' => 'El vídeo debe ser una URL que empiece por https://',
    ];
}
```

`Rule::in()` construye la regla `in:DAW,DAM,ASIR,SMR` a partir del catálogo: si mañana el centro ofrece un ciclo nuevo, la validación lo acepta sin tocarla.

## Preparación del entorno de trabajo

Seguimos en el proyecto `marcapersonalfp`, con _Breeze_ y la sesión de Ana López de [5.7](./RA5_7_breeze.md). Crea la rama del apartado en el terminal de la máquina virtual:

```bash
git switch main
git pull upstream main
git switch -c RA5_8
```

Para probar el área privada como alumnos distintos, registra también a Marcos Pérez (`marcos.perez@marcapersonalfp.test`) y a Laura García (`laura.garcia@marcapersonalfp.test`).

## Ejercicios

1. **El dominio.** Crea `app/Dominio/Ciclo.php` y `app/Dominio/Curriculo.php` como en este apartado. Compruébalos en `php artisan tinker`:

    ```php
    $daw = App\Dominio\Ciclo::catalogo()['DAW'];
    $daw->titulacion();
    ```

2. **El repositorio.** Crea en `app/Repositorios/` la interfaz `CurriculoRepository` y su implementación `ArrayCurriculoRepository`, y regístrala como _singleton_ en `AppServiceProvider`.

3. **El listado.** Crea `CurriculoController`, con su acción `index`, y la vista `curriculos/index.blade.php`: una tarjeta por currículo con el nombre del alumno, el nombre del ciclo y su titulación, y un enlace al vídeo si lo tiene. Si no hay ninguno, "Todavía no hay currículos". Las rutas de currículos van en su propio fichero, `routes/curriculos.php`.

4. **Mi currículo.** Crea `CurriculoRequest` y `MiCurriculoController`, con las acciones `edit` y `update`, protegidas con `auth`. La vista `curriculos/mio.blade.php` muestra el nombre del alumno (sin campo para cambiarlo) y un formulario con:
    - un `<select name="ciclo">` con las opciones del catálogo, que recibe de la acción en la variable `ciclos`;
    - un campo `video` de tipo `url`;
    - los valores actuales del currículo, si existe: `old('video', $curriculo?->videoCurriculum)`.

    El formulario se envía por `PUT` a `mi-curriculo.update`. Añade a la barra de navegación los enlaces "Currículos" y, para los usuarios identificados, "Mi currículo".

5. **Un repositorio que recuerda.** Prueba el área privada: guarda tu currículo, recarga la página… ¿Qué ha pasado? Crea `JsonCurriculoRepository` y cambia el registro del contenedor para usarlo. Vuelve a probarlo con Ana, Marcos y Laura, y abre `storage/app/curriculos.json`. ¿Has tenido que cambiar algún controlador?

6. **Investiga.** Responde en tu _pull request_:
    - En `MiCurriculoController`, ¿quién llama al constructor y de dónde sale el repositorio que recibe?
    - Con las herramientas de desarrollo, añade al formulario un campo `<input name="alumno" value="Otra Persona">` y envíalo, como en 4.4. ¿Qué nombre se guarda? ¿Por qué?
    - ¿Qué ocurriría si registraras `JsonCurriculoRepository` con `bind()` en lugar de `singleton()`? Pista: ¿cuántas veces se construiría en una petición?
    - En el Bloque 6 escribiremos `EloquentCurriculoRepository`. ¿Qué ficheros tendrás que crear o cambiar? ¿Y cuáles no?

> **Puente.** En _v0_, cada página creaba a mano los objetos que necesitaba (`new ValidadorCurriculo($ciclos)`), cargaba sus clases con `require_once` y guardaba los datos en `$_SESSION`, sabiendo exactamente dónde estaban. En _Laravel_, las clases se cargan solas, el contenedor entrega a cada controlador lo que pide y el repositorio esconde dónde se guardan los datos: cambiar de array a fichero, o de fichero a base de datos, no toca los controladores. Esto que hicimos a mano, el framework lo automatiza así.

## De _v0_ a _Laravel_: el Bloque 5

_marcapersonalFP_ está reconstruida con _Laravel_. Este es el plan que nos marcamos en 5.1, ya cumplido:

| En _v0_ (a mano) | En _Laravel_ | Apartado |
|---|---|---|
| Un script en `public/` por página | Rutas y controladores | 5.3, 5.5 |
| `require_once` de cada fichero de `src/` | _Autoload_ de _Composer_ | 5.2 |
| `session_start()` con sus opciones | Lo hace el framework | 5.2 |
| HTML con `<?php if (): ?>` y `<?= e() ?>` | _Blade_: `@if`, `{{ }}` | 5.4 |
| `ValidadorCurriculo::validar()` | Un _Form Request_ | 5.6, 5.8 |
| `value="<?= e($datos['alumno'] ?? '') ?>"` | `old('alumno')` | 5.6 |
| `$_SESSION['mensaje']` + `unset()` | `redirect()->with()` y `@session` | 5.6 |
| `password_hash()` y `Autenticador` | `Hash::make()` y `Auth::attempt()`, con _Breeze_ | 5.7 |
| `exigirLogin()` | _Middleware_ `auth` | 5.7 |
| Las clases de `src/` | `app/Dominio/` y un repositorio | 5.8 |

Y lo que queda para los bloques siguientes:

| Pendiente | Bloque |
|---|---|
| Modelos _Eloquent_ y `EloquentCurriculoRepository` | 6 |
| `Ciclo::catalogo()` → `Ciclo::all()`, desde la base de datos | 6 |
| `exigirRol()`: que solo los docentes vean ciertos datos (_Gates_ y _Policies_) | 7 |

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 5, copia el archivo [RA5_8_ProyectoTest.php](./materiales/ejercicios-laravel/tests/Feature/RA5_8_ProyectoTest.php) a la carpeta `tests/Feature/` de tu proyecto y ejecuta, en el terminal de `workspace`, ese test en concreto:

```bash
php artisan test --filter=RA5_8_ProyectoTest
```

El test sustituye tu repositorio por un `ArrayCurriculoRepository` vacío, así que no toca tu fichero `curriculos.json`. Comprueba:
- las clases del dominio y del repositorio;
- que el contenedor entrega siempre el mismo `JsonCurriculoRepository`;
- el listado público de currículos;
- que "Mi currículo" exige sesión, guarda el currículo con el nombre de la sesión y lo muestra al volver;
- la validación, con los mensajes de 3.8.

Si todo está bien: `Tests: 6 passed (25 assertions)`.

### Todos los tests del Bloque 5 a la vez

Si has ido copiando a `tests/Feature/` el test de cada apartado, ya tienes ahí los ocho del Bloque 5. Para lanzar **solo** esos, usa `--filter` con el prefijo común, como en 3.8 y 4.4:

```bash
php artisan test --filter=RA5_
```

Si todo el Bloque 5 está resuelto: `Tests: 52 passed (196 assertions)`.

Necesita `nginx` y `php-fpm` levantados, por la petición real del test de 5.1, y los estilos compilados.

---

**Siguiente:** Bloque 6 — Acceso a almacenes de datos (RA6).
<!-- {% endraw %} -->
