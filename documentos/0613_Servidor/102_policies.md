# Policies

## Generando Policies

Las `policies` son clases que organizan la lógica de **autorización** referente a un determinado modelo o recurso.

En nuestro caso, organizaremos la autorización sobre el modelo `App\Models\Curriculo` con el correspondiente `policy` `App\Policies\CurriculoPolicy` para autorizar las acciones del usuario, tales como crear o actualizar curriculos.

Podemos generar la `policy` usando el comando _Artisan_ `make:policy` que podemos asociar al modelo `Curriculo` con el modificador `--model`:

```bash
php artisan make:policy CurriculoPolicy --model=Curriculo
```

## Registando Policies

Si el `model` y la `policy` siguen las convenciones de nomenclatura de Laravel, las `policy`serán registradas automáticamente por Laravel. No obstante, podemos registrarlas manualmente en el método `boot()` de `App\Providers\AppServiceProvider`, de manera similar a cómo lo hicimos con los `gate`:

```diff
// app/Providers/AppServiceProvider.php
 namespace App\Providers;
 
+use App\Models\Curriculo;
+use App\Policies\CurriculoPolicy;
 use Illuminate\Auth\Notifications\ResetPassword;
+use Illuminate\Support\Facades\Gate;
 use Illuminate\Support\ServiceProvider;
 
 class AppServiceProvider extends ServiceProvider
@@ -23,5 +26,6 @@ public function boot(): void
         ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
             return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
         });
+        Gate::policy(Curriculo::class, CurriculoPolicy::class);
     }
 }

```

> Fíjate que hemos eliminado las autorizaciones con Gates definidas en el apartado anterior.

## Creando Policies

### Policy en Métodos

Una vez que la clase `policy` se ha registrado, se pueden crear métodos para autorizar acciones. Por ejemplo, vamos a definir el método `update` en nuestro `CurriculoPolicy` que determinará si un `User` podrá modificar una instancia de `Curriculo`.

Este método `update` recibirá una instancia de `User` y una instancia `Curriculo` como argumentos, y devolverá `true` o `false` indicando si el usuario está o no autorizado para actualizar el `Curriculo`. En nuestro caso, verificaremos que el `id` del usuario autenticado es el mismo que el valor del atributo `user_id` del curriculo:

```php
<?php

namespace App\Policies;

use App\Models\Curriculo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CurriculoPolicy
{
    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Curriculo  $curriculo
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Curriculo $curriculo)
    {
        return $user->id === $curriculo->user_id;
    }
}
```

Como hemos usado la opción `--model` al generar la `policy` con el comando _Artisan_, la clase ya contendrá métodos para las acciones `viewAny`, `view`, `create`, `update`, `delete`, `restore`, y `forceDelete`.

### Métodos sin modelos

Hay ocasiones, como ocurre con la acción `create` en el que no se recibe ninguna instancia del modelo y únicamente recibimos la instancia del usuario autenticado. Vamos a suponer que el usuario con el `id = 1` es el **administrador** y que únicamente le vamos a permitir a ese administrador la creación de registros curriculos:

```php
    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->email === env(ADMIN_EMAIL);
    }
```

### Filtros en las Policies

Algunos usuarios deberán estar autorizados a realizar cualquiera de las acciones sobre los modelos. Para ello, definiremos el siguiente _closure_ en `app/Providers/AppServiceProvider.php`. Esta característica se suele utilizar para autorizar a los administradores a realizar cualquier acción:

```php
  Gate::before(function (User $user, string $ability) {
      if ($user->isAdministrator()) {
          return true;
      }
  });
```
> Para poder utilizar el método `isAdministrador()` necesitaremos crear dicho método en el modelo `User`.

## Autorizando acciones

### A través del modelo User

El modelo `User` incluye 2 métodos que nos ayudarán a autorizar las acciones: `can()` y `cannot()`. Estos métodos reciben el nombre de la acción que queremos autorizar y la instancia del modelo. Por ejemplo, permitirá determinar si un usuario está autorizado a actualizar (`update`) una determinada instancia del modelo `Curriculo`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Curriculo;
use Illuminate\Http\Request;

class CurriculoController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Curriculo  $curriculo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Curriculo $curriculo)
    {
        abort_if ($request->user()->cannot('update', $curriculo), 403);

        // Actualiza el curriculo...
    }
}
```

Si existe una `policy` registrada para ese modelo, el método `can()` llamará automáticamente a la `policy` apropiada y devolverá un valor _booleano_ como resultado. Si, por el contrario, no se ha definido esa policy para el modelo, el método `can()` intentará llamar al Gate correspondiente al nombre de la acción.

#### Acciones que no requieren modelos

Recordemos que algunas acciones pueden corresponder a métodos que no requieren de una instancia previa del modelo, por ejemplo el método `create()`. En estas situaciones, podemos enviar, como parámetro, el nombre de la clase al método `can()`.

```php
$request->user()->cannot('create', Curriculo::class)
```

de la siguiente forma:

```php
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        abort_if ($request->user()->cannot('create', Curriculo::class), 403);

        // Crea el curriculo...
    }
```
### A través de `Gate`

Además de los método provistos por el modelo `User`, _Laravel_ ofrece un método `authorize`, perteneciente al _facade_ `Gate`. Este método acepta, como parámetros, el método que se pretende autorizar y la instancia o clase del modelo que interviene en la autorización.

El método `authorize` lanzará una excepción `Illuminate\Auth\Access\AuthorizationException` en el caso de que el usuario no tenga permisos para realizar la acción, que será convertida automáticamente a una respuesta _HTTP_ con un código de estado `403`:

```diff
// app/Http/Controllers/API/CurriculoController.php
 use App\Http\Resources\CurriculoResource;
 use App\Models\Curriculo;
 use Illuminate\Http\Request;
+use Illuminate\Support\Facades\Gate;
 
 class CurriculoController extends Controller
 {
@@ -47,6 +48,8 @@ public function show(Curriculo $curriculo)
      */
     public function update(Request $request, Curriculo $curriculo)
     {
+        Gate::authorize('update', $curriculo);
+
         $curriculoData = json_decode($request->getContent(), true);
         $curriculo->update($curriculoData);

```

#### Acciones que no requieren una instancia del modelo

Como se mencionó previamente, algunos métodos, como `create` no requieren una instancia del modelo. En ese caso, deberemos pasar el nombre de la clase al método `authorize`. El nombre de la clase será necesario para determinar la clase `policy` a utilizar para la autorización:

```php
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /* 
        abort_if ($request->user()->cannot('update', $curriculo), 403);
        */

        $this->authorize('create', Curriculo::class);
        
        $curriculo = json_decode($request->getContent(), true);

        $curriculo = Curriculo::create($curriculo);

        return new CurriculoResource($curriculo);
    }
```
