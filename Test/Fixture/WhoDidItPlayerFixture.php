<?php
/**
 * PHP 5
 *
 * @author Mark Scherer
 * @author Marc Würth
 * @license http://opensource.org/licenses/mit-license.php MIT
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
	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'name' => ['type' => 'string', 'null' => false],
		'created' => 'datetime',
		'created_by' => ['type' => 'integer', 'null' => true],
		'modified' => 'datetime',
		'modified_by' => ['type' => 'integer', 'null' => true]
	];

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = [
		['name' => 'mark', 'created' => '2007-03-17 01:16:23'],
		['name' => 'jack', 'created' => '2007-03-17 01:18:23'],
		['name' => 'larry', 'created' => '2007-03-17 01:20:23'],
		['name' => 'jose', 'created' => '2007-03-17 01:22:23'],
	];

}
