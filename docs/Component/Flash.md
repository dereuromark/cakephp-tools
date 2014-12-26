# Flash Component

An enhanced FlashComponent capable of
- Stackable messages for each type
- Persistent (across requests) and transient messages
- Inject it into the headers as `X-Ajax-Flashmessage` for REST/AJAX requests

## Configs
- 'headerKey' => 'X-Flash', // Set to empty string to deactivate
- 'sessionLimit' => 99 // Max message limit for session to avoid session flooding (Configure doesn't need one)

## Usage
Attach it to your controllers in `initialize()` like so:
```php
$this->loadComponent('Tools.Flash');
```

Also add the helper for it:
```php
public $helpers = array('Tools.Flash');
```

In your layouts, you don't need to change the `$this->Flash->render()` call, as the syntax for this helper is the same.

### Basic Example
```php
// Inside an action
$this->Flash->message('Yeah it works.', 'success');
$this->Flash->message('Careful.', 'warning');
$this->Flash->message('O o.', 'error');
```

### You can also use the new syntactic sugar:
```php
// Inside an action
$this->Flash->success('Yeah it works.');
$this->Flash->warning('Careful.');
$this->Flash->error('O o.');
```

## Notes
You can use any type (success, warning, error, info, ...) of message, except the two reserved ones `message` and `set`.
At least if you plan on using the magic method invokation. But even if not, it would be good practice to not use those two.