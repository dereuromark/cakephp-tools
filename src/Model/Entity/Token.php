<?php

namespace Tools\Model\Entity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $token
 * @property-read string $key
 * @property string $content
 * @property int $used
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 * @property bool $unlimited
 */
class Token extends Entity {

	/**
	 * Shim to allow ->key access for ->token.
	 *
	 * @deprecated Use token instead.
	 *
	 * @return string|null
	 */
	public function _getKey(): ?string {
		trigger_error('Deprecated. Use ->token instead.', E_USER_DEPRECATED);

		return $this->token;
	}

}
