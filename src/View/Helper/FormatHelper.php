<?php
namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\View\Helper\TextHelper;
use Cake\View\StringTemplate;
use Cake\View\View;

/**
 * Format helper with basic html snippets
 *
 * TODO: make snippets more "css and background image" (instead of inline img links)
 *
 * @author Mark Scherer
 * @license MIT
 */
class FormatHelper extends TextHelper {

	/**
	 * Other helpers used by FormHelper
	 *
	 * @var array
	 */
	public $helpers = ['Html', 'Tools.Numeric'];

	public $template;

	protected $_defaults = [
		'fontIcons' => false, // Defaults to false for BC
		'iconNamespace' => 'fa',  // Used to be icon
	];

	public function __construct(View $View, array $config = []) {
		$config += $this->_defaults;

		if ($config['fontIcons'] === true) {
			$config['fontIcons'] = (array)Configure::read('Format.fontIcons');
			if ($namespace = Configure::read('Format.iconNamespace')) {
				$config['iconNamespace'] = $namespace;
			}
		}

		$templates = [
			'icon' => '<i class="{{class}}" title="{{title}}" data-placement="bottom" data-toggle="tooltip"></i>',
		] + (array)Configure::read('Format.templates');
		if (!isset($this->template)) {
			$this->template = new StringTemplate($templates);
		}

		parent::__construct($View, $config);
	}

	/**
	 * jqueryAccess: {id}Pro, {id}Contra
	 *
	 * @return string
	 */
	public function thumbs($id, $inactive = false, $inactiveTitle = null) {
		$status = 'Active';
		$upTitle = __d('tools', 'consentThis');
		$downTitle = __d('tools', 'dissentThis');
		if ($inactive === true) {
			$status = 'Inactive';
			$upTitle = $downTitle = !empty($inactiveTitle) ? $inactiveTitle : __d('tools', 'alreadyVoted');
		}

		if ($this->_config['fontIcons']) {
			// TODO: Return proper font icons
			// fa-thumbs-down
			// fa-thumbs-up
		}

		$ret = '<div class="thumbsUpDown">';
		$ret .= '<div id="' . $id . 'Pro' . $status . '" rel="' . $id . '" class="thumbUp up' . $status . '" title="' . $upTitle . '"></div>';
		$ret .= '<div id="' . $id . 'Contra' . $status . '" rel="' . $id . '" class="thumbDown down' . $status . '" title="' . $downTitle . '"></div>';
		$ret .= '<br class="clear"/>';
		$ret .=	'</div>';
		return $ret;
	}

	/**
	 * Display neighbor quicklinks
	 *
	 * @param array $neighbors (containing prev and next)
	 * @param string $field: just field or Model.field syntax
	 * @param array $options:
	 * - name: title name: next{Record} (if none is provided, "record" is used - not translated!)
	 * - slug: true/false (defaults to false)
	 * - titleField: field or Model.field
	 * @return string
	 */
	public function neighbors($neighbors, $field, $options = []) {
		if (mb_strpos($field, '.') !== false) {
			$fieldArray = explode('.', $field, 2);
			$alias = $fieldArray[0];
			$field = $fieldArray[1];
		}

		if (empty($alias)) {
			if (!empty($neighbors['prev'])) {
				$modelNames = array_keys($neighbors['prev']);
				$alias = $modelNames[0];
			} elseif (!empty($neighbors['next'])) {
				$modelNames = array_keys($neighbors['next']);
				$alias = $modelNames[0];
			}
		}
		if (empty($field)) {
		}

		$name = 'Record'; // Translation further down!
		if (!empty($options['name'])) {
			$name = ucfirst($options['name']);
		}

		$prevSlug = $nextSlug = null;
		if (!empty($options['slug'])) {
			if (!empty($neighbors['prev'])) {
				$prevSlug = Inflector::slug($neighbors['prev'][$alias][$field], '-');
			}
			if (!empty($neighbors['next'])) {
				$nextSlug = Inflector::slug($neighbors['next'][$alias][$field], '-');
			}
		}
		$titleAlias = $alias;
		$titleField = $field;
		if (!empty($options['titleField'])) {
			if (mb_strpos($options['titleField'], '.') !== false) {
				$fieldArray = explode('.', $options['titleField'], 2);
				$titleAlias = $fieldArray[0];
				$titleField = $fieldArray[1];
			} else {
				$titleField = $options['titleField'];
			}
		}
		if (!isset($options['escape']) || $options['escape'] === false) {
			$titleField = h($titleField);
		}

		$ret = '<div class="next-prev-navi nextPrevNavi">';
		if (!empty($neighbors['prev'])) {
			$url = [$neighbors['prev'][$alias]['id'], $prevSlug];
			if (!empty($options['url'])) {
				$url += $options['url'];
			}

			$ret .= $this->Html->link($this->cIcon(ICON_PREV, false) . '&nbsp;' . __d('tools', 'prev' . $name), $url, ['escape' => false, 'title' => $neighbors['prev'][$titleAlias][$titleField]]);
		} else {
			$ret .= $this->cIcon(ICON_PREV_DISABLED, __d('tools', 'noPrev' . $name)) . '&nbsp;' . __d('tools', 'prev' . $name);
		}
		$ret .= '&nbsp;&nbsp;';
		if (!empty($neighbors['next'])) {
			$url = [$neighbors['next'][$alias]['id'], $prevSlug];
			if (!empty($options['url'])) {
				$url += $options['url'];
			}

			$ret .= $this->Html->link($this->cIcon(ICON_NEXT, false) . '&nbsp;' . __d('tools', 'next' . $name), $url, ['escape' => false, 'title' => $neighbors['next'][$titleAlias][$titleField]]);
		} else {
			$ret .= $this->cIcon(ICON_NEXT_DISABLED, __d('tools', 'noNext' . $name)) . '&nbsp;' . __d('tools', 'next' . $name);
		}
		$ret .= '</div>';
		return $ret;
	}

