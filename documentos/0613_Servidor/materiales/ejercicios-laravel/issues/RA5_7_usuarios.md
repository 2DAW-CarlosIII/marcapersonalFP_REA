# Los usuarios del _ePortfolio_
<!-- {% raw %} -->

> **Para el profesorado.** Este documento contiene cinco _issues_: el **principal** y cuatro **_sub-issues_**. Cada uno empieza en un encabezado `## Issue …` y termina justo antes del siguiente. Copia cada sección en su _issue_ del repositorio del grupo, con el texto del encabezado como título. Crea los cuatro _sub-issues_ desde el principal, con **Create sub-issue**, y asigna cada uno al integrante que hizo esa tabla en 5.5 y 5.6.
>
> A diferencia de 5.5 y 5.6, **el principal lo hacen todos los integrantes**, como los _issues_ de 5.2 a 5.4: instalar _Breeze_ y recuperar lo que sobrescribe es una práctica que conviene que haga cada uno. El grupo fusiona uno y cierra los demás, y solo entonces empiezan los _sub-issues_.
>
> Todos comparten un único test, [RA5_7_UsuariosTest.php](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_7_UsuariosTest.php). Cada _issue_ ejecuta solo sus tests, con `--group`; cuando todo esté fusionado, el test completo debe pasar en `main`.

---

## Issue principal: _Breeze_ en el _ePortfolio_

