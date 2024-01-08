# Introducción

En los anteriores capítulos, hemos visto cómo crear una aplicación web con Laravel, utilizando el patrón MVC, para permitir la  gestión de los datos utilizados en la web _marcapersonalfp.es_.

Al finalizar el trimestre anterior, conseguimos una web funcional, que permitía _autenticación_, con la que se accedía a un _dashboard_ de gestión. En ese momento, el _dashboard_ se limitaba a la gestión del perfil del propio usuario. En este trimestre, vamos a dotar de mayor funcionalidad a ese _dashboard_, creando una **API** para la administración de todos los datos de nuestra aplicación, que será consuminada por una aplicación _REACT JS_.

Para la definición de la API, hemos utilizado la herramienta [Swagger](https://swagger.io/), que nos permite definir la API de forma sencilla, y nos genera la documentación de la misma. El resultado es un [fichero en formato _YAML_](./materiales/swagger/marcapersonalFP_api.yaml).

Para editar este fichero, podemos utilizar _Swagger Editor_, y para probar su funcionalidad, utilizaremos _Swagger UI_. Para levantar ambos contenedores, ejecutaremos el siguiente comando desde el directorio en el que tenemos `laradock`:

```bash
docker compose up -d swagger-editor swagger-ui
```

_Swagger Editor_ estará disponible en la dirección [http://localhost:5151](http://localhost:5151), y _Swagger UI_ en [http://localhost:5555](http://localhost:5555).
