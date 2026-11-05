# Corrección automática de los _pull requests_ (profesorado)

Herramientas para corregir automáticamente los _pull requests_ que el alumnado abre en el repositorio base del curso (por ejemplo, `2DAW-CarlosIII/marcapersonalfp2627`) y reunir sus resultados en una hoja de calificaciones. Los _pull requests_ **no se fusionan**: se corrigen y se cierran.

| Fichero | Qué es |
|---|---|
| [`correccion.yml`](./correccion.yml) | Flujo de _GitHub Actions_ que ejecuta los tests de cada _pull request_ |
| [`recoger_notas.py`](./recoger_notas.py) | Script que reúne los resultados en dos CSV |
| [`alumnos.example.csv`](./alumnos.example.csv) | Ejemplo de la relación entre usuarios de _GitHub_ y alumnos |

## Qué hace el flujo

En cada _pull request_ que se abre, se reabre o recibe nuevos _commits_:

1. Descarga el código del _pull request_ en una máquina limpia de _GitHub_.
2. Restaura `phpunit.xml` y `tests/TestCase.php` desde la rama base, para que el alumno no pueda cambiar cómo se ejecutan los tests.
3. Prepara la aplicación igual que en 5.1, con _SQLite_: `composer install`, `.env` a partir de `.env.example`, clave y migraciones. Además, compila los estilos con `npm run build`.
4. Deduce el apartado del nombre de la rama (`RA5_3`, `RA5_3-arreglo`… → 5.3) y descarga del REA los **tests oficiales** de ese apartado y de los anteriores. Antes, borra las copias del alumno, que podrían estar modificadas.
5. Ejecuta `php artisan test --filter=RA5_ --exclude-group=entorno`.
6. Guarda el resultado (JUnit XML y un `meta.json` con el número del _pull request_, el autor, la rama y el apartado) como **artefacto** de la ejecución, durante 90 días.
7. Muestra al alumno una tabla con los tests aprobados y fallidos, en la pestaña **Checks** de su _pull request_.

### El grupo `entorno`

Algunas comprobaciones dependen de la máquina virtual de cada alumno, no del código que sube, y no se pueden hacer en _GitHub_. Llevan el atributo `#[Group('entorno')]` y el flujo las excluye:

- **todo el test de 5.1:** remotos de _git_, `APP_KEY`, `APP_NAME` y `APP_URL` del `.env`, la base _SQLite_ y el sitio de _nginx_;
- **en 5.2, `test_configuracion_regional`:** `APP_LOCALE` y `APP_FAKER_LOCALE` solo están en el `.env`.

El alumnado sigue ejecutándolas en su máquina con los comandos de cada apartado. La nota de 5.1 tiene que salir de otra evidencia, por ejemplo la revisión del propio _pull request_.

Como en 5.1 no queda ningún test que ejecutar, sus _pull requests_ terminan en verde con el aviso "Este apartado no tiene tests automáticos", y no aparecen en los CSV. Si no se encuentra ningún test por otro motivo (por ejemplo, un error fatal de la aplicación), la ejecución sí falla.

### Seguridad

El flujo usa el evento `pull_request`, el que _GitHub_ recomienda para el código que llega desde un _fork_: el código del alumno se ejecuta con un _token_ de **solo lectura** (`permissions: contents: read`) y **sin acceso a los secretos** del repositorio. Además, los datos del _pull request_, como el nombre de la rama, solo se usan a través de variables de entorno, para que no se puedan inyectar órdenes. No añadas secretos a este flujo.

Con este evento, la versión de `correccion.yml` que se ejecuta es **la del propio _pull request_**: un alumno podría modificarla para aprobar sin pasar los tests. Por eso `recoger_notas.py` comprueba los ficheros de cada _pull request_ y marca en la columna `revisar` los que cambian algo de `.github/`. Su nota no es fiable hasta que lo revises.

