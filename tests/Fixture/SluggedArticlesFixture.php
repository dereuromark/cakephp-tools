<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SluggedArticlesFixture extends TestFixture {

	/**
	 * fields property
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => ''],
		'slug' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => ''],
		'long_title' => ['type' => 'string', 'null' => false, 'default' => ''],
		'long_slug' => ['type' => 'string', 'null' => false, 'default' => ''],
		'section' => ['type' => 'integer', 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * records property
	 *
	 * @var array
	 */
	public $records = [
		[
			'title' => 'Foo',
			'slug' => 'foo',
			'long_title' => 'Foo Bar',
			'long_slug' => 'foo-bar',
			'section' => null,
		],
	];

}
