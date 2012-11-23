<?php

//App::import('Helper', 'Tools.Form');
App::uses('FormHelper', 'View/Helper');

/**
 * Enhance Forms with JS widget stuff
 * TODO: namespace change: make it HtmlHelper
 *
 * FormExtHelper
 * 2011-03-07 ms
 */
class FormExtHelper extends FormHelper { // Maybe FormHelper itself some day?

	public $helpers = array('Html', 'Js', 'Tools.Common');

	public $settings = array(
		'webroot' => true # false => tools plugin
	);

	public $scriptsAdded = array(
		'date' => false,
		'time' => false,
		'maxLength' => false,
		'autoComplete' => false
	);

	public function __construct($View = null, $settings = array()) {
		if (($webroot = Configure::read('Asset.webroot')) !== null) {
			$this->settings['webroot'] = $webroot;
		}

		parent::__construct($View, $settings);
	}

	/**
	 * Creates an HTML link, but accesses the url using DELETE method.
	 * Requires javascript to be enabled in browser.
	 *
	 * This method creates a `<form>` element. So do not use this method inside an existing form.
	 * Instead you should add a submit button using FormHelper::submit()
	 *
	 * ### Options:
	 *
	 * - `data` - Array with key/value to pass in input hidden
	 * - `confirm` - Can be used instead of $confirmMessage.
	 * - Other options is the same of HtmlHelper::link() method.
	 * - The option `onclick` will be replaced.
	 *
	 * @param string $title The content to be wrapped by <a> tags.
	 * @param string|array $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
	 * @param array $options Array of HTML attributes.
	 * @param string $confirmMessage JavaScript confirmation message.
	 * @return string An `<a />` element.
	 */
	public function deleteLink($title, $url = null, $options = array(), $confirmMessage = false) {
		$options['method'] = 'DELETE';
		return $this->postLink($title, $url, $options, $confirmMessage);
	}

/**
 * Creates a textarea widget.
 *
 * ### Options:
 *
 * - `escape` - Whether or not the contents of the textarea should be escaped. Defaults to true.
 *
 * @param string $fieldName Name of a field, in the form "Modelname.fieldname"
 * @param array $options Array of HTML attributes, and special options above.
 * @return string A generated HTML text input element
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::textarea
 */
	public function textarea($fieldName, $options = array()) {
		$options['normalize'] = false;
		return parent::textarea($fieldName, $options);
	}

	/**
	 * fix for required adding (only manually)
	 * 2011-11-01 ms
	 */
	protected function _introspectModel($model, $key, $field = null) {
		if ($key === 'validates' && Configure::read('Validation.autoRequire') === false) {
			return false;
		}
		return parent::_introspectModel($model, $key, $field);
	}

