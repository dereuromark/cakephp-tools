<?php

namespace Dereuromark\Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class SluggedArticleFixture
 *
 */
class SluggedArticleFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => ''],
		'slug' => ['type' => 'string', 'length' => 245, 'null' => false, 'default' => ''],
		'long_title' => array('type' => 'string', 'null' => false, 'default' => ''),
		'long_slug' => array('type' => 'string', 'null' => false, 'default' => ''),
		'section' => ['type' => 'integer', 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array(
			'title' => 'Foo',
			'slug' => 'foo',
			'long_title' => 'Foo Bar',
			'long_slug' => 'foo-bar',
			'section' => null,
		),
	);

}
