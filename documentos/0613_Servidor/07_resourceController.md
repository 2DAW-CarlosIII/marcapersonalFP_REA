# Controladores de Recursos

## 1. Introducción

Un controlador de recursos **API** en _Laravel_ es similar a un controlador de recursos regular, pero está diseñado específicamente para manejar las solicitudes _API_. No incluye los métodos `create` y `edit` porque en una _API_ típicamente no se manejan formularios _HTML_. Aquí está la correspondencia de los métodos para un controlador de recursos _API_:

- `index`: Maneja una solicitud GET para recuperar todos los recursos.
- `store`: Maneja una solicitud POST para crear un nuevo recurso.
- `show`: Maneja una solicitud GET para mostrar un recurso específico.
- `update`: Maneja una solicitud PUT o PATCH para actualizar un recurso existente.
- `destroy`: Maneja una solicitud DELETE para eliminar un recurso existente.

Estos métodos corresponden a las operaciones **CRUD** (Crear, Leer, Actualizar, Borrar) en una **API RESTful**.