	const GENDER_FEMALE = 2;
	const GENDER_MALE = 1;

	/**
	 * Displays gender icon
	 *
	 * @return string
	 */
	public function genderIcon($value = null) {
		$value = (int)$value;
		if ($value == static::GENDER_FEMALE) {
			$icon =	$this->icon('genderFemale', null, null, null, ['class' => 'gender']);
		} elseif ($value == static::GENDER_MALE) {
			$icon =	$this->icon('genderMale', null, null, null, ['class' => 'gender']);
		} else {
			$icon =	$this->icon('genderUnknown', null, null, null, ['class' => 'gender']);
		}
		return $icon;
	}

	/**
	 * Display a font icon (fast and resource-efficient).
	 * Uses http://fontawesome.io/icons/
	 *
	 * Options:
	 * - size (int|string: 1...5 or large)
	 * - rotate (integer: 90, 270, ...)
	 * - spin (booelan: true/false)
	 * - extra (array: muted, light, dark, border)
	 * - pull (string: left, right)
	 *
	 * @param string|array $icon
	 * @param array $options
	 * @return string
	 */
	public function fontIcon($icon, array $options = [], array $attributes = []) {
		$defaults = [
			'namespace' => $this->_config['iconNamespace']
		];
		$options += $defaults;
		$icon = (array)$icon;
		$class = [];
		foreach ($icon as $i) {
			$class[] = $options['namespace'] . '-' . $i;
		}
		if (!empty($options['extra'])) {
			foreach ($options['extra'] as $i) {
				$class[] = $options['namespace'] . '-' . $i;
			}
		}
		if (!empty($options['size'])) {
			$class[] = $options['namespace'] . '-' . ($options['size'] === 'large' ? 'large' : $options['size'] . 'x');
		}
		if (!empty($options['pull'])) {
			$class[] = 'pull-' . $options['pull'];
		}
		if (!empty($options['rotate'])) {
			$class[] = $options['namespace'] . '-rotate-' . (int)$options['rotate'];
		}
		if (!empty($options['spin'])) {
			$class[] = $options['namespace'] . '-spin';
		}
		return '<i class="' . implode(' ', $class) . '"></i>';
	}

