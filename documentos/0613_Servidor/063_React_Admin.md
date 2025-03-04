# React-admin

_React-admin_ es un _framework_ que cubre la mayoría de las necesidades de las aplicaciones típicas de administración y _B2B_. Es un gran ahorro de tiempo que desbloquea tu creatividad y te ayuda a construir grandes aplicaciones. Sus características únicas y sesgadas lo convierten en la mejor opción para los desarrolladores de **Single-Page Apps**.

## Conceptos clave

_React-admin_ se basa en varias decisiones de diseño que estructuran su código base.

### Aplicación de una sola página

_React-admin_ está diseñado específicamente para construir **Single-Page Applications (SPA)**. En una aplicación _react-admin_, el navegador obtiene el _HTML_, _CSS_ y _JavaScript_ requeridos para renderizar la aplicación solo una vez. Posteriormente, los datos se obtienen de las _APIs_ a través de llamadas _AJAX_. Esto contrasta con las aplicaciones web tradicionales, donde el navegador obtiene una nueva página _HTML_ para cada pantalla.

![Ciclo de vida de SPA](https://marmelab.com/react-admin/img/SPA-lifecycle.png)

La arquitectura _SPA_ garantiza que las aplicaciones _react-admin_ sean excepcionalmente rápidas, fáciles de alojar y compatibles con las _APIs_ existentes sin requerir un _backend_ dedicado.

Para lograr esto, _react-admin_ utiliza un enrutador interno, impulsado por `react-router`, para mostrar la pantalla apropiada cuando el usuario hace clic en un enlace. Los desarrolladores pueden definir rutas usando el componente `<Resource>` para rutas _CRUD_ y el componente `<CustomRoutes>` para otras rutas.

Por ejemplo, la siguiente aplicación _react-admin_:

```javascript
import { Admin, Resource, CustomRoutes } from 'react-admin';
import { Route } from 'react-router-dom';

export const App = () => (
    <Admin dataProvider={dataProvider}>
        <Resource name="labels" list={LabelList} edit={LabelEdit} show={LabelShow} />
        <Resource label="genres" list={GenreList} />
        <Resource name="artists" list={ArtistList} edit={ArtistDetail} create={ArtistCreate}>
            <Route path=":id/songs" element={<SongList />} />
            <Route path=":id/songs/:songId" element={<SongDetail />} />
        </Resource>
        <CustomRoutes>
            <Route path="/profile" element={<Profile />} />
            <Route path="/organization" element={<Organization />} />
        </CustomRoutes>
    </Admin>
);
```

Declara las siguientes rutas:

```javascript
    /labels: <LabelList>
    /labels/:id: <LabelEdit>
    /labels/:id/show: <LabelShow>
    /genres: <GenreList>
    /artists: <ArtistList>
    /artists/:id: <ArtistDetail>
    /artists/create: <ArtistCreate>
    /artists/:id/songs: <SongList>
    /artists/:id/songs/:songId: <SongDetail>
    /profile: <Profile>
    /organization: <Organization>
```

El componente `<Resource>` permite que `react-admin` vincule automáticamente las páginas _CRUD_ entre sí, incluidas las de entidades relacionadas. Este enfoque le permite pensar en su aplicación en términos de _entidades_, en lugar de verse abrumado por la administración de rutas.

### _Providers_

_React-admin_ no hace suposiciones sobre la estructura específica de su _API_. En cambio, define su propia sintaxis para la obtención de datos, la autenticación, la internacionalización y las preferencias. Para interactuar con su _API_, _react-admin_ se basa en adaptadores llamados _providers_.

![Providers de React-admin](https://marmelab.com/react-admin/img/providers.png)

Por ejemplo, para obtener una lista de registros de la _API_, usaría el objeto _dataProvider_ de la siguiente manera:

```javascript
dataProvider.getList('posts', {
    pagination: { page: 1, perPage: 5 },
    sort: { field: 'title', order: 'ASC' },
    filter: { author_id: 12 },
}).then(response => {
    console.log(response);
});
// {
//     data: [
//         { id: 452, title: "Harry Potter Cast: Where Now?", author_id: 12 },
//         { id: 384, title: "Hermione: A Feminist Icon", author_id: 12 },
//         { id: 496, title: "Marauder's Map Mysteries", author_id: 12 },
//         { id: 123, title: "Real-World Roots of Wizard Spells", author_id: 12 },
//         { id: 189, title: "Your True Hogwarts House Quiz", author_id: 12 },
//     ],
//     total: 27
// }
```

El método `dataProvider.getList()` es responsable de traducir esta solicitud en la solicitud _HTTP_ apropiada a su _API_. Al utilizar el proveedor de datos _REST_, el código anterior se traducirá a:

```http
GET http://path.to.my.api/posts?sort=["title","ASC"]&range=[0, 4]&filter={"author_id":12}

HTTP/1.1 200 OK

Content-Type: application/json
Content-Range: posts 0-4/27
[
    { id: 452, title: "Harry Potter Cast: Where Now?", author_id: 12 },
    { id: 384, title: "Hermione: A Feminist Icon", author_id: 12 },
    { id: 496, title: "Marauder's Map Mysteries", author_id: 12 },
    { id: 123, title: "Real-World Roots of Wizard Spells", author_id: 12 },
    { id: 189, title: "Your True Hogwarts House Quiz", author_id: 12 },
]
```

_React-admin_ viene con más de 50 proveedores de datos para varios backends, incluidos _REST_, _GraphQL_, _Firebase_, _Django REST Framework_, _API Platform_ y más. Si estos proveedores no se adaptan a su API, tiene la flexibilidad de desarrollar un proveedor personalizado.

Este enfoque es la razón por la que los componentes _react-admin_ no llaman directamente a `fetch` o `axios`. En cambio, confían en el proveedor de datos para obtener datos de la _API_. Del mismo modo, se recomienda que sus componentes personalizados sigan el mismo patrón y utilicen los _hooks_ del proveedor de datos, como `useGetList`:

```javascript
import { useGetList } from 'react-admin';

const MyComponent = () => {
    const { data, total, loading, error } = useGetList('posts', {
        pagination: { page: 1, perPage: 5 },
        sort: { field: 'title', order: 'ASC' },
        filter: { author_id: 12 },
    });

    if (loading) return <Loading />;
    if (error) return <Error />;
    return (
        <div>
            <h1>Found {total} posts matching your query</h1>
            <ul>
                {data.map(record => (
                    <li key={record.id}>{record.title}</li>
                ))}
            </ul>
        </div>
    )
};
```

Al usar `useGetList`, obtiene varios beneficios más allá de un simple `fetch`: maneja las credenciales del usuario, activa los indicadores de carga, administra los estados de carga, maneja los errores, almacena en caché los resultados para un uso futuro y controla la forma de los datos, entre otras cosas.

Cada vez que necesite comunicarse con un servidor, utilizará estos _proveedores_. Dado que están especializados en sus respectivos dominios y están estrechamente integrados con react-admin, le ahorrarán una cantidad significativa de tiempo y esfuerzo.

## Referencias

[Documentación oficial de React-Admin](https://marmelab.com/react-admin/Architecture.html)
