# Manejo de ficheros

_Laravel_ facilita el manejo de ficheros, tanto si se van a almacenar en el **sistema de ficheros local**, como si se van a almacenar en un servicio como _**Amazon S3**_.

En esta práctica guiada, vamos a utilizar el _driver_ que incluye para el almacenamiento en el **sistema de archivos local**.

## Preparar la base de datos.

Vamos a crear un fichero de migración que añada un atributo _avatar_, el cual va a contener la imagen asociada a un `Estudiante`.

```bash
php artisan make:migration add_avatar_to_estudiantes_table --table=estudiantes
```

> Renombra el fichero como _`[año]_[mes]_[día]`_`_000001_add_avatar_to_user_table.php`.

El contenido del método  `up()` de la migración añadirá el atributo `avatar` a la tabla  `estudiantes`.

```php
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('avatar')->nullable();
        });
    }
```

Por su parte, el contenido del método  `down()` de la migración borrará el atributo `avatar` de la tabla `estudiantes`.

```php
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
```

Lanzaremos la migración para que el atributo tenga reflejo en la base de datos.

```bash
php artisan migrate
```

## Modificar el formulario

Vamos a modificar el formulario de edición de un `Estudiante` para que permita la subida de un fichero. Para ello, además de crear un campo de tipo `file`, debemos indicar que el formulario va a enviar un fichero, por lo que debemos añadir el atributo `enctype="multipart/form-data"` al formulario.

{% raw %}
```php
<form action="{{ action([App\Http\Controllers\EstudianteController::class, 'putEdit'], ['id' => $estudiante->id]) }}" method="POST" enctype="multipart/form-data">
...
    <div class="form-group">
        <label for="avatar">Avatar</label>
        <input type="file" class="form-control" id="avatar" name="avatar" placeholder="Avatar">
    </div>

    <div class="form-group text-center">
...
```
{% endraw %}

## Elección del destino de los ficheros.

Vamos a utilizar el **driver public** para almacenar los ficheros en el disco `public`, para posibilitar el acceso público a los ficheros. Por defecto, el disco `public` almacena sus ficheros en `storage/app/public`.

Para hacer que estos ficheros sean accesibles desde la web, deberíamos crear un _enlace simbólico_ desde `public/storage` hasta `storage/app/public`. Para ello, podríamos utilizar el siguiente comando _Artisan_:

```bash
php artisan storage:link
```

> **Importante**: Este comando genera una ruta absoluta para el enlace simbólico y, como estamos utilizando _Docker_, esa ruta absoluta no funcionaría dentro del contenedor, por lo que es conveniente que el enlace simbólico lo generemos nosotros con los siguientes comandos:

```bash
cd public/
ln -s ../storage/app/public storage
cd ..
```

## Recibir y Almacenar el fichero

Para almacenar un fichero que se ha enviado en una petición _HTTP_, podemos utilizar el método `store()` de la instancia del fichero enviado en la petición.

Por eso, generaremos el siguiente código en `AvatarController`:

```php
    public function putEdit(Request $request, $id): RedirectResponse
    {
        $estudiante = Estudiante::findOrFail($id);

        // TODO: Eliminar el avatar anterior si existiera
        $path = $request->file('avatar')->store('avatars', ['disk' => 'public']);
        $estudiante->avatar = $path;
        $estudiante->save();

        $estudiante->update($request->all());
        return redirect()->action([self::class, 'getShow'], ['id' => $estudiante->id]);
    }
```

## Mostrando el fichero en la vista

Para mostrar el fichero en la vista, podemos utilizar el método `url()` de la clase `Storage`:

{% raw %}
```php
@if ($estudiante->avatar)
    <img src="{{ Storage::url($estudiante->avatar) }}" alt="Avatar" class="img-thumbnail">
@else
        <img width="300" style="height:300px" alt="Curriculum-vitae-warning-icon" src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9f/Curriculum-vitae-warning-icon.svg/256px-Curriculum-vitae-warning-icon.svg.png">
@endif
```
{% endraw %}
