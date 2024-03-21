# 2.5. Artisan

_Laravel_ incluye un interfaz de línea de comandos (_CLI_, _Command line interface_) llamado _**Artisan**_. Esta utilidad nos va a permitir realizar múltiples tareas necesarias durante el proceso de desarrollo o despliegue a producción de una aplicación, por lo que nos facilitará y acelerará el trabajo.

Para ver una lista de todas las opciones que incluye _Artisan_ podemos ejecutar el siguiente comando en un consola o terminal del sistema en la carpeta raíz de nuestro proyecto:

```bash
php artisan list
```

O simplemente:

```bash
php artisan
```

Si queremos obtener una ayuda más detallada sobre alguna de las opciones de _Artisan_ simplemente tenemos que escribir la palabra `help` delante del comando en cuestión, por ejemplo:

```bash
php artisan help migrate
```

Es probable que debas utilizar el comando `php artisan key:generate`, para generar la clave de encriptación de un proyecto Web cuyo código lo obtenemos de repositorios compartidos. Poco a poco iremos viendo más opciones de _Artisan_, de momento vamos a comentar solo dos opciones importantes: el listado de rutas y la generación de código.

## Listado de rutas

Para ver un listado con todas las rutas que hemos definido en el fichero routes.php podemos ejecutar el comando:

```bash
php artisan route:list
```

Esto nos mostrará una tabla con el método, la dirección, la acción y los filtros definidos para todas las rutas. De esta forma podemos comprobar todas las rutas de nuestra aplicación y asegurarnos de que esté todo correcto.

## Generación de código

_Laravel_ permite la generación de código gracias a _Artisan_. A través de la opción `make` podemos generar diferentes componentes de _Laravel_ (_controladores_, _modelos_, _filtros_, etc.) como si fueran plantillas. Esto nos ahorrará mucho trabajo y podremos empezar a escribir directamente el contenido del componente. Por ejemplo, para crear un nuevo controlador tendríamos que escribir:

```bash
php artisan make:controller ProyectoController
```

En las siguientes secciones utilizaremos algunos de estos conceptos y también veremos más comandos de _Artisan_.
