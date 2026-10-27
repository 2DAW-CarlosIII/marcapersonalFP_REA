# Desarrollo Web en Entorno Servidor

Los contenidos que se presentan a continuación constituyen un desarrollo que, inicialmente, se basó en el libro [Laravel 5](https://ajgallego.gitbooks.io/laravel-5/), escrito por Antonio Javier Gallego Sánchez.

> **Estructura por Resultados de Aprendizaje.** El material está organizado según los **bloques de contenido** del módulo (currículo de la Región de Murcia, ~150 h), de modo que cada bloque se corresponde con un Resultado de Aprendizaje (RA) y con su carga horaria orientativa. La distribución oficial agrupa RA4 y RA5 en un único bloque; aquí se separan (Bloques 4 y 5) para que el Bloque _n_ corresponda siempre al RA _n_. El plan de esta reorganización está en [PLAN_integracion_RA.md](./PLAN_integracion_RA.md).
>
> **Leyenda:** 🆕 = contenido nuevo pendiente de desarrollo · 🔁 = contenido existente pendiente de reubicar/renumerar a su bloque.

**Antes de empezar:** todo el módulo comparte el mismo entorno (Docker + Laradock). Prepáralo siguiendo [0. Instalación y preparación del entorno](./00_instalacionEntorno.md) — en la máquina virtual del módulo ya viene preinstalado.

| Bloque | Resultado de Aprendizaje | Horas |
|-------:|--------------------------|------:|
| 1 | Selección de arquitecturas y herramientas (RA1) — *fuera del REA* | 8 |
| 2 | Inserción de código en páginas web (RA2) | 8 |
| 3 | Programación con código embebido (RA3) | 24 |
| 4 | Aplicaciones web con código embebido: estado y autenticación (RA4) | 10 |
| 5 | Generación dinámica de páginas web: introducción a Laravel (RA5) | 26 |
| 6 | Acceso a almacenes de datos (RA6) | 32 |
| 7 | Servicios web reutilizables (RA7) | 28 |
| 8 | Aplicaciones web dinámicas (RA8) | 6 |
| 9 | Aplicaciones web híbridas (RA9) | 6 |

---

## Bloque 1. Selección de arquitecturas y herramientas de programación (RA1)

*Este bloque no se desarrolla como contenido del REA.* Se imparte y evalúa con recursos externos vivos, difícilmente igualables por material estático:

- [Stack Overflow Developer Survey](https://survey.stackoverflow.co/)
- [Índice TIOBE](https://www.tiobe.com/tiobe-index/)
- [Índice PYPL](https://pypl.github.io/PYPL.html)
- [StackShare](https://stackshare.io)
- [CNCF Cloud Native Landscape](https://landscape.cncf.io/)

Material de apoyo conservado: [Introducción](./01_introduccion.md).

## Bloque 2. Inserción de código en páginas web (RA2)

Fundamentos de PHP embebido, construyendo una primera versión de *marcapersonalFP* en PHP plano.

1. [PHP embebido en HTML: etiquetas `<?php ?>` y tecnologías asociadas](./RA2_1_phpEmbebido.md)
2. [Sintaxis, sentencias y salida (`echo`/`print`)](./RA2_2_sintaxisSalida.md)
3. [Tipos de datos y conversiones; variables y constantes; operadores](./RA2_3_tiposVariablesOperadores.md)
4. [Ámbitos de las variables](./RA2_4_ambitos.md)
5. *Proyecto:* [listado de currículos desde un array](./RA2_5_proyectoListado.md)

## Bloque 3. Programación basada en lenguajes de marcas con código embebido (RA3)

1. [Tomas de decisión](./RA3_1_decisiones.md)
2. [Bucles](./RA3_2_bucles.md)
3. [Arrays y tipos compuestos](./RA3_3_arrays.md)
4. [Funciones](./RA3_4_funciones.md)
5. [Programación Orientada a Objetos en PHP](./RA3_5_poo.md)
6. [Formularios web: recuperación (`$_GET`/`$_POST`), procesamiento y validación](./RA3_6_formularios.md)
7. [Comentarios](./RA3_7_comentarios.md)
8. *Proyecto:* [alta y validación de un currículo](./RA3_8_proyectoAltaCurriculo.md)

## Bloque 4. Desarrollo de aplicaciones web utilizando código embebido (RA4)

Cerramos _marcapersonalFP v0_, todavía en PHP plano: mantenimiento del estado, autenticación de usuarios y depuración.

1. [Mantenimiento del estado: *cookies* y sesiones](./RA4_1_estado.md)
2. [Autenticación de usuarios: *login* básico](./RA4_2_login.md)
3. [Depuración con Xdebug](./RA4_3_xdebug.md)
4. *Proyecto:* [área privada de _marcapersonalFP v0_](./RA4_4_proyectoAreaPrivada.md)

## Bloque 5. Generación dinámica de páginas web: introducción a Laravel (RA5)

Reconstruimos _marcapersonalFP_ con _Laravel_, separando la lógica de negocio de la presentación.

1. [Instalación de _Laravel_ y desarrollo colaborativo](./RA5_1_instalacion.md)
2. [Estructura de un proyecto, configuración y _Artisan_](./RA5_2_estructura.md)
3. [Rutas](./RA5_3_rutas.md)
4. [Vistas, _Blade_ y _layouts_](./RA5_4_vistas.md)
5. [Controladores](./RA5_5_controladores.md)
    - Ejercicios colaborativos opcionales: [Reconocimientos](./0363_reconocimientoController.md) · [Actividades](./0364_actividadController.md)
6. [Formularios, datos de entrada, validación y redirecciones](./RA5_6_formularios.md)
7. [_Middleware_ y control de usuarios con _Breeze_](./RA5_7_breeze.md)
8. 🆕 *Proyecto:* _marcapersonalFP_ en _Laravel_. Separación de la lógica de negocio: POO y patrones de diseño (MVC, _Singleton_, repositorio, DAO) (borrador: [Controlador de currículos](./0362_curriculoController.md))

## Bloque 6. Acceso a almacenes de datos (RA6)

1. [Base de datos](./04_basesDatos.md)
    1. [Configuración inicial](./041_configuracionInicial.md)
    2. [Migraciones](./042_migraciones.md)
    3. [Schema Builder](./043_schemaBuilder.md)
    4. [Modelos de datos mediante ORM](./044_modelosORM.md)
    5. [Inicialización de la base de datos (Seeding)](./045_databaseSeeding.md)
    6. [Constructor de consultas (Query Builder)](./046_queryBuilder.md)
    7. [Ejercicios](./047_ejerciciosBD.md): [FamiliaProfesional](./0471_BDFamiliaProfesional.md) · [Currículos](./0472_BDCurriculo.md) · [Reconocimientos](./0473_BDReconocimiento.md) · [Actividades](./0474_BDActividad.md) · [Crear/modificar](./0475_crearModificarFamiliaProfesional.md) · [Ficheros](./0476_utilizarFicheros.md)
    8. 🔁 [Usuarios: migración, registro y _seeder_](./052_ejerciciosUsers.md) (procede de los antiguos ejercicios de _Breeze_; la instalación está en [5.7](./RA5_7_breeze.md))
2. [Relaciones entre modelos](./08_relaciones.md)
    1. [Uno a uno](./081_relaciones_unoAuno.md)
    2. [Uno a muchos](./082_relaciones_unoAmuchos.md)
    3. [Muchos a muchos](./083_relaciones_muchosAmuchos.md)
    4. [Insertar y actualizar con relaciones](./084_DML_Relaciones.md)
    5. [Ejercicios](./085_ejerciciosRelaciones.md)
3. 🆕 Transacciones y **bloqueos** (la sección de transacciones ya existe en [Query Builder](./046_queryBuilder.md); falta bloqueos)

## Bloque 7. Servicios web reutilizables (RA7)

1. [Creación de una API](./06_crearAPI.md)
    1. [Introducción a API y REST](./061_api_y_rest.md)
    2. [Esqueleto de nuestra API](./062_esqueleto_API.md)
    3. [PHP_CRUD_API](./065_PHP_CRUD_API.md)
2. [Controladores de recursos](./07_resourceController.md)
    1. [Controlador de recursos de ciclos](./071_resourceControlerCiclos.md)
    2. [Middleware para adaptar la respuesta](./072_middlewareReactAdmin.md)
    3. [Helper para la búsqueda](./073_helperBusqueda.md)
    4. [Ejercicios](./074_ejerciciosControladoresRecursos.md)
3. [Autenticación de la API](./09_autenticacion.md)
    1. [Por tokens](./091_autenticacionTokens.md)
    2. [Por sesiones](./092_autenticacionSesionAPI.md)
4. [Autorización](./10_autorizacion.md)
    1. [Gates](./101_gates.md) · [Policies](./102_policies.md) · [Roles y permisos](./103_roles.md) · [Ejercicios](./104_ejerciciosAutorizacion.md)
5. [Manejo de ficheros en la API](./11_manejarFicheros.md)
6. 🆕 Documentación del servicio web (OpenAPI/Swagger)

## Bloque 8. Aplicaciones web dinámicas (RA8)

1. [React-Admin](./063_React_Admin.md)
2. [React-Admin sobre Inertia](./064_inertia_reactadmin.md)
3. [Personalizar las tablas de React-Admin](./066_componentesRA.md)
4. 🆕 Validación de formularios, internacionalización (i18n) y desarrollo guiado por pruebas (TDD)

## Bloque 9. Aplicaciones web híbridas (RA9)

1. [Consumiendo API externas](./12_apiExterna.md)
    1. [Ejercicios](./121_ejerciciosAPI.md)
2. [Enviando correos electrónicos](./13_email.md)
    1. [Ejercicios](./131_ejerciciosEmail.md)
3. 🆕 Reutilización de código, repositorios a medida e introducción a Big Data / inteligencia de negocios

---

## Anexos

- [Segunda convocatoria](./14_segundaConvocatoria.md)

## Máquina Virtual

Para el desarrollo de la aplicación se facilita una máquina virtual con Debian, Docker, Visual Studio Code y Laradock preinstalado (PHP, Composer, Node/npm y depuración con XDebug se ejecutan dentro de los contenedores de Laradock, no instalados en el sistema — ver [0. Instalación y preparación del entorno](./00_instalacionEntorno.md)).
