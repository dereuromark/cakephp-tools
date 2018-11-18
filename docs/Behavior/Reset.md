# Reset Behavior

A CakePHP behavior to automatically "reset" all records as batch process
- Re-triggers all callbacks (beforeValidate/beforeSave)
- Batch process of x entries per run to avoid memory overkill
- Custom callbacks attachable

## Configs
| Key  | Default | Description |
| ------------- | ------------- | ------------- |
|   limit           |   100       |    batch of records per loop    |
|   timeout         |   null      |    in seconds                   |
|   fields          |   array()   |    if not displayField          |
|   updateFields    |   array()   |    if saved fields should be different from fields  |
|   validate        |   true      |    trigger beforeValidate callback            |
|   updateTimestamp |   false     |    update modified/updated timestamp          |
|   scope           |   array()   |    optional conditions         |
|   callback        |   null      |    |

## Usage
Attach it to your models in `initialize()` like so:
```php
$this->addBehavior('Tools.Reset', $config);
```

This resetting should ideally be done via CLI shell/task.
You should make a shell command for this and execute this migration code once
on deploy of the modified code or SQL schema.
If you are not using CLI, make sure you set the time limit in your controller action accordingly (HOUR for example).

## Examples

### Resetting Slugs
```php
// First we need to re-load the Slugged behavior to enable "overwrite" mode
$this->Post->removeBehavior('Slugged');
$this->Post->addBehavior('Tools.Slugged', ['label' => 'title', 'overwite' => true]);
// Load the Reset behavior with only the title and slug field to read and modify.
$this->Post->addBehavior('Tools.Reset', ['fields' => ['title', 'slug']]);
$res = $this->Post->resetRecords();
// Debug output with number of records modified in $res
```

### Retrigger/Init Geocoding
```php
$this->Post->addBehavior('Tools.Reset', ['fields' => ['address', 'lat', 'lng'], 'timeout' => 3]);
$res = $this->Post->resetRecords();
```
Since all lat/lng fields are still null it will geocode the records and populate those fields.
It will skip already geocoded ones. If you want to skip those completely (not even read them),
just set the scope to `'NOT' => ['lat' => null]` etc.

Note that in this case we also use a timeout to avoid getting a penalty by Google for geocoding too many records per minute.

### Advanced example: Resetting composite cache field

In this case we added a new cache field to our messages in order to make the search faster with >> 100000 records. The data was containing all the info we needed – in serialized format. We needed a callback here as there was some logic involved. So we simply made a shell containing both callback method and shell command:
```php
$this->Messages->addBehavior('Tools.Reset', [
    'fields' => ['data'], 'updateFields' => ['guest_name'],
    'scope' => ['data LIKE' => '{%'], 'callback' => 'UpdateShell::prepMessage'
    ]);
$res = $this->Messages->resetRecords();
$this->out('Done: ' . $res);
```

The callback method (in this case just statically, as we didnt want to mess with the model itself):
```php
public static function prepMessage(array $row) {
    if (empty($row['data_array']['GUEST_FIRST_NAME'])) {
        return [];
    }

    $row['guest_name'] = $row['data_array']['GUEST_FIRST_NAME'] . ' ' . $row['data_array']['GUEST_LAST_NAME'];
    return $row;
}
```
See the test cases for more ways to use callbacks – including adjusting the updateFields list.

So as you can see, everything that involves a complete “re-save” including triggering of important
callbacks (in model and behaviors) of all or most records can leverage this behavior in a DRY, quick and reusable way.
