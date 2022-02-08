# Tokens

Easily easily manage (store, retrieve, validate) tokens.
They are useful in the registration process of users,
or if you want to send some double-opt-in confirmation emails, for example.

The main methods of the model are
* newKey(string $type, ?string $key = null, $uid = null, $content = null)
* useKey(string $type, string $key, $uid = null)
* spendKey(int $id)

User and security relevant token usage should always be bound to the id of the user (user_id).
Here you should also use one-time tokens only.

Other operations can also omit this field.
Here you could also use unlimited tokens if needed.

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
$tokenKey = $this->Tokens->newKey('activate', null, $user->id);
```

As 4th parameter any string content can be stored.

### Activate action
```php
$this->loadModel('Tools.Tokens');
$token = $this->Tokens->useKey('activate', $tokenKey);

if ($token && $token->used) {
    $this->Flash->warning(__('Already activated'));
} elseif ($token) {
    $uid = $token->user_id;
    // Confirm activation and redirect to home
}
```

## Other usage

### Changing email
Here the 4th argument comes in handy.
The new email address one will be stored in content until
validation is complete and will then replace the old one.

### One time email links
Login or otherwise.


## Garbage Collect
From Cronjob/CLI
```php
$this->loadModel('Tools.Tokens');
$this->Tokens->garbageCollector();
```

## Stats
There is also a method `stats()` to retrieve statistics if required/useful to you.

## Security notes
By default, the tokens have a validity of one week.
You can modify this value in your model.

Do not send plain passwords with your emails or print them out anywhere.
That's why you should send the expiring tokens.

If you feel like you need more information on the implementation process,
read [this article at troyhunt.com](http://www.troyhunt.com/2012/05/everything-you-ever-wanted-to-know.html).
It describes in a very verbose way what to do and what better not to do.

## Upgrade Notes from 3.x
If you come from 3.x:
- The field `key` is now `token_key` to avoid SQL reserved keyword issue.
- Typehints are now in place.
