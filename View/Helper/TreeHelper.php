<?php

/**
 * PHP versions 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2008, Andy Dawson
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::uses('AppHelper', 'View/Helper');

/**
 * Helper to generate tree representations of MPTT or recursively nested data.
 *
 * @author Andy Dawson
 * @author Mark Scherer
 * @link http://www.dereuromark.de/2013/02/17/cakephp-and-tree-structures/
 */
class TreeHelper extends AppHelper {

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $_defaults = array(
		'model' => null,
		'alias' => 'name',
		'type' => 'ul',
		'itemType' => 'li',
		'id' => false,
		'class' => false,
		'element' => false,
		'callback' => false,
		'autoPath' => false,
		'hideUnrelated' => false,
		'treePath' => array(),
		'left' => 'lft',
		'right' => 'rght',
		'depth' => 0,
		'maxDepth' => 999,
		'firstChild' => true,
		'indent' => null,
		'splitDepth' => false,
		'splitCount' => null,
		'totalNodes' => null,
		'fullSettings' => false,
	);

	/**
	 * Settings property
	 *
	 * @var array
	 */
	protected $_settings = array();

	/**
	 * TypeAttributes property
	 *
	 * @var array
	 */
	protected $_typeAttributes = array();

	/**
	 * TypeAttributesNext property
	 *
	 * @var array
	 */
	protected $_typeAttributesNext = array();

	/**
	 * ItemAttributes property
	 *
	 * @var array
	 */
	protected $_itemAttributes = array();

	/**
	 * Helpers variable
	 *
	 * @var array
	 */
	public $helpers = array('Html');

