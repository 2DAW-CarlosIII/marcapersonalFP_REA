# 3.5. Formularios

En esta sección vamos a repasar brevemente como crear un formulario usando etiquetas de _HTML_, los distintos elementos o inputs que podemos utilizar, además también veremos como conectar el envío de un formulario con un controlador, como protegernos de ataques _CSRF_ y algunas cuestiones más.

## Crear formularios

Para abrir y cerrar un formulario que apunte a la _URL_ actual y utilice el método _POST_ tenemos que usar las siguientes etiquetas _HTML_:

```php
<form method="POST">
    ...
</form>
```

Si queremos cambiar la _URL_ de envío de datos podemos utilizar el atributo `action` de la forma:

{% raw %}
```php
<form action="{{ url('foo/bar') }}" method="POST">
    ...
</form>
```
{% endraw %}

La función `url()` generará la dirección a la ruta indicada. Ademas también podemos usar la función `action()` para indicar directamente el método de un controlador a utilizar, por ejemplo:

```php
use App\Http\Controllers\HomeController;

action([HomeController::class, 'getIndex'])
```

Como hemos visto anteriormente, en Laravel podemos definir distintas acciones para procesar peticiones realizadas a una misma ruta pero usando un método distinto (`GET`, `POST`, `PUT`, `DELETE`). Por ejemplo, podemos definir la ruta `user` de tipo `GET` para que nos devuelva la página con el formulario para crear un usuario, y por otro lado definir la ruta "`user`" de tipo `POST` para procesar el envío del formulario. De esta forma cada ruta apuntará a un método distinto de un controlador y nos facilitará la separación del código.

_HTML_ solo permite el uso de formularios de tipo `GET` o `POST`. Si queremos enviar un formulario usando otros de los métodos (o verbos) definidos en el protocolo _REST_, como son `PUT`, `PATCH` o `DELETE`, tendremos que añadir un campo oculto para indicarlo. Laravel establece el uso del nombre `_method` para indicar el método a usar, por ejemplo:

```php
<form action="/foo/bar" method="POST">
    <input type="hidden" name="_method" value="PUT">
    ...
</form>
```

Laravel se encargará de recoger el valor de dicho campo y de procesarlo como una petición tipo `PUT` (o la que indiquemos). Además, para facilitar más la definición de este tipo de formularios ha añadido la función `method_field`` que directamente creará este campo oculto:

{% raw %}
```php
<form action="/foo/bar" method="POST">
    {{ method_field('PUT') }}
    ...
</form>
```
{% endraw %}

## Protección contra CSRF

El _CSRF_ (del inglés _Cross-site request forgery_ o falsificación de petición en sitios cruzados) es un tipo de exploit malicioso de un sitio web en el que comandos no autorizados son transmitidos por un usuario en el cual el sitio web confía.

Laravel proporciona una forma fácil de protegernos de este tipo de ataques. Simplemente tendremos que utilizar la directiva de Blade `@csrf` después de abrir el formulario, igual que vimos en la sección anterior, este método añadirá un campo oculto ya configurado con los valores necesarios. A continuación se incluye un ejemplo de uso:

```php
<form action="/foo/bar" method="POST">
    @csrf
    ...
