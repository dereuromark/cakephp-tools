<?php
App::uses('Utility', 'Tools.Utility');

/** BASIC STUFF **/

//use FULL_BASE_URL (cake) instead of http_base?
if (!empty($_SERVER['HTTP_HOST'])) {
	define('HTTP_HOST', $_SERVER['HTTP_HOST']);
	define('HTTP_BASE', 'http://' . HTTP_HOST); //FULL_BASE_URL
} else {
	define('HTTP_HOST', '');
	define('HTTP_BASE', '');
}

if (!empty($_SERVER['PHP_SELF'])) {
	define('HTTP_SELF', '' . $_SERVER['PHP_SELF']);
}
if (!empty($_SERVER['REQUEST_URI'])) {
	define('HTTP_URI', '' . $_SERVER['REQUEST_URI']);
}
if (!empty($_SERVER['HTTP_REFERER'])) {
	define('HTTP_REF', '' . $_SERVER['HTTP_REFERER']);
}

define('CHOWN_PUBLIC', 0770);


# Useful when putting a string together that needs some "pretty" html-doc. source layouting
# Only visible in SOURCE code (not in html layout in the browser)
//define('LF',''); // line feed (depending on the system)
define('LF', PHP_EOL); // line feed (depending on the system): \n or \n\r etc.
# Alternativly NL,CR:
define('NL', "\n"); // new line
define('CR', "\r"); // carriage return

define('TB', "\t"); // tabulator

# Useful for html layouting
# Visible in the Html Layout in the Browser
define('BR', '<br />'); // line break

# Make the app and l10n play nice with Windows.
if (substr(PHP_OS, 0, 3) == 'WIN') { // || strpos(@php_uname(), 'ARCH')
	define('WINDOWS', true);
} else {
	define('WINDOWS', false);
}

define('FORMAT_DB_DATETIME', 'Y-m-d H:i:s'); // date(...)
define('FORMAT_DB_DATE', 'Y-m-d');
define('FORMAT_DB_TIME', 'H:i:s');

define('DEFAULT_DATETIME', '0000-00-00 00:00:00');
define('DEFAULT_DATE', '0000-00-00');
define('DEFAULT_TIME', '00:00:00');

# deprecated (could be wrong, if timezone is modified)
define('CURRENT_YEAR', date('Y'));
define('CURRENT_MONTH', date('m'));
define('CURRENT_DAY', date('d'));

# workpaths
define('FILES', APP . 'files' . DS);
define('LOCALE', APP . 'locale' . DS);

if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}

# Validation ## (minus should be "hyphen")
/** Valid characters: letters only */
define('VALID_ALPHA', '/^[a-zA-Z]+$/');

/** Valid characters: letters,underscores only */
define('VALID_ALPHA_UNDERSCORES', '/^[a-zA-Z_]+$/');

/** Valid characters: letters,underscores,minus only */
define('VALID_ALPHA_MINUS_UNDERSCORES', '/^[a-zA-Z_-]+$/');

/** Valid characters: letters,spaces only */
define('VALID_ALPHA_WHITESPACES', '/^[a-zA-Z ]+$/');

/** Valid characters: letters,numbers,underscores only */
define('VALID_ALPHANUMERIC_UNDERSCORES', '/^[\da-zA-Z_]+$/');

/** Valid characters: letters,numbers,underscores,minus only */
define('VALID_ALPHANUMERIC_MINUS_UNDERSCORES', '/^[\da-zA-Z_-]+$/');

/** Valid characters: letters,numbers,spaces only */
define('VALID_ALPHANUMERIC_WHITESPACES', '/^[\da-zA-Z ]+$/');

/** Valid characters: letters,numbers,spaces,underscores only */
define('VALID_ALPHANUMERIC_WHITESPACES_UNDERSCORES', '/^[\da-zA-Z _]+$/');

/** Valid characters: numbers,underscores only */
define('VALID_NUMERIC_UNDERSCORES', '/^[\d_]+$/');

/** Valid characters: numbers,spaces only */
define('VALID_NUMERIC_WHITESPACES', '/^[\d ]+$/');

/** Valid characters: numbers,spaces,underscores only */
define('VALID_NUMERIC_WHITESPACES_UNDERSCORES', '/^[\d _]+$/');