	/**
	 * Quick way of printing default icons
	 *
	 * @todo refactor to $type, $options, $attributes
	 *
	 * @param type
	 * @param title
	 * @param alt (set to FALSE if no alt is supposed to be shown)
	 * @param bool automagic i18n translate [default true = __('xyz')]
	 * @param options array ('class'=>'','width/height'=>'','onclick=>'') etc
	 * @return string
	 */
	public function icon($type, $t = null, $a = null, $translate = null, $options = []) {
		if (isset($t) && $t === false) {
			$title = '';
		} else {
			$title = $t;
		}

		if (isset($a) && $a === false) {
			$alt = '';
		} else {
			$alt = $a;
		}

		if (!$this->_config['fontIcons'] || !isset($this->_config['fontIcons'][$type])) {
			if (array_key_exists($type, $this->icons)) {
				$pic = $this->icons[$type]['pic'];
				$title = (isset($title) ? $title : $this->icons[$type]['title']);
				$alt = (isset($alt) ? $alt : preg_replace('/[^a-zA-Z0-9]/', '', $this->icons[$type]['title']));
				if ($translate !== false) {
					$title = __($title);
					$alt = __($alt);
				}
				if ($alt) {
					$alt = '[' . $alt . ']';
				}
			} else {
				$pic = 'pixelspace.gif';
			}
			$defaults = ['title' => $title, 'alt' => $alt, 'class' => 'icon'];
			$newOptions = $options + $defaults;

			return $this->Html->image('icons/' . $pic, $newOptions);
		}

		$options['title'] = $title;
		$options['translate'] = $translate;
		return $this->_fontIcon($type, $options);
	}

	/**
	 * Custom Icons
	 *
	 * @param string $icon (constant or filename)
	 * @param array $options:
	 * - translate, ...
	 * @param array $attributes:
	 * - title, alt, ...
	 * THE REST IS DEPRECATED
	 * @return string
	 */
	public function cIcon($icon, $t = null, $a = null, $translate = true, $options = []) {
		if (is_array($t)) {
			$translate = isset($t['translate']) ? $t['translate'] : true;
			$options = (array)$a;
			$a = isset($t['alt']) ? $t['alt'] : null; // deprecated
			$t = isset($t['title']) ? $t['title'] : null; // deprecated
		}

		$type = pathinfo($icon, PATHINFO_FILENAME);

		if (!$this->_config['fontIcons'] || !isset($this->_config['fontIcons'][$type])) {
			$title = isset($t) ? $t : ucfirst($type);
			$alt = (isset($a) ? $a : Inflector::slug($title));
			if ($translate !== false) {
				$title = __($title);
				$alt = __($alt);
			}
			if ($alt) {
				$alt = '[' . $alt . ']';
			}
			$defaults = ['title' => $title, 'alt' => $alt, 'class' => 'icon'];
			$options += $defaults;
			if (substr($icon, 0, 1) !== '/') {
				$icon = 'icons/' . $icon;
			}
			return $this->Html->image($icon, $options);
		}

		$options['title'] = $t;
		$options['translate'] = $translate;
		return $this->_fontIcon($type, $options);
	}

	/**
	 * FormatHelper::_fontIcon()
	 *
	 * @param string $type
	 * @param array $options
	 * @return string
	 */
	protected function _fontIcon($type, $options) {
		$iconType = $this->_config['fontIcons'][$type];

		$defaults = [
			'class' => $iconType . ' ' . $type
		];
		$options += $defaults;

		if (!isset($options['title'])) {
			$options['title'] = ucfirst($type);
			if ($options['translate'] !== false) {
				$options['title'] = __($options['title']);
			}
		}

		return $this->template->format('icon', $options);
	}

	/**
	 * Display yes/no symbol.
	 *
	 * @todo $on=1, $text=false, $ontitle=false,... => in array(OPTIONS)
	 *
	 * @param text: default FALSE; if TRUE, text instead of the image
	 * @param ontitle: default FALSE; if it is embadded in a link, set to TRUE
	 * @return image:Yes/No or text:Yes/No
	 */
	public function yesNo($v, $ontitle = null, $offtitle = null, $on = 1, $text = false, $notitle = false) {
		$ontitle = (!empty($ontitle) ? $ontitle : __d('tools', 'Yes'));
		$offtitle = (!empty($offtitle) ? $offtitle : __d('tools', 'No'));
		$sbez = ['0' => @substr($offtitle, 0, 1), '1' => @substr($ontitle, 0, 1)];
		$bez = ['0' => $offtitle, '1' => $ontitle];

		if ($v == $on) {
			$icon = ICON_YES;
			$value = 1;
		} else {
			$icon = ICON_NO;
			$value = 0;
		}

		if ($text !== false) {
			return $bez[$value];
		}

		$options = ['title' => ($ontitle === false ? '' : $bez[$value]), 'alt' => $sbez[$value], 'class' => 'icon'];

		if ($this->_config['fontIcons']) {
			return $this->cIcon($icon, $options['title']);
		}
		return $this->Html->image('icons/' . $icon, $options);
	}

