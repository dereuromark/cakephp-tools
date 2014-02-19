<?php

App::uses('TextHelper', 'View/Helper');
App::uses('HtmlHelper', 'View/Helper');
App::uses('NumberLib', 'Tools.Utility');
App::uses('View', 'View');

/**
 * The core text helper is unsecure and outdated in functionality.
 * This aims to compensate the deficiencies.
 *
 * autoLinkEmails
 * - obfuscate (defaults to FALSE right now)
 * (- maxLength?)
 * - escape (defaults to TRUE for security reasons regarding plain text)
 *
 * autoLinkUrls
 * - stripProtocol (defaults To FALSE right now)
 * - maxLength (to shorten links in order to not mess up the layout in some cases - appends ...)
 * - escape (defaults to TRUE for security reasons regarding plain text)
 *
 */
class TextExtHelper extends TextHelper {

	/**
	 * Constructor
	 *
	 * ### Settings:
	 *
	 * - `engine` Class name to use to replace String functionality.
	 *            The class needs to be placed in the `Utility` directory.
	 *
	 * @param View $View the view object the helper is attached to.
	 * @param array $settings Settings array Settings array
	 * @throws CakeException when the engine class could not be found.
	 */
	public function __construct(View $View, $settings = array()) {
		$settings = Hash::merge(array('engine' => 'Tools.TextLib'), $settings);
		parent::__construct($View, $settings);
	}

	/**
	 * Convert all links and email adresses to HTML links.
	 *
	 * @param string $text Text
	 * @param array $options Array of HTML options.
	 * @return string The text with links
	 * @link http://book.cakephp.org/view/1469/Text#autoLink-1620
	 */
	public function autoLink($text, $options = array(), $htmlOptions = array()) {
		if (!isset($options['escape']) || $options['escape'] !== false) {
			$text = h($text);
			$options['escape'] = false;
		}
		return $this->autoLinkEmails($this->autoLinkUrls($text, $options, $htmlOptions), $options, $htmlOptions);
	}

	/**
	 * Fix to allow obfuscation of email (js, img?)
	 *
	 * @param string $text
	 * @param htmlOptions (additionally - not yet supported by core):
	 * - obfuscate: true/false (defaults to false)
	 * @param array $options
	 * - escape (defaults to true)
	 * @return string html
	 * @override
	 */
	public function autoLinkEmails($text, $options = array(), $htmlOptions = array()) {
		if (!isset($options['escape']) || $options['escape'] !== false) {
			$text = h($text);
		}

		$linkOptions = 'array(';
		foreach ($htmlOptions as $option => $value) {
			$value = var_export($value, true);
			$linkOptions .= "'$option' => $value, ";
		}
		$linkOptions .= ')';

		$customOptions = 'array(';
		foreach ($options as $option => $value) {
			$value = var_export($value, true);
			$customOptions .= "'$option' => $value, ";
		}
		$customOptions .= ')';

		$atom = '[\p{L}0-9!#$%&\'*+\/=?^_`{|}~-]';
		return preg_replace_callback('/(' . $atom . '+(?:\.' . $atom . '+)*@[\p{L}0-9-]+(?:\.[a-z0-9-]+)+)/iu',
			create_function('$matches', 'return TextExtHelper::prepareEmail($matches[0],' . $linkOptions . ',' . $customOptions . ');'), $text);
	}

	/**
	 * @param string $email
	 * @param options:
	 * - obfuscate: true/false (defaults to false)
	 * @return string html
	 */
	public static function prepareEmail($email, $options = array(), $customOptions = array()) {
		$obfuscate = false;
		if (isset($options['obfuscate'])) {
			$obfuscate = $options['obfuscate'];
			unset($options['obfuscate']);
		}

		if (!isset($customOptions['escape']) || $customOptions['escape'] !== false) {
			$email = hDec($email);
		}

		$Html = new HtmlHelper(new View(null));
		//$Html->tags = $Html->loadConfig();
		//debug($Html->tags);
		if (!$obfuscate) {
			return $Html->link($email, "mailto:" . $email, $options);
		}

		$class = __CLASS__;
		$Common = new $class;
		$Common->Html = $Html;
		return $Common->encodeEmailUrl($email, null, array(), $options);
	}

