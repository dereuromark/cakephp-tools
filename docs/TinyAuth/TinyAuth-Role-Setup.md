## Role Setup for TinyAuth

The CakePHP default basically is User belongsTo Role (1:N).
This "single role" setup works for most of the apps.

If you really need a "multi role" setup where users can have multiple roles at once, you need an extra pivot table (users_roles per convention) that contains the role information per user. The User model doesn't need a role_id field then. In both cases the Role model just contains information about a role, nothing else.

The following expects that you have read and understood the [TinyAuth docs](TinyAuth.md).

### Single role
If you don't even want to maintain a Role model and roles table, something like this suffices:
```php
$config['Role'] = array(
	'admin' => '1',
	'moderator' => '2',
	'user' => '3',
);
```

The session data of a logged in user then needs to look something like this:
```php
'Auth' => [
    'User' => [
        'id' => '1'
        'role_id' => '3'
    ]
]
```
This is pretty much CakePHP standard for a simple belongsTo relation from User to Role (the Role model data itself is not necessary here, as "role_id" contains all we need.

See [cakefest.dereuromark.de](http://cakefest.dereuromark.de/) and the github code for a live example.
Also don't forget to check the test cases for Tiny. The reveal a lot of insight.

### Multi role
You will basically have a User and a Role model, and a pivot table roles_users with (role_id, user_id).

For your AppController setup, you want to contain the HABTM Role:
```php
'authenticate' => [
    'Form' => [
        'contain' => ['Role']
    ]
]
```

As you can see from the [test cases for Tiny](https://github.com/dereuromark/tools/blob/master/Test/Case/Controller/Component/Auth/TinyAuthorizeTest.php), upon login the Session should look like this:
```php
'Auth' => [
    'User' => [
        'id' => '1'
        'Role' => [
            [
                'id' => '1',
                'RoleUser' => []
            ],
            [
                'id' => '3',
                'RoleUser' => []
            ]
        ]
    ]
]
```
This is the default structure when working with basic HABTM relations.

Of course, you can also use your own AuthComponent / login hook where you simply add all the user's role as flat list:
```php
'Auth' => [
    'User' => [
        'id' => '1'
        'Role' => ['1', '3']
    ]
]
```
That would be accepted from TinyAuth, as well.

I don't have an open source example for this, so I just copied out details from an existing app.


### A note about role access inside code/templates
Basic role based access to actions is nice. But sometimes you also need role based decisions inside controller actions or the view templates, helpers or elements.

If you are using a Role model + roles table you should have a slug field in there so you can identify the user's role(s) in the code.
These can then either be hard-coded in the config, or added to it at runtime (once, ideally cached).

So in your code and using Auth class as described in the blog post, you can use them anywhere like this:
```php
if (Auth::hasRole(Configure::read('moderator'))) {
    echo $moderatorLinks;
}
```

If you are using configure instead of a Role model, this is even easier as you can replace the magic numbers from above with constants.

So in your bootstrap you define the roles as this:
```php
define('ROLE_ADMIN', 1);
define('ROLE_MODERATOR', 2);
define('ROLE_USER', 3);
```

In your config they now look like this:
```php
$config['Role'] = array(
	'admin' => ROLE_ADMIN,
	'moderator' => ROLE_MODERATOR,
	'user' => ROLE_USER,
);
```

So in your code and using Auth class as described in the blog post, you can use them anywhere like this:
```php
if (Auth::hasRoles(array(ROLE_ADMIN, ROLE_MODERATOR)) {
    echo $this->element('moderator_info_box');
}
```