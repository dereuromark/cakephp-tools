<?php

App::uses('ModelBehavior', 'Model');
App::uses('Set', 'Utility'); // TODO: use Hash instead of Set

/**
 * Revision Behavior
 *
 * Revision is a solution for adding undo and other versioning functionality
 * to your database models. It is set up to be easy to apply to your project,
 * to be easy to use and not get in the way of your other model activity.
 * It is also intended to work well with it's sibling, LogableBehavior.
 *
 * Feature list :
 *
 * - Easy to install
 * - Automagically save revision on model save
 * - Able to ignore model saves which only contain certain fields
 * - Limit number of revisions to keep, will delete oldest
 * - Undo functionality (or update to any revision directly)
 * - Revert to a datetime (and even do so cascading)
 * - Get a diff model array to compare two or more revisions
 * - Inspect any or all revisions of a model
 * - Work with Tree Behavior
 * - Includes beforeUndelete and afterUndelete callbacks
 * - NEW As of 1.2 behavior will revision HABTM relationships (from one way)
 *
 * Install instructions :
 *
 * - Place the newest version of RevisionBehavior in your app/models/behaviors folder
 * - Add the behavior to AppModel (or single models if you prefer)
 * - Create a shadow table for each model that you want revision for.
 * - Behavior will gracefully do nothing for models that has behavior, without table
 * - If adding to an existing project, run the initializeRevisions() method once for each model.
 *
 * About shadow tables :
 *
 * You should make these AFTER you have baked your ordinary tables as they may interfer. By default
 * the tables should be named "[prefix][model_table_name]_revs" If you wish to change the suffix you may
 * do so in the property called $revisionSuffix found bellow. Also by default the behavior expects
 * the revision tables to be in the same dbconfig as the model, but you may change this on a per
 * model basis with the useDbConfig config option.
 *
 * Add the same fields as in the live table, with 3 important differences.
 * - The 'id' field should NOT be the primary key, nor auto increment
 * - Add the fields 'version_id' (int, primary key, autoincrement) and
 * 'version_created' (datetime)
 * - Skipp fields that should not be saved in shadowtable (lft,right,weight for instance)
 *
 * Configuration :
 *
 * - 'limit' : number of revisions to keep, must be at least 2
 * - 'ignore' : array containing the name of fields to ignore
 * - 'auto' : boolean when false the behavior will NOT generate revisions in afterSave
 * - 'useDbConfig' : string/null Name of dbConfig to use. Null to use Model's
 *
 * Limit functionality :
 * The shadow table will save a revision copy when it saves live data, so the newest
 * row in the shadow table will (in most cases) be the same as the current live data.
 * The exception is when the ignore field functionality is used and the live data is
 * updated only in those fields.
 *
 * Ignore field(s) functionality :
 * If you wish to be able to update certain fields without generating new revisions,
 * you can add those fields to the configuration ignore array. Any time the behavior's
 * afterSave is called with just primary key and these fields, it will NOT generate
 * a new revision. It WILL however save these fields together with other fields when it
 * does save a revision. You will probably want to set up cron or otherwise call
 * createRevision() to update these fields at some points.
 *
 * Auto functionality :
 * By default the behavior will insert itself into the Model's save process by implementing
 * beforeSave and afterSave. In afterSave, the behavior will save a new revision of the dataset
 * that is now the live data. If you do NOT want this automatic behavior, you may set the config
 * option 'auto' to false. Then the shadow table will remain empty unless you call createRevisions
 * manually.
 *
 * HABTM revision feature :
 * In order to do revision on HABTM relationship, add a text field to the main model's shadow table
 * with the same name as the association, ie if Article habtm ArticleTag as Tag, add a field 'Tag'
 * to articles_revs.
 * NB! In version 1.2 and up to current, Using HABTM revision requires that both models uses this
 * behavior (even if secondary model does not have a shadow table).
 *
 * 1.1.1 => 1.1.2 changelog
 * - revisions() got new paramter: $includeCurrent
 * This now defaults to false, resulting in a change from 1.1.1. See tests
 *
 * 1.1.6 => 1.2
 * - includes HABTM revision control (one way)
 *
 * 1.2 => 1.2.1
 * - api change in revertToDate, added paramter for force delete if reverting to before earliest
 *
 * 1.2.6 => 1.2.7
 * 	 - api change: removed shadow(), changed revertToDate() to only recurse into related models that
 * are dependent when cascade is true
 *
 * 2.0.5 => CakePHP 2.x
 *
 * @author Ronny Vindenes
 * @author Alexander 'alkemann' Morland
 * @license MIT
 * @modifed 27. march 2009
 * @version 2.0.5
 * @modified 2012-07-28 Mark Scherer (2.x ready)
 * @cakephp 2.x
 */
