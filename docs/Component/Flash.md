# Flash Component

An enhanced FlashComponent capable of
- Stackable messages for each type
- Persistent (across requests) and transient messages
- Inject it into the headers as `X-Ajax-Flashmessage` for REST/AJAX requests

## Configs


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

## Basic Example
```php
// Inside an action
$this->Flash->message('Yeah it works.', 'success');
$this->Flash->message('Careful.', 'warning');
$this->Flash->message('O o.', 'error');
```

## You can also use the new syntactic sugar:
```php
// Inside an action
$this->Flash->success('Yeah it works.');
$this->Flash->warning('Careful.');
$this->Flash->error('O o.');
```
