# One Time Login Links

Send your user(s) an email with a login link.
Usually thats your login action with `?token={token}` appended.

You can generate one using:
```php
$tokensTable = $this->fetchTable('Tools.Tokens');
$token = $tokensTable->newKey('login_link', null, $user->id);
```

If you want a custom token to be used:
```php
$token = $tokensTable->newKey('login_link', '123', $user->id);
```

Then, implement your authenticator on the other side in your Application.php:

```php
$service->loadIdentifier('Authentication.JwtSubject', [
    'resolver' => [
        'className' => 'Authentication.Orm',
        'finder' => 'active',
    ],
]);

// Session, Form, Cookie first
$service->loadAuthenticator('Tools.LoginLink', [
    'loginUrl' => Router::url([
        'prefix' => false,
        'plugin' => false,
        'controller' => 'Account',
        'action' => 'login',
    ]),
]);
```

Now you should be able to log your users in on top of classic form login.
