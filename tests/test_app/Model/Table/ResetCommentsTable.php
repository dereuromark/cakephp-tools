<?php

namespace TestApp\Model\Table;

use Cake\ORM\Entity;
use Tools\Model\Table\Table;

class ResetCommentsTable extends Table {

	/**
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->setDisplayField('comment');
		parent::initialize($config);
	}

	/**
	 * @param \Cake\ORM\Entity $record
	 * @param array $updateFields
	 * @return \Cake\ORM\Entity
	 */
	public function customCallback(Entity $record, array &$updateFields) {
		$record->comment .= ' xyz';
		$fields[] = 'some_other_field';
		return $record;
	}

	/**
	 * @param \Cake\ORM\Entity $record
	 * @param array $updateFields
	 * @return \Cake\ORM\Entity
	 */
	public function customObjectCallback(Entity $record, array &$updateFields) {
		$record['comment'] .= ' xxx';
		$updateFields[] = 'some_other_field';
		return $record;
	}

	/**
	 * @param \Cake\ORM\Entity $record
	 * @param array $updateFields
	 * @return \Cake\ORM\Entity
	 */
	public static function customStaticCallback(Entity $record, array &$updateFields) {
		$record['comment'] .= ' yyy';
		$updateFields[] = 'some_other_field';
		return $record;
	}

	/**
	 * @param \Cake\ORM\Entity $record
	 * @param array $updateFields
	 * @return \Cake\ORM\Entity
	 */
	public static function fieldsCallback(Entity $record, array &$updateFields) {
		$record['comment'] = 'foo';
		return $record;
	}

	/**
	 * @param \Cake\ORM\Entity $record
	 * @param array $updateFields
	 * @return \Cake\ORM\Entity
	 */
	public static function fieldsCallbackAuto(Entity $record, array &$updateFields) {
		$record['comment'] = 'bar';
		$updateFields[] = 'comment';
		return $record;
	}

}
