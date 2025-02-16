# `Gates`

Tan solo como un ejemplo, mostraremos cómo podemos autorizar la modificación de un `curriculo` en el caso de que el usuario autenticado sea el asociado a dicho `curriculo`:

Los `gates` los definiremos en el método `boot()` de `app/Providers/AppServiceProvider.php` utilizando la clase `Gate`:

```diff
 namespace App\Providers;

+use App\Models\Curriculo;
+use App\Models\User;
 use Illuminate\Auth\Notifications\ResetPassword;
+use Illuminate\Support\Facades\Gate;
 use Illuminate\Support\ServiceProvider;
 
 class AppServiceProvider extends ServiceProvider
@@ -23,5 +26,8 @@ public function boot(): void
         ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
             return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
         });
+        Gate::define('update-curriculo', function (User $user, Curriculo $curriculo) {
+        return $user->id === $curriculo->user_id;
+        });
     }
 }
```

Una vez definido, modificaremos el método `update()` del controlador `CurriculoController` para comprobar si el usuario tiene permisos antes de que pueda modificar el `curriculo`:

```diff
 use App\Http\Resources\CurriculoResource;
+use Illuminate\Support\Facades\Gate;
 
 class CurriculoController extends Controller
 {
@@ -46,8 +47,10 @@ public function show(Curriculo $curriculo)
      */
     public function update(Request $request, Curriculo $curriculo)
     {
+        abort_if (! Gate::allows('update-curriculo', $curriculo), 403);
+
         $curriculoData = json_decode($request->getContent(), true);
         $curriculo->update($curriculoData);
 
         return new CurriculoResource($curriculo);

```

> Para poder probarlo, debemos asegurarnos de que el usuario es autenticado con la petición. Para ello, debemos añadir el _middleware_`('auth:sanctum')` a la ruta. En nuestro caso ese middleware ya lo tenemos asociado al grupo de rutas v1, por lo que no será necesaria la aasociación a la ruta concreta.

Una vez protegida la ruta, podemos autenticarnos con un usuario que esté asociado a un `curriculo` y veremos como puede modificar los datos de ese `curriculo` pero no puede modificar los datos del resto de registros `curriculos`. En realidad, _React-admin_ hace una actualización **optimista** de la operación y, en un principio, muestra el registro actualizado, a la espera de la confirmación del _backend_. Si el backend devuelve el `error 403`, _React-admin_ retira la autenticación.