/** Valid integers: > 0 */
define('VALID_INTEGERS', '/^[\d]+$/'); //??


if (!defined('FORMAT_NICE_YMDHMS')) {
	define('FORMAT_NICE_YMDHMS','d.m.Y, H:i:s');
	define('FORMAT_NICE_YMDHM','d.m.Y, H:i');
	define('FORMAT_NICE_YM','m.Y');
	define('FORMAT_NICE_YMD','d.m.Y');
	define('FORMAT_NICE_MD','d.m.');
	define('FORMAT_NICE_D','d'); # xx
	define('FORMAT_NICE_W_NUM','w'); # xx (0=sunday to 6=saturday)
	define('FORMAT_NICE_W_ABBR','D'); # needs manual translation
	define('FORMAT_NICE_W_FULL','l'); # needs manual translation
	define('FORMAT_NICE_M','m'); # xx
	define('FORMAT_NICE_M_ABBR','M'); # needs manual translation
	define('FORMAT_NICE_M_FULL','F'); # needs manual translation
	define('FORMAT_NICE_Y_ABBR','y'); # xx
	define('FORMAT_NICE_Y','Y'); # xxxx
	define('FORMAT_NICE_HM','H:i');
	define('FORMAT_NICE_HMS','H:i:s');

	# localDate strings
	define('FORMAT_LOCAL_WA_YMDHMS','%a, %d.%m.%Y, %H:%M:%S');
	define('FORMAT_LOCAL_WF_YMDHMS','%A, %d.%m.%Y, %H:%M:%S');
	define('FORMAT_LOCAL_WA_YMDHM','%a, %d.%m.%Y, %H:%M');
	define('FORMAT_LOCAL_WF_YMDHM','%A, %d.%m.%Y, %H:%M');

	define('FORMAT_LOCAL_YMDHMS','%d.%m.%Y, %H:%M:%S');
	define('FORMAT_LOCAL_YMDHM','%d.%m.%Y, %H:%M');
	define('FORMAT_LOCAL_YMD','%d.%m.%Y');
	define('FORMAT_LOCAL_MD','%d.%m.');
	define('FORMAT_LOCAL_D','%d'); # xx
	define('FORMAT_LOCAL_W_NUM','%w'); # xx (0=sunday to 6=saturday)
	define('FORMAT_LOCAL_W_ABBR','%a'); # needs translation
	define('FORMAT_LOCAL_W_FULL','%A'); # needs translation
	define('FORMAT_LOCAL_M','%m'); # xx
	define('FORMAT_LOCAL_M_ABBR','%b'); # needs translation
	define('FORMAT_LOCAL_M_FULL','%B'); # needs translation
	define('FORMAT_LOCAL_Y_ABBR','%y'); # xx
	define('FORMAT_LOCAL_Y','%Y'); # xxxx
	define('FORMAT_LOCAL_H','%H');
	define('FORMAT_LOCAL_S','%S');
	define('FORMAT_LOCAL_HM','%H:%i');
	define('FORMAT_LOCAL_HMS','%H:%M:%S');
}


/*** chars ***/

/* see http://www.htmlcodetutorial.com/characterentities_famsupp_69.html */
define('CHAR_LESS', '&lt;'); # <
define('CHAR_GREATER', '&gt;'); # >
define('CHAR_QUOTE', '&quot;'); # "
define('CHAR_APOSTROPHE', '&#39'); # '
define('CHAR_ARROWS', '&raquo;'); # »
define('CHAR_ARROWS_R', '&#187;'); # »
define('CHAR_ARROWS_L', '&#171;'); # «
define('CHAR_AVERAGE', '&#216;'); # Ø
define('CHAR_INFIN', '&infin;'); # 8
define('CHAR_MILL', '&#137;'); # ‰ (per mille) / or &permil;
define('CHAR_PLUSMN', '&plusmn;'); # 8
define('CHAR_HELLIP', '&#8230;'); # … (horizontal ellipsis = three dot leader)
define('CHAR_CIRCA', '&asymp;'); # ˜ (almost equal to)
define('CHAR_CHECKBOX_EMPTY', '&#9744;]'); #
define('CHAR_CHECKBOX_MAKRED', '&#9745'); #
define('CHAR_CHECKMARK', '&#10003;');
define('CHAR_CHECKMARK_BOLD', '&#10004;');
define('CHAR_BALLOT', '&#10007;');
define('CHAR_BALLOT_BOLD', '&#10008;');
define('CHAR_ABOUT','&asymp;'); # … (horizontal ellipsis = three dot leader)

