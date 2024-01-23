# Encryption Behavior

A CakePHP behavior to automatically encrypt and decrypt data passed through the ORM.

## Technical limitation
* Be aware, that your table columns need to be in a **binary** format and **large enough** to contain the encrypted payload. Something like `varbinary(1024)`
* You are no longer able to search, filter or join with those specific columns on a database level.
* The encryption key needs to be at least 32 characters long. See [here](https://book.cakephp.org/5/en/core-libraries/security.html) to learn more.

## Usage
Attach it to your model's `Table` class in its `initialize()` method like so:
```php
$this->addBehavior('Tools.Encryption', [
    'fields' => ['secret_field'],
    'key' => \Cake\Core\Configure::read('Security.encryption')
]);
```

After attaching the behavior, a call like

```php
$user = $this->Users->newEmptyEntity();
$user = $this->Users->patchEntity($user, [
    'username' => 'cake',
    'password' => 'a random generated string hopefully'
    'secret_field' => 'my super mysterious secret'
]);
$this->Users->saveOrFail($user);
```

will result in the `secret_field` to be automatically encrypted.

Same goes for when you are fetching the entry from the ORM via

```php
$user = $this->Users->get($id);
// or
$users = $this->Users->find()->all();
```

will automatically decrypt the binary data.

## Recommendations

* Please do not use encryption if you don't need it! Password authentication for user login should always be implemented via hashing, not encryption.
* It is recommended to use a separate encryption key compared to your `Security.salt` value.
