<nav id="nav">
  <ul>
      <li @if (Route::currentRouteName() == 'home' || true)
          class="current"
      @endif
          style="white-space: nowrap;"><a href="/"><img src="{{ asset('/images/mp-logo.png') }}" height="64px" alt="" style="position:relative;bottom: -22px"/></a></li>
      <li @if (Route::currentRouteName() == 'proyectos')
          class="current"
      @endif
          style="white-space: nowrap;"><a href="/proyectos">Proyectos</a></li>
      <li @if (Route::currentRouteName() == 'curriculos')
          class="current"
      @endif
          style="white-space: nowrap;"><a href="/curriculos">Currículos</a></li>
      <li @if (Route::currentRouteName() == 'actividades')
          class="current"
      @endif
          style="white-space: nowrap;"><a href="/actividades">Actividades</a></li>
      <li @if (Route::currentRouteName() == 'reconocimientos')
          class="current"
      @endif
          style="white-space: nowrap;"><a href="/reconocimientos">Reconocimientos</a></li>
      <li style="user-select: none; cursor: pointer; white-space: nowrap; opacity: 1;" class="opener">
          <a href="#">Usuario</a>

          <ul style="user-select: none; display: none; position: absolute;" class="">
              <li style="white-space: nowrap;"><a href="/login" style="display: block;">Login</a></li>
              <li style="white-space: nowrap;"><a href="/register" style="display: block;">Register</a></li>
              <li style="user-select: none; cursor: pointer; white-space: nowrap;" class="opener">
                  <a href="#" style="display: block;">Más opciones de usuario</a>
                  <ul style="user-select: none; display: none; position: absolute;" class="dropotron">
                      <li style="white-space: nowrap;"><a href="/dashboard" style="display: block;">Dashboard</a></li>
                      <li style="white-space: nowrap;"><a href="/profile.edit" style="display: block;">Profile</a></li>
                      <li style="white-space: nowrap;"><a href="/logout" style="display: block;">Logout</a></li>
                  </ul>
              </li>
          </ul>
      </li>
  </ul>
</nav>
<!-- Más adelante podremos utilizar nombres de rutas para acceder a las mismas, en lugar de las rutas relativas -->