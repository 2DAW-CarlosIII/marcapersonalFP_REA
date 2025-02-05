# Relaciones muchos a muchos

Las relaciones **muchos a muchos** son ligeramente más complicadas que las relaciones `hasOne` y `hasMany`. Un ejemplo de una relación **muchos a muchos** es un `user` que habla muchos `idiomas` y esos `idiomas` también son hablados por otros `users` en la aplicación. Por ejemplo, un `user` puede tener asignado el `idioma` de `Inglés` y `Francés`; sin embargo, esos `idiomas` también pueden ser asignados a otros `users` también. Entonces, un `user` habla muchos `idiomas` y un `idioma` tiene muchos `users`.

## Estructura de la tabla

Para definir esta relación, se necesitan tres tablas de base de datos: `users`, `idiomas` y `users_idiomas`. La tabla `users_idiomas` se deriva del orden alfabético de los nombres de los modelos relacionados y contiene las columnas `user_id` e `idioma_id`. Esta tabla se utiliza como tabla intermedia que vincula los `users` y `idiomas`.

Recuerde, dado que un `idioma` puede pertenecer a muchos `users`, no podemos simplemente colocar una columna `user_id` en la tabla `idiomas`. Esto significaría que un `idioma` solo podría pertenecer a un solo `user`. Para proporcionar soporte para `idiomas` que se asignan a múltiples `users`, se necesita la tabla `users_idiomas`. Podemos resumir la estructura de la tabla de la relación de la siguiente manera:

```
users
    id - integer
    name - string

idiomas
    id - integer
    name - string

users_idiomas
    user_id - integer
    idioma_id - integer
```

## Estructura del modelo

Las relaciones **muchos a muchos** se definen escribiendo un método que devuelve el resultado del método `belongsToMany()`. El método `belongsToMany()` es proporcionado por la clase base `Illuminate\Database\Eloquent\Model` que es utilizada por todos los modelos _Eloquent_ de su aplicación. Por ejemplo, definamos un método `idiomas()` en nuestro modelo `User`. El primer argumento pasado a este método es el nombre de la clase del modelo relacionado:

```php
<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
 
class User extends Model
{
    /**
     * The idiomas that belong to the user.
     */
    public function idiomas(): BelongsToMany
    {
        return $this->belongsToMany(Idioma::class);
    }
}
```

Una vez que se define la relación, puede acceder a los `idiomas` del `user` utilizando la propiedad de relación dinámica `idiomas`:

```php
use App\Models\User;
 
$user = User::find(1);
 
foreach ($user->idiomas as $idioma) {
    // ...
}
```

Como todas las relaciones también sirven como _constructores de consultas_, puede agregar más restricciones a la consulta de la relación llamando al método `idiomas` y continuando encadenando condiciones a la consulta:

```php
$idiomas = User::find(1)->idiomas()->orderBy('name')->get();
```

Para determinar el nombre de la tabla de la relación de la tabla intermedia, _Eloquent_ unirá los dos nombres de modelo relacionados en orden alfabético. Sin embargo, eres libre de anular esta convención. Puedes hacerlo pasando un segundo argumento al método `belongsToMany()`:

```php
return $this->belongsToMany(Idioma::class, 'users_idiomas');
```

Además de personalizar el nombre de la tabla intermedia, también puede personalizar los nombres de columna de las claves en la tabla pasando argumentos adicionales al método `belongsToMany()`. El tercer argumento es el nombre de la clave foránea del modelo en el que está definiendo la relación, mientras que el cuarto argumento es el nombre de la clave foránea del modelo al que se está uniendo:

```php
return $this->belongsToMany(Idioma::class, 'users_idiomas', 'user_id', 'idioma_id');
```

## Definiendo el inverso de la relación

Para definir el "inverso" de una relación **muchos a muchos**, debe definir un método en el modelo relacionado que también devuelva el resultado del método `belongsToMany()`. Para completar nuestro ejemplo de `user` / `idioma`, definamos el método `users()` en el modelo `Idioma`:

```php
<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
 
class Idioma extends Model
{
    /**
     * The users that belong to the idioma.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_idiomas');
    }
}
```

Como puede ver, la relación se define exactamente igual que su contraparte del modelo `User` con la excepción de hacer referencia al modelo `App\Models\User`. Dado que estamos reutilizando el método `belongsToMany()`, todas las opciones habituales de personalización de tabla y clave están disponibles al definir el "inverso" de las relaciones **muchos a muchos**.

## Recuperando columnas de la tabla intermedia

Como ya ha aprendido, trabajar con relaciones **muchos a muchos** requiere la presencia de una tabla intermedia. _Eloquent_ proporciona algunas formas muy útiles de interactuar con esta tabla. Por ejemplo, supongamos que nuestro modelo `User` tiene muchos modelos `Idioma` con los que está relacionado. Después de acceder a esta relación, podemos acceder a la tabla intermedia usando el atributo `pivot` en los modelos:

```php
use App\Models\User;
 
$user = User::find(1);
 
foreach ($user->idiomas as $idioma) {
    echo $idioma->pivot->created_at;
}
```
Hay que destacar que cada modelo `Idioma` que recuperamos se asigna automáticamente a un atributo `pivot`. Este atributo contiene un modelo que representa la tabla intermedia.

Por defecto, solo las claves del modelo estarán presentes en el modelo `pivot`. Si su tabla intermedia contiene atributos adicionales, debe especificarlos al definir la relación:

```php
return $this->belongsToMany(Idioma::class)->withPivot('certificado');
```

Si quisiéramos que la tabla intermedia también contenga las marcas de tiempo `created_at` y `updated_at`, podemos usar el método `withTimestamps` en la definición de la relación:

```php
return $this->belongsToMany(Idioma::class)->withTimestamps();
```

> Las tablas intermedias que utilizan las marcas de tiempo mantenidas automáticamente por _Eloquent_ requieren que ambas columnas de marca de tiempo _created_at_ y _updated_at_ estén presentes.

## Ejemplo

En el [capítulo dedicado a las relaciones Uno a Uno](./091_relaciones_unoAuno.md#ejemplo), hemos visto un ejemplo de relación Uno a Uno en el que asociábamos el `Curriculo` a cada `User`. En esta ocasión, vamos a ver un ejemplo de relación Muchos a Muchos en el que asociaremos los recursos `Idioma` asociados a cada `User`.

Para ello, en el modelo `User` definiremos el método `idiomas()` que devolverá el resultado del método `belongsToMany()`:

```php
<?php
         return $this->hasOne(Curriculo::class);
     }
 
+    /**
+     * The idiomas that belong to the user.
+     */
+    public function idiomas(): BelongsToMany
+    {
+        return $this->belongsToMany(Idioma::class, 'users_idiomas', 'user_id', 'idioma_id')->withPivot(['nivel', 'certificado']);
+    }
+
 }

```

Y en el método `toArray()` del Recurso `UserResource`, añadiremos la relación `idiomas`:

```php
public function toArray(Request $request): array
     {
         return array_merge(parent::toArray($request), [
             'curriculo' => new CurriculoResource($this->curriculo),
+            'idiomas' => IdiomaResource::collection($this->idiomas),
         ]);
     }
```