/* not very often used */
define('CHAR_RPIME', '&#8242;'); # ' (minutes)
define('CHAR_DOUBLE_RPIME', '&#8243;'); # ? (seconds)


/** BASIC FUNCTIONS **/

/**
 * own slug function
 * 2010-11-07 ms
 */
function slug($string, $separator = null, $low = true) {
	$additionalSlugElements = array(
		'/º|°/' => 0,
		'/¹/' => 1,
		'/²/' => 2,
		'/³/' => 3,
		# new utf8 char "capitel ß" still missing here! '/.../' => 'SS', (TODO in 2009)
		'/@/' => 'at',
		'/æ/' => 'ae',
		'/©/' => 'C',
		'/ç|¢/' => 'c',
		'/Ð/' => 'D',
		'/€/' => 'EUR',
		'/™/' => 'TM',
		# more missing?
	);

	if ($separator === null) {
		$separator = defined('SEO_SEPARATOR') ? SEO_SEPARATOR : '-';
	}
	$res = Inflector::slug($string, $separator, $additionalSlugElements);
	if ($low) {
		$res = strtolower($res);
	}
	return $res;
}


/**
 * Since nl2br doesn't remove the line breaks when adding in the <br /> tags,
 * it is necessary to strip those off before you convert all of the tags, otherwise you will get double spacing
 * @param string $str
 * @return string
 * 2010-11-07 ms
 */
function br2nl($str) {
	$str = preg_replace("/(\r\n|\r|\n)/", "", $str);
	return preg_replace("=<br */?>=i", "\n", $str);
}

/**
 * Replaces CRLF with spaces
 *
 * @param string $text Any text
 * @return string Safe string without new lines
 * 2010-11-14 ms
 */
function safenl($str) {
	//$str = str_replace(chr(13).chr(10), " ", $str); # \r\n
	//$str = str_replace(chr(13), " ", $str); # \r
	//$str = str_replace(chr(10), " ", $str); # \n
	$str = preg_replace("/(\r\n|\r|\n)/", " ", $str);
	return $str;
}

/**
 * @param array $keyValuePairs
 * @return string $key
 * like array_shift() only for keys and not values
 * 2011-01-22 ms
 */
function arrayShiftKeys(&$array) {
	trigger_error('deprecated - use Tools.Utility instead');
	//TODO: improve?
	foreach ($array as $key => $value) {
		unset($array[$key]);
		return $key;
	}
}

/**
 * Flattens an array, or returns FALSE on fail.
 * 2011-07-02 ms
 */
function arrayFlatten($array) {
	trigger_error('deprecated - use Tools.Utility instead');

	if (!is_array($array)) {
	return false;
	}
	$result = array();
	foreach ($array as $key => $value) {
	if (is_array($value)) {
		$result = array_merge($result, arrayFlatten($value));
	} else {
		$result[$key] = $value;
	}
	}
	return $result;
}


/**
 * convenience function to check on "empty()"
 * 2009-06-15 ms
 */
function isEmpty($var = null) {
	if (empty($var)) {
		return true;
	}
	return false;
}


/**
 * //TODO: use Debugger::exportVar() instead?
 * of what type is the specific value
 * @return type (NULL, array, bool, float, int, string, object, unknown) + value
 * 2009-03-03 ms
 */
function returns($value) {
	if ($value === null) {
		return 'NULL';
	} elseif (is_array($value)) {
		return '(array)' . '<pre>' . print_r($value, true) . '</pre>';
	} elseif ($value === true) {
		return '(bool)TRUE';
	} elseif ($value === false) {
		return '(bool)FALSE';
	} elseif (is_numeric($value) && is_float($value)) {
		return '(float)' . $value;
	} elseif (is_numeric($value) && is_int($value)) {
		return '(int)' . $value;
	} elseif (is_string($value)) {
		return '(string)' . $value;
	} elseif (is_object($value)) {
		return '(object)' . get_class($value) . '<pre>' . print_r($value, true) .
			'</pre>';
	} else {
		return '(unknown)' . $value;
	}
}

