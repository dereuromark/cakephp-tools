<?php
/**
 * PHP 5
 *
 * @author Mark Scherer
 * @author Marc Würth
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link https://github.com/dereuromark/tools
 */

/**
 * Fixture for WhoDidIt
 */
class WhoDidItPlayerFixture extends CakeTestFixture {

	/**
	 * Fields property
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false),
		'created' => 'datetime',
		'created_by' => array('type' => 'integer', 'null' => true),
		'modified' => 'datetime',
		'modified_by' => array('type' => 'integer', 'null' => true)
	);

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = array(
		array('name' => 'mark', 'created' => '2007-03-17 01:16:23'),
		array('name' => 'jack', 'created' => '2007-03-17 01:18:23'),
		array('name' => 'larry', 'created' => '2007-03-17 01:20:23'),
		array('name' => 'jose', 'created' => '2007-03-17 01:22:23'),
	);

}
