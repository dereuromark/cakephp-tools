# Tokens

Easily easily manage (store, retrieve, validate) tokens.
They are useful in the registration process of users,
or if you want to send some double-opt-in confirmation emails, for example.

The main methods of the model are
* newKey(string $type, ?string $key = null, $uid = null, $content = null)
* useKey(string $type, string $key, $uid = null)
* spendKey(int $id)

User and security relevant token usage should always be bound to the id of the user (user_id).
Other operations can also omit this field.

## Install
```
bin/cake migrations migrate -p Tools
```
If you need a different table schema, e.g. for user_id to be UUID, you can copy
over the migration file and customize. In that case execute it then without the plugin option.

## Usage for registration

### Register action
```php
$this->loadModel('Tools.Tokens');
$cCode = $this->Tokens->newKey('activate', null, $user->id);
```

### Activate action
```php
$this->loadModel('Tools.Tokens');
$token = $this->Tokens->useKey('activate', $keyToCheck);

if ($token && $token->used) {
    $this->Flash->warning(__('alreadyActivatedMessage'));
} elseif ($token) {
    $uid = $token->user_id;
    // Confirm activation and redirect to home
}
```

## Other usage



## Garbage Collect
From Cronjob/CLI
```php
$this->loadModel('Tools.Tokens');
$this->Tokens->garbageCollector();
```

## Upgrade Notes from 3.x
If you come from 3.x:
- The field `key` is now `token` to avoid SQL reserved keyword issue.
- Typehints are now in place.
