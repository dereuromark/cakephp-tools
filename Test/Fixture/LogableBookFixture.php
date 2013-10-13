<?php
class LogableBookFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'length' => 255, 'null' => false),
		'weight' => array('type' => 'integer', 'null' => false)
	);

	public $records = array(
		array('id' => 3, 'title' => 'Sixth Book', 'weight' => 6 ),
		array('id' => 6, 'title' => 'Fifth Book', 'weight' => 5 ),
		array('id' => 2, 'title' => 'First Book', 'weight' => 1 ),
		array('id' => 1, 'title' => 'Second Book', 'weight' => 2 ),
		array('id' => 4, 'title' => 'Third Book', 'weight' => 3 ),
		array('id' => 5, 'title' => 'Fourth Book', 'weight' => 4 )
	);
}
