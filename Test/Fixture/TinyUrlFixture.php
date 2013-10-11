<?php
/**
 * TinyUrlFixture
 *
 */
class TinyUrlFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''),
		'user_id' => array('type' => 'string', 'null' => false, 'length' => 36, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'model' => array('type' => 'string', 'null' => false, 'length' => 30, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'foreign_id' => array('type' => 'string', 'null' => false, 'length' => 36, 'key' => 'index', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'url' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'for external urls', 'charset' => 'utf8'),
		'content' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'can transport some information', 'charset' => 'utf8'),
		'used' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'collate' => null, 'comment' => ''),
		'last_used' => array('type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'user_id' => array('column' => 'user_id', 'unique' => 0), 'foreign_id' => array('column' => 'foreign_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array(
			'id' => 1,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 1,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
		array(
			'id' => 2,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 2,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
		array(
			'id' => 3,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 3,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
		array(
			'id' => 4,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 4,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
		array(
			'id' => 5,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 5,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
		array(
			'id' => 6,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 6,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
		array(
			'id' => 7,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 7,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
		array(
			'id' => 8,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 8,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
		array(
			'id' => 9,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 9,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
		array(
			'id' => 10,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreign_id' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'used' => 10,
			'last_used' => '2011-12-15 00:35:33',
			'created' => '2011-12-15 00:35:33',
			'modified' => '2011-12-15 00:35:33'
		),
	);
}