	/**
	 * Helper Function to Obfuscate Email by inserting a span tag (not more! not very secure on its own...)
	 * each part of this mail now does not make sense anymore on its own
	 * (striptags will not work either)
	 *
	 * @param string email: necessary (and valid - containing one @)
	 * @return string html
	 */
	public function encodeEmail($mail) {
		list($mail1, $mail2) = explode('@', $mail);
		$encMail = $this->encodeText($mail1) . '<span>@</span>' . $this->encodeText($mail2);
		return $encMail;
	}

	/**
	 * Obfuscates Email (works without JS!) to avoid lowlevel spam bots to get it
	 *
	 * @param string mail: email to encode
	 * @param string text: optional (if none is given, email will be text as well)
	 * @param array attributes: html tag attributes
	 * @param array params: ?subject=y&body=y to be attached to "mailto:xyz"
	 * @return string html with js generated link around email (and non js fallback)
	 */
	public function encodeEmailUrl($mail, $text = null, $params = array(), $attr = array()) {
		if (empty($class)) {
			$class = 'email';
		}

		$defaults = array(
			'title' => __('for use in an external mail client'),
			'class' => 'email',
			'escape' => false
		);

		if (empty($text)) {
			$text = $this->encodeEmail($mail);
		}

		$encMail = 'mailto:' . $mail;
		//$encMail = $this->encodeText($encMail); # not possible
		// additionally there could be a span tag in between: email<span syle="display:none"></span>@web.de

		$querystring = '';
		foreach ($params as $key => $val) {
			if ($querystring) {
				$querystring .= "&$key=" . rawurlencode($val);
			} else {
				$querystring = "?$key=" . rawurlencode($val);
			}
		}

		$attr = array_merge($defaults, $attr);

		$xmail = $this->Html->link('', $encMail . $querystring, $attr);
		$xmail1 = mb_substr($xmail, 0, count($xmail) - 5);
		$xmail2 = mb_substr($xmail, -4, 4);

		$len = mb_strlen($xmail1);
		$i = 0;
		while ($i < $len) {
			$c = mt_rand(2, 6);
			$par[] = (mb_substr($xmail1, $i, $c));
			$i += $c;
		}
		$join = implode('\'+\'', $par);

			return '<script language=javascript><!--
		document.write(\'' . $join . '\');
		//--></script>
			' . $text . '
		<script language=javascript><!--
		document.write(\'' . $xmail2 . '\');
		//--></script>';

		//return '<a class="'.$class.'" title="'.$title.'" href="'.$encmail.$querystring.'">'.$encText.'</a>';
	}

	/**
	 * Encodes Piece of Text (without usage of JS!) to avoid lowlevel spam bots to get it
	 *
	 * @param STRING text to encode
	 * @return string html (randomly encoded)
	 */
	public static function encodeText($text) {
		$encmail = '';
		$length = mb_strlen($text);
		for ($i = 0; $i < $length; $i++) {
			$encMod = mt_rand(0, 2);
			switch ($encMod) {
				case 0: // None
					$encmail .= mb_substr($text, $i, 1);
					break;
				case 1: // Decimal
					$encmail .= "&#" . ord(mb_substr($text, $i, 1)) . ';';
					break;
				case 2: // Hexadecimal
					$encmail .= "&#x" . dechex(ord(mb_substr($text, $i, 1))) . ';';
					break;
			}
		}
		return $encmail;
	}

	/**
	 * Fix to allow shortened urls that do not break layout etc
	 *
	 * @param string $text
	 * @param options (additionally - not yet supported by core):
	 * - stripProtocol: bool (defaults to true)
	 * - maxLength: int (defaults no none)
	 * @param htmlOptions
	 * - escape etc
	 * @return string html
	 * @override
	 */
	public function autoLinkUrls($text, $options = array(), $htmlOptions = array()) {
		if (!isset($options['escape']) || $options['escape'] !== false) {
			$text = h($text);
			$matchString = 'hDec($matches[0])';
		} else {
			$matchString = '$matches[0]';
		}

		if (isset($htmlOptions['escape'])) {
			$options['escape'] = $htmlOptions['escape'];
		}
		//$htmlOptions['escape'] = false;

		$htmlOptions = var_export($htmlOptions, true);
		$customOptions = var_export($options, true);

		$text = preg_replace_callback('#(?<!href="|">)((?:https?|ftp|nntp)://[^\s<>()]+)#i', create_function('$matches',
			'$Html = new HtmlHelper(new View(null)); return $Html->link(TextExtHelper::prepareLinkName(hDec($matches[0]), ' . $customOptions . '), hDec($matches[0]),' . $htmlOptions . ');'), $text);

		return preg_replace_callback('#(?<!href="|">)(?<!http://|https://|ftp://|nntp://)(www\.[^\n\%\ <]+[^<\n\%\,\.\ <])(?<!\))#i',
			create_function('$matches', '$Html = new HtmlHelper(new View(null)); return $Html->link(TextExtHelper::prepareLinkName(hDec($matches[0]), ' . $customOptions . '), "http://" . hDec($matches[0]),' . $htmlOptions . ');'), $text);
	}

	/**
	 * @param string $link
	 * @param options:
	 * - stripProtocol: bool (defaults to true)
	 * - maxLength: int (defaults to 50)
	 * - escape (defaults to false, true needed for hellip to work)
	 * @return string html/$plain
	 */
	public static function prepareLinkName($link, $options = array()) {
		// strip protocol if desired (default)
		if (!isset($options['stripProtocol']) || $options['stripProtocol'] !== false) {
			$link = self::stripProtocol($link);
		}
		if (!isset($options['maxLength'])) {
			$options['maxLength'] = 50; # should be long enough for most cases
		}
		// shorten display name if desired (default)
		if (!empty($options['maxLength']) && mb_strlen($link) > $options['maxLength']) {
			$link = mb_substr($link, 0, $options['maxLength']);
			// problematic with autoLink()
			if (!empty($options['html']) && isset($options['escape']) && $options['escape'] === false) {
				$link .= '&hellip;'; # only possible with escape => false!
			} else {
				$link .= '...';
			}
		}
		return $link;
	}

	/**
	 * Remove http:// or other protocols from the link
	 *
	 * @param string $url
	 * @return string strippedUrl
	 */
	public static function stripProtocol($url) {
		$pieces = parse_url($url);
		if (empty($pieces['scheme'])) {
			return $url; # already stripped
		}
		return mb_substr($url, mb_strlen($pieces['scheme']) + 3); # +3 <=> :// # can only be 4 with "file" (file:///)...
	}

	/**
	 * Minimizes the given url to a maximum length
	 *
	 * @param string $url the url
	 * @param integer $max the maximum length
	 * @param array $options
	 * - placeholder
	 * @return string the manipulated url (+ eventuell ...)
	 */
	public function minimizeUrl($url = null, $max = null, $options = array()) {
		// check if there is nothing to do
		if (empty($url) || mb_strlen($url) <= (int)$max) {
			return (string)$url;
		}
		// http:// has not to be displayed, so
		if (mb_substr($url, 0, 7) === 'http://') {
			$url = mb_substr($url, 7);
		}
		// cut the parameters
		if (mb_strpos($url, '/') !== false) {
			$url = strtok($url, '/');
		}
		// return if the url is short enough
		if (mb_strlen($url) <= (int)$max) {
			return $url;
		}
		// otherwise cut a part in the middle (but only if long enough!!!)
		// TODO: more dynamically
		$placeholder = CHAR_HELLIP;
		if (!empty($options['placeholder'])) {
			$placeholder = $options['placeholder'];
		}

		$end = mb_substr($url, -5, 5);
		$front = mb_substr($url, 0, (int)$max - 8);
		return $front . $placeholder . $end;
	}

	/**
	 * Transforming int values into ordinal numbers (1st, 3rd, ...).
	 * When using HTML, you can use <sup>, as well.
	 *
	 * @param $num (INT) - the number to be suffixed.
	 * @param $sup (BOOL) - whether to wrap the suffix in a superscript (<sup>) tag on output.
	 * @return string ordinal
	 */
	public static function ordinalNumber($num = 0, $sup = false) {
		$ordinal = NumberLib::ordinal($num);
		return ($sup) ? $num . '<sup>' . $ordinal . '</sup>' : $num . $ordinal;
	}

	/**
	 * Syntax highlighting using php internal highlighting
	 *
	 * @param string $filename
	 * @param boolean $return (else echo directly)
	 * @return string
	 */
	public static function highlightFile($file, $return = true) {
		return highlight_file($file, $return);
	}

	/**
	 * Syntax highlighting using php internal highlighting
	 *
	 * @param string $contentstring
	 * @param boolean $return (else echo directly)
	 * @return string
	 */
	public static function highlightString($string, $return = true) {
		return highlight_string($string, $return);
	}

}