</form>
```

## Elementos de un formulario

A continuación vamos a ver los diferentes elementos que podemos añadir a un formulario. En todos los tipos de campos en los que tengamos que recoger datos es importante añadir sus atributos name e id, ya que nos servirán después para recoger los valores rellenados por el usuario.

### Campos de texto

Para crear un campo de texto usamos la etiqueta de HTML input, para la cual tenemos que indicar el tipo text y su nombre e identificador de la forma:

```html
<input type="text" name="nombre" id="nombre">
```

En este ejemplo hemos creado un campo de texto vacío cuyo nombre e identificador es **nombre**. El atributo `name` indica el nombre de variable donde se guardará el texto introducido por el usuario y que después utilizaremos desde el controlador para acceder al valor.

Si queremos podemos especificar un valor por defecto usando el atributo value:

```html
<input type="text" name="nombre" id="nombre" value="Texto inicial">
```

Desde una vista con _Blade_ podemos asignar el contenido de una variable (en el ejemplo `$nombre`) para que aparezca el campo de texto con dicho valor. Esta opción es muy útil para crear formularios en los que tenemos que editar un contenido ya existente, como por ejemplo editar los datos de usuario. A continuación se muestra un ejemplo:

{% raw %}
```html
<input type="text" name="nombre" id="nombre" value="{{ $nombre }}">
```
{% endraw %}

Para mostrar los valores introducidos en una petición anterior podemos usar el método `old()`, el cual recuperará las variables almacenadas en la petición anterior. Por ejemplo, imaginad que creáis un formulario para el registro de usuarios y al enviar el formulario comprobáis que el usuario introducido está repetido. En ese caso se tendría que volver a mostrar el formulario con los datos introducidos y marcar dicho campo como erróneo. Para esto, después de comprobar que hay un error en el controlador, habría que realizar una redirección a la página anterior añadiendo la entrada como ya vimos con `withInput()`, por ejemplo: `return back()->withInput();`. El método `withInput()` añade todas las variables de entrada a la sesión, y esto nos permite recuperarlas después de la forma:

{% raw %}
```html
<input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}">
```
{% endraw %}

Más adelante, cuando veamos como recoger los datos de entrada revisaremos el proceso completo para procesar un formulario.

### Más campos tipo input

Utilizando la etiqueta `input` podemos crear más tipos de campos como contraseñas o campos ocultos:

```html
<input type="password" name="password" id="password">
```

```html
<input type="hidden" name="oculto" value="valor">
```

Los campos para contraseñas lo único que hacen es ocultar las letras escritas. Los campos ocultos se suelen utilizar para almacenar opciones o valores que se desean enviar junto con los datos del formulario pero que no se tienen que mostrar al usuario. En las secciones anteriores ya hemos visto que Laravel lo utiliza internamente para almacenar un hash o código para la protección contra ataques tipo _CSRF_ y que también lo utiliza para indicar si el tipo de envío del formulario es distinto de `POST` o `GET`. Además nosotros lo podemos utilizar para almacenar cualquier valor que después queramos recoger justo con los datos del formulario.

También podemos crear otro tipo de inputs como `email`, `number`, `tel`, etc. (podéis consultar la lista de tipos permitidos [aquí](http://www.w3schools.com/html/html_form_input_types.asp)). Para definir estos campos se hace exactamente igual que para un campo de texto pero cambiando el tipo por el deseado, por ejemplo:

```html
<input type="email" name="correo" id="correo">
```

```html
<input type="number" name="numero" id="numero">
```

```html
<input type="tel" name="telefono" id="telefono">
```

### Textarea

Para crear un área de texto simplemente tenemos que usar la etiqueta _HTML_ textarea de la forma:

```html
<textarea name="texto" id="texto"></textarea>
```

Esta etiqueta además permite indicar el número de filas (`rows`) y columnas (`cols`) del área de texto. 

Para insertar un texto o valor inicial lo tenemos que poner entre la etiqueta de apertura y la de cierre.

A continuación se puede ver un ejemplo completo:

```html
<textarea name="texto" id="texto" rows="4" cols="50">Texto por defecto</textarea>
```

### Etiquetas

Las etiquetas nos permiten poner un texto asociado a un campo de un formulario para indicar el tipo de contenido que se espera en dicho campo. Por ejemplo añadir el texto **Nombre** antes de un input tipo texto donde el usuario tendrá que escribir su nombre.

Para crear una etiqueta tenemos que usar el tag `label` de HTML:

```html
<label for="nombre">Nombre</label>
```

Donde el atributo for se utiliza para especificar el identificador del campo relacionado con la etiqueta. De esta forma, al pulsar sobre la etiqueta se marcará automáticamente el campo relacionado. A continuación se muestra un ejemplo completo:

```php
<label for="correo">Correo electrónico:</label>
<input type="email" name="correo" id="correo">
```

### Checkbox y Radio buttons

Para crear campos tipo _checkbox_ o tipo _radio button_ tenemos que utilizar también la etiqueta `input`, pero indicando el tipo `chekbox` o `radio` respectivamente. Por ejemplo, para crear un _checkbox_ para aceptar los términos escribiríamos:

```php
<label for="terms">Aceptar términos</label>
<input type="checkbox" name="terms" id="terms" value="1">
```

En este caso, al enviar el formulario, si el usuario marca la casilla nos llegaría la variable con nombre `terms` con valor `1`. En caso de que no marque la casilla no llegaría nada, ni siquiera la variable vacía.

Para crear una lista de _checkbox_ o de _radio button_ es importante que todos tengan el mismo nombre (para la propiedad `name`). De esta forma los valores devueltos estarán agrupados en esa variable, y además, el _radio button_ funcionará correctamente: al apretar sobre una opción se desmarcará la que este seleccionada en dicho grupo (entre todos los que tengan el mismo nombre). Por ejemplo:

```php
<label for="color">Elige tu color favorito:</label>
<br>
<input type="radio" name="color" id="color" value="rojo">Rojo<br>
<input type="radio" name="color" id="color" value="azul">Azul<br>
<input type="radio" name="color" id="color" value="amarillo">Amarillo<br>
<input type="radio" name="color" id="color" value="verde">Verde<br>
```

Además podemos añadir el atributo `checked` para marcar una opción por defecto:

```php
<label for="clase">Clase:</label>
<input type="radio" name="clase" id="clase" value="turista" checked>Turista<br>
<input type="radio" name="clase" id="clase" value="preferente">Preferente<br>
```

### Ficheros

Para generar un campo para subir ficheros utilizamos también la etiqueta `input` indicando en su tipo el valor `file`, por ejemplo:

```php
<label for="imagen">Sube la imagen:</label>
<input type="file" name="imagen" id="imagen">
```

Para enviar ficheros la etiqueta de apertura del formulario tiene que cumplir dos requisitos importantes:

    El método de envío tiene que ser `POST` o `PUT`.
    Tenemos que añadir el atributo `enctype="multipart/form-data"` para indicar la codificacón.

A continuación se incluye un ejemplo completo:

```php
<form enctype="multipart/form-data" method="post">
    <label for="imagen">Sube la imagen:</label>
    <input type="file" name="imagen" id="imagen">
