# Los formularios del _ePortfolio_
<!-- {% raw %} -->

> **Para el profesorado.** Este documento contiene cinco _issues_: el **principal** y cuatro **_sub-issues_**. Cada uno empieza en un encabezado `## Issue …` y termina justo antes del siguiente. Copia cada sección en su _issue_ del repositorio del grupo, con el texto del encabezado como título. Crea los cuatro _sub-issues_ desde el principal, con **Create sub-issue**, y asigna cada uno al integrante que hizo el controlador de esa tabla en 5.5. El principal lo resuelve un solo integrante, y se fusiona antes de que empiecen los demás.
>
> Todos comparten un único test, [RA5_6_ValidacionTest.php](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_6_ValidacionTest.php). Cada _issue_ ejecuta solo sus tests, con `--group`; cuando todo esté fusionado, el test completo debe pasar en `main`.

---

## Issue principal: los formularios del _ePortfolio_

En el apartado [5.6. Formularios, datos de entrada, validación y redirecciones](https://github.com/2DAW-CarlosIII/marcapersonalFP_REA/blob/master/documentos/0613_Servidor/RA5_6_formularios.md) diste a los proyectos sus formularios de alta y edición, con un _Form Request_ que los valida y una redirección con mensaje. En el _ePortfolio_ vais a hacer lo mismo con las cuatro tablas de 5.5, repartiendo el trabajo igual que entonces:

- Este _issue_ añade al _layout_ el **mensaje _flash_**, que comparten todas las páginas, y fija las **convenciones** que seguiréis todos.
- Después, cuatro _sub-issues_, uno por integrante, crean los formularios de una tabla: **evidencias**, **familias profesionales**, **ciclos formativos** y **módulos formativos**. Cada uno, el de la tabla cuyo controlador hizo en 5.5.

### Cómo organizaros

Este _issue_ lo resuelve **un solo integrante**, el que tenga asignado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_6
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. **Los _sub-issues_ no empiezan hasta entonces**: cuando se fusione, cada integrante actualiza su _fork_ (`git switch main` y `git pull upstream main`) y crea la rama de su _sub-issue_.

### Tareas

#### 1. El mensaje _flash_, en el _layout_

- Añade a `components/layout.blade.php` el bloque `@session('mensaje')` del apartado, dentro de la plantilla del grupo y justo encima de `{{ $slot }}`.
- Dale un aspecto acorde con la plantilla: sus colores y sus bordes, no los del ejemplo del apartado.
- Así, el mensaje aparece en cualquier página que use `<x-layout>`, sin tocar ninguna vista.

#### 2. Pruébalo

Todavía no hay ningún formulario que deje un mensaje. Para verlo, añade a `routes/web.php` una ruta **provisional** que redirija con uno:

| Dirección | Respuesta |
|---|---|
| `/prueba-mensaje` | Una redirección al listado de evidencias, con el mensaje `Esto es un mensaje de prueba.` |

Pide `/prueba-mensaje`: debe aparecer el listado con el mensaje. Recarga la página: el mensaje ya no está, porque solo dura una petición. Cuando funcione, **borra la ruta** antes de hacer el _commit_.

### Las convenciones de los _sub-issues_

Cada _sub-issue_ crea los formularios de una tabla. Para que el resultado sea coherente, todos siguen estas convenciones. Estos son los ejemplos de familias profesionales:

| Pieza | Convención | Ejemplo |
|---|---|---|
| Rutas | `POST /recurso` (`recurso.store`) y `PUT /recurso/edit/{id}` (`recurso.update`), con el `{id}` restringido a números, en el grupo de `routes/recurso.php` | `familias-profesionales.store` |
| _Form Request_ | El nombre del controlador, con `Request` en lugar de `Controller`, creado con _Artisan_ | `FamiliaProfesionalRequest` |
| Acciones | `store` y `update` reciben el _Form Request_, no un `Request`; `update` recibe además el `{id}` | `update(FamiliaProfesionalRequest $request, int $id)` |
| Formularios | Los de alta y edición, con `@csrf`, `old()` y `@error` en cada campo, enviados con `route()`. El de edición lleva `@method('PUT')` y los valores actuales: `old('campo', valor actual)` | |
| Listas cerradas | Los campos con la regla `in:` se eligen en un `<select>`. En la edición, marca la opción actual con la directiva [`@selected`](https://laravel.com/docs/blade#additional-attributes) | `grado` |
| Títulos | Las vistas de alta y edición conservan su título de 5.5, para que su test siga pasando | `Nueva familia profesional` |
| Enlace al alta | El listado enlaza con el formulario de alta | `route('familias-profesionales.create')` |
| Tras el alta | Redirige al listado con un mensaje | `Familia profesional «nombre» creada.` |
| Tras la edición | Responde 404 si el `id` no existe y, si existe, redirige a su detalle con un mensaje | `Familia profesional «nombre» actualizada.` |
| Mensajes de error | En español, en `messages()`: al menos el de `required` de cada campo obligatorio | `El código de la familia profesional es obligatorio.` |

Los dos formularios son casi iguales. Si quieres evitar repetirlo, sácalo a un componente, como propone el apartado. Ponle el nombre del recurso, para que no choque con el de tus compañeros: por ejemplo, `components/formulario-familia-profesional.blade.php`.

Tres cosas que aún no se pueden hacer, porque no hay base de datos:

- **Guardar.** Como en el apartado, `store` y `update` validan y redirigen con el mensaje, sin guardar nada. En el Bloque 6, guardarán los datos en la base de datos.
- **Comprobar que un código no se repite.** La regla `unique:` consulta la base de datos. Llegará en el Bloque 6.
- **Elegir la familia de un ciclo o el ciclo de un módulo en una lista.** Las claves ajenas se escriben como texto, con el código, y se valida su formato. En el Bloque 6 serán un `<select>` con los datos de la otra tabla, y se validarán con `exists:`.

**Conflictos.** Esta vez, cada _sub-issue_ solo toca sus propios ficheros: su controlador, su fichero de rutas, sus vistas y su _Form Request_. No debería haber conflictos. Si aparece alguno, resuélvelo como en 5.5.

### Comprobación automática

Copia el test [RA5_6_ValidacionTest.php](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_6_ValidacionTest.php) a la carpeta `tests/Feature/` del _ePortfolio_ y ejecuta los tests de este _issue_ en el terminal de `workspace`:

```bash
curl -o tests/Feature/RA5_6_ValidacionTest.php https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_6_ValidacionTest.php
php artisan test --filter=RA5_6_ValidacionTest --group=principal
```

El test comprueba que un mensaje guardado en la sesión aparece en dos páginas distintas, el listado de evidencias y el de familias profesionales.

Si todo está bien: `Tests: 1 passed`.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ (5.1, "Probar un _pull request_ localmente") y ejecuta el test.
2. Revisa el código:
    - ¿El bloque `@session` está en `components/layout.blade.php`, y no repetido en cada vista?
    - ¿Se ha borrado la ruta `/prueba-mensaje`?
    - ¿El aspecto del mensaje encaja con la plantilla?
3. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, actualizad todos vuestro _fork_ para empezar los _sub-issues_. Este _issue_ no se cierra todavía: queda abierto hasta que se resuelvan sus cuatro _sub-issues_.

---

## Issue 1: los formularios de las evidencias

En este _sub-issue_ das a las evidencias sus formularios de alta y edición, con su _Form Request_ y sus acciones `store` y `update`. Sigue las convenciones del _issue_ principal.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_6_evidencias
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado.

### Tareas

#### 1. Las rutas

Añade a `routes/evidencias.php` las rutas `evidencias.store` (`POST /evidencias`) y `evidencias.update` (`PUT /evidencias/edit/{id}`), con sus acciones `store` y `update` en `EvidenciaController`.

#### 2. El _Form Request_

Crea `EvidenciaRequest` con estas reglas, que siguen la tabla `evidencias` del _ePortfolio_:

| Campo | Reglas |
|---|---|
| `estudiante_id` | `required`, `integer`, `min:1` |
| `tarea_id` | `required`, `integer`, `min:1` |
| `url` | `required`, `url`, `max:2048` |
| `descripcion` | `required`, `string` |
| `estado_validacion` | `required`, `in:pendiente,validada,rechazada,asignada` |

Escribe en `messages()` los mensajes en español, al menos los de `required`. El de la regla `url` debe ser exactamente `La dirección de la evidencia no es una URL válida.`

#### 3. El formulario de alta

En `evidencias/create.blade.php`, debajo del título, crea el formulario con un campo por cada regla, con `old()` y `@error` en cada uno:

| Etiqueta | `name` | Tipo |
|---|---|---|
| Estudiante | `estudiante_id` | `number` |
| Tarea | `tarea_id` | `number` |
| Dirección | `url` | `url` |
| Descripción | `descripcion` | `<textarea>` |
| Estado de validación | `estado_validacion` | `<select>` con los cuatro estados |

- El botón de envío dice "Añadir evidencia".
- La acción `store` redirige al listado con el mensaje `Evidencia «descripción» creada.`, con la descripción de la evidencia entre las comillas.
- Añade al listado un enlace al formulario de alta.

#### 4. El formulario de edición

En `evidencias/edit.blade.php`, el mismo formulario:

- enviado a `evidencias.update` con `@method('PUT')`;
- con los valores actuales de la evidencia y su estado marcado en el `<select>`;
- con el botón "Modificar evidencia".

La acción `update` responde 404 si la evidencia no existe. Si existe, redirige a su detalle con el mensaje `Evidencia «descripción» actualizada.`

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_6_ValidacionTest --group=evidencias
```

El test comprueba:
- las rutas `store` y `update`, y su método HTTP;
- que `store` y `update` reciben `EvidenciaRequest`;
- los campos de los dos formularios, el _token_ CSRF, el botón y los valores de la evidencia al editar;
- que un alta válida redirige al listado con su mensaje, y que este aparece en la página;
- que un alta no válida vuelve al formulario con un error por cada campo incorrecto y lo escrito;
- los mensajes en español;
- que una edición válida redirige al detalle con su mensaje, y el 404 si la evidencia no existe.

Si todo está bien: `Tests: 8 passed`. Los tests de los _issues_ de 5.3 (`--filter=RA5_3_DireccionesTest`) y 5.5 (`--filter=RA5_5_RecursosTest`) también deben seguir pasando.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_ y los de los _issues_ de 5.3 y 5.5.
2. Prueba los formularios en el navegador:
    - Envía el alta vacía: cada campo debe mostrar su error en español.
    - Envíala con la descripción escrita y una dirección incorrecta: la descripción debe seguir ahí.
    - Envía un alta válida: debes llegar al listado con el mensaje. Recarga: el mensaje desaparece.
    - Edita la evidencia 4: el formulario muestra sus datos y su estado, `rechazada`.
3. Revisa el código:
    - ¿Las reglas están en el _Form Request_, y no en el controlador?
    - ¿`update` comprueba que la evidencia existe?
    - ¿Los dos formularios repiten mucho código? ¿Convendría un componente?
4. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo.

---

## Issue 2: los formularios de las familias profesionales

En este _sub-issue_ das a las familias profesionales sus formularios de alta y edición, con su _Form Request_ y sus acciones `store` y `update`. Sigue las convenciones del _issue_ principal.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_6_familias-profesionales
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado.

### Tareas

#### 1. Las rutas

Añade a `routes/familias-profesionales.php` las rutas `familias-profesionales.store` (`POST /familias-profesionales`) y `familias-profesionales.update` (`PUT /familias-profesionales/edit/{id}`), con sus acciones `store` y `update` en `FamiliaProfesionalController`.

#### 2. El _Form Request_

Crea `FamiliaProfesionalRequest` con estas reglas, que siguen la tabla `familias_profesionales` del _ePortfolio_:

| Campo | Reglas |
|---|---|
| `codigo` | `required`, `string`, `max:50`, `regex:/^[A-Z0-9_-]+$/i` |
| `nombre` | `required`, `string`, `max:255` |
| `descripcion` | `nullable`, `string` |

- La expresión regular solo admite letras, números, guiones y guiones bajos, como los códigos del _seeder_ (`ADG`, `IFC`…).
- Escribe las reglas como un array, no separadas por `|`: dentro de una expresión regular, `|` tiene otro significado.
- Escribe en `messages()` los mensajes en español, al menos los de `required`. El de `codigo` debe ser exactamente `El código de la familia profesional es obligatorio.`

#### 3. El formulario de alta

En `familias-profesionales/create.blade.php`, debajo del título, crea el formulario con un campo por cada regla, con `old()` y `@error` en cada uno:

| Etiqueta | `name` | Tipo |
|---|---|---|
| Código | `codigo` | `text` |
| Nombre | `nombre` | `text` |
| Descripción | `descripcion` | `<textarea>` |

- El botón de envío dice "Añadir familia profesional".
- La acción `store` redirige al listado con el mensaje `Familia profesional «nombre» creada.`, con el nombre de la familia entre las comillas.
- Añade al listado un enlace al formulario de alta.

#### 4. El formulario de edición

En `familias-profesionales/edit.blade.php`, el mismo formulario:

- enviado a `familias-profesionales.update` con `@method('PUT')`;
- con los valores actuales de la familia;
- con el botón "Modificar familia profesional".

La acción `update` responde 404 si la familia no existe. Si existe, redirige a su detalle con el mensaje `Familia profesional «nombre» actualizada.`

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_6_ValidacionTest --group=familias-profesionales
```

El test comprueba lo mismo que en las evidencias: las rutas, el _Form Request_, los dos formularios, el alta válida y la no válida, los mensajes en español y la edición.

Si todo está bien: `Tests: 8 passed`. El test del _issue_ de 5.5 (`--filter=RA5_5_RecursosTest`) también debe seguir pasando.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_ y el del _issue_ de 5.5.
2. Prueba los formularios en el navegador:
    - Envía el alta vacía: el código y el nombre deben mostrar su error en español, y la descripción no.
    - Envía un código con espacios: debe rechazarlo, conservando lo escrito.
    - Envía un alta válida: debes llegar al listado con el mensaje. Recarga: el mensaje desaparece.
    - Edita la última familia, la 25: el formulario muestra `VIC` y `VIDRIO Y CERÁMICA`.
3. Revisa el código:
    - ¿Las reglas están en el _Form Request_, y no en el controlador?
    - ¿`update` comprueba que la familia existe?
    - ¿Los dos formularios repiten mucho código? ¿Convendría un componente?
4. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo.

---

## Issue 3: los formularios de los ciclos formativos

En este _sub-issue_ das a los ciclos formativos sus formularios de alta y edición, con su _Form Request_ y sus acciones `store` y `update`. Sigue las convenciones del _issue_ principal.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_6_ciclos-formativos
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado.

### Tareas

#### 1. Las rutas

Añade a `routes/ciclos-formativos.php` las rutas `ciclos-formativos.store` (`POST /ciclos-formativos`) y `ciclos-formativos.update` (`PUT /ciclos-formativos/edit/{id}`), con sus acciones `store` y `update` en `CicloFormativoController`.

#### 2. El _Form Request_

Crea `CicloFormativoRequest` con estas reglas, que siguen la tabla `ciclos_formativos` del _ePortfolio_:

| Campo | Reglas |
|---|---|
| `codigo_familia` | `required`, `string`, `max:50`, `regex:/^[A-Z0-9_-]+$/i` |
| `grado` | `required`, `in:basico,medio,superior` |
| `codigo` | `required`, `string`, `max:50`, `regex:/^[A-Z0-9_-]+$/i` |
| `nombre` | `required`, `string`, `max:255` |
| `descripcion` | `nullable`, `string` |

- `codigo_familia` es la clave ajena: el código de la familia profesional, con el mismo formato que los códigos del _seeder_.
- Escribe las reglas como un array, no separadas por `|`: dentro de una expresión regular, `|` tiene otro significado.
- Escribe en `messages()` los mensajes en español, al menos los de `required`. El de la regla `in` de `grado` debe ser exactamente `El grado debe ser básico, medio o superior.`

#### 3. El formulario de alta

En `ciclos-formativos/create.blade.php`, debajo del título, crea el formulario con un campo por cada regla, con `old()` y `@error` en cada uno:

| Etiqueta | `name` | Tipo |
|---|---|---|
| Familia profesional | `codigo_familia` | `text`, con el código de la familia |
| Grado | `grado` | `<select>` con los valores `basico`, `medio` y `superior`, y los textos "Básico", "Medio" y "Superior" |
| Código | `codigo` | `text` |
| Nombre | `nombre` | `text` |
| Descripción | `descripcion` | `<textarea>` |

- El botón de envío dice "Añadir ciclo formativo".
- La acción `store` redirige al listado con el mensaje `Ciclo formativo «nombre» creado.`, con el nombre del ciclo entre las comillas.
- Añade al listado un enlace al formulario de alta.

#### 4. El formulario de edición

En `ciclos-formativos/edit.blade.php`, el mismo formulario:

- enviado a `ciclos-formativos.update` con `@method('PUT')`;
- con los valores actuales del ciclo y su grado marcado en el `<select>`;
- con el botón "Modificar ciclo formativo".

La acción `update` responde 404 si el ciclo no existe. Si existe, redirige a su detalle con el mensaje `Ciclo formativo «nombre» actualizado.`

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_6_ValidacionTest --group=ciclos-formativos
```

El test comprueba lo mismo que en las evidencias: las rutas, el _Form Request_, los dos formularios (con las tres opciones del grado), el alta válida y la no válida, los mensajes en español y la edición.

Si todo está bien: `Tests: 8 passed`. El test del _issue_ de 5.5 (`--filter=RA5_5_RecursosTest`) también debe seguir pasando.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_ y el del _issue_ de 5.5.
2. Prueba los formularios en el navegador:
    - Envía el alta vacía: cada campo obligatorio debe mostrar su error en español.
    - Con las herramientas de desarrollo, cambia el `value` de una opción del grado por `master` y envía el formulario: debe rechazarlo con el mensaje del grado.
    - Envía un alta válida: debes llegar al listado con el mensaje. Recarga: el mensaje desaparece.
    - Edita un ciclo de grado superior: el `<select>` debe mostrar "Superior".
3. Revisa el código:
    - ¿Las reglas están en el _Form Request_, y no en el controlador?
    - ¿`update` comprueba que el ciclo existe?
    - ¿Los dos formularios repiten mucho código? ¿Convendría un componente?
4. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo.

---

## Issue 4: los formularios de los módulos formativos

En este _sub-issue_ das a los módulos formativos sus formularios de alta y edición, con su _Form Request_ y sus acciones `store` y `update`. Sigue las convenciones del _issue_ principal.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_6_modulos-formativos
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado.

### Tareas

#### 1. Las rutas

Añade a `routes/modulos-formativos.php` las rutas `modulos-formativos.store` (`POST /modulos-formativos`) y `modulos-formativos.update` (`PUT /modulos-formativos/edit/{id}`), con sus acciones `store` y `update` en `ModuloFormativoController`.

#### 2. El _Form Request_

Crea `ModuloFormativoRequest` con estas reglas, que siguen la tabla `modulos_formativos` del _ePortfolio_:

| Campo | Reglas |
|---|---|
| `codigo_ciclo` | `required`, `string`, `max:50`, `regex:/^[A-Z0-9_-]+$/i` |
| `nombre` | `required`, `string`, `max:255` |
| `codigo` | `required`, `string`, `max:50`, `regex:/^[A-Z0-9_-]+$/i` |
| `horas_totales` | `required`, `integer`, `min:1`, `max:2000` |
| `curso_escolar` | `nullable`, `in:PRIMERO,SEGUNDO` |
| `centro` | `nullable`, `string`, `max:255` |
| `descripcion` | `nullable`, `string` |

- `codigo_ciclo` es la clave ajena: el código del ciclo formativo, con el mismo formato que los códigos del _seeder_.
- Escribe las reglas como un array, no separadas por `|`: dentro de una expresión regular, `|` tiene otro significado.
- Escribe en `messages()` los mensajes en español, al menos los de `required`. El de la regla `integer` de `horas_totales` debe ser exactamente `Las horas totales deben ser un número entero.`

#### 3. El formulario de alta

En `modulos-formativos/create.blade.php`, debajo del título, crea el formulario con un campo por cada regla, con `old()` y `@error` en cada uno:

| Etiqueta | `name` | Tipo |
|---|---|---|
| Ciclo formativo | `codigo_ciclo` | `text`, con el código del ciclo |
| Nombre | `nombre` | `text` |
| Código | `codigo` | `text` |
| Horas totales | `horas_totales` | `number` |
| Curso | `curso_escolar` | `<select>` con una opción vacía y los valores `PRIMERO` y `SEGUNDO` |
| Centro | `centro` | `text` |
| Descripción | `descripcion` | `<textarea>` |

- En el alta, el campo `centro` propone el centro de la configuración del _ePortfolio_ (_issue_ de 5.2): léelo con `config()`, no lo escribas a mano.
- El botón de envío dice "Añadir módulo formativo".
- La acción `store` redirige al listado con el mensaje `Módulo formativo «nombre» creado.`, con el nombre del módulo entre las comillas.
- Añade al listado un enlace al formulario de alta.

#### 4. El formulario de edición

En `modulos-formativos/edit.blade.php`, el mismo formulario:

- enviado a `modulos-formativos.update` con `@method('PUT')`;
- con los valores actuales del módulo y su curso marcado en el `<select>`;
- con el botón "Modificar módulo formativo".

La acción `update` responde 404 si el módulo no existe. Si existe, redirige a su detalle con el mensaje `Módulo formativo «nombre» actualizado.`

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_6_ValidacionTest --group=modulos-formativos
```

El test comprueba lo mismo que en las evidencias: las rutas, el _Form Request_, los dos formularios (con las opciones del curso), el alta válida y la no válida, los mensajes en español y la edición. Además, comprueba que el centro del alta sale de la configuración.

Si todo está bien: `Tests: 9 passed`. El test del _issue_ de 5.5 (`--filter=RA5_5_RecursosTest`) también debe seguir pasando.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_ y el del _issue_ de 5.5.
2. Prueba los formularios en el navegador:
    - Abre el alta: el centro debe estar ya escrito, el de la configuración.
    - Envía el alta vacía: cada campo obligatorio debe mostrar su error en español, y el curso, el centro y la descripción no.
    - Escribe `-5` en las horas: debe rechazarlo, conservando lo demás.
    - Envía un alta válida: debes llegar al listado con el mensaje. Recarga: el mensaje desaparece.
    - Edita un módulo de segundo curso: el `<select>` debe mostrar `SEGUNDO`.
3. Revisa el código:
    - ¿Las reglas están en el _Form Request_, y no en el controlador?
    - ¿El centro del alta sale de `config()`?
    - ¿`update` comprueba que el módulo existe?
    - ¿Los dos formularios repiten mucho código? ¿Convendría un componente?
4. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo. Cuando lo estén los cuatro, cerrad también el _issue_ principal y comprobad en `main` que el test completo pasa:

```bash
php artisan test --filter=RA5_6_ValidacionTest
```

Si todo está bien: `Tests: 34 passed`.
<!-- {% endraw %} -->
