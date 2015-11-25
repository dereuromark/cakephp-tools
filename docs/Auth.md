# Auth

## ModernPasswordHasher for Authentication
You are tired of sha1 and other hashing algos that are not designed for hashing passwords and because they
aren't secure? Use cutting edge 5.5 PHP (and CakePHP 3 core) functionality (shimmed to work even with 5.4) now.

```php
$this->Auth->authenticate = array(
	'Form' => array(
		'passwordHasher' => 'Tools.Modern',
		'scope' => array('status' => User::STATUS_ACTIVE),
	)
);
```

It can also be used inside of other authentication classes, e.g. when you use FriendsOfCake/Authenticate:
```php
$this->Auth->authenticate = array(
	'Authenticate.MultiColumn' => array(
		'passwordHasher' => 'Tools.Modern',
 		'columns' => array('username', 'email'),
 		'userModel' => 'User',
 		'scope' => ...,
 		'fields' => ...,
 	),
	...
);
```


### Providing BC for old passwords
Taking it one step further: We also want to continue supporting the old hashs, and slowly upgrading
them to the new ones.

This can easily be done using the Fallback hasher class:
```php
$this->Auth->authenticate = array(
	'Authenticate.MultiColumn' => array(
		'passwordHasher' => array(
			'className' => 'Tools.Fallback',
			'hashers' => array(
				'Tools.Modern', 'Simple'
			)
		),
 		'columns' => array('username', 'email'),
 		'userModel' => 'User',
 		'scope' => ...,
 		'fields' => ...,
 	),
	...
);
```

Inside the login() action we need a little script to re-hash outdated passwords then:
```php
if ($this->Auth->login()) {
	$uid = $this->Auth->user('id');
	$dbPassword = $this->User->field('password', ...);
	if ($this->User->needsRehash($dbPassword)) {
		$newHash = $this->User->hash($this->request->data['User']['password']);
		// Update this user
	}
	...
}
```

It uses methods of the User model, which we create for this use case:
```php
/**
 * @param string $pwd
 * @return bool Success
 */
public function needsRehash($pwd) {
	$options = array(
		'className' => 'Tools.Fallback',
		'hashers' => array(
			'Tools.Modern', 'Simple'
		)
	);
	$passwordHasher = $this->_getPasswordHasher($options); // Implement this on your own
	return $passwordHasher->needsRehash($pwd);
}

/**
 * @param string $pwd
 * @return string Hash
 */
public function hash($pwd) {
	$options = array(
		'className' => 'Tools.Fallback',
		'hashers' => array(
			'Tools.Modern', 'Simple'
		)
	);
	$passwordHasher = $this->_getPasswordHasher($options); // Implement this on your own
	return $passwordHasher->hash($pwd);
}
```

### Using Passwordable as a clean and DRY wrapper
When using Passwordable, the following Configure config
```
	'Passwordable'  => [
		'passwordHasher' => ['className' => 'Fallback', 'hashers' => ['Tools.Modern', 'Simple']]
	],
```
will take care of all for both login and user creation.
No extra model methods and duplicate configs necessary.
See [docs](http://www.dereuromark.de/2011/08/25/working-with-passwords-in-cakephp/).


## TinyAuth for Authorization
Super-fast super-slim Authorization once you are logged in.
See [TinyAuth](TinyAuth/TinyAuth.md).


See the [CakeFest app](https://github.com/dereuromark/cakefest) for a demo show case around all of the above.