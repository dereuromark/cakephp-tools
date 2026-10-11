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
	 * @var array<string, mixed>
	 */
	protected array $defaultConfig = [];

	/**
	 * @param int $id
	 * @param array<string, mixed> $options
	 *
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function neighbors($id, array $options = []) {
		if (!$id) {
			throw new InvalidArgumentException("The 'id' key is required for find('neighbors')");
		}

		/** @var string $primaryKey */
		$primaryKey = $this->table->getPrimaryKey();
		$sortField = $this->table->hasField('created') ? 'created' : $primaryKey;
		$defaults = [
			'sortField' => $this->table->getAlias() . '.' . $sortField,
		];
		$options += $defaults;

		$normalDirection = (empty($options['reverse']));
		$sortDirWord = $normalDirection ? ['ASC', 'DESC'] : ['DESC', 'ASC'];
		$sortDirSymb = $normalDirection ? ['>=', '<='] : ['<=', '>='];

		if (empty($options['value'])) {
			$data = $this->table->find('all', ...['conditions' => [$primaryKey => $id]])->first();
			if ($data === null) {
				return ['prev' => null, 'next' => null];
			}
			[$model, $sortField] = pluginSplit($options['sortField']);
			$options['value'] = $data->get($sortField);
		}

		$return = [];

		$findOptions = [];
		if (isset($options['contain'])) {
			$findOptions['contain'] = $options['contain'];
		}

		if (!empty($options['fields'])) {
			$findOptions['fields'] = $options['fields'];
		}
		$findOptions['conditions'][$this->table->getAlias() . '.' . $primaryKey . ' !='] = $id;

		$prevOptions = $findOptions;
		$prevOptions['conditions'] = Hash::merge($prevOptions['conditions'], [$options['sortField'] . ' ' . $sortDirSymb[1] => $options['value']]);
		$prevOptions['order'] = [$options['sortField'] => $sortDirWord[1]];
		$return['prev'] = $this->table->find('all', ...$prevOptions)->first();

		$nextOptions = $findOptions;
		$nextOptions['conditions'] = Hash::merge($nextOptions['conditions'], [$options['sortField'] . ' ' . $sortDirSymb[0] => $options['value']]);
		$nextOptions['order'] = [$options['sortField'] => $sortDirWord[0]];
		$return['next'] = $this->table->find('all', ...$nextOptions)->first();

		return $return;
	}

}