	/**
	 * Get URL of a png img of a website (16x16 pixel).
	 *
	 * @param string domain
	 * @return string
	 */
	public function siteIconUrl($domain) {
		if (strpos($domain, 'http') === 0) {
			// Strip protocol
			$pieces = parse_url($domain);
			$domain = $pieces['host'];
		}
		return 'http://www.google.com/s2/favicons?domain=' . $domain;
	}

	/**
	 * Display a png img of a website (16x16 pixel)
	 * if not available, will return a fallback image (a globe)
	 *
	 * @param domain (preferably without protocol, e.g. "www.site.com")
	 * @return string
	 */
	public function siteIcon($domain, $options = []) {
		$url = $this->siteIconUrl($domain);
		$options['width'] = 16;
		$options['height'] = 16;
		if (!isset($options['alt'])) {
			$options['alt'] = $domain;
		}
		if (!isset($options['title'])) {
			$options['title'] = $domain;
		}
		return $this->Html->image($url, $options);
	}

	/**
	 * Display a disabled link tag
	 *
	 * @param string $text
	 * @param array $options
	 * @return string
	 */
	public function disabledLink($text, $options = []) {
		$defaults = ['class' => 'disabledLink', 'title' => __d('tools', 'notAvailable')];
		$options += $defaults;

		return $this->Html->tag('span', $text, $options);
	}

	/**
	 * Generates a pagination count: #1 etc for each pagination record
	 * respects order (ASC/DESC)
	 *
	 * @param array $paginator
	 * @param int $count (current post count on this page)
	 * @param string $dir (ASC/DESC)
	 * @return int
	 * @deprecated
	 */
	public function absolutePaginateCount(array $paginator, $count, $dir = null) {
		if ($dir === null) {
			$dir = 'ASC';
		}

		$currentPage = $paginator['page'];
		$pageCount = $paginator['pageCount'];
		$totalCount = $paginator['count'];

		$limit = $paginator['limit'];
		$step = 1; //$paginator['step'];
		//pr($paginator);

		if ($dir === 'DESC') {
			$currentCount = $count + ($pageCount - $currentPage) * $limit * $step;
			if ($currentPage != $pageCount && $pageCount > 1) {
				$currentCount -= $pageCount * $limit * $step - $totalCount;
			}
		} else {
			$currentCount = $count + ($currentPage - 1) * $limit * $step;
		}

		return $currentCount;
	}

	/**
	 * Fixes utf8 problems of native php str_pad function
	 * //TODO: move to textext helper?
	 *
	 * @param string $input
	 * @param int $padLength
	 * @param string $padString
	 * @param mixed $padType
	 * @return string input
	 */
	public function pad($input, $padLength, $padString, $padType = STR_PAD_RIGHT) {
		$length = mb_strlen($input);
		if ($padLength - $length > 0) {
			switch ($padType) {
				case STR_PAD_LEFT:
					$input = str_repeat($padString, $padLength - $length) . $input;
					break;
				case STR_PAD_RIGHT:
					$input .= str_repeat($padString, $padLength - $length);
					break;
			}
		}
		return $input;
	}

	/**
	 * Returns red colored if not ok
	 *
	 * @param string $value
	 * @param $okValue
	 * @return string Value in HTML tags
	 */
	public function warning($value, $ok = false) {
		if (!$ok) {
			return $this->ok($value, false);
		}
		return $value;
	}

	/**
	 * Returns green on ok, red otherwise
	 *
	 * @todo Remove inline css and make classes better: green=>ok red=>not-ok
	 *   Maybe use templating
	 *
	 * @param mixed $currentValue
	 * @param bool $ok: true/false (defaults to false)
	 * //@param string $comparizonType
	 * //@param mixed $okValue
	 * @return string newValue nicely formatted/colored
	 */
	public function ok($value, $ok = false) {
		if ($ok) {
			$value = '<span class="green" style="color:green">' . $value . '</span>';
		} else {
			$value = '<span class="red" style="color:red">' . $value . '</span>';
		}
		return $value;
	}

