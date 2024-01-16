# Controlador de recursos API para los recursos `ciclos`

## Creación del Modelo

En capítulos anteriores hemos creado modelos para diferentes tablas. Estos modelos no incluían los de las tablas `ciclos` ni `familias profesionales`

Comenzaremos generando el modelo de la tabla ciclos:

```bash
php artisan make:model Ciclo
```

Para poder utilizar posteriormente el método `create()` del modelo, deberemos, previamente, incluir el siguiente código en dicho modelo :

    protected $fillable = [
        'id',
        'codCiclo',
        'codFamilia',
        'familia_id',
        'grado',
        'nombre'
    ];

## Controlador

A continuación, crearemos el controlador de recursos con el siguiente comando artisan:

```bash
php artisan make:controller API/CicloController --api --model=Ciclo
```

Este comando creará el archivo con el código del controlador `app/Http/Controllers/API/CicloController.php`:

## Recursos API y Colecciones

Como ya sabemos, el objetivo de una _API_ es gestionar **recursos**. _Laravel_ nos permite generar los recursos correspondientes a un _Modelo_ y las **colecciones** de esas instancias de manera sencilla, con el siguiente comando:

En _Laravel_, los **Recursos API** y las **Colecciones** son dos características que permiten transformar tus modelos y colecciones de modelos en _JSON_.

### Recursos API

Un **Recurso API** en _Laravel_ es una forma de transformar un modelo individual en una estructura _JSON_. Esto es útil _cuando necesitas controlar qué datos se envían al cliente y cómo se estructuran_. Para crear un recurso API, puedes usar el comando php artisan make:resource, como se muestra en tu código:

```bash
php artisan make:resource CicloResource
```

Esto creará una nueva clase `CicloResource` en el directorio `app/Http/Resources`. Podemos personalizar el método `toArray()` de esta clase para controlar qué datos de tu modelo `Ciclo` se exponen a través de la _API_.

### Colecciones

Las Colecciones en Laravel son una forma de encapsular una colección de modelos y transformarlos en _JSON_. Son útiles cuando necesitas enviar una lista de modelos a través de tu _API_. Para transformar una colección de modelos en _JSON_, puedes usar el método `collection()` en tu recurso, como se muestra a continuación:
```php
<?php
    CicloResource::collection(Ciclo::all());
?>
```

Esto transformará cada modelo Ciclo en la colección de ciclos obtenida por la ejecución de `Ciclo::all()` utilizando la lógica de transformación definida en tu `CicloResource`.

## Rutas

Por último, crearemos una _ruta de recurso_ para una _API_. Para ello, incorporaremos, en el fichero `/routes/api.php`, el siguiente contenido:

> Entre las 2 rutas que ya tenemos definidas, incorporaremos:

```php
Route::prefix('v1')->group(function () {
    Route::apiResource('ciclos', CicloController::class);
});
```

> A partir de este momento, incluiremos todas las rutas en un grupo de rutas con el prefijo `v1` por lo que necesitaremos actualizar el valor de la variable de entorno `VITE_JSON_SERVER_URL` al valor `http://marcapersonalfp.test/api/v1`.

Para poder utilizar la clase `CicloController`, deberemos añadir el use correspondiente:

```php
use App\Http\Controllers\API\CustomerController;
```

## Funcionalidad

Como hemos definido previamente `CicloResource` como un recurso para el modelo `Ciclo`:

- para los métodos que devuelvan una única instancia del modelo Ciclo, utilizaremos `new CicloResource(instancia)`,
- mientras que si devolvemos un conjunto de instancias, devolveremos `CicloResource::collection(colección)`

Así, el código de nuestro controlador quedaría de la siguiente forma:

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CicloResource;
use App\Models\Ciclo;
use Illuminate\Http\Request;

class CicloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CicloResource::collection(Ciclo::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $ciclo = json_decode($request->getContent(), true);

        $ciclo = Ciclo::create($ciclo['data']['attributes']);

        return new CicloResource($ciclo);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ciclo $ciclo)
    {
        return new CicloResource($ciclo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ciclo $ciclo)
    {
        $cicloData = json_decode($request->getContent(), true);
        $ciclo->update($cicloData['data']['attributes']);

        return new CicloResource($ciclo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ciclo $ciclo)
    {
        $ciclo->delete();
    }
}
```
