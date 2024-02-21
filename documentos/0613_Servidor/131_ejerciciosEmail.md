# Ejercicios Email

## Ejercicio 1

Debemos crear un sistema de autenticación basado en tokens para las empresas. Cuando una empresa recibe el correo electrónico generado en el [capítulo anterior](./13_email.md), debería poder hacer clic en el enlace y autenticarse automáticamente en la aplicación.

### Pasos:

1. Crea una nueva ruta en `routes/api.php` para manejar el enlace de acceso. La ruta debería ser algo como `/api/v1/empresas/acceso/{token}` y debería apuntar al método `accesoPorToken` del controlador `API\EmpresaController`.
2. En `EmpresaController`, define el método `accesoPorToken`. Este método debería buscar una empresa con el token proporcionado. Si la empresa existe, debería autenticar al usuario asociado a la empresa y redirigir a la empresa a la página de inicio.
3. Para autenticar al usuario, puedes usar el método [`Auth::login`](https://laravel.com/api/10.x/Illuminate/Support/Facades/Auth.html#method_login), de la clase `Illuminate/Support/Facades/Auth`. Este método inicia una sesión para el usuario dado.
4. Permite que el método `accesoPorToken` sea [accesible sin autenticación](./103_roles.md#permitir-el-acceso-a-algunos-métodos-a-usuarios-anónimos).
5. Se le debería enviar un email a la dirección de correo electrónico asociado a la empresa, informándole de que se ha iniciado sesión en su cuenta.

## Ejercicio 2

Cuando una empresa está autenticada, podrá acceder al currículo de un estudiante. Pero, si intenta descargar el currículo en _PDF_, se le debería mostrar un mensaje indicando que se le enviará un correo electrónico al estudiante para solicitar permiso.

En ese momento, se le enviará un correo electrónico al estudiante con un enlace para que pueda autorizar la descarga del currículo. Este enlace lanzará un `GET` a la ruta `/api/v1/curriculos/{idEmpresa}/autorizar`.

En ese mismo correo también se le mostrarán los datos (nombre y nif) de la empresa que ha solicitado el currículo.

## Ejercicio 3

Cuando un estudiante autoriza la visualización del currículo, se le debería enviar un correo electrónico a la empresa informándole de que el estudiante ha autorizado la visualización del currículo.

Esto se debe hacer con la consiguiente creación del método `autorizar` del controlador `API\CurriculoController` así como con la ruta necesaria.

Este método sólo lo podrá ejecutar el estudiante propietario del currículo.

Como respuesta a la autorización, se le debería enviar un correo electrónico a la empresa informándole de que el estudiante ha autorizado la visualización del currículo y también incluirá un enlace para que pueda descargar el currículo en _PDF_: `GET /api/v1/curriculos/{idCurriculo}/{md5_file}`.

> En este momento, no vamos generar el código necesario para saber qué empresas han sido autorizadas para ver qué currículos, por eso, vamos a permitir que elusuario que disponga del valor correspondiente a la ejecución de la función [`md5_file`](https://www.php.net/manual/es/function.md5-file.php) sobre el fichero almacenado en `storage/app/curriculos` pueda descargar el currículo.

A este correo electrónico se le debería añadir también el enlace del [capítulo anterior](./13_email.md) con el token de acceso a la aplicación.

## Ejercicio 4

Implementar el método `getCurriculoByMd5` del controlador `API\CurriculoController` que permita descargar el currículo en _PDF_.

El método debe comprobar que el valor del parámetro `md5_file` enviado coincide con el obtenido al volver a aplicarla función [`md5_file`](https://www.php.net/manual/es/function.md5-file.php) sobre el fichero almacenado en `storage/app/curriculos`.

Este método lo deberá poder ejecutar una empresa autenticada.