	public function postLink($title, $url = null, $options = array(), $confirmMessage = false) {
		if (!isset($options['class'])) {
			$options['class'] = 'postLink';
		}
		return parent::postLink($title, $url , $options, $confirmMessage);
	}


/**
 * Generates a form input element complete with label and wrapper div
 * HTML 5 ready!
 *
 * ### Options
 *
 * See each field type method for more information. Any options that are part of
 * $attributes or $options for the different **type** methods can be included in `$options` for input().
 *
 * - `type` - Force the type of widget you want. e.g. `type => 'select'`
 * - `label` - Either a string label, or an array of options for the label. See FormHelper::label()
 * - `div` - Either `false` to disable the div, or an array of options for the div.
 *    See HtmlHelper::div() for more options.
 * - `options` - for widgets that take options e.g. radio, select
 * - `error` - control the error message that is produced
 * - `empty` - String or boolean to enable empty select box options.
 * - `before` - Content to place before the label + input.
 * - `after` - Content to place after the label + input.
 * - `between` - Content to place between the label + input.
 * - `format` - format template for element order. Any element that is not in the array, will not be in the output.
 *    - Default input format order: array('before', 'label', 'between', 'input', 'after', 'error')
 *    - Default checkbox format order: array('before', 'input', 'between', 'label', 'after', 'error')
 *    - Hidden input will not be formatted
 *    - Radio buttons cannot have the order of input and label elements controlled with these settings.
 *
 * @param string $fieldName This should be "Modelname.fieldname"
 * @param array $options Each type of input takes different options.
 * @return string Completed form widget.
 * @access public
 * @link http://book.cakephp.org/view/1390/Automagic-Form-Elements
 */
	public function inputExt($fieldName, $options = array()) {
		//$this->setEntity($fieldName);

		$options = array_merge(
			array('before' => null, 'between' => null, 'after' => null, 'format' => null),
			$this->_inputDefaults,
			$options
		);

		$modelKey = $this->model();
		$fieldKey = $this->field();
		if (!isset($this->fieldset[$modelKey])) {
			$this->_introspectModel($modelKey);
		}

		if (!isset($options['type'])) {
			$magicType = true;
			$options['type'] = 'text';
			if (isset($options['options'])) {
				$options['type'] = 'select';
			} elseif (in_array($fieldKey, array('color', 'email', 'number', 'range', 'url'))) {
				$options['type'] = $fieldKey;
			} elseif (in_array($fieldKey, array('psword', 'passwd', 'password'))) {
				$options['type'] = 'password';
			} elseif (isset($this->fieldset[$modelKey]['fields'][$fieldKey])) {
				$fieldDef = $this->fieldset[$modelKey]['fields'][$fieldKey];
				$type = $fieldDef['type'];
				$primaryKey = $this->fieldset[$modelKey]['key'];
			}

			if (isset($type)) {
				$map = array(
					'string' => 'text', 'datetime' => 'datetime', 'boolean' => 'checkbox',
					'timestamp' => 'datetime', 'text' => 'textarea', 'time' => 'time',
					'date' => 'date', 'float' => 'text',	 'integer' => 'number',
				);

				if (isset($this->map[$type])) {
					$options['type'] = $this->map[$type];
				} elseif (isset($map[$type])) {
					$options['type'] = $map[$type];
				}
				if ($fieldKey == $primaryKey) {
					$options['type'] = 'hidden';
				}
			}
			if (preg_match('/_id$/', $fieldKey) && $options['type'] !== 'hidden') {
				$options['type'] = 'select';
			}

			if ($modelKey === $fieldKey) {
				$options['type'] = 'select';
				if (!isset($options['multiple'])) {
					$options['multiple'] = 'multiple';
				}
			}
		}
		$types = array('checkbox', 'radio', 'select');

		if (
			(!isset($options['options']) && in_array($options['type'], $types)) ||
			(isset($magicType) && $options['type'] == 'text')
		) {
			$varName = Inflector::variable(
				Inflector::pluralize(preg_replace('/_id$/', '', $fieldKey))
			);
			$varOptions = $this->_View->getVar($varName);
			if (is_array($varOptions)) {
				if ($options['type'] !== 'radio') {
					$options['type'] = 'select';
				}
				$options['options'] = $varOptions;
			}
		}

		$autoLength = (!array_key_exists('maxlength', $options) && isset($fieldDef['length']));
		if ($autoLength && $options['type'] == 'text') {
			$options['maxlength'] = $fieldDef['length'];
		}
		if ($autoLength && $fieldDef['type'] == 'float') {
			$options['maxlength'] = array_sum(explode(',', $fieldDef['length']))+1;
		}

		$divOptions = array();
		$div = $this->_extractOption('div', $options, true);
		unset($options['div']);

		if (!empty($div)) {
			$divOptions['class'] = 'input';
			$divOptions = $this->addClass($divOptions, $options['type']);
			if (is_string($div)) {
				$divOptions['class'] = $div;
			} elseif (is_array($div)) {
				$divOptions = array_merge($divOptions, $div);
			}
			if (
				isset($this->fieldset[$modelKey]) &&
				in_array($fieldKey, $this->fieldset[$modelKey]['validates'])
			) {
				$divOptions = $this->addClass($divOptions, 'required');
			}
			if (!isset($divOptions['tag'])) {
				$divOptions['tag'] = 'div';
			}
		}

		$label = null;
		if (isset($options['label']) && $options['type'] !== 'radio') {
			$label = $options['label'];
			unset($options['label']);
		}

		if ($options['type'] === 'radio') {
			$label = false;
			if (isset($options['options'])) {
				$radioOptions = (array)$options['options'];
				unset($options['options']);
			}
		}

		if ($label !== false) {
			$label = $this->_inputLabel($fieldName, $label, $options);
		}

		$error = $this->_extractOption('error', $options, null);
		unset($options['error']);

		$selected = $this->_extractOption('selected', $options, null);
		unset($options['selected']);

		if (isset($options['rows']) || isset($options['cols'])) {
			$options['type'] = 'textarea';
		}

		if ($options['type'] === 'datetime' || $options['type'] === 'date' || $options['type'] === 'time' || $options['type'] === 'select') {
			$options += array('empty' => false);
		}
		if ($options['type'] === 'datetime' || $options['type'] === 'date' || $options['type'] === 'time') {
			$dateFormat = $this->_extractOption('dateFormat', $options, 'MDY');
			$timeFormat = $this->_extractOption('timeFormat', $options, 12);
			unset($options['dateFormat'], $options['timeFormat']);
		}
		if ($options['type'] === 'email') {
		}

		$type = $options['type'];
		$out = array_merge(
			array('before' => null, 'label' => null, 'between' => null, 'input' => null, 'after' => null, 'error' => null),
			array('before' => $options['before'], 'label' => $label, 'between' => $options['between'], 'after' => $options['after'])
		);
		$format = null;
		if (is_array($options['format']) && in_array('input', $options['format'])) {
			$format = $options['format'];
		}
		unset($options['type'], $options['before'], $options['between'], $options['after'], $options['format']);

		switch ($type) {
			case 'hidden':
				$input = $this->hidden($fieldName, $options);
				$format = array('input');
				unset($divOptions);
			break;
			case 'checkbox':
				$input = $this->checkbox($fieldName, $options);
				$format = $format ? $format : array('before', 'input', 'between', 'label', 'after', 'error');
			break;
			case 'radio':
				$input = $this->radio($fieldName, $radioOptions, $options);
			break;
			case 'select':
				$options += array('options' => array());
				$list = $options['options'];
				unset($options['options']);
				$input = $this->select($fieldName, $list, $selected, $options);
			break;
			case 'time':
				$input = $this->dateTime($fieldName, null, $timeFormat, $selected, $options);
			break;
			case 'date':
				$input = $this->dateTime($fieldName, $dateFormat, null, $selected, $options);
			break;
			case 'datetime':
				$input = $this->dateTime($fieldName, $dateFormat, $timeFormat, $selected, $options);
			break;
			case 'textarea':
				$input = $this->textarea($fieldName, $options + array('cols' => '30', 'rows' => '6'));
			break;
			case 'password':
			case 'file':
				$input = $this->{$type}($fieldName, $options);
			break;
			default:
				$options['type'] = $type;
				$input = $this->text($fieldName, $options);
		}

		if ($type != 'hidden' && $error !== false) {
			$errMsg = $this->error($fieldName, $error);
			if ($errMsg) {
				$divOptions = $this->addClass($divOptions, 'error');
				$out['error'] = $errMsg;
			}
		}

		$out['input'] = $input;
		$format = $format ? $format : array('before', 'label', 'between', 'input', 'after', 'error');
		$output = '';
		foreach ($format as $element) {
			$output .= $out[$element];
			unset($out[$element]);
		}
		if (!empty($divOptions['tag'])) {
			$tag = $divOptions['tag'];
			unset($divOptions['tag']);
			$output = $this->Html->tag($tag, $output, $divOptions);
		}
		return $output;
	}


