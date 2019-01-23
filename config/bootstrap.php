<?php

if (!defined('FORMAT_DB_DATETIME')) {
	define('FORMAT_DB_DATETIME', 'Y-m-d H:i:s');
}

if (!defined('FORMAT_DB_DATE')) {
	define('FORMAT_DB_DATE', 'Y-m-d');
}

if (!defined('FORMAT_DB_TIME')) {
	define('FORMAT_DB_TIME', 'H:i:s');
}

if (!defined('FORMAT_NICE_YMDHMS')) {
	define('FORMAT_NICE_YMDHMS', 'd.m.Y, H:i:s');
	define('FORMAT_NICE_YMDHM', 'd.m.Y, H:i');
	define('FORMAT_NICE_YM', 'm.Y');
	define('FORMAT_NICE_YMD', 'd.m.Y');
	define('FORMAT_NICE_MD', 'd.m.');
	define('FORMAT_NICE_D', 'd'); # xx
	define('FORMAT_NICE_W_NUM', 'w'); # xx (0=sunday to 6=saturday)
	define('FORMAT_NICE_W_ABBR', 'D'); # needs manual translation
	define('FORMAT_NICE_W_FULL', 'l'); # needs manual translation
	define('FORMAT_NICE_M', 'm'); # xx
	define('FORMAT_NICE_M_ABBR', 'M'); # needs manual translation
	define('FORMAT_NICE_M_FULL', 'F'); # needs manual translation
	define('FORMAT_NICE_Y_ABBR', 'y'); # xx
	define('FORMAT_NICE_Y', 'Y'); # xxxx
	define('FORMAT_NICE_HM', 'H:i');
	define('FORMAT_NICE_HMS', 'H:i:s');

	// localDate strings
	define('FORMAT_LOCAL_WA_YMDHMS', '%a, %d.%m.%Y, %H:%M:%S');
	define('FORMAT_LOCAL_WF_YMDHMS', '%A, %d.%m.%Y, %H:%M:%S');
	define('FORMAT_LOCAL_WA_YMDHM', '%a, %d.%m.%Y, %H:%M');
	define('FORMAT_LOCAL_WF_YMDHM', '%A, %d.%m.%Y, %H:%M');

	define('FORMAT_LOCAL_YMDHMS', '%d.%m.%Y, %H:%M:%S');
	define('FORMAT_LOCAL_YMDHM', '%d.%m.%Y, %H:%M');
	define('FORMAT_LOCAL_YMD', '%d.%m.%Y');
	define('FORMAT_LOCAL_MD', '%d.%m.');
	define('FORMAT_LOCAL_D', '%d'); # xx
	define('FORMAT_LOCAL_W_NUM', '%w'); # xx (0=sunday to 6=saturday)
	define('FORMAT_LOCAL_W_ABBR', '%a'); # needs translation
	define('FORMAT_LOCAL_W_FULL', '%A'); # needs translation
	define('FORMAT_LOCAL_M', '%m'); # xx
	define('FORMAT_LOCAL_M_ABBR', '%b'); # needs translation
	define('FORMAT_LOCAL_M_FULL', '%B'); # needs translation
	define('FORMAT_LOCAL_Y_ABBR', '%y'); # xx
	define('FORMAT_LOCAL_YMS_ABBR', '%d.%m.%y');
	define('FORMAT_LOCAL_Y', '%Y'); # xxxx
	define('FORMAT_LOCAL_H', '%H');
	define('FORMAT_LOCAL_S', '%S');
	define('FORMAT_LOCAL_HM', '%H:%M');
	define('FORMAT_LOCAL_HMS', '%H:%M:%S');
}

