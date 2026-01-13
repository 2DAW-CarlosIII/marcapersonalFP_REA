# Introducción

En los anteriores capítulos, hemos visto cómo crear una aplicación web con Laravel, utilizando el patrón MVC, para permitir la  gestión de los datos utilizados en la web _marcapersonalfp.es_.

Al finalizar el trimestre anterior, conseguimos una web funcional, que permitía _autenticación_, con la que se accedía a un _dashboard_ de gestión. En ese momento, el _dashboard_ se limitaba a la gestión del perfil del propio usuario. En este trimestre, vamos a dotar de mayor funcionalidad a ese _dashboard_, creando una **API** para la administración de todos los datos de nuestra aplicación, que será consumida por una aplicación _REACT JS_.

Para la definición de la API, hemos utilizado la herramienta [Swagger](https://swagger.io/), que nos permite definir la API de forma sencilla, y nos genera la documentación de la misma. El resultado es un [fichero en formato _YAML_](./materiales/swagger/marcapersonalFP_api.yaml).

Para editar este fichero, podemos utilizar _Swagger Editor_, y para probar su funcionalidad, utilizaremos _Swagger UI_.

Para poder utilizar _Swagger_ tenemos dos opciones:

1.- Integrar _Swagger_ en el proyecto _Laravel_
2.- Levantar el contenedor de _Swagger_ que incluye _Laradock_.

## Integrar _Swagger_ en el proyecto _Laravel_

Para integrar _Swagger_ en un proyecto _Laravel_, debemos instalar el paquete [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger), siguiendo las instrucciones:

```bash
composer require "darkaonline/l5-swagger"
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

Posteriormente, editamos el archivo de configuración `config/l5-swagger.php` , asegurándonos de configurar correctamente las siguientes opciones:

```php
                /*



                 * File name of the generated json documentation file


                 */


                'docs_json' => 'marcapersonalFP_api.json',





                /*


                 * File name of the generated YAML documentation file


                 */


                'docs_yaml' => 'marcapersonalFP_api.yaml',





                /*


                 * Set this to `json` or `yaml` to determine which documentation file to use in UI


                 */


                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'yaml'),
```

Por último, creamos la carpeta `storage/api-docs` y, en ella, colocamos el fichero _YAML_ de la API, que se encuentra en la ruta [https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/refs/heads/master/documentos/0613_Servidor/materiales/swagger/marcapersonalFP_api.yaml](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/refs/heads/master/documentos/0613_Servidor/materiales/swagger/marcapersonalFP_api.yaml)

## Levantar el contenedor de _Swagger_ que incluye _Laradock_.

Vamos a levantar el contenedor _Swagger UI_ ejecutando el siguiente comando desde el directorio en el que tenemos `laradock`:

```bash
docker compose up -d swagger-ui
```

_Swagger UI_ estará disponible en la dirección [http://localhost:5555](http://localhost:5555) o [http://marcapersonalfp.test:5555](http://marcapersonalfp.test:5555).

Una vez abierto _Swagger UI_ en el navegador, podemos **explorar** el fichero _YAML_ de la API, que se encuentra en la ruta [https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/refs/heads/master/documentos/0613_Servidor/materiales/swagger/marcapersonalFP_api.yaml](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/refs/heads/master/documentos/0613_Servidor/materiales/swagger/marcapersonalFP_api.yaml).
