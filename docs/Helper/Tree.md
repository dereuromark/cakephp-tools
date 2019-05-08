# Tree Helper

A CakePHP helper to handle tree structures.

By default, it uses the core TreeBehavior and MPTT (Modified Preorder Tree Traversal).
But it sure can work with any tree like input as nested object or array structure.

It can work with both arrays and Entity objects. The latter should be preferred as you can
then use all properties and getters on that object.

## Usage

### Basic usage
Include helper in your AppView class as
```php
$this->addHelper('Tools.Tree', [
    ...
]);
```

Then you can use it in your templates as
```php
echo $this->Tree->generate($articles);
```

### Templated usage
By default, just outputting the display name is usually not enough.
You want to create some `Template/Element/tree_element.ctp` element instead:

```php
echo $this->Tree->generate($articles, ['element' => 'tree_element']);
```

That template can then contain all normal template additions, including full helper access:

```php
<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Article|\Cake\Collection\CollectionInterface $data
 * @var bool $activePathElement
 */

if (!$data->visible) { // You can do anything here depending on the record content
    return;
}
$label = $data->title;
if ($activePathElement) {
    $label .= ' (active)';
}
?>
<li>
<?php echo $this->Html->link($label, ['action' => 'view', $data->id]); ?>
</li>
```

So the current entity object is available as `$data` variable inside this snippet.

#### Available element data

- $data : object|object[]|array
- $parent : object|array|null
- $depth : int
- $hasChildren : int
- $numberOfDirectChildren : int
- $numberOfTotalChildren : int
- $firstChild : bool
- $lastChild : bool
- $hasVisibleChildren : bool
- $activePathElement : string
- $isSibling : bool

plus all config values. 

### Callback usage

Here the same keys are available on the first argument (`$data` array). So the above `$data` would actually be
`$data['data']` and usually be the entity. 
If you are passing entities, it helps to inline annotate in this case:
```php
    $closure = function(array $data) {
        /** @var \Cake\ORM\Entity $entity */
        $entity = $data['data'];

        return h($entity->name) . ($data['activePathElement'] ? ' (active)' : '');
    };
```

### Active path
When using the TreeHelper for navigation structures, you would usually want to set the active path as class elements ("active") 
on the `<li>` elements.
You can do that by passing in the current path.
```php
// Your controller fetches the navigation tree
$tree = $this->Table->find('threaded')->toArray();

// The current active element in the tree (/view/6)
$id = 6;
        
// We need to get the current path for this element
$nodes = $this->Table->find('path', ['for' => $id]);
$path = $nodes->extract('id')->toArray();

// In your view
$options = [
    'autoPath' => [$current->lft, $current->rght], 
    'treePath' => $path, 
    'element' => 'tree', // src/Template/Element/tree.ctp
];
echo $this->Tree->generate($tree, $options);
```
The `autoPath` setting passed using `[lft, rght]` of your current element will auto-add "active" into your elements.
You can also just pass the current entity (`'autoPath' => $current`) and it will extract lft and rght properties based on the config.

The `treePath` is optional and needed for additional things like hiding unrelated siblings etc.

## Outview

You can read some more tutorial like details in [my blog post](https://www.dereuromark.de/2013/02/17/cakephp-and-tree-structures/).
