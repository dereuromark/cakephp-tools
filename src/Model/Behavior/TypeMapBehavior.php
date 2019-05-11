<?php

namespace Tools\Model\Behavior;

use Cake\ORM\Behavior;
use RuntimeException;

/**
 * A behavior that will allow changing a table's field types on the fly.
 *
 * Usage: See docs
 *
 * @author Mark Scherer
 * @license MIT
 */
class TypeMapBehavior extends Behavior {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'fields' => [], // Fields to change column type for
	];

	/**
	 * @param array $config
	 * @throws \RuntimeException
	 * @return void
	 */
	public function initialize(array $config): void {
		if (empty($this->_config['fields'])) {
			throw new RuntimeException('Fields are required');
		}
		if (!is_array($this->_config['fields'])) {
			$this->_config['fields'] = (array)$this->_config['fields'];
		}

		foreach ($this->_config['fields'] as $field => $type) {
			if (is_array($type)) {
				$type = $field['type'];
			}
			if (!is_string($type)) {
				throw new RuntimeException('Invalid field type setup.');
			}

			$this->_table->getSchema()->setColumnType($field, $type);
		}
	}

}