function dump($var) {
	if (class_exists('Debugger')) {
		App::import('Core', 'Debugger');
	}
	return Debugger::dump($var);
}

/**
 * Returns htmlentities - string
 *
 * ENT_COMPAT	= Will convert double-quotes and leave single-quotes alone.
 * ENT_QUOTES	= Will convert both double and single quotes. !!!
 * ENT_NOQUOTES= Will leave both double and single quotes unconverted.
 */
function ent($text) {
	return (!empty($text) ? htmlentities($text, ENT_QUOTES, 'UTF-8') : '');
}

/**
 * Convenience method for htmlspecialchars_decode
 *
 * @param string $text Text to wrap through htmlspecialchars_decode
 * @return string Wrapped text
 * 2011-04-03 ms
 */
function hDec($text, $quoteStyle = ENT_QUOTES) {
	if (is_array($text)) {
		return array_map('hDec', $text);
	}
	return trim(htmlspecialchars_decode($text, $quoteStyle));
}

/**
 * Convenience method for html_entity_decode
 *
 * @param string $text Text to wrap through htmlspecialchars_decode
 * @return string Wrapped text
 * 2011-04-03 ms
 */
function entDec($text, $quoteStyle = ENT_QUOTES) {
	if (is_array($text)) {
		return array_map('entDec', $text);
	}
	return (!empty($text) ? trim(html_entity_decode($text, $quoteStyle, 'UTF-8')) : '');
}

/**
 * focus is on the filename (without path)
 * 2011-06-02 ms
 */
function extractFileInfo($type = null, $filename) {
	if ($info = extractPathInfo($type, $filename)) {
		return $info;
	}
	$pos = strrpos($filename, '.');
	$res = '';
	switch ($type) {
		case 'extension':
		case 'ext':
			$res = ($pos !== false) ? substr($filename, $pos+1) : '';
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

/**
 * uses native PHP function to retrieve infos about a filename etc.
 * @param string type (extension/ext, filename/file, basename/base, dirname/dir)
 * @param string filename to check on
 * //TODO: switch parameters!!!
 * 2009-01-22 ms
 */
function extractPathInfo($type = null, $filename) {
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
			$infoType = null;
	}
	return pathinfo($filename, $infoType);
}


/**
 * Shows pr() messages, even with debug=0
 *
 * @param mixed $content
 * @param bool $collapsedAndExpandable
 * @param array $options
 * - class, showHtml, showFrom, jquery, returns, debug
 * 2011-01-19 ms
 */
function pre($var, $collapsedAndExpandable = false, $options = array()) {
	$defaults = array(
		'class' => 'cake-debug',
		'showHtml' => false, # escape < and > (or manually escape with h() prior to calling this function)
		'showFrom' => false, # display file + line
		'jquery' => null, # auto - use jQuery (true/false to manually decide),
		'returns' => false, # returns(),
		'debug' => false # showOnlyOnDebug
	);
	$options = array_merge($defaults, $options);
	if ($options['debug'] && !Configure::read('debug')) {
		return '';
	}
	$res = '<div class="'.$options['class'].'">';

	$pre = '';
	if ($collapsedAndExpandable) {
		$js = 'if (this.parentNode.getElementsByTagName(\'pre\')[0].style.display==\'block\') {this.parentNode.getElementsByTagName(\'pre\')[0].style.display=\'none\'} else {this.parentNode.getElementsByTagName(\'pre\')[0].style.display=\'block\'}';
		$jsJquery = 'jQuery(this).parent().children(\'pre\').slideToggle(\'fast\')';
		if ($options['jquery'] === true) {
			$js = $jsJquery;
		} elseif ($options['jquery'] !== false) {
			# auto
			$js = 'if (typeof jQuery == \'undefined\') {'.$js.'} else {'.$jsJquery.'}';
		}
		$res .= '<span onclick="'.$js.'" style="cursor: pointer; font-weight: bold">Debug</span>';
		if ($options['showFrom']) {
			$calledFrom = debug_backtrace();
			$from = '<em>' . substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1) . '</em>';
			$from .= ' (line <em>' . $calledFrom[0]['line'] . '</em>)';
			$res .= '<div>'.$from.'</div>';
		}
		$pre = ' style="display: none"';
	}

	if ($options['returns']) {
 		$var = returns($var);
	} else {
		$var = print_r($var, true);
	}
	$res .= '<pre' . $pre . '>' . $var . '</pre>';
	$res .= '</div>';
	return $res;
}

