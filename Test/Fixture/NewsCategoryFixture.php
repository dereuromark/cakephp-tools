<?php

class NewsCategoryFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255, 'null' => false)
	);

	public $records = array(
		array('id' => 1, 'name' => 'Development'),
		array('id' => 2, 'name' => 'Programming'),
		array('id' => 3, 'name' => 'Scripting'),
	);
}
