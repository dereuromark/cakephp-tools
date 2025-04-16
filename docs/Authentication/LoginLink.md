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
$service->loadIdentifier('Tools.LoginLink', [
    'resolver' => [
        'className' => 'Authentication.Orm',
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

### Email confirmation

When you not just send login link emails to already verified users, but if you
replace the registration login process with such quick-logins, you will need to
use a callback to set the email active when the authenticator gets called.
This way your custom finder (e.g. `'active'`) will actually find the user then.

```php
$service->loadIdentifier('Tools.LoginLink', [
    'resolver' => [
        'className' => 'Authentication.Orm',
        'finder' => 'active',
    ],
    'preCallback' => function (int $id) {
        TableRegistry::getTableLocator()->get('Users')->confirmEmail($id);
    },
]);
```
