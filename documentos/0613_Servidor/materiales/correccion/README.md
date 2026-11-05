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

### Seguridad

El flujo usa el evento `pull_request_target`, que ejecuta **la versión de `correccion.yml` de la rama base**. Así, un alumno no puede modificarlo en su _pull request_ para aprobar sin pasar los tests. Además, no hace falta aprobar a mano la primera ejecución de cada alumno.

A cambio, el código del alumno se ejecuta con limitaciones:
- un _token_ de **solo lectura** (`permissions: contents: read`);
- **sin secretos** y sin caché compartida;
- los datos del _pull request_, como el nombre de la rama, solo se usan a través de variables de entorno, para que no se puedan inyectar órdenes.

No añadas secretos a este flujo.

## Puesta en marcha (una vez por curso)

1. En el repositorio base, crea `.github/workflows/correccion.yml` con el contenido de [`correccion.yml`](./correccion.yml) y súbelo a `main`. Con `pull_request_target`, el flujo solo funciona cuando está en la rama base.
2. En **Settings → Actions → General**, comprueba que _Actions_ está activado y que se permiten las acciones que usa: `actions/checkout`, `actions/setup-node`, `actions/upload-artifact` y `shivammathur/setup-php`.
3. (Recomendado) Fija la versión de los tests: en **Settings → Secrets and variables → Actions → Variables**, crea la variable `REA_REF` con el _hash_ de un _commit_ del REA. Si no existe, se usa `master`, y cualquier cambio en los tests del REA afectaría a las correcciones en curso.
4. Los _pull requests_ que ya estaban abiertos antes de añadir el flujo no se corrigen solos. Ciérralos y vuelve a abrirlos (o pide al alumno que suba un _commit_ nuevo).

## Recoger las notas

Necesitas _GitHub CLI_ (`gh`) en la máquina virtual, identificado con tu cuenta ([§12 de la preparación del entorno](../../00_instalacionEntorno.md#12-github-cli-gh)). Copia `alumnos.example.csv` como `alumnos.csv` y complétalo con el usuario de _GitHub_ y el nombre de cada alumno. Después:

```bash
python3 recoger_notas.py 2DAW-CarlosIII/marcapersonalfp2627 alumnos.csv
```

El script genera, en la carpeta desde la que lo ejecutas:

| Fichero | Contenido |
|---|---|
| `notas_detalle.csv` | Una fila por _pull request_ y clase de test: tests, aprobados, fallidos, nota (0-10) y el nombre de los tests fallidos |
| `notas_resumen.csv` | Una fila por alumno y una columna por apartado (`RA5_1` … `RA5_8`) |

En el resumen, la nota de cada apartado es la de **su propio test** (por ejemplo, `RA5_3_RutasTest` en un _pull request_ de la rama `RA5_3`): aprobados ÷ tests × 10. Si un alumno abre varios _pull requests_ del mismo apartado, cuenta el último. Los tests de apartados anteriores aparecen en el detalle, pero no cambian la nota. La nota final del bloque se calcula en la hoja de cálculo, con los pesos que decidas, por ejemplo las horas de cada apartado.

Los artefactos caducan a los 90 días. Ejecuta el script al terminar cada apartado y guarda los CSV.

### Cerrar los _pull requests_

Cierra cada _pull request_ cuando su ejecución haya terminado (una marca verde o roja junto al último _commit_). Cerrarlo no borra el artefacto, así que el script lo sigue encontrando.
