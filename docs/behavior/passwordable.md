# Passwordable behavior

`Passwordable` validates temporary password fields and hashes an accepted password into the stored field. Attach it before creating or patching an entity, only in actions that permit a password change. Do not also hash the same password in an entity setter.

## Setup

Use a table extending `Tools\Model\Table\Table`, or supply an equivalent `validateIdentical` validation method. Make `pwd` and `pwd_repeat` accessible for the operation. With current-password validation enabled, also allow `pwd_current`.

In a controller action, fetch the application table and add the behavior before patching:

```php
$users = $this->fetchTable('Users');
$users->addBehavior('Tools.Passwordable', ['require' => false]);
$user = $users->get($id);

if ($this->getRequest()->is(['post', 'put', 'patch'])) {
    $user = $users->patchEntity($user, $this->getRequest()->getData(), [
        'fields' => ['name', 'pwd', 'pwd_repeat'],
    ]);
    if ($users->save($user)) {
        return $this->redirect(['action' => 'view', $user->id]);
    }
    $user->unset('pwd');
    $user->unset('pwd_repeat');
}
$this->set(compact('user'));
```

Resolve `$id` through the application's access controls before editing an account. Authentication and session identity updates remain application responsibilities.

In the form template:

```php
echo $this->Form->control('pwd', ['type' => 'password', 'value' => '']);
echo $this->Form->control('pwd_repeat', ['type' => 'password', 'value' => '']);
```

`require => false` allows an edit without a new password. When a password is supplied, confirmation must match. For a dedicated password-change form, retain the default `require => true` and consider `current => true`.

## Configuration

| Option | Default | Purpose |
| --- | --- | --- |
| `field` | `password` | Stored hash field. |
| `formField` | `pwd` | Submitted plaintext field; must differ from `field`. |
| `formFieldRepeat` | `pwd_repeat` | Confirmation field. |
| `formFieldCurrent` | `pwd_current` | Current-password field. |
| `confirm` | `true` | Require matching confirmation. |
| `require` | `true` | Require a new password. |
| `current` | `false` | Validate the supplied current password. |
| `allowSame` | `true` | Permit the previous password. |
| `passwordHasher` | `Default` | Tools password hasher configuration. |
| `minLength`, `maxLength` | `PWD_MIN_LENGTH`, `PWD_MAX_LENGTH` | Length limits defined when the behavior class is loaded. |
| `validator` | `default` | Validator to extend. |
| `customValidation` | `null` | Additional rules for the password form field. |
| `forceFieldList` | `false` | Create a marshal field list from submitted data when none is provided. |

Global defaults can be configured under `Passwordable`; per-behavior options take precedence. The behavior adds its temporary password fields to an explicit marshal `fields` list, but the entity's accessibility rules still apply.

The built-in `Default` hasher uses PHP's `password_hash()` and `password_verify()`. Its `hashType` and `hashOptions` can be configured. Custom hashers must extend `Tools\Auth\AbstractPasswordHasher`; class names resolve within Tools first, then through app or plugin resolution. Former `Fallback` and `Weak` examples do not describe the hashers shipped by this plugin.

`needsPasswordRehash()` checks whether an existing hash needs updating under the configured hasher. An automatic rehash after login must be integrated with the application's current Authentication service and authorized persistence flow.
