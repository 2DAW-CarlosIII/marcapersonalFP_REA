# Corrección automática de los _pull requests_ (profesorado)

Herramientas para corregir automáticamente los _pull requests_ del alumnado y reunir sus resultados en una hoja de calificaciones.

**Desde 5.5, se corrigen los ejercicios del _ePortfolio_**: los _pull requests_ que cada alumno abre en el repositorio de _ePortfolio_ de su grupo (por ejemplo, `2DAW-CarlosIII/eportfolio_g1`) para resolver los _issues_ de [`ejercicios-laravel/issues/`](../ejercicios-laravel/issues/). El docente va generando las soluciones de los ejercicios del REA en _marcapersonalfp_ (`marcapersonalfp2627`), donde el alumnado ya no abre _pull requests_. Hasta 5.4, el flujo se aplicó a ese repositorio, y `recoger_notas.py` sigue leyendo sus resultados (ver "Los resultados de _marcapersonalfp_").

| Fichero | Qué es |
|---|---|
| [`correccion.yml`](./correccion.yml) | Flujo de _GitHub Actions_ que ejecuta los tests de cada _pull request_ |
| [`recoger_notas.py`](./recoger_notas.py) | Script que reúne los resultados de uno o varios repositorios en dos CSV |
| [`alumnos.example.csv`](./alumnos.example.csv) | Ejemplo de la relación entre usuarios de _GitHub_ y alumnos |

## Qué hace el flujo

En cada _pull request_ que se abre, se reabre o recibe nuevos _commits_:

1. Descarga el código del _pull request_ en una máquina limpia de _GitHub_.
2. Restaura `phpunit.xml` y `tests/TestCase.php` desde la rama base, para que el alumno no pueda cambiar cómo se ejecutan los tests.
3. Prepara la aplicación igual que en 5.1, con _SQLite_: `composer install`, `.env` a partir de `.env.example`, clave y migraciones. Además, compila los estilos con `npm run build`.
4. Deduce del nombre de la rama **qué ejercicio** se corrige (tabla siguiente) y descarga del REA los **tests oficiales** de los _issues_: el de ese ejercicio y los de los _issues_ anteriores. Antes, borra las copias del alumno, que podrían estar modificadas.
5. Ejecuta el test del ejercicio (solo su `#[Group]`, si es un _sub-issue_), que es el que **cuenta para la nota**, y después los de los _issues_ anteriores, que deben **seguir pasando**. En los dos casos, con `--exclude-group=entorno`. Si falla cualquiera de ellos, la ejecución sale en rojo.
6. Guarda el resultado (dos JUnit XML y un `meta.json` con el repositorio, el número del _pull request_, el autor, la rama y el ejercicio) como **artefacto** de la ejecución, durante 90 días.
7. Muestra al alumno una tabla con los tests aprobados y fallidos de cada test, en la pestaña **Checks** de su _pull request_.

### De la rama al ejercicio

| Rama | Ejercicio | Qué se ejecuta para la nota |
|---|---|---|
| `RA5_2`, `RA5_3`, `RA5_4` | `RA5_2`, `RA5_3`, `RA5_4` | El test del _issue_ completo (`RA5_2_ConfiguracionTest`…) |
| `RA5_5` | `RA5_5_principal` | `RA5_5_RecursosTest --group=principal` |
| `RA5_5_evidencias` | `RA5_5_evidencias` | `RA5_5_RecursosTest --group=evidencias` (igual con los otros tres recursos) |
| `RA5_6`… sin test todavía | ninguno | Solo los tests de los _issues_ anteriores, con un aviso |
| Cualquier otra | ninguno | Todos los tests, con un aviso: no cuentan para la nota |

Detrás del nombre se admite un sufijo con guion (`RA5_3-arreglo`, `RA5_5_evidencias-v2`). El _issue_ del 5.4 es el de 5.4.1 (la plantilla), en la rama `RA5_4`.

Los tests que conoce el flujo están en su variable `TESTS_EPORTFOLIO`. **Cuando se redacta el test de un _issue_ nuevo** (5.6, 5.7…), hay que añadirlo a esa lista en [`correccion.yml`](./correccion.yml) y actualizar el flujo en los repositorios de los grupos (ver "Puesta en marcha"). Si el test tiene un `#[Group]` por _sub-issue_, el flujo los reconoce solo: basta con que cada rama lleve el nombre del grupo, como en 5.5.

### El grupo `entorno`