	/**
	 * Tree generation method.
	 *
	 * Accepts the results of
	 * 	find('all', array('fields' => array('lft', 'rght', 'whatever'), 'order' => 'lft ASC'));
	 * 	children(); // if you have the tree behavior of course!
	 * or 	find('threaded'); and generates a tree structure of the data.
	 *
	 * Settings (2nd parameter):
	 *	'model' => name of the model (key) to look for in the data array. defaults to the first model for the current
	 * controller. If set to false 2d arrays will be allowed/expected.
	 *	'alias' => the array key to output for a simple ul (not used if element or callback is specified)
	 *	'type' => type of output defaults to ul
	 *	'itemType => type of item output default to li
	 *	'id' => id for top level 'type'
	 *	'class' => class for top level 'type'
	 *	'element' => path to an element to render to get node contents.
	 *	'callback' => callback to use to get node contents. e.g. array(&$anObject, 'methodName') or 'floatingMethod'
	 *	'autoPath' => array($left, $right [$classToAdd = 'active']) if set any item in the path will have the class $classToAdd added. MPTT only.
	 *  'hideUnrelated' => if unrelated (not children, not siblings) should be hidden, needs 'treePath', true/false or array/string for callback
	 *  'treePath' => treePath to insert into callback/element
	 *	'left' => name of the 'lft' field if not lft. only applies to MPTT data
	 *	'right' => name of the 'rght' field if not rght. only applies to MPTT data
	 *	'depth' => used internally when running recursively, can be used to override the depth in either mode.
	 *  'maxDepth' => used to control the depth upto which to generate tree
	 *	'firstChild' => used internally when running recursively.
	 *	'splitDepth' => if multiple "parallel" types are required, instead of one big type, nominate the depth to do so here
	 *		example: useful if you have 30 items to display, and you'd prefer they appeared in the source as 3 lists of 10 to be able to
	 *		style/float them.
	 *	'splitCount' => the number of "parallel" types. defaults to null (disabled) set the splitCount,
	 *		and optionally set the splitDepth to get parallel lists
	 *
	 * @param array $data data to loop on
	 * @param array $settings
	 * @return string html representation of the passed data
	 * @throws CakeException
	 */
	public function generate($data, $settings = array()) {
		if (!$data) {
			return '';
		}

		$this->_settings = array_merge($this->_defaults, (array)$settings);
		if ($this->_settings['autoPath'] && !isset($this->_settings['autoPath'][2])) {
			$this->_settings['autoPath'][2] = 'active';
		}
		extract($this->_settings);
		if ($indent === null && Configure::read('debug')) {
			$indent = true;
		}
		if ($model === null && $this->_View->params['models']) {
			foreach ($this->_View->params['models'] as $model => $value) {
				break;
			}
		}
		if ($model === null) {
			foreach ($data as $key => $value) {
				foreach ($value as $model => $array) {
					break;
				}
			}
		}
		$this->_settings['model'] = $model;

		$this->_itemAttributes = $this->_typeAttributes = $this->_typeAttributesNext = array();
		$stack = array();
		if ($depth == 0) {
			if ($class) {
				$this->addTypeAttribute('class', $class, null, 'previous');
			}
			if ($id) {
				$this->addTypeAttribute('id', $id, null, 'previous');
			}
		}
		$return = '';
		$addType = true;
		$this->_settings['totalNodes'] = count($data);
		$keys = array_keys($data);

		if ($hideUnrelated === true || is_numeric($hideUnrelated)) {
			$this->_markUnrelatedAsHidden($data, $treePath);
		} elseif ($hideUnrelated && is_callable($hideUnrelated)) {
			call_user_func($hideUnrelated, $data, $treePath);
		}

		foreach ($data as $i => &$result) {
			/* Allow 2d data arrays */
			if ($model && isset($result[$model])) {
				$row =& $result[$model];
			} else {
				$row =& $result;
			}

			/* Close open items as appropriate */
			// @codingStandardsIgnoreStart
			while ($stack && ($stack[count($stack)-1] < $row[$right])) {
				// @codingStandardsIgnoreEnd
				array_pop($stack);
				if ($indent) {
					$whiteSpace = str_repeat("\t", count($stack));
					$return .= "\r\n" . $whiteSpace . "\t";
				}
				if ($type) {
					$return .= '</' . $type . '>';
				}
				if ($itemType) {
					$return .= '</' . $itemType . '>';
				}
			}

			/* Some useful vars */
			$hasChildren = $firstChild = $lastChild = $hasVisibleChildren = false;
			$numberOfDirectChildren = $numberOfTotalChildren = null;

			if (isset($result['children'])) {
				if ($result['children'] && $depth < $maxDepth) {
					$hasChildren = $hasVisibleChildren = true;
					$numberOfDirectChildren = count($result['children']);
				}
				$key = array_search($i, $keys);
				if ($key === 0) {
					$firstChild = true;
				}
				if ($key == count($keys) - 1) {
					$lastChild = true;
				}
			} elseif (isset($row[$left])) {
				if ($row[$left] != ($row[$right] - 1) && $depth < $maxDepth) {
					$hasChildren = true;
					$numberOfTotalChildren = ($row[$right] - $row[$left] - 1) / 2;
					if (isset($data[$i + 1]) && $data[$i + 1][$model][$right] < $row[$right]) {
						$hasVisibleChildren = true;
					}
				}
				if (!isset($data[$i - 1]) || ($data[$i - 1][$model][$left] == ($row[$left] - 1))) {
					$firstChild = true;
				}
				if (!isset($data[$i + 1]) || ($stack && $stack[count($stack) - 1] == ($row[$right] + 1))) {
					$lastChild = true;
				}
			} else {
				throw new CakeException('Invalid Tree Structure');
			}

			$activePathElement = null;
			if ($autoPath) {
				if ($result[$model][$left] <= $autoPath[0] && $result[$model][$right] >= $autoPath[1]) {
					$activePathElement = true;
				} elseif (isset($autoPath[3]) && $result[$model][$left] == $autoPath[0]) {
					$activePathElement = $autoPath[3];
				} else {
					$activePathElement = false;
				}
			}

			$depth = $depth ? $depth : count($stack);

			$elementData = array(
				'data' => $result,
				'depth' => $depth,
				'hasChildren' => $hasChildren,
				'numberOfDirectChildren' => $numberOfDirectChildren,
				'numberOfTotalChildren' => $numberOfTotalChildren,
				'firstChild' => $firstChild,
				'lastChild' => $lastChild,
				'hasVisibleChildren' => $hasVisibleChildren,
				'activePathElement' => $activePathElement,
				'isSibling' => ($depth == 0 && !$activePathElement) ? true : false,
			);
			if ($elementData['isSibling'] && $hideUnrelated) {
				$result['children'] = array();
			}

			$this->_settings = array_merge($this->_settings, $elementData);
			if ($this->_settings['fullSettings']) {
				$elementData = $this->_settings;
			}

			/* Main Content */
			if ($element) {
				$content = $this->_View->element($element, $elementData);
			} elseif ($callback) {
				list($content) = array_map($callback, array($elementData));
			} else {
				$content = $row[$alias];
			}

			if (!$content) {
				continue;
			}
			$whiteSpace = str_repeat("\t", $depth);
			if ($indent && strpos($content, "\r\n", 1)) {
				$content = str_replace("\r\n", "\n" . $whiteSpace . "\t", $content);
			}
			/* Prefix */
			if ($addType) {
				if ($indent) {
					$return .= "\r\n" . $whiteSpace;
				}
				if ($type) {
					$typeAttributes = $this->_attributes($type, array('data' => $elementData));
					$return .= '<' . $type . $typeAttributes . '>';
				}
			}
			if ($indent) {
				$return .= "\r\n" . $whiteSpace . "\t";
			}
			if ($itemType) {
				$itemAttributes = $this->_attributes($itemType, $elementData);
				$return .= '<' . $itemType . $itemAttributes . '>';
			}
			$return .= $content;
			/* Suffix */
			$addType = false;
			if ($hasVisibleChildren) {
				if ($numberOfDirectChildren) {
					$settings['depth'] = $depth + 1;
					$return .= $this->_suffix();
					$return .= $this->generate($result['children'], $settings);
					if ($itemType) {
						if ($indent) {
							$return .= $whiteSpace . "\t";
						}
						$return .= '</' . $itemType . '>';
					}
				} elseif ($numberOfTotalChildren) {
					$addType = true;
					$stack[] = $row[$right];
				}
			} else {
				if ($itemType) {
					$return .= '</' . $itemType . '>';
				}
				$return .= $this->_suffix();
			}
		}
		/* Cleanup */
		while ($stack) {
			array_pop($stack);
			if ($indent) {
				$whiteSpace = str_repeat("\t", count($stack));
				$return .= "\r\n" . $whiteSpace . "\t";
			}
			if ($type) {
				$return .= '</' . $type . '>';
			}
			if ($itemType) {
				$return .= '</' . $itemType . '>';
			}
		}

		if ($return && $indent) {
			$return .= "\r\n";
		}
		if ($return && $type) {
			if ($indent) {
				$return .= $whiteSpace;
			}
			$return .= '</' . $type . '>';
			if ($indent) {
				$return .= "\r\n";
			}
		}
		return $return;
	}

