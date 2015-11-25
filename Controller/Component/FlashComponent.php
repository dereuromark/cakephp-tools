<?php

App::uses('Component', 'Controller');
App::uses('Configure', 'Core');
App::uses('Inflector', 'Utility');

/**
 * A flash component to enhance flash message support with stackable messages, both
 * persistent and transient.
 *
 * @author Mark Scherer
 * @copyright 2012 Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class FlashComponent extends Component {

	public $components = ['Session'];

	protected $_defaultConfig = [
		'headerOnAjax' => true,
		'transformCore' => true,
		'useElements' => false, // Set to true to use 3.x flash message rendering via Elements
		'type' => 'info',
		'typeToElement' => false, // Set to true to have a single type to Element matching
		'plugin' => null, // Only for typeToElement
		'element' => 'Tools.default',
		'params' => [],
		'escape' => false
	];

	/**
	 * Constructor.
	 *
	 * @param ComponentCollection $collection
	 * @param array $config
	 */
	public function __construct(ComponentCollection $collection, $config = []) {
		$defaults = (array)Configure::read('Flash') + $this->_defaultConfig;
		//BC
		if (Configure::read('Common.messages') !== null) {
			$defaults['transformCore'] = Configure::read('Common.messages');
		}

		$config += $defaults;
		parent::__construct($collection, $config);
	}

	/**
	 * For automatic startup
	 * for this helper the controller has to be passed as reference
	 *
	 * @return void
	 */
	public function initialize(Controller $Controller) {
		parent::initialize($Controller);

		$this->Controller = $Controller;
	}

	/**
	 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
	 * Controller::render()
	 *
	 * Unless Configure::read('Ajax.transformCore') is false, it will also transform any core ones to this plugin.
	 * Unless Configure::read('Ajax.headerOnAjax') is false, it will pass the messages as header to AJAX requests.
	 * Set it to false if other components are handling the message return in AJAX use cases already.
	 *
	 * @param object $Controller Controller with components to beforeRender
	 * @return void
	 */
	public function beforeRender(Controller $Controller) {
		if ($this->settings['transformCore'] && $messages = $this->Session->read('Message')) {
			foreach ($messages as $message) {
				$this->message($message['message'], 'error');
			}
			$this->Session->delete('Message');
		}

		if ($this->settings['headerOnAjax'] && isset($this->Controller->request) && $this->Controller->request->is('ajax')) {
			$ajaxMessages = array_merge(
				(array)$this->Session->read('messages'),
				(array)Configure::read('messages')
			);
			// The header can be read with JavaScript and a custom Message can be displayed
			$this->Controller->response->header('X-Ajax-Flashmessage', json_encode($ajaxMessages));

			$this->Session->delete('messages');
		}
	}

	/**
	 * Adds a flash message.
	 * Updates "messages" session content (to enable multiple messages of one type).
	 *
	 * ### Options:
	 *
	 * - `element` The element used to render the flash message. Default to 'default'.
	 * - `params` An array of variables to make available when using an element.
	 * - `escape` If content should be escaped or not in the element itself or if elements are not used in the component.
	 * - `typeToElement`
	 * - `plugin`
	 *
	 * @param string $message Message to output.
	 * @param array|string $options Options or Type ('error', 'warning', 'success', 'info' or custom class).
	 * @return void
	 */
	public function message($message, $options = []) {
		$message = $this->_prepMessage($message, $options, $this->settings);

		$old = (array)$this->Session->read('messages');
		$type = $options['type'];
		if (isset($old[$type]) && count($old[$type]) > 99) {
			array_shift($old[$type]);
		}
		$old[$type][] = $message;
		$this->Session->write('messages', $old);
	}

	/**
	 * Adds a transient flash message.
	 * These flash messages that are not saved (only available for current view),
	 * will be merged into the session flash ones prior to output.
	 *
	 * Since this method can be accessed statically, it only works with Configure configuration,
	 * not with runtime config as the normal message() method.
	 *
	 * ### Options:
	 *
	 * - `element` The element used to render the flash message. Default to 'default'.
	 * - `params` An array of variables to make available when using an element.
	 * - `escape` If content should be escaped or not in the element itself or if elements are not used in the component.
	 * - `typeToElement`
	 * - `plugin`
	 * - `useElements`
	 *
	 * @param string $message Message to output.
	 * @param array|string $options Options or Type ('error', 'warning', 'success', 'info' or custom class).
	 * @return void
	 */
	public static function transientMessage($message, $options = []) {
		$defaults = (array)Configure::read('Flash') + [
			'type' => 'info',
			'escape' => false,
			'params' => [],
			'element' => 'Tools.default',
			'typeToElement' => false,
			'useElements' => false,
			'plugin' => null,
		];
		$message = static::_prepMessage($message, $options, $defaults);

		$old = (array)Configure::read('messages');
		$type = $options['type'];
		if (isset($old[$type]) && count($old[$type]) > 99) {
			array_shift($old[$type]);
		}
		$old[$type][] = $message;
		Configure::write('messages', $old);
	}

	/**
	 * FlashComponent::_prepMessage()
	 *
	 * @param string $message
	 * @param array $options
	 * @return array
	 */
	protected static function _prepMessage($message, &$options, $defaults) {
		if (!is_array($options)) {
			$type = $options ?: $defaults['type'];
			$options = ['type' => $type];
		}
		$options += $defaults;

		$message = [
			'message' => $message,
			'params' => $options['params'],
			'escape' => $options['escape']
		];

		if ($options['useElements']) {
			if ($options['typeToElement'] && $options['element'] === $defaults['element']) {
				$options['element'] = ($options['plugin'] ? $options['plugin'] . '.' : '') . $options['type'];
			}
			list($plugin, $element) = pluginSplit($options['element']);
			if ($plugin) {
				$message['element'] = $plugin . '.Flash/' . $element;
			} else {
				$message['element'] = 'Flash/' . $element;
			}
		} else {
			// Simplify?
			if (!$message['escape'] && !$message['params']) {
				$message = $message['message'];
			}
		}
		return $message;
	}

	/**
	 * Magic method for verbose flash methods based on types.
	 *
	 * For example: $this->Flash->success('My message')
	 *
	 * @param string $name Element name to use.
	 * @param array $args Parameters to pass when calling `FlashComponent::set()`.
	 * @return void
	 * @throws InternalErrorException If missing the flash message.
	 */
	public function __call($name, $args) {
		if (count($args) < 1) {
			throw new InternalErrorException('Flash message missing.');
		}

		$options = ['type' => Inflector::underscore($name)];
		if (!empty($args[1])) {
			$options += (array)$args[1];
		}

		$this->message($args[0], $options);
	}

}
