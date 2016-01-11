# Passwordable Behavior

A CakePHP behavior to work with passwords the easy way
- Complete validation
- Hashing of password
- Requires fields (no tempering even without security component)
- Usable for edit forms (require => false for optional password update)

Also capable of:
- Require current password prior to altering it (current => true)
- Don't allow the same password it was before (allowSame => false)

## Configs
| Key  | Default | Description |
| ------------- | ------------- | ------------- |
| field             |   'password'          |   |
| confirm           |   true                |    Set to false if in admin view and no confirmation (pwd_repeat) is required     |
| require           |   true                |    If a password change is required (set to false for edit forms, leave it true for pure password update forms)|
| current           |   false               |    Enquire the current password for security purposes     |
| formField         |   'pwd'               |   |
| formFieldRepeat   |   'pwd_repeat'        |   |
| formFieldCurrent  |   'pwd_current'       |   |
| userModel         |   null                |   Defaults to Users      |
| auth              |   null                |   Which component (defaults to AuthComponent)    |
| authType          |   'Form'              |   Which type of authenticate (Form, Blowfish, ...)|
| passwordHasher    |   'Default'           |   If a custom pwd hasher is been used         |
| allowSame         |   true                |   Don't allow the old password on change      |
| minLength         |   PWD_MIN_LENGTH      |   |   
| maxLength         |   PWD_MAX_LENGTH      |   |   
| validator         |   'default'           |   |
| customValidation  |   null                |    Custom validation rule(s) for the formField on top     |

You can either pass those to the behavior at runtime, or globally via Configure and `app.php`:
```php
$config = [
	'Passwordable' => [
		'passwordHasher' => ['className' => 'Fallback', 'hashers' => ['Default', 'Weak']]
	]
]
```
In this case we use the Fallback hasher class and both Default (Blowfish, CakePHP3 default) and Weak (E.g. sha1) hashing algorithms.
The latter is necessary when you try to upgrade an existing CakePHP2 application which used some weak hashing algo to Cake3. This way
you can use both parallel. And new accounts will use the new hasher. Order matters!

## Usage
Do NOT hard-add it in the model itself.
Attach it dynamically in only those actions where you actually change the password like so:
```php
$this->Users->addBehavior('Tools.Passwordable', $config);
```
as first line in any action where you want to allow the user to change his password.
Also add the two form fields in the form (pwd, pwd_confirm)

The rest is CakePHP automagic :)

Also note that you can apply global settings via Configure key 'Passwordable', as well,
if you don't want to manually pass them along each time you use the behavior. This also
keeps the code clean and lean. See the `app.default.php` file for details.

And do NOT add any password hashing to your Table or Entity classes. That would hash the password twice.

## Examples

### Register (Add) form
```php
namespace App\Controller;

use Tools\Controller\Controller;

class UsersController extends Controller {

	public function register() {
		$user = $this->Users->newEntity();
		$this->Users->addBehavior('Tools.Passwordable');


		if ($this->request->is(['put', 'post'])) {
			$user = $this->Users->patchEntity($user, $this->request->data);
			$user->role_id = Configure::read('Roles.user');

			if ($this->Users->save($user)) {
				// Log in right away
				$this->Auth->setUser($user->toArray());
				// Flash message OK
				return $this->redirect(['action' => 'index']);
			}
			// Flash message ERROR

			// Pwd should not be passed to the view again for security reasons
			$user->unsetProperty('pwd');
			$user->unsetProperty('pwd_repeat');
		}

		$this->set(compact('user'));
	}

}
```

### Edit form
```php
namespace App\Controller;

use Tools\Controller\Controller;

class UsersController extends Controller {

	public function edit() {
		$uid = $this->request->session()->read('Auth.User.id');
		$user = $this->Users->get($uid);
		$this->Users->addBehavior('Tools.Passwordable', ['require' => false]);

		if ($this->request->is(['put', 'post'])) {
			$options = [
				'fieldList' => [ 'pwd', 'pwd_repeat', ... ]
			];
			$user = $this->Users->patchEntity($user, $this->request->data, $options);
			if ($this->Users->save($user)) {
				// Update session data, as well
				$this->Auth->setUser($user->toArray());
				// Flash message OK
				return $this->redirect(['action' => 'index']);
			}
			// Flash message ERROR
		}

		$this->set(compact('user'));
	}

}
```

### Login with Fallback hasher class and automatic rehashing
In the config example above you can see both Default and Weak hashers being used.
We want to upgrade all accounts piece by piece upon login automatically. This way it can be done
without the user noticing:
```php
public function login() {
	if ($this->request->is(['put', 'post'])) {
		$user = $this->Auth->identify();
		if ($user) {
			$this->Users->addBehavior('Tools.Passwordable', ['confirm' => false]);
			$password = $this->request->data['password'];
			$dbPassword = $this->Users->field('password', ['id' => $user['id']]);

			if ($this->Users->needsPasswordRehash($dbPassword)) {
				$data = [
					'id' => $user['id'],
					'pwd' => $password,
					'modified' => false
				];
				$updatedUser = $this->Users->newEntity($data, ['markNew' => false]);
				if (!$this->Users->save($updatedUser, ['validate' => false])) {
					trigger_error(sprintf('Could not store new pwd for user %s.', $user['id']));
				}
			}
			unset($user['password']);
			$this->Auth->setUser($user);
			// Flash message OK
			return $this->redirect($this->Auth->redirectUrl());
		}
		// Flash message ERROR

	}
}
```
Note that the `passwordHasher` config has been set here globabally to assert the Fallback hasher class to kick in.


### Adding custom validation rules on top
If the default rules don't satisfy your needs, you can add some more on top:
```php
$rules = ['validateCustom' => [
		'rule' => ['custom', '#^[a-z0-9]+$#'], // Just a test example, never use this regex!
		'message' => __('Foo Bar'),
		'last' => true,
	],
	'validateCustomExt' => [
		'rule' => ['custom', '#^[a-z]+$#'], // Just a test example, never use this regex!
		'message' => __('Foo Bar Ext'),
		'last' => true,
	]
);
$this->User->Behaviors->load('Tools.Passwordable', ['customValidation' => $rules]);
```
But please do NOT use the above regex examples. Also never try to limit the chars to only a subset of characters.
Always allow [a-z], [0-9] and ALL special chars a user can possibly type in.
Regex rules can be useful to assert that the password is strong enough, though. That means, that it contains not just letters/numbers, but
also some special chars. This would be way more secure and useful. But also try to be reasonable here, some developers tend to overreach here,
making it very annoying to set up passwords.
