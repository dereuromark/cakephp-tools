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
	 * Minimizes the given URL to a minimum length.
	 *
	 * @param string $url the url
	 * @param int|null $max the maximum length
	 * @param array<string, mixed> $options
	 * - placeholder
	 * @return string The manipulated url (+ maybe ...)
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
			/** @var non-empty-string $url */
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

	/**
	 * Replace placeholders with links.
	 *
	 * @param string $text The text to operate on.
	 * @param array<string, mixed> $htmlOptions The options for the generated links.
	 * @return string The text with links inserted.
	 */
	protected function _linkUrls(string $text, array $htmlOptions): string {
		if (!isset($htmlOptions['callable'])) {
			$replace = [];
			foreach ($this->_placeholders as $hash => $content) {
				$link = $url = $content['content'];
				$envelope = $content['envelope'];
				if (!preg_match('#^[a-z]+\://#i', $url)) {
					$url = 'http://' . $url;
				}

				$linkOptions = $htmlOptions;
				unset($htmlOptions['maxLength'], $htmlOptions['stripProtocol'], $htmlOptions['ellipsis']);

				$replace[$hash] = $envelope[0] . $this->Html->link($this->prepareLinkName($link, $linkOptions), $url, $htmlOptions) . $envelope[1];
			}

			return strtr($text, $replace);
		}

		$callable = $htmlOptions['callable'];
		unset($htmlOptions['callable']);
		if (!is_callable($callable)) {
			throw new CakeException(sprintf('The `outbound` option must be a callable, %s given', getType($callable)));
		}

		$replace = [];
		foreach ($this->_placeholders as $hash => $content) {
			$link = $url = $content['content'];
			$envelope = $content['envelope'];
			if (!preg_match('#^[a-z]+\://#i', $url)) {
				$url = 'http://' . $url;
			}
			$replace[$hash] = $envelope[0] . $callable($link, $url, $htmlOptions) . $envelope[1];
		}

		return strtr($text, $replace);
	}

	/**
	 * @param string $name
	 * @param array $options Options:
	 * - stripProtocol: bool (defaults to true)
	 * - maxLength: int (defaults to 50)
	 * - ellipsis (defaults to UTF8 version)
	 *
	 * @return string html/$plain
	 */
	public function prepareLinkName(string $name, array $options = []): string {
		// strip protocol if desired (default)
		if (!isset($options['stripProtocol']) || $options['stripProtocol'] !== false) {
			$name = (string)preg_replace('(^https?://)', '', $name);
		}
		if (!isset($options['maxLength'])) {
			$options['maxLength'] = 50; # should be long enough for most cases
		}
		// shorten display name if desired (default)
		if (!empty($options['maxLength']) && mb_strlen($name) > $options['maxLength']) {
			$name = mb_substr($name, 0, $options['maxLength']);
			$name .= $options['ellipsis'] ?? '…';
		}

		return $name;
	}

}
