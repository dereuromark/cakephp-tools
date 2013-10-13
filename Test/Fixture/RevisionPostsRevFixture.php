<?php
class RevisionPostsRevFixture extends CakeTestFixture {

	public $fields = array(
			'version_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
			'version_created' => array('type' => 'datetime', 'null' => false, 'default' => null),
			'id' => array('type' => 'integer', 'null' => false, 'default' => null),
			'title' => array('type' => 'string', 'null' => false, 'default' => null),
			'content' => array('type' => 'text', 'null' => false, 'default' => null));

	public $records = array(
		array(
			'version_id' => 1,
			'version_created' => '2008-12-08 11:38:53',
			'id' => 1,
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'
		),
		array(
			'version_id' => 2,
			'version_created' => '2008-12-08 11:38:54',
			'id' => 2,
			'title' => 'Post 2',
			'content' => 'Lorem ipsum dolor sit.'
		),
		array(
			'version_id' => 3,
			'version_created' => '2008-12-08 11:38:55',
			'id' => 3,
			'title' => 'Post 3',
			'content' => 'Lorem ipsum dolor sit.',
		),
	);
}
