# Html Helper

An enhanced HtmlHelper
- imageFromBlob()
- linkReset() and linkComplete()

## Usage
Attach it to your controllers like so:
```php
public $helpers = ['Tools.Html'];
```
It will replace the CakePHP core one everywhere.

### Image from Blob
Sometimes you might want to directly output images inside the HTML without external files involved.
The image, like a small 16x16 icon, could also directly come from the database.

```php
$blobImage = file_get_contents($path . 'my-image.png');
// or from somewhere else

echo $this->Html->imageFromBlob($blobImage);
```
The output will be a base64 encoded embedded image.

Bear in mind that this does not work with all (especially older) browsers.

### Reset and Complete Links
In some cases you want to display the links without the plugin and prefix automatically being taken from the current URL.
This is especially important with navigation menu or layout elements, as those will be outputted on every page, even outside of the current plugin or prefix scope.

You can then either manually and verbosely always set both to `false`, or just use the convenience method:
```php
echo $this->Html->linkReset(['controller' => 'Foo', 'action' => 'bar']);
```

Inside `/admin/plugin-name/example/action` the linked URL would normally become `/admin/plugin-name/foo/bar`.
With the linkReset() method it will become the desired: `/foo/bar`.

In both cases, however, the query strings are not passed on. If you want that, you can use the other convenience method:
```php
echo $this->Html->linkComplete(['controller' => 'Foo', 'action' => 'bar']);
```
Now if there was a query string `?q=x` on the current action, it would also be passed along as `/foo/bar?q=x`.


See also [Url helper](/docs/Url/Url.md).
