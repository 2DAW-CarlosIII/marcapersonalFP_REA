# Crear un registro de FamiliaProfesional

## Definir el destino de los datos del formulario

Para crear un registro de una familia profesional, habíamos creado un formulario que nos permitía introducir los datos de la familia profesional. Deberíamos modificar la propiedad `action` de ese formulario para que apunte al método `store` del controlador `FamiliaProfesionalController`:

{% raw %}
```php
<form action="{{ action([\App\Http\Controllers\FamiliaProfesionalController::class, 'store']) }}" method="POST">
```
{% endraw %}

## Crear la ruta y el método del controlador

Los datos introducidos en el formulario serán enviados al método `store` del controlador `FamiliaProfesionalController`, para lo que debemos crear la ruta correspondiente en el fichero `routes/familias_profesionales.php`:

```php
Route::post('/', [FamiliaProfesionalController::class, 'store']);
```

En el método `store()` del controlador `FamiliaProfesionalController` se crea un nuevo registro de familia profesional a partir de los datos introducidos en el formulario. Posteriormente, se redirige al método `getShow()` del controlador `FamiliaProfesionalController` para mostrar los datos del estudiante creado:

```php
public function store(Request $request): RedirectResponse
{
    $familiaProfesional = FamiliaProfesional::create($request->all());
    return redirect()->action([self::class, 'getShow'], ['id' => $familiaProfesional->id]);
}
```

## Adaptar el modelo `FamiliaProfesional`
Para poder utilizar el método `create()` de la clase `FamiliaProfesional` debemos indicar los campos que se pueden rellenar en el modelo `FamiliaProfesional`. Para ello, en el fichero `app/Models/FamiliaProfesional.php` debemos definir la propiedad `$fillable`:

```php
class FamiliaProfesional extends Model
{
    protected $table = 'familias_profesionales';

    protected $fillable = ['codigo', 'nombre'];
}
```

# Modificar un registro de Familia Profesional

En el caso de la modificación de un registro de la tabla `familias_profesionales`, tenemos casi todo el código desarrollado. No obstante, debemos asegurarnos de que cubrimos todos los requisitos.

## Destino de los datos del formulario

Para modificar un registro de la tabla `familias_profesionales`, habíamos creado un formulario que nos permitía modificar los datos de la familia profesional. Deberíamos modificar la propiedad `action` de ese formulario para que apunte al método `putEdit()` del controlador `FamiliaProfesionalController`:

{% raw %}
```php
<form action="{{ action([\App\Http\Controllers\FamiliaProfesionalController::class, 'putEdit'], ['id' => $familiaProfesional->id]) }}" method="POST">
```
{% endraw %}

## Crear la ruta y el método del controlador

Los datos introducidos en el formulario serán enviados al método `putEdit()` del controlador `FamiliaProfesionalController`, para lo que debemos asegurarnos de disponer de la ruta correspondiente en el fichero `routes/familias_profesionales.php`:

```php
Route::put('/{id}', [FamiliaProfesionalController::class, 'putEdit']);
```

En el método `putEdit()` del controlador `FamiliaProfesionalController` se modifica el registro de la tabla `familias_profesionales` a partir de los datos introducidos en el formulario. Posteriormente, se redirige al método `getShow()` del controlador `FamiliaProfesionalController` para mostrar los datos de la familia profesional modificada:

```php
   public function putEdit(Request $request, $id): RedirectResponse
   {
       $familiaProfesional = FamiliaProfesional::findOrFail($id);

       $familiaProfesional->update($request->all());
       return redirect()->action([self::class, 'getShow'], ['id' => $familiaProfesional->id]);
    }
```
