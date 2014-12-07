# Rss View

A CakePHP view class to quickly output RSS feeds
- By default template-less
- Very customizable regarding namespaces and prefixes
- Supports CDATA (unescaped content).

## Configs
- 'limit' => 100 // batch of records per loop
- 'timeout' => null // in seconds
- 'fields' => array() // if not displayField
- 'updateFields' => array() // if saved fields should be different from fields
- 'validate' => true // trigger beforeValidate callback
- 'updateTimestamp' => false // update modified/updated timestamp
- 'scope' => array() // optional conditions
- 'callback' => null

## Usage
You can config it in your actions like so:
```php
$this->viewClass = 'Tools.Rss';
```

The nicer way would be to use extenions-routing and let CakePHP auto-switch to this view class.
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
