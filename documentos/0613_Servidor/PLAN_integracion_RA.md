# Plan de integración normativa ↔ práctica guiada (REA 0613) · v3

**Objetivo:** alinear los Resultados de Aprendizaje (RA) y criterios de evaluación (CE) del módulo *Desarrollo web en entorno servidor* con la práctica guiada del REA *marcapersonalFP*, reflejando la secuencia lógica de los RA e integrando los fundamentos de PHP (RA2–RA4) dentro de la propia práctica.

> **Cambios respecto a la v2:** el antiguo Bloque 4 (RA4 + RA5, 36 h) se divide en dos bloques, uno por RA, de modo que el **Bloque _n_ corresponde siempre al RA _n_** (9 bloques, con el Bloque 1 fuera del REA); se reajustan las horas (Bloque 2: 12 → 8 h; Acceso a datos: 28 → 32 h); se pasa a **Laravel 13**; y se actualiza el flujo de contribución (§6).

## Datos confirmados

- **Repo REA (docs):** personal `github.com/albsierra/marcapersonalFP_REA` (`origin`) y global `github.com/2DAW-CarlosIII/marcapersonalFP_REA` (`upstream`, el que usa el alumnado). Mismos SHAs en ambos (ver §6).
- **Repo de código Laravel:** uno por curso, creado por el profesor en la organización y *forkeado* por el alumnado (flujo descrito en [2.1.1 Desarrollo colaborativo con GitHub](./0211_desarrolloColaborativoGitHub.md)). Curso 2026/27: `github.com/2DAW-CarlosIII/marcapersonalfp2627` — Laravel **13**, PHP **8.4** (composer `^8.3`), Breeze **2.4** (pila _Blade_), Sanctum **4.3**. Referencia del curso anterior: `marcapersonalfp2526` (Laravel 12, Breeze 2.3, Sanctum 4).
- **Modelos:** los definidos en `materiales/swagger/marcapersonalFP_api.yaml` (`Ciclo`, `Curriculo`, `FamiliaProfesional`, `Proyecto`, `User`, `Reconocimiento`, `Actividad`, `Competencia`, `Empresa`, `Idioma`…), según los vaya necesitando cada bloque.
- **Decisión aceptada:** RA4 en **PHP nativo** (sesiones/cookies/login), en su propio bloque, + **refuerzo en Laravel** (Bloque 5).
- **Carga lectiva Murcia:** ~**150 h**; ver la distribución adaptada de §1.
- **Ejecución:** en **Claude Code**, sobre el repo, fichero a fichero (§6).
- **Fuera de alcance — Bloque 1 (RA1, Selección de arquitecturas y herramientas):** se imparte con recursos externos vivos y no se desarrolla como contenido del REA. Recursos usados: [Stack Overflow Survey](https://survey.stackoverflow.co/), [índice TIOBE](https://www.tiobe.com/tiobe-index/), [índice PYPL](https://pypl.github.io/PYPL.html), [StackShare](https://stackshare.io), [CNCF Landscape](https://landscape.cncf.io/).

---

## 1. Distribución horaria: oficial (Murcia) y adaptada al REA

La distribución oficial de Murcia tiene 8 bloques y agrupa RA4 y RA5 en su bloque 4. El REA usa 9 bloques, **uno por RA**:

| Bloque REA | Título | RA | Horas | Bloque oficial (h) |
|-----------:|--------|----|------:|--------------------|
| 1 | Selección de arquitecturas y herramientas *(fuera del REA: recursos externos)* | RA1 | 8 | 1 (8) |
| 2 | Inserción de código en páginas web | RA2 | 8 | 2 (12) |
| 3 | Programación basada en lenguajes de marcas con código embebido | RA3 | 24 | 3 (24) |
| 4 | Desarrollo de aplicaciones web utilizando código embebido | RA4 | 10 | 4 (36), junto con RA5 |
| 5 | Generación dinámica de páginas web: introducción a Laravel | RA5 | 26 | 4 (36), junto con RA4 |
| 6 | Acceso a almacenes de datos | RA6 | 32 | 5 (28) |
| 7 | Servicios web reutilizables | RA7 | 28 | 6 (28) |
| 8 | Aplicaciones web dinámicas | RA8 | 6 | 7 (6) |
| 9 | Aplicaciones web híbridas | RA9 | 6 | 8 (6) |
| | **Total** | | **148** | **148** |

**Reajuste de horas:** el alumnado llega con conocimientos previos de programación, así que el Bloque 2 baja de 12 a 8 h; esas 4 h pasan al de acceso a datos, que asume los modelos _Eloquent_ (ver §2).

**Lectura clave:** los bloques 2, 3 y 4 (≈ **42 h**, casi un tercio del módulo) son **programación embebida / PHP plano**. El REA original apenas los cubría: ahí está el grueso del trabajo nuevo.

---

## 2. Tensión secuencia-RA ↔ lógica Laravel (resuelta en la v3)

La normativa pone RA4 (estado/autenticación) antes de RA5 (separación lógica/presentación, frameworks). En Laravel lo natural es enseñar el MVC primero. La distribución oficial lo resolvía fundiendo RA4 y RA5 en un único bloque de 36 h. La v3 los **separa**:

- **Bloque 4 (RA4)**, en PHP plano: *cookies*, sesiones, *login* básico y depuración con Xdebug. Cierra _marcapersonalFP v0_ (`vanilla_php`): el alta de 3.8 conserva lo dado de alta en la sesión y redirige con un mensaje (patrón POST/Redirect/GET), y aparece un área privada con *login*.
- **Bloque 5 (RA5)**, con Laravel: empieza por las ventajas de separar lógica y presentación (RA5 a), justo después de haber sufrido una _v0_ que las mezcla; las sesiones, el *middleware* `auth` y _Breeze_ refuerzan RA4 dentro del framework (RA5 f: "mantenimiento de estado y separación de la lógica de negocio").

Motivos: la evaluación en FP es por RA (cada bloque tiene su cierre y sus instrumentos); se respeta el orden normativo; y el nombre de los ficheros (`RAn_x_…`) coincide con el número de bloque en todo el REA.

**Decisiones de diseño del Bloque 5 (Laravel):**

- **Sin base de datos propia hasta el Bloque 6.** El proyecto usa la base _SQLite_ que _Laravel_ crea por defecto; _MariaDB_ se configura al empezar el Bloque 6.
- **_Breeze_ (pila _Blade_) como caja negra:** se instala y se explica qué automatiza frente al *login* nativo del Bloque 4. Lo que exige migraciones o *seeders* (campos propios de `users`, usuarios de prueba) pasa al Bloque 6. Se prefieren a los nuevos *starter kits* de _Laravel_ porque estos solo se eligen al crear el proyecto, se apoyan en _Fortify_ (lógica dentro de `vendor`) y sustituyen _Blade_ por _Inertia_/_TypeScript_ o _Livewire_; además, los capítulos de API (Bloque 7) y _React-Admin_ (Bloque 8) dependen de _Breeze_.
- **Hilo `Curriculo` + patrón repositorio:** las clases de `vanilla_php/src/` pasan a `app/` con espacio de nombres (autoload de _Composer_); los datos se sirven a través de un repositorio con una implementación sobre array en el Bloque 5, sustituida por una implementación _Eloquent_ en el Bloque 6 sin tocar los controladores. `Proyecto` sigue siendo el ejemplo guiado de los ejercicios (array en el controlador → BD en el Bloque 6 → _React-Admin_).
- **Proyecto `~/Documentos/laravel/marcapersonalfp`**, servido en `http://marcapersonalfp.test`; el sitio por defecto de _nginx_ sigue sirviendo `vanilla_php`.

---

## 3. Estructura del REA reorganizada (mapeo a los 9 bloques)

| Bloque (RA · h) | Contenido del REA | Procedencia |
|---|---|---|
| **1 · RA1 · 8h** | *Fuera del REA.* Se imparte con recursos externos (Stack Overflow Survey, TIOBE, PYPL, StackShare, CNCF Landscape). El REA solo mantiene la instalación del entorno como prerrequisito | — |
| **2 · RA2 · 8h** | PHP embebido en HTML; etiquetas `<?php ?>`; tipos y conversiones; variables/constantes; operadores; ámbitos | **Nuevo** |
| **3 · RA3 · 24h** | Decisión, bucles, arrays/tipos compuestos, funciones; **POO en PHP**; formularios y procesamiento (`$_GET`/`$_POST`), validación; comentarios | **Nuevo** |
| **4 · RA4 · 10h** | Mantenimiento del estado (*cookies*, sesiones), autenticación nativa (*login*/*logout*, `password_hash`), depuración con Xdebug; proyecto: área privada de _v0_ | **Nuevo** |
| **5 · RA5 · 26h** | *Laravel:* instalación, rutas, Artisan, vistas/Blade/layout, controladores, middleware, redirecciones, formularios/datos de entrada/validación, **Breeze** (control de usuarios); separación lógica/presentación (MVC), **POO y patrones** (Singleton, repositorio, DAO) | Actuales cap. 2, 3 y 5 + patrones **nuevo** |
| **6 · RA6 · 32h** | Configuración BD (MariaDB), migraciones, Schema Builder, **Eloquent/ORM**, seeding, Query Builder, relaciones (1:1, 1:N, N:M), transacciones y **bloqueos** | Actuales cap. 4 y 8 (+ bloqueos) |
| **7 · RA7 · 28h** | API y REST, esqueleto de la API, controladores de recursos, autenticación de API (tokens/sesiones), autorización (gates/policies/roles), manejo de ficheros en la API, **documentación OpenAPI/Swagger** | Actuales cap. 6 (REST), 7, 9, 10, 11 |
| **8 · RA8 · 6h** | React-Admin / Inertia, validación de formularios, **internacionalización (i18n)**, **TDD** | React-Admin del actual cap. 6 (+ i18n, TDD) |
| **9 · RA9 · 6h** | Consumo de APIs externas, reutilización, repositorios a medida, correo, **introducción a Big Data / inteligencia de negocios** | Actuales cap. 12 y 13 (+ Big Data/BI) |

Hilo conductor: **marcapersonalFP v0 en PHP plano (bloques 2–4) → reconstrucción con Laravel (bloque 5+)**, con la frase puente "esto que hicimos a mano, el framework lo automatiza así".

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

**Bloque 4 — RA4 (aplicaciones web con código embebido).**

| CE | Contenido |
|----|-----------|
| a | Mecanismos para mantener información de un cliente concreto (*cookies*, sesiones) y sus ventajas |
| b | Mantenimiento del estado con `$_SESSION` |
| c | Almacenamiento y recuperación de información en el cliente (*cookies*) |
| d, e | Mecanismos de autenticación; *login* nativo (`password_hash`/`password_verify`, `session_regenerate_id`) |
| f | Herramientas de depuración (Xdebug) y prueba (PHPUnit) |

**Bloque 5 — RA5 (separación lógica/presentación).** Separación lógica (a, b), controles/vistas dinámicas (c, d), configuración (e), estado + lógica (f: sesiones, *middleware* `auth`, Breeze), **POO y patrones (g)**, prueba y documentación (h).

**Huecos a rellenar (marcados en la matriz v1):** POO/patrones (RA5 g), **bloqueos** de BD (RA6), **i18n** y **TDD** (RA8), **Big Data/BI** (RA9), y documentación OpenAPI explícita (RA7).

---

## 5. Plan de Pull Requests

*(El bloque 1 / RA1 queda fuera: no genera PR.)*

1. **PR 0** — Este plan + `README` de la nueva estructura de bloques (sin mover aún contenido). *Base de acuerdo.* ✅
2. **PR 1** — Bloque 2 (RA2): fundamentos de PHP embebido. marcapersonalFP v0 (listado). ✅
3. **PR 2** — Bloque 3 (RA3): estructuras, POO y formularios en PHP. v0 (alta + validación). ✅
4. **PR 3** — Bloques 4 y 5: RA4 nativo (cierre de _v0_) + reescritura de los capítulos Laravel (rutas/controladores/vistas/Breeze) + POO/patrones.
5. **PR 4** — Bloques 6 y 7: reubicación de BD/relaciones y API/servicios a la nueva numeración + modelos Eloquent + bloqueos + doc OpenAPI.
6. **PR 5** — Bloques 8 y 9: React-Admin, i18n, TDD, APIs externas, Big Data/BI.

Cada PR es revisable y reversible por separado. Los alumnos usan la rama estable; el trabajo vive en ramas de *feature*.

---

## 6. Flujo de trabajo

- **Ramas** en el repo personal (`PR1`, `PR2`, `PR3`…), creadas desde `master`. Historia lineal, sin *merge commits*: la integración se hace con *fast-forward* desde una máquina con permiso de escritura en los dos repos, empujando los mismos SHAs a `origin/master` y `upstream/master`. Nunca "Rebase and merge" ni "Squash" en GitHub, ni *rebase* de ramas ya publicadas.
- **Fichero a fichero:** se redacta cada apartado (`.md` + test + presentación _RevealJS_ + enlace en el README), se revisa, y solo entonces se hace **un commit por apartado** con todo junto.
- **Tests:** reales y enlazados (nunca embebidos), uno por fichero de contenido, con el comando de ese fichero en cada `.md` y el comando agregado del bloque en el último. Bloques 2–4: `materiales/ejercicios-vanilla/tests/`, con `vendor/bin/phpunit`; desde el Bloque 5: `materiales/ejercicios-laravel/tests/Feature/`, con `php artisan test --filter=…`. La cifra de aserciones documentada se verifica siempre con una ejecución real.

---

## 7. Mejoras futuras

Ideas detectadas durante el desarrollo que no se abordan ahora. Al resolver una, se borra de la lista y se indica en el mensaje del commit.

- **Ejercicios que modifiquen, no solo que reproduzcan.** En los Bloques 2 y 3, los ejercicios piden sobre todo copiar y probar el código expuesto en el apartado. Conviene añadir en cada apartado al menos un ejercicio que obligue a **modificarlo o ampliarlo** (otro campo, otra regla, otro caso), con su comprobación en el test.
- **_Traits_ en la POO de 3.5.** [3.5. Programación Orientada a Objetos](./RA3_5_poo.md) trata herencia e interfaces, pero no los **_traits_** (`trait`, `use` dentro de una clase), el mecanismo de PHP para reutilizar métodos entre clases que no comparten herencia. Conviene introducirlos allí (por ejemplo, entre "Herencia" e "Interfaces", con un ejemplo del dominio), porque _Laravel_ los usa constantemente en los modelos: `use HasFactory;` (Bloque 6, [Modelos de datos mediante ORM](./044_modelosORM.md)) o `use HasApiTokens;` (Bloque 7, [Autenticación por tokens](./091_autenticacionTokens.md)).
- **Desbordes horizontales en presentaciones del Bloque 3.** Diapositivas más anchas que los 960 px de _RevealJS_: 3.1 (diapositiva 12, 970 px), 3.6 (diapositiva 18, 1023 px) y 3.8 (diapositiva 14, 1128 px). Las tablas con la clase `sin-salto` y la primera columna de cualquier tabla (`tbody th`) no admiten saltos de línea. Desde el Bloque 4, la validación de las presentaciones mide el ancho además del alto.
- **Contenidos de RA4 en el currículo de Murcia que el REA trata en otros bloques.** El apartado d) del currículo ("Desarrollo de aplicaciones web utilizando código embebido") incluye "Manejo de ficheros. Subida y manipulación" y "Envíos de correos electrónicos con documentos adjuntos". En el REA están en los Bloques 7 ([Manejo de ficheros](./11_manejarFicheros.md)) y 9 ([Correo](./13_email.md)). Una opción, descartada por ahora para 4.4: guardar los currículos en un fichero JSON compartido (con `LOCK_EX`, que anticipa los bloqueos de RA6) y subir el PDF del campo `texto_curriculum`.
