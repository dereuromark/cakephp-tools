<?php
/* Image Fixture generated on: 2011-11-20 21:59:04 : 1321822744 */

/**
 * ImageFixture
 *
 */
class ImageFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'name' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 100, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'description' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'is_master' => array('type' => 'boolean', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'filename' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'model' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 30, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'foreign_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null, 'collate' => null, 'comment' => ''),
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
			'id' => '4e0db616-1378-475f-8cd2-0c707cb063f2',
			'name' => 'Burger',
			'description' => '',
			'is_master' => 1,
			'filename' => 'burger.jpg',
			'model' => 'Meal',
			'foreign_id' => '112',
			'created' => '2011-07-01 13:57:10',
			'modified' => '2011-07-01 13:57:10'
		),
		array(
			'id' => '4e0db616-bf60-4418-9c39-0c707cb063f2',
			'name' => 'Chili Con Carne',
			'description' => '',
			'is_master' => 1,
			'filename' => 'chili_con_carne.jpg',
			'model' => 'Meal',
			'foreign_id' => '108',
			'created' => '2011-07-01 13:57:10',
			'modified' => '2011-07-01 13:57:10'
		),
		array(
			'id' => '4e0db616-7b0c-487a-bd8c-0c707cb063f2',
			'name' => 'Currywurst',
			'description' => '',
			'is_master' => 1,
			'filename' => 'currywurst.jpg',
			'model' => 'Meal',
			'foreign_id' => '63',
			'created' => '2011-07-01 13:57:10',
			'modified' => '2011-07-01 13:57:10'
		),
		array(
			'id' => '4e0db616-db7c-47b6-a4ae-0c707cb063f2',
			'name' => 'Dampfnudeln',
			'description' => '',
			'is_master' => 1,
			'filename' => 'dampfnudeln.jpg',
			'model' => 'Meal',
			'foreign_id' => '31',
			'created' => '2011-07-01 13:57:10',
			'modified' => '2011-07-01 13:57:10'
		),
		array(
			'id' => '4e0db617-0a40-4607-94c5-0c707cb063f2',
			'name' => 'Enchilada',
			'description' => '',
			'is_master' => 1,
			'filename' => 'enchilada.jpg',
			'model' => 'Meal',
			'foreign_id' => '109',
			'created' => '2011-07-01 13:57:11',
			'modified' => '2011-07-01 13:57:11'
		),
		array(
			'id' => '4e0db617-e104-476e-b264-0c707cb063f2',
			'name' => 'Entenfleisch',
			'description' => '',
			'is_master' => 1,
			'filename' => 'entenfleisch-2.jpg',
			'model' => 'Meal',
			'foreign_id' => '52',
			'created' => '2011-07-01 13:57:11',
			'modified' => '2011-07-01 13:57:11'
		),
		array(
			'id' => '4e0db617-b7ac-4e25-99a8-0c707cb063f2',
			'name' => 'Griechischer Salat',
			'description' => '',
			'is_master' => 1,
			'filename' => 'griechischer_salat.jpg',
			'model' => 'Meal',
			'foreign_id' => '99',
			'created' => '2011-07-01 13:57:11',
			'modified' => '2011-07-01 13:57:11'
		),
		array(
			'id' => '4e0db617-17e4-4755-a2c1-0c707cb063f2',
			'name' => 'Gyros',
			'description' => '',
			'is_master' => 1,
			'filename' => 'gyros.jpg',
			'model' => 'Meal',
			'foreign_id' => '81',
			'created' => '2011-07-01 13:57:11',
			'modified' => '2011-07-01 13:57:11'
		),
		array(
			'id' => '4e0db617-88bc-4e5c-af7c-0c707cb063f2',
			'name' => 'Hähnchenschnitzel',
			'description' => '',
			'is_master' => 1,
			'filename' => 'haehnchenschnitzel-2.jpg',
			'model' => 'Meal',
			'foreign_id' => '33',
			'created' => '2011-07-01 13:57:11',
			'modified' => '2011-07-01 13:57:11'
		),
		array(
			'id' => '4e0db617-f5a0-450a-ab52-0c707cb063f2',
			'name' => 'Hühnerfleisch',
			'description' => '',
			'is_master' => 1,
			'filename' => 'huehnerfleisch-2.jpg',
			'model' => 'Meal',
			'foreign_id' => '72',
			'created' => '2011-07-01 13:57:11',
			'modified' => '2011-07-01 13:57:11'
		),
	);
}