/**
 * Checks if the string [$haystack] contains [$needle]
 * @param string $haystack Input string.
 * @param string $needle Needed char or string.
 * @return boolean
 */
function contains($haystack, $needle, $caseSensitive = false) {
	return (!$caseSensitive ? stripos($haystack, $needle) : strpos($haystack, $needle))
		!== false;
}

/**
 * Can compare two float values
 * @deprecated use NumberLib::isFloatEqual
 * @link http://php.net/manual/en/language.types.float.php
 * @return boolean
 */
function isFloatEqual($x, $y, $precision = 0.0000001) {
	trigger_error('deprecated - use NumberLib::isFloatEqual instead');
	return ($x+$precision >= $y) && ($x-$precision <= $y);
}

/**
 * Checks if the string [$haystack] starts with [$needle]
 * @param string $haystack Input string.
 * @param string $needle Needed char or string.
 * @return boolean
 */
function startsWith($haystack, $needle, $caseSensitive = false) {
	if ($caseSensitive) {
		return (mb_strpos($haystack, $needle) === 0);
	}
	return (mb_stripos($haystack, $needle) === 0);
}

/**
 * Checks if the String [$haystack] ends with [$needle]
 * @param string $haystack Input string.
 * @param string $needle Needed char or string
 * @return boolean
 */
function endsWith($haystack, $needle, $caseSensitive = false) {
	if ($caseSensitive) {
		return mb_strrpos($haystack, $needle) === mb_strlen($haystack) - mb_strlen($needle);
	}
	return mb_strripos($haystack, $needle) === mb_strlen($haystack) - mb_strlen($needle);
}

/* deprecated? */
function isLoggedIn() {
	return isset($_SESSION) && !empty($_SESSION['Auth']['User']['id']);
}

/* deprecated? */
function uid($default = null) {
	return (isset($_SESSION) && !empty($_SESSION['Auth']['User']['id'])) ? $_SESSION['Auth']['User']['id'] :
		$default;
}

/**
 * own shutdown function - also logs fatal errors (necessary until cake2.2)
 * 2010-10-17 ms
 */
function shutDownFunction() {
	if (Configure::version() >= 2.3) {
		return;
	}
	$error = error_get_last();
	if (empty($error)) {
		return;
	}
	$matching = array(
		E_ERROR =>'E_ERROR',
		E_WARNING => 'E_WARNING',
		E_PARSE => 'E_',
		E_NOTICE => 'E_',
		E_CORE_ERROR => 'E_',
		E_COMPILE_ERROR => 'E_',
		E_COMPILE_WARNING => 'E_',
		E_STRICT => 'E_STRICT',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_DEPRECATED => 'E_DEPRECATED',
	);
	App::uses('CakeLog', 'Log');

	if (in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
		$error['type_name'] = 'Fatal Error';
		$type = 'error';

	} elseif (Configure::read('Debug.log') && isset($matching[$error['type']])) {
		$error['type_name'] = 'Error';
		$type = 'notice';
	}

	if (!isset($type)) {
		return;
	}

	App::uses('Debugger', 'Utility');
	$trace = Debugger::trace(array('start' => 1, 'format' => 'log', 'args'=>true));
	$path = Debugger::trimPath($error['file']);

	$message = $error['type_name'].' '.$matching[$error['type']].' in '.$path. ' (line '.$error['line'].'): ' . $error['message'];
	$message .= PHP_EOL . $trace;
	App::uses('MyErrorHandler', 'Tools.Error');
	$message .= MyErrorHandler::traceDetails();

	CakeLog::write($type, $message);
}

//TODO: move into separate lib!
class DebugTab {
	public static $content = array();
	public static $groups = array();
}

