# Enums
Since CakePHP 5 you can now use native enums.
Just spice it a bit with Tools plugin magic, and all good to go.

Add the `EnumOptionsTrait` to your enums to have `options()` method available in your templates etc.

```php
use Tools\Model\Enum\EnumOptionsTrait;

enum UserStatus: int implements EnumLabelInterface {

	use EnumOptionsTrait;

    ...

}
```

If you now need the options array for some entity-less form, you can use it as such:
```php
echo $this->Form->control('status', ['options' => \App\Model\Enum\UserStatus::options()]);
```
The same applies if you ever need to narrow down the options (e.g. not display some values as dropdown option),
or if you want to resort the options for display:

```php
$options = UserStatus::options([UserStatus::ACTIVE, UserStatus::INACTIVE]);
echo $this->Form->control('status', ['options' => $options]);
```
