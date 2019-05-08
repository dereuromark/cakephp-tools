# Slugged Behavior

A CakePHP behavior to automatically create and store slugs.
- Input data can consist of one or many fields
- Slugs can be unique and persistent, ideal for lookups by slug
- Multibyte aware, umlauts etc will be properly replaced

## Configs
<table>
    <tbody>
        <tr>
            <th>Key</th>
            <th>Default</th>
            <th>Description</th>
        </tr>
        <tr>
            <td>label</td>
            <td>`null`</td>
            <td>
                <ul>
                    <li> set to the name of a field to use for the slug </li>
                    <li> an array of fields to use as slugs </li>
                    <li> or leave as null to rely on the format returned by find('list') to determine the string to use for slugs </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>    field   </td>
            <td>  `'slug'`  </td>
            <td>    The slug field name     </td>
        </tr>
        <tr>
            <td>    overwriteField  </td>
            <td>  'overwrite_slug'      </td>
            <td>    The boolean field/property to trigger overwriting if "overwrite" is false     </td>
        </tr>
        <tr>
            <td>    mode    </td>
            <td> `'url'`   </td>
            <td>
                <ul>
                    <li> <b>ascii: </b> retuns an ascii slug generated using the core Inflector::slug() function </li>
                    <li> <b>display: </b> a dummy mode which returns a slug legal for display - removes illegal (not unprintable) characters </li>
                    <li> <b>url: </b> returns a slug appropriate to put in a URL </li>
                    <li> <b>class: </b> a dummy mode which returns a slug appropriate to put in a html class (there are no restrictions) </li>
                    <li> <b>id: </b> returns a slug appropriate to use in a html id </li>
                    <li> <b>{callable}: </b> Use your custom callable to pass in your slugger method </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>    separator  </td>
            <td> `-`   </td>
            <td>    The separator to use     </td>
        </tr>
        <tr>
            <td>    length  </td>
            <td> `null`   </td>
            <td>    Set to 0 for no length. Will be auto-detected if possible via schema.     </td>
        </tr>
        <tr>
            <td>    overwrite    </td>
            <td> `false`   </td>
            <td>
                has the following values
                <ul>
                    <li> <b>false: </b> once the slug has been saved, do not change it (use if you are doing lookups based on slugs) </li>
                    <li> <b>true: </b> if the label field values change, regenerate the slug (use if you are the slug is just window-dressing) </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>    unique    </td>
            <td> `false`   </td>
            <td>
                has the following values
                <ul>
                    <li> <b>false: </b> will not enforce a unique slug, whatever the label is is direclty slugged without checking for duplicates </li>
                    <li> <b>true: </b> use if you are doing lookups based on slugs (see overwrite) </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>    case    </td>
            <td> `null`   </td>
            <td>
                has the following values
                <ul>
                    <li>    <b> null    </b>    don't change the case of the slug           </li>
                    <li>    <b> low     </b>    force lower case. E.g. "this-is-the-slug"   </li>
                    <li>    <b> up      </b>    force upper case E.g. "THIS-IS-THE-SLUG"    </li>
                    <li>    <b> title   </b>    force title case. E.g. "This-Is-The-Slug"   </li>
                    <li>    <b> camel   </b>    force CamelCase. E.g. "ThisIsTheSlug"       </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>    replace  </td>
            <td> <i>see code</i>   </td>
            <td>    Custom replacements as array. `Set to null` to disable.    </td>
        </tr>
        <tr>
            <td>    on  </td>
            <td> `'beforeRules'`   </td>
            <td>    `beforeSave` or `beforeMarshal` or `beforeRules`.     </td>
        </tr>
        <tr>
            <td>    scope  </td>
            <td> `[]`   </td>
            <td>    Certain conditions to use as scope.     </td>
        </tr>
        <tr>
            <td>    tidy  </td>
            <td> `true`   </td>
            <td>    If cleanup should be run on slugging.     </td>
        </tr>
    </tbody>
</table>

## Usage
Attach it to your models in `initialize()` like so:
```php
$this->addBehavior('Tools.Slugged');
```

## Examples

### Persistent slugs
We want to store categories and we need a slug for nice SEO URLs like `/category/[slugname]/`.

```php
$this->addBehavior('Tools.Slugged',
    ['label' => 'name', 'unique' => true, 'mode' => 'ascii']
);
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
$this->addBehavior('Tools.Slugged',
    ['label' => 'name', 'overwrite' => true, 'mode' => 'ascii', 'unique' => true]
);
```
Note that we don't need "unique" either then.

Each save now re-triggers the slug generation.

### Using a custom slugger
You can pass your own callable for slugging into the `mode` config.
And you can even use a static method on any class this way (given it has a static `slug()` method):
```
$this->addBehavior('Tools.Slugged', ['mode' => [MySlugger::class, 'slug']]);
```

Tip: Use `'mode' => [Text::class, 'slug']` if you want to avoid using the deprecated `Inflector::slug()` method.
Don't forget the use statement at the top of the file, though (`use Tools\Utility\Text;`).
