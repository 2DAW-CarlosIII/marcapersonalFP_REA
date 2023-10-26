# 1. Introducción
## ¿Qué es Laravel?

Laravel es un _framework_ de código abierto para el desarrollo de aplicaciones web con PHP, que posee una sintaxis simple, expresiva y elegante. Fue creado en 2011 por Taylor Otwell, inspirándose en Ruby on Rails y Symfony, de los cuales ha adoptado sus principales ventajas.

Laravel facilita el desarrollo simplificando el trabajo con tareas comunes como la _autenticación_, el _enrutamiendo_, gestión sesiones, el almacenamiento en caché, etc. Algunas de las principales características y ventajas de Laravel son:

- Esta diseñado para desarrollar bajo el patrón **MVC (modelo - vista - controlador)**, centrándose en la correcta separación y modularización del código. Lo que facilita el trabajo en equipo, así como la claridad, el mantenimiento y la reutilización del código.
- Integra un sistema ORM de mapeado de datos relacional llamado **Eloquent** aunque también permite la construcción de consultas directas a base de datos mediante su **Query Builder**.
- Permite la gestión de bases de datos y la manipulación de tablas desde código, manteniendo un control de versiones de las mismas mediante su sistema de **Migraciones**.
- Utiliza un sistema de plantillas para las vistas llamado **Blade**, el cual hace uso de la cache para darle mayor velocidad. Blade facilita la creación de vistas mediante el uso de **layouts**, **herencia** y **secciones**.
- Facilita la extensión de funcionalidad mediante paquetes o librerías externas. De esta forma es muy sencillo añadir paquetes que nos faciliten el desarrollo de una aplicación y nos ahorren mucho tiempo de programación.
- Incorpora un intérprete de línea de comandos llamado **Artisan** que nos ayudará con un montón de tareas rutinarias como la creación de distintos componentes de código, trabajo con la base de datos y migraciones, gestión de rutas, cachés, colas, tareas programadas, etc.

## MVC: Modelo - Vista - Controlador

El modelo–vista–controlador (MVC) es un patrón de arquitectura de software que separa los datos y la lógica de negocio de una aplicación de la interfaz de usuario y el módulo encargado de gestionar los eventos y las comunicaciones. Para ello MVC propone la construcción de tres componentes distintos que son el modelo, la vista y el controlador, es decir, por un lado define componentes para la representación de la información, y por otro lado para la interacción del usuario. Este patrón de arquitectura de software se basa en las ideas de reutilización de código y la separación de conceptos, características que buscan facilitar la tarea de desarrollo de aplicaciones y su posterior mantenimiento.

De manera genérica, los componentes de **MVC** se podrían definir como sigue:

- El **Modelo**: Es la representación de la información con la cual el sistema opera, por lo tanto gestiona todos los accesos a dicha información, tanto consultas como actualizaciones. Las peticiones de acceso o manipulación de información llegan al 'modelo' a través del 'controlador'.
- El **Controlador**: Responde a eventos (usualmente acciones del usuario) e invoca peticiones al 'modelo' cuando se hace alguna solicitud de información (por ejemplo, editar un documento o un registro en una base de datos). Por tanto se podría decir que el 'controlador' hace de intermediario entre la 'vista' y el 'modelo'.
- La **Vista**: Presenta el 'modelo' y los datos preparados por el controlador al usuario de forma visual. El usuario podrá interactuar con la vista y realizar otras peticiones que se enviarán al controlador.

Además, en Laravel cumple los estándares: [PSR-4](http://www.php-fig.org/psr/psr-4/) para la carga automática de clases a partir de su ruta de archivos, y [PSR-12](http://www.php-fig.org/psr/psr-12/) como guía de estilo del código fuente.
