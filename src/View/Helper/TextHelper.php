<?php

namespace Tools\View\Helper;

use Cake\Core\App;
use Cake\Core\Exception\CakeException;
use Cake\View\Helper\TextHelper as CakeTextHelper;
use Cake\View\View;
use Tools\I18n\Number;
use Tools\Utility\Utility;

if (!defined('CHAR_HELLIP')) {
	define('CHAR_HELLIP', '&#8230;'); # � (horizontal ellipsis = three dot leader)
}

/**
 * This helper extends the core Text helper and adds some improvements.
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
 * @mixin \Tools\Utility\Text
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class TextHelper extends CakeTextHelper {

	/**
	 * Cake Utility Text instance
	 *
	 * @var \Cake\Utility\Text
	 */
	protected $_engine;

	/**
	 * ### Settings:
	 *
	 * - `engine` Class name to use to replace Text functionality.
	 *            The class needs to be placed in the `Utility` directory.
	 *
	 * @param \Cake\View\View $view the view object the helper is attached to.
	 * @param array<string, mixed> $config Settings array Settings array
	 */
	public function __construct(View $view, array $config = []) {
		$config += ['engine' => 'Tools.Text'];

		parent::__construct($view, $config);

		/** @psalm-var class-string<\Cake\Utility\Text>|null $engineClass */
		$engineClass = App::className($config['engine'], 'Utility');
		if ($engineClass === null) {
			throw new CakeException(sprintf('Class for `%s` could not be found', $config['engine']));
		}

		$this->_engine = new $engineClass($config);
	}

	/**
	 * Call methods from String utility class
	 *
	 * @param string $method Method to invoke
	 * @param array $params Array of params for the method.
	 * @return mixed Whatever is returned by called method, or false on failure
	 */
	public function __call(string $method, array $params): mixed {
		return $this->_engine->{$method}(...$params);
	}

	/**
	 * Minimizes the given URL to a maximum length
	 *
	 * @param string $url the url
	 * @param int|null $max the maximum length
	 * @param array<string, mixed> $options
	 * - placeholder
	 * @return string the manipulated url (+ eventuell ...)
	 */
	public function minimizeUrl(string $url, ?int $max = null, array $options = []): string {
		// check if there is nothing to do
		if (!$url || mb_strlen($url) <= (int)$max) {
			return $url;
		}
		// http:// etc has not to be displayed, so
		$url = Utility::stripProtocol($url);
		// cut the parameters
		if (mb_strpos($url, '/') !== false) {
			/** @var string $url */
			$url = strtok($url, '/');
		}
		// return if the url is short enough
		if (mb_strlen($url) <= (int)$max) {
			return $url;
		}
		// otherwise cut a part in the middle (but only if long enough!)
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
	 * Removes http:// or other protocols from the link.
	 *
	 * @param string $url
	 * @param array<string> $protocols Defaults to http and https. Pass empty array for all.
	 * @return string strippedUrl
	 */
	public function stripProtocol(string $url, array $protocols = ['http', 'https']): string {
		return Utility::stripProtocol($url, $protocols);
	}

	/**
	 * Transforming int values into ordinal numbers (1st, 3rd, ...).
	 * When using HTML, you can use <sup>, as well.
	 *
	 * @param int $num The number to be suffixed.
	 * @param bool $sup Whether to wrap the suffix in a superscript (<sup>) tag on output.
	 * @return string ordinal
	 */
	public function ordinalNumber(int $num = 0, bool $sup = false): string {
		$ordinal = Number::ordinal($num);

		return ($sup) ? $num . '<sup>' . $ordinal . '</sup>' : $num . $ordinal;
	}

	/**
	 * Syntax highlighting using php internal highlighting
	 *
	 * @param string $file Filename
	 * @return string
	 */
	public function highlightFile(string $file): string {
		return highlight_file($file, true);
	}

	/**
	 * Syntax highlighting using php internal highlighting
	 *
	 * @param string $string Content
	 * @return string
	 */
	public function highlightString(string $string): string {
		return highlight_string($string, true);
	}

}
