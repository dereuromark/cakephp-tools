<?php

namespace Tools\Model\Entity;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $type
 * @property string $token_key
 * @property string|null $content
 * @property int $used
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property bool $unlimited
 */
class Token extends Entity {
}
