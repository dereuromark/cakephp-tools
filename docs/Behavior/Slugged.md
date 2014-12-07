# Slugged Behavior

A CakePHP behavior to automatically create and store slugs.
- Input data can consist of one or many fields
- Slugs can be unique and persistent, ideal for lookups by slug
- Multibyte aware, umlauts etc will be properly replaced

## Configs
- label:
	set to the name of a field to use for the slug, an array of fields to use as slugs or leave as null to rely
	on the format returned by find('list') to determine the string to use for slugs
- field: The slug field name
- overwriteField: The boolean field to trigger overwriting if "overwrite" is false
- mode: has the following values
	ascii - retuns an ascii slug generated using the core Inflector::slug() function
	display - a dummy mode which returns a slug legal for display - removes illegal (not unprintable) characters
	url - returns a slug appropriate to put in a URL
	class - a dummy mode which returns a slug appropriate to put in a html class (there are no restrictions)
	id - retuns a slug appropriate to use in a html id
- separator: The separator to use
- length:
 Set to 0 for no length. Will be auto-detected if possible via schema.
- overwrite: has 2 values
	false - once the slug has been saved, do not change it (use if you are doing lookups based on slugs)
	true - if the label field values change, regenerate the slug (use if you are the slug is just window-dressing)
- unique: has 2 values
	false - will not enforce a unique slug, whatever the label is is direclty slugged without checking for duplicates
	true - use if you are doing lookups based on slugs (see overwrite)
- case: has the following values
	null - don't change the case of the slug
	low - force lower case. E.g. "this-is-the-slug"
	up - force upper case E.g. "THIS-IS-THE-SLUG"
	title - force title case. E.g. "This-Is-The-Slug"
	camel - force CamelCase. E.g. "ThisIsTheSlug"
- replace: custom replacements as array
- on: beforeSave or beforeValidate
- scope: certain conditions to use as scope
- tidy: If cleanup should be run on slugging

## Usage
Attach it to your models in `initialize()` like so:
```php
$this->addBehavior('Tools.Slugged', $config);
```

## Examples

### Persistent slugs
We want to store categories and we need a slug for nice SEO URLs like `/category/[slugname]/`.

```php
$this->addBehavior('Tools.Jsonable',
	array('label' => 'name', 'unique' => true, 'mode' => 'ascii'));
```

Upon creating and storing a new record it will look for content in "name" and create a slug in "slug" field.

With the above config on "edit" the slug will not be modified if you alter the name. That is important to know.
You cannot just change the slug, as the URL is most likely indexed by search engines now.

If you want to do that, you would also need a .htaccess rewrite rule to 301 redirect from the old to the new slug.
So if that is the case, you could add an "overwrite field" to your form.
```html
echo $this->Form->field('overwrite_slug', ['type' => 'checkbox']);
```
Once that boolean checkbox is clicked it will then perform the slug update on save.

### Non persistent slugs
If we just append the slug to the URL, such as `/category/123-[slugname]`, then we don't need to persist the slug.
```php
$this->addBehavior('Tools.Jsonable',
	array('label' => 'name', 'overwrite' => true, 'mode' => 'ascii'));
```
Note that we don't need "unique" either then.

Each save now re-triggers the slug generation.