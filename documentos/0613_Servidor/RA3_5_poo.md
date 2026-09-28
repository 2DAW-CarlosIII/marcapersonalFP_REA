# 3.5. Programación Orientada a Objetos en PHP

**Presentación de apoyo** (_RevealJS_): [RA3_5_poo_slides.html](./materiales/slides/RA3_5_poo_slides.html)

## De los arrays a los objetos

Hasta ahora hemos representado un currículo o un ciclo con un array asociativo. Funciona, pero tiene limitaciones que en un proyecto real se notan pronto:

- **Nada garantiza su forma.** Si en un sitio escribes `$curriculo['alumno']` y en otro `$curriculo['alumo']`, PHP no se queja al crearlo: lo descubrirás (con suerte) cuando falle al mostrarlo.
- **No se puede declarar su tipo con precisión.** En 3.4, `ordenarPorAlumno(array $curriculos)` acepta cualquier array, tenga o no currículos dentro.
- **Los datos y las operaciones van por separado.** `titulacion()` es una función suelta que recibe un grado; nada la une al ciclo al que pertenece.

La **Programación Orientada a Objetos** (POO) resuelve estas tres cosas agrupando en una misma unidad los **datos** y las **operaciones** que trabajan con ellos:

- Una **clase** es la definición —el molde— de un tipo de dato nuevo: qué datos tiene (**propiedades**) y qué sabe hacer (**métodos**).
- Un **objeto** es una **instancia** concreta de una clase: el ciclo DAW y el ciclo DAM son dos objetos de la clase `Ciclo`.

Es el tipo compuesto `object` que anunciamos en 2.3 y 3.3. Y es, además, la base de todo _Laravel_: en el Bloque 4, cada tabla de la base de datos se representa con una clase (`Ciclo`, `Curriculo`, `User`...).

## Una primera clase

```php
<?php
declare(strict_types=1);

class Ciclo
{
    public string $codCiclo;
    public string $nombre;
    public string $grado;

    public function __construct(string $codCiclo, string $nombre, string $grado)
    {
        $this->codCiclo = $codCiclo;
        $this->nombre   = $nombre;
        $this->grado    = $grado;
    }
}
```

- Las **propiedades** se declaran con su visibilidad (`public`, enseguida la explicamos) y su **tipo**: una propiedad tipada solo admite valores de ese tipo, algo que las variables sueltas no permitían (2.3).
- **`__construct`** es el **constructor**: un método especial que se ejecuta automáticamente al crear el objeto y que se encarga de dejarlo en un estado válido.
- **`$this`** es el propio objeto sobre el que se está ejecutando el método. `$this->codCiclo` es "la propiedad `codCiclo` de este objeto".

Los objetos se crean con **`new`** y sus miembros se usan con el operador **`->`**:

```php
<?php
  $daw = new Ciclo('DAW', 'Desarrollo de Aplicaciones Web', 'Superior');
  $dam = new Ciclo('DAM', 'Desarrollo de Aplicaciones Multiplataforma', 'Superior');

  echo $daw->nombre;   // Desarrollo de Aplicaciones Web
  echo $dam->codCiclo; // DAM
?>
```

Por convención, el nombre de una clase empieza por mayúscula y va en _PascalCase_ (`Ciclo`, `FamiliaProfesional`); propiedades y métodos, en _camelCase_ (`codCiclo`, `estaPublicado()`).

### Promoción de propiedades en el constructor

Declarar cada propiedad, repetirla como parámetro y asignarla en el constructor es muy repetitivo. Desde PHP 8, si un parámetro del constructor lleva visibilidad, se convierte **a la vez** en propiedad y se asigna solo. La clase anterior queda así:

```php
<?php
class Ciclo
{
    public function __construct(
        public string $codCiclo,
        public string $nombre,
        public string $grado,
    ) {
    }
}
```

Es exactamente equivalente. Usaremos esta forma siempre que podamos.

## Métodos

Un **método** es una función declarada dentro de una clase. Todo lo visto en 3.4 (parámetros, tipos, `return`) sirve igual; la diferencia es que un método trabaja con los datos **de su objeto**, a través de `$this`:

