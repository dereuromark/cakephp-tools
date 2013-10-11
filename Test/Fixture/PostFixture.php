<?php
/**
 * PostFixture
 *
 */
class PostFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'),
		'title' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'slug' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'markup' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 2),
		'content' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'foreign_key' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'model' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'created_by' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'modified_by' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
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
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 1,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 1,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
		array(
			'id' => 2,
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 2,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 2,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
		array(
			'id' => 3,
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 3,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 3,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
		array(
			'id' => 4,
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 4,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 4,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
		array(
			'id' => 5,
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 5,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 5,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
		array(
			'id' => 6,
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 6,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 6,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
		array(
			'id' => 7,
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 7,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 7,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
		array(
			'id' => 8,
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 8,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 8,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
		array(
			'id' => 9,
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 9,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 9,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
		array(
			'id' => 10,
			'title' => 'Lorem ipsum dolor sit amet',
			'slug' => 'Lorem ipsum dolor sit amet',
			'markup' => 10,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'foreign_key' => 10,
			'model' => 'Lorem ipsum dolor sit amet',
			'created_by' => 'Lorem ipsum dolor sit amet',
			'modified_by' => 'Lorem ipsum dolor sit amet',
			'created' => '2012-02-05 15:23:31',
			'modified' => '2012-02-05 15:23:31'
		),
	);
}
