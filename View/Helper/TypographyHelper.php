<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 */
App::uses('AppHelper', 'View/Helper');

/**
 * Typography Class converted to Cake Helper
 *
 * In the database you usually got ", ', and - as uniform chars.
 * On output you might want to localize them according to your country's/languages' preferences.
 *
 * For Swiss, for example: "Some quote" might become «Some quote»
 * For German: "Some quote" might become „Some quote“
 *
 * @modified Mark Scherer
 * @cakephp 2.x
 * @php 5
 * @link http://www.dereuromark.de/2012/08/12/typographic-behavior-and-typography-helper/
 */
class TypographyHelper extends AppHelper {

	// Block level elements that should not be wrapped inside <p> tags
	public $blockElements = 'address|blockquote|div|dl|fieldset|form|h\d|hr|noscript|object|ol|p|pre|script|table|ul';

	// Elements that should not have <p> and <br /> tags within them.
	public $skipElements = 'p|pre|ol|ul|dl|object|table|h\d';

	// Tags we want the parser to completely ignore when splitting the string.
	public $inlineElements =
		'a|abbr|acronym|b|bdo|big|br|button|cite|code|del|dfn|em|i|img|ins|input|label|map|kbd|q|samp|select|small|span|strong|sub|sup|textarea|tt|var';

	// array of block level elements that require inner content to be within another block level element
	public $innerBlockRequired = array('blockquote');

	// the last block element parsed
	public $lastBlockElement = '';

	// whether or not to protect quotes within { curly braces }
	public $protectBracedQuotes = false;

	public $matching = array(
		'deu' => 'low', // except for Switzerland
		'eng' => 'default',
		'fra' => 'angle',
	);

