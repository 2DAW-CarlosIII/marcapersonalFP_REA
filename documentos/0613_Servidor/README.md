# Desarrollo Web en Entorno Servidor

Los contenidos que se presentan a continuación constituyen un desarrollo que, inicialmente, se basó en el libro [Laravel 5](https://ajgallego.gitbooks.io/laravel-5/), escrito por Antonio Javier Gallego Sánchez.

> **Estructura por Resultados de Aprendizaje.** El material está organizado según los **bloques de contenido** del módulo (currículo de la Región de Murcia, ~150 h), de modo que cada bloque se corresponde con un Resultado de Aprendizaje (RA) y con su carga horaria orientativa. El plan de esta reorganización está en [PLAN_integracion_RA.md](./PLAN_integracion_RA.md).
>
> **Leyenda:** 🆕 = contenido nuevo pendiente de desarrollo · 🔁 = contenido existente pendiente de reubicar/renumerar a su bloque.

**Antes de empezar:** todo el módulo comparte el mismo entorno (Docker + Laradock). Prepáralo siguiendo [0. Instalación y preparación del entorno](./00_instalacionEntorno.md) — en la máquina virtual del módulo ya viene preinstalado.

| Bloque | Resultado de Aprendizaje | Horas |
|-------:|--------------------------|------:|
| 1 | Selección de arquitecturas y herramientas (RA1) — *fuera del REA* | 8 |
| 2 | Inserción de código en páginas web (RA2) | 12 |
| 3 | Programación con código embebido (RA3) | 24 |
| 4 | Aplicaciones web con código embebido: introducción a Laravel (RA4 + RA5) | 36 |
| 5 | Acceso a almacenes de datos (RA6) | 28 |
| 6 | Servicios web reutilizables (RA7) | 28 |
| 7 | Aplicaciones web dinámicas (RA8) | 6 |
| 8 | Aplicaciones web híbridas (RA9) | 6 |

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
3. 🆕 Tipos de datos y conversiones; variables y constantes; operadores
4. 🆕 Ámbitos de las variables
5. 🆕 *Proyecto:* listado de currículos desde un array

## Bloque 3. Programación basada en lenguajes de marcas con código embebido (RA3)

1. 🆕 Tomas de decisión
2. 🆕 Bucles
3. 🆕 Arrays y tipos compuestos
4. 🆕 Funciones
5. 🆕 Programación Orientada a Objetos en PHP
6. 🆕 Formularios web: recuperación (`$_GET`/`$_POST`), procesamiento y validación
7. 🆕 Comentarios
8. 🆕 *Proyecto:* alta y validación de un currículo

## Bloque 4. Desarrollo de aplicaciones web con código embebido: introducción a Laravel (RA4 + RA5)

### Puente: estado y autenticación en PHP nativo (RA4)

1. 🆕 Sesiones y cookies nativas; *login* básico
2. 🆕 Depuración con XDebug

### Introducción a Laravel (RA5)

1. [Instalación](./021_instalacion.md)
    1. [Desarrollo colaborativo con GitHub](./0211_desarrolloColaborativoGitHub.md)
2. [Funcionamiento básico](./022_funcionamientoBasico.md)
3. [Rutas](./023_rutas.md) · [Ejercicios de rutas](./024_ejercicioRutas.md) · [Rutas avanzadas](./025_rutasAvanzadas.md)
4. [Artisan](./026_artisan.md)
5. [Vistas](./027_vistas.md)
    1. [Plantillas mediante Blade](./0271_vistasBlade.md)
    2. [Crear un Layout](./0272_crearLayout.md)
    3. [Ejercicios de vistas](./0273_ejerciciosVistas.md)
6. [Controladores, filtros y formularios](./03_controladoresFiltrosFormularios.md)
    1. [Controladores](./031_controladores.md) · [Ejercicios](./0311_ejerciciosControladores.md)
    2. [Middleware o filtros](./032_middlewares.md)
    3. [Redirecciones](./033_Redirecciones.md)
    4. [Formularios](./034_Formularios.md)
    5. [Datos de entrada](./035_datosEntrada.md)
7. [Control de usuarios con Breeze](./05_autenticacion.md)
    1. [Control de usuarios](./051_Autenticacion.md)
    2. [Ejercicios](./052_ejerciciosUsers.md)
8. 🆕 Separación de la lógica de negocio: POO y patrones de diseño (MVC, Singleton, repositorio, DAO)

## Bloque 5. Acceso a almacenes de datos (RA6)

1. [Base de datos](./04_basesDatos.md)
    1. [Configuración inicial](./041_configuracionInicial.md)
    2. [Migraciones](./042_migraciones.md)
    3. [Schema Builder](./043_schemaBuilder.md)
    4. [Modelos de datos mediante ORM](./044_modelosORM.md)
    5. [Inicialización de la base de datos (Seeding)](./045_databaseSeeding.md)
    6. [Constructor de consultas (Query Builder)](./046_queryBuilder.md)
    7. [Ejercicios](./047_ejerciciosBD.md): [FamiliaProfesional](./0471_BDFamiliaProfesional.md) · [Currículos](./0472_BDCurriculo.md) · [Reconocimientos](./0473_BDReconocimiento.md) · [Actividades](./0474_BDActividad.md) · [Crear/modificar](./0475_crearModificarFamiliaProfesional.md) · [Ficheros](./0476_utilizarFicheros.md)
2. [Relaciones entre modelos](./08_relaciones.md)
    1. [Uno a uno](./081_relaciones_unoAuno.md)
    2. [Uno a muchos](./082_relaciones_unoAmuchos.md)
    3. [Muchos a muchos](./083_relaciones_muchosAmuchos.md)
    4. [Insertar y actualizar con relaciones](./084_DML_Relaciones.md)
    5. [Ejercicios](./085_ejerciciosRelaciones.md)
3. 🆕 Transacciones y **bloqueos** (la sección de transacciones ya existe en [Query Builder](./046_queryBuilder.md); falta bloqueos)

## Bloque 6. Servicios web reutilizables (RA7)

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

## Bloque 7. Aplicaciones web dinámicas (RA8)

1. [React-Admin](./063_React_Admin.md)
2. [React-Admin sobre Inertia](./064_inertia_reactadmin.md)
3. [Personalizar las tablas de React-Admin](./066_componentesRA.md)
4. 🆕 Validación de formularios, internacionalización (i18n) y desarrollo guiado por pruebas (TDD)

## Bloque 8. Aplicaciones web híbridas (RA9)

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
