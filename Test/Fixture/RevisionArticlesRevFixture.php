<?php
class RevisionArticlesRevFixture extends CakeTestFixture {
	var $name = 'RevisionArticlesRev';
	var $fields = array(
			'version_id' => array('type' => 'integer','null' => false,'default' => NULL,'key' => 'primary'), 
			'version_created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
			'id' => array('type' => 'integer','null' => false,'default' => NULL), 
			'user_id' => array('type' => 'integer', 'null' => false, 'default' => NULL), 
			'parent_id' => array('type' => 'integer','null' => true,'default' => NULL), 
			'title' => array('type' => 'string', 'null' => false, 'default' => NULL), 
			'content' => array('type' => 'text', 'null' => false, 'default' => NULL),
			'indexes' => array('PRIMARY' => array('column' => 'version_id')));
	var $records = array(
	);
}
?>