function debugTab($var = false, $display = false, $key = null) {
	if (is_string($display)) {
		$key = $display;
		$display = true;
	}
	if (Configure::read('debug') > 0) {
		$calledFrom = debug_backtrace();
		if (is_string($key)) {
			if (!isset(debugTab::$groups[$key])) {
				DebugTab::$groups[$key] = array();
			}
			DebugTab::$groups[$key][] = array(
				'debug' => print_r($var, true),
				'file' => substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1),
				'line' => $calledFrom[0]['line'],
				'display' => $display
			);
		} else {
			DebugTab::$content[] = array(
				'debug' => print_r($var, true),
				'file' => substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1),
				'line' => $calledFrom[0]['line'],
				'display' => $display
			);
		}
	}
	return true;
}

/**
 * base64 encode and replace chars base64 uses that would mess up the url
 * @return string or NULL
 */
function base64UrlEncode($fieldContent) {
	if (empty($fieldContent)) {
		return null;
	}
	$tmp = base64_encode($fieldContent);
	return str_replace(array('/', '='), array('-', '_'), $tmp);
}

/**
 * base64 decode and undo replacing of chars base64 uses that would mess up the url
 * @return string or NULL
 */
function base64UrlDecode($fieldContent) {
	if (empty($fieldContent)) {
		return null;
	}
	$tmp = str_replace(array('-', '_'), array('/', '='), $fieldContent);
	return base64_decode($tmp);
}



/**
 * pretty_json
 *
 * @link https://github.com/ndejong/pretty_json/blob/master/pretty_json.php
 * @param string $json - the original JSON string
 * @param string $ind - the string to indent with
 * @return string
 */
function pretty_json($json, $ind = "\t") {

	// Replace any escaped \" marks so we don't get tripped up on quotemarks_counter
	$tokens = preg_split('|([\{\}\]\[,])|', str_replace('\"', '~~PRETTY_JSON_QUOTEMARK~~', $json), -1, PREG_SPLIT_DELIM_CAPTURE);

	$indent = 0;
	$result = "";
	$quotemarks_counter = 0;
	$next_token_use_prefix = true;

	foreach ($tokens as $token) {

		$quotemarks_counter = $quotemarks_counter + (count(explode('"', $token)) - 1);

		if ($token == "") {
			continue;
		}

		if ($next_token_use_prefix) {
			$prefix = str_repeat($ind, $indent);
		} else {
			$prefix = null;
		}

		// Determine if the quote marks are open or closed
		if ($quotemarks_counter & 1) {
			// odd - thus quotemarks open
			$next_token_use_prefix = false;
			$new_line = null;
		} else {
			// even - thus quotemarks closed
			$next_token_use_prefix = true;
			$new_line = "\n";
		}

		if ($token == "{" || $token == "[") {
			$indent++;
			$result .= $token . $new_line;
		} elseif ($token == "}" || $token == "]") {
			$indent--;

			if ($indent >= 0) {
				$prefix = str_repeat($ind, $indent);
			}

			if ($next_token_use_prefix) {
				$result .= $new_line . $prefix . $token;
			} else {
				$result .= $new_line . $token;
			}
		} elseif ($token == ",") {
				$result .= $token . $new_line;
		} else {
			$result .= $prefix . $token;
		}
	}
	$result = str_replace('~~PRETTY_JSON_QUOTEMARK~~', '\"', $result);
	return $result;
}

/*** > PHP5.3 ***/

/**
 * replacement since it is deprecated in PHP5.3.3 (needs testing!!!)
 *
 * TODO: Write cool MimeLib to do this fucking Mime stuff in a better way
 *		 and also build a mime type WITH the charset of the file/strig like:
 *		 text/plain; charset=utf-8
 * @deprecated This function has been deprecated as the PECL extension Fileinfo provides the same functionality (and more) in a much cleaner way
 **/
if (!function_exists('mime_content_type')) {
	function mime_content_type($file, $method = 0) {
		if (WINDOWS) {
			return false;
		}
	if ($method == 0) {
		ob_start();
		system('/usr/bin/file -i -b ' . realpath($file));
		$type = ob_get_clean();

		$parts = explode(';', $type);

		return trim($parts[0]);
		} elseif ($method == 1) {
		// another method here
		}
	}
}