	/**
	 * override with some custom functionality
	 * - html5 list/datalist (fallback = invisible)
	 * 2011-07-16 ms
	 */
	public function input($fieldName, $options = array()) {
		$this->setEntity($fieldName);

		$modelKey = $this->model();
		$fieldKey = $this->field();

		if (isset($options['datalist'])) {
			$options['autocomplete'] = 'off';
			if (!isset($options['list'])) {
				$options['list'] = ucfirst($fieldKey).'List';
			}
			$datalist = $options['datalist'];

			$list = '<datalist id="'.$options['list'].'">';
			//$list .= '<!--[if IE]><div style="display: none"><![endif]-->';
			foreach ($datalist as $key => $val) {
				if (!isset($options['escape']) || $options['escape'] !== false) {
					$key = h($key);
					$val = h($val);
				}
				$list .= '<option label="'.$val.'" value="'.$key.'"></option>';
			}
			//$list .= '<!--[if IE]></div><![endif]-->';
			$list .= '</datalist>';
			unset($options['datalist']);
			$options['after'] = !empty($options['after']) ? $options['after'].$list : $list;
		}

		if (isset($options['required'])) {
			$this->_introspectModel($modelKey, 'validates', $fieldKey);
			$this->fieldset[$modelKey]['validates'][$fieldKey] = $options['required'];
			if ($options['required'] === false) {
				$autoRequire = Configure::read('Validation.autoRequire');
				Configure::write('Validation.autoRequire', false);
			}
			//unset($options['require']);
		}
		if (Configure::read('Validation.browserAutoRequire') !== true) {
			if (!empty($options['required'])) {
				//$options['div']['class'] = !empty($options['div']['class']) ? $options['div']['class'].' required' : 'required';
				//$options['class'] = $this->addClass(isset($options['class'])?$options['class']:array(), 'required');
				/*
				$this->setEntity($fieldName);
				$modelKey = $this->model();
				$fieldKey = $this->field();
				$this->fieldset[$modelKey]['validates'][$fieldKey] = true;
				*/
			}
			if (isset($options['required'])) {
				unset($options['required']);
			}
		}


		$res = parent::input($fieldName, $options);

		if (isset($autoRequire)) {
			Configure::write('Validation.autoRequire', $autoRequire);
		}

		return $res;
	}

/** date(time) **/

