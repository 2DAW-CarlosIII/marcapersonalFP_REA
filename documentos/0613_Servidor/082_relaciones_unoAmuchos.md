# Relaciones Uno a Muchos

Una relación **uno a muchos** se utiliza para definir relaciones donde un solo modelo es el padre de uno o más modelos hijo. Por ejemplo, una `familia_profesional` puede tener una gran cantidad de `ciclos`. Como todas las demás relaciones _Eloquent_, las relaciones **uno a muchos** se definen definiendo un método en su modelo _Eloquent_:

```php
<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
 
class FamiliaProfesional extends Model
{
    /**
     * Get the ciclos for the familia_profesional.
     */
    public function ciclos(): HasMany
    {
        return $this->hasMany(Ciclo::class);
    }
}
```

Recuerda que _Eloquent_ determinará automáticamente la columna **clave foránea** adecuada para el modelo `Ciclo`. Por convención, _Eloquent_ tomará el nombre **snake case** del modelo padre y lo sufijará con `_id`. Entonces, en este ejemplo, _Eloquent_ asumirá que la columna clave foránea en el modelo Ciclo es `familiaprofesional_id`.

Si desea sobrescribir esta convención, puede pasar un segundo argumento al método `hasMany()`:

```php
return $this->hasMany(Ciclo::class, 'familia_id');
```
o bien, añadiendo el nombre de la columna clave primaria del modelo padre:

```php
return $this->hasMany(Ciclo::class, 'familia_id', 'id');
```

Una vez que se ha definido el método de relación, podemos acceder a la colección de ciclos relacionados accediendo a la propiedad `ciclos`. Recuerda que, dado que _Eloquent_ proporciona **propiedades de relación dinámica**, podemos acceder a los métodos de relación como si estuvieran definidos como propiedades en el modelo:

```php
use App\Models\FamiliaProfesional;
 
$ciclos = FamiliaProfesional::find(1)->ciclos;
 
foreach ($ciclos as $ciclo) {
    // ...
}
```

Dado que todas las relaciones también sirven como **constructores de consultas**, también puedes agregar restricciones adicionales a las consultas de relación:

```php
$ciclo = FamiliaProfesional::find(1)->ciclos()
                    ->where('title', 'foo')
                    ->first();
```

## Definiendo la relación inversa

La relación inversa de una relación `hasMany()` es la misma relación `belongsTo()` que cimos en el [capítulo anterior](./091_relaciones_unoAuno.md#definiendo-la-relación-inversa). Por ejemplo, vamos a definir la relación inversa del método `ciclos` que definimos anteriormente en el modelo `FamiliaProfesional`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ciclo extends Model
{
    /**
     * Get the familia_profesional that owns the ciclo.
     */
    public function familiaProfesional(): BelongsTo
    {
        return $this->belongsTo(FamiliaProfesional::class, 'familia_id');
    }
}
```

> Observa que hemos pasado el nombre de la columna clave foránea `familia_id` como segundo argumento al método `belongsTo()`.

## Ejemplo

De las dos métodos explicados anteriormente para trabajar con relaciones **uno a muchos**, en esta ocasión vamos a utilizar el método `belongsTo()`. para mostrar la `FamiliaProfesional` asociada a un determinado `Ciclo`. Para ello, vamos a utilizar el método `familiaProfesional()`, definido anteriormente, en el recurso correspondiente al modelo `Ciclo`, modificando el método `toArray()` del archivo `app/Http/Resources/CicloResource.php`:

```php
     public function toArray(Request $request): array
     {
-        return parent::toArray($request);
+        return array_merge(parent::toArray($request), [
+            'familia_profesional' => $this->familiaProfesional,
+        ]);
     }
 }
```
