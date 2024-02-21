# Enviando correo electrónico

## Objetivo

Nuestro objetivo es enviar, a las empresas que sean registradas por un docente, un correo electrónico de invitación a la plataforma. Dicho correo electrónico deberá contener un enlace que permita a la empresa autenticarse en la plataforma utilizando un token de acceso generado para ella.

### Adaptando el modelo `Empresa`

#### Atributo `user_id` en `Empresa`

Para una empresa se pueda autenticar, necesitamos que ésta esté asociada a un `User`. Para ello, vamos a crear una relación `1:1` entre `User` y `Empresa`. Comenzaremos por añadir una columna `user_id` a la tabla `empresas` que será una _clave ajena_ que apuntará a la columna `id` de la tabla `users`.

1. Añade la columna `user_id` a la tabla `empresas` mediante una migración:

```bash
php artisan make:migration add_user_id_to_empresas
```

2. Abre el archivo de migración y añade el siguiente código:

```php
public function up()
{
    Schema::table('empresas', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('token', 100)->nullable()->change();
        $table->string('nombre', 100)->nullable();
        $table->foreign('user_id')->references('id')->on('users');
    });
}

public function down()
{
    Schema::table('empresas', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
        $table->dropColumn('nombre');
    });
}
```

> Hemos aprovechado para permitir que el atributo `token` sea `NULL`, ya que va a ser generado posteriormente con un valor aleatorio. También hemos creado un atributo `nombre`, el cual parece necesario a la hora de dirigirnos a una empresa.

3. Ejecuta la migración:

```bash
php artisan migrate
```

#### Relación `User` - `Empresa`

Una vez que hemos añadido la columna `user_id` a la tabla `empresas`, podemos definir la relación `1:1` entre `User` y `Empresa`. Para ello, añadiremos el siguiente método al modelo `Empresa.php`:

```php
public function user()
{
    return $this->belongsTo(User::class);
}
```

#### Asociar el `User` a la `Empresa`