	//TODO: use http://trentrichardson.com/examples/timepicker/
	// or maybe: http://pttimeselect.sourceforge.net/example/index.html (if 24 hour + select dropdowns are supported)
	/**
	 * quicklinks: clear, today, ...
	 * 2011-04-29 ms
	 */
	public function dateScripts($scripts = array(), $quicklinks = false) {
		foreach ($scripts as $script) {
			if (!$this->scriptsAdded[$script]) {
				switch ($script) {
					case 'date':
						if ($this->settings['webroot']) {
							$this->Html->script('datepicker/lang/de', false);
							$this->Html->script('datepicker/datepicker', false);
							$this->Html->css('common/datepicker', null, array('inline'=>false));
						} else {
							$this->Common->script(array('Tools.Js|datepicker/lang/de', 'Tools.Js|datepicker/datepicker'), false);
							$this->Common->css(array('Tools.Js|datepicker/datepicker'), null, array('inline'=>false));
						}
						$this->scriptsAdded['date'] = true;
						break;
					case 'time':
						continue;
						if ($this->settings['webroot']) {

						} else {
							//'Tools.Jquery|ui/core/jquery.ui.core', 'Tools.Jquery|ui/core/jquery.ui.widget', 'Tools.Jquery|ui/widgets/jquery.ui.slider',
							$this->Common->script(array('Tools.Jquery|plugins/jquery.timepicker.core', 'Tools.Jquery|plugins/jquery.timepicker'), false);
							$this->Common->css(array('Tools.Jquery|ui/core/jquery.ui', 'Tools.Jquery|plugins/jquery.timepicker'), null, array('inline'=>false));
						}
						break;
					default:
						break;

				}

				if ($quicklinks) {

				}
			}
		}
	}


	public function dateTimeExt($field, $options = array()) {
		$res = array();
		if (!isset($options['separator'])) {
			$options['separator'] = null;
		}
		if (!isset($options['label'])) {
			$options['label'] = null;
		}

		if (strpos($field, '.') !== false) {
			list($model, $field) = explode('.', $field, 2);
		} else {
			$entity = $this->entity();
			$model = $this->model();
		}

		$defaultOptions = array(
			'empty' => false,
			'return' => true,
		);

		$customOptions = array_merge($defaultOptions, $options);


		$res[] = $this->date($field, $customOptions);
		$res[] = $this->time($field, $customOptions);

		$select = implode(' &nbsp; ', $res);

		//return $this->date($field, $options).$select;
		if ($this->isFieldError($field)) {
			$error = $this->error($field);
		} else {
			$error = '';
		}

		$fieldname = Inflector::camelize($field);
		$script = '
<script type="text/javascript">
	// <![CDATA[
		var opts = {
			formElements:{"'.$model.$fieldname.'":"Y","'.$model.$fieldname.'-mm":"m","'.$model.$fieldname.'-dd":"d"},
			showWeeks:true,
			statusFormat:"l-cc-sp-d-sp-F-sp-Y",

			// Position the button within a wrapper span with an id of "button-wrapper"
			positioned:"button-wrapper"
		};
		datePickerController.createDatePicker(opts);
	// ]]>
</script>
		';
		return '<div class="input date'.(!empty($error)?' error':'').'">'.$this->label($model.'.'.$field, $options['label']).''.$select.''.$error.'</div>'.$script;
	}

	/**
	 * @deprecated
	 * use Form::dateExt
	 */
	public function date($field, $options = array()) {
		return $this->dateExt($field, $options);
	}

