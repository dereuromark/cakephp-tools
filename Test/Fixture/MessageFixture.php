<?php
/**
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2008, Andy Dawson
 * @link www.ad7six.com
 * @since v 1.0
 * @modifiedBy $LastChangedBy$
 * @lastModified $Date$
 * @license http://opensource.org/licenses/mit-license.php MIT
 */

/**
 * MessageFixture class
 *
 */
class MessageFixture extends CakeTestFixture {

	/**
	 * Fields property
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'random' => ['type' => 'integer', 'null' => false],
		'name' => ['type' => 'string', 'null' => false],
		'slug' => ['type' => 'string', 'null' => true],
		'section' => ['type' => 'integer', 'null' => true],
	];

	/**
	 * Records property
	 *
	 * The records are created out of sequence so that theirs id are not sequncial.
	 * The order field values are used only in the list behavior test
	 *
	 * @var array
	 */
	public $records = [
		['random' => 1, 'name' => 'First'],
		['random' => 10, 'name' => 'Tenth'],
		['random' => 4, 'name' => 'Fourth'],
		['random' => 8, 'name' => 'Eigth'],
		['random' => 5, 'name' => 'Fifth'],
		['random' => 7, 'name' => 'Seventh'],
		['random' => 3, 'name' => 'Third'],
		['random' => 9, 'name' => 'Ninth'],
		['random' => 2, 'name' => 'Second'],
		['random' => 6, 'name' => 'Sixth'],
	];

}
