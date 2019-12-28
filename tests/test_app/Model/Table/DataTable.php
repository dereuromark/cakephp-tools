<?php

namespace TestApp\Model\Table;

use Cake\Database\Schema\TableSchema;
use Tools\Model\Table\Table;

class DataTable extends Table {

	/**
	 * @param \Cake\Database\Schema\TableSchema $schema
	 *
	 * @return \Cake\Database\Schema\TableSchema
	 */
	protected function _initializeSchema(TableSchema $schema) {
		$schema->setColumnType('data_json', 'json');
		$schema->setColumnType('data_array', 'array');

		return $schema;
	}

}