	/**
	 * date input (day, month, year) + js
	 * @see http://www.frequency-decoder.com/2006/10/02/unobtrusive-date-picker-widgit-update/
	 * @param field (field or Model.field)
	 * @param options
	 * - separator (between day, month, year)
	 * - label
	 * - empty
	 * - disableDays (TODO!)
	 * - minYear/maxYear (TODO!) / rangeLow/rangeHigh (xxxx-xx-xx or today)
	 * 2010-01-20 ms
	 */
	public function dateExt($field, $options = array()) {
		$return = false;
		if (isset($options['return'])) {
			$return = $options['return'];
			unset($options['return']);
		}
		$quicklinks = false;
		if (isset($options['quicklinks'])) {
			$quicklinks = $options['quicklinks'];
			unset($options['quicklinks']);
		}
		if (isset($options['callbacks'])) {
			$callbacks = $options['callbacks'];
			unset($options['callbacks']);
		}

		$this->dateScripts(array('date'), $quicklinks);
		$res = array();
		if (!isset($options['separator'])) {
			$options['separator'] = '-';
		}
		if (!isset($options['label'])) {
			$options['label'] = null;
		}

		if (isset($options['disableDays'])) {
			$disableDays = $options['disableDays'];
		}
		if (isset($options['highligtDays'])) {
			$highligtDays = $options['highligtDays'];
		} else {
			$highligtDays = '67';
		}

		if (strpos($field, '.') !== false) {
			list($modelName, $fieldName) = explode('.', $field, 2);
		} else {
			$entity = $this->entity();
			$modelName = $this->model();
			$fieldName = $field;
		}

		$defaultOptions = array(
			'empty' => false,
			'minYear' => date('Y')-10,
			'maxYear' => date('Y')+10
		);
		$defaultOptions = array_merge($defaultOptions, (array)Configure::read('Form.date'));

		$fieldName = Inflector::camelize($fieldName);

		$customOptions = array(
			'id' => $modelName.$fieldName.'-dd',
			'class' => 'day'
		);
		$customOptions = array_merge($defaultOptions, $customOptions, $options);
		$res['d'] = $this->day($field, $customOptions);

		$customOptions = array(
			'id' => $modelName.$fieldName.'-mm',
			'class' => 'month'
		);
		$customOptions = array_merge($defaultOptions, $customOptions, $options);
		$res['m'] = $this->month($field, $customOptions);

		$customOptions = array(
			'id' => $modelName.$fieldName,
			'class' => 'year'
		);
		$customOptions = array_merge($defaultOptions, $customOptions, $options);
		$minYear = $customOptions['minYear'];
		$maxYear = $customOptions['maxYear'];
		$res['y'] = $this->year($field, $minYear, $maxYear, $customOptions);

		if (isset($options['class'])) {
			$class = $options['class'];
			unset($options['class']);
		}

		$select = implode($options['separator'], $res);

		if ($this->isFieldError($field)) {
			$error = $this->error($field);
		} else {
			$error = '';
		}

		if (!empty($callbacks)) {
			//callbackFunctions:{"create":...,"dateset":[updateBox]},
			$c = $callbacks['update'];
			$callbacks = 'callbackFunctions:{"dateset":['.$c.']},';
		}

		if (!empty($customOptions['type']) && $customOptions['type'] == 'text') {
			$script = '
<script type="text/javascript">
	// <![CDATA[
		var opts = {
			formElements:{"'.$modelName.$fieldName.'":"d-dt-m-dt-Y"},
			showWeeks:true,
			statusFormat:"l-cc-sp-d-sp-F-sp-Y",
			'.(!empty($callbacks)?$callbacks:'').'
			// Position the button within a wrapper span with an id of "button-wrapper"
			positioned:"button-wrapper"
		};
		datePickerController.createDatePicker(opts);
	// ]]>
</script>
		';

			$options = array_merge(array('id' => $modelName.$fieldName), $options);
			$select = $this->text($field, $options);
			return '<div class="input date'.(!empty($error)?' error':'').'">'.$this->label($modelName.'.'.$field, $options['label']).''.$select.''.$error.'</div>'.$script;
		}


		if ($return) {
			return $select;
		}
		$script = '
<script type="text/javascript">
	// <![CDATA[
		var opts = {
			formElements:{"'.$modelName.$fieldName.'":"Y","'.$modelName.$fieldName.'-mm":"m","'.$modelName.$fieldName.'-dd":"d"},
			showWeeks:true,
			statusFormat:"l-cc-sp-d-sp-F-sp-Y",
			'.(!empty($callbacks)?$callbacks:'').'
			// Position the button within a wrapper span with an id of "button-wrapper"
			positioned:"button-wrapper"
		};
		datePickerController.createDatePicker(opts);
	// ]]>
</script>
		';
		return '<div class="input date'.(!empty($error)?' error':'').'">'.$this->label($modelName.'.'.$field, $options['label']).''.$select.''.$error.'</div>'.$script;
	}

	/**
	 * @deprecated
	 * use Form::dateTimeExt
	 */
	public function dateTime($field, $options = array(), $tf = 24, $a = array()) {
		# temp fix
		if (!is_array($options)) {
			/*
			if ($options === null) {
				$options = 'DMY';
			}
			*/
			return parent::dateTime($field, $options, $tf, $a);
		}
		return $this->dateTimeExt($field, $options);
	}

	/**
	 * @deprecated
	 * use Form::timeExt
	 */
	public function time($field, $options = array()) {
		return $this->timeExt($field, $options);
	}

