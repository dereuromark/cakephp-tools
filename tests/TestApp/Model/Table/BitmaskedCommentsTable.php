<?php

namespace TestApp\Model\Table;

use Tools\Model\Table\Table;

class BitmaskedCommentsTable extends Table {

	/**
	 * @var array
	 */
	public $validate = [
		'status' => [
			'notBlank' => [
				'rule' => 'notBlank',
				'last' => true
			]
		]
	];

}
