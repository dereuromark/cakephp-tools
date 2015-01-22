<?php
/**
 * User Fixture
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP Datasources v 0.3
 * @license http://opensource.org/licenses/mit-license.php MIT
 */

/**
 * User Fixture
 *
 */
class ArrayUserFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'born_id' => ['type' => 'integer', 'null' => true],
		'name' => ['type' => 'string', 'null' => false]
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		['born_id' => 1, 'name' => 'User 1'],
		['born_id' => 2, 'name' => 'User 2'],
		['born_id' => 1, 'name' => 'User 3'],
		['born_id' => 3, 'name' => 'User 4']
	];

}
