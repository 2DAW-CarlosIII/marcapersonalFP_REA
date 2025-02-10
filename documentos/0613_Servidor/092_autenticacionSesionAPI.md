# Autenticación con Sesiones en API

Aunque la norma general es utilizar la autenticación por tokens en las API, en ocasiones, puede ser necesario utilizar la autenticación por sesiones. Por ejemplo, si queremos utilizar la API desde una aplicación web, que ya utiliza la autenticación por sesiones.

## Middlewares que afectan a las sesiones

Si queremos utilizar la autenticación por sesiones en la API, debemos hacer que a las rutas definidas en `routes/api.php` les afecten los middlewares `EncryptCookies` y `StartSession`. Para ello, debemos modificar el fichero `bootstrap/app.php`, para añadir esos dos _middleware_ en el array `api` de la propiedad `$middlewareGroups`:

```diff

     ->withMiddleware(function (Middleware $middleware) {
         $middleware->api(prepend: [
-            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
+            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
             ReactAdminResponse::class,
+            Illuminate\Cookie\Middleware\EncryptCookies::class,
+            \Illuminate\Session\Middleware\StartSession::class,
         ]);
```

## Adaptando el logout

El tener dos vistas con el Login, nos genera un problema a la hora de hacer el `logout`, ya que no sabemos desde qué vista se ha hecho el login. Para solucionar este problema, debemos modificar el fichero `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, para que, en lugar de responder con una redirección, envíe, tan solo, una respuesta _JSON_ con el estado `200`:

```diff
-    public function destroy(Request $request): RedirectResponse
+    public function destroy(Request $request)
     {
         Auth::guard('web')->logout();
 
@@ -48,6 +48,6 @@ public function destroy(Request $request): RedirectResponse
 
         $request->session()->regenerateToken();
 
-        return redirect('/');
+        return response()->json(['message' => 'Sesión cerrada correctamente']);
     }
 }
```

Para poder hacer una petición `POST /logout` debemos hacer que no se solicite el `@csrf`. Para ello, debemos modificar el fichero `app/Http/Middleware/VerifyCsrfToken.php`, :

```diff
     protected $except = [
-        //
+        '/logout',
     ];
```

## Cambios en React-admin

### AuthProvider

```javascript
// in resources/js/react_admin/authProvider.js

import { dataProvider } from "./dataProvider";

export const authProvider = {
    login: ({ username, password }) => {
        return dataProvider.postLogin( username, password )
        .then(response => {
            if (response.status < 200 || response.status >= 300) {
              throw new Error(response.statusText);
            }
        })
        .catch((e) => {
                throw new Error('Network error')
        });
    },
    logout: () => {
        return dataProvider.postLogout()
        .then(response => {
            if (response.status < 200 || response.status >= 300) {
              throw new Error(response.statusText);
            }
        })
        .then(() => {return `${import.meta.env.VITE_JSON_SERVER_URL}/../../`})
        .catch((e) => {
                throw new Error('Network error')
        });
    },
    checkAuth: () =>{
        return dataProvider.getIdentity()
            .then(( data ) => {
                return data.json
            })
            .catch(() => {
                throw new Error('Network error')
            });
    },
    checkError: (error) => {
        return Promise.resolve();
    },
    getIdentity: () => {
        return dataProvider.getIdentity()
            .then(( data ) => {
                return data.json
            })
            .catch(() => {
                throw new Error('Network error')
            });
    },
    getPermissions: () => Promise.resolve('')
};
```

### dataProvider

```javascript
// in resources/js/react-admin/dataProvider.ts
import { fetchUtils } from 'react-admin';
import jsonServerProvider from "ra-data-json-server";
import { stringify } from 'query-string';

const httpClient = (url, options = {}) => {
    if (!options.headers) {
        options.headers = new Headers({ Accept: 'application/json' });
    }
    const token = localStorage.getItem('auth') ? JSON.parse(localStorage.getItem('auth')) : undefined
    if (token) {
        options.headers.set('Authorization', `${token.token_type} ${token.access_token}`);
    }
    return fetchUtils.fetchJson(url, options);
};

const dataProvider = jsonServerProvider(
    import.meta.env.VITE_JSON_SERVER_URL,
    httpClient
);

const apiUrl = `${import.meta.env.VITE_JSON_SERVER_URL}`;

dataProvider.getMany = (resource, params) => {
    const query = {
        id: params.ids,
    };
    const url = `${apiUrl}/${resource}?${stringify(query, {arrayFormat: 'bracket'})}`;
    return httpClient(url).then(({ json }) => ({ data: json }));
};

dataProvider.createToken = (email, password) => {
    return httpClient(`${apiUrl}/tokens`, {
        method: 'POST',
        body: JSON.stringify({ email, password }),
        headers: new Headers({ 'Content-Type': 'application/json' }),
    });
};

dataProvider.deleteToken = () => {
    return httpClient(`${apiUrl}/tokens`, {
        method: 'DELETE',
        headers: new Headers({ 'Content-Type': 'application/json' }),
    });
};

dataProvider.getIdentity = () => {
    return httpClient(`${apiUrl}/user`, {
        method: 'GET',
        headers: new Headers({ 'Content-Type': 'application/json' }),
    });
};

dataProvider.postLogin = (email, password) => {
    return httpClient(`${apiUrl}/login`, {
        method: 'POST',
        body: JSON.stringify({ email, password }),
        headers: new Headers({ 'Content-Type': 'application/json' }),
    });
};

dataProvider.postLogout = () => {
    return httpClient(`${apiUrl}../../../logout`, {
        method: 'POST',
        headers: new Headers({ 'Content-Type': 'application/json' }),
    });
};

export { dataProvider };
```