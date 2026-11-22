# Los controladores del _ePortfolio_
<!-- {% raw %} -->

> **Para el profesorado.** Este documento contiene cinco _issues_: el **principal** y cuatro **_sub-issues_**. Cada uno empieza en un encabezado `## Issue …` y termina justo antes del siguiente. Copia cada sección en su _issue_ del repositorio del grupo, con el texto del encabezado como título. Crea los cuatro _sub-issues_ desde el principal, con **Create sub-issue**, y asigna cada uno a un integrante. El principal lo resuelve un solo integrante, y se fusiona antes de que empiecen los demás.
>
> Todos comparten un único test, [RA5_5_RecursosTest.php](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_5_RecursosTest.php). Cada _issue_ ejecuta solo sus tests, con `--group`; cuando todo esté fusionado, el test completo debe pasar en `main`.

---

## Issue principal: los controladores del _ePortfolio_

En el apartado [5.5. Controladores](https://github.com/2DAW-CarlosIII/marcapersonalFP_REA/blob/master/documentos/0613_Servidor/RA5_5_controladores.md) sacaste de `routes/web.php` la lógica de los proyectos a un controlador, y la portada y el perfil a dos controladores invocables. En el _ePortfolio_ vais a hacer lo mismo, pero repartiendo el trabajo:

- Este _issue_ pasa la **portada** y el **perfil** a controladores invocables, y fija las **convenciones** que seguiréis todos.
- Después, cuatro _sub-issues_, uno por integrante, crean el controlador de una tabla del [diagrama entidad-relación](https://github.com/2DAW-CarlosIII/ePortfolio/blob/master/docs/eportfolio_er_diagram.mermaid): **evidencias**, **familias profesionales**, **ciclos formativos** y **módulos formativos**.

### Cómo organizaros

Este _issue_ lo resuelve **un solo integrante**, el que tenga asignado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_5
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. **Los _sub-issues_ no empiezan hasta entonces**: cuando se fusione, cada integrante actualiza su _fork_ (`git switch main` y `git pull upstream main`) y crea la rama de su _sub-issue_.

### Tareas

#### 1. La portada, con un controlador invocable

- Crea el controlador invocable `HomeController` con _Artisan_.
- Su método `__invoke()` devuelve lo mismo que devuelve ahora la ruta `/`.
- La ruta `/` pasa a apuntar al controlador, sin nombre de método.

#### 2. El perfil, con un controlador invocable

- Crea el controlador invocable `PerfilController`.
- Recibe el `{id}` opcional de la ruta y devuelve lo mismo que ahora: `Perfil del usuario {id}`, o `Mi perfil` si no hay `{id}`.
- La ruta `/perfil/{id?}` mantiene su nombre (`perfil`) y su restricción, y apunta al controlador.

#### 3. Comprueba tus rutas

Ejecuta `php artisan route:list --except-vendor`. En la columna de la acción, la portada y el perfil deben mostrar sus controladores.

### Las convenciones de los _sub-issues_

Cada _sub-issue_ crea el controlador de una tabla. Para que el resultado sea coherente, todos siguen estas convenciones. Estos son los ejemplos de familias profesionales:

| Pieza | Convención | Ejemplo |
|---|---|---|
| Controlador | El nombre de la tabla, en singular, con el sufijo `Controller` | `FamiliaProfesionalController` |
| Acciones | `index`, `show`, `create` y `edit`, como en 5.5 | |
| Direcciones | `/recurso`, `/recurso/show/{id}`, `/recurso/create` y `/recurso/edit/{id}`, con los `{id}` restringidos a números | `/familias-profesionales/show/{id}` |
| Nombres de ruta | `recurso.index`, `recurso.show`, `recurso.create` y `recurso.edit` | `familias-profesionales.index` |
| Fichero de rutas | `routes/recurso.php`, con un grupo `Route::controller()`, prefijo de dirección y prefijo de nombre, incluido desde `routes/web.php` con `require` | `routes/familias-profesionales.php` |
| Vistas | Una carpeta por recurso, con `index`, `show`, `create` y `edit`, todas con `<x-layout>` | `resources/views/familias-profesionales/index.blade.php` |
| Datos | Un array en una propiedad privada del controlador, hasta el Bloque 6. El `id` de cada elemento es su **posición** en el array, desde 0 | `private array $familiasProfesionales` |
| No encontrado | `show` y `edit` responden 404 si el `id` no existe en el array | `abort_unless(…, 404)` |

Las vistas de alta y edición solo llevan, de momento, su título: los formularios llegarán en 5.6.

En el detalle, las **claves ajenas** se muestran tal como están en los datos, sin buscar nada en otra tabla: por ejemplo, el código de la familia de un ciclo. En el Bloque 6, cuando los datos estén en la base de datos, se convertirán en relaciones.

**Conflictos.** Cuatro _pull requests_ que añaden cada uno una línea `require` a `routes/web.php` y un enlace a la barra de navegación **chocarán** al fusionarse. Es normal. Cuando _GitHub_ avise de un conflicto en tu _pull request_, resuélvelo en tu rama, conservando las líneas de todos:

```bash
git switch RA5_5_recurso
git pull upstream main
```

_Git_ marcará las líneas en conflicto en los ficheros afectados. Edítalos, deja las de todos, y termina con `git add`, `git commit` y `git push`.

### Comprobación automática

Copia el test [RA5_5_RecursosTest.php](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_5_RecursosTest.php) a la carpeta `tests/Feature/` del _ePortfolio_ y ejecuta los tests de este _issue_ en el terminal de `workspace`:

```bash
curl -o tests/Feature/RA5_5_RecursosTest.php https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_5_RecursosTest.php
php artisan test --filter=RA5_5_RecursosTest --group=principal
```

El test comprueba que la portada y el perfil responden, y que sus rutas apuntan a `HomeController` y a `PerfilController`.

Si todo está bien: `Tests: 2 passed`.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ (5.1, "Probar un _pull request_ localmente") y ejecuta el test.
2. Ejecuta `php artisan route:list --except-vendor` y comprueba la acción de la portada y la del perfil.
3. Pide `/`, `/perfil` y `/perfil/3`: deben responder igual que antes.
4. Revisa el código: ¿los dos controladores son invocables, con un único método `__invoke()`? ¿Queda en `routes/web.php` alguna función que debería estar en un controlador?
5. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, actualizad todos vuestro _fork_ para empezar los _sub-issues_. Este _issue_ no se cierra todavía: queda abierto hasta que se resuelvan sus cuatro _sub-issues_.

---

## Issue 1: el controlador de las evidencias

Las evidencias ya tienen sus rutas (_issue_ de 5.3) y sus vistas (5.4). En este _sub-issue_ pasan a un controlador, `EvidenciaController`, y sus vistas muestran por fin datos de verdad. Sigue las convenciones del _issue_ principal.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_5_evidencias
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. Lee en el _issue_ principal qué hacer si aparece un conflicto.

### Tareas

#### 1. El controlador y sus datos

Crea `EvidenciaController` con las acciones `index`, `show`, `create` y `edit`. Sus datos son estas ocho evidencias, en la propiedad privada `$evidencias`. Cada una sigue los campos de la tabla `evidencias` del diagrama:

```php
private array $evidencias = [
    ['estudiante_id' => 1, 'tarea_id' => 1, 'url' => 'https://example.com/evidencias/1',
     'descripcion' => 'Instalación de Laravel y desarrollo colaborativo con GitHub', 'estado_validacion' => 'validada'],
    ['estudiante_id' => 1, 'tarea_id' => 2, 'url' => 'https://example.com/evidencias/2',
     'descripcion' => 'Configuración del proyecto y comando propio de Artisan', 'estado_validacion' => 'validada'],
    ['estudiante_id' => 1, 'tarea_id' => 3, 'url' => 'https://example.com/evidencias/3',
     'descripcion' => 'Rutas con nombre, grupos y restricciones', 'estado_validacion' => 'pendiente'],
    ['estudiante_id' => 2, 'tarea_id' => 1, 'url' => 'https://example.com/evidencias/4',
     'descripcion' => 'Instalación de Laravel en el ePortfolio del grupo', 'estado_validacion' => 'validada'],
    ['estudiante_id' => 2, 'tarea_id' => 2, 'url' => 'https://example.com/evidencias/5',
     'descripcion' => 'Fichero de configuración propio', 'estado_validacion' => 'rechazada'],
    ['estudiante_id' => 3, 'tarea_id' => 1, 'url' => 'https://example.com/evidencias/6',
     'descripcion' => 'Pull request revisado por un compañero', 'estado_validacion' => 'asignada'],
    ['estudiante_id' => 3, 'tarea_id' => 3, 'url' => 'https://example.com/evidencias/7',
     'descripcion' => 'Respuesta en JSON con la configuración', 'estado_validacion' => 'pendiente'],
    ['estudiante_id' => 4, 'tarea_id' => 2, 'url' => 'https://example.com/evidencias/8',
     'descripcion' => 'Layout con la plantilla del grupo', 'estado_validacion' => 'validada'],
];
```

`estado_validacion` solo puede valer `pendiente`, `validada`, `rechazada` o `asignada`, como en la tabla.

#### 2. Las rutas, a su fichero

Mueve el grupo de rutas de las evidencias del _issue_ de 5.3 a `routes/evidencias.php`. Ahora es un grupo con `Route::controller()`, e inclúyelo desde `routes/web.php`. Las direcciones, los nombres y las restricciones no cambian.

#### 3. Las vistas, con datos

Mantén el título de cada vista (`Listado de evidencias`, `Evidencia {id}`, `Nueva evidencia` y `Modificar evidencia {id}`), para que el test del _issue_ de 5.3 siga pasando:

- **El listado** muestra cada evidencia, con su descripción y su estado de validación, y un enlace a su detalle. Usa un componente para cada evidencia, como la tarjeta de 5.4, y distingue cada estado con un color diferente (`@class`).
- **El detalle** muestra todos sus campos: el estudiante y la tarea (sus `id`), la `url` como enlace, la descripción y el estado. Añade un enlace para editarla y otro para volver al listado.
- **El alta y la edición**, de momento, solo su título.

#### 4. La barra de navegación

Añade a la barra de navegación del grupo un enlace al listado de evidencias, con `route()`.

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_5_RecursosTest --group=evidencias
```

Si todo está bien: `Tests: 6 passed`. El test del _issue_ de 5.3 (`--filter=RA5_3_DireccionesTest`) también debe seguir pasando.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_ y el test del _issue_ de 5.3.
2. Recorre el listado, el detalle de varias evidencias y la edición. Pide `/evidencias/show/8` y `/evidencias/show/abc`: los dos deben dar 404.
3. Revisa el código:
    - ¿Queda en `routes/web.php` alguna ruta de las evidencias, aparte del `require`?
    - ¿El controlador comprueba que la evidencia existe antes de usarla?
    - ¿El listado usa un componente para cada evidencia, y `@class` para el estado?
4. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo.

---

## Issue 2: el controlador de las familias profesionales

En este _sub-issue_ creas el controlador de la tabla `familias_profesionales` del diagrama, con sus rutas y sus vistas. Sigue las convenciones del _issue_ principal.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_5_familias-profesionales
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. Lee en el _issue_ principal qué hacer si aparece un conflicto.

### Tareas

#### 1. El controlador y sus datos

Crea `FamiliaProfesionalController` con las acciones `index`, `show`, `create` y `edit`.

Sus datos son las 26 familias profesionales de la Formación Profesional, que el _ePortfolio_ ya tiene preparadas para la base de datos en su [`FamiliaProfesionalSeeder.php`](https://github.com/2DAW-CarlosIII/ePortfolio/blob/master/database/seeders/FamiliaProfesionalSeeder.php). Copia el array `$familias_profesionales` de ese fichero a la propiedad privada `$familiasProfesionales` de tu controlador. Cada familia tiene `codigo`, `nombre` y `descripcion`.

El array del _seeder_ usa la sintaxis `array( … )`, que es equivalente a `[ … ]`: puedes dejarla tal cual. En el Bloque 6, ese mismo _seeder_ llevará los datos a la base de datos.

#### 2. Las rutas, en su fichero

Crea las rutas en `routes/familias-profesionales.php`, siguiendo las convenciones, e inclúyelo desde `routes/web.php`:

| Dirección | Nombre | Acción |
|---|---|---|
| `/familias-profesionales` | `familias-profesionales.index` | `index` |
| `/familias-profesionales/show/{id}` | `familias-profesionales.show` | `show` |
| `/familias-profesionales/create` | `familias-profesionales.create` | `create` |
| `/familias-profesionales/edit/{id}` | `familias-profesionales.edit` | `edit` |

#### 3. Las vistas

En `resources/views/familias-profesionales/`:

- **El listado**, titulado `Familias profesionales`, muestra cada familia con su código y su nombre, y un enlace a su detalle. Usa un componente para cada familia.
- **El detalle** muestra el código, el nombre y la descripción, con un enlace para editarla y otro para volver al listado.
- **El alta** (`Nueva familia profesional`) y **la edición** (`Modificar familia profesional {id}`), de momento, solo su título.

#### 4. La barra de navegación

Añade a la barra de navegación del grupo un enlace al listado de familias profesionales, con `route()`.

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_5_RecursosTest --group=familias-profesionales
```

Si todo está bien: `Tests: 6 passed`.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_.
2. Recorre el listado y el detalle de varias familias. Pide `/familias-profesionales/show/26` y `/familias-profesionales/show/abc`: los dos deben dar 404, porque los `id` van del 0 al 25.
3. Revisa el código:
    - ¿Los datos son exactamente los del _seeder_, sin familias de menos ni de más?
    - ¿El controlador comprueba que la familia existe antes de usarla?
    - ¿Las rutas están en su fichero, con `Route::controller()`?
4. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo.

---

## Issue 3: el controlador de los ciclos formativos

En este _sub-issue_ creas el controlador de la tabla `ciclos_formativos` del diagrama, con sus rutas y sus vistas. Sigue las convenciones del _issue_ principal.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_5_ciclos-formativos
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. Lee en el _issue_ principal qué hacer si aparece un conflicto.

### Tareas

#### 1. El controlador y sus datos

Crea `CicloFormativoController` con las acciones `index`, `show`, `create` y `edit`.

Sus datos son los ciclos formativos que el _ePortfolio_ ya tiene preparados para la base de datos en su [`CicloFormativoSeeder.php`](https://github.com/2DAW-CarlosIII/ePortfolio/blob/master/database/seeders/CicloFormativoSeeder.php). Copia el array `$ciclos_formativos` de ese fichero a la propiedad privada `$ciclosFormativos` de tu controlador. Cada ciclo tiene:

| Campo | Contenido |
|---|---|
| `codigo_familia` | El código de su familia profesional (por ejemplo, `IFC`): la clave ajena, de momento como código |
| `grado` | `basico`, `medio` o `superior` |
| `codigo` | El código del ciclo (por ejemplo, `DAPW3`) |
| `nombre` | El título |
| `descripcion` | Una descripción |

El array del _seeder_ usa la sintaxis `array( … )`, que es equivalente a `[ … ]`: puedes dejarla tal cual. Son unos doscientos ciclos.

#### 2. Las rutas, en su fichero

Crea las rutas en `routes/ciclos-formativos.php`, siguiendo las convenciones, e inclúyelo desde `routes/web.php`:

| Dirección | Nombre | Acción |
|---|---|---|
| `/ciclos-formativos` | `ciclos-formativos.index` | `index` |
| `/ciclos-formativos/show/{id}` | `ciclos-formativos.show` | `show` |
| `/ciclos-formativos/create` | `ciclos-formativos.create` | `create` |
| `/ciclos-formativos/edit/{id}` | `ciclos-formativos.edit` | `edit` |

#### 3. Las vistas

En `resources/views/ciclos-formativos/`:

- **El listado**, titulado `Ciclos formativos`, muestra cada ciclo con su código, su nombre y su grado, y un enlace a su detalle. Usa un componente para cada ciclo, y distingue cada grado con un color diferente (`@class`).
- **El detalle** muestra todos sus campos, incluido el código de su familia profesional, con un enlace para editarlo y otro para volver al listado.
- **El alta** (`Nuevo ciclo formativo`) y **la edición** (`Modificar ciclo formativo {id}`), de momento, solo su título.

#### 4. La barra de navegación

Añade a la barra de navegación del grupo un enlace al listado de ciclos formativos, con `route()`.

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_5_RecursosTest --group=ciclos-formativos
```

Si todo está bien: `Tests: 6 passed`.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_.
2. Recorre el listado y el detalle de varios ciclos, de los tres grados. Pide `/ciclos-formativos/show/9999` y `/ciclos-formativos/show/abc`: los dos deben dar 404.
3. Revisa el código:
    - ¿Los datos son exactamente los del _seeder_?
    - ¿El detalle muestra el código de la familia profesional?
    - ¿El controlador comprueba que el ciclo existe antes de usarlo?
4. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo.

---

## Issue 4: el controlador de los módulos formativos

En este _sub-issue_ creas el controlador de la tabla `modulos_formativos` del diagrama, con sus rutas y sus vistas. Sigue las convenciones del _issue_ principal.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_5_modulos-formativos
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. Lee en el _issue_ principal qué hacer si aparece un conflicto.

### Tareas

#### 1. El controlador y sus datos

Crea `ModuloFormativoController` con las acciones `index`, `show`, `create` y `edit`.

Sus datos son los módulos formativos que el _ePortfolio_ ya tiene preparados para la base de datos en su [`ModuloFormativoSeeder.php`](https://github.com/2DAW-CarlosIII/ePortfolio/blob/master/database/seeders/ModuloFormativoSeeder.php). Copia el array `$modulos_formativos` de ese fichero a la propiedad privada `$modulosFormativos` de tu controlador. Cada módulo tiene:

| Campo | Contenido |
|---|---|
| `codigo_ciclo` | El código de su ciclo formativo (por ejemplo, `DAPW3`): la clave ajena, de momento como código |
| `nombre` | El nombre del módulo |
| `codigo` | El código del módulo (por ejemplo, `0613`) |
| `horas_totales` | Las horas del módulo |
| `curso_escolar` | `PRIMERO` o `SEGUNDO` |
| `centro` | El centro donde se imparte |
| `descripcion` | Una descripción |

El array del _seeder_ usa la sintaxis `array( … )`, que es equivalente a `[ … ]`: puedes dejarla tal cual.

#### 2. Las rutas, en su fichero

Crea las rutas en `routes/modulos-formativos.php`, siguiendo las convenciones, e inclúyelo desde `routes/web.php`:

| Dirección | Nombre | Acción |
|---|---|---|
| `/modulos-formativos` | `modulos-formativos.index` | `index` |
| `/modulos-formativos/show/{id}` | `modulos-formativos.show` | `show` |
| `/modulos-formativos/create` | `modulos-formativos.create` | `create` |
| `/modulos-formativos/edit/{id}` | `modulos-formativos.edit` | `edit` |

#### 3. Las vistas

En `resources/views/modulos-formativos/`:

- **El listado**, titulado `Módulos formativos`, muestra cada módulo con su código, su nombre y el código de su ciclo, y un enlace a su detalle. Usa un componente para cada módulo.
- **El detalle** muestra todos sus campos, incluido el código de su ciclo formativo, con un enlace para editarlo y otro para volver al listado.
- **El alta** (`Nuevo módulo formativo`) y **la edición** (`Modificar módulo formativo {id}`), de momento, solo su título.

#### 4. La barra de navegación

Añade a la barra de navegación del grupo un enlace al listado de módulos formativos, con `route()`.

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_5_RecursosTest --group=modulos-formativos
```

Si todo está bien: `Tests: 6 passed`.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_.
2. Recorre el listado y el detalle de varios módulos. Pide `/modulos-formativos/show/9999` y `/modulos-formativos/show/abc`: los dos deben dar 404.
3. Revisa el código:
    - ¿Los datos son exactamente los del _seeder_?
    - ¿El detalle muestra el código del ciclo formativo?
    - ¿El controlador comprueba que el módulo existe antes de usarlo?
4. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo. Cuando lo estén los cuatro, cerrad también el _issue_ principal y comprobad en `main` que el test completo pasa:

```bash
php artisan test --filter=RA5_5_RecursosTest
```

Si todo está bien: `Tests: 26 passed`.
<!-- {% endraw %} -->