```php
<?php
class Ciclo
{
    // ... constructor ...

    public function titulacion(): string
    {
        return $this->grado === 'Superior' ? 'Técnico Superior' : 'Técnico';
    }
}

$daw = new Ciclo('DAW', 'Desarrollo de Aplicaciones Web', 'Superior');
echo $daw->titulacion();   // Técnico Superior
?>
```

Compara con `titulacion($grado)` de 3.4: ahora no hay que pasarle el grado, porque el ciclo **ya lo conoce**.

## Visibilidad y encapsulación

Cada propiedad y cada método lleva un modificador de **visibilidad**, que decide desde dónde se puede usar:

| Visibilidad | Accesible desde... |
|---|---|
| `public` | Cualquier sitio |
| `protected` | La propia clase y las clases que heredan de ella (lo veremos en [Herencia](#herencia)) |
| `private` | Solo la propia clase |

La **encapsulación** consiste en ocultar los datos internos (`private`) y ofrecer solo los métodos necesarios para trabajar con ellos. Así, la clase controla cómo cambia su estado. Por ejemplo, un currículo empieza en borrador y solo se puede **publicar**; no queremos que nadie lo "despublique" asignando `false` a mano:

```php
<?php
class Curriculo
{
    private bool $publicado = false;

    public function __construct(
        public string $alumno,
    ) {
    }

    public function publicar(): void
    {
        $this->publicado = true;
    }

    public function estaPublicado(): bool
    {
        return $this->publicado;
    }
}

$curriculo = new Curriculo('Ana López');
$curriculo->publicar();
echo $curriculo->estaPublicado() ? 'publicado' : 'en borrador';   // publicado

// $curriculo->publicado = false;   // Error: Cannot access private property
?>
```

### Propiedades de solo lectura: `readonly`

Hay datos que se fijan al crear el objeto y no deben cambiar después: el código de un ciclo no cambia una vez creado. Para eso existe **`readonly`**: la propiedad se puede **leer** desde fuera, pero solo se puede **asignar una vez**, dentro de la clase (normalmente, en el constructor):

```php
<?php
class Ciclo
{
    public function __construct(
        public readonly string $codCiclo,
        public readonly string $nombre,
        public readonly string $grado,
    ) {
    }
}

$daw = new Ciclo('DAW', 'Desarrollo de Aplicaciones Web', 'Superior');
echo $daw->codCiclo;        // DAW: se puede leer
// $daw->codCiclo = 'DAM';  // Error: Cannot modify readonly property
?>
```

Con `private` + métodos controlamos **cómo** cambia un dato; con `readonly` garantizamos que **no cambia**.

## Constantes de clase y miembros estáticos

En 2.3 anunciamos que `const` —a diferencia de `define()`— puede usarse **dentro de una clase**. Una **constante de clase** pertenece a la clase, no a cada objeto, y es ideal para los valores permitidos de un dato:

```php
<?php
class Ciclo
{
    public const GRADO_MEDIO    = 'Medio';
    public const GRADO_SUPERIOR = 'Superior';

    // ...

    public function titulacion(): string
    {
        return $this->grado === self::GRADO_SUPERIOR ? 'Técnico Superior' : 'Técnico';
    }
}

$daw = new Ciclo('DAW', 'Desarrollo de Aplicaciones Web', Ciclo::GRADO_SUPERIOR);
?>
```

Se accede con el operador **`::`**: desde fuera, `NombreDeClase::CONSTANTE`; desde dentro, `self::CONSTANTE` (`self` es "esta clase"). Si te equivocas al escribir `Ciclo::GRADO_SUPERIRO`, PHP da un error inmediato, cosa que no ocurre con un string mal escrito como `'Superiro'`.

Del mismo modo, un método o una propiedad **`static`** pertenecen a la clase y se usan sin crear ningún objeto. Un uso muy habitual es el **método de fábrica**: un método estático que construye un objeto a partir de otros datos. Por ejemplo, a partir de un array como los que hemos usado hasta ahora (o como los que llegarán de un formulario en 3.6):

```php
<?php
class Ciclo
{
    // ...

    public static function desdeArray(array $datos): self
    {
        return new self($datos['codCiclo'], $datos['nombre'], $datos['grado']);
    }
}

$dam = Ciclo::desdeArray(['codCiclo' => 'DAM', 'nombre' => 'Desarrollo de Aplicaciones Multiplataforma', 'grado' => 'Superior']);
?>
```

Dentro de un método estático **no existe `$this`**, porque no hay ningún objeto: solo la clase. En el Bloque 4 usarás constantemente métodos estáticos de fábrica de _Laravel_, como `Ciclo::find(1)` o `Ciclo::create([...])`.

> Una **propiedad** `static` es compartida por todos los objetos de la clase. Igual que la variable `static` de una función (2.4), **no sobrevive entre peticiones**: cada petición ejecuta el script desde cero.

## Los objetos no se copian

En 3.3 vimos que asignar un array hace una copia. Con los objetos **no**: la variable guarda un **identificador** del objeto (un _manejador_), y asignarla o pasarla a una función copia ese identificador, no el objeto. Las dos variables apuntan **al mismo objeto**:

```php
<?php
  $curriculo = new Curriculo('Ana López');
  $otro = $curriculo;

  $otro->publicar();
  echo $curriculo->estaPublicado() ? 'publicado' : 'en borrador';   // publicado: es el mismo objeto
?>
```

Si necesitas de verdad una copia independiente, se crea con `clone`: `$copia = clone $curriculo;`.

## Objetos dentro de objetos

Una propiedad puede ser, a su vez, un objeto. Así un currículo **tiene** un ciclo (a esto se le llama **composición**), y su tipo lo deja claro:

```php
<?php
class Curriculo
{
    public function __construct(
        public readonly string $alumno,
        public readonly Ciclo $ciclo,
        public readonly ?string $videoCurriculum = null,
    ) {
    }
}

$curriculo = new Curriculo('Ana López', $daw, 'https://youtu.be/curriculo1');
echo $curriculo->ciclo->nombre;   // Desarrollo de Aplicaciones Web
?>
```

Fíjate en que el nombre de una clase (`Ciclo`) se usa como **tipo declarado**, igual que `int` o `string`: si le pasas otra cosa, `TypeError`. Y en que `$videoCurriculum` es nullable con valor por defecto `null` (3.4): un currículo puede crearse sin vídeo.

## Herencia

Todas las entidades de _marcapersonalFP_ (ciclos, currículos, proyectos, usuarios...) tienen cosas en común: por ejemplo, un identificador `id`. En vez de repetirlo en cada clase, podemos escribirlo una vez en una clase **padre** y hacer que las demás **hereden** de ella con **`extends`**:

```php
<?php
abstract class Modelo
{
    public function __construct(
        public readonly int $id,
    ) {
    }

    abstract public function descripcion(): string;
}

class Ciclo extends Modelo
{
    public function __construct(
        int $id,
        public readonly string $codCiclo,
        public readonly string $nombre,
        public readonly string $grado,
    ) {
        parent::__construct($id);
    }

    public function descripcion(): string
    {
        return $this->codCiclo . ' — ' . $this->nombre;
    }
}
?>
```

- La clase **hija** (`Ciclo`) hereda todas las propiedades y métodos públicos y protegidos de la **padre** (`Modelo`): `$daw->id` funciona aunque `Ciclo` no declare `id`.
- Si la hija define su propio constructor, debe llamar al del padre con **`parent::__construct(...)`** para que se inicialice lo que le corresponde. Fíjate en que `$id` **no** lleva visibilidad en el constructor de `Ciclo`: no es una propiedad nueva, solo un parámetro que se pasa al padre.
- **`abstract class`** es una clase que **no se puede instanciar** (`new Modelo(1)` da error): solo sirve de base para otras. Un **método abstracto** declara su firma sin cuerpo y **obliga** a cada hija a implementarlo: todo modelo tiene que saber dar su `descripcion()`, cada uno a su manera.
- Cualquier objeto de una clase hija es también del tipo de la padre: una función que declare un parámetro `Modelo` acepta un `Ciclo` o un `Curriculo`.

> **Puente al Bloque 4.** En _Laravel_, tus modelos se escribirán `class Ciclo extends Model`: heredarán de una clase padre del framework que ya sabe guardarse en la base de datos, buscarse, convertirse a JSON... La idea es la misma que aquí, pero la clase padre la escribe el framework.

## Interfaces

La herencia expresa "**es un**" (un `Ciclo` es un `Modelo`). A veces queremos expresar otra cosa: "**sabe hacer**". Por ejemplo, currículos y proyectos se pueden **publicar**, pero los ciclos no. Una **interfaz** declara un conjunto de métodos, sin implementarlos, y las clases que la **implementan** (`implements`) se comprometen a tenerlos:

```php
<?php
interface Publicable
{
    public function publicar(): void;
    public function estaPublicado(): bool;
}

class Curriculo extends Modelo implements Publicable
{
    private bool $publicado = false;

    // ... constructor y descripcion() ...

    public function publicar(): void
    {
        $this->publicado = true;
    }

    public function estaPublicado(): bool
    {
        return $this->publicado;
    }
}
?>
```

Una clase solo puede heredar de **una** clase padre, pero puede implementar **varias** interfaces. Y una interfaz también sirve como tipo declarado: una función `publicarTodos(Publicable ...$elementos)` aceptaría currículos y proyectos, sin importarle de qué clase sean. Para comprobar en tiempo de ejecución si un objeto es de una clase o implementa una interfaz, existe el operador `instanceof`: `$curriculo instanceof Publicable` vale `true`.

## Organizar las clases en ficheros

Como las funciones de 3.4, las clases van en `vanilla_php/src/`, en ficheros que **solo definen** (sin salida). La convención, que seguiremos, es **una clase (o interfaz) por fichero**, con el **mismo nombre** que la clase: `src/Ciclo.php`, `src/Curriculo.php`...

Cada fichero carga con `require_once` lo que necesita para definirse: `Ciclo.php` necesita `Modelo.php`, porque hereda de ella. Así, una página solo tiene que cargar las clases que usa directamente, y cada una arrastra sus dependencias; `require_once` evita que un fichero se cargue dos veces aunque lo pidan varios.

> **Puente al Bloque 4.** En _Laravel_, la clase `Ciclo` vive en `app/Models/Ciclo.php` y se identifica con un **espacio de nombres** (`App\Models\Ciclo`). Gracias a esa correspondencia entre nombre y ruta, el _autoload_ de _Composer_ sabe qué fichero cargar sin ningún `require_once`: la convención "una clase por fichero, con su nombre" que empezamos aquí es la que lo hace posible.

## Preparación del entorno de trabajo

Seguimos con el mismo proyecto `vanilla_php`, con la carpeta `src/` que creamos en [3.4](./RA3_4_funciones.md#preparación-del-entorno-de-trabajo). Si lo necesitas, repasa [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md#preparación-del-entorno-de-trabajo).

Al terminar los ejercicios, `src/` tendrá estos ficheros:

```
vanilla_php/src/
├── RA3_funciones.php   (de 3.4)
├── Modelo.php
├── Publicable.php
├── Ciclo.php
└── Curriculo.php
```

## Ejercicios

Los ejercicios 1 a 4 son ficheros de `src/`: todos empiezan con `<?php` y `declare(strict_types=1);`, y **no producen salida**. El ejercicio 5 es la página que los usa.

1. **Clase abstracta.** Crea `src/Modelo.php` con la clase abstracta `Modelo` de la sección [Herencia](#herencia).
2. **Interfaz.** Crea `src/Publicable.php` con la interfaz `Publicable` de la sección [Interfaces](#interfaces).
3. **`Ciclo`.** Crea `src/Ciclo.php` reuniendo todo lo visto sobre ella:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/Modelo.php';

    class Ciclo extends Modelo
    {
        public const GRADO_MEDIO    = 'Medio';
        public const GRADO_SUPERIOR = 'Superior';

        public function __construct(
            int $id,
            public readonly string $codCiclo,
            public readonly string $nombre,
            public readonly string $grado,
        ) {
            parent::__construct($id);
        }

        public function titulacion(): string
        {
            return $this->grado === self::GRADO_SUPERIOR ? 'Técnico Superior' : 'Técnico';
        }

        public function descripcion(): string
        {
            return $this->codCiclo . ' — ' . $this->nombre;
        }

        public static function desdeArray(array $datos): self
        {
            return new self($datos['id'], $datos['codCiclo'], $datos['nombre'], $datos['grado']);
        }
    }
    ```

4. **`Curriculo`.** Crea `src/Curriculo.php`:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/Modelo.php';
    require_once __DIR__ . '/Publicable.php';
    require_once __DIR__ . '/Ciclo.php';

    class Curriculo extends Modelo implements Publicable
    {
        private bool $publicado = false;

        public function __construct(
            int $id,
            public readonly string $alumno,
            public readonly Ciclo $ciclo,
            public readonly ?string $videoCurriculum = null,
        ) {
            parent::__construct($id);
        }

        public function publicar(): void
        {
            $this->publicado = true;
        }

        public function estaPublicado(): bool
        {
            return $this->publicado;
        }

        public function descripcion(): string
        {
            return $this->alumno . ' (' . $this->ciclo->codCiclo . ')';
        }
    }
    ```

5. **La página.** Crea `public/RA3_poo.php` y compruébala en `http://localhost/RA3_poo.php`:

    ```php
    <?php
    declare(strict_types=1);

    require_once __DIR__ . '/../src/Curriculo.php';

    $daw = new Ciclo(1, 'DAW', 'Desarrollo de Aplicaciones Web', Ciclo::GRADO_SUPERIOR);
    $curriculo = new Curriculo(1, 'Ana López', $daw, 'https://youtu.be/curriculo1');

    $mismoCurriculo = $curriculo;   // no es una copia: es el mismo objeto
    $mismoCurriculo->publicar();
    ?>
    <h1><?= $daw->descripcion() ?></h1>
    <p>Titulación: <?= $daw->titulacion() ?></p>
    <p><?= $curriculo->descripcion() ?>: <?= $curriculo->estaPublicado() ? 'publicado' : 'en borrador' ?></p>
    ```

    Antes de recargar, anota en un comentario si esperas ver "publicado" o "en borrador", teniendo en cuenta que se ha llamado a `publicar()` a través de `$mismoCurriculo`, no de `$curriculo`. Después, añade temporalmente al final del bloque PHP, de una en una, estas líneas y lee el error que produce cada una (bórrala antes de probar la siguiente): `$daw->codCiclo = 'DAM';`, `new Modelo(2);` y `echo Ciclo::GRADO_SUPERIRO;`.
6. **Investiga.** Desde PHP 8.1 existen las **enumeraciones** (`enum`). Busca en la documentación qué es un _backed enum_ y cómo escribirías un `enum Grado: string` con los casos `Medio` y `Superior`. ¿Qué ventaja tendría usarlo como tipo de la propiedad `$grado` (`public readonly Grado $grado`) frente a un `string` más las constantes `GRADO_MEDIO` y `GRADO_SUPERIOR`? Escribe la respuesta en un comentario de la página; no cambies las clases (el test comprueba la versión con constantes).

## Comprueba tu solución automáticamente (opcional)

Para los ejercicios 1 a 5, copia el archivo [RA3_5_PooTest.php](./materiales/ejercicios-vanilla/tests/RA3_5_PooTest.php) a la carpeta `vanilla_php/tests/` de tu proyecto y ejecuta, desde un terminal situado en la carpeta `laradock/`, ese fichero de test en concreto:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit tests/RA3_5_PooTest.php
```

Como en 3.4, el test carga tus clases con `require_once` y crea objetos para comprobar directamente su comportamiento: qué devuelven sus métodos, qué hereda cada clase, qué propiedades son privadas o de solo lectura... Solo el último test pide la página a _nginx_.

Si todo está bien: `OK (9 tests, 23 assertions)`.

---

**Siguiente:** [3.6. Formularios web: recuperación, procesamiento y validación](./RA3_6_formularios.md)
