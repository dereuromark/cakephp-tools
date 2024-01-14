<?php
/**
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */

namespace Tools\Model\Behavior;

use ArrayObject;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Security;

/**
 * Allows entity fields to be automatically encrypted when saving/updating and
 * decrypted when fetching the data
 */
class EncryptionBehavior extends Behavior {

	/**
	 * Default configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'fields' => [],
		'key' => '',
	];

	/**
	 * @param array $config The config passed to the behavior
	 * @return void
	 */
	public function initialize(array $config): void {
		if (isset($config['fields'])) {
			$this->setConfig('fields', $config['fields']);
			$this->_config['fields'] = array_unique($this->_config['fields']);
		}
	}

	/**
	 * Events this listener is interested in.
	 *
	 * @return array<string, mixed>
	 */
	public function implementedEvents(): array {
		return [
			// Trigger this after app models beforeSave hook
			'Model.beforeSave' => [
				'callable' => 'beforeSave',
				'priority' => 100,
			],
			'Model.beforeFind' => 'beforeFind',
		];
	}

	/**
	 * Encrypting the fields
	 *
	 * @param \Cake\Event\EventInterface $event The event
	 * @param \Cake\Datasource\EntityInterface $entity The associated entity
	 * @param \ArrayObject $options Options passed to the event
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$fields = $this->getConfig('fields');
		$key = $this->getConfig('key');
		foreach ($fields as $fieldName) :
			if ($entity->has($fieldName)) :
				$content = $entity->get($fieldName);
				if (!empty($content)) :
					$entity->set($fieldName, Security::encrypt($content, $key));
				endif;
			endif;
		endforeach;
	}

	/**
	 * Decrypting the fields
	 *
	 * @param \Cake\Event\EventInterface $event The event
	 * @param \Cake\ORM\Query\SelectQuery $query The query to adjust
	 * @param \ArrayObject $options The options passed to the event
	 * @param bool $primary Whether the query is the root query or an associated query
	 * @return void
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void {
		$query->formatResults(function (CollectionInterface $results) {
			return $results->map(function ($row) {
				$fields = $this->getConfig('fields');
				$key = $this->getConfig('key');
				foreach ($fields as $fieldName) :
					if (isset($row->$fieldName) && is_resource($row->$fieldName)) :
						$content = stream_get_contents($row->$fieldName);
						if (!empty($content)) {
							$row[$fieldName] = Security::decrypt($content, $key);
						} else {
							$row[$fieldName] = '';
						}
					endif;
				endforeach;

				return $row;
			});
		});
	}

}