	public function timeExt($field, $options = array()) {
		$return = false;
		if (isset($options['return'])) {
			$return = $options['return'];
			unset($options['return']);
		}

		$this->dateScripts(array('time'));
		$res = array();
		if (!isset($options['separator'])) {
			$options['separator'] = ':';
		}
		if (!isset($options['label'])) {
			$options['label'] = null;
		}

		$defaultOptions = array(
			'empty' => false,
			'timeFormat' => 24,
		);

		if (strpos($field, '.') !== false) {
			list($model, $field) = explode('.', $field, 2);
		} else {
			$entity = $this->entity();
			$model = $this->model();
		}
		$fieldname = Inflector::camelize($field);

		$customOptions = array_merge($defaultOptions, $options);
		$format24Hours = $customOptions['timeFormat'] != '24' ? false : true;

		if (strpos($field, '.') !== false) {
			list($model, $field) = explode('.', $field, 2);
		} else {
			$entity = $this->entity();
			$model = $this->model();
		}

		$hourOptions = array_merge($customOptions, array('class'=>'hour'));
		$res['h'] = $this->hour($field, $format24Hours, $hourOptions);

		$minuteOptions = array_merge($customOptions, array('class'=>'minute'));
		$res['m'] = $this->minute($field, $minuteOptions);

		$select = implode($options['separator'], $res);

		if ($this->isFieldError($field)) {
			$error = $this->error($field);
		} else {
			$error = '';
		}

		if ($return) {
			return $select;
		}
		/*
		$script = '
<script type="text/javascript">
	// <![CDATA[
		$(document).ready(function() {
		$(\'#'.$model.$fieldname.'-timepicker\').jtimepicker({
			// Configuration goes here
			\'secView\': false
		});
	});
	// ]]>
</script>
		';
		*/
		$script = '';
		//<div id="'.$model.$fieldname.'-timepicker"></div>
		return '<div class="input date'.(!empty($error)?' error':'').'">'.$this->label($model.'.'.$field, $options['label']).''.$select.''.$error.'</div>'.$script;
	}


/** maxLength **/

	public $maxLengthOptions = array(
		'maxCharacters' => 255,
		//'events' => array(),
		'status' => true,
		'statusClass' => 'status',
		'statusText' => 'characters left',
		'slider' => true
	);

	public function maxLengthScripts() {
		if (!$this->scriptsAdded['maxLength']) {
			$this->Html->script('jquery/maxlength/jquery.maxlength', array('inline'=>false));
			$this->scriptsAdded['maxLength'] = true;
		}
	}

	/**
	 * maxLength js for textarea input
	 * final output
	 * @param array $selectors with specific settings
	 * @param array $globalOptions
	 * @return string with JS code
	 * 2009-07-30 ms
	 */
	public function maxLength($selectors = array(), $options = array()) {
		$this->maxLengthScripts();
		$js = '';
		$this->maxLengthOptions['statusText'] = __($this->maxLengthOptions['statusText']);

		$selectors = (array)$selectors;
		foreach ($selectors as $selector => $settings) {
			if (is_int($selector)) {
				$selector = $settings;
				$settings = array();
			}
			$js .= $this->_maxLength($selector, array_merge($this->maxLengthOptions, $settings));
		}

		if (!empty($options['plain'])) {
			return $js;
		}
		$js = $this->documentReady($js);
		return $this->Html->scriptBlock($js);
	}

