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
	public $fields = [
		'id' => ['type' => 'integer', 'null' => false, 'length' => 10, 'key' => 'primary', 'collate' => null],
		'title' => ['type' => 'string', 'null' => false, 'length' => 255],
		'slug' => ['type' => 'string', 'null' => false, 'length' => 245],
		'long_title' => ['type' => 'string', 'null' => false],
		'long_slug' => ['type' => 'string', 'null' => false],
		'section' => ['type' => 'integer', 'null' => true],
		'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => 1]],
		'tableParameters' => []
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => '1',
			'title' => 'Foo',
			'slug' => 'foo',
			'long_title' => 'Foo Bar',
			'long_slug' => 'foo-bar',
			'section' => 1,
		],
	];

}
