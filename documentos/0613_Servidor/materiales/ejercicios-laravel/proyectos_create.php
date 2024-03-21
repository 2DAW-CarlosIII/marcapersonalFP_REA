<div class="row" style="margin-top:40px">
   <div class="offset-md-3 col-md-6">
      <div class="card">
         <div class="card-header text-center">
            Añadir proyecto
         </div>
         <div class="card-body" style="padding:30px">

            {{-- TODO: Abrir el formulario e indicar el método POST --}}

            {{-- TODO: Protección contra CSRF --}}

            <div class="form-group">
               <label for="nombre">Nombre</label>
               <input type="text" name="nombre" id="nombre" class="form-control">
            </div>

            <div class="form-group">
               {{-- TODO: Completa el input para el docente --}}
            </div>

            <div class="form-group">
               {{-- TODO: Completa el input para la URL de GitHub --}}
            </div>

            <div class="form-group">
               <label for="descripcion">Metadatos</label>
               <textarea name="metadatos" id="metadatos" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group text-center">
               <button type="submit" class="btn btn-primary" style="padding:8px 100px;margin-top:25px;">
                   Añadir proyecto
               </button>
            </div>

            {{-- TODO: Cerrar formulario --}}

         </div>
      </div>
   </div>
</div>