	protected function _maxLength($selector, $settings = array()) {
		return '
jQuery(\''.$selector.'\').maxlength('.$this->Js->object($settings, array('quoteKeys'=>false)).');
';
	}


	public function scripts($type) {
		switch ($type) {
			case 'charCount':
				$this->Html->script('jquery/plugins/charCount', array('inline'=>false));
				$this->Html->css('/js/jquery/plugins/charCount', null, array('inline'=>false));
				break;
			default:
				return false;
		}
		$this->scriptsAdded[$type] = true;
		return true;
	}


	public $charCountOptions = array(
		'allowed' => 255,
	);

	public function charCount($selectors = array(), $options = array()) {
		$this->scripts('charCount');
		$js = '';

		$selectors = (array)$selectors;
		foreach ($selectors as $selector => $settings) {
			if (is_int($selector)) {
				$selector = $settings;
				$settings = array();
			}
			$settings = array_merge($this->charCountOptions, $options, $settings);
			$js .= 'jQuery(\''.$selector.'\').charCount('.$this->Js->object($settings, array('quoteKeys'=>false)).');';
		}
		$js = $this->documentReady($js);
		return $this->Html->scriptBlock($js, array('inline' => isset($options['inline']) ? $options['inline'] : true));
	}



	public function documentReady($string) {
		return 'jQuery(document).ready(function() {
'.$string.'
});';
	}


	public function autoCompleteScripts() {
		if (!$this->scriptsAdded['autoComplete']) {
			$this->Html->script('jquery/autocomplete/jquery.autocomplete', false);
			$this->Html->css('/js/jquery/autocomplete/jquery.autocomplete', null, array('inline'=>false));
			$this->scriptsAdded['autoComplete'] = true;
		}
	}


	/**
	 * //TODO
	 * @param jquery: defaults to null = no jquery markup
	 * - url, data, object (one is necessary), options
	 * 2010-01-27 ms
	 */
	public function autoComplete($field = null, $options = array(), $jquery = null) {
		$this->autoCompleteScripts();

		$defaultOptions = array(
			'autocomplete' => 'off'
		);
		$options = array_merge($defaultOptions, $options);
		if (empty($options['id']) && is_array($jquery)) {
			$options['id'] = Inflector::camelize(str_replace(".", "_", $field));
		}

		$res = $this->input($field, $options);
		if (is_array($jquery)) {
			# custom one
			$res .= $this->_autoComplete($options['id'], $jquery);
		}
		return $res;
	}


	protected function _autoComplete($id, $jquery = array()) {
		if (!empty($jquery['url'])) {
			$var = '"'.$this->Html->url($jquery['url']).'"';
		} elseif (!empty($jquery['var'])) {
			$var = $jquery['object'];
		} else {
			$var = '['.$jquery['data'].']';
		}

		$options = '';
		if (!empty($jquery['options'])) {

		}

		$js = 'jQuery("#'.$id.'").autocomplete('.$var.', {
		'.$options.'
	});
';
		$js = $this->documentReady($js);
		return $this->Html->scriptBlock($js);
	}


/** checkboxes **/

	public function checkboxScripts() {
		if (!$this->scriptsAdded['checkbox']) {
			$this->Html->script('jquery/checkboxes/jquery.checkboxes', false);
			$this->scriptsAdded['checkbox'] = true;
		}
	}

	/**
	 * returns script + elements "all", "none" etc
	 * 2010-02-15 ms
	 */
	public function checkboxScript($id) {
		$this->checkboxScripts();
		$js = 'jQuery("#'.$id.'").autocomplete('.$var.', {
		'.$options.'
	});
';
		$js = $this->documentReady($js);
		return $this->Html->scriptBlock($js);
	}

	public function checkboxButtons($buttonsOnly = false) {
		$res = '<div>';
		$res .= __('Selection').': ';

		$res .= $this->Html->link(__('All'), 'javascript:void(0)');
		$res .= $this->Html->link(__('None'), 'javascript:void(0)');
		$res .= $this->Html->link(__('Revert'), 'javascript:void(0)');

		$res .= '</div>';
		if ($buttonsOnly !== true) {
			$res .= $this->checkboxScript();
		}
		return $res;
	}

	/**
	 * displays a single checkbox - called for each
	 */
	public function _checkbox($id, $group = null, $options = array()) {
		$defaults = array(
	 		'class' => 'checkboxToggle'
		);
		$options = array_merge($defaults, $options);
		return $script . parent::checkbox($fieldName, $options);
	}


/** other stuff **/

	/**
	 * echo $this->FormExt->buttons($buttons);
	 * with
	 * $buttons = array(
	 *  array(
	 *   'title' => 'Login',
	 *   'options' => array('type' => 'submit')
	 *  ),
	 *  array(...)
	 * );
	 * @param array $buttons
	 * @return string $buttonSubmitDiv
	 * 2009-07-26 ms
	 */
	public function buttons($buttons = null) {
		$return = '';
		if (!empty($buttons) && is_array($buttons)) {
			$buttons_content = '';
			foreach ($buttons as $button) {
				if (empty($button['options'])) { $button['options'] = array(); }
				$buttons_content .= $this->button($button['name'], $button['options']);
			}
			$return = $this->Html->div('submit', $buttons_content);
		}
		return $return;
	}