	/**
	 * Useful for displaying tabbed (code) content when the default of 8 spaces
	 * inside <pre> is too much. This converts it to spaces for better output.
	 *
	 * Inspired by the tab2space function found at:
	 * @see http://aidan.dotgeek.org/lib/?file=function.tab2space.php
	 * @param string $text
	 * @param int $spaces
	 * @return string
	 */
	public function tab2space($text, $spaces = 4) {
		$spaces = str_repeat(" ", $spaces);
		$text = preg_split("/\r\n|\r|\n/", trim($text));
		$wordLengths = [];
		$wArray = [];

		// Store word lengths
		foreach ($text as $line) {
			$words = preg_split("/(\t+)/", $line, -1, PREG_SPLIT_DELIM_CAPTURE);
			foreach (array_keys($words) as $i) {
				$strlen = strlen($words[$i]);
				$add = isset($wordLengths[$i]) && ($wordLengths[$i] < $strlen);
				if ($add || !isset($wordLengths[$i])) {
					$wordLengths[$i] = $strlen;
				}
			}
			$wArray[] = $words;
		}

		$text = '';

		// Apply padding when appropriate and rebuild the string
		foreach (array_keys($wArray) as $i) {
			foreach (array_keys($wArray[$i]) as $ii) {
				if (preg_match("/^\t+$/", $wArray[$i][$ii])) {
					$wArray[$i][$ii] = str_pad($wArray[$i][$ii], $wordLengths[$ii], "\t");
				} else {
					$wArray[$i][$ii] = str_pad($wArray[$i][$ii], $wordLengths[$ii]);
				}
			}
			$text .= str_replace("\t", $spaces, implode("", $wArray[$i])) . "\n";
		}

		return $text;
	}

	/**
	 * Translate a result array into a HTML table
	 *
	 * @todo Move to Text Helper etc.
	 *
	 * @author Aidan Lister <aidan@php.net>
	 * @version 1.3.2
	 * @link http://aidanlister.com/2004/04/converting-arrays-to-human-readable-tables/
	 * @param array $array The result (numericaly keyed, associative inner) array.
	 * @param bool $recursive Recursively generate tables for multi-dimensional arrays
	 * @param string $null String to output for blank cells
	 */
	public function array2table($array, $options = []) {
		$defaults = [
			'null' => '&nbsp;',
			'recursive' => false,
			'heading' => true,
			'escape' => true
		];
		$options += $defaults;

		// Sanity check
		if (empty($array) || !is_array($array)) {
			return false;
		}

		if (!isset($array[0]) || !is_array($array[0])) {
			$array = [$array];
		}

		// Start the table
		$table = "<table>\n";

		if ($options['heading']) {
			// The header
			$table .= "\t<tr>";
			// Take the keys from the first row as the headings
			foreach (array_keys($array[0]) as $heading) {
				$table .= '<th>' . ($options['escape'] ? h($heading) : $heading) . '</th>';
			}
			$table .= "</tr>\n";
		}

		// The body
		foreach ($array as $row) {
			$table .= "\t<tr>";
			foreach ($row as $cell) {
				$table .= '<td>';

				// Cast objects
				if (is_object($cell)) {
					$cell = (array)$cell;
				}

				if ($options['recursive'] && is_array($cell) && !empty($cell)) {
					// Recursive mode
					$table .= "\n" . static::array2table($cell, $options) . "\n";
				} else {
					$table .= (!is_array($cell) && strlen($cell) > 0) ? ($options['escape'] ? h($cell) : $cell) : $options['null'];
				}

				$table .= '</td>';
			}

			$table .= "</tr>\n";
		}

		$table .= '</table>';
		return $table;
	}

