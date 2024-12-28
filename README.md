# nx-model

mvc-model for nx


> composer require veasin/nx-model

```php
class users extends multiple{
    const TABLE_NAME = 'user';
}
class user extends single{
    const MULTIPLE = users::class;
}
```