# La configuración del _ePortfolio_
<!-- {% raw %} -->

En el apartado [5.2. Estructura de un proyecto, configuración y _Artisan_](https://github.com/2DAW-CarlosIII/marcapersonalFP_REA/blob/master/documentos/0613_Servidor/RA5_2_estructura.md) configuraste _marcapersonalfp_: el idioma, un fichero de configuración propio, un comando de _Artisan_ y la depuración con _Xdebug_. En este _issue_ vais a hacer lo mismo en el _ePortfolio_ del grupo.

Esta vez no hay código que copiar: el apartado 5.2 tiene todo lo que necesitáis. Si os atascáis, volved a él antes de preguntar.

## Cómo organizaros

**Cada integrante del grupo hace todas las tareas** en su _fork_ del repositorio del grupo, y abre su propio _pull request_ hacia el repositorio del grupo. Antes de empezar, crea la rama a partir de la última versión de `main`:

```bash
git switch main
git pull upstream main
git switch -c RA5_2
```

Cada _pull request_ lo revisa otro integrante del grupo, y cada uno revisa el de un compañero (al final de este _issue_ tenéis cómo). Repartid las revisiones para que nadie se quede sin revisor.

El proyecto está en la carpeta `eportfolio` de laradock (`/var/www/eportfolio` dentro de los contenedores) y se abre en `http://eportfolio.test`, igual que _marcapersonalfp_.

## Tareas

### 1. La configuración regional

El _ePortfolio_ debe estar en español, y los datos de prueba que generaremos en el Bloque 6 también.

- Cambia en el `.env` las variables del idioma de la aplicación (`es`) y del idioma de los datos de prueba (`es_ES`), como en 5.2.
- Haz el mismo cambio en **`.env.example`**. En _marcapersonalfp_ bastaba con tu `.env`, pero aquí el proyecto lo compartís varios: el `.env` no se sube, y `.env.example` es lo que tus compañeros copiarán para crear el suyo.

Comprueba el resultado con `php artisan config:show app`.

### 2. Un fichero de configuración propio

Crea `config/eportfolio.php`, el equivalente a `config/marcapersonalfp.php` de 5.2:

- Tiene una opción, `centro`, con el nombre del centro.
- El valor se lee de la variable `NOMBRE_CENTRO` del `.env` y, si no existe, vale `CIFP Carlos III`.
- Pon un comentario que explique para qué sirve la opción.
- Añade `NOMBRE_CENTRO` al `.env` **y** a `.env.example`. Recuerda las comillas, porque el valor tiene espacios.

Comprueba el resultado en `php artisan tinker` con `config('eportfolio.centro')`.

### 3. Un comando propio

En `routes/console.php`, crea el comando `eportfolio:centro`:

- Escribe en verde el nombre del centro, leído de la configuración de la tarea 2 (no lo escribas dentro del comando).
- Tiene una descripción, que se ve en `php artisan list`.

Ejecútalo con `php artisan eportfolio:centro` y búscalo en `php artisan list`.

### 4. _Xdebug_ en el _ePortfolio_

- Abre la carpeta del _ePortfolio_ en VS Code y crea su `.vscode/launch.json`, como en 5.2. Piensa qué ruta tiene el proyecto dentro del contenedor para escribir bien el `pathMappings`.
- Pon un punto de ruptura en la última línea de `public/index.php`, empieza a escuchar (<kbd>F5</kbd>) y pide `http://eportfolio.test` con el disparador.
- Comprueba que el `.gitignore` excluye `/.vscode`, y si no, añádelo: `launch.json` es configuración de tu editor, no del proyecto, y no debe aparecer en tu _pull request_.

Cuando funcione, añade a la descripción de tu _pull request_ una captura de VS Code detenido en el punto de ruptura, en la que se vea la **Pila de llamadas**.

## Comprobación automática

Copia el test [RA5_2_ConfiguracionTest.php](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_2_ConfiguracionTest.php) a la carpeta `tests/Feature/` del _ePortfolio_ y ejecútalo en el terminal de `workspace`:

```bash
curl -o tests/Feature/RA5_2_ConfiguracionTest.php https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/ejercicios-laravel/issues/RA5_2_ConfiguracionTest.php
php artisan test --filter=RA5_2_ConfiguracionTest
```

El test comprueba:

- que `.env.example` tiene el idioma de la aplicación, el de los datos de prueba y `NOMBRE_CENTRO`;
- que tu `.env` tiene el mismo idioma (esta comprobación depende de la máquina de cada uno);
- que `config/eportfolio.php` existe y que `centro` se lee de `NOMBRE_CENTRO` con `env()`;
- que el comando `eportfolio:centro` existe, tiene descripción y escribe el nombre del centro;
- que el `.gitignore` excluye `/.vscode`.

Si todo está bien: `Tests: 6 passed`.

## Revisión por un compañero

Cada integrante revisa el _pull request_ de un compañero:

1. Descarga la rama del _pull request_ (5.1, "Probar un _pull request_ localmente") y ejecuta el test.
2. Compara su `.env` con el `.env.example` de la rama: ¿le falta alguna variable nueva? Si es así, la añade a su `.env`, como haría cualquier compañero que se descarga el proyecto.
3. Ejecuta `php artisan eportfolio:centro` y `php artisan list`. Después cambia `NOMBRE_CENTRO` en su `.env`, vuelve a ejecutar el comando y comprueba que el nombre cambia sin tocar el código. Al terminar, deja la variable como estaba.
4. Revisa el código: ¿el comentario de `config/eportfolio.php` explica la opción? ¿El comando lee el nombre de la configuración, o lo tiene escrito dentro? ¿Se ha colado en el _pull request_ algún fichero que no debería, como `.env` o `.vscode/launch.json`?
5. Comprueba que la descripción del _pull request_ tiene la captura de _Xdebug_.
6. Deja en el _pull request_ un comentario con el resultado del test y lo que habría que mejorar, señalando cada línea con un comentario de revisión. Si todo está bien, aprueba el _pull request_.

Cuando los _pull requests_ de todos los integrantes estén aprobados, el grupo elige uno y lo fusiona en `main`; los demás se cierran sin fusionar. Con eso, el _issue_ queda resuelto: cerradlo y actualizad cada uno vuestro _fork_ (`git switch main` y `git pull upstream main`) antes de empezar el siguiente.
<!-- {% endraw %} -->