Algunas comprobaciones dependen de la máquina virtual de cada alumno, no del código que sube, y no se pueden hacer en _GitHub_. Llevan el atributo `#[Group('entorno')]` y el flujo las excluye. En los _issues_ del _ePortfolio_, de momento solo una: en 5.2, `test_configuracion_regional_del_env`, porque `APP_LOCALE` y `APP_FAKER_LOCALE` del `.env` no se suben. El alumnado la sigue ejecutando en su máquina, y la revisa el compañero.

Si un ejercicio solo tiene comprobaciones del grupo `entorno`, su _pull request_ termina en verde con el aviso "Este ejercicio no tiene tests automáticos", y no aparece en los CSV. Si no se encuentra ningún test por otro motivo (por ejemplo, un error fatal de la aplicación), la ejecución sí falla.

### Seguridad

El flujo usa el evento `pull_request`, el que _GitHub_ recomienda para el código que llega desde un _fork_: el código del alumno se ejecuta con un _token_ de **solo lectura** (`permissions: contents: read`) y **sin acceso a los secretos** del repositorio. Además, los datos del _pull request_, como el nombre de la rama, solo se usan a través de variables de entorno, para que no se puedan inyectar órdenes. No añadas secretos a este flujo.

Con este evento, la versión de `correccion.yml` que se ejecuta es **la del propio _pull request_**: un alumno podría modificarla para aprobar sin pasar los tests. Por eso `recoger_notas.py` comprueba los ficheros de cada _pull request_ y marca en la columna `revisar` los que cambian algo de `.github/`. Su nota no es fiable hasta que lo revises.

En el _ePortfolio_ hay un riesgo más: el alumnado **fusiona** los _pull requests_ en el repositorio del grupo, así que un cambio en `.github/` que nadie detecte acabaría en `main` y se ejecutaría en todos los _pull requests_ siguientes. Por eso `recoger_notas.py` compara también el `correccion.yml` de la rama principal de cada repositorio con el de esta carpeta, y avisa si no coinciden. Para evitarlo del todo, puedes proteger `.github/` en cada repositorio: un fichero `.github/CODEOWNERS` con la línea `/.github/ @tu-usuario` y, en **Settings → Rules**, una regla para `main` que exija la revisión de los _code owners_.

> **¿Por qué no `pull_request_target`?** Ese evento ejecutaría siempre la versión del flujo de `main`, que el alumno no puede tocar. Pero `actions/checkout` ya se niega a descargar con él el código de un _fork_ (habría que activar `allow-unsafe-pr-checkout`), y _GitHub_ restringe ese evento en los repositorios públicos desde el 2 de diciembre de 2026.

## Puesta en marcha (en cada repositorio de grupo)

1. Crea en la rama `main` de cada repositorio de _ePortfolio_ el fichero `.github/workflows/correccion.yml`, con el contenido de [`correccion.yml`](./correccion.yml). Así llega a todos los _pull requests_, aunque el alumno hiciera su _fork_ antes de que existiera: _GitHub_ ejecuta el flujo sobre la combinación del _pull request_ con `main`. Con `gh`, desde esta carpeta, se suben los cinco a la vez:

    ```bash
    gh auth refresh -s workflow
    for n in 1 2 3 4 5; do
      gh api -X PUT "repos/2DAW-CarlosIII/eportfolio_g$n/contents/.github/workflows/correccion.yml" \
        -f message="Añade la corrección automática" -f content="$(base64 -w0 correccion.yml)"
    done
    ```

    El permiso `workflow` hace falta para crear o cambiar flujos de _GitHub Actions_. Para **actualizar** el fichero cuando ya existe, la API pide además su _hash_ actual: añade `-f sha="$(gh api repos/2DAW-CarlosIII/eportfolio_g$n/contents/.github/workflows/correccion.yml --jq .sha)"`. Cada integrante recibe el flujo en su _fork_ la próxima vez que actualice su `main` (`git pull upstream main`).

2. En **Settings → Actions → General** de cada repositorio:
    - comprueba que _Actions_ está activado y que se permiten las acciones que usa: `actions/checkout`, `actions/setup-node`, `actions/upload-artifact` y `shivammathur/setup-php`;
    - en **Approval for running fork pull request workflows from contributors**, elige **Require approval for first-time contributors who are new to GitHub**. Con la opción por defecto, tendrías que pulsar **Approve and run** en el primer _pull request_ de cada alumno que aún no tenga ninguno fusionado. Con la que se propone, solo en los de las cuentas de _GitHub_ recién creadas.
