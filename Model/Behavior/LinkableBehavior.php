<?php
App::uses('ModelBehavior', 'Model');

/**
 * LinkableBehavior
 * Light-weight approach for data mining on deep relations between models.
 * Join tables based on model relations to easily enable right to left find operations.
 * Original behavior by rafaelbandeira3 on GitHub.
 * Includes modifications from Terr, n8man, and Chad Jablonski
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * GiulianoB ( https://github.com/giulianob/linkable )
 *
 * @version 1.0;
 *
 * @version 1.1:
 * -Brought in improvements and test cases from Terr. However, THIS VERSION OF LINKABLE IS NOT DROP IN COMPATIBLE WITH Terr's VERSION!
 * -If fields aren't specified, will now return all columns of that model
 * -No need to specify the foreign key condition if a custom condition is given. Linkable will automatically include the foreign key relationship.
 * -Ability to specify the exact condition Linkable should use (e.g. $this->Post->find('first', array('link' => array('User' => array('conditions' => array('exactly' => 'User.last_post_id = Post.id'))))) )
 * This is usually required when doing on-the-fly joins since Linkable generally assumes a belongsTo relationship when no specific relationship is found and may produce invalid foreign key conditions.
 * -Linkable will no longer break queries that use SQL COUNTs
 *
 * @version 1.2:
 * @modified Mark Scherer
 * - works with cakephp2.x (89.84 test coverage)
 */
class LinkableBehavior extends ModelBehavior {

	protected $_key = 'link';

	protected $_options = array(
		'type' => true, 'table' => true, 'alias' => true,
		'conditions' => true, 'fields' => true, 'reference' => true,
		'class' => true, 'defaults' => true
	);

	protected $_defaults = array('type' => 'LEFT');