En el apartado [5.7. _Middleware_ y control de usuarios con _Breeze_](https://github.com/2DAW-CarlosIII/marcapersonalFP_REA/blob/master/documentos/0613_Servidor/RA5_7_breeze.md) creaste un _middleware_ propio, instalaste _Breeze_ y protegiste con `auth` el alta y la edición de los proyectos. En el _ePortfolio_ lo haréis en dos pasos:

- Este _issue_ instala _Breeze_, recupera lo que sobrescribe, añade un _middleware_ propio y pone en la barra de navegación los enlaces para entrar y salir.
- Después, cuatro _sub-issues_, uno por integrante, protegen el alta y la edición de una tabla: **evidencias**, **familias profesionales**, **ciclos formativos** y **módulos formativos**.

### Cómo organizaros

**Cada integrante del grupo hace todas las tareas** de este _issue_ en su _fork_, y abre su propio _pull request_ hacia el repositorio del grupo. Antes de empezar, crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_7
```

Cada _pull request_ lo revisa otro integrante del grupo, y cada uno revisa el de un compañero. Repartid las revisiones para que nadie se quede sin revisor.

**Los _sub-issues_ no empiezan hasta que se fusione uno.** Cuando todos los _pull requests_ estén aprobados, el grupo elige uno y lo fusiona en `main`; los demás se cierran sin fusionar. Después, cada integrante actualiza su _fork_ (`git switch main` y `git pull upstream main`) y crea la rama de su _sub-issue_.

### Tareas

#### 1. Un _commit_ antes de instalar

`breeze:install` sobrescribe varios ficheros del proyecto. Antes de instalarlo, comprueba con `git status` que no tienes nada sin guardar. Así, `git status` y `git diff` te mostrarán después exactamente qué ha cambiado _Breeze_, y podrás recuperar lo tuyo.

#### 2. Instala _Breeze_

Instálalo como en el apartado, con la pila _Blade with Alpine_, sin modo oscuro y con _PHPUnit_. `php artisan migrate` no debería crear nada: la tabla `users` existe desde que se creó el proyecto.

Ejecuta `git status`:

- Los ficheros marcados como nuevos son de _Breeze_.
- Los marcados como modificados eran tuyos y _Breeze_ los ha cambiado. En los pasos siguientes recuperas los que hacen falta.

Si _Breeze_ ha sobrescrito algún componente vuestro de `components/` con el mismo nombre que uno suyo (por ejemplo, `dropdown.blade.php`), os hacen falta los dos. Recupera el tuyo con otro nombre y cambia las vistas que lo usan.

#### 3. Seguid con _Tailwind_ 4

Aquí el _ePortfolio_ **se separa del apartado**:

- _Breeze_ devuelve el proyecto a _Tailwind_ 3, y en el apartado llevamos el tema de la plantilla a `tailwind.config.js`.
- La plantilla de vuestro grupo está montada sobre _Tailwind_ 4: su bloque `@theme` de `resources/css/app.css`, las clases que cambiasteis en 5.4 y, en algunas plantillas, sus `@utility`.

Por eso, en lugar de pasar la plantilla a _Tailwind_ 3, se recuperan los ficheros de antes de instalar _Breeze_:

| Paso | Cómo |
|---|---|
| Recupera los estilos, los _scripts_ y las dependencias | `git restore resources/css/app.css resources/js/app.js vite.config.js package.json`, y también `package-lock.json` si está en el repositorio |
| Borra la configuración de _Tailwind_ 3 | `rm tailwind.config.js postcss.config.js` |
| Instala el plugin de formularios, que usan las vistas de _Breeze_ | `npm install -D @tailwindcss/forms` |
| Actívalo en `resources/css/app.css`, debajo del `@import` de _Tailwind_ | `@plugin "@tailwindcss/forms";` |
| Vuelve a compilar | `npm install` y `npm run build` |

Si tenías `npm run build -- --watch` en otro terminal, detenlo y vuelve a lanzarlo.

- Comprueba que la plantilla conserva sus colores, sus fuentes y sus menús.
- Abre `/login`: las vistas de _Breeze_ usan clases de _Tailwind_ 3, pero casi todas siguen existiendo en _Tailwind_ 4. Pueden verse algo distintas (por ejemplo, alguna sombra), y no importa.

El plugin de formularios da a todos los `<input>`, `<select>` y `<textarea>` un aspecto base, también a los de vuestros formularios de 5.6. Revisad que sigan viéndose bien.

#### 4. Recupera las rutas

_Breeze_ ha sustituido `routes/web.php` por el suyo. Compáralo con el que teníais, con `git diff routes/web.php`, y deja en el fichero las dos cosas:

- **Las vuestras:** la portada, el perfil, `/acerca` y los `require` de los ficheros de rutas de las cuatro tablas.
- **Las de _Breeze_:** `/dashboard`, el grupo de `/profile` y el `require` de `routes/auth.php`. No conserves su ruta `/`, que mostraba la página de bienvenida.

Comprueba el resultado con `php artisan route:list --except-vendor`.

#### 5. El logo

Las vistas de _Breeze_ (_login_, registro, panel…) usan sus propios _layouts_, y no la plantilla del grupo. Déjalos así, pero cambia `components/application-logo.blade.php` para que muestre el logo de vuestra plantilla, enlazado con `asset()`, o el nombre del _ePortfolio_ si la plantilla no tiene logo.

#### 6. Un _middleware_ propio

Crea con _Artisan_ el _middleware_ `CabeceraCentro`, como en el apartado:

- Añade a cada respuesta la cabecera `X-Centro`, con el centro de la configuración del _ePortfolio_ (_issue_ de 5.2): `config('eportfolio.centro')`.
- Aplícalo a todo el grupo `web` en `bootstrap/app.php`.

#### 7. La barra de navegación

Añade a la barra de navegación del grupo, donde mejor encaje en la plantilla:

- para los invitados (`@guest`), los enlaces "Entrar" y "Registrarse";
- para los usuarios identificados (`@auth`), su nombre y el botón "Salir", en un formulario `POST` con `@csrf`, como en el apartado.

Si la plantilla tiene un menú distinto para el móvil, añádelos también ahí.

#### 8. Pruébalo

- En `http://eportfolio.test/register`, regístrate con tu nombre.
- Comprueba que la barra de navegación muestra tu nombre y que `/dashboard` responde.
- Sal y vuelve a entrar.
- En la pestaña **Red** de las herramientas de desarrollo, comprueba que las respuestas llevan la cabecera `X-Centro`.

### Comprobación automática

Copia el test [RA5_7_UsuariosTest.php](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_7_UsuariosTest.php) a la carpeta `tests/Feature/` del _ePortfolio_ y ejecuta los tests de este _issue_ en el terminal de `workspace`:

```bash
curl -o tests/Feature/RA5_7_UsuariosTest.php https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_7_UsuariosTest.php
php artisan test --filter=RA5_7_UsuariosTest --group=principal
```

El test comprueba:
- que _Breeze_ está instalado, con sus rutas, y que `/dashboard` exige sesión;
- que siguen vuestras rutas: la portada con `HomeController`, el perfil, `/acerca` y las cuatro tablas;
- que el proyecto sigue con _Tailwind_ 4 y el plugin de formularios;
- la cabecera `X-Centro`, y que sale de la configuración;
- la barra de navegación, con y sin sesión.

Si todo está bien: `Tests: 5 passed`.

Los tests de los _issues_ anteriores, de 5.2 a 5.6, deben seguir pasando. Vuelve a descargar los de 5.3, 5.5 y 5.6: desde este _issue_, entran a las páginas de alta y edición como un usuario identificado.

```bash
for test in RA5_3_DireccionesTest RA5_5_RecursosTest RA5_6_ValidacionTest; do
  curl -o tests/Feature/$test.php https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/$test.php
done
php artisan test --filter='RA5_[2-6]_'
```

_Breeze_ también ha traído sus propios tests, en `tests/Feature/Auth/` y `tests/Feature/ProfileTest.php`. Ejecútalos con `php artisan test tests/Feature/Auth tests/Feature/ProfileTest.php`: deben pasar sin tocarlos.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

Cada integrante revisa el _pull request_ de un compañero:

1. Descarga la rama del _pull request_ (5.1, "Probar un _pull request_ localmente"). _Breeze_ es una dependencia nueva: ejecuta `composer install`, `npm install` y `npm run build` antes que nada.
2. Ejecuta los tests de este _issue_, los de los _issues_ anteriores y los de _Breeze_.
3. Recorre el _ePortfolio_:
    - ¿La plantilla se ve igual que antes de instalar _Breeze_?
    - ¿Responden la portada, `/acerca` y los listados de las cuatro tablas?
    - Regístrate, sal y vuelve a entrar. ¿La barra de navegación cambia según la sesión? ¿Las páginas de _Breeze_ muestran el logo del grupo?
4. Revisa el código:
    - En `routes/web.php`, ¿están vuestras rutas y las de _Breeze_, sin su ruta `/`?
    - ¿Queda `tailwind.config.js` o `postcss.config.js`?
    - ¿El _middleware_ lee el centro de la configuración?
    - ¿El botón "Salir" es un formulario `POST` con `@csrf`, y no un enlace?
5. Deja en el _pull request_ un comentario con el resultado de los tests y lo que habría que mejorar, señalando cada línea con un comentario de revisión. Si todo está bien, apruébalo.

Cuando los _pull requests_ de todos los integrantes estén aprobados, el grupo elige uno y lo fusiona en `main`; los demás se cierran sin fusionar. Después, actualizad todos vuestro _fork_ para empezar los _sub-issues_. Este _issue_ no se cierra todavía: queda abierto hasta que se resuelvan sus cuatro _sub-issues_.

---

## Issue 1: las evidencias, solo con sesión

En este _sub-issue_ proteges con el _middleware_ `auth` el alta y la edición de las evidencias. El listado y el detalle siguen siendo públicos.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_7_evidencias
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. Solo toca los ficheros de las evidencias: no debería haber conflictos.

### Tareas

#### 1. Protege las rutas

En `routes/evidencias.php`, aplica `auth` a un grupo con las cuatro rutas que crean o modifican: `create`, `store`, `edit` y `update`. `index` y `show` quedan fuera del grupo, como en el apartado.

Comprueba el resultado con `php artisan route:list -v --path=evidencias`.

#### 2. Los enlaces, solo con sesión

Un invitado no debe ver enlaces que lo llevan al _login_:

- en el listado, el enlace al alta solo se muestra con `@auth`;
- en el detalle, el enlace a la edición, también.

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_7_UsuariosTest --group=evidencias
```

El test comprueba:
- que, sin sesión, el alta, la edición y sus envíos redirigen al _login_, y que sus rutas tienen el _middleware_ `auth`;
- que, sin sesión, el listado y el detalle siguen respondiendo;
- que, con sesión, el alta y la edición responden;
- que los enlaces al alta y a la edición solo aparecen con sesión.

Si todo está bien: `Tests: 4 passed`. Los tests de los _issues_ de 5.3, 5.5 y 5.6 también deben seguir pasando.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_ y los de los _issues_ de 5.3, 5.5 y 5.6.
2. Sin sesión, pide `/evidencias/edit/3`: debes llegar al _login_. Entra: debes volver a la edición de la evidencia 3.
3. Sin sesión, recorre el listado y un detalle: no debe haber enlaces al alta ni a la edición. Con sesión, sí.
4. Revisa el código: ¿las cuatro rutas protegidas están en un único grupo con `auth`, y no protegidas una a una?
5. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo.

---

## Issue 2: las familias profesionales, solo con sesión

En este _sub-issue_ proteges con el _middleware_ `auth` el alta y la edición de las familias profesionales. El listado y el detalle siguen siendo públicos.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_7_familias-profesionales
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. Solo toca los ficheros de las familias profesionales: no debería haber conflictos.

### Tareas

#### 1. Protege las rutas

En `routes/familias-profesionales.php`, aplica `auth` a un grupo con las cuatro rutas que crean o modifican: `create`, `store`, `edit` y `update`. `index` y `show` quedan fuera del grupo, como en el apartado.

Comprueba el resultado con `php artisan route:list -v --path=familias-profesionales`.

#### 2. Los enlaces, solo con sesión

Un invitado no debe ver enlaces que lo llevan al _login_:

- en el listado, el enlace al alta solo se muestra con `@auth`;
- en el detalle, el enlace a la edición, también.

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_7_UsuariosTest --group=familias-profesionales
```

El test comprueba lo mismo que en las evidencias: las rutas protegidas, el listado y el detalle públicos, el acceso con sesión y los enlaces.

Si todo está bien: `Tests: 4 passed`. Los tests de los _issues_ de 5.5 y 5.6 también deben seguir pasando.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_ y los de los _issues_ de 5.5 y 5.6.
2. Sin sesión, pide `/familias-profesionales/create`: debes llegar al _login_. Entra: debes volver al formulario de alta.
3. Sin sesión, recorre el listado y un detalle: no debe haber enlaces al alta ni a la edición. Con sesión, sí.
4. Revisa el código: ¿las cuatro rutas protegidas están en un único grupo con `auth`, y no protegidas una a una?
5. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo.

---

## Issue 3: los ciclos formativos, solo con sesión

En este _sub-issue_ proteges con el _middleware_ `auth` el alta y la edición de los ciclos formativos. El listado y el detalle siguen siendo públicos.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_7_ciclos-formativos
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. Solo toca los ficheros de los ciclos formativos: no debería haber conflictos.

### Tareas

#### 1. Protege las rutas

En `routes/ciclos-formativos.php`, aplica `auth` a un grupo con las cuatro rutas que crean o modifican: `create`, `store`, `edit` y `update`. `index` y `show` quedan fuera del grupo, como en el apartado.

Comprueba el resultado con `php artisan route:list -v --path=ciclos-formativos`.

#### 2. Los enlaces, solo con sesión

Un invitado no debe ver enlaces que lo llevan al _login_:

- en el listado, el enlace al alta solo se muestra con `@auth`;
- en el detalle, el enlace a la edición, también.

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_7_UsuariosTest --group=ciclos-formativos
```

El test comprueba lo mismo que en las evidencias: las rutas protegidas, el listado y el detalle públicos, el acceso con sesión y los enlaces.

Si todo está bien: `Tests: 4 passed`. Los tests de los _issues_ de 5.5 y 5.6 también deben seguir pasando.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_ y los de los _issues_ de 5.5 y 5.6.
2. Sin sesión, pide `/ciclos-formativos/edit/5`: debes llegar al _login_. Entra: debes volver a la edición del ciclo 5.
3. Sin sesión, recorre el listado y un detalle: no debe haber enlaces al alta ni a la edición. Con sesión, sí.
4. Revisa el código: ¿las cuatro rutas protegidas están en un único grupo con `auth`, y no protegidas una a una?
5. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo.

---

## Issue 4: los módulos formativos, solo con sesión

En este _sub-issue_ proteges con el _middleware_ `auth` el alta y la edición de los módulos formativos. El listado y el detalle siguen siendo públicos.

### Cómo organizaros

Lo resuelve el integrante que lo tenga asignado, cuando el _issue_ principal esté fusionado. Crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_7_modulos-formativos
```

Su _pull request_ lo revisa otro integrante, y se fusiona en `main` en cuanto esté aprobado. Solo toca los ficheros de los módulos formativos: no debería haber conflictos.

### Tareas

#### 1. Protege las rutas

En `routes/modulos-formativos.php`, aplica `auth` a un grupo con las cuatro rutas que crean o modifican: `create`, `store`, `edit` y `update`. `index` y `show` quedan fuera del grupo, como en el apartado.

Comprueba el resultado con `php artisan route:list -v --path=modulos-formativos`.

#### 2. Los enlaces, solo con sesión

Un invitado no debe ver enlaces que lo llevan al _login_:

- en el listado, el enlace al alta solo se muestra con `@auth`;
- en el detalle, el enlace a la edición, también.

### Comprobación automática

Si aún no tienes el test, descárgalo como se explica en el _issue_ principal. Ejecuta los tests de este _issue_:

```bash
php artisan test --filter=RA5_7_UsuariosTest --group=modulos-formativos
```

El test comprueba lo mismo que en las evidencias: las rutas protegidas, el listado y el detalle públicos, el acceso con sesión y los enlaces.

Si todo está bien: `Tests: 4 passed`. Los tests de los _issues_ de 5.5 y 5.6 también deben seguir pasando.

Al abrir tu _pull request_, el repositorio del grupo ejecuta en _GitHub_ estos mismos tests, y los de los _issues_ anteriores, que deben seguir pasando. El resultado aparece en la pestaña **Checks**, y es el que cuenta para la nota. Para eso, la rama debe llamarse exactamente como se indica arriba.

### Revisión por un compañero

1. Descarga la rama del _pull request_ y ejecuta los tests de este _issue_ y los de los _issues_ de 5.5 y 5.6.
2. Sin sesión, pide `/modulos-formativos/create`: debes llegar al _login_. Entra: debes volver al formulario de alta.
3. Sin sesión, recorre el listado y un detalle: no debe haber enlaces al alta ni a la edición. Con sesión, sí.
4. Revisa el código: ¿las cuatro rutas protegidas están en un único grupo con `auth`, y no protegidas una a una?
5. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar. Si todo está bien, apruébalo.

Cuando el _pull request_ esté fusionado, este _sub-issue_ queda resuelto: ciérralo. Cuando lo estén los cuatro, cerrad también el _issue_ principal y comprobad en `main` que el test completo pasa:

```bash
php artisan test --filter=RA5_7_UsuariosTest
```

Si todo está bien: `Tests: 21 passed`.
<!-- {% endraw %} -->
