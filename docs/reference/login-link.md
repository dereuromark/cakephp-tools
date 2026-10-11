# One Time Login Links

Send your user(s) an email with a login link.
Usually thats your login action with `?token={token}` appended.

You can generate one using:
```php
$tokensTable = $this->fetchTable('Tools.Tokens');
$token = $tokensTable->newKey('login_link', null, $user->id);
```

For shorter-lived login links, configure the token type once and every new row will persist that validity:
```php
$tokensTable->typeValidity = [
    'login_link' => 86400,
];
```

If you want a custom token to be used:
```php
$token = $tokensTable->newKey('login_link', '123', $user->id);
```

Then, implement your authenticator on the other side in your Application.php:

```php
// Session, Form, Cookie first
$service->loadAuthenticator('Tools.LoginLink', [
    'identifier' => [
        'className' => 'Tools.LoginLink',
        'resolver' => [
            'className' => 'Authentication.Orm',
            'userModel' => 'Users',
        ],
    ],
    'loginUrl' => [
        'prefix' => false,
        'plugin' => false,
        'controller' => 'Account',
        'action' => 'login',
    ],
    'urlChecker' => 'Authentication.Default',
]);
```

Now you should be able to log your users in on top of classic form login.

Note: Set `loginUrl` here, and to the login url. This way it will not collide
with other pages and possible `token` query strings.

### Email matching and callbacks

Include the user's current email address in the token content when an existing login link must stop working after an email change:

```php
$token = $tokensTable->newKey('login_link', null, $user->id, $user->email);
```

The authenticator checks this content against the resolved user's nonempty email. By default, `oneTime` is enabled and the token is consumed before identity lookup, including a failed lookup or email mismatch.

`Tools.LoginLink` identifiers support `preCallback` before resolving the user. This callback runs before the authenticator's email-content check. Do not use them as proof that an email address has been verified. Registration confirmation needs a separate flow that validates the token and expected address before updating the account.

### Sending mails via queue
It is highly advised to do any (email) sending here via queue.
Just add the `$token` into it and you are good to go:
```php
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
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