	public function beforeFind(Model $Model, $query) {
		if (isset($query[$this->_key])) {
			$optionsDefaults = $this->_defaults + array('reference' => $Model->alias, $this->_key => array());
			$optionsKeys = $this->_options + array($this->_key => true);

			// If containable is being used, then let it set the recursive!
			if (empty($query['contain'])) {
				$query = array_merge(array('joins' => array()), $query, array('recursive' => -1));
			} else {
				$query = array_merge(array('joins' => array()), $query);
			}
			$iterators[] = $query[$this->_key];
			$cont = 0;
			do {
				$iterator = $iterators[$cont];
				$defaults = $optionsDefaults;
				if (isset($iterator['defaults'])) {
					$defaults = array_merge($defaults, $iterator['defaults']);
					unset($iterator['defaults']);
				}
				$iterations = Set::normalize($iterator);
				foreach ($iterations as $alias => $options) {
					if ($options === null) {
						$options = array();
					}
					$options = array_merge($defaults, compact('alias'), $options);
					if (empty($options['alias'])) {
						throw new InvalidArgumentException(sprintf('%s::%s must receive aliased links', get_class($this), __FUNCTION__));
					}

					// try to find the class name - important for aliased relations
					foreach ($Model->belongsTo as $relationAlias => $relation) {
						if (empty($relation['className']) || $relationAlias !== $options['alias']) {
							continue;
						}
						$options['class'] = $relation['className'];
						break;
					}
					foreach ($Model->hasOne as $relationAlias => $relation) {
						if (empty($relation['className']) || $relationAlias !== $options['alias']) {
							continue;
						}
						$options['class'] = $relation['className'];
						break;
					}
					foreach ($Model->hasMany as $relationAlias => $relation) {
						if (empty($relation['className']) || $relationAlias !== $options['alias']) {
							continue;
						}
						$options['class'] = $relation['className'];
						break;
					}

					// guess it then
					if (empty($options['table']) && empty($options['class'])) {
						$options['class'] = $options['alias'];
					} elseif (!empty($options['table']) && empty($options['class'])) {
						$options['class'] = Inflector::classify($options['table']);
					}

					// the incoming model to be linked in query using class and alias
					$_Model = ClassRegistry::init(array('class' => $options['class'], 'alias' => $options['alias']));
					// the already in query model that links to $_Model
					$Reference = ClassRegistry::init($options['reference']);
					$db = $_Model->getDataSource();
					$associations = $_Model->getAssociated();
					if (isset($Reference->belongsTo[$_Model->alias])) {
						$type = 'hasOne';
						$association = $Reference->belongsTo[$_Model->alias];
					} elseif (isset($Reference->hasOne[$_Model->alias])) {
						$type = 'belongsTo';
						$association = $Reference->hasOne[$_Model->alias];
					} elseif (isset($Reference->hasMany[$_Model->alias])) {
						$type = 'belongsTo';
						$association = $Reference->hasMany[$_Model->alias];
					} elseif (isset($associations[$Reference->alias])) {
						$type = $associations[$Reference->alias];
						$association = $_Model->{$type}[$Reference->alias];
					} else {
						$_Model->bindModel(array('belongsTo' => array($Reference->alias)));
						$type = 'belongsTo';
						$association = $_Model->{$type}[$Reference->alias];
						$_Model->unbindModel(array('belongsTo' => array($Reference->alias)));
					}

					if (!isset($options['conditions'])) {
						$options['conditions'] = array();
					} elseif (!is_array($options['conditions'])) {
						// Support for string conditions
						$options['conditions'] = array($options['conditions']);
					}

					if (isset($options['conditions']['exactly'])) {
						if (is_array($options['conditions']['exactly']))
							$options['conditions'] = reset($options['conditions']['exactly']);
						else
							$options['conditions'] = array($options['conditions']['exactly']);
					} else {
						if ($type === 'belongsTo') {
							$modelKey = $_Model->escapeField($association['foreignKey']);
							$modelKey = str_replace($_Model->alias, $options['alias'], $modelKey);
							$referenceKey = $Reference->escapeField($Reference->primaryKey);
							$options['conditions'][] = "{$referenceKey} = {$modelKey}";
						} elseif ($type === 'hasAndBelongsToMany') {
							// try to determine fields by HABTM model
							if (isset($association['with'])) {
								$Link = $_Model->{$association['with']};
								if (isset($Link->belongsTo[$_Model->alias])) {
									$modelLink = $Link->escapeField($Link->belongsTo[$_Model->alias]['foreignKey']);
								}
								if (isset($Link->belongsTo[$Reference->alias])) {
									$referenceLink = $Link->escapeField($Link->belongsTo[$Reference->alias]['foreignKey']);
								}
							} else {
								$Link = $_Model->{Inflector::classify($association['joinTable'])};
							}
							// try to determine fields by current model relation settings
							if (empty($modelLink) && !empty($_Model->{$type}[$Reference->alias]['foreignKey'])) {
								$modelLink = $Link->escapeField($_Model->{$type}[$Reference->alias]['foreignKey']);
							}
							if (empty($referenceLink) && !empty($_Model->{$type}[$Reference->alias]['associationForeignKey'])) {
								$referenceLink = $Link->escapeField($_Model->{$type}[$Reference->alias]['associationForeignKey']);
							}
							// fallback to defaults otherwise
							if (empty($modelLink)) {
								$modelLink = $Link->escapeField($association['foreignKey']);
							}
							if (empty($referenceLink)) {
								$referenceLink = $Link->escapeField($association['associationForeignKey']);
							}
							$referenceKey = $Reference->escapeField();
							$query['joins'][] = array(
								'alias' => $Link->alias,
								'table' => $Link->table, //$Link->getDataSource()->fullTableName($Link),
								'conditions' => "{$referenceLink} = {$referenceKey}",
								'type' => 'LEFT',
							);
							$modelKey = $_Model->escapeField();
							$modelKey = str_replace($_Model->alias, $options['alias'], $modelKey);
							$options['conditions'][] = "{$modelLink} = {$modelKey}";
						} else {
							$referenceKey = $Reference->escapeField($association['foreignKey']);
							$modelKey = $_Model->escapeField($_Model->primaryKey);
							$modelKey = str_replace($_Model->alias, $options['alias'], $modelKey);
							$options['conditions'][] = "{$modelKey} = {$referenceKey}";
						}
					}

					if (empty($options['table'])) {
						$options['table'] = $_Model->table;
					}

					// Decide whether we should mess with the fields or not
					// If this query is a COUNT query then we just leave it alone
					if (!isset($query['fields']) || is_array($query['fields']) || strpos($query['fields'], 'COUNT(*)') === false) {
						if (!empty($options['fields'])) {
							if ($options['fields'] === true && !empty($association['fields'])) {
								$options['fields'] = $db->fields($_Model, null, $association['fields']);
							} elseif ($options['fields'] === true) {
								$options['fields'] = $db->fields($_Model);
							} else {
								$options['fields'] = $db->fields($_Model, null, $options['fields']);
							}
						} elseif (!isset($options['fields']) || (isset($options['fields']) && !is_array($options['fields']))) {
							if (!empty($association['fields'])) {
									$options['fields'] = $db->fields($_Model, null, $association['fields']);
							} else {
									$options['fields'] = $db->fields($_Model);
							}
						}

						if (!empty($options['class']) && $options['class'] !== $alias) {
							//$options['fields'] = str_replace($options['class'], $alias, $options['fields']);
						}
						if (is_array($query['fields'])) {
							$query['fields'] = array_merge($query['fields'], $options['fields']);
						} else {
							// If user didn't specify any fields then select all fields by default (just as find would)
							$query['fields'] = array_merge($db->fields($Model), $options['fields']);
						}
					}

					$options[$this->_key] = array_merge($options[$this->_key], array_diff_key($options, $optionsKeys));
					$options = array_intersect_key($options, $optionsKeys);
					if (!empty($options[$this->_key])) {
						$iterators[] = $options[$this->_key] + array('defaults' => array_merge($defaults, array('reference' => $options['class'])));
					}

					$query['joins'][] = array_intersect_key($options, array('type' => true, 'alias' => true, 'table' => true, 'conditions' => true));
				}
				$cont++;
				$notDone = isset($iterators[$cont]);
			} while ($notDone);
		}

		unset($query['link']);

		return $query;
	}

}
