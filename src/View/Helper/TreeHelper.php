<?php

/**
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2008, Andy Dawson
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Exception;

/**
 * Helper to generate tree representations of MPTT or recursively nested data.
 *
 * @deprecated Use https://github.com/ADmad/cakephp-tree instead.
 * @author Andy Dawson
 * @author Mark Scherer
 * @link http://www.dereuromark.de/2013/02/17/cakephp-and-tree-structures/
 */
class TreeHelper extends Helper {

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
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
		'treePath' => [],
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
	];

	/**
	 * Config settings property
	 *
	 * @var array
	 */
	protected $_config = [];

	/**
	 * TypeAttributes property
	 *
	 * @var array
	 */
	protected $_typeAttributes = [];

	/**
	 * TypeAttributesNext property
	 *
	 * @var array
	 */
	protected $_typeAttributesNext = [];

	/**
	 * ItemAttributes property
	 *
	 * @var array
	 */
	protected $_itemAttributes = [];

	/**
	 * Tree generation method.
	 *
	 * Accepts the results of
	 *     find('all', array('fields' => array('lft', 'rght', 'whatever'), 'order' => 'lft ASC'));
	 *     children(); // if you have the tree behavior of course!
	 * or find('threaded'); and generates a tree structure of the data.
	 *
	 * Settings (2nd parameter):
	 *    'model' => name of the model (key) to look for in the data array. defaults to the first model for the current
	 * controller. If set to false 2d arrays will be allowed/expected.
	 *    'alias' => the array key to output for a simple ul (not used if element or callback is specified)
	 *    'type' => type of output defaults to ul
	 *    'itemType => type of item output default to li
	 *    'id' => id for top level 'type'
	 *    'class' => class for top level 'type'
	 *    'element' => path to an element to render to get node contents.
	 *    'callback' => callback to use to get node contents. e.g. array(&$anObject, 'methodName') or 'floatingMethod'
	 *    'autoPath' => array($left, $right [$classToAdd = 'active']) if set any item in the path will have the class $classToAdd added. MPTT only.
	 *  'hideUnrelated' => if unrelated (not children, not siblings) should be hidden, needs 'treePath', true/false or array/string for callback
	 *  'treePath' => treePath to insert into callback/element
	 *    'left' => name of the 'lft' field if not lft. only applies to MPTT data
	 *    'right' => name of the 'rght' field if not rght. only applies to MPTT data
	 *    'depth' => used internally when running recursively, can be used to override the depth in either mode.
	 *  'maxDepth' => used to control the depth upto which to generate tree
	 *    'firstChild' => used internally when running recursively.
	 *    'splitDepth' => if multiple "parallel" types are required, instead of one big type, nominate the depth to do so here
	 *        example: useful if you have 30 items to display, and you'd prefer they appeared in the source as 3 lists of 10 to be able to
	 *        style/float them.
	 *    'splitCount' => the number of "parallel" types. defaults to null (disabled) set the splitCount,
	 *        and optionally set the splitDepth to get parallel lists
	 *
	 * @param array|\Cake\Orm\Query $data Data to loop over
	 * @param array $config Config
	 * @return string HTML representation of the passed data
	 * @throws \Exception
	 */
	public function generate($data, array $config = []) {
		if (is_object($data)) {
			$data = $data->toArray();
		}
		if (!$data) {
			return '';
		}

		$this->_config = $config + $this->_defaultConfig;
		if ($this->_config['autoPath'] && !isset($this->_config['autoPath'][2])) {
			$this->_config['autoPath'][2] = 'active';
		}
		extract($this->_config);
		if ($indent === null && Configure::read('debug')) {
			$indent = true;
		}

		$this->_itemAttributes = $this->_typeAttributes = $this->_typeAttributesNext = [];
		$stack = [];
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
		$this->_config['totalNodes'] = count($data);
		$keys = array_keys($data);

		if ($hideUnrelated === true || is_numeric($hideUnrelated)) {
			$this->_markUnrelatedAsHidden($data, $treePath);
		} elseif ($hideUnrelated && is_callable($hideUnrelated)) {
			call_user_func($hideUnrelated, $data, $treePath);
		}

		foreach ($data as $i => &$result) {
			/* Allow 2d data arrays */
			if (is_object($result)) {
				$result = $result->toArray();
			}
			if ($model && isset($result->$model)) {
				$row = &$result->$model;
			} else {
				$row = &$result;
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
				if ($key === count($keys) - 1) {
					$lastChild = true;
				}
			} elseif (isset($row[$left])) {
				if ($row[$left] != ($row[$right] - 1) && $depth < $maxDepth) {
					$hasChildren = true;
					$numberOfTotalChildren = ($row[$right] - $row[$left] - 1) / 2;
					if (isset($data[$i + 1]) && $data[$i + 1][$right] < $row[$right]) {
						$hasVisibleChildren = true;
					}
				}
				if (!isset($data[$i - 1]) || ($data[$i - 1][$left] == ($row[$left] - 1))) {
					$firstChild = true;
				}
				if (!isset($data[$i + 1]) || ($stack && $stack[count($stack) - 1] == ($row[$right] + 1))) {
					$lastChild = true;
				}
			} else {
				throw new Exception('Invalid Tree Structure');
			}

			$activePathElement = null;
			if ($autoPath) {
				if ($result[$left] <= $autoPath[0] && $result[$right] >= $autoPath[1]) {
					$activePathElement = true;
				} elseif (isset($autoPath[3]) && $result[$left] == $autoPath[0]) {
					$activePathElement = $autoPath[3];
				} else {
					$activePathElement = false;
				}
			}

			$depth = $depth ? $depth : count($stack);

			$elementData = [
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
			];

			if ($elementData['isSibling'] && $hideUnrelated) {
				$result['children'] = [];
			}

			$this->_config = $elementData + $this->_config;
			if ($this->_config['fullSettings']) {
				$elementData = $this->_config;
			}

			/* Main Content */
			if ($element) {
				$content = $this->_View->element($element, $elementData);
			} elseif ($callback) {
				list($content) = array_map($callback, [$elementData]);
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
					$typeAttributes = $this->_attributes($type, ['data' => $elementData]);
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
					$config['depth'] = $depth + 1;
					$return .= $this->_suffix();
					$return .= $this->generate($result['children'], $config);
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
	 * @param mixed|null $value
	 * @param string $previousOrNext
	 * @return void
	 */
	public function addTypeAttribute($id = '', $key = '', $value = null, $previousOrNext = 'next') {
		$var = '_typeAttributes';
		$firstChild = isset($this->_config['firstChild']) ? $this->_config['firstChild'] : true;
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
	 * Suffix method.
	 *
	 * Used to close and reopen a ul/ol to allow easier listings
	 *
	 * @param bool $reset
	 * @return string
	 */
	protected function _suffix($reset = false) {
		static $_splitCount = 0;
		static $_splitCounter = 0;

		if ($reset) {
			$_splitCount = 0;
			$_splitCounter = 0;
		}
		extract($this->_config);
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
					unset($this->_config['callback']);
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
	 * @param string $rType
	 * @param array $elementData
	 * @param bool $clear
	 * @return string
	 */
	protected function _attributes($rType, array $elementData = [], $clear = true) {
		extract($this->_config);
		if ($rType === $type) {
			$attributes = $this->_typeAttributes;
			if ($clear) {
				$this->_typeAttributes = $this->_typeAttributesNext;
				$this->_typeAttributesNext = [];
			}
		} else {
			$attributes = $this->_itemAttributes;
			$this->_itemAttributes = [];
			if ($clear) {
				$this->_itemAttributes = [];
			}
		}
		if ($rType === $itemType && $elementData['activePathElement']) {
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
	 * @param array $tree Tree
	 * @param array $path Tree path
	 * @param int $level
	 * @return void
	 * @throws \Exception
	 */
	protected function _markUnrelatedAsHidden(&$tree, array $path, $level = 0) {
		extract($this->_config);
		$siblingIsActive = false;
		foreach ($tree as $key => &$subTree) {
			if (is_object($subTree)) {
				$subTree = $subTree->toArray();
			}
			if (!isset($subTree['children'])) {
				throw new Exception('Only works with threaded (nested children) results');
			}

			if (!empty($path[$level]) && $subTree['id'] == $path[$level]['id']) {
				$subTree['show'] = 1;
				$siblingIsActive = true;
			}
			if (!empty($subTree['show'])) {
				foreach ($subTree['children'] as &$v) {
					$v['parent_show'] = 1;
				}
			}
			if (is_numeric($hideUnrelated) && $hideUnrelated > $level) {
				$siblingIsActive = true;
			}
		}
		foreach ($tree as $key => &$subTree) {
			if ($level && !$siblingIsActive && !isset($subTree['parent_show'])) {
				$subTree['hide'] = 1;
			}
			$this->_markUnrelatedAsHidden($subTree['children'], $path, $level + 1);
		}
	}

}
