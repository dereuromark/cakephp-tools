<?php
if (!defined('ENCHANT_MYSPELL')) {
	define('ENCHANT_MYSPELL', 1);
}
if (!defined('ENCHANT_ISPELL')) {
	define('ENCHANT_ISPELL', 2);
}

/**
 * Wrapper for the PHP Extension Enchant which provides basic spellchecking
 * @author Mark Scherer
 * @licence MIT
 *
 */
class SpellLib {

	const ENGINE_MYSPELL = ENCHANT_MYSPELL; # default engine

	const ENGINE_ISPELL = ENCHANT_ISPELL;

	/**
	 * Available engines
	 */
	protected $_engines = array(self::ENGINE_MYSPELL => 'myspell', self::ENGINE_ISPELL => 'ispell');

	/**
	 * Available languages
	 */
	protected $_langs = array('en_GB', 'de_DE');

	protected $_Broker;

	protected $_Dict;

	//public $settings = array();

	public function __construct($options = array()) {
		if (!function_exists('enchant_broker_init')) {
			throw new InternalErrorException(__('Module %s not installed', 'Enchant'));
		}
		$this->_Broker = enchant_broker_init();

		$defaults = array(
			'path' => VENDORS . 'dictionaries' . DS,
			'lang' => 'en_GB',
			'engine' => self::ENGINE_MYSPELL
		);
		$defaults = array_merge($defaults, (array)Configure::read('Spell'));
		$options = array_merge($defaults, $options);

		if (!isset($this->_engines[$options['engine']])) {
			throw new InternalErrorException(__('Engine %s not found', (string) $options['engine']));
		}
		$engineFolder = $this->_engines[$options['engine']];
		enchant_broker_set_dict_path($this->_Broker, $options['engine'], $options['path'] . $engineFolder . DS);

		if (!enchant_broker_dict_exists($this->_Broker, $options['lang'])) {
			throw new InternalErrorException(__('Dictionary %s not found', $options['lang']));
		}

		$this->_Dict = enchant_broker_request_dict($this->_Broker, $options['lang']);
	}

	public function listDictionaries() {
		return enchant_broker_list_dicts($this->_Broker);
	}

	public function listBrokers() {
		 return enchant_broker_describe($this->_Broker);
	}

	/**
	 * @return boolean Success
	 */
	public function check($word) {
		return enchant_dict_check($this->_Dict, $word);
	}

	/**
	 * @return array listOfSuggestions
	 */
	public function suggestions($word) {
		return enchant_dict_suggest($this->_Dict, $word);
	}

	public function __destruct() {
		enchant_broker_free($this->_Broker);
	}

}
