# nx-model

mvc-model for nx


> composer require veasin/nx-model

```php
class users extends multiple{
    const TABLE = 'user u';
}
class user extends single{
    const MULTIPLE = users::class;
}
```

```php
$table =users::sql();
$user->list();
```