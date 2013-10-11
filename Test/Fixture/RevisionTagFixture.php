<?php
class RevisionTagFixture extends CakeTestFixture {

	public $fields = array(
			'id' => array(
					'type' => 'integer',
					'null' => false,
					'default' => null,
					'key' => 'primary'),
			'title' => array('type' => 'string', 'null' => false, 'default' => null),
			'indexes' => array('PRIMARY' => array('column' => 'id')));

	public $records = array(
		array(
			'id' => 1,
			'title' => 'Fun',
		),
		array(
			'id' => 2,
			'title' => 'Hard'
		),
		array(
			'id' => 3,
			'title' => 'Trick'
		),
		array(
			'id' => 4,
			'title' => 'News'
		),
	);
}
