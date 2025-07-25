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

## todo

* [x] S.delete() -> S.destroy() rename
* [ ] S.reload() reload from db
* [ ] S.save(...fieldNames) only save some fields
* [ ] S['id'] need support?
* [ ] M.find() -> yield<data|obj> ?
* [ ] M.truncate() ?
* [ ] M.define() ?
  * [ ] add getter&setter ?
  * [ ] validate ?
  * [ ] M.sync() ? CREATE TABLE IF NO EXISTS `name` (...)