	/**
	 * AddItemAttribute function
	 *
	 * Called to modify the attributes of the next <item> to be processed
	 * Note that the content of a 'node' is processed before generating its wrapping <item> tag
	 *
	 * @param string $id
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function addItemAttribute($id = '', $key = '', $value = null) {
		if ($value !== null) {
			$this->_itemAttributes[$id][$key] = $value;
		} elseif (!(isset($this->_itemAttributes[$id]) && in_array($key, $this->_itemAttributes[$id]))) {
			$this->_itemAttributes[$id][] = $key;
		}
	}

	/**
	 * AddTypeAttribute function
	 *
	 * Called to modify the attributes of the next <type> to be processed
	 * Note that the content of a 'node' is processed before generating its wrapping <type> tag (if appropriate)
	 * An 'interesting' case is that of a first child with children. To generate the output
	 * <ul> (1)
	 *      <li>XYZ (3)
	 *              <ul> (2)
	 *                      <li>ABC...
	 *                      ...
	 *              </ul>
	 *              ...
	 * The processing order is indicated by the numbers in brackets.
	 * attributes are allways applied to the next type (2) to be generated
	 * to set properties of the holding type - pass 'previous' for the 4th param
	 * i.e.
	 * // Hide children (2)
	 * $tree->addTypeAttribute('style', 'display', 'hidden');
	 * // give top level type (1) a class
	 * $tree->addTypeAttribute('class', 'hasHiddenGrandChildren', null, 'previous');
	 *
	 * @param string $id
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function addTypeAttribute($id = '', $key = '', $value = null, $previousOrNext = 'next') {
		$var = '_typeAttributes';
		$firstChild = isset($this->_settings['firstChild']) ? $this->_settings['firstChild'] : true;
		if ($previousOrNext === 'next' && $firstChild) {
			$var = '_typeAttributesNext';
		}
		if ($value !== null) {
			$this->{$var}[$id][$key] = $value;
		} elseif (!(isset($this->{$var}[$id]) && in_array($key, $this->{$var}[$id]))) {
			$this->{$var}[$id][] = $key;
		}
	}

	/**
	 * SupressChildren method
	 *
	 * @return void
	 */
	public function supressChildren() {
	}