3. (Recomendado) Fija la versión de los tests: en **Settings → Secrets and variables → Actions → Variables** de la **organización**, crea la variable `REA_REF` con el _hash_ de un _commit_ del REA, y dale acceso a los repositorios de los grupos. Así la cambias una sola vez para los cinco. Si no existe, se usa `master`, y cualquier cambio en los tests del REA afectaría a las correcciones en curso.
4. Los _pull requests_ que ya estaban abiertos antes de añadir el flujo no se corrigen solos. Pide al alumno que suba un _commit_ nuevo, o ciérralo y vuelve a abrirlo.

**Los conflictos detienen el flujo.** Un _pull request_ con conflictos con `main` no ejecuta el flujo: _GitHub_ necesita poder combinarlo con la rama base. En el _ePortfolio_ es normal, porque `main` avanza cada vez que el grupo fusiona un _pull request_, y en los _sub-issues_ los conflictos son seguros (ver el _issue_ de 5.5). En cuanto el alumno los resuelve y sube su rama, el flujo se ejecuta.

## Recoger las notas

Necesitas _GitHub CLI_ (`gh`) en la máquina virtual, identificado con tu cuenta ([§12 de la preparación del entorno](../../00_instalacionEntorno.md#12-github-cli-gh)). Copia `alumnos.example.csv` como `alumnos.csv` y complétalo con el usuario de _GitHub_ y el nombre de cada alumno. Después, con los cinco repositorios a la vez:

```bash
python3 recoger_notas.py --alumnos alumnos.csv 2DAW-CarlosIII/eportfolio_g{1..5}
```

(`{1..5}` lo expande _bash_ en los cinco nombres.) El script avisa de los repositorios cuyo `correccion.yml` de `main` no coincide con el de esta carpeta, y genera, en la carpeta desde la que lo ejecutas:

| Fichero | Contenido |
|---|---|
| `notas_detalle.csv` | Una fila por _pull request_ y clase de test: repositorio, ejercicio, tipo (`ejercicio`, el que cuenta, o `anterior`), tests, aprobados, fallidos, nota (0-10) y el nombre de los tests fallidos |
| `notas_resumen.csv` | Una fila por alumno, con su repositorio, una columna por ejercicio (`RA5_2`, `RA5_3`, `RA5_4`, `RA5_5_principal`, `RA5_5_evidencias`…) y la columna `revisar`, con los _pull requests_ que cambian `.github/` |

En el resumen, la nota de cada ejercicio es la de **su propio test** (o de su grupo, en un _sub-issue_) en el _pull request_ de ese ejercicio: aprobados ÷ tests × 10. Si un alumno abre varios _pull requests_ del mismo ejercicio, cuenta el último. Los tests de los _issues_ anteriores aparecen en el detalle, pero no cambian la nota.

En los ejercicios con trabajo repartido, cada alumno tiene nota en **su** _sub-issue_, y solo uno en el principal: la columna de cada recurso tiene una nota por grupo. La nota final del bloque se calcula en la hoja de cálculo, con los pesos que decidas (por ejemplo, las horas de cada apartado, o una sola nota por apartado con el _sub-issue_ de cada alumno).

Los artefactos caducan a los 90 días. Ejecuta el script al terminar cada apartado y guarda los CSV.

### Los resultados de _marcapersonalfp_

Los artefactos del flujo anterior (5.1 a 5.4, en `marcapersonalfp2627`) se leen igual: su ejercicio es el apartado de la rama (`RA5_2`, `RA5_3`…), y en el detalle los tests de los apartados anteriores salen como `anterior`. Recógelos **antes de que caduquen**, por ejemplo junto con los de los grupos:

```bash
python3 recoger_notas.py --alumnos alumnos.csv 2DAW-CarlosIII/marcapersonalfp2627 2DAW-CarlosIII/eportfolio_g{1..5}
```

El aviso de que su `correccion.yml` no coincide con el de esta carpeta es normal: es la versión anterior del flujo. Como el alumnado ya no abre _pull requests_ en ese repositorio, puedes dejarlo o borrarlo: borrarlo no elimina los artefactos que ya existen.

### Los _pull requests_, después de corregirlos

En el _ePortfolio_, **el grupo decide qué se fusiona**: de cada _issue_ fusiona un _pull request_ y cierra los demás (en los _sub-issues_, se fusionan todos). Ni fusionar ni cerrar un _pull request_ borra su artefacto, así que el script lo sigue encontrando. Antes de recoger las notas, comprueba que la ejecución del último _commit_ de cada _pull request_ ha terminado (una marca verde o roja junto a él).
