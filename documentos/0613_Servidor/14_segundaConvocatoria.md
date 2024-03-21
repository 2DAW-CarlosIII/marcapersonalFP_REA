## Segunda convocatoria

## Preparar el código para el examen:

* crea una variable de entorno llamada EMAIL_EMPRESA, cuyo valor será tu email de alu.murciaeduca.es
* asegúrate de que tienes el entorno actualizado:
    * cambia a la rama master del proyecto marcapersonalfp23_24
    * actualiza la rama master
    * crea una rama llamada segundaConvocatoria
    * haz que se reinicie la base de datos y que incorpore los datos semilla iniciales
    * toma nota del _token_ de la empresa de prueba, por si lo necesitaras para autenticarte como empresa
* al finalizar el examen deberás hacer un _Pull Request_ de tu rama

> **Notas:**
>
> * Copilot debe estar deshabilitado (_Disable Completions_) y no se puede utilizar la inteligencia artificial para la resolución de ninguno de estos ejercicios
> * El examen debe hacerse en el ordenador del aula. No está permitida la realización en el ordenador portátil del estudiante.
> * Se aconseja el uso de Rested para probar algunos de los endpoints


## Ejercicios

**Queremos llevar un control de los estudiantes que han dado permiso para que determinadas empresas puedan descargar su currículo en pdf.**

1. Para ello, debemos crear una tabla `permisos_descargas` que tendrá los siguientes atributos:

	* **`curriculo_id`**: será clave ajena de la tabla `curriculos` e indicará el `Curriculo` que se pretende descargar,
	* **`empresa_id`**: será clave ajena de la tabla de `users` e indicará el usuario que ha solicitado la descarga del `Curriculo`
	* **`validado`**: este atributo será `NULL` cuando la `Empresa` solicita la visualización de un `Curriculo`, mientras que tendrá `true` cuando el `Estudiante` haya otorgado a la `Empresa` el derecho a la descarga.

    > La tabla anterior llevará, para mayor sencillez, los atributos `id` y los correspondientes a los _timestamps_.

<hr />

2. Debemos posibilitar que una `Empresa` realice una petición `POST` al endpoint `/api/v1/curriculos/{id}/permisoDescarga`.

    * El resultado de la ejecución correcta de la funcionalidad asociada a ese endpoint deberá:
        * crear una fila en la tabla `permisos_descargas`, cuyo contenido será
            * **`curriculo_id`**: el `id` enviado como parámetro
            * **`empresa_id`**: el identificador del usuario autenticado
            * **`validado`**: `NULL`
        * devolverá un _resource_ del tipo `PermisoDescarga` con la información de la fila añadida.

    > No será necesario crear un controlador nuevo, la lógica se puede implementar en el controlador de currículos.
    > Ten en cuenta que si decides crear un modelo asociado a la tabla `permisos_descargas`, es posible que tengas que indicar, en el modelo, el nombre de la tabla con el que está relacionado.
    > Para autenticarte como `Empresa`, deberás enviar una petición `GET` al endpoint `empresas/acceso/{token}`, donde el _token_ será el que se ha asociado a la empresa de prueba en el momento de crear los datos iniciales.

<hr />

3. El _endpoint_ anterior únicamente lo podrá ejecutar un usuario que tenga una empresa asociada. Para ello, se puede utilizar el método `esEmpresa()` del modelo `User`.

<hr />

4. También necesitamos dar la posibilidad a un estudiante de realizar una petición `PUT` al _endpoint_ `/api/v1/curriculos/{id}/permitirDescarga`.

    * El resultado de la ejecución correcta de la funcionalidad asociada a ese _endpoint_ deberá:
        * buscar el `Curriculo` asociado al `User` que está autenticado.
        * buscar la fila (sólo una), en la tabla `permisos_descargas`, que cumpla que:
            * el valor de `curriculo_id` es el `id` del `Curriculo` encontrado en el punto anterior
            * y el valor de `empresa_id` es el `id` enviado como parámetro.
        * poner a `true` el valor del atributo `validado`
        * devolverá un _resource_ del tipo `PermisoDescargaResource` con la información del `Curriculo` del `Estudiante`.
    
    > Al ser una consulta a la base de datos que contiene dos criterior, habrá que utilizar dos veces el método `where` para realizarla.

<hr />

5. El _endpoint_ anterior únicamente lo podrá ejecutar un `Estudiante`.

<hr />

6. Existe una tercera opción para el valor del atributo `validado`: `false`. El significado de un valor `false` en el atributo `validado` es que una `Empresa` ha sido _banneada_ por un `Estudiante`, el cual ha considerado que la `Empresa` ha realizado un mal uso de su `Curriculo` en _pdf_. Debemos crear un _middleware_ que devuelva un código `412 (Precondition Failed)` si un usuario tiene su `id` asociado a un valor `false` en alguna de las filas de la tabla `permisos_descargas`.

<hr />

7. Haz que, antes de devolver el _resource_ correspondiente a la ejecución del endpoint `/api/v1/curriculos/{id}/permisoDescarga` se le envíe al `Estudiante` asociado al `Curriculo` un mensaje de correo electrónico avisándole de que una `Empresa` quiere ver su currículo.