Continuando con el modelo `Empresa`, queremos conseguir que se asocien automáticamente un `User` a una `Empresa` cuando se registre una nueva `Empresa`. Para ello, vamos a utilizar los [eventos de _Eloquent_](https://laravel.com/docs/10.x/eloquent#events-using-closures), que permiten realizar acciones cuando se producen determinados eventos en los modelos _Eloquent_. En nuestro caso, actuaremos en el evento `created` del modelo `Empresa`.

1. En el archivo `app/Models/Empresa.php`, añade el siguiente código:

```php
protected static function booted()
{
    static::created(function ($empresa) {
        $empresa->token = Str::random(60);

        $usuario = User::create([
            'name' => Str::slug($empresa->nif),
            'nombre' => substr($empresa->nombre, 0, 50),
            'email' => $empresa->email,
            'password' => bcrypt('token'),
        ]);

        $empresa->user()->associate($usuario);
        $empresa->save();
    });
}
```

> Será necesario incluir la clase `Str`:
```php
use Illuminate\Support\Str;
```

## Introducción al envío de correo electrónico

El envío de correo electrónico no tiene por qué ser complicado. _Laravel_ proporciona una _API_ de correo electrónico limpia y sencilla alimentada por el popular componente _Symfony Mailer_. _Laravel_ y _Symfony Mailer_ proporcionan controladores para enviar correo electrónico a través de **_SMTP_**, _Mailgun_, _Postmark_, _Amazon SES_ y _sendmail_, lo que le permite comenzar rápidamente a enviar correo a través de un servicio local o en la nube de su elección.

Nosotros utilizaremos **Gmail** como servidor de correo a través de **_SMTP_**. Para ello, necesitaremos modificar las siguientes variables de entorno en nuestro archivo `.env`:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=nre@alu.murciaeduca.es
MAIL_PASSWORD="your_gmail_app_password"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=nre@alu.murciaeduca.es
MAIL_FROM_NAME="${APP_NAME}"
```

> Modifica el valor de `MAIL_USERNAME` y `MAIL_FROM_ADDRESS` con tus credenciales de _@alu.murciaeduca.es_. El valor de la variable `MAIL_PASSWORD` será la contraseña de aplicación que obtendrás a continuación.

## Configurando nuestra cuenta de Google

Antes de comenzar a enviar correos electrónicos desde su aplicación _Laravel_, debe configurar su cuenta de GMail. Existen dos opciones de configuración: permitir que las aplicaciones de terceros accedan a ella o crear una contraseña de aplicación. Esta segunda opción es más segura, por lo que optaremos por utilizarla en esta práctica.

1. Accede a [tu cuenta de Google](https://myaccount.google.com/) y selecciona **_Seguridad_** en el menú de la izquierda. Desplázate hacia abajo hasta **_Acceso de aplicaciones menos seguras_** y actívalo. Si no ves esta opción, es posible que tu cuenta de Google esté protegida con la autenticación de dos factores. En ese caso, deberás crear una contraseña de aplicación para tu aplicación _Laravel_.
2. Si aún no la tienes, activa la [verificación en dos pasos](https://myaccount.google.com/signinoptions/two-step-verification) en tu cuenta de Google. Si no sabes cómo hacerlo, puedes seguir [estas instrucciones](https://support.google.com/accounts/answer/185839?hl=es).
3. Una vez activada la verificación en dos pasos, podrás crear una [contraseña de aplicación](https://myaccount.google.com/apppasswords) para tu aplicación _Laravel_. Como nombre de la aplicación, selecciona el valor que configuramos en la variable de entorno `APP_NAME` de tu archivo `.env`, en nuestro caso, debería ser `Marca Personal F.P.`. Como resultado, obtendrás una contraseña de 16 caracteres que deberás copiar y pegar en tu archivo `.env` en la variable `MAIL_PASSWORD`.


## Generando el Mailable

Laravel utiliza clases `Mailable` para representar correos electrónicos. Podemos generar una nueva clase `Mailable` utilizando el comando _Artisan_ `make:mail`:

```bash
php artisan make:mail NuevaEmpresaRegistrada
```

### Estructura del Mailable

Una vez que hayas generado una clase `Mailable`, ábrela para explorar su contenido. La configuración de la clase `Mailable` se realiza en varios métodos, incluyendo los métodos `envelope`, `content` y `attachments`.

El método `envelope` devuelve un objeto `Illuminate\Mail\Mailables\Envelope` que define el asunto y, a veces, los destinatarios del mensaje.

```php
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Marca Personal F.P.: Encuentre el profesional que necesita',
        );
    }
```

El método `content` devuelve un objeto `Illuminate\Mail\Mailables\Content` que define la plantilla de _Blade_ que se utilizará para generar el contenido del mensaje. Por ejemplo, podríamos tener una vista `emails.empresas.nuevaEmpresa`:

```php
    public function content()
    {
        return new Content(
            view: 'emails.empresas.nuevaEmpresa',
        );
    }
```

Vamos a intentar que el correo electrónico esté personalizado con datos de la propia empresa, por lo que añadiremos un atributo `$empresa` a la clase `NuevaEmpresaRegistrada` y lo pasaremos al **constructor** de la clase.

```php
use App\Models\Empresa;

class NuevaEmpresaRegistrada extends Mailable
{
    use Queueable, SerializesModels;

    public $empresa;

