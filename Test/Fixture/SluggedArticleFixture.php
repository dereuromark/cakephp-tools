<?php

/**
 * Fixture to test SluggedBeavior
 */
class SluggedArticleFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'length' => 10, 'key' => 'primary', 'collate' => null),
		'title' => array('type' => 'string', 'null' => false, 'length' => 255),
		'slug' => array('type' => 'string', 'null' => false, 'length' => 245),
		'long_title' => array('type' => 'string', 'null' => false),
		'long_slug' => array('type' => 'string', 'null' => false),
		'section' => array('type' => 'integer', 'null' => true),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array()
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array(
			'id' => '1',
			'title' => 'Foo',
			'slug' => 'foo',
			'long_title' => 'Foo Bar',
			'long_slug' => 'foo-bar',
			'section' => 1,
		),
	);

}