	/**
	 * Suffix method.
	 *
	 * Used to close and reopen a ul/ol to allow easier listings
	 *
	 * @return void
	 */
	protected function _suffix($reset = false) {
		static $_splitCount = 0;
		static $_splitCounter = 0;

		if ($reset) {
			$_splitCount = 0;
			$_splitCounter = 0;
		}
		extract($this->_settings);
		if ($splitDepth || $splitCount) {
			if (!$splitDepth) {
				$_splitCount = $totalNodes / $splitCount;
				$rounded = (int)$_splitCount;
				if ($rounded < $_splitCount) {
					$_splitCount = $rounded + 1;
				}
			} elseif ($depth == $splitDepth - 1) {
				$total = $numberOfDirectChildren ? $numberOfDirectChildren : $numberOfTotalChildren;
				if ($total) {
					$_splitCounter = 0;
					$_splitCount = $total / $splitCount;
					$rounded = (int)$_splitCount;
					if ($rounded < $_splitCount) {
						$_splitCount = $rounded + 1;
					}
				}
			}
			if (!$splitDepth || $depth == $splitDepth) {
				$_splitCounter++;
				if ($type && ($_splitCounter % $_splitCount) === 0 && !$lastChild) {
					unset ($this->_settings['callback']);
					return '</' . $type . '><' . $type . '>';
				}
			}
		}
	}

	/**
	 * Attributes function.
	 *
	 * Logic to apply styles to tags.
	 *
	 * @param mixed $rType
	 * @param array $elementData
	 * @return void
	 */
	protected function _attributes($rType, $elementData = array(), $clear = true) {
		extract($this->_settings);
		if ($rType == $type) {
			$attributes = $this->_typeAttributes;
			if ($clear) {
				$this->_typeAttributes = $this->_typeAttributesNext;
				$this->_typeAttributesNext = array();
			}
		} else {
			$attributes = $this->_itemAttributes;
			$this->_itemAttributes = array();
			if ($clear) {
				$this->_itemAttributes = array();
			}
		}
		if ($rType == $itemType && $elementData['activePathElement']) {
			if ($elementData['activePathElement'] === true) {
				$attributes['class'][] = $autoPath[2];
			} else {
				$attributes['class'][] = $elementData['activePathElement'];
			}
		}
		if (!$attributes) {
			return '';
		}
		foreach ($attributes as $type => $values) {
			foreach ($values as $key => $val) {
				if (is_array($val)) {
					$attributes[$type][$key] = '';
					foreach ($val as $vKey => $v) {
						$attributes[$type][$key][$vKey] .= $vKey . ':' . $v;
					}
					$attributes[$type][$key] = implode(';', $attributes[$type][$key]);
				}
				if (is_string($key)) {
					$attributes[$type][$key] = $key . ':' . $val . ';';
				}
			}
			$attributes[$type] = $type . '="' . implode(' ', $attributes[$type]) . '"';
		}
		return ' ' . implode(' ', $attributes);
	}

	/**
	 * Mark unrelated records as hidden using `'hide' => 1`.
	 * In the callback or element you can then return early in this case.
	 *
	 * @param array $tree
	 * @param array $treePath
	 * @param integer $level
	 * @return void
	 * @throws CakeException
	 */
	protected function _markUnrelatedAsHidden(&$tree, $path, $level = 0) {
		extract($this->_settings);
		$siblingIsActive = false;
		foreach ($tree as $key => &$subTree) {
			if (!isset($subTree['children'])) {
				throw new CakeException('Only workes with threaded (nested children) results');
			}

			if (!empty($path[$level]) && $subTree[$model]['id'] == $path[$level][$model]['id']) {
				$subTree[$model]['show'] = 1;
				$siblingIsActive = true;
			}
			if (!empty($subTree[$model]['show'])) {
				foreach ($subTree['children'] as &$v) {
					$v[$model]['parent_show'] = 1;
				}
			}
			if (is_numeric($hideUnrelated) && $hideUnrelated > $level) {
				$siblingIsActive = true;
			}
		}
		foreach ($tree as $key => &$subTree) {
			if ($level && !$siblingIsActive && !isset($subTree[$model]['parent_show'])) {
				$subTree[$model]['hide'] = 1;
			}
			$this->_markUnrelatedAsHidden($subTree['children'], $path, $level + 1);
		}
	}

}
