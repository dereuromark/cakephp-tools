# Rss View

A CakePHP view class to quickly output RSS feeds
- By default template-less
- Very customizable regarding namespaces and prefixes
- Supports CDATA (unescaped content).

## Configs
With `setNamespace()` you can add namespaces and their definition URLs.
They will only be added to the output if actually needed, when a namespace's prefix is being used.

By default, a `rss` subdir is being used. If you don't want that, you can set the `public $subDir` to null or any other value you like.

## Usage
Enable RSS extensions with `Router::extensions(['rss'])` (in your routes or bootstrap file).
You can then access it via:
```
/controller/action.rss
```

We then need an action to output our feed.
You can enable the RSS view class it in your actions like so:
```php
$this->viewClass = 'Tools.Rss';
```

The nicer way would be to use extensions-routing and let CakePHP auto-switch to this view class.
See the documentation on how to use view class mapping to automatically respond with the RssView for each request to the rss extension:

    'rss' => 'Tools.Rss'

With the help of parseExtensions() and RequestHandler this will save you the extra view config line in your actions.

By setting the '_serialize' key in your controller, you can specify a view variable
that should be serialized to XML and used as the response for the request.
This allows you to omit views + layouts, if your just need to emit a single view
variable as the XML response.

In your controller, you could do the following:
```php
$this->set(array('posts' => $posts, '_serialize' => 'posts'));
```
When the view is rendered, the `$posts` view variable will be serialized
into the RSS XML.

**Note** The view variable you specify must be compatible with Xml::fromArray().

If you don't use the `_serialize` key, you will need a view. You can use extended
views to provide layout like functionality. This is currently not yet tested/supported.

## Examples

### Basic feed
A basic feed contains at least a title, description and a link for both channel and items.
It is also advised to add the `atom:link` to the location of the feed itself.

```php
$this->viewClass = 'Tools.Rss'; // Important if you do not have an auto-switch for the rss extension
$atomLink = array('controller' => 'Topics', 'action' => 'feed', '_ext' => 'rss'); // Example controller and action
$data = array(
    'channel' => array(
        'title' => 'Channel title',
        'link' => 'http://channel.example.org',
        'description' => 'Channel description',
        'atom:link' => array('@href' => $atomLink),
    ),
    'items' => array(
        array('title' => 'Title One', 'link' => 'http://example.org/one',
            'author' => 'one@example.org', 'description' => 'Content one'),
        array('title' => 'Title Two', 'link' => 'http://example.org/two',
            'author' => 'two@example.org', 'description' => 'Content two'),
    ));
);
$this->set(array('data' => $data, '_serialize' => 'data'));
```

### Built in namespaces
It is also possible to use one of the already built in namespaces – e.g. if you want to display
a post’s username instead of email (which you should^^). You can also add the content itself
as CDATA. The description needs to be plain text, so if you have HTML markup, make sure to
strip that out for the description but pass it unescaped to the content namespace tag for it.
```php
$data = array(
    'channel' => array(
        'title' => 'Channel title',
        'link' => 'http://channel.example.org',
        'description' => 'Channel description'
    ),
    'items' => array(
        array('title' => 'Title One', 'link' => 'http://example.org/one',
            'dc:creator' => 'Mr Bean', 'description' => 'Content one',
            'content:encoded' => 'Some <b>HTML</b> content'),
        array('title' => 'Title Two', 'link' => 'http://example.org/two',
            'dc:creator' => 'Luke Skywalker', 'description' => 'Content two',
            'content:encoded' => 'Some <b>more HTML</b> content'),
    )
);
$this->set(array('data' => $data, '_serialize' => 'data'));
```

### Custom namespaces
You can easily register new namespaces, e.g. to support the google data feeds (`xmlns:g="http://base.google.com/ns/1.0"`):

```php
$data = array(
    'document' => array(
        'namespace' => array(
            'g' => 'http://base.google.com/ns/1.0'
        )
    )
    'channel' => array(
        ...
    ),
    'items' => array(
        array('g:price' => 25, ...),
    )
);
$this->set(array('data' => $data, '_serialize' => 'data'));
```

### Passing params.
If you need to pass params to this view, use query strings:
```
.../action.rss?key1=value1&key2=value2
```

### Vista
There are still lots of things that could be implemented. It still does not handle all the use cases possible, for example.

It also stands to discussion if one could further generalize the class to not only support RSS feeds, but other type of feeds, as well.
