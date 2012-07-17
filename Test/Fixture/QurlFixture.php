<?php
/**
 * QurlFixture
 *
 */
class QurlFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'),
		'key' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 60, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'url' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'for external urls', 'charset' => 'utf8'),
		'title' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'content' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'can transport some information', 'charset' => 'utf8'),
		'note' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'internal', 'charset' => 'utf8'),
		'comment' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'for the user as flash message', 'charset' => 'utf8'),
		'used' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'last_used' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => '1'),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
		),
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
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 1,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
		array(
			'id' => 2,
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 2,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
		array(
			'id' => 3,
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 3,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
		array(
			'id' => 4,
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 4,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
		array(
			'id' => 5,
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 5,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
		array(
			'id' => 6,
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 6,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
		array(
			'id' => 7,
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 7,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
		array(
			'id' => 8,
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 8,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
		array(
			'id' => 9,
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 9,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
		array(
			'id' => 10,
			'key' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'note' => 'Lorem ipsum dolor sit amet',
			'comment' => 'Lorem ipsum dolor sit amet',
			'used' => 10,
			'last_used' => '2012-05-22 13:50:19',
			'active' => 1,
			'created' => '2012-05-22 13:50:19'
		),
	);
}
