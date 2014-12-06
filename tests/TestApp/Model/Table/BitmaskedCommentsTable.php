<?php

namespace TestApp\Model\Table;

use Tools\Model\Table\Table;

class BitmaskedCommentsTable extends Table {

	public $validate = array(
		'status' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			)
		)
	);

}
