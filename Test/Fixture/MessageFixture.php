<?php
/**
 * Short description for message_fixture.php
 *
 * Long description for message_fixture.php
 *
 * PHP version 5
 *
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
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
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
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'random' => array('type' => 'integer', 'null' => false),
		'name' => array('type' => 'string', 'null' => false),
		'slug' => array('type' => 'string', 'null' => true),
		'section' => array('type' => 'integer', 'null' => true),
	);

	/**
	 * Records property
	 *
	 * The records are created out of sequence so that theirs id are not sequncial.
	 * The order field values are used only in the list behavior test
	 *
	 * @var array
	 */
	public $records = array(
		array('random' => 1, 'name' => 'First'),
		array('random' => 10, 'name' => 'Tenth'),
		array('random' => 4, 'name' => 'Fourth'),
		array('random' => 8, 'name' => 'Eigth'),
		array('random' => 5, 'name' => 'Fifth'),
		array('random' => 7, 'name' => 'Seventh'),
		array('random' => 3, 'name' => 'Third'),
		array('random' => 9, 'name' => 'Ninth'),
		array('random' => 2, 'name' => 'Second'),
		array('random' => 6, 'name' => 'Sixth'),
	);

}
