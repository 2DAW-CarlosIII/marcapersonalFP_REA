# 11. Manejar Ficheros en la API

Como comentamos en el capítulo dedicado a los [formularios y datos de entrada](./RA5_6_formularios.md#ficheros) y en el capítulo [utilización de ficheros](./0476_utilizarFicheros.md), _Laravel_ facilita el manejo de ficheros, tanto si se van a almacenar en el sistema de ficheros _local_, como si se van a almacenar en un servicio como _Amazon S3_.

En esta práctica guiada, vamos a utilizar el driver que incluye para el almacenamiento en el sistema de archivos `public` para conseguir subir al servidor un archivo comprimido asociado al proyecto, que posteriormente enviaremos a GitHub para su despliegue.

## Backend

### Modelo `Proyecto`

Para poder manejar el atributo `fichero` en operaciones masivas, debemos modificar la propiedad `fillable`del modelo `app/Models/Proyecto.php`, añadiendo el atributo `fichero` al _array_:

```php
    protected $fillable = [
        'nombre',
        'docente_id',
        'dominio',
        'metadatos',
        'calificacion',
        'fichero',
    ];
```

### Controlador de proyectos

Ya disponemos de un controlador de proyectos para gestionar los _endpoints_ de ese tipo de recurso. No obstante, vamos a ampliar su funcionalidad para que acepte un fichero asociado a la ruta `PUT /proyectos/{proyecto}`, por lo que tendremos que modificar el método `update` de `app/Http/Controllers/API/ProyectoController.php` con el siguiente contenido:

```diff
     public function update(Request $request, Proyecto $proyecto)
     {
+        $proyectoData = $request->all();
+        if($proyectoRepoZip = $request->file('fichero')) {
+            $request->validate([
+                'fichero' => 'required|mimes:zip,rar,bz,bz2,7z|max:5120', // Se permiten ficheros comprimidos de hasta 5 MB
+            ], [
+                'fichero.required' => 'Por favor, selecciona un fichero.',
+                'fichero.mimes' => 'El fichero debe ser un fichero ZIP.',
+                'fichero.max' => 'El tamaño del fichero no debe ser mayor a 5 MB.',
+            ]);
+
+            $path = $proyectoRepoZip->store('repoZips', ['disk' => 'public']);
+            $proyectoData['fichero'] = $path;
+        } else {
+            $proyectoData['fichero'] = $proyecto->fichero;
+        }
 
-        $proyectoData = json_decode($request->getContent(), true);
         $proyecto->update($proyectoData);

        return new ProyectoResource($proyecto);
    }
```
### Cambios en el recurso devuelto

Cuando realicemos las modificaciones en React-Admin, el componente `<FileInput />` va a requerir, para rellenar convenientemente el componente `<FileField>`, que se devuelva un array o un objeto que contenga una propiedad `src` y una propiedad `title`. Por eso, modificaremos el fichero `app/Http/Resources/ProyectoResource.php` para que devuelva un array con esas propiedades:

```diff
             'tutor' => $this->docente ? $this->docente->nombre . " " . $this->docente->apellidos : null,
             'ciclos' => CicloResource::collection($this->ciclos),
             'estudiantes' => $this->users()->get(),
+            'attachments' => $this->fichero
+                ? [
+                    'src' => ('/storage/' . $this->fichero),
+                    'title' => $this->nombre
+                  ]
+                : null,
         ]);
     }
 }

```

Con los cambios anteriores, ya tendríamos preparado el _Backend_.

## Frontend

### Página Proyectos

En la página de _React-admin_ correspondiente a los proyectos (`resources/js/react-admin/Pages/Proyectos.jsx`), añadiremos un componente `<FileInput />` que permitirá enviar el fichero del proyecto.

Para ello, haremos las siguientes modificaciones en el archivo:

```diff
    SelectInput,
+    FileInput, FileField,
    ShowButton,
```
...
```diff
         <TextInput source="dominio" />
         <TutorInput />
         <NumberInput source="calificacion" />
+        <FileInput source="attachments" label="Archivo comprimido con el proyecto">
+            <FileField source="src" title="title" />
+        </FileInput>
     </SimpleForm>

    </Edit>
```

### DataProvider

Hasta el momento, las peticiones `PUT` realizadas por el `DataProvider` de React-admin han funcionado correctamente. Pero, la función `update`del `DataProvider` no funciona si hay que enviar ficheros, siendo necesario ampliar las funcionalidades del `DataProvider` utilizado por React-admin.

Para ello, vamos a modficar el fichero `resources/js/react-admin/dataProvider.ts` para añadir esas funcionalidades extendidas que necesitamos:

```diff
const dataProvider = jsonServerProvider(
     httpClient
 );

+const originalDataProvider = jsonServerProvider(
+    import.meta.env.VITE_JSON_SERVER_URL,
+    httpClient
+);
+
 const apiUrl = `${import.meta.env.VITE_JSON_SERVER_URL}`;
```

y

```diff
     });
 };

+dataProvider.update = (resource, params) => {
+    if (resource !== 'proyectos' || !params.data.attachments) {
+        return originalDataProvider.update(resource, params);
+    }
+
+    let formData = new FormData();
+    for (const property in params.data) {
+        formData.append(`${property}`, `${params.data[property]}`);
+    }
+
+    formData.append('fichero', params.data.attachments.rawFile)
+    formData.append('_method', 'PUT')
+
+    const url = `${apiUrl}/${resource}/${params.id}`
+    return httpClient(url, {
+        method: 'POST',
+        body: formData,
+    })
+    .then(json => {
+        return {
+            ...json,
+            data: json.json
+        }
+    })
+}
+
 export { dataProvider };
```

Como podemos observar, si la petición no se refiere a la actualización de los proyectos o no incluye un fichero, se envía al `dataProvider` básico. En caso contrario, se genera una petición `PUT` haciendo que los datos enviados viajen en el `body` de la petición como `formData`.
