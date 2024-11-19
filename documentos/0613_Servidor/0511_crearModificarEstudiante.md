# Crear un registro de Estudiante

## Definir el destino de los datos del formulario

Para crear un registro de estudiante, habíamos creado un formulario que nos permitía introducir los datos del estudiante. Deberíamos modificar la propiedad `action`de ese formulario para que apunte al método `store` del controlador `EstudianteController`:

{% raw %}
```php
<form action="{{ action([\App\Http\Controllers\EstudianteController::class, 'store']) }}" method="POST">
```
{% endraw %}

## Crear la ruta y el método del controlador

Los datos introducidos en el formulario serán enviados al método `store` del controlador `EstudianteController`, para lo que debemos crear la ruta correspondiente en el grupo `estudiantes` del fichero `routes/web.php`:

```php
Route::post('/', [EstudianteController::class, 'store']);
```

En el método `store()` del controlador `EstudianteController` se crea un nuevo registro de estudiante a partir de los datos introducidos en el formulario. Posteriormente, se redirige al método `getShow()` del controlador `EstudianteController` para mostrar los datos del estudiante creado:

```php
public function store(Request $request): RedirectResponse
{
    $estudiante = Estudiante::create($request->all());
    
    return redirect()->action([self::class, 'getShow'], ['id' => $estudiante->id]);
}
```

## Adaptar el modelo `Estudiante`
Para poder utilizar el método `create()` de la clase `Estudiante` debemos indicar los campos que se pueden rellenar en el modelo `Estudiante`. Para ello, en el fichero `app/Models/Estudiante.php` debemos definir la propiedad `$fillable`:

```php
class Estudiante extends Model
{
    use HasFactory;

    protected $fillable = [
       'nombre',
       'apellidos',
       'direccion',
       'votos',
       'ciclo',
   ];
}
```

# Modificar un registro de Estudiante

En el caso de la modificación de un registro de estudiantes, tenemos casi todo el código desarrollado. No obstante, debemos asegurarnos de que cubrimos todos los reuisitos.

## Destino de los datos del formulario

Para modificar un registro de estudiante, habíamos creado un formulario que nos permitía modificar los datos del estudiante. Deberíamos modificar la propiedad `action`de ese formulario para que apunte al método `putEdit()` del controlador `EstudianteController`:

{% raw %}
```php
<form action="{{ action([\App\Http\Controllers\EstudianteController::class, 'putEdit'], ['id' => $estudiante->id]) }}" method="POST">
```
{% endraw %}

## Crear la ruta y el método del controlador

Los datos introducidos en el formulario serán enviados al método `putEdit()` del controlador `EstudianteController`, para lo que debemos asegurarnos de disponer de la ruta correspondiente en el grupo `estudiantes` del fichero `routes/web.php`:

```php
Route::put('/{id}', [EstudianteController::class, 'putEdit']);
```

En el método `putEdit()` del controlador `EstudianteController` se modifica el registro de estudiante a partir de los datos introducidos en el formulario. Posteriormente, se redirige al método `getShow()` del controlador `EstudianteController` para mostrar los datos del estudiante modificado:

```php
   public function putEdit(Request $request, $id): RedirectResponse
   {
       $estudiante = Estudiante::findOrFail($id);

       $estudiante->update($request->all());
       return redirect()->action([self::class, 'getShow'], ['id' => $estudiante->id]);
    }
```
