<?php
/* PaymentMethod Fixture generated on: 2011-11-20 21:59:23 : 1321822763 */

/**
 * PaymentMethodFixture
 *
 */
class PaymentMethodFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''),
		'active' => array('type' => 'boolean', 'null' => true, 'default' => '0', 'collate' => null, 'comment' => ''),
		'set_rate' => array('type' => 'float', 'null' => false, 'default' => '0.00', 'length' => '6,2', 'collate' => null, 'comment' => 'fixed charge'),
		'rel_rate' => array('type' => 'float', 'null' => false, 'default' => '0.0000', 'length' => '5,4', 'collate' => null, 'comment' => 'relative to amount'),
		'sort' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'collate' => null, 'comment' => ''),
		'description' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'hint' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'details' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'duration' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'name' => array('type' => 'string', 'null' => false, 'length' => 250, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'alias' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 30, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'),
		'hook' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 30, 'collate' => 'utf8_unicode_ci', 'comment' => 'what function is supposed to be triggered', 'charset' => 'utf8'),
		'url' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => 'homepage', 'charset' => 'utf8'),
		'votes' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'collate' => null, 'comment' => ''),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''),
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
			'id' => '1',
			'active' => 0,
			'set_rate' => '0.35',
			'rel_rate' => '0.0000',
			'sort' => '0',
			'description' => 'Manuelle Bank-Überweisung via Online-Banking, Tele-Banking oder Scheck.',
			'hint' => 'Im Anschluss an die Bestellung bekommst du die Kontodaten, auf die dann das Geld überwiesen werden muss. Erst nach Bestätigung des Geldeingangs (1-2 Tage) werden die Downloads dann freigeschalten.',
			'details' => '',
			'duration' => '',
			'name' => 'Überweisung',
			'alias' => 'ueberweisung',
			'hook' => '',
			'url' => '',
			'votes' => '0',
			'created' => '2010-06-15 13:15:09',
			'modified' => '2011-09-29 23:37:29'
		),
		array(
			'id' => '2',
			'active' => 1,
			'set_rate' => '0.35',
			'rel_rate' => '0.0190',
			'sort' => '0',
			'description' => 'Sofort-Download',
			'hint' => 'Du wirst beim Absenden der Bestellung automatisch über eine verschlüsselte Verbindung zu {name} weitergeleitet, wo die Bezahlung abgewickelt wird. Danach landest du wieder hier im Shop und kannst deine Downloads direkt herunterladen.',
			'details' => '',
			'duration' => '',
			'name' => 'PayPal',
			'alias' => 'paypal',
			'hook' => '',
			'url' => '',
			'votes' => '0',
			'created' => '2010-06-15 13:17:01',
			'modified' => '2011-10-03 16:34:34'
		),
		array(
			'id' => '3',
			'active' => 0,
			'set_rate' => '0.35',
			'rel_rate' => '0.0190',
			'sort' => '0',
			'description' => 'Sofort-Download',
			'hint' => 'Du wirst beim Absenden der Bestellung automatisch über eine verschlüsselte Verbindung zu {name} weitergeleitet, wo die Bezahlung abgewickelt wird. Danach landest du wieder hier im Shop und kannst deine Downloads direkt herunterladen.',
			'details' => '',
			'duration' => '',
			'name' => 'ClickAndBuy',
			'alias' => '',
			'hook' => '',
			'url' => '',
			'votes' => '0',
			'created' => '2010-09-19 19:27:54',
			'modified' => '2010-09-19 20:28:08'
		),
		array(
			'id' => '4',
			'active' => 0,
			'set_rate' => '0.35',
			'rel_rate' => '0.0350',
			'sort' => '0',
			'description' => 'Mastercard, Visa, ...',
			'hint' => 'Die Abrechnung wird über Paypal abgewickelt. Du wirst nach der bestätigung über eine verschlüsselte Verbindung auf die Paypal-Seite geleitet und kannst dort deine Kartendaten sicher eingeben. nach erfolgter Bezahlung wirst du zum Shop zurückgeleitet und kannst direkt die Downloads herunterladen.',
			'details' => '',
			'duration' => '',
			'name' => 'Kreditkarte',
			'alias' => '',
			'hook' => '',
			'url' => '',
			'votes' => '1',
			'created' => '2010-09-19 20:00:22',
			'modified' => '2011-07-16 13:50:58'
		),
		array(
			'id' => '5',
			'active' => 0,
			'set_rate' => '0.00',
			'rel_rate' => '0.0000',
			'sort' => '0',
			'description' => '',
			'hint' => '',
			'details' => 'Kostenpflichtig für Verkäufer (13 Euro im Monat?)',
			'duration' => '',
			'name' => 'ipayment',
			'alias' => '',
			'hook' => '',
			'url' => '',
			'votes' => '2',
			'created' => '2010-09-19 20:06:20',
			'modified' => '2010-09-19 21:06:31'
		),
		array(
			'id' => '6',
			'active' => 0,
			'set_rate' => '0.00',
			'rel_rate' => '0.0000',
			'sort' => '0',
			'description' => '',
			'hint' => '',
			'details' => 'Kostenpflichtig für Verkäufer',
			'duration' => '',
			'name' => 'Authorize',
			'alias' => 'authorize',
			'hook' => '',
			'url' => '',
			'votes' => '0',
			'created' => '2010-09-19 20:15:12',
			'modified' => '2011-09-29 17:52:29'
		),
		array(
			'id' => '12',
			'active' => 0,
			'set_rate' => '0.00',
			'rel_rate' => '0.0000',
			'sort' => '0',
			'description' => 'Elektronisches Geld Bitcoin',
			'hint' => '',
			'details' => '',
			'duration' => '',
			'name' => 'Bitcoin',
			'alias' => 'bitcoin',
			'hook' => '',
			'url' => '',
			'votes' => '0',
			'created' => '2011-07-16 12:19:32',
			'modified' => '2011-09-29 23:37:23'
		),
	);
}