</form>
```

### Listas desplegables

Para crear una lista desplegable utilizamos la etiqueta _HTML_ `select`. Las opciones la indicaremos entre la etiqueta de apertura y cierre usando elementos `option`, de la forma:

```php
<select name="marca">
  <option value="volvo">Volvo</option>
  <option value="saab">Saab</option>
  <option value="mercedes">Mercedes</option>
  <option value="audi">Audi</option>
</select>
```

En el ejemplo anterior se creará una lista desplegable con cuatro opciones. Al enviar el formulario el valor seleccionado nos llegará en la variable marca. Además, para elegir una opción por defecto podemos utilizar el atributo `selected`, por ejemplo:

```php
<label for="talla">Elige la talla:</label>
<select name="talla" id="talla">
  <option value="XS">XS</option> 
  <option value="S">S</option>
  <option value="M" selected>M</option>
  <option value="L">L</option>
  <option value="XL">XL</option>
</select>
```

### Botones

Por último vamos a ver como añadir botones a un formulario. En un formulario podremos añadir tres tipos distintos de botones:

    `submit` para enviar el formulario,
    `reset` para restablecer o borrar los valores introducidos y
    `button` para crear botones normales para realizar otro tipo de acciones (como volver a la página anterior).

A continuación se incluyen ejemplo de cada uno de ellos:

```php
<button type="submit">Enviar</button>
<button type="reset">Borrar</button>
<button type="button">Volver</button>
```

## Recuperando los datos del Formulario

Para obtener la instancia del objeto `Request` actual debemos inyectar la clase `Illuminate\Http\Request` en el método del controlador.

```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $name = $request->input('name');

        //
    }
}
```
{% endraw %}
