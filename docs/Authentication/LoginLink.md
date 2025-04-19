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
    'loginUrl' => [
        'prefix' => false,
        'plugin' => false,
        'controller' => 'Account',
        'action' => 'login',
    ),
]);
```

Now you should be able to log your users in on top of classic form login.

Note: It is advised to set a `url` here, and to the login url. This way it will not collide
with other pages and possible `token` query strings.

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

Here you want to additionally confirm that the email matches. So make sure to add that to the Token content:
```php
$token = TableRegistry::getTableLocator()->get('Tools.Tokens')
    ->newKey('login_link', null, $user->id, $user->email);
```

### Sending mails via queue
It is highly advised to do any (email) sending here via queue.
Just add the `$token` into it and you are good to go:
```
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$token = ...;
$email = $user->email;
$data = [
    'to' => $user->email,
    'toName' => $user->full_name,
    'subject' => __('Login link'),
    'template' => 'login_link',
    'vars' => compact('email', 'token'),
];
$queuedJobsTable->createJob('Email', $data); // Your Email queue task
```

Put this in the email template (linking to your login action):
```php
<?= $this->Url->build(['controller' => 'Account', 'action' => 'login', '?' => ['token' => $token]], ['fullBase' => true]) ?>
```
