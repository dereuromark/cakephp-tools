# Enums

`EnumOptionsTrait` builds a form options array from a backed enum whose cases implement CakePHP's `EnumLabelInterface`.

Create `src/Model/Enum/UserStatus.php`:

```php
namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Tools\Model\Enum\EnumOptionsTrait;

enum UserStatus: int implements EnumLabelInterface {
    use EnumOptionsTrait;

    case ACTIVE = 1;
    case INACTIVE = 2;

    public function label(): string {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }
}
```

Use all cases in a template:

```php
use App\Model\Enum\UserStatus;

echo $this->Form->control('status', ['options' => UserStatus::options()]);
```

Pass cases to narrow or reorder the options:

```php
$options = UserStatus::options([UserStatus::INACTIVE, UserStatus::ACTIVE]);
echo $this->Form->control('status', ['options' => $options]);
```

The trait also accepts backed values in that list. Invalid values throw the enum's `ValueError`.
