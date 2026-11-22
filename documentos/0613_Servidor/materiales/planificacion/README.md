# Planificación del _ePortfolio_ en _GitHub Projects_

[`planificar_eportfolio.py`](./planificar_eportfolio.py) crea y mantiene un **proyecto de _GitHub_** por cada grupo. El proyecto marca **cuándo se plantea y cuándo termina** cada ejercicio del _ePortfolio_, desde el Bloque 5 hasta el final del curso. Las fechas se calculan a partir de las horas de cada bloque y apartado y del calendario del curso, que están en [`planificacion.toml`](./planificacion.toml).

Para cada destino, en este orden, el _script_:

1. busca el proyecto en la organización y, si no existe, lo crea;
2. lo hace público, le pone una descripción y un README con la tabla de fechas, y lo enlaza al repositorio;
3. crea los campos `Planteamiento` y `Fecha límite` (fechas) y `Bloque` (selección);
4. crea en el repositorio los _issues_ que falten, con el texto de los `.md` de [`ejercicios-laravel/issues/`](../ejercicios-laravel/issues/), y enlaza los _sub-issues_ a su _issue_ principal;
5. añade al proyecto los _issues_ y un **borrador** por cada ejercicio que aún no tiene _issue_ (5.6, 5.7 y los bloques 6 a 9);
6. pone a cada elemento sus fechas y su bloque.

Es **idempotente**: reutiliza lo que ya existe, sin duplicar nada, y solo cambia lo que no coincide. Si cambia el calendario, basta con corregir `planificacion.toml` y volver a ejecutarlo.

## Las fechas de cada ejercicio

- **`Planteamiento`**: el día en que se explica su apartado.
- **`Fecha límite`**: el día en que empieza el apartado siguiente. La revisión por un compañero puede terminar algo después, pero la fecha límite no se mueve.

Un bloque sin apartados (de momento, del 6 al 9) lleva un único borrador. Si el bloque tiene margen (`horas_planificadas`, por ejemplo 20 h de ejercicios sobre 24), su fecha límite es el día en que acabarían esas horas.

Para los apartados ya planteados, se escribe su fecha real en `planteamiento`, y los siguientes se calculan desde ahí. Una fecha límite distinta de la calculada se fija con `fecha_limite`, como la del Bloque 9, que termina el lunes siguiente al último día de clase. Los bloques sin apartados también admiten las dos.

## Los destinos y el proyecto de referencia

El primer destino es **`marcapersonalfp2627`**, el repositorio sobre el que el profesorado explica: todo se genera primero en él (su proyecto y sus _issues_), y después en los cinco grupos. Los proyectos de los grupos que aún no existen **se copian del de referencia** con `gh project copy`, de modo que heredan sus campos, sus borradores y **sus vistas**. Por eso conviene configurar las vistas del proyecto de referencia **antes** de crear los de los grupos (ver más abajo).

Un destino con `crear_issues = false` en `planificacion.toml` solo lleva el proyecto y los borradores, sin _issues_.

## Requisitos