> **¿Por qué no `pull_request_target`?** Ese evento ejecutaría siempre la versión del flujo de `main`, que el alumno no puede tocar. Pero `actions/checkout` ya se niega a descargar con él el código de un _fork_ (habría que activar `allow-unsafe-pr-checkout`), y _GitHub_ restringe ese evento en los repositorios públicos desde el 2 de diciembre de 2026.

## Puesta en marcha (una vez por curso)

1. En el repositorio base, crea `.github/workflows/correccion.yml` con el contenido de [`correccion.yml`](./correccion.yml) y súbelo a `main`. Así llega a todos los _pull requests_, aunque el alumno hiciera su _fork_ antes de que existiera: _GitHub_ ejecuta el flujo sobre la combinación del _pull request_ con `main`.
2. En **Settings → Actions → General**:
    - comprueba que _Actions_ está activado y que se permiten las acciones que usa: `actions/checkout`, `actions/setup-node`, `actions/upload-artifact` y `shivammathur/setup-php`;
    - en **Approval for running fork pull request workflows from contributors**, elige **Require approval for first-time contributors who are new to GitHub**. Para _GitHub_, quien nunca ha tenido un _pull request_ fusionado es un colaborador nuevo, y estos no se fusionan nunca: con la opción por defecto tendrías que pulsar **Approve and run** en cada _pull request_ de cada alumno. Con la que se propone, solo en los de las cuentas de _GitHub_ recién creadas.
3. (Recomendado) Fija la versión de los tests: en **Settings → Secrets and variables → Actions → Variables**, crea la variable `REA_REF` con el _hash_ de un _commit_ del REA. Si no existe, se usa `master`, y cualquier cambio en los tests del REA afectaría a las correcciones en curso.
4. Los _pull requests_ que ya estaban abiertos antes de añadir el flujo no se corrigen solos. Ciérralos y vuelve a abrirlos (o pide al alumno que suba un _commit_ nuevo).

Un _pull request_ con conflictos con `main` no ejecuta el flujo: _GitHub_ necesita poder combinarlo con la rama base. Como `main` no recibe los cambios del alumnado, solo ocurriría si un alumno modifica el propio `correccion.yml`.

## Recoger las notas

Necesitas _GitHub CLI_ (`gh`) en la máquina virtual, identificado con tu cuenta ([§12 de la preparación del entorno](../../00_instalacionEntorno.md#12-github-cli-gh)). Copia `alumnos.example.csv` como `alumnos.csv` y complétalo con el usuario de _GitHub_ y el nombre de cada alumno. Después:

```bash
python3 recoger_notas.py 2DAW-CarlosIII/marcapersonalfp2627 alumnos.csv
```

El script genera, en la carpeta desde la que lo ejecutas:

| Fichero | Contenido |
|---|---|
| `notas_detalle.csv` | Una fila por _pull request_ y clase de test: tests, aprobados, fallidos, nota (0-10) y el nombre de los tests fallidos |
| `notas_resumen.csv` | Una fila por alumno, una columna por apartado (`RA5_1` … `RA5_8`) y la columna `revisar`, con los _pull requests_ que cambian `.github/` |

En el resumen, la nota de cada apartado es la de **su propio test** (por ejemplo, `RA5_3_RutasTest` en un _pull request_ de la rama `RA5_3`): aprobados ÷ tests × 10. Si un alumno abre varios _pull requests_ del mismo apartado, cuenta el último. Los tests de apartados anteriores aparecen en el detalle, pero no cambian la nota. La nota final del bloque se calcula en la hoja de cálculo, con los pesos que decidas, por ejemplo las horas de cada apartado.

Los artefactos caducan a los 90 días. Ejecuta el script al terminar cada apartado y guarda los CSV.

### Cerrar los _pull requests_

Cierra cada _pull request_ cuando su ejecución haya terminado (una marca verde o roja junto al último _commit_). Cerrarlo no borra el artefacto, así que el script lo sigue encontrando.
