# Relaciones

A menudo, una tabla de la base de datos está relacionadas con alguna otra tabla. Por ejemplo, un `curriculo` está relacionado con un `user`.

_Eloquent_ facilita la gestión de estas relaciones y soporta las relaciones más comunes:

- [Uno a Uno](./081_relaciones_unoAuno.md)
- [Uno a Muchos](./082_relaciones_unoAmuchos.md)
- [Muchos a Muchos](./083_relaciones_muchosAmuchos.md)
- [Uno a Uno a través de una tabla pivot](https://laravel.com/docs/12.x/eloquent-relationships#has-one-through)
- [Uno a Muchos a través de una tabla pivot](https://laravel.com/docs/12.x/eloquent-relationships#has-many-through)
- [Uno a Uno (Polymórficas)](https://laravel.com/docs/12.x/eloquent-relationships#one-to-one-polymorphic-relations)
- [Uno a Muchos (Polymórficas)](https://laravel.com/docs/12.x/eloquent-relationships#one-to-many-polymorphic-relations)
- [Muchos a Muchos (Polymórficas)](https://laravel.com/docs/12.x/eloquent-relationships#many-to-many-polymorphic-relations)

Aunque en la lista anterior incluye todos los tipos de relaciones, para el alcance de este curso, veremos las más usuales.

## Definiendo Relaciones

Las relaciones en _Eloquent_ se definen como **métodos** en las clases correspondientes a los modelos. En los capítulos siguientes, veremos cómo definir cada una de las relaciones de los modelos.
