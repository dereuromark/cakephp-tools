# Auth functionality

The Tools plugin contains some convenience wrappers to work with Auth user data.
They have a trait in common that keeps the functionality DRY.

## AuthUser Component
Attach it to your controllers in `initialize()` like so:
```php
$this->loadComponent('Tools.AuthUser');
```

## AuthUser Helper
Load your helper in your View class or use the controller property:
```php
$this->loadHelper('Tools.AuthUser');

// or
public $helpers = ['Tools.AuthUser'];
```
Don't forget that the component has to be included for the helper to function.


## Usage
Both component and helper function the same:

```php
// Read the id of the logged in user as shortcut method (Auth.User.id)
$uid = $this->AuthUser->id();

// Get the username (Auth.User.username)
$username = $this->AuthUser->user('username');

// Check for a specific role
$roles = $this->AuthUser->roles();

// Check for a specific role
$hasRole = $this->AuthUser->hasRole(ROLE_XYZ);
```

## Notes
The above example uses default settings and ROLE_{...} constants.
Use your own settings if necessary to overwrite the default behavior.
