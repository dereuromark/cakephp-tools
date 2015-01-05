# TinyAuth
The fast and easy way for user authorization in CakePHP 2.x applications.

See [my blog article](http://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2/) for additional
infos and a live demo.


## Basic Features
- Singe or multi role
- DB (dynamic) or Configure based role definition
- INI file (static) based access rights (controller-action/role setup)
- Lightweight and incredibly fast

### Use if
- You want to have a fixed (static) number of roles, and assign users to roles to define what actions etc. they can reach.
- You want to have all those role/action pairs out of the controller and in a single place (file). Keeps the controller lean.

### Do NOT use if
- You need *row-level* based access (allow/deny actions to specific users).
- You want to dynamically adjust access rights via backend (or enhance it with a web GUI yourself).

### Planned features (not yet available):
- AuthShell for easy CLI access / setup
- Web frontend to optionally allow dynamic access rights


## Docs

### Preparations
Please make sure the Tools Plugin is properly loaded (see the plugin readme for details).
If you plan on using prefixed routing (admin, ...), enable those in your core.php or bootstrap.php.

I assume you already got the AuthComponent included in the $components array of your AppController.
You probably also excluded all public views with sth like
```php
$this->Auth->allow('contact_form'); // in beforeFilter() of the specific controllers
```
This snippet (in the contact controller) makes Auth skip this action completely. The action will be accessible to everybody right away.

This is especially important for your login/register actions:
```php
// UsersController
public function beforeFilter() {
	parent::beforeFilter();
	$this->Auth->allow('login', 'logout', 'register', ...);
}
```
Those actions should never trigger any Authorize module. All other actions then use our ACL to determine if access is granted or not.

You probably got a Role model (User belongsTo Role / User hasAndBelongsToMany Role) attached to the User.
This table needs at least a "name" and an "alias" slug field to function. See the test fixture for details.

If you don't want this, use Configure to store your keys like so:
```php
// in your config.php if applicable or using Configure::write('Role', array(...))
$config['Role'] = array(
	// slug => identifier (unique magical number or maybe better a constant)
	'superadmin' => 1,
	'admin' => 2,
	'moderator' => 3,
	'helper' => 4,
	'user' => 5,
);
```
The Configure approach overwrites any Model/table one.

You should at least have a `user` - and maybe an `admin` role - for it to make sense.
The advantage here: At any time you can switch from Configure to a Role model + roles table and vice versa without having to change much.

You must also have some kind of Authentication in your AppController:
```php
$this->Auth->authenticate = array('Form'); // Uses username and password for login
```
**Important:** At least one type of authentication is necessary for any `Authorize` module to be usable.

So far so good. You can login/logout and once you are logged in browse all non-public pages.
Even admin pages, of course. Thats where the TinyAuth class comes in.


### TinyAuth
First of all include it in your beforeFilter() method of the AppController:
```php
$this->Auth->authorize = array('Tools.Tiny');
```
Alternatively, you could pass it as component settings right away:
```php
public $components = [
        ...
        'Auth' => [
            'loginRedirect' => ...,
            'logoutRedirect' => ...,
            'authenticate' => ['Form'],
            'authorize' => ['Tools.Tiny']
        ],
];
```

Now create a file in /Config/ called acl.ini like so:
```ini
[Tools.Countries]
* = superadmin ; this is a comment

[Account]
edit,change_pw = *

[Activities]
admin_index,admin_edit,admin_add,admin_delete = admin,superadmin
index = *

[Users]
index,search = user
* = moderator,admin
```

The format is normal PHP INI style. I already included all kind of examples. * is a placeholder for "any".
The plugin prefix for controllers is not necessary as of now (maybe for Cake3 where the same controller name is allowed multiple times due to PHP5.3 namespaces).
Comments in INI files start with ";".

Explanations:

- Superadmin can access all Countries actions of the Tools plugin
- Account actions are accessible by all roles (and therefore logged in persons)
- Activities can be modified by all admins and listed by all (logged in persons)
- Users can search and list other users, but only moderators and admins have access to all other actions

That's it. Really easy, isn't it?


### Some details
TinyAuth expects a Session Auth User like so:
```php
Auth.User.id
Auth.User.role_id (belongsTo - role key directly in the users table)
```
or so:
```php
Auth.User.id
Auth.User.Role (hasAndBelongsToMany - multi role array containing all role keys)
```
As you can see <strong>it can manage both single and multiple role setup</strong>.
That's sth the core one lacks, as well.

The current configuration is cached in the persistent folder by default. In development mode (debug > 0) it will be regenerated all the time, though. So remember that you have to manually clear your cache in productive mode for changes to take effect!

For more insight into the different role setups see [this Wiki page](https://github.com/dereuromark/tools/wiki/Tiny-Auth-Role-setup).


### Quicktips
If you have a cleanly separated user/admin interface there is a way to allow all user actions to users right away;
```php
$this->Auth->authorize = array('Tools.Tiny' => array('allowUser' => true));
```
Only for admin views the authorization is required then.

If you got a "superadmin" role and want it to access everything automatically, do this in the beforeFilter method of your AppController:
```php
$userRoles = $this->Session->read('Auth.User.Role');
if ($userRoles && in_array(Configure::read('Role.superadmin'), $userRoles)) {
	// Skip auth for this user entirely
	$this->Auth->allow('*'); // cake2.x: `$this->Auth->allow();` without any argument!
}
```


### What about login/register when already logged in

That is something most are neither aware of, nor does the core offer a out-of-the-box solution.
Fact is, once you are logged in it is total nonsense to have access again to login/register/lost_pwd actions.
Here comes my little trick:
```php
// In your beforeFilter() method of the AppController for example (after Auth adapter config!)
$allowed = array('Users' => array('login', 'lost_password', 'register'));
if (!$this->Session->check('Auth.User.id')) {
	return;
}
foreach ($allowed as $controller => $actions) {
	if ($this->name === $controller && in_array($this->request->action, $actions)) {
		// Flash message - you can use your own method here - or CakePHP's setFlash() - as well
		$this->Common->flashMessage('The page you tried to access is not relevant if you are already logged in. Redirected to main page.', 'info');
		return $this->redirect($this->Auth->loginRedirect);
	}
}
```


### Changes

#### UPDATE 2012-01-10
The auth model can now be anything you like. It doesn't have to be `Role` or `role_id`.
The new cake2.x uses "groups" per default.
You can easily adjust that now by passing <code>`aclModel` => 'Group'</code> or <code>`aclKey` => 'group_id'</code> to the Tiny class, for instance.

#### UPDATE 2013-03-10
Some will be happy to hear that the deeper "contained" Role array is now supported besides the flat array of role keys. This deep/verbose array of roles has been introduced in Cake2.2 with the new ["contain" param for Auth](https://github.com/cakephp/cakephp/pull/594). So it made sense to support this in TinyAuth. See the test case for details.

#### UPDATE 2013-06-25
A new option `allowAdmin` makes it now possible to use TinyAuth even with less configuration in some cases. `True` makes the admin role access any admin prefixed action and together with `allowUser` (allows all logged in users to allow non admin prefixed URLs) this can be used to set up a basic admin auth. No additional configuration required except for the `adminRole` config value which needs to be set to the corresponding integer value.
Of, course, you can additionally allow further actions. But if you just need to quickly enable an admin backend, this could be the way to go.


### Notes

#### NOTE 2012-02-25
It seems that especially new-beys seem to mix up the meaning of `*` in the ACL. Although it is already laid out in the above text I will try to make it more clear:
This `any` placeholder for "roles" only refers to those users that are logged in. You must not declare your public actions this way!
All those must be declared in your controller using `$this->Auth->allow()` (in earlier versions of cake `$this->Auth->allow('*')).

The reason is that Authenticate comes before Authorize. So without Authentication (logged in) there will never be any Authorization (check on roles).

#### NOTE 2013-02-12
You can use this in conjunction with my [Auth class](https://github.com/dereuromark/tools/blob/master/Lib/Auth.php) for a quick way to check on the current user and its role(s) anywhere in your application:
```php
App::uses('Auth', 'Tools.Lib'); // In your bootstrap (after plugin is loaded)

if (Auth::id()) {
	$username = Auth::user('username');
	// do sth
}

if (Auth::hasRole(Configure::read('moderator'))) { // if you used configure slugs
	// do sth
}

if (Auth::hasRoles(array(ROLE_ADMIN, ROLE_MODERATOR)) { // if you used configure and constants instead of magic numbers
	// do sth
}
```
See the inline class documentation or the test cases for details.

#### Upcoming
A shell to quickly modify the INI file (and batch-update for new controllers etc) should be ready some time soon.

There might some day also the possibility to use some CRUD backend to manage the ACL (either via database or modifying the INI file).
If someone wants to help, go for it.