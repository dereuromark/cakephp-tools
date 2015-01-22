<?php

namespace TestApp\Model\Table;

use Tools\Model\Table\Table;

class BitmaskedCommentsTable extends Table {

	public $validate = [
		'status' => [
			'notEmpty' => [
				'rule' => 'notEmpty',
				'last' => true
			]
		]
	];

}
