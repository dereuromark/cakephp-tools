<?php

namespace TestApp\Model\Table;

use Cake\ORM\Entity;
use Tools\Model\Table\Table;

class ResetCommentsTable extends Table {

	public function initialize(array $config) {
		$this->displayField('comment');
		parent::initialize($config);
	}

	public function customCallback(Entity $record, &$updateFields) {
		$record->comment .= ' xyz';
		$fields[] = 'some_other_field';
		return $record;
	}

	public function customObjectCallback(Entity $record, &$updateFields) {
		$record['comment'] .= ' xxx';
		$updateFields[] = 'some_other_field';
		return $record;
	}

	public static function customStaticCallback(Entity $record, &$updateFields) {
		$record['comment'] .= ' yyy';
		$updateFields[] = 'some_other_field';
		return $record;
	}

	public static function fieldsCallback(Entity $record, &$updateFields) {
		$record['comment'] = 'foo';
		return $record;
	}

	public static function fieldsCallbackAuto(Entity $record, &$updateFields) {
		$record['comment'] = 'bar';
		$updateFields[] = 'comment';
		return $record;
	}

}