	public $icons = [
		'up' => [
			'pic' => ICON_UP,
			'title' => 'Up',
		],
		'down' => [
			'pic' => ICON_DOWN,
			'title' => 'Down',
		],
		'edit' => [
			'pic' => ICON_EDIT,
			'title' => 'Edit',
		],
		'view' => [
			'pic' => ICON_VIEW,
			'title' => 'View',
		],
		'delete' => [
			'pic' => ICON_DELETE,
			'title' => 'Delete',
		],
		'reset' => [
			'pic' => ICON_RESET,
			'title' => 'Reset',
		],
		'help' => [
			'pic' => ICON_HELP,
			'title' => 'Help',
		],
		'loader' => [
			'pic' => 'loader.white.gif',
			'title' => 'Loading...',
		],
		'loader-alt' => [
			'pic' => 'loader.black.gif',
			'title' => 'Loading...',
		],
		'details' => [
			'pic' => ICON_DETAILS,
			'title' => 'Details',
		],
		'use' => [
			'pic' => ICON_USE,
			'title' => 'Use',
		],
		'yes' => [
			'pic' => ICON_YES,
			'title' => 'Yes',
		],
		'no' => [
			'pic' => ICON_NO,
			'title' => 'No',
		],
		// deprecated from here down
		'close' => [
			'pic' => ICON_CLOCK,
			'title' => 'Close',
		],
		'reply' => [
			'pic' => ICON_REPLY,
			'title' => 'Reply',
		],
		'time' => [
			'pic' => ICON_CLOCK,
			'title' => 'Time',
		],
		'check' => [
			'pic' => ICON_CHECK,
			'title' => 'Check',
		],
		'role' => [
			'pic' => ICON_ROLE,
			'title' => 'Role',
		],
		'add' => [
			'pic' => ICON_ADD,
			'title' => 'Add',
		],
		'remove' => [
			'pic' => ICON_REMOVE,
			'title' => 'Remove',
		],
		'email' => [
			'pic' => ICON_EMAIL,
			'title' => 'Email',
		],
		'options' => [
			'pic' => ICON_SETTINGS,
			'title' => 'Options',
		],
		'lock' => [
			'pic' => ICON_LOCK,
			'title' => 'Locked',
		],
		'warning' => [
			'pic' => ICON_WARNING,
			'title' => 'Warning',
		],
		'genderUnknown' => [
			'pic' => 'gender_icon.gif',
			'title' => 'genderUnknown',
		],
		'genderMale' => [
			'pic' => 'gender_icon_m.gif',
			'title' => 'genderMale',
		],
		'genderFemale' => [
			'pic' => 'gender_icon_f.gif',
			'title' => 'genderFemale',
		],
	];

}

// Default icons

if (!defined('ICON_UP')) {
	define('ICON_UP', 'up.gif');
}
if (!defined('ICON_DOWN')) {
	define('ICON_DOWN', 'down.gif');
}
if (!defined('ICON_EDIT')) {
	define('ICON_EDIT', 'edit.gif');
}
if (!defined('ICON_VIEW')) {
	define('ICON_VIEW', 'see.gif');
}
if (!defined('ICON_DELETE')) {
	define('ICON_DELETE', 'delete.gif');
}
if (!defined('ICON_DETAILS')) {
	define('ICON_DETAILS', 'loupe.gif');
}
if (!defined('ICON_OPTIONS')) {
	define('ICON_OPTIONS', 'options.gif');
}
if (!defined('ICON_SETTINGS')) {
	define('ICON_SETTINGS', 'options.gif');
}
if (!defined('ICON_USE')) {
	define('ICON_USE', 'use.gif');
}
if (!defined('ICON_CLOSE')) {
	define('ICON_CLOSE', 'close.gif');
}
if (!defined('ICON_REPLY')) {
	define('ICON_REPLY', 'reply.gif');
}

if (!defined('ICON_RESET')) {
	define('ICON_RESET', 'reset.gif');
}
if (!defined('ICON_HELP')) {
	define('ICON_HELP', 'help.gif');
}
if (!defined('ICON_YES')) {
	define('ICON_YES', 'yes.gif');
}
if (!defined('ICON_NO')) {
	define('ICON_NO', 'no.gif');
}
if (!defined('ICON_CLOCK')) {
	define('ICON_CLOCK', 'clock.gif');
}
if (!defined('ICON_CHECK')) {
	define('ICON_CHECK', 'check.gif');
}
if (!defined('ICON_ROLE')) {
	define('ICON_ROLE', 'role.gif');
}
if (!defined('ICON_ADD')) {
	define('ICON_ADD', 'add.gif');
}
if (!defined('ICON_REMOVE')) {
	define('ICON_REMOVE', 'remove.gif');
}
if (!defined('ICON_EMAIL')) {
	define('ICON_EMAIL', 'email.gif');
}
if (!defined('ICON_LOCK')) {
	define('ICON_LOCK', 'lock.gif');
}
if (!defined('ICON_WARNING')) {
	define('ICON_WARNING', 'warning.png');
}
if (!defined('ICON_MAP')) {
	define('ICON_MAP', 'map.gif');
}
