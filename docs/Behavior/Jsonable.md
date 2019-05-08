# Jsonable Behavior

A CakePHP behavior to automatically store nested data as JSON string and return the array on read again.
- Data can be of type array, params or list - or kept in JSON format
- Additional sanitize functionality with "clean", "sort" and "unique

## Important note
Using 3.5+ you might not even need this anymore, as you can use type classes directly:
```php
    /**
     * @param \Cake\Database\Schema\TableSchema $schema
     *
     * @return \Cake\Database\Schema\TableSchema
     */
    protected function _initializeSchema(TableSchema $schema)
    {
        $schema->columnType('my_field', 'json');

        return $schema;
    }
```    
This is best combined with the Shim.Json type, as it properly handles `null` values:
```php
// in bootstrap
Type::map('json', 'Shim\Database\Type\JsonType');
```

But if you still need/want more flexible approaches, continue reading.


## Usage
Attach it to your model's `Table` class in its `initialize()` like so:
```php
$this->addBehavior('Tools.Jsonable', $options);
```

Tip: If you have other behaviors that might modify the array data prior to saving, better use a higher priority:
```php
$this->addBehavior('Tools.Jsonable', ['priority' => 11, ...]);
```
So that it is run last.

## Options
| Key  | Default | Description |
| ------------- | ------------- | ------------- |
| fields  | array() | Array of the fields to be converted  |
| input  | 'array'  | can be \[json, array, param or list\] (param/list only works with specific fields) |
| output  | 'array'  | can be \[json, array, param or list\] (param/list only works with specific fields) |
| separator  | '\|'  | only for param or list |
| keyValueSeparator  | ':'  | only for param |
| leftBound  | '{'  | only for list |
| rightBound  | '}'  | only for list |
| clean  | true | only for list (auto clean values on insert) |
| sort  | false | only for list |
| unique  | true | only for list |
| map  | array()  | map on a different DB field |
| encodeParams  |   | params for json_encode |
| decodeParams  |   | params for json_decode |


## Examples

The behavior supports different input/output formats:
- "array" is the default input and output format
- "list" is useful as some kind of pseudo enums or simple lists
- "params" is useful for multiple key/value pairs
- can be used to create dynamic forms (and tables)

Also automatically cleans lists and works with custom separators/markup etc if you want it to.

### Array
In my first scenario where I used it, I had a geocoder behavior attached to the model which returned an array.
I wanted to save all the returned values, though, for debugging purposes in a field "debug".
By using the following snippet I was able to do exactly that with a single line of config.
The rest is CakePHP automagic :)

```php
$this->addBehavior('Tools.Jsonable',
    array('fields' => ['debug'], 'map' => ['geocoder_result']);
```
I could access the array in the view as any other array since the behavior re-translates it back into an array on find().

Note: The mapping option is useful if you want to rename certain fields.
In my case the geocoder puts its data into the field "geocoder_result".
I might need to access this array later on in the model. So I "jsonable" it in the "debug" field for DB input
and leave the source field untouched for any later usage.
The same goes for the output: It will map the JSON content of "debug" back to the field "geocoder_result" as array, so
I have both types available then.

### Params
What if needed something more frontend suitable.
I want to be able to use a textarea field where I can put all kinds of params
which will then also be available as array afterwards (as long as you are not in edit mode, of course).

We can switch to param style here globally for the entity:

```php
$this->addBehavior('Tools.Jsonable',
    ['fields' => 'details', 'input' => 'param', 'output' => 'array']);
```

Only for the add/edit action we need to also make "output" "param" at runtime:
```php
$this->Table->behaviors()->Jsonable->options(
    ['fields' => 'details', 'input' => 'param', 'output' => 'param']);
```

The form contains a "details" textarea field. We can insert:
```php
param1:value1|param2:value2
```

In our views we get our data now as array:
```php
debug($entity->get('details'));
// Prints:
// ['param1' => 'value1', 'param2' => 'value2']
```


### Enums
we can also simulate an ENUM by using
```php
$this->addBehavior('Tools.Jsonable',
    ['fields' => 'tags', 'sort' => true, 'unique' => true, 'input' => 'list', 'output' => 'array']);
```
Dont' forget to use `'output' => 'list'` for add/edit actions.

In our textarea we can now type:
```
dog, cat, cat, fish
```

In our views we would result in:
```php
debug($entity->get('tags'));
// Prints:
// ['cat', 'dog', 'fish']
```

Note: The cleanup automation you can additionally turn on/off. There are more things to explore. Dig into the source code for that.

Yes - you could make a new table/relation for this in the first place.
But sometimes it's just quicker to create such an enumeration field.

Bear in mind: It then cannot be sorted/searched by those values, though.
For a more static solution take a look at my [Static Enums](http://www.dereuromark.de/2010/06/24/static-enums-or-semihardcoded-attributes/).