    public function __construct(Empresa $empresa)
    {
        $this->empresa = $empresa;
    }
```

En el método content nos servirá para referenciar la vista que se utilizará para generar el contenido del mensaje

### Utilizando Blade para el contenido del correo electrónico

Podemos utilizar las vistas de _Blade_ para diseñar el contenido de nuestro correo electrónico. Por ejemplo, podríamos tener una vista en `resources/views/emails/empresas/nuevaEmpresa` que recibe un objeto `Empresa`.

En primer lugar, creamos la vista con _Artisan_:

```bash
php artisan make:view emails.empresas.nuevaEmpresa
```


Posteriormente, incluimos el contenido en el archivo `resources/views/emails/empresas/nuevaEmpresa`:

```html
<p>Uno de nuestros docentes considera que le podría interesar conocer la página web <strong>Marca Personal F.P.</strong>
    que le ayudará a encontrar los profesionales cuyas competencias mejor se adaptan a las necesidades de su empresa.</p>
<ul>
    <li>Nombre: {{ $empresa->nombre }}</li>
    <li>Correo electrónico: {{ $empresa->email }}</li>
    <li>Teléfono: {{ $empresa->telefono }}</li>
</ul>
<p>Para visitarnos, por favor, haga clic en el siguiente enlace:
    <a href="{{ url('api/v1/empresas/acceso', $empresa->token) }}">Registro en Marca Personal F.P.</a></p>
</p>
```

4. Enviando el Email
Para enviar el correo electrónico, podemos usar el método `send()` del facade `Mail` de _Laravel_. Por ejemplo, podríamos hacer esto en el método evento `created`, que se genera tras el registro de una nueva empresa:

```diff
+ use App\Mail\NuevaEmpresaRegistrada;
+ use Illuminate\Support\Facades\Mail;

    static::created(function ($empresa) {
        $empresa->token = Str::random(60);

        $usuario = User::create([
            'name' => Str::slug($empresa->nif),
            'nombre' => substr($empresa->nombre, 0, 50),
            'email' => $empresa->email,
            'password' => bcrypt('token'),
        ]);

        $empresa->user()->associate($usuario);
        $empresa->save();
+         Mail::to($empresa->email)->send(new NuevaEmpresaRegistrada($empresa));
    });
```

## Probar el envío

Antes de probar el envío del correo electrónico, es conveniente modificar el componente de `Reac-admin` correspondiente a la `Empresa`, ya que hemos de incluir el atributo `nombre` y ocultar el atributo `token`, que será gestionado internamente. Para ello, realiza las siguientes modificaciones en el archivo `resources/js/react-admin/Pages/Empresa.jsx`

```diff
@@ -42,8 +42,8 @@ export const EmpresaList = () => {
             <Datagrid bulkActionButtons={false} >
               <TextField source="id" />
               <TextField source="nif" />
+              <TextField source="nombre" />
               <TextField source="email" />
-              <TextField source="token" />
               <ShowButton />
               <EditButton />
             </Datagrid>
@@ -64,8 +64,8 @@ export const EmpresaEdit = () => (
         <SimpleForm>
             <NumberInput label="ID" source="id" />
             <TextInput source="nif" />
+            <TextInput source="nombre" />
             <TextInput source="email" />
-            <TextInput source="token" />
         </SimpleForm>
     </Edit>
 
@@ -77,8 +77,8 @@ export const EmpresaShow = () => (
         <SimpleShowLayout>
             <NumberField label="ID" source="id" />
             <TextField source="nif" />
+            <TextField source="nombre" />
             <TextField source="email" />
-            <TextField source="token" />
             <ShowButton />
             <EditButton />
         </SimpleShowLayout>
@@ -90,8 +90,8 @@ export const EmpresaCreate = () => (
         <SimpleForm>
             <NumberInput label="ID" source="id" />
             <TextInput source="nif" />
+            <TextInput source="nombre" />
             <TextInput source="email" />
-            <TextInput source="token" />
         </SimpleForm>
     </Create>
 );
```

A continuación, crea una nueva empresa que tenga, una dirección de correo electrónico que tú gestiones, de manera que puedas recibir el correo que se envía.

> Si necesitas realizar la prueba más de una vez, necesitarás eliminar de la base de datos, tanto la empresa que has creado como el usuario que se genera automáticamente.

Momentos después de haber creado la empresa, deberás recibir un correo electrónico en la cuenta asociada a dicha empresa.
