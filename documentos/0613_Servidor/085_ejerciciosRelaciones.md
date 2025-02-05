# Ejercicios

## Filtrar por id

En muchos de los listados que se pueden realizar con _React-admin_, se utiliza el componente `ReferenceField`, el cual busca registros relacionados en otra tabla. Por ejemplo, el siguiente `ReferenceField` es el que se utilizará para mostrar el nombre de la `familia_profesional` asociada a un `ciclo`:

```jsx
    <ReferenceField label="Familia Profesional" source="familia_id" reference="familias_profesionales">
        <TextField source="nombre" />
    </ReferenceField>
```
Con el componente anterior, conseguimos que se vea el nombre de la `familia_profesional` asociado a cada `ciclo`, como se muestra a continuación:

ID | Cod Ciclo | Cod Familia | Familia Profesional | Grado | Nombre
-|-|-|-|-|-
1 | ACEC2 | ADG | **ACTIVIDADES FÍSICAS Y DEPORTIVAS** | G.M. | Técnico en Actividades Ecuestres | 
2 | ACFI3 | ADG | **ACTIVIDADES FÍSICAS Y DEPORTIVAS** | G.S. | Técnico Superior en Acondicionamiento Físico | 
3 | ACID1 | ADG | **ACTIVIDADES FÍSICAS Y DEPORTIVAS** | BÁSICA | Profesional Básico en Acceso y Conservación en Instalaciones Deportivas | 
4 | EASO3 | ADG | **ACTIVIDADES FÍSICAS Y DEPORTIVAS** | G.S. | Técnico Superior en Enseñanza y Animación Sociodeportiva | 
5 | GMTL2 | ADG | **ACTIVIDADES FÍSICAS Y DEPORTIVAS** | G.M. | Técnico en Guía en el Medio Natural y de Tiempo Libre | 
6 | ADFI3 | AFD | **ADMINISTRACIÓN Y GESTIÓN** | G.S. | Técnico Superior en Administración y Finanzas | 
7 | ASDI3 | AFD | **ADMINISTRACIÓN Y GESTIÓN** | G.S. | Técnico Superior en Asistencia a la Dirección | 
8 | GADM2 | AFD | **ADMINISTRACIÓN Y GESTIÓN** | G.M. | Técnico en Gestión Administrativa | 
9 | INOF1 | AFD | **ADMINISTRACIÓN Y GESTIÓN** | BÁSICA | Profesional Básico en Informática de Oficina | 
10 | SEAD1 | AFD | **ADMINISTRACIÓN Y GESTIÓN** | BÁSICA | Profesional Básico en Servicios Administrativos

Para obtener las `familias_profesionales` asociadas a los `ciclos` mostrados en un listado, _React-admin_ realiza una petición a la _API_ con la siguiente forma:

```
Request URL: http://marcapersonalfp.test/api/v1/familias_profesionales?id[]=1&id[]=2
Request Method: GET
```

Como actualmente los filtros que aplicamos no tienen en cuenta los `id` enviados en la _query_, la _API_ devuelve todos los registros de la tabla `familias_profesionales`, lo que significa que, aunque se soliciten 2, se devuelven las 26 `familias_profesionales` almacenadas en la base de datos.

**Debemos modificar el _middlware_ que aplica los filtros para que, en el caso de que se esté enviando una petición como la anterior, se aplique un filtro por `id` en la consulta y se devuelvan únicamente los registros solicitados**:
```json
[
    {
        "id": 1,
        "codigo": "ADG",
        "nombre": "**ACTIVIDADES FÍSICAS Y DEPORTIVAS**",
        "created_at": null,
        "updated_at": null
    },
    {
        "id": 2,
        "codigo": "AFD",
        "nombre": "**ADMINISTRACIÓN Y GESTIÓN**",
        "created_at": null,
        "updated_at": null
    },
    {
        "id": 3,
        "codigo": "AGA",
        "nombre": "AGRARIA",
        "created_at": null,
        "updated_at": null
    },
    {
        "id": 4,
        "codigo": "ARA",
        "nombre": "ARTES Y ARTESANÍAS",
        "created_at": null,
        "updated_at": null
    },
    {
        "id": 5,
        "codigo": "ARG",
        "nombre": "ARTES GRÁFICAS",
        "created_at": null,
        "updated_at": null
    }
]
```
> Nota: cuando se envía un array de `id` en la query no se envían otros tipos de filtros, por lo que, en ese caso, no es necesario aplicarlos.

# Ejercicio relaciones

Define las relaciones entre las tablas: 

users - ciclos
users - competencias
users - proyectos
users - actividades (la tabla intermedia es reconocimientos)
actividades - competencias
actividades - reconocimientos
proyectos - ciclos

A continuación, modifica la definición del recurso correspondiente a la primera de las dos tablas para que incluya los registros relacionados de la otra tabla.

![Esquema relacional](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/marcapersonalFP.drawio.png)

# Insertar en tablas intermedias

La forma de insertar y actualizar a través de relaciones la puedes consultar en [inserción y actualización a través de relaciones](https://github.com/2DAW-CarlosIII/marcapersonalFP_REA/blob/master/documentos/0613_Servidor/084_DML_Relaciones.md)

Una vez consultada dicha documentación, realiza el siguiente ejercicio con la tabla intermedia expuesta en el título del _issue_.

Actualmente, cuando realizamos el _seed_ de las tablas intermedias, elegimos valores `id` relacionados aleatoriamente de entre los que existen en cada una de las tablas. Ahora que conocemos cómo añadir relaciones entre dos registros utilizando las relaciones, vamos a modificar los _seed_ existentes de las tablas intermedias. Para ello, vamos a coger todos los registros de cada una de las tablas y, por cada uno de ellos, vamos a relacionarle registros aleatorios de la tabla relacionada. El número de registros relacionados serán 0, 1 o 2.

- competencias_actividades
- users_competencias
- users_idiomas
- users_ciclos
- participantes_proyectos
- proyectos_ciclos
