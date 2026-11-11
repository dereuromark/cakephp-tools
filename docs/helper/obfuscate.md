# Obfuscate Helper

Keeps email addresses and similar data out of the plain HTML, mainly against harvesting bots.

## Setup

Include the helper in your AppView class:

```php
$this->loadHelper('Tools.Obfuscate');
```

## Email links

### emailLink()

Renders a mailto link whose markup never contains the address. It needs no inline script, so it works under a strict Content Security Policy without a nonce for the link itself, and it also works for content inserted after page load (AJAX, htmx, Turbo).

```php
echo $this->Obfuscate->emailLink('info@example.com');
echo $this->Obfuscate->emailLink('info@example.com', 'Contact us', ['subject' => 'Hello'], ['class' => 'btn']);
```

Arguments:

- `$mail`: the address.
- `$text`: the link text. Escaped unless `escape` (or `escapeTitle`) is set to `false` in `$attr`, as with `HtmlHelper::link()`.
- `$params`: mailto query params such as `subject`, `body`, `cc`. Keys and values are URL encoded.
- `$attr`: link attributes. Defaults to `class="email"` and a `title`.

The first call renders:

```html
<a href="#" title="for use in an external mail client" class="email"
   data-tools-obfuscate="=02bj5SZsBXbhhXZA9mZulmOvRHbpFWb" data-tools-obfuscate-text="1">info [at] example [dot] com</a>
```

`data-tools-obfuscate` holds the full mailto URL, base64 encoded and then reversed. The `Tools.obfuscate` script decodes it, sets `href` and, if no `$text` was given, replaces the placeholder text with the address.

Without JavaScript the link stays at `#` and shows the given text or the placeholder.

### The script

`emailLink()` appends `webroot/js/obfuscate.js` to the `script` view block, once per request no matter how many links are rendered. Your layout needs to output that block:

```php
<?= $this->fetch('script') ?>
```

`HtmlHelper::script()` adds the `cspScriptNonce` request attribute as nonce if present. Under a policy without nonces the file is allowed by `script-src 'self'`.

The script decodes all links on `DOMContentLoaded`. Links inserted later are decoded on their first mouseover, focus, touch or click. To decode a fragment right away, call:

```js
window.ToolsObfuscate.init(container);
```

### Configuration

| Key | Default | Description |
| --- | --- | --- |
| `script` | `'Tools.obfuscate'` | Asset appended by `emailLink()`. Set to `false` to include the script yourself, for example in a bundle. |
| `scriptBlock` | `'script'` | View block the script is appended to. |
| `placeholder` | `['@' => ' [at] ', '.' => ' [dot] ']` | Replacements that turn the address into the link text used without JavaScript. |

```php
$this->loadHelper('Tools.Obfuscate', ['script' => false]);
```

### encodeEmailUrl()

Deprecated, use `emailLink()`. It writes the link with `document.write()` from two inline scripts, which need a CSP nonce and do nothing in content inserted after page load.

## Other methods

- `encodeEmail($mail)`: the address as randomly entity-encoded text with the `@` in a `<span>`.
- `encodeText($text)`: random decimal/hex entity encoding of any text.
- `hideEmail($mail)`: `test@test.de` becomes `t..t@t..t.de`.
- `wordCensor($str, $censored, $replacement = null)`: replaces the given words with `#` or the replacement.
