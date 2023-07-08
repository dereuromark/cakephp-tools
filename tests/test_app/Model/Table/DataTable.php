<?php

namespace TestApp\Model\Table;

use Tools\Model\Table\Table;

class DataTable extends Table {

	public function initialize(array $config): void
	{
		parent::initialize($config);

		$schema = $this->getSchema();
		$schema->setColumnType('data_json', 'json');
	}

}