- **_Python_ 3.11** o posterior, que trae `tomllib` (_Debian_ 13 tiene la 3.13). No necesita paquetes adicionales.
- **_GitHub CLI_** (`gh`), instalado e identificado como en [§12 de la preparación del entorno](../../00_instalacionEntorno.md#12-github-cli-gh), con una cuenta que pueda crear proyectos e _issues_ en la organización `2DAW-CarlosIII`.
- El **permiso `project`** del _token_ de `gh`, que no se concede por defecto. Se añade una vez:

    ```bash
    gh auth refresh -s project
    gh auth status
    ```

    En la línea `Token scopes` de `gh auth status` debe aparecer `'project'`.

> **Antes de exportar la máquina virtual, cierra la sesión de `gh`** con `gh auth logout`, como explica §12: el _token_, ahora con permiso sobre los proyectos, viajaría dentro de la máquina virtual.

## Uso

Los comandos se ejecutan desde esta carpeta, en un terminal de la máquina virtual (no en `workspace`, donde no está `gh`).

### 1. Revisa las fechas

```bash
python3 planificar_eportfolio.py --calendario
```

Muestra la fecha de planteamiento y la fecha límite de cada apartado, y avisa si algo termina después del fin de curso. Con `--csv fechas.csv` las guarda además en un CSV (UTF-8 con BOM, para que Excel y LibreOffice muestren bien las tildes).

### 2. Pruébalo sin tocar _GitHub_

```bash
python3 planificar_eportfolio.py --dry-run
```

Consulta lo que ya existe en _GitHub_ y muestra los comandos que ejecutaría, sin ejecutarlos. Fíjate sobre todo en los _issues_: los que se crearon a mano se reconocen por su **título** (sin tener en cuenta el énfasis de _Markdown_ ni las mayúsculas), y deben salir como `ya existe`. Si alguno sale como `se crea` y ya está en el repositorio del grupo, cambia allí su título por el del encabezado del `.md` antes de la ejecución real, o se duplicará.

Con `--sin-conexion`, no consulta _GitHub_ y supone que no existe nada: sirve para probar el _script_ en un equipo sin `gh`.

### 3. El proyecto de referencia

```bash
python3 planificar_eportfolio.py --destino marcapersonalfp2627
```

Al terminar, muestra la dirección del proyecto. Configura en él las vistas (siguiente apartado).

### 4. Los proyectos de los grupos

```bash
python3 planificar_eportfolio.py
```

Procesa todos los destinos: el de referencia (que ya está al día) y los cinco grupos. Con `--destino` se elige uno o varios:

```bash
python3 planificar_eportfolio.py --destino "26_27_Grupo 1" --destino "26_27_Grupo 2"
```

Si el _script_ se detiene por un error, corrige la causa y vuelve a ejecutarlo: continúa donde lo dejó.

## Las vistas, a mano

La API de _GitHub_ no permite crear las vistas de un proyecto, así que se configuran desde la web, una sola vez, en el proyecto de referencia:

1. Abre el proyecto y pulsa **New view** → **Roadmap**.
2. En el menú de la vista, **Date fields**: _Start date_ = `Planteamiento` y _Target date_ = `Fecha límite`.
3. Si quieres, agrupa por `Bloque` (**Group by**) y elige la escala (**Zoom level**: _Month_ o _Quarter_).
4. Cambia el nombre de la vista (por ejemplo, "Calendario") y guárdala (**Save changes**).

Los proyectos de los grupos que se creen después la tendrán ya, porque se copian del de referencia. Los que existieran antes, hay que configurarlos igual.

## Cuándo volver a ejecutarlo

- **Cambia el calendario** (un festivo, las horas de un apartado, la fecha real de un planteamiento): corrige `planificacion.toml` y ejecútalo. Solo cambian las fechas afectadas y la tabla del README del proyecto.
- **Se redacta el _issue_ de un apartado** (por ejemplo, 5.6): en su apartado de `planificacion.toml`, escribe `issue = "RA5_6_….md"` y quita `borrador = true`. Al ejecutarlo, crea el _issue_ (y sus _sub-issues_, si los tiene) en cada repositorio, lo añade al proyecto y borra el borrador.
- **Se planifica un bloque nuevo**: añade sus apartados en `[[bloques.apartados]]`, con sus horas y su `issue` o `borrador = true`. El borrador único del bloque desaparece, y cada apartado lleva su ejercicio.

## Lo que no hace

- **No borra** _issues_ ni proyectos. De los elementos del proyecto, solo borra **sus propios borradores** (los reconoce por un comentario oculto en el texto) cuando ya no corresponden a ningún ejercicio de `planificacion.toml`: porque su _issue_ ya existe, porque su bloque ya tiene apartados o porque se ha quitado. Un _issue_ cuyo apartado se quita de `planificacion.toml` se queda en el proyecto con sus últimas fechas.
- **No cambia el texto de un _issue_ que ya existe**: si se corrige el `.md`, hay que copiar el cambio a mano en cada repositorio.
- **No crea las vistas** (ver arriba), ni campos de tipo iteración: bastan los dos de fecha.
- **No añade opciones nuevas al campo `Bloque`**: si aparece un bloque que no estaba, avisa, y la opción se añade a mano en la configuración del proyecto.
