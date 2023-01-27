<?php

namespace TestApp\Model\Table;

use Tools\Model\Table\Table;

class DataTable extends Table {

  /**
   * @param array $config
   * @return void
   */
	public function initialize(array $config): void {
		$schema = $this->getSchema();
		$schema->setColumnType('data_json', 'json');
		$schema->setColumnType('data_array', 'array');
	}

}