class RevisionBehavior extends ModelBehavior {

	/**
	 * Shadow table prefix.
	 * Only change this value if it causes table name crashes.
	 *
	 * @var string
	 */
	public $revisionSuffix = '_revs';

	/**
	 * Defaul setting values.
	 *
	 * @var array
	 */
	protected $_defaults = array(
		'limit' => false,
		'auto' => true,
		'ignore' => array(),
		'useDbConfig' => null,
		'model' => null);

	/**
	 * Old data, used to detect changes.
	 *
	 * @var array
	 */
	protected $_oldData = array();

	/**
	 * Configure the behavior through the Model::actsAs property
	 *
	 * @param object $Model
	 * @param array $config
	 * @return void
	 */
	public function setup(Model $Model, $config = array()) {
		if (!empty($config)) {
			$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
		} else {
			$this->settings[$Model->alias] = $this->_defaults;
		}
		$this->createShadowModel($Model);
		$Model->Behaviors->load('Containable');
	}

	/**
	 * Manually create a revision of the current record of Model->id
	 *
	 * @example $this->Post->id = 5; $this->Post->createRevision();
	 * @param object $Model
	 * @return boolean Success
	 */
	public function createRevision(Model $Model) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return false;
		}
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return false;
		}
		$habtm = array();
		$allHabtm = $Model->getAssociated('hasAndBelongsToMany');
		foreach ($allHabtm as $assocAlias) {
			if (isset($Model->ShadowModel->_schema[$assocAlias])) {
				$habtm[] = $assocAlias;
			}
		}
		$data = $Model->find('first', array('conditions' => array($Model->alias . '.' . $Model->primaryKey => $Model->id),
				'contain' => $habtm));
		$Model->ShadowModel->create($data);
		$Model->ShadowModel->set('version_created', date('Y-m-d H:i:s'));
		foreach ($habtm as $assocAlias) {
			$foreignKeys = Set::extract($data, '/' . $assocAlias . '/' . $Model->{$assocAlias}->primaryKey);
			$Model->ShadowModel->set($assocAlias, implode(',', $foreignKeys));
		}
		return (bool)$Model->ShadowModel->save();
	}

	/**
	 * Returns an array that maps to the Model, only with multiple values for fields that has been changed
	 *
	 * @example $this->Post->id = 4; $changes = $this->Post->diff();
	 * @example $this->Post->id = 4; $myChanges = $this->Post->diff(null,nul, array('conditions'=>array('user_id'=>4)));
	 * @example $this->Post->id = 4; $difference = $this->Post->diff(45,192);
	 * @param Object $Model
	 * @param integer $fromVersionId
	 * @param integer $toVersionId
	 * @param array $options
	 * @return array
	 */
	public function diff(Model $Model, $fromVersionId = null, $toVersionId = null, $options = array()) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return array();
		}
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return array();
		}
		if (isset($options['conditions'])) {
			$conditions = array_merge($options['conditions'], array($Model->primaryKey => $Model->id));
		} else {
			$conditions = array($Model->primaryKey => $Model->id);
		}
		if (is_numeric($fromVersionId) || is_numeric($toVersionId)) {
			if (is_numeric($fromVersionId) && is_numeric($toVersionId)) {
				$conditions['version_id'] = array($fromVersionId, $toVersionId);
				if ($Model->ShadowModel->find('count', array('conditions' => $conditions)) < 2) {
					return array();
				}
			} else {
				if (is_numeric($fromVersionId)) {
					$conditions['version_id'] = $fromVersionId;
				} else {
					$conditions['version_id'] = $toVersionId;
				}
				if ($Model->ShadowModel->find('count', array('conditions' => $conditions)) < 1) {
					return array();
				}
			}
		}
		$conditions = array($Model->primaryKey => $Model->id);
		if (is_numeric($fromVersionId)) {
			$conditions['version_id >='] = $fromVersionId;
		}
		if (is_numeric($toVersionId)) {
			$conditions['version_id <='] = $toVersionId;
		}
		$options['conditions'] = $conditions;
		$all = $this->revisions($Model, $options, true);
		if (!$all) {
			return array();
		}
		$unified = array();
		$keys = array_keys($all[0][$Model->alias]);
		foreach ($keys as $field) {
			$allValues = Set::extract($all, '/' . $Model->alias . '/' . $field);
			$allValues = array_reverse(array_unique(array_reverse($allValues, true)), true);
			if (sizeof($allValues) == 1) {
				$unified[$field] = reset($allValues);
			} else {
				$unified[$field] = $allValues;
			}
		}
		return array($Model->alias => $unified);
	}

	/**
	 * Will create a current revision of all rows in Model, if none exist.
	 * Use this if you add the revision to a model that allready has data in
	 * the DB.
	 * If you have large tables or big/many fields, use $limit to reduce the
	 * number of rows that is run at once.
	 *
	 * @example $this->Post->initializeRevisions();
	 * @param object $Model
	 * @param integer $limit number of rows to initialize in one go
	 * @return boolean Success
	 */
	public function initializeRevisions(Model $Model, $limit = 100) {
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return false;
		}
		if ($Model->ShadowModel->useTable === false) {
			trigger_error('RevisionBehavior: Missing shadowtable : ' . $Model->table . $this->suffix, E_USER_WARNING);
			return false;
		}
		if ($Model->ShadowModel->find('count') != 0) {
			return false;
		}
		$count = $Model->find('count');
		if ($limit < $count) {
			$remaining = $count;
			for ($p = 1; true; $p++) {

				$this->init($Model, $p, $limit);

				$remaining = $remaining - $limit;
				if ($remaining <= 0) {
					break;
				}
			}
		} else {
			$this->init($Model, 1, $count);
		}
		return true;
	}

	/**
	 * Saves revisions for rows matching page and limit given
	 *
	 * @param object $Model
	 * @param integer $page
	 * @param integer $limit
	 * @return void
	 */
	protected function init(Model $Model, $page, $limit) {
		$habtm = array();
		$allHabtm = $Model->getAssociated('hasAndBelongsToMany');
		foreach ($allHabtm as $assocAlias) {
			if (isset($Model->ShadowModel->_schema[$assocAlias])) {
				$habtm[] = $assocAlias;
			}
		}
		$all = $Model->find('all', array(
			'limit' => $limit,
			'page' => $page,
			'contain' => $habtm));
		$versionCreated = date('Y-m-d H:i:s');
		foreach ($all as $data) {
			$Model->ShadowModel->create($data);
			$Model->ShadowModel->set('version_created', $versionCreated);
			$Model->ShadowModel->save();
		}
	}

	/**
	 * Finds the newest revision, including the current one.
	 * Use with caution, the live model may be different depending on the usage
	 * of ignore fields.
	 *
	 * @example $this->Post->id = 6; $newestRevision = $this->Post->newest();
	 * @param object $Model
	 * @param array $options
	 * @return array
	 */
	public function newest(Model $Model, $options = array()) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return array();
		}
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return array();
		}
		if (isset($options['conditions'])) {
			$options['conditions'] = array_merge($options['conditions'], array($Model->alias . '.' . $Model->primaryKey => $Model->id));
		} else {
			$options['conditions'] = array($Model->alias . '.' . $Model->primaryKey => $Model->id);
		}

		return $Model->ShadowModel->find('first', $options);
	}

	/**
	 * Find the oldest revision for the current Model->id
	 * If no limit is used on revision and revision has been enabled for the model
	 * since start, this call will return the original first record.
	 *
	 * @example $this->Post->id = 2; $original = $this->Post->oldest();
	 * @param object $Model
	 * @param array $options
	 * @return array
	 */
	public function oldest(Model $Model, $options = array()) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return array();
		}
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return array();
		}
		if (isset($options['conditions'])) {
			$options['conditions'] = array_merge($options['conditions'], array($Model->primaryKey => $Model->id));
		} else {
			$options['conditions'] = array($Model->primaryKey => $Model->id);
		}
		$options['order'] = 'version_created ASC, version_id ASC';
		return $Model->ShadowModel->find('first', $options);
	}

	/**
	 * Find the second newest revisions, including the current one.
	 *
	 * @example $this->Post->id = 6; $undoRevision = $this->Post->previous();
	 * @param object $Model
	 * @param array $options
	 * @return array
	 */
	public function previous(Model $Model, $options = array()) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return array();
		}
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return array();
		}
		$options['limit'] = 1;
		$options['page'] = 2;
		if (isset($options['conditions'])) {
			$options['conditions'] = array_merge($options['conditions'], array($Model->primaryKey => $Model->id));
		} else {
			$options['conditions'] = array($Model->primaryKey => $Model->id);
		}
		$revisions = $Model->ShadowModel->find('all', $options);
		if (!$revisions) {
			return array();
		}
		return $revisions[0];
	}

	/**
	 * Revert all rows matching conditions to given date.
	 * Model rows outside condition or not edited will not be affected. Edits since date
	 * will be reverted and rows created since date deleted.
	 *
	 * @param object $Model
	 * @param array $options 'conditions','date'
	 * @return boolean Success
	 */
	public function revertAll(Model $Model, $options = array()) {
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return false;
		}
		if (empty($options) || !isset($options['date'])) {
			return false;
		}
		if (!isset($options['conditions'])) {
			$options['conditions'] = array();
		}
		// leave model rows out side of condtions alone
		// leave model rows not edited since date alone

		$all = $Model->find('all', array('conditions' => $options['conditions'], 'fields' => $Model->primaryKey));
		$allIds = Set::extract($all, '/' . $Model->alias . '/' . $Model->primaryKey);

		$cond = $options['conditions'];
		$cond['version_created <'] = $options['date'];
		$createdBeforeDate = $Model->ShadowModel->find('all', array(
			'order' => $Model->primaryKey,
			'conditions' => $cond,
			'fields' => array('version_id', $Model->primaryKey)));
		$createdBeforeDateIds = Set::extract($createdBeforeDate, '/' . $Model->alias . '/' . $Model->primaryKey);

		$deleteIds = array_diff($allIds, $createdBeforeDateIds);

		// delete all Model rows where there are only version_created later than date
		$Model->deleteAll(array($Model->alias . '.' . $Model->primaryKey => $deleteIds), false, true);

		unset($cond['version_created <']);
		$cond['version_created >='] = $options['date'];
		$createdAfterDate = $Model->ShadowModel->find('all', array(
			'order' => $Model->primaryKey,
			'conditions' => $cond,
			'fields' => array('version_id', $Model->primaryKey)));
		$createdAfterDateIds = Set::extract($createdAfterDate, '/' . $Model->alias . '/' . $Model->primaryKey);
		$updateIds = array_diff($createdAfterDateIds, $deleteIds);

		$revertSuccess = true;
		// update model rows that have version_created earlier than date to latest before date
		foreach ($updateIds as $mid) {
			$Model->id = $mid;
			if (!$Model->revertToDate($options['date'])) {
				$revertSuccess = false;
			}
		}
		return $revertSuccess;
	}

	/**
	 * Revert current Model->id to the given revision id
	 * Will return false if version id is invalid or save fails
	 *
	 * @example $this->Post->id = 3; $this->Post->revertTo(12);
	 * @param object $Model
	 * @param integer $versionId
	 * @return boolean Success
	 */
	public function revertTo(Model $Model, $versionId) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return false;
		}
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return false;
		}
		$data = $Model->ShadowModel->find('first', array('conditions' => array('version_id' => $versionId)));
		if (!$data) {
			return false;
		}
		foreach ($Model->getAssociated('hasAndBelongsToMany') as $assocAlias) {
			if (isset($Model->ShadowModel->_schema[$assocAlias])) {
				$data[$assocAlias][$assocAlias] = explode(',', $data[$Model->alias][$assocAlias]);
			}
		}
		return (bool)$Model->save($data);
	}

	/**
	 * Revert to the oldest revision after the given datedate.
	 * Will cascade to hasOne and hasMany associeted models if $cascade is true.
	 * Will return false if no change is made on the main model
	 *
	 * @example $this->Post->id = 3; $this->Post->revertToDate(date('Y-m-d H:i:s',strtotime('Yesterday')));
	 * @example $this->Post->id = 4; $this->Post->revertToDate('2008-09-01',true);
	 * @param object $Model
	 * @param string $datetime
	 * @param boolean $cascade
	 * @param boolean $forceDelete
	 * @return boolean Success
	 */
	public function revertToDate(Model $Model, $datetime, $cascade = false, $forceDelete = false) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return null;
		}
		if ($cascade) {
			$associated = array_merge($Model->hasMany, $Model->hasOne);
			foreach ($associated as $assoc => $data) {

				// Continue with next association if no shadow model
				if (empty($Model->$assoc->ShadowModel)) {
					continue;
				}

				$ids = array();

				$cascade = false;
				/* Check if association has dependent children */
				$depassoc = array_merge($Model->$assoc->hasMany, $Model->$assoc->hasOne);
				foreach ($depassoc as $dep) {
					if ($dep['dependent']) {
						$cascade = true;
					}
				}

				/* Query live data for children */
				$children = $Model->$assoc->find('list', array('conditions' => array($data['foreignKey'] => $Model->id), 'recursive' =>
						-1));
				if (!empty($children)) {
					$ids = array_keys($children);
				}

				/* Query shadow table for deleted children */
				$revisionChildren = $Model->$assoc->ShadowModel->find('all', array(
					'fields' => array('DISTINCT ' . $Model->primaryKey),
					'conditions' => array($data['foreignKey'] => $Model->id, 'NOT' => array($Model->primaryKey => $ids)),
					));
				if (!empty($revisionChildren)) {
					$ids = array_merge($ids, Set::extract($revisionChildren, '/' . $assoc . '/' . $Model->$assoc->primaryKey));
				}

				/* Revert all children */
				foreach ($ids as $id) {
					$Model->$assoc->id = $id;
					$Model->$assoc->revertToDate($datetime, $cascade, $forceDelete);
				}
			}
		}
		if (empty($Model->ShadowModel)) {
			return true;
		}
		$data = $Model->ShadowModel->find('first', array('conditions' => array($Model->primaryKey => $Model->id,
					'version_created <=' => $datetime), 'order' => 'version_created ASC, version_id ASC'));
		/* If no previous version was found and revertToDate() was called with force_delete, then delete the live data, else leave it alone */
		if (!$data) {
			if ($forceDelete) {
				$Model->logableAction['Revision'] = 'revertToDate(' . $datetime . ') delete';
				return $Model->delete($Model->id);
			}
			return true;
		}
		$habtm = array();
		foreach ($Model->getAssociated('hasAndBelongsToMany') as $assocAlias) {
			if (isset($Model->ShadowModel->_schema[$assocAlias])) {
				$habtm[] = $assocAlias;
			}
		}
		$liveData = $Model->find('first', array('contain' => $habtm, 'conditions' => array($Model->alias . '.' . $Model->
					primaryKey => $Model->id)));

		$Model->logableAction['Revision'] = 'revertToDate(' . $datetime . ') add';
		if ($liveData) {
			$Model->logableAction['Revision'] = 'revertToDate(' . $datetime . ') edit';
			foreach ($Model->getAssociated('hasAndBelongsToMany') as $assocAlias) {
				if (isset($Model->ShadowModel->_schema[$assocAlias])) {
					$ids = Set::extract($liveData, '/' . $assocAlias . '/' . $Model->$assocAlias->primaryKey);
					if (empty($ids) || is_string($ids)) {
						$liveData[$Model->alias][$assocAlias] = '';
					} else {
						$liveData[$Model->alias][$assocAlias] = implode(',', $ids);
					}
					$data[$assocAlias][$assocAlias] = explode(',', $data[$Model->alias][$assocAlias]);
				}
				unset($liveData[$assocAlias]);
			}

			$changeDetected = false;
			foreach ($liveData[$Model->alias] as $key => $value) {
				if (isset($data[$Model->alias][$key])) {
					$oldValue = $data[$Model->alias][$key];
				} else {
					$oldValue = '';
				}
				if ($value != $oldValue) {
					$changeDetected = true;
				}
			}

			if (!$changeDetected) {
				return true;
			}
		}

		$auto = $this->settings[$Model->alias]['auto'];
		$this->settings[$Model->alias]['auto'] = false;
		$Model->ShadowModel->create($data, true);
		$Model->ShadowModel->set('version_created', date('Y-m-d H:i:s'));
		$Model->ShadowModel->save();
		$Model->versionId = $Model->ShadowModel->id;
		$success = (bool)$Model->save($data);
		$this->settings[$Model->alias]['auto'] = $auto;
		return $success;
	}

	/**
	 * Returns a comeplete list of revisions for the current Model->id.
	 * The options array may include Model::find parameters to narrow down result
	 * Alias for shadow('all', array('conditions'=>array($Model->primaryKey => $Model->id)));
	 *
	 * @example $this->Post->id = 4; $history = $this->Post->revisions();
	 * @example $this->Post->id = 4; $today = $this->Post->revisions(array('conditions'=>array('version_create >'=>'2008-12-10')));
	 * @param object $Model
	 * @param array $options
	 * @param boolean $includeCurrent If true will include last saved (live) data
	 * @return array
	 */
	public function revisions(Model $Model, $options = array(), $includeCurrent = false) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return array();
		}
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return array();
		}
		if (isset($options['conditions'])) {
			$options['conditions'] = array_merge($options['conditions'], array($Model->alias . '.' . $Model->primaryKey => $Model->id));
		} else {
			$options['conditions'] = array($Model->alias . '.' . $Model->primaryKey => $Model->id);
		}
		if (!$includeCurrent) {
			$current = $this->newest($Model, array('fields' => array($Model->alias . '.version_id', $Model->primaryKey)));
			$options['conditions'][$Model->alias . '.version_id !='] = $current[$Model->alias]['version_id'];
		}
		return $Model->ShadowModel->find('all', $options);
	}

	/**
	 * Undoes an delete by saving the last revision to the Model
	 * Will return false if this Model->id exist in the live table.
	 * Calls Model::beforeUndelete and Model::afterUndelete
	 *
	 * @example $this->Post->id = 7; $this->Post->undelete();
	 * @param object $Model
	 * @return boolean Success
	 */
	public function undelete(Model $Model) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return null;
		}
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return false;
		}
		if ($Model->find('count', array('conditions' => array($Model->primaryKey => $Model->id), 'recursive' => -1)) > 0) {
			return false;
		}
		$data = $this->newest($Model);
		if (!$data) {
			return false;
		}
		$beforeUndeleteSuccess = true;
		if (method_exists($Model, 'beforeUndelete')) {
			$beforeUndeleteSuccess = $Model->beforeUndelete();
		}
		if (!$beforeUndeleteSuccess) {
			return false;
		}
		$modelId = $data[$Model->alias][$Model->primaryKey];
		unset($data[$Model->alias][$Model->ShadowModel->primaryKey]);
		$Model->create($data, true);
		$autoSetting = $this->settings[$Model->alias]['auto'];
		$this->settings[$Model->alias]['auto'] = false;
		$saveSuccess = $Model->save();
		$this->settings[$Model->alias]['auto'] = $autoSetting;
		if (!$saveSuccess) {
			return false;
		}
		$Model->updateAll(
			array($Model->primaryKey => $modelId),
			array($Model->primaryKey => $Model->id)
		);
		$Model->id = $modelId;
		$Model->createRevision();
		$afterUndeleteSuccess = true;
		if (method_exists($Model, 'afterUndelete')) {
			$afterUndeleteSuccess = $Model->afterUndelete();
		}
		return $afterUndeleteSuccess;
	}

	/**
	 * Update to previous revision
	 *
	 * @example $this->Post->id = 2; $this->Post->undo();
	 * @param object $Model
	 * @return boolean Success
	 */
	public function undo(Model $Model) {
		if (!$Model->id) {
			trigger_error('RevisionBehavior: Model::id must be set', E_USER_WARNING);
			return null;
		}
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return false;
		}
		$data = $this->previous($Model);
		if (!$data) {
			$Model->logableAction['Revision'] = 'undo add';
			$Model->delete($Model->id);
			return false;
		}
		foreach ($Model->getAssociated('hasAndBelongsToMany') as $assocAlias) {
			if (isset($Model->ShadowModel->_schema[$assocAlias])) {
				$data[$assocAlias][$assocAlias] = explode(',', $data[$Model->alias][$assocAlias]);
			}
		}
		$Model->logableAction['Revision'] = 'undo changes';
		return (bool)$Model->save($data);
	}

	/**
	 * Calls create revision for all rows matching primary key list of $idlist
	 *
	 * @example $this->Model->updateRevisions(array(1,2,3));
	 * @param object $Model
	 * @param array $idlist
	 * @return void
	 */
	public function updateRevisions(Model $Model, $idlist = array()) {
		if (!$Model->ShadowModel) {
			trigger_error('RevisionBehavior: ShadowModel doesnt exist.', E_USER_WARNING);
			return;
		}
		foreach ($idlist as $id) {
			$Model->id = $id;
			$Model->createRevision();
		}
	}

	/**
	 * Causes revision for habtm associated models if that model does version control
	 * on their relationship. BeforeDelete identifies the related models that will need
	 * to do the revision update in afterDelete. Uses
	 *
	 * @param unknown_type $Model
	 * @return void
	 */
	public function afterDelete(Model $Model) {
		if ($this->settings[$Model->alias]['auto'] === false) {
			return;
		}
		if (!$Model->ShadowModel) {
			return;
		}
		if (isset($this->deleteUpdates[$Model->alias]) && !empty($this->deleteUpdates[$Model->alias])) {
			foreach ($this->deleteUpdates[$Model->alias] as $assocAlias => $assocIds) {
				$Model->{$assocAlias}->updateRevisions($assocIds);
			}
			unset($this->deleteUpdates[$Model->alias]);
		}
	}

	/**
	 * Will create a new revision if changes have been made in the models non-ignore fields.
	 * Also deletes oldest revision if limit is (active and) reached.
	 *
	 * @param object $Model
	 * @param boolean $created
	 * @return boolean Success
	 */
	public function afterSave(Model $Model, $created, $options = array()) {
		if ($this->settings[$Model->alias]['auto'] === false) {
			return true;
		}
		if (!$Model->ShadowModel) {
			return true;
		}
		if ($created) {
			$Model->ShadowModel->create($Model->data, true);
			$Model->ShadowModel->set($Model->primaryKey, $Model->id);
			$Model->ShadowModel->set('version_created', date('Y-m-d H:i:s'));
			foreach ($Model->data as $alias => $aliasData) {
				if (isset($Model->ShadowModel->_schema[$alias])) {
					if (isset($aliasData[$alias]) && !empty($aliasData[$alias])) {
						$Model->ShadowModel->set($alias, implode(',', $aliasData[$alias]));
					}
				}
			}
			$success = (bool)$Model->ShadowModel->save();
			$Model->versionId = $Model->ShadowModel->id;
			return $success;
		}

		$habtm = array();
		foreach ($Model->getAssociated('hasAndBelongsToMany') as $assocAlias) {
			if (isset($Model->ShadowModel->_schema[$assocAlias])) {
				$habtm[] = $assocAlias;
			}
		}
		$data = $Model->find('first', array('contain' => $habtm, 'conditions' => array($Model->alias . '.' . $Model->primaryKey =>
					$Model->id)));

		$changeDetected = false;
		foreach ($data[$Model->alias] as $key => $value) {
			if (isset($data[$Model->alias][$Model->primaryKey]) && !empty($this->_oldData[$Model->alias]) && isset($this->_oldData[$Model->
				alias][$Model->alias][$key])) {

				$oldValue = $this->_oldData[$Model->alias][$Model->alias][$key];
			} else {
				$oldValue = '';
			}
			if ($value != $oldValue && !in_array($key, $this->settings[$Model->alias]['ignore'])) {
				$changeDetected = true;
			}
		}
		$Model->ShadowModel->create($data);
		if (!empty($habtm)) {
			foreach ($habtm as $assocAlias) {
				if (in_array($assocAlias, $this->settings[$Model->alias]['ignore'])) {
					continue;
				}
				$oldIds = Set::extract($this->_oldData[$Model->alias], $assocAlias . '.{n}.id');
				if (!isset($Model->data[$assocAlias])) {
					$Model->ShadowModel->set($assocAlias, implode(',', $oldIds));
					continue;
				}
				$currentIds = Set::extract($data, $assocAlias . '.{n}.id');
				$idChanges = array_diff($currentIds, $oldIds);
				if (!empty($idChanges)) {
					$Model->ShadowModel->set($assocAlias, implode(',', $currentIds));
					$changeDetected = true;
				} else {
					$Model->ShadowModel->set($assocAlias, implode(',', $oldIds));
				}
			}
		}
		unset($this->_oldData[$Model->alias]);
		if (!$changeDetected) {
			return true;
		}
		$Model->ShadowModel->set('version_created', date('Y-m-d H:i:s'));
		$Model->ShadowModel->save();
		$Model->versionId = $Model->ShadowModel->id;
		if (is_numeric($this->settings[$Model->alias]['limit'])) {
			$conditions = array('conditions' => array($Model->alias . '.' . $Model->primaryKey => $Model->id));
			$count = $Model->ShadowModel->find('count', $conditions);
			if ($count > $this->settings[$Model->alias]['limit']) {
				$conditions['order'] = $Model->alias . '.version_created ASC, ' . $Model->alias . '.version_id ASC';
				$oldest = $Model->ShadowModel->find('first', $conditions);
				$Model->ShadowModel->id = null;
				$Model->ShadowModel->delete($oldest[$Model->alias][$Model->ShadowModel->primaryKey]);
			}
		}
		return true;
	}

	/**
	 * Causes revision for habtm associated models if that model does version control
	 * on their relationship. BeforeDelete identifies the related models that will need
	 * to do the revision update in afterDelete.
	 *
	 * @param object $Model
	 * @return boolean Success
	 */
	public function beforeDelete(Model $Model, $cascade = true) {
		if ($this->settings[$Model->alias]['auto'] === false) {
			return true;
		}
		if (!$Model->ShadowModel) {
			return true;
		}
		foreach ($Model->hasAndBelongsToMany as $assocAlias => $a) {
			if (isset($Model->{$assocAlias}->ShadowModel->_schema[$Model->alias])) {
				$joins = $Model->{$a['with']}->find('all', array('recursive' => -1, 'conditions' => array($a['foreignKey'] => $Model->
							id)));
				$this->deleteUpdates[$Model->alias][$assocAlias] = Set::extract($joins, '/' . $a['with'] . '/' . $a['associationForeignKey']);
			}
		}
		return true;
	}

	/**
	 * Revision uses the beforeSave callback to remember the old data for comparison in afterSave
	 *
	 * @param object $Model
	 * @return boolean Success
	 */
	public function beforeSave(Model $Model, $options = array()) {
		if ($this->settings[$Model->alias]['auto'] === false) {
			return true;
		}
		if (!$Model->ShadowModel) {
			return true;
		}
		$Model->ShadowModel->create();
		if (!isset($Model->data[$Model->alias][$Model->primaryKey]) && !$Model->id) {
			return true;
		}

		$habtm = array();
		foreach ($Model->getAssociated('hasAndBelongsToMany') as $assocAlias) {
			if (isset($Model->ShadowModel->_schema[$assocAlias])) {
				$habtm[] = $assocAlias;
			}
		}
		$this->_oldData[$Model->alias] = $Model->find('first', array(
				'contain' => $habtm, 'conditions' => array($Model->alias . '.' . $Model->primaryKey => $Model->id)));

		return true;
	}

	/**
	 * Returns a generic model that maps to the current $Model's shadow table.
	 *
	 * @param object $Model
	 * @return boolean Success
	 */
	protected function createShadowModel(Model $Model) {
		if ($this->settings[$Model->alias]['useDbConfig'] === null) {
			$dbConfig = $Model->useDbConfig;
		} else {
			$dbConfig = $this->settings[$Model->alias]['useDbConfig'];
		}
		$db = ConnectionManager::getDataSource($dbConfig);
		if ($Model->useTable) {
			$shadowTable = $Model->useTable;
		} else {
			$shadowTable = Inflector::tableize($Model->name);
		}
		$shadowTable = $shadowTable . $this->revisionSuffix;
		$prefix = $Model->tablePrefix ? $Model->tablePrefix : $db->config['prefix'];
		$fullTableName = $prefix . $shadowTable;

		$existingTables = $db->listSources();
		if (!in_array($fullTableName, $existingTables)) {
			$Model->ShadowModel = false;
			return false;
		}
		$useShadowModel = $this->settings[$Model->alias]['model'];
		//TOOD: use App::uses() and ClassRegistry::init() - also allow Plugin.Model syntax
		if (is_string($useShadowModel) && App::import('Model', $useShadowModel)) {
			$Model->ShadowModel = new $useShadowModel(false, $shadowTable, $dbConfig);
		} else {
			$Model->ShadowModel = new Model(false, $shadowTable, $dbConfig);
		}
		if ($Model->tablePrefix) {
			$Model->ShadowModel->tablePrefix = $Model->tablePrefix;
		}
		$Model->ShadowModel->alias = $Model->alias; // . 'Shadow';
		$Model->ShadowModel->primaryKey = 'version_id';
		$Model->ShadowModel->order = 'version_created DESC, version_id DESC';
		return true;
	}

}
