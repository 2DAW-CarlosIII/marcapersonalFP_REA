# Las rutas del _ePortfolio_
<!-- {% raw %} -->

En el apartado [5.3. Rutas](https://github.com/2DAW-CarlosIII/marcapersonalFP_REA/blob/master/documentos/0613_Servidor/RA5_3_rutas.md) definiste las direcciones de _marcapersonalfp_: rutas con parámetros, restricciones, nombres, un grupo y una respuesta en JSON. En este _issue_ vais a definir las del _ePortfolio_ del grupo.

El _ePortfolio_ todavía no tiene datos: las entidades que tendrá cuando esté terminado están en su [diagrama entidad-relación](https://github.com/2DAW-CarlosIII/ePortfolio/blob/master/docs/eportfolio_er_diagram.mermaid). Empezamos por su recurso central, las **evidencias**: lo que cada estudiante sube para demostrar que ha resuelto una tarea. Como en 5.3, cada ruta devuelve de momento un texto; en el Bloque 5 irán llegando las vistas, los controladores y los formularios.

No hay código que copiar: el apartado 5.3 tiene todo lo que necesitáis. Si os atascáis, volved a él antes de preguntar.

## Cómo organizaros

**Cada integrante del grupo hace todas las tareas** en su _fork_ del repositorio del grupo, y abre su propio _pull request_ hacia el repositorio del grupo. Antes de empezar, crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_3
```

Cada _pull request_ lo revisa otro integrante del grupo, y cada uno revisa el de un compañero (al final de este _issue_ tenéis cómo). Repartid las revisiones para que nadie se quede sin revisor.

Todas las rutas van en `routes/web.php`.

## Tareas

### 1. La portada

La ruta `/` sustituye a la de la página de bienvenida de _Laravel_ y devuelve el texto `Portada del ePortfolio`.

### 2. Las rutas de las evidencias

Define estas rutas. Cada una devuelve el texto de la tabla, con el `{id}` recibido donde aparece:

| Método | Dirección | Nombre | Texto que devuelve |
|---|---|---|---|
| GET | `/evidencias` | `evidencias.index` | `Listado de evidencias` |
| GET | `/evidencias/show/{id}` | `evidencias.show` | `Evidencia {id}` |
| GET | `/evidencias/create` | `evidencias.create` | `Nueva evidencia` |
| GET | `/evidencias/edit/{id}` | `evidencias.edit` | `Modificar evidencia {id}` |

Requisitos:

- Las cuatro rutas van en **un grupo**, con prefijo de dirección y prefijo de nombre: dentro del grupo no se repite `evidencias` ni en las direcciones ni en los nombres.
- Todos los `{id}` son **números naturales**: con cualquier otra cosa, la respuesta es 404 sin llegar a vuestro código.

### 3. El perfil

Añade la ruta `/perfil/{id?}`, con nombre `perfil`:

- Con `{id}`, devuelve `Perfil del usuario {id}`.
- Sin `{id}`, devuelve `Mi perfil`.
- El `{id}` también es un número natural.

### 4. Una respuesta en JSON

Añade la ruta `/acerca`, con nombre `acerca`. Devuelve un array con dos claves, que _Laravel_ convierte en JSON:

| Clave | Valor |
|---|---|
| `aplicacion` | El nombre de la aplicación, el `APP_NAME` del `.env` |
| `centro` | El nombre del centro, de la configuración que creaste en el _issue_ de 5.2 |

Los dos valores se leen **de la configuración**, con `config()`: no los escribas dentro de la ruta. Si mañana cambia el nombre del centro en el `.env`, `/acerca` tiene que cambiar sin tocar el código.

Ábrela en `http://eportfolio.test/acerca` y mira, en las herramientas de desarrollo (F12), pestaña **Red**, la cabecera `Content-Type` de la respuesta.

### 5. Comprueba tus rutas

Ejecuta `php artisan route:list --except-vendor` y compara el resultado con las tablas de este _issue_: métodos, direcciones y nombres. Con `php artisan route:list --path=evidencias` deben salir solo las cuatro rutas de las evidencias.

## Comprobación automática

Copia el test [RA5_3_DireccionesTest.php](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_3_DireccionesTest.php) a la carpeta `tests/Feature/` del _ePortfolio_ y ejecútalo en el terminal de `workspace`:

```bash
curl -o tests/Feature/RA5_3_DireccionesTest.php https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_3_DireccionesTest.php
php artisan test --filter=RA5_3_DireccionesTest
```

El test comprueba:

- el texto de la portada, de cada ruta de las evidencias y del perfil, con `{id}` y sin él;
- que un `{id}` que no es un número da 404;
- los nombres de las rutas, generando sus direcciones con `route()`;
- que las rutas de las evidencias están en un grupo con el prefijo `evidencias`;
- que `/acerca` devuelve el nombre de la aplicación y el del centro, y que los dos salen de la configuración: el test la cambia y comprueba que la respuesta cambia con ella.

Si todo está bien: `Tests: 8 passed`.

## Revisión por un compañero

Cada integrante revisa el _pull request_ de un compañero:

1. Descarga la rama del _pull request_ (5.1, "Probar un _pull request_ localmente") y ejecuta el test.
2. Ejecuta `php artisan route:list --except-vendor` y compara cada ruta con las tablas de este _issue_.
3. En el navegador, pide `/evidencias/show/abc`, `/evidencias/edit/abc` y `/perfil/abc`: las tres deben dar 404. Pide también `/perfil`, sin `{id}`.
4. Cambia `NOMBRE_CENTRO` en tu `.env`, pide `/acerca` y comprueba que el nombre del centro cambia sin tocar el código. Al terminar, deja la variable como estaba.
5. Revisa el código de `routes/web.php`:
    - ¿Las rutas de las evidencias están en un grupo, sin repetir `evidencias` en sus direcciones ni en sus nombres?
    - ¿Todos los `{id}` tienen su restricción?
    - ¿`/acerca` lee los dos valores con `config()`, o alguno está escrito a mano?
    - ¿Queda la ruta de la página de bienvenida, o alguna ruta de prueba que sobre?
    - ¿Se ha colado en el _pull request_ algún fichero que no debería, como `.env`?
6. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar, señalando cada línea con un comentario de revisión. Si todo está bien, aprueba el _pull request_.

Cuando los _pull requests_ de todos los integrantes estén aprobados, el grupo elige uno y lo fusiona en `main`; los demás se cierran sin fusionar. Con eso, el _issue_ queda resuelto: cerradlo y actualizad cada uno vuestro _fork_ (`git switch main` y `git pull upstream main`) antes de empezar el siguiente.
<!-- {% endraw %} -->
