<?php

namespace TestApp\Model\Table;

use Tools\Model\Table\Table;

class ToolsUsersTable extends Table {

	/**
	 * 2.x way of declaring order - here to test shims
	 *
	 * @var array
	 */
	public $order = ['name' => 'ASC'];

}