/** nice buttons **/

	protected $buttons = array();
	protected $buttonAlign = 'left';

	/**
	 * @param title
	 * @param options:
	 * - color (green, blue, red, orange)
	 * - url
	 * - align (left/right)
	 * @param attributes (html)
	 * 2010-03-15 ms
	 */
	public function addButton($title, $options = array(), $attr = array()) {

		$url = !empty($options['url']) ? $options['url'] : 'javascript:void(0)';
		$color = !empty($options['color']) ? ' ovalbutton'.ucfirst($options['color']) : '';

		if (isset($options['align'])) {
			$this->buttonAlign = $options['align'];
		}

		if ($this->buttonAlign == 'left') {
			$align = 'margin-right:5px';
		} elseif ($this->buttonAlign == 'right') {
			$align = 'margin-left:5px';
		}

		$class = 'ovalbutton'.$color;
		if (!empty($attr['class'])) {
			$class .= ' '.$attr['class'];
		}
		$style = array();
		if (!empty($align)) {
			$style[] = $align;
		}
		if (!empty($attr['class'])) {
			$style[] = $attr['style'];
		}
		$style = implode(';', $style);
		$attr = array_merge($attr, array('escape'=>false,'class'=>$class, 'style'=>$style));

		//$this->buttons[] = '<a class="ovalbutton'.$color.'"'.$href.''.$align.'><span>'.$title.'</span></a>';

		if (!isset($attr['escape']) || $attr['escape'] !== true) {
			$title = h($title);
		}
		$this->buttons[] = $this->Html->link('<span>'.$title.'</span>', $url, $attr);
	}

	/**
	 * 2010-03-15 ms
	 */
	public function displayButtons($options = array()) {
		$res = '<div class="buttonwrapper" style="text-align: '.$this->buttonAlign.'">'.implode('', $this->buttons).'</div>';
		$this->buttons = array();
		$this->buttonAlign = 'left';
		return $res;
	}


/*
	public $datetimeQuicklinks = '
	public function str_pad(input, pad_length, pad_string, pad_type) {
	// http://kevin.vanzonneveld.net
	// + original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// + namespaced by: Michael White (http://getsprink.com)
	// + input by: Marco van Oort
	// + bugfixed by: Brett Zamir (http://brett-zamir.me)
	// * example 1: str_pad('Kevin van Zonneveld', 30, '-=', 'STR_PAD_LEFT');
	// * returns 1: '-=-=-=-=-=-Kevin van Zonneveld'
	// * example 2: str_pad('Kevin van Zonneveld', 30, '-', 'STR_PAD_BOTH');
	// * returns 2: '------Kevin van Zonneveld-----'
	var half = '',
		pad_to_go;

	var str_pad_repeater = function (s, len) {
		var collect = '',
			i;

		while (collect.length < len) {
			collect += s;
		}
		collect = collect.substr(0, len);

		return collect;
	};

	input += '';
	pad_string = pad_string !== undefined ? pad_string : ' ';

	if (pad_type != 'STR_PAD_LEFT' && pad_type != 'STR_PAD_RIGHT' && pad_type != 'STR_PAD_BOTH') {
		pad_type = 'STR_PAD_RIGHT';
	}
	if ((pad_to_go = pad_length - input.length) > 0) {
		if (pad_type == 'STR_PAD_LEFT') {
			input = str_pad_repeater(pad_string, pad_to_go) + input;
		} elseif (pad_type == 'STR_PAD_RIGHT') {
			input = input + str_pad_repeater(pad_string, pad_to_go);
		} elseif (pad_type == 'STR_PAD_BOTH') {
			half = str_pad_repeater(pad_string, Math.ceil(pad_to_go / 2));
			input = half + input + half;
			input = input.substr(0, pad_length);
		}
	}

	return input;
}

$(document).ready(function() {
		$('.date').append(' <span class="setRemove hand">ENTFERNEN</span> <span class="setToday hand">HEUTE</span> <span class="setNow hand">JETZT</span>');

		$('.setRemove').click(function() {
			var container = $(this).parent('div');
			container.children('.day').val("");
			container.children('.month').val("");
			container.children('.year').val("");
			container.children('.hour').val("");
			container.children('.minute').val("");
		});

		$('.setNow').click(function() {
			var d = new Date();
			var curr_hour = str_pad(d.getHours(), 2, "0", 'STR_PAD_LEFT');
			var curr_minute = str_pad(d.getMinutes(), 2, "0", 'STR_PAD_LEFT');

			var container = $(this).parent('div');
			container.children('.hour').val(curr_hour);
			container.children('.minute').val(curr_minute);
		});

		$('.setToday').click(function() {
			var d = new Date();
			var curr_date = str_pad(d.getDate(), 2, "0", 'STR_PAD_LEFT');
			var curr_month = str_pad(d.getMonth()+1, 2, "0", 'STR_PAD_LEFT');
			var curr_year = str_pad(d.getFullYear(), 2, "4", 'STR_PAD_LEFT');

			var container = $(this).parent('div');
			container.children('.day').val(curr_date);
			container.children('.month').val(curr_month);
			container.children('.year').val(curr_year);
		});

	});
';
*/

}