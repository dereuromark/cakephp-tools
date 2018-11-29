# Tree Helper

A CakePHP helper to handle tree structures.

By default, it uses the core TreeBehavior and MPTT (Modified Preorder Tree Traversal).
But it sure can work with any tree like input as nested object or array structure.

### Usage

#### Basic usage
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

#### Templated usage
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
 */

if (!$data->visible) { // You can do anything here depending on the record content
	return;
}
?>
<li>
<?php echo $this->Html->link($data->title, ['action' => 'view', $data->id]); ?>
</li>
```

So the current entity object is available as `$data` variable inside this snippet.

### Available element/callback data

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

### Outview

You can read some more tutorial like details in [my blog post](http://www.dereuromark.de/2013/02/17/cakephp-and-tree-structures/).
