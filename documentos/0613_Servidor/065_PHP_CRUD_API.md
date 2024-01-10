# Crear una CRUD API

Al iniciar la aplicación React y acceder a la ruta `Dashboard`, nos encontramos con un mensaje de error, que nos indica que no es posible recuperar los datos de la API, ya que todavía no hemos creado ningún _endpoint_ de esa _API_.

Existen buenos tutoriales en Internet para crear una _API_ con las operaciones básicas de _CRUD_ con _Laravel_:

- [Laravel 8 REST API CRUD Tutorial by Example App with Bootstrap 4 and MySQL](https://www.techiediaries.com/laravel-8-rest-api-crud-mysql/)
- [Create PHP Laravel 10 CRUD Web App with MySQL](https://www.positronx.io/php-laravel-crud-operations-mysql-tutorial/)
- [How to create REST API CRUD in Laravel 10](https://medium.com/@miladev95/how-to-create-rest-api-crud-in-laravel-10-8a5d09cd7901)

El principal problema de los anteriores tutoriales es que se restringen a una tabla y deberíamos replicarlos para cada una de las tablas que nosotros vamos a utilizar y nos gustaría poner a disposición del módulo de **Desarrollo Web en Entorno Cliente** los _endpoints_ de gestión de todas las tablas de nuestra base de datos en el menor tiempo posible.

# PHP-CRUD-API

_PHP-CRUD-API_ es un script _PHP_, de un solo archivo, que añade una **API REST** a una base de datos _MySQL/MariaDB_, _PostgreSQL_, _SQL Server_ o _SQLite_.

## Instalación

Para añadir esta librería de _API_ automática, debemos ejecutar, en la carpeta de nuestro proyecto, los siguientes comando:

```bash
composer require symfony/psr-http-message-bridge
composer require laminas/laminas-diactoros
composer require mevdschee/php-crud-api
```

Añadimos el siguiente contenido al final del fichero `routes/api.php`:

```php
Route::any('/{any}', function (ServerRequestInterface $request) {
    $config = new Config([
        'address' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'basePath' => '/api',
    ]);
    $api = new Api($config);
    $response = $api->handle($request);

    try {
        $records = json_decode($response->getBody()->getContents())->records;
        $response = response()->json($records, 200, $headers = ['X-Total-Count' => count($records)]);
    } catch (\Throwable $th) {

    }
    return $response;

})->where('any', '.*');
```

Para que esas peticiones sean convenientemente ejecutadas, debemos añadir al principio del fichero `routes/api.php` las librerías necesarias:

```php
use Psr\Http\Message\ServerRequestInterface;
use Tqdev\PhpCrudApi\Api;
use Tqdev\PhpCrudApi\Config\Config;
```

Reemplazamos la cadena _php-crud-api_ en el código anterior para que coincida con el nombre de usuario, contraseña y base de datos de nuestra configuración (preferiblemente leyéndolos de las variables de entorno).

Ahora, deberíamos poder lanzar peticiones a la API, utilizando _Swuagger UI_, que se encuentra en la dirección [http://localhost:5555](http://localhost:5555). Para ello, deberemos elegir el archivo [`marcapersonalFP_api.yaml`](https://raw.githubusercontent.com/2DAW-CarlosIII/marcapersonalFP_REA/master/documentos/0613_Servidor/materiales/swagger/marcapersonalFP_api.yaml), como fuente para Explorar y _http://marcapersonalFP.test/api/records - Entorno React_ como servidor.

Para no tener problemas con _CORS_, debemos asignar a la variable de entorno `FRONTEND_URL` la dirección de _Swagger UI_:

```bash
FRONTEND_URL=http://localhost:5555
```
