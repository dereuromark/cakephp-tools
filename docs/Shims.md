## Shims and CO
Write cutting edge 2.x code - and prepare for 3.x.

Note that most of the functionality will be moved to a separate [Shim plugin](https://github.com/dereuromark/cakephp-shim).

### Write smart (future aware) code
- Drop deprecated stuff early.
- Upgrade to new ways as soon as possible and while it's still easy to do so (minor changes well testable).
- Use the current 2.x version and all the new syntax of it (You can leverage the Upgrade shell to automate necessary changes).

See [Tips-Upgrading-to-CakePHP-2.x](https://github.com/dereuromark/cakephp-upgrade/wiki/Tips-Upgrading-to-CakePHP-2.x) and
the plugin in general.

### Assert query strings througout the project
Don't use named params anywhere, they will be gone in 3.x anyway.

Default settings for Paginator, ... can be set using Configure:
```php
$config['Paginator'] = array(
	'paramType' => 'querystring'
);
```
Just make sure, that your AppController extends the `Tools.MyController` class.

Tip: Using the Shim plugin this is already done for you out of the box.

You can also set `Configure::write('Shim.warnAboutNamedParams', true)` to get a warning if
any of your code still uses the deprecated named params.


### FlashMessages
In 3.x there will be a FlashComponent instead. Mine also provides stackable (multi) messages.
See https://github.com/dereuromark/cakephp-tools/wiki/Flash-messages for details.

### Login/Passwords
Already use the PHP5.5+ password functionality with the ModernPasswordHasher class and the Passwordable behavior.
Easily upgradable to 3.x in minutes.

### RSS
Use RssView instead of the akward and limited helper approach.


### More

#### Templates
Backported StringTemplate class (from CakePHP3.0) can be used to use template based rendering of HTML tags.

#### Helpers
FormExt and HtmlExt helpers also provide extended functionality and 3.x shims.

#### Ajax
Use the AjaxView which will easily be upgradable as a clean component approach.


Also see the [Shim plugin](https://github.com/dereuromark/cakephp-shim) for details and documentation on more possible shims you can leverage.