// Make the app and L10n play nice with Windows.
if (!defined('WINDOWS')) {
	if (DS === '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

/**
 * Convenience function to check on "empty()"
 *
 * @param mixed $var
 * @return bool Result
 */

if (!function_exists('isEmpty')) {
	function isEmpty($var = null) {
		if (empty($var)) {
			return true;
		}
		return false;
	}
}

/**
 * Returns of what type the specific value is
 *
 * //TODO: use Debugger::exportVar() instead?
 *
 * @param mixed $value
 * @return mixed Type (NULL, array, bool, float, int, string, object, unknown) + value
 */

if (!function_exists('returns')) {
	function returns($value) {
		if ($value === null) {
			return 'NULL';
		}
		if (is_array($value)) {
			return '(array)' . '<pre>' . print_r($value, true) . '</pre>';
		}
		if ($value === true) {
			return '(bool)TRUE';
		}
		if ($value === false) {
			return '(bool)FALSE';
		}
		if (is_numeric($value) && is_float($value)) {
			return '(float)' . $value;
		}
		if (is_numeric($value) && is_int($value)) {
			return '(int)' . $value;
		}
		if (is_string($value)) {
			return '(string)' . $value;
		}
		if (is_object($value)) {
			return '(object)' . get_class($value) . '<pre>' . print_r($value, true) .
				'</pre>';
		}

		return '(unknown)' . $value;
	}
}

/**
 * Returns htmlentities - string
 *
 * ENT_COMPAT	= Will convert double-quotes and leave single-quotes alone.
 * ENT_QUOTES	= Will convert both double and single quotes. !!!
 * ENT_NOQUOTES = Will leave both double and single quotes unconverted.
 *
 * @param string $text
 * @return string Converted text
 */

if (!function_exists('ent')) {
	function ent($text) {
		return !empty($text) ? htmlentities($text, ENT_QUOTES, 'UTF-8') : '';
	}
}

/**
 * Convenience method for htmlspecialchars_decode
 *
 * @param string $text Text to wrap through htmlspecialchars_decode
 * @param int $quoteStyle
 * @return string Converted text
 */

if (!function_exists('hDec')) {
	function hDec($text, $quoteStyle = ENT_QUOTES) {
		if (is_array($text)) {
			return array_map('hDec', $text);
		}

		return trim(htmlspecialchars_decode($text, $quoteStyle));
	}
}

/**
 * Convenience method for html_entity_decode
 *
 * @param string $text Text to wrap through htmlspecialchars_decode
 * @param int $quoteStyle
 * @return string Converted text
 */

if (!function_exists('entDec')) {
	function entDec($text, $quoteStyle = ENT_QUOTES) {
		if (is_array($text)) {
			return array_map('entDec', $text);
		}

		return !empty($text) ? trim(html_entity_decode($text, $quoteStyle, 'UTF-8')) : '';
	}
}

/**
 * Focus is on the filename (without path)
 *
 * @deprecated Use native method instead
 *
 * @param string $filename to check on
 * @param string|null $type (extension/ext, filename/file, basename/base, dirname/dir)
 * @return mixed
 */

if (!function_exists('extractFileInfo')) {
	function extractFileInfo($filename, $type = null) {
		$info = extractPathInfo($filename, $type);
		if ($info) {
			return $info;
		}
		$pos = strrpos($filename, '.');
		$res = '';
		switch ($type) {
			case 'extension':
			case 'ext':
				$res = ($pos !== false) ? substr($filename, $pos + 1) : '';
				break;
			case 'filename':
			case 'file':
				$res = ($pos !== false) ? substr($filename, 0, $pos) : '';
				break;
			default:
				break;
		}

		return $res;
	}
}

/**
 * Uses native PHP function to retrieve infos about a filename etc.
 * Improves it by not returning non-file-name characters from url files if specified.
 * So "filename.ext?foo=bar#hash" would simply be "filename.ext" then.
 *
 * @deprecated Use native method instead
 *
 * @param string $filename to check on
 * @param string|null $type (extension/ext, filename/file, basename/base, dirname/dir)
 * @param bool $fromUrl
 * @return mixed
 */

if (!function_exists('extractPathInfo')) {
	function extractPathInfo($filename, $type = null, $fromUrl = false) {
		switch ($type) {
			case 'extension':
			case 'ext':
				$infoType = PATHINFO_EXTENSION;
				break;
			case 'filename':
			case 'file':
				$infoType = PATHINFO_FILENAME;
				break;
			case 'basename':
			case 'base':
				$infoType = PATHINFO_BASENAME;
				break;
			case 'dirname':
			case 'dir':
				$infoType = PATHINFO_DIRNAME;
				break;
			default:
				$infoType = $type;
		}
		$result = pathinfo($filename, $infoType);
		if ($fromUrl) {
			if (($pos = strpos($result, '#')) !== false) {
				$result = substr($result, 0, $pos);
			}
			if (($pos = strpos($result, '?')) !== false) {
				$result = substr($result, 0, $pos);
			}
		}

		return $result;
	}
}

/**
 * Shows pr() messages, even with debug = 0.
 * Also allows additional customization.
 *
 * @param mixed $var
 * @param bool $collapsedAndExpandable
 * @param array $options
 * - class, showHtml, showFrom, jquery, returns, debug
 * @return string HTML
 */

if (!function_exists('pre')) {
	function pre($var, $collapsedAndExpandable = false, $options = []) {
		$defaults = [
			'class' => 'cake-debug',
			'showHtml' => false, // Escape < and > (or manually escape with h() prior to calling this function)
			'showFrom' => false, // Display file + line
			'jquery' => null, // null => Auto - use jQuery (true/false to manually decide),
			'debug' => false, // Show only with debug > 0
		];
		$options += $defaults;
		if ($options['debug'] && !Configure::read('debug')) {
			return '';
		}
		if (PHP_SAPI === 'cli') {
			return sprintf("\n%s\n", print_r($var, true));
		}

		$res = '<div class="' . $options['class'] . '">';

		$pre = '';
		if ($collapsedAndExpandable) {
			$js = 'if (this.parentNode.getElementsByTagName(\'pre\')[0].style.display==\'block\') {this.parentNode.getElementsByTagName(\'pre\')[0].style.display=\'none\'} else {this.parentNode.getElementsByTagName(\'pre\')[0].style.display=\'block\'}';
			$jsJquery = 'jQuery(this).parent().children(\'pre\').slideToggle(\'fast\')';
			if ($options['jquery'] === true) {
				$js = $jsJquery;
			} elseif ($options['jquery'] !== false) {
				// auto
				$js = 'if (typeof jQuery == \'undefined\') {' . $js . '} else {' . $jsJquery . '}';
			}
			$res .= '<span onclick="' . $js . '" style="cursor: pointer; font-weight: bold">Debug</span>';
			if ($options['showFrom']) {
				$calledFrom = debug_backtrace();
				$from = '<em>' . substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1) . '</em>';
				$from .= ' (line <em>' . $calledFrom[0]['line'] . '</em>)';
				$res .= '<div>' . $from . '</div>';
			}
			$pre = ' style="display: none"';
		}

		$var = print_r($var, true);
		if (!$options['showHtml']) {
			$var = h($var);
		}

		$res .= '<pre' . $pre . '>' . $var . '</pre>';
		$res .= '</div>';

		return $res;
	}
}

/**
 * Checks if the string [$haystack] contains [$needle]
 *
 * @param string $haystack Input string.
 * @param string $needle Needed char or string.
 * @param bool $caseSensitive
 * @return bool
 */

if (!function_exists('contains')) {
	function contains($haystack, $needle, $caseSensitive = false) {
		$result = !$caseSensitive ? stripos($haystack, $needle) : strpos($haystack, $needle);

		return $result !== false;
	}
}

/**
 * Checks if the string [$haystack] starts with [$needle]
 *
 * @param string $haystack Input string.
 * @param string $needle Needed char or string.
 * @param bool $caseSensitive
 * @return bool
 */

if (!function_exists('startsWith')) {
	function startsWith($haystack, $needle, $caseSensitive = false) {
		if ($caseSensitive) {
			return mb_strpos($haystack, $needle) === 0;
		}

		return mb_stripos($haystack, $needle) === 0;
	}
}

/**
 * Checks if the String [$haystack] ends with [$needle]
 *
 * @param string $haystack Input string.
 * @param string $needle Needed char or string
 * @param bool $caseSensitive
 * @return bool
 */

if (!function_exists('endsWith')) {
	function endsWith($haystack, $needle, $caseSensitive = false) {
		if ($caseSensitive) {
			return mb_strrpos($haystack, $needle) === mb_strlen($haystack) - mb_strlen($needle);
		}

		return mb_strripos($haystack, $needle) === mb_strlen($haystack) - mb_strlen($needle);
	}
}
