# Plan de integración normativa ↔ práctica guiada (REA 0613) · v2

**Objetivo:** alinear los Resultados de Aprendizaje (RA) y criterios de evaluación (CE) del módulo *Desarrollo web en entorno servidor* con la práctica guiada del REA *marcapersonalFP*, reflejando la secuencia lógica de los RA e integrando los fundamentos de PHP (RA2–RA3) dentro de la propia práctica.

## Datos confirmados

- **Repo REA (docs):** `github.com/albsierra/marcapersonalFP_REA` — flujo **fork + PR**. Base actual **limpia y al día** (ya incorpora patch, numeración y correcciones de enlaces).
- **Repo de código Laravel:** `github.com/2DAW-CarlosIII/marcapersonalfp2526` — Laravel **12**, PHP **8.4** (composer `^8.2`), Breeze **2.3**, Sanctum **4**. Modelos: `Ciclo`, `Curriculo`, `FamiliaProfesional`, `Proyecto`, `User`.
- **Decisión aceptada:** RA4 en **PHP nativo** (sesiones/cookies/login) + **refuerzo en Laravel**.
- **Carga lectiva Murcia:** ~**150 h** (8 bloques), según la distribución oficial de abajo.
- **Ejecución:** en **Claude Code**, sobre el repo, con flujo fork + PR.
- **Fuera de alcance — Bloque 1 (RA1, Selección de arquitecturas y herramientas):** se imparte con recursos externos vivos y no se desarrolla como contenido del REA. Recursos usados: [Stack Overflow Survey](https://survey.stackoverflow.co/), [índice TIOBE](https://www.tiobe.com/tiobe-index/), [índice PYPL](https://pypl.github.io/PYPL.html), [StackShare](https://stackshare.io), [CNCF Landscape](https://landscape.cncf.io/).

---

## 1. Distribución horaria oficial (Murcia) como esqueleto

Usamos los 8 bloques oficiales como columna vertebral del REA (antes que una numeración inventada). Cada bloque se corresponde con un RA:

| Bloque | Título oficial | RA | Horas |
|-------:|----------------|----|------:|
| 1 | Selección de arquitecturas y herramientas *(fuera del REA: recursos externos)* | RA1 | 8 |
| 2 | Inserción de código en páginas web | RA2 | 12 |
| 3 | Programación basada en lenguajes de marcas con código embebido | RA3 | 24 |
| 4 | Desarrollo de Apps Web con código embebido: Introducción a Laravel | RA4 + RA5 | 36 |
| 5 | Acceso a almacenes de datos | RA6 | 28 |
| 6 | Servicios web reutilizables | RA7 | 28 |
| 7 | Aplicaciones web dinámicas | RA8 | 6 |
| 8 | Aplicaciones web híbridas | RA9 | 6 |
| | **Total** | | **148** |

**Lectura clave:** los bloques 2, 3 y el arranque del 4 (≈ **42–44 h**, casi un tercio del módulo) son **programación embebida / PHP**. El REA actual apenas los cubre: ahí está el grueso del trabajo nuevo.

---

## 2. Tensión secuencia-RA ↔ lógica Laravel (resuelta)

La normativa pone RA4 (estado/autenticación) antes de RA5 (MVC/frameworks). En Laravel lo natural es enseñar el MVC primero. La distribución oficial ya lo resuelve: **funde RA4 y RA5 en el bloque 4** ("...utilizando código embebido: Introducción a Laravel"). Aplicamos la decisión acordada: **RA4 se ve primero "a mano" en PHP** (cierre del bloque 3 / puente al 4) y **se refuerza con Breeze** ya dentro de Laravel.

---

## 3. Estructura del REA reorganizada (mapeo a los 8 bloques)

| Bloque (RA · h) | Contenido del REA | Procedencia |
|---|---|---|
| **1 · RA1 · 8h** | *Fuera del REA.* Se imparte con recursos externos (Stack Overflow Survey, TIOBE, PYPL, StackShare, CNCF Landscape). El REA solo mantiene, si acaso, la instalación del entorno como prerrequisito del bloque 4 | — |
| **2 · RA2 · 12h** | PHP embebido en HTML; etiquetas `<?php ?>`; tipos y conversiones; variables/constantes; operadores; ámbitos | **Nuevo** |
| **3 · RA3 · 24h** | Decisión, bucles, arrays/tipos compuestos, funciones; **POO en PHP**; formularios y procesamiento (`$_GET`/`$_POST`), validación; comentarios | **Nuevo** |
| **4 · RA4+RA5 · 36h** | *Puente RA4 nativo:* sesiones/cookies/login en PHP + depuración. *Laravel:* instalación, rutas, Artisan, vistas/Blade/layout, controladores, middleware, redirecciones, formularios/datos de entrada, **Breeze** (control de usuarios); separación lógica/presentación (MVC), **POO y patrones** (Singleton, repositorio, DAO) | RA4 nativo **nuevo** + actuales cap. 2, 3 y 5 |
| **5 · RA6 · 28h** | Configuración BD, migraciones, Schema Builder, **Eloquent/ORM**, seeding, Query Builder, relaciones (1:1, 1:N, N:M), transacciones y **bloqueos** | Actuales cap. 4 y 8 (+ bloqueos) |
| **6 · RA7 · 28h** | API y REST, esqueleto de la API, controladores de recursos, autenticación de API (tokens/sesiones), autorización (gates/policies/roles), manejo de ficheros en la API, **documentación OpenAPI/Swagger** | Actuales cap. 6 (REST), 7, 9, 10, 11 |
| **7 · RA8 · 6h** | React-Admin / Inertia, validación de formularios, **internacionalización (i18n)**, **TDD** | React-Admin del actual cap. 6 (+ i18n, TDD) |
| **8 · RA9 · 6h** | Consumo de APIs externas, reutilización, repositorios a medida, correo, **introducción a Big Data / inteligencia de negocios** | Actuales cap. 12 y 13 (+ Big Data/BI) |

Hilo conductor: **marcapersonalFP v0 en PHP plano (bloques 2–4) → reconstrucción con Laravel (bloque 4+)**, con la frase puente "esto que hicimos a mano, el framework lo automatiza así".

---

## 4. Trazabilidad criterio a criterio (bloques nuevos y huecos)

**Bloque 1 — RA1 (arquitecturas).** *Fuera del alcance del REA:* se evalúa con actividades sobre recursos externos vivos (Stack Overflow Survey, TIOBE, PYPL, StackShare, CNCF Landscape), difícilmente igualables por contenido estático. No se desarrolla en el repositorio.

**Bloque 2 — RA2 (inserción de código).**

| CE | Contenido |
|----|-----------|
| a, b, c | PHP embebido, tecnologías asociadas (PHP; mención ASP/JSP/Python), etiquetas `<?php ?>` |
| d, e, f | Sintaxis, sentencias simples y efectos, directivas (`echo`/`print`) |
| g | Tipos, variables/constantes, operadores |
| h | Ámbitos de las variables |

**Bloque 3 — RA3 (programación embebida).**

| CE | Contenido |
|----|-----------|
| a | Tomas de decisión |
| b | Bucles |
| c | Arrays y tipos compuestos |
| d | Funciones (+ **POO** exigida por el contenido c del currículo) |
| e, f | Formularios y recuperación/procesamiento de datos |
| g | Comentarios |

**Bloque 4 — RA4 + RA5.** RA4: estado (b), almacenamiento en cliente (c), autenticación (d, e), herramientas de depuración (f) → sesiones/cookies nativas + Breeze + XDebug. RA5: separación lógica (a, b), controles/vistas dinámicas (c, d), configuración (e), estado + lógica (f), **POO y patrones (g)**, prueba y documentación (h).

**Huecos a rellenar (marcados en la matriz v1):** POO/patrones (RA5 g), **bloqueos** de BD (RA6), **i18n** y **TDD** (RA8), **Big Data/BI** (RA9), y documentación OpenAPI explícita (RA7).

---

## 5. Plan de Pull Requests (fork + PR contra `albsierra/marcapersonalFP_REA`)

*(El bloque 1 / RA1 queda fuera: no genera PR.)*

1. **PR 0** — Este plan + `README` de la nueva estructura de bloques (sin mover aún contenido). *Base de acuerdo.*
2. **PR 1** — Bloque 2 (RA2): fundamentos de PHP embebido. marcapersonalFP v0 (listado).
3. **PR 2** — Bloque 3 (RA3): estructuras, POO y formularios en PHP. v0 (alta + validación).
4. **PR 3** — Bloque 4: puente RA4 nativo + reubicación de los capítulos Laravel (rutas/controladores/vistas/Breeze) + POO/patrones.
5. **PR 4** — Bloques 5 y 6: reubicación de BD/relaciones y API/servicios a la nueva numeración + bloqueos + doc OpenAPI.
6. **PR 5** — Bloques 7 y 8: React-Admin, i18n, TDD, APIs externas, Big Data/BI.

Cada PR es revisable y reversible por separado. Los alumnos usan la rama estable; el trabajo vive en ramas de *feature*.

---

## 6. Para arrancar la ejecución (Claude Code)

El trabajo se hará en **Claude Code** sobre el repo, con flujo **fork + PR**. Punto de partida recomendado:

- Crear el *fork* de `albsierra/marcapersonalFP_REA` y una rama `integracion-ra`.
- **PR 0**: añadir este plan al repo (p. ej. `documentos/0613_Servidor/PLAN_integracion_RA.md`) y el `README` de la nueva estructura de bloques, sin mover aún contenido, para fijar el esqueleto acordado.
- Continuar por el **bloque de PHP (PR 1, RA2)**, que es el mayor hueco y el arranque del hilo "marcapersonalFP v0 en PHP plano".

Este documento sirve de *brief* para esa sesión de Claude Code.