	/**
	 * Automatically uses the typography specified.
	 * By default, uses Configure::read('App.language') to determine locale preference.
	 * It will then try to match the language to the type of characters used.
	 * You can hardwire this by using Configure::read('Typography.locale'); and directly set it
	 * to 'low' or 'angle'. It will then disregard the language.
	 *
	 * This function converts text, making it typographically correct:
	 * - Converts double spaces into paragraphs.
	 * - Converts single line breaks into <br /> tags
	 * - Converts single and double quotes into correctly facing curly quote entities.
	 * - Converts three dots into ellipsis.
	 * - Converts double dashes into em-dashes.
	 * - Converts two spaces into entities
	 *
	 * @param string $str Text
	 * @param boolean $reduceLinebreaks Whether to reduce more then two consecutive newlines to two
	 * @return string Text
	 */
	public function autoTypography($str, $reduceLinebreaks = false) {
		if ($str === '') {
			return '';
		}

		// Standardize Newlines to make matching easier
		if (strpos($str, "\r") !== false) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		// Reduce line breaks. If there are more than two consecutive linebreaks
		// we'll compress them down to a maximum of two since there's no benefit to more.
		if ($reduceLinebreaks === true) {
			$str = preg_replace("/\n\n+/", "\n\n", $str);
		}

		// HTML comment tags don't conform to patterns of normal tags, so pull them out separately, only if needed
		$htmlComments = array();
		if (strpos($str, '<!--') !== false) {
			if (preg_match_all("#(<!\-\-.*?\-\->)#s", $str, $matches)) {
				for ($i = 0, $total = count($matches[0]); $i < $total; $i++) {
					$htmlComments[] = $matches[0][$i];
					$str = str_replace($matches[0][$i], '{@HC' . $i . '}', $str);
				}
			}
		}

		// match and yank <pre> tags if they exist. It's cheaper to do this separately since most content will
		// not contain <pre> tags, and it keeps the PCRE patterns below simpler and faster
		if (strpos($str, '<pre') !== false) {
			$str = preg_replace_callback("#<pre.*?>.*?</pre>#si", array($this, '_protectCharacters'), $str);
		}

		// Convert quotes within tags to temporary markers.
		$str = preg_replace_callback("#<.+?>#si", array($this, '_protectCharacters'), $str);

		// Do the same with braces if necessary
		if ($this->protectBracedQuotes === true) {
			$str = preg_replace_callback("#\{.+?\}#si", array($this, '_protectCharacters'), $str);
		}

		// Convert "ignore" tags to temporary marker. The parser splits out the string at every tag
		// it encounters. Certain inline tags, like image tags, links, span tags, etc. will be
		// adversely affected if they are split out so we'll convert the opening bracket < temporarily to: {@TAG}
		$str = preg_replace("#<(/*)(" . $this->inlineElements . ")([ >])#i", "{@TAG}\\1\\2\\3", $str);

		// Split the string at every tag. This expression creates an array with this prototype:
		//
		//	[array]
		//	{
		//		[0] = <opening tag>
		//		[1] = Content...
		//		[2] = <closing tag>
		//		Etc...
		//	}
		$chunks = preg_split('/(<(?:[^<>]+(?:"[^"]*"|\'[^\']*\')?)+>)/', $str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		// Build our finalized string. We cycle through the array, skipping tags, and processing the contained text
		$str = '';
		$process = true;
		$paragraph = false;
		$currentChunk = 0;
		$totalChunks = count($chunks);

		foreach ($chunks as $chunk) {
			$currentChunk++;

			// Are we dealing with a tag? If so, we'll skip the processing for this cycle.
			// Well also set the "process" flag which allows us to skip <pre> tags and a few other things.
			if (preg_match("#<(/*)(" . $this->blockElements . ").*?>#", $chunk, $match)) {
				if (preg_match("#" . $this->skipElements . "#", $match[2])) {
					$process = ($match[1] === '/') ? true : false;
				}

				if ($match[1] === '') {
					$this->lastBlockElement = $match[2];
				}

				$str .= $chunk;
				continue;
			}

			if ($process == false) {
				$str .= $chunk;
				continue;
			}

			// Force a newline to make sure end tags get processed by _formatNewlines()
			if ($currentChunk == $totalChunks) {
				$chunk .= "\n";
			}

			// Convert Newlines into <p> and <br /> tags
			$str .= $this->_formatNewlines($chunk);
		}

		// No opening block level tag? Add it if needed.
		if (!preg_match("/^\s*<(?:" . $this->blockElements . ")/i", $str)) {
			$str = preg_replace("/^(.*?)<(" . $this->blockElements . ")/i", '<p>$1</p><$2', $str);
		}

		// Convert quotes, elipsis, em-dashes, non-breaking spaces, and ampersands
		$str = $this->formatCharacters($str);

		// restore HTML comments
		for ($i = 0, $total = count($htmlComments); $i < $total; $i++) {
			// remove surrounding paragraph tags, but only if there's an opening paragraph tag
			// otherwise HTML comments at the ends of paragraphs will have the closing tag removed
			// if '<p>{@HC1}' then replace <p>{@HC1}</p> with the comment, else replace only {@HC1} with the comment
			$str = preg_replace('#(?(?=<p>\{@HC' . $i . '\})<p>\{@HC' . $i . '\}(\s*</p>)|\{@HC' . $i . '\})#s', $htmlComments[$i], $str);
		}

		// Final clean up
		$table = array(
			// If the user submitted their own paragraph tags within the text
			// we will retain them instead of using our tags.
			'/(<p[^>*?]>)<p>/'	=> '$1', // <?php BBEdit syntax coloring bug fix

			// Reduce multiple instances of opening/closing paragraph tags to a single one
			'#(</p>)+#'			=> '</p>',
			'/(<p>\W*<p>)+/'	=> '<p>',

			// Clean up stray paragraph tags that appear before block level elements
			'#<p></p><(' . $this->blockElements . ')#'	=> '<$1',

			// Clean up stray non-breaking spaces preceeding block elements
			'#(&nbsp;\s*)+<(' . $this->blockElements . ')#'	=> '  <$2',

			// Replace the temporary markers we added earlier
			'/\{@TAG\}/'		=> '<',
			'/\{@DQ\}/'			=> '"',
			'/\{@SQ\}/'			=> "'",
			'/\{@DD\}/'			=> '--',
			'/\{@NBS\}/'		=> '  ',

			// An unintended consequence of the _formatNewlines function is that
			// some of the newlines get truncated, resulting in <p> tags
			// starting immediately after <block> tags on the same line.
			// This forces a newline after such occurrences, which looks much nicer.
			"/><p>\n/"			=> ">\n<p>",

			// Similarly, there might be cases where a closing </block> will follow
			// a closing </p> tag, so we'll correct it by adding a newline in between
			"#</p></#"			=> "</p>\n</"
			);

		// Do we need to reduce empty lines?
		if ($reduceLinebreaks === true) {
			$table['#<p>\n*</p>#'] = '';
		} else {
			// If we have empty paragraph tags we add a non-breaking space
			// otherwise most browsers won't treat them as true paragraphs
			$table['#<p></p>#'] = '<p>&nbsp;</p>';
		}

		return preg_replace(array_keys($table), $table, $str);
	}

	/**
	 * Format Characters
	 *
	 * This function mainly converts double and single quotes
	 * to curly entities, but it also converts em-dashes,
	 * double spaces, and ampersands
	 *
	 * @param string
	 * @return string
	 */
	public function formatCharacters($str, $locale = null) {
		//static $table;
		if ($locale === null) {
			$locale = Configure::read('Typography.locale');
		}
		if (!$locale) {
			$locale = 'default';
			$language = Configure::read('App.language');
			if ($language && isset($this->matching[$language])) {
				$locale = $this->matching[$language];
			}
		}

		$locales = array(
			'default' => array(
				'leftSingle' => '&#8216;', // &lsquo; / ‘
				'rightSingle' => '&#8217;', // &rsquo; / ’
				'leftDouble' => '&#8220;', // &ldquo; / “
				'rightDouble' => '&#8221;', // &rdquo; / ”
			),
			'low' => array(
				'leftSingle' => '&sbquo;', // &sbquo; / ‚
				'rightSingle' => '&#8219;', // &rsquo; / ’
				'leftDouble' => '&bdquo;', // &bdquo; / „
				'rightDouble' => '&#8223;', // &rdquo; / ”
			),
			'angle' => array(
				'leftSingle' => '&lsaquo;', // ‹
				'rightSingle' => '&rsaquo;', // ›
				'leftDouble' => '&#171;', // &laquo; / «
				'rightDouble' => '&#187;', // &raquo; / »
			),
		);

		if (!isset($table)) {
			$table = array(
				// nested smart quotes, opening and closing
				// note that rules for grammar (English) allow only for two levels deep
				// and that single quotes are _supposed_ to always be on the outside
				// but we'll accommodate both
				// Note that in all cases, whitespace is the primary determining factor
				// on which direction to curl, with non-word characters like punctuation
				// being a secondary factor only after whitespace is addressed.
				'/\'"(\s|$)/'					=> '&#8217;&#8221;$1',
				'/(^|\s|<p>)\'"/'				=> '$1&#8216;&#8220;',
				'/\'"(\W)/'						=> '&#8217;&#8221;$1',
				'/(\W)\'"/'						=> '$1&#8216;&#8220;',
				'/"\'(\s|$)/'					=> '&#8221;&#8217;$1',
				'/(^|\s|<p>)"\'/'				=> '$1&#8220;&#8216;',
				'/"\'(\W)/'						=> '&#8221;&#8217;$1',
				'/(\W)"\'/'						=> '$1&#8220;&#8216;',

				// single quote smart quotes
				'/\'(\s|$)/'					=> '&#8217;$1',
				'/(^|\s|<p>)\'/'				=> '$1&#8216;',
				'/\'(\W)/'						=> '&#8217;$1',
				'/(\W)\'/'						=> '$1&#8216;',

				// double quote smart quotes
				'/"(\s|$)/'						=> '&#8221;$1',
				'/(^|\s|<p>)"/'					=> '$1&#8220;',
				'/"(\W)/'						=> '&#8221;$1',
				'/(\W)"/'						=> '$1&#8220;',

				// apostrophes
				"/(\w)'(\w)/"					=> '$1&rsquo;$2', // we dont use #8217; to avoid collision on replace

				// Em dash and ellipses dots
				'/\s?\-\-\s?/'					=> '&#8212;',
				'/(\w)\.{3}/'					=> '$1&#8230;',

				// double space after sentences
				'/(\W)  /'						=> '$1&nbsp; ',

				// ampersands, if not a character entity
				'/&(?!#?[a-zA-Z0-9]{2,};)/'		=> '&amp;'
			);
			if ($locale && !empty($locales[$locale])) {
				foreach ($table as $key => $val) {
					$table[$key] = str_replace($locales['default'], $locales[$locale], $val);
				}
			}
		}

		return preg_replace(array_keys($table), $table, $str);
	}

	/**
	 * Format Newlines
	 *
	 * Converts newline characters into either <p> tags or <br />
	 *
	 * @param string
	 * @return string
	 */
	protected function _formatNewlines($str) {
		if ($str === '') {
			return $str;
		}

		if (strpos($str, "\n") === false && !in_array($this->lastBlockElement, $this->innerBlockRequired)) {
			return $str;
		}

		// Convert two consecutive newlines to paragraphs
		$str = str_replace("\n\n", "</p>\n\n<p>", $str);

		// Convert single spaces to <br /> tags
		$str = preg_replace("/([^\n])(\n)([^\n])/", "\\1<br />\\2\\3", $str);

		// Wrap the whole enchilada in enclosing paragraphs
		if ($str !== "\n") {
			// We trim off the right-side new line so that the closing </p> tag
			// will be positioned immediately following the string, matching
			// the behavior of the opening <p> tag
			$str = '<p>' . rtrim($str) . '</p>';
		}

		// Remove empty paragraphs if they are on the first line, as this
		// is a potential unintended consequence of the previous code
		$str = preg_replace("/<p><\/p>(.*)/", "\\1", $str, 1);

		return $str;
	}

	/**
	 * Protect Characters
	 *
	 * Protects special characters from being formatted later
	 * We don't want quotes converted within tags so we'll temporarily convert them to {@DQ} and {@SQ}
	 * and we don't want double dashes converted to emdash entities, so they are marked with {@DD}
	 * likewise double spaces are converted to {@NBS} to prevent entity conversion
	 *
	 * @param array
	 * @return string
	 */
	protected function _protectCharacters($match) {
		return str_replace(array("'", '"', '--', '  '), array('{@SQ}', '{@DQ}', '{@DD}', '{@NBS}'), $match[0]);
	}

	/**
	 * Convert newlines to HTML line breaks except within PRE tags
	 *
	 * @param string
	 * @return string
	 */
	public function nl2brExceptPre($str) {
		$ex = explode("pre>", $str);
		$ct = count($ex);

		$newstr = '';
		for ($i = 0; $i < $ct; $i++) {
			if (($i % 2) == 0) {
				$newstr .= nl2br($ex[$i]);
			} else {
				$newstr .= $ex[$i];
			}

			if ($ct - 1 != $i) {
				$newstr .= "pre>";
			}
		}

		return $newstr;
	}

}
