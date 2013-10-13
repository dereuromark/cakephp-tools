<?php
class LogableCommentFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'content' => array('type' => 'string', 'length' => 255, 'null' => false),
	);

	public $records = array(
		array('id' => 1, 'content' => 'I like it'),
		array('id' => 2, 'content' => 'I don\'t'),
		array('id' => 3, 'content' => 'I LOVE it!'),
		array('id' => 4, 'content' => 'I hate it'),

	);
}
