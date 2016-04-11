<?php

namespace Tools\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Utility\Hash;
use InvalidArgumentException;

/**
 * Neighbor Behavior
 */
class NeighborBehavior extends Behavior {

	/**
	 * Default config for a model that has this behavior attached.
	 *
	 * Setting force_modified to true will have the same effect as overriding the save method as
	 * described in the code example for "Using created and modified" in the Cookbook.
	 *
	 * @var array
	 * @link http://book.cakephp.org/2.0/en/models/saving-your-data.html#using-created-and-modified
	 */
	protected $_defaultConfig = [
	];

	public function neighbors($id, array $options = []) {
		if (empty($id)) {
			throw new InvalidArgumentException("The 'id' key is required for find('neighbors')");
		}
		$sortField = $this->_table->hasField('created') ? 'created' : $this->_table->primaryKey();
		$defaults = [
			'sortField' => $this->_table->alias() . '.' . $sortField,
			//'displayField' => $this->_table->alias() . '.' . $this->_table->displayField()
		];
		$options += $defaults;

		$normalDirection = (!empty($options['reverse']) ? false : true);
		$sortDirWord = $normalDirection ? ['ASC', 'DESC'] : ['DESC', 'ASC'];
		$sortDirSymb = $normalDirection ? ['>=', '<='] : ['<=', '>='];

		if (empty($options['value'])) {
			$data = $this->_table->find('all', ['conditions' => [$this->_table->primaryKey() => $id]])->first();
			list($model, $sortField) = pluginSplit($options['sortField']);
			$options['value'] = $data[$sortField];
		}

		$return = [];

		$findOptions = [];
		if (isset($options['contain'])) {
			$findOptions['contain'] = $options['contain'];
		}

		if (!empty($options['fields'])) {
			$findOptions['fields'] = $options['fields'];
		}
		$findOptions['conditions'][$this->_table->alias() . '.' . $this->_table->primaryKey() . ' !='] = $id;

		$prevOptions = $findOptions;
		$prevOptions['conditions'] = Hash::merge($prevOptions['conditions'], [$options['sortField'] . ' ' . $sortDirSymb[1] => $options['value']]);
		$prevOptions['order'] = [$options['sortField'] => $sortDirWord[1]];
		//debug($prevOptions);
		$return['prev'] = $this->_table->find('all', $prevOptions)->first();

		$nextOptions = $findOptions;
		$nextOptions['conditions'] = Hash::merge($nextOptions['conditions'], [$options['sortField'] . ' ' . $sortDirSymb[0] => $options['value']]);
		$nextOptions['order'] = [$options['sortField'] => $sortDirWord[0]];
		//debug($nextOptions);
		$return['next'] = $this->_table->find('all', $nextOptions)->first();

		return $return;
	}

}
