<?php

namespace Tools\View\Helper;

use Cake\View\Helper;

/**
 * Protect email and alike on output, against bots mainly.
 *
 * TODO: make snippets more "css and background image" (instead of inline img links)
 *
 * @author Mark Scherer
 * @license MIT
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ObfuscateHelper extends Helper {

	/**
	 * Other helpers used
	 *
	 * @var array<int|string, array<string, mixed>|string>
	 */
	protected array $helpers = ['Html'];

	/**
	 * - `script`: Asset appended to the view block by emailLink(), `false` to include it yourself
	 * - `scriptBlock`: View block the script is appended to
	 * - `placeholder`: Replacements turning the address into the no-JS link text
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'script' => 'Tools.obfuscate',
		'scriptBlock' => 'script',
		'placeholder' => [
			'@' => ' [at] ',
			'.' => ' [dot] ',
		],
	];

	/**
	 * It is still believed that encoding will stop spam-bots being able to find your email address.
	 * Nevertheless, encoded email address harvester are on the way (http://www.dreamweaverfever.com/experiments/spam/).
	 *
	 * Helper Function to Obfuscate Email by inserting a span tag (not more! not very secure on its own...)
	 * each part of this mail now does not make sense anymore on its own
	 * (striptags will not work either)
	 *
	 * @param string $mail Email, necessary (and valid - containing one @)
	 * @return string
	 */
	public function encodeEmail($mail) {
		$pieces = explode('@', $mail);
		if (count($pieces) < 2) {
			return $mail;
		}
		[$mail1, $mail2] = $pieces;

		return $this->encodeText($mail1) . '<span>@</span>' . $this->encodeText($mail2);
	}

	/**
	 * Obfuscates Email (works without JS!) to avoid spam bots to get it
	 *
     * @deprecated Use emailLink() instead, it needs no inline script and works with a strict CSP.
     * @param string $mail Email to encode
     * @param string|null $text Text, ptional (if none is given, email will be text as well)
     * @param array $params ?subject=y&body=y to be attached to "mailto:xyz"
     * @param array<string, mixed> $attr HTML tag attributes, escaped unless `escape` is explicitly set to false
     * @return string Save string with JS generated link around email (and non JS fallback)
	 */
	public function encodeEmailUrl($mail, $text = null, array $params = [], array $attr = []) {
		$defaults = [
			'title' => __d('tools', 'for use in an external mail client'),
			'class' => 'email',
		];

		if (!$text) {
			$text = $this->encodeEmail($mail);
		}

		$encMail = 'mailto:' . $mail;
		$querystring = $this->mailtoQuery($params);

		$attr = array_merge($defaults, $attr);

		$xmail = $this->Html->link('', $encMail . $querystring, $attr);
		$xmail1 = mb_substr((string)$xmail, 0, -4);
		$xmail2 = mb_substr((string)$xmail, -4, 4);

		$len = mb_strlen($xmail1);
		$i = 0;
		$par = [];
		while ($i < $len) {
			$c = mt_rand(2, 6);
			$par[] = (mb_substr($xmail1, $i, $c));
			$i += $c;
		}
		$join = implode(' + ', array_map([$this, 'jsString'], $par));

		$request = $this->getView()->getRequest();
		$cspNonce = (string)($request->getAttribute('cspScriptNonce') ?? $request->getAttribute('cspNonce', ''));
		$nonceAttr = $cspNonce !== '' ? ' nonce="' . h($cspNonce) . '"' : '';

		return '<script language=javascript' . $nonceAttr . '><!--
	document.write(' . $join . ');
	//--></script>
		' . $text . '
	<script language=javascript' . $nonceAttr . '><!--
	document.write(' . $this->jsString($xmail2) . ');
	//--></script>';
	}

	/**
	 * Renders a mailto link without the address in the markup and without inline script.
	 *
	 * The link points to `#` and carries the encoded mailto URL in `data-tools-obfuscate`.
	 * The `Tools.obfuscate` script (appended to the script block by default) decodes it.
	 * Without JS the link shows `$text`, or a placeholder like `name [at] example [dot] com`.
	 *
	 * @param string $mail Email address
	 * @param string|null $text Link text, defaults to the placeholder that the script replaces with the address
	 * @param array<string, mixed> $params Query params like `subject` or `body` attached to the mailto URL
	 * @param array<string, mixed> $attr HTML tag attributes, escaped unless `escape` is explicitly set to false
	 * @return string
	 */
	public function emailLink(string $mail, ?string $text = null, array $params = [], array $attr = []): string {
		$attr += [
			'title' => __d('tools', 'for use in an external mail client'),
			'class' => 'email',
		];
		$attr['data-tools-obfuscate'] = $this->encodeMailto('mailto:' . $mail . $this->mailtoQuery($params));

		if ($text === null || $text === '') {
			$text = strtr($mail, (array)$this->getConfig('placeholder'));
			if (($attr['escape'] ?? true) === false || ($attr['escapeTitle'] ?? true) === false) {
				$text = h($text);
			}
			$attr['data-tools-obfuscate-text'] = '1';
		}

		$script = $this->getConfig('script');
		if ($script) {
			$this->Html->script($script, ['block' => $this->getConfig('scriptBlock')]);
		}

		return $this->Html->link($text, '#', $attr);
	}

	/**
	 * Reversible encoding mirrored by webroot/js/obfuscate.js: base64, then string reversed.
	 *
	 * @param string $mailto
	 * @return string
	 */
	protected function encodeMailto(string $mailto): string {
		return strrev(base64_encode($mailto));
	}

	/**
	 * @param array<string, mixed> $params
	 * @return string
	 */
	protected function mailtoQuery(array $params): string {
		$querystring = '';
		foreach ($params as $key => $val) {
			$querystring .= ($querystring ? '&' : '?') . rawurlencode((string)$key) . '=' . rawurlencode((string)$val);
		}

		return $querystring;
	}

	/**
	 * Encodes a value as a JavaScript string literal that is safe inside an inline script.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function jsString(string $value): string {
		return (string)json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
	}

	/**
	 * Encodes Piece of Text (without usage of JS!) to avoid spam bots to get it
	 *
	 * @param string $text Text to encode
	 * @return string (randomly encoded)
	 */
	public function encodeText($text) {
		$encmail = '';
		$length = mb_strlen($text);
		for ($i = 0; $i < $length; $i++) {
			$encMod = mt_rand(0, 2);
			switch ($encMod) {
				case 0: // None
					$encmail .= h(mb_substr($text, $i, 1));

					break;
				case 1: // Decimal
					$encmail .= '&#' . (int)mb_ord(mb_substr($text, $i, 1)) . ';';

					break;
				case 2: // Hexadecimal
					$encmail .= '&#x' . dechex((int)mb_ord(mb_substr($text, $i, 1))) . ';';

					break;
			}
		}

		return $encmail;
	}

	/**
	 * test@test.de becomes t..t@t..t.de
	 *
	 * @param string $mail Valid(!) email address
	 * @return string
	 */
	public static function hideEmail($mail) {
		$mailParts = explode('@', $mail, 2);
		$domainParts = explode('.', $mailParts[1], 2);

		$user = mb_substr($mailParts[0], 0, 1) . '..' . mb_substr($mailParts[0], -1, 1);
		$domain = mb_substr($domainParts[0], 0, 1) . '..' . mb_substr($domainParts[0], -1, 1) . '.' . $domainParts[1];

		return $user . '@' . $domain;
	}

	/**
	 * Word Censoring Function
	 *
	 * Supply a string and an array of disallowed words and any
	 * matched words will be converted to #### or to the replacement
	 * word you've submitted.
	 *
	 * @param string $str the text string
	 * @param array $censored the array of censored words
	 * @param string|null $replacement the optional replacement value
	 * @return string
	 */
	public function wordCensor(string $str, array $censored, ?string $replacement = null) {
		if (!$censored) {
			return $str;
		}
		$str = ' ' . $str . ' ';

		// \w, \b and a few others do not match on a unicode character
		// set for performance reasons. As a result words like ..ber
		// will not match on a word boundary. Instead, we'll assume that
		// a bad word will be bookended by any of these characters.
		$delim = '[-_\'\"`() {}<>\[\]|!?@#%&,.:;^~*+=\/ 0-9\n\r\t]';

		foreach ($censored as $badword) {
			if ($replacement !== null) {
				$str = (string)preg_replace("/({$delim})(" . str_replace('\*', '\w*?', preg_quote((string)$badword, '/')) . ")({$delim})/i", "\\1{$replacement}\\3", $str);
			} else {
				$str = (string)preg_replace_callback("/({$delim})(" . str_replace('\*', '\w*?', preg_quote((string)$badword, '/')) . ")({$delim})/i", function ($matches) {
					return $matches[1] . str_repeat('#', strlen($matches[2])) . $matches[3];
				}, $str);
			}
		}

		return trim($str);
	}

}
