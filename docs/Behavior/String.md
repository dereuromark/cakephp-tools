# String Behavior

A CakePHP behavior to apply basic string operations for your input.

Note that most string modification should be done once, on save.
Prevent using output modification if possible as it is done on every fetch.

### Usage

#### Input formatting
Include behavior in your Table class as
```php
$this->addBehavior('Tools.String', [
    'fields' => ['title'], 
    'input' => ['ucfirst'],
]);
```
This will `ucfirst()` the title prior to saving.

Tip: If you have other behaviors that might modify the array data prior to saving, better use a lower (higher value) priority:
```php
$this->addBehavior('Tools.String', [
    ...
    'priority' => 11,
]);
```

The input filters are an array and therefore can also be stacked. They will be executed in the order given. 
If string that function is expected to exist. You can also use callables and anonymous functions, of course. 

#### Output formatting
Instead of the preferred input formatting you can also modify the output (for each find):
```php
$this->addBehavior('Tools.String', [
    ...
    'output' => ['ucfirst'],
]);
```


### Examples

Imagine the following config:
```php
    'fields' => ['title', 'comment'], 
    'input' => ['strtolower', 'ucwords'],
```

And the input:
```php
$data = [
    'title' => 'some nAme',
    'comment' => 'myBlog',
    'url' => 'www.dereuromark.de',
];
$comment = $this->Comments->newEntity($data);
$result = $this->Comments->save($comment);
```

The title would be saved as `Some Name` and the comment as `MyBlog`.
