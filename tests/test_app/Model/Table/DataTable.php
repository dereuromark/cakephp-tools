<?php

namespace App\Model\Table;

use Cake\Database\Schema\TableSchemaInterface;
use Tools\Model\Table\Table;

class DataTable extends Table {

	/**
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 *
	 * @return \Cake\Database\Schema\TableSchemaInterface
	 */
	protected function _initializeSchema(TableSchemaInterface $schema): TableSchemaInterface {
		$schema->setColumnType('data_json', 'json');
		$schema->setColumnType('data_array', 'array');

		return $schema;
	}

}
