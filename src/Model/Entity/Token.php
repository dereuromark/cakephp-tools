<?php

namespace Tools\Model\Entity;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $type
 * @property string $token_key
 * @property string $key Deprecated, use token_key instead.
 * @property string|null $content
 * @property int $used
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property bool $unlimited
 */
class Token extends Entity {

	/**
	 * Shim to allow ->key access for ->token_key.
	 *
	 * @deprecated Use token_key instead.
	 *
	 * @see \Tools\Model\Entity\Token::$key
	 *
	 * @return string|null
	 */
	public function _getKey(): ?string {
		trigger_error('Deprecated. Use ->token_key instead.', E_USER_DEPRECATED);

		return $this->token_key;
	}

	/**
	 * Shim to allow ->key access for ->token_key.
	 *
	 * @deprecated Use token_key instead.
	 *
	 * @see \Tools\Model\Entity\Token::$key
	 *
	 * @param string|null $key
	 *
	 * @return void
	 */
	public function _setKey(?string $key): void {
		trigger_error('Deprecated. Use ->token_key instead.', E_USER_DEPRECATED);

		$this->token_key = $key;
	}

}
