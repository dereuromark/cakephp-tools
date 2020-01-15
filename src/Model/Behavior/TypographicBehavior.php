<?php
/**
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */

namespace Tools\Model\Behavior;

use ArrayAccess;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;

/**
 * Replace regionalized chars with standard ones on input.
 *
 * “smart quotes” become "dumb quotes" on save
 * „low-high“ become "high-high"
 * same for single quotes (apostrophes)
 * in order to unify them. Basic idea is a unified non-regional version in the database.
 *
 * Using the TypographyHelper we can then format the output
 * according to the language/regional setting (in some languages
 * the high-high smart quotes, in others the low-high ones are preferred)
 *
 * Settings are:
 * - string $before (validate/save)
 * - array $fields (leave empty for auto detection)
 * - bool $mergeQuotes (merge single and double into " or any custom char)
 *
 * TODOS:
 * - respect primary and secondary quotations marks as well as alternatives
 *
 * @link https://www.dereuromark.de/2012/08/12/typographic-behavior-and-typography-helper/
 * @link http://en.wikipedia.org/wiki/Non-English_usage_of_quotation_marks
 */
class TypographicBehavior extends Behavior {

	const BEFORE_MARSHAL = 'marshal';
	const BEFORE_SAVE = 'save';

	/**
	 * @var array
	 */
	protected $_map = [
		'in' => [
			'‘' => '\'',
			// Translates to '&lsquo;'.
			'’' => '\'',
			// Translates to '&rsquo;'.
			'‚' => '\'',
			// Translates to '&sbquo;'.
			'‛' => '\'',
			// Translates to '&#8219;'.
			'“' => '"',
			// Translates to '&ldquo;'.
			'”' => '"',
			// Translates to '&rdquo;'.
			'„' => '"',
			// Translates to '&bdquo;'.
			'‟' => '"',
			// Translates to '&#8223;'.
			'«' => '"',
			// Translates to '&laquo;'.
			'»' => '"',
			// Translates to '&raquo;'.
			'‹' => '\'',
			// Translates to '&laquo;'.
			'›' => '\'',
			// Translates to '&raquo;'.
		],
		'out' => [
			// Use the TypographyHelper for this at runtime.
		],
	];

	/**
	 * @var int|null
	 */
	protected $_id;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'before' => self::BEFORE_SAVE, // save or marshal
		'fields' => [],
		'mergeQuotes' => false, // Set to true for " or explicitly set a char (" or ').
	];

	/**
	 * Initiate behavior for the model using specified settings.
	 * Available settings:
	 *
	 * @param array $config Settings to override for model.
	 * @return void
	 */
	public function initialize(array $config): void {
		if (empty($this->_config['fields'])) {
			$schema = $this->getTable()->getSchema();

			$fields = [];
			foreach ($schema->columns() as $field) {
				$v = $schema->getColumn($field);
				if (!in_array($v['type'], ['string', 'text'])) {
					continue;
				}
				if (!empty($v['key'])) {
					continue;
				}
				if (isset($v['length']) && $v['length'] === 1) { // TODO: also skip UUID (lenght 36)?
					continue;
				}
				$fields[] = $field;
			}
			$this->_config['fields'] = $fields;
		}
		if ($this->_config['mergeQuotes'] === true) {
			$this->_config['mergeQuotes'] = '"';
		}
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return bool
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options) {
		if ($this->_config['before'] === 'marshal') {
			$this->process($data);
		}

		return true;
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options) {
		if ($this->_config['before'] === 'save') {
			$this->process($entity);
		}
	}

	/**
	 * Run the behavior over all records of this model
	 * This is useful if you attach it after some records have already been saved without it.
	 *
	 * @param bool $dryRun
	 * @return int count Number of affected/changed records
	 */
	public function updateTypography($dryRun = false) {
		$options = ['limit' => 100, 'offset' => 0];
		$count = 0;
		while ($records = $this->getTable()->find('all', $options)->toArray()) {
			foreach ($records as $record) {
				$changed = false;
				foreach ($this->_config['fields'] as $field) {
					if (empty($record[$field])) {
						continue;
					}
					$tmp = $this->_prepareInput($record[$field]);
					if ($tmp == $record[$field]) {
						continue;
					}
					$record[$field] = $tmp;
					$changed = true;
				}
				if ($changed) {
					if (!$dryRun) {
						$this->getTable()->save($record, ['validate' => false]);
					}
					$count++;
				}
			}
			$options['offset'] += 100;
		}
		return $count;
	}

	/**
	 * Run before a model is saved
	 *
	 * @param \ArrayAccess $data
	 * @return void
	 */
	public function process(ArrayAccess $data) {
		foreach ($this->_config['fields'] as $field) {
			if (!empty($data[$field])) {
				$data[$field] = $this->_prepareInput($data[$field]);
			}
		}
	}

	/**
	 * @param string $string
	 * @return string cleanedInput
	 */
	protected function _prepareInput($string) {
		$map = $this->_map['in'];
		if ($this->_config['mergeQuotes']) {
			foreach ($map as $key => $val) {
				$map[$key] = $this->_config['mergeQuotes'];
			}
		}
		return str_replace(array_keys($map), array_values($map), $string);
	}

}
