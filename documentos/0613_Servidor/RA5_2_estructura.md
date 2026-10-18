# 2.2. Funcionamiento básico

En esta sección vamos a analizar la estructura de un proyecto, es decir, donde va cada cosa, y a continuación veremos el ciclo de vida de una petición en _Laravel_.

## Estructura de un proyecto

Al crear un nuevo proyecto de Laravel se nos generará una estructura de carpetas y ficheros para organizar nuestro código. Es importante que conozcamos para que vale cada elemento y donde tenemos que colocar nuestro código. En este manual lo iremos viendo poco a poco, por lo que más adelante se volverán a explicar algunos de estos elementos más en detalle. Pero de momento vamos a explicar brevemente las carpetas que más utilizaremos y las que mejor tendremos que conocer:

- `app` – Contiene el código principal de la aplicación. Esta carpeta a su vez está dividida en muchas subcarpetas que analizaremos en la siguiente sección.

- `config` – Aquí se encuentran todos los archivos de configuración de la aplicación: base datos, cache, correos, sesiones o cualquier otra configuración general de la aplicación.

- `database` – En esta carpeta se incluye todo lo relacionado con la definición de la base de datos de nuestro proyecto. Dentro de ella podemos encontrar a su vez tres carpetas: factores, migrations y seeds. En el capítulo sobre base de datos analizaremos mejor su contenido.

- `public` – Es la única carpeta pública, la única que debería ser visible en nuestro servidor web. Todo las peticiones y solicitudes a la aplicación pasan por esta carpeta, ya que en ella se encuentra el index.php, este archivo es el que inicia todo el proceso de ejecución del framework. En este directorio también se alojan los archivos _CSS_, _Javascript_, imágenes y otros archivos que se quieran hacer públicos.

- `resources` – Esta carpeta contiene a su vez tres carpetas: assets, views y lang:

  - `resources/views` – Este directorio contiene las vistas de nuestra aplicación. En general serán plantillas de HTML que usan los controladores para mostrar la información. Hay que tener en cuenta que en esta carpeta no se almacenan los Javascript, CSS o imágenes, ese tipo de archivos se tienen que guardar en la carpeta public.

  - `resources/lang` – En esta carpeta se guardan archivos PHP que contienen arrays con los textos de nuestro sitio web en diferentes lenguajes, solo será necesario utilizarla en caso que se desee que la aplicación se pueda traducir.

  - `resources/assets` – Se utiliza para almacenar los fuentes de los assets tipo less o sass que se tendrían que compilar para generar las hojas de estilo públicas. No es necesario usar esta carpeta ya que podemos escribir directamente las las hojas de estilo dentro de la carpeta public.

  - `resources/js`

- `routes` – Este directorio define todas las rutas de nuestro sitio web, enlazando una URL del navegador con un método de un controlador. Además nos permite realizar validaciones (mediante Middleware) y otras operaciones sobre las rutas de nuestro sitio.

- `bootstrap` – En esta carpeta se incluye el código que se carga para procesar cada una de las llamadas a nuestro proyecto. Normalmente no tendremos que modificar nada de esta carpeta.

- `storage` – En esta carpeta Laravel almacena toda la información interna necesarios para la ejecución de la web, como son los archivos de sesión, la caché, la compilación de las vistas, meta información y los logs del sistema. Normalmente tampoco tendremos que tocar nada dentro de esta carpeta, unicamente se suele acceder a ella para consultar los logs.

- `tests` – Esta carpeta se utiliza para los ficheros con las pruebas automatizadas. Laravel incluye un sistema que facilita todo el proceso de pruebas con PHPUnit.

- `vendor` – En esta carpeta se alojan todas las librerías y dependencias que conforman el framework de Laravel. Esta carpeta tampoco la tendremos que modificar, ya que todo el código que contiene son librerías que se instalan y actualizan mediante la herramienta Composer.

Además, en la carpeta raíz también podemos encontrar dos ficheros muy importantes y que también utilizaremos:

- `.env` – Este fichero ya lo hemos mencionado en la sección de instalación, se utiliza para almacenar los valores de configuración que son propios de la máquina o instalación actual. Lo que nos permite cambiar fácilmente la configuración según la máquina en la que se instale y tener opciones distintas para producción, para distintos desarrolladores, etc. Importante, este fichero debería estar en el .gitignore.

- `composer.json` – Este fichero es el utilizado por _Composer_ para realizar la instalación de Laravel. En una instalación inicial únicamente se especificará la instalación de un paquete, el propio framework de Laravel, pero podemos especificar la instalación de otras librerías o paquetes externos que añadan funcionalidad a Laravel.

### Carpeta App

La carpeta `app` es la que contiene el código principal del proyecto, como son las rutas, controladores, filtros y modelos de datos. Si accedemos a esta carpeta veremos que contiene a su vez muchas sub-carpetas, pero la principal que vamos a utilizar es la carpeta `Http`:

- `app/Http/Controllers` – Contiene todos los archivos con las clases de los controladores que sirven para interactuar con los modelos, las vistas y manejar la lógica de la aplicación.

- `app/Http/Middleware` – Son los filtros o clases intermedias que podemos utilizar para realizar determinadas acciones, como la validación de permisos, antes o después de la ejecución de una petición a una ruta de nuestro proyecto web.

Además de esta carpeta encontraremos muchas otras como `Console`, `Events`, `Exceptions`, `Jobs`, `Listeners`, `Policies` y `Providers`. Más adelante veremos algunas de estas carpetas pero, inicialmente, la única que vamos a utilizar es `Http`.

En la raíz de app también podemos encontrar el fichero `User.php`. Este fichero es un modelo de datos que viene predefinido por Laravel para trabajar con los usuarios de la web, que incluye métodos para hacer _login_, _registro_, etc. En el capítulo sobre bases de datos hablaremos más sobre esto.

## Funcionamiento básico

El funcionamiento básico que sigue Laravel tras una petición web a una URL de nuestro sitio es el siguiente:

- Todas las peticiones entran a través del fichero `public/index.php`, el cual en primer lugar comprobará en los ficheros de rutas (`routes`) si la URL es válida y en caso de serlo a que controlador tiene que hacer la petición.

- A continuación se llamará al método del controlador asignado para dicha ruta. Como hemos visto, el controlador es el punto de entrada de las peticiones del usuario, el cual, dependiendo de la petición:

  - Accederá a la base de datos (si fuese necesario) a través de los "modelos" para obtener datos (o para añadir, modificar o eliminar).
  - Tras obtener los datos necesarios los preparará para pasárselos a la vista.

- En el tercer paso el controlador llamará a una vista con una serie de datos asociados, la cual se preparará para mostrarse correctamente a partir de los datos de entrada y por último se mostrará al usuario.

A continuación se incluye un pequeño esquema de este funcionamiento:

![Esquema de funcionamiento de una petición Laravel](./images/esquema_funcionamiento.png)

En las siguientes secciones iremos viendo cada uno de estos apartados por separado. En primer lugar se estudiará como podemos definir las rutas que tiene nuestra aplicación y como las tenemos que enlazar con los controladores. Seguidamente se verán los controladores y vistas, dejando los modelos de datos y el uso de la base de datos para más adelante.
