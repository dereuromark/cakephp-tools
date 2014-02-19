<?php

/**
 * Draft 0.2 for PHP argument order fix
 */

App::uses('Str', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * @see https://bugs.php.net/bug.php?id=44794
 */
class StrTest extends MyCakeTestCase {

	public function testDebugInfo() {
		$this->skipIf(php_sapi_name() === 'cli', 'Only for webtest runner');
		$functionLists = array(
			'Array' => array(
				'array_change_key_case',
				'array_chunk',
				'array_combine',
				'array_count_values',
				'array_diff_assoc',
				'array_diff_key',
				'array_diff_uassoc',
				'array_diff_ukey',
				'array_diff',
				'array_fill_keys',
				'array_fill',
				'array_filter',
				'array_flip',
				'array_intersect_assoc',
				'array_intersect_key',
				'array_intersect_uassoc',
				'array_intersect_ukey',
				'array_intersect',
				'array_key_exists',
				'array_keys',
				'array_map',
				'array_merge_recursive',
				'array_merge',
				'array_multisort',
				'array_pad',
				'array_pop',
				'array_product',
				'array_push',
				'array_rand',
				'array_reduce',
				'array_replace_recursive',
				'array_replace',
				'array_reverse',
				'array_search',
				'array_shift',
				'array_slice',
				'array_splice',
				'array_sum',
				'array_udiff_assoc',
				'array_udiff_uassoc',
				'array_udiff',
				'array_uintersect_assoc',
				'array_uintersect_uassoc',
				'array_uintersect',
				'array_unique',
				'array_unshift',
				'array_values',
				'array_walk_recursive',
				'array_walk',
				'array',
				'arsort',
				'asort',
				'compact',
				'count',
				'current',
				'each',
				'end',
				'extract',
				'in_array',
				'key',
				'krsort',
				'ksort',
				'list',
				'natcasesort',
				'natsort',
				'next',
				'pos',
				'prev',
				'range',
				'reset',
				'rsort',
				'shuffle',
				'sizeof',
				'sort',
				'uasort',
				'uksort',
				'usort'),
			'String' => array(
				'addcslashes',
				'addslashes',
				'bin2hex',
				'chop',
				'chr',
				'chunk_split',
				'convert_cyr_string',
				'convert_uudecode',
				'convert_uuencode',
				'count_chars',
				'crc32',
				'crypt',
				'echo',
				'explode',
				'fprintf',
				'get_html_translation_table',
				'hebrev',
				'hebrevc',
				'hex2bin',
				'html_entity_decode',
				'htmlentities',
				'htmlspecialchars_decode',
				'htmlspecialchars',
				'implode',
				'join',
				'lcfirst',
				'levenshtein',
				'localeconv',
				'ltrim',
				'md5_file',
				'md5',
				'metaphone',
				'money_format',
				'nl_langinfo',
				'nl2br',
				'number_format',
				'ord',
				'parse_str',
				'print',
				'printf',
				'quoted_printable_decode',
				'quoted_printable_encode',
				'quotemeta',
				'rtrim',
				'setlocale',
				'sha1_file',
				'sha1',
				'similar_text',
				'soundex',
				'sprintf',
				'sscanf',
				'str_getcsv',
				'str_ireplace',
				'str_pad',
				'str_repeat',
				'str_replace',
				'str_rot13',
				'str_shuffle',
				'str_split',
				'str_word_count',
				'strcasecmp',
				'strchr',
				'strcmp',
				'strcoll',
				'strcspn',
				'strip_tags',
				'stripcslashes',
				'stripos',
				'stripslashes',
				'stristr',
				'strlen',
				'strnatcasecmp',
				'strnatcmp',
				'strncasecmp',
				'strncmp',
				'strpbrk',
				'strpos',
				'strrchr',
				'strrev',
				'strripos',
				'strrpos',
				'strspn',
				'strstr',
				'strtok',
				'strtolower',
				'strtoupper',
				'strtr',
				'substr_compare',
				'substr_count',
				'substr_replace',
				'substr',
				'trim',
				'ucfirst',
				'ucwords',
				'vfprintf',
				'vprintf',
				'vsprintf',
				'wordwrap'),
			);
		$res = '';
		foreach ($functionLists as $type => $functions) {
			$res .= "$type functions:\n";
			foreach ($functions as $function) {
				try {
					$needle = new ReflectionParameter($function, "needle");
					$haystack = new ReflectionParameter($function, "haystack");
					$order = ($needle->getPosition() < $haystack->getPosition() ? '$needle, $haystack' : '$haystack, $needle');
					$res .= sprintf("%20s %s\n", $function, $order);
				}
				catch (ReflectionException $e) {
					continue;
				}
			}
			$res .= "\n";
		}
		$this->debug($res);
	}

	/**
	 * Fixed
	 * - documented return type (mixed)
	 * - argument order
	 * - missing underscore
	 */
	public function testStrStr() {
		$res = Str::str('some', 'more some text');
		$expected = 'some text';
		$this->assertSame($expected, $res);

		$res = Str::str('some', 'more som text');
		$expected = false;
		$this->assertSame($expected, $res);
	}

	/**
	 * No changes
	 *
	 * @return void
	 */
	public function testStrReplace() {
		$res = Str::replace('some', 'more', 'in some text');
		$expected = 'in more text';
		$this->assertSame($expected, $res);

		$count = 0;
		$res = Str::replace('some', 'more', 'in some text', $count);
		$this->assertSame($expected, $res);
		$this->assertSame(1, $count);
	}

	/**
	 * No changes
	 *
	 * @return void
	 */
	public function testSubstrReplace() {
		$res = Str::substrReplace('some', 'more', 0, 0);
		$expected = 'moresome';
		$this->assertSame($expected, $res);

		$res = Str::substrReplace('some', 'more', 1, 0);
		$expected = 'smoreome';
		$this->assertSame($expected, $res);
	}

	/**
	 * No changes
	 *
	 * @return void
	 */
	public function testCount() {
		$res = Str::count('more', 'some more and more text');
		$this->assertSame(2, $res);

		$res = Str::count('more', 'some text');
		$this->assertSame(0, $res);

		$res = Str::count('more', 'some more and more text and even more text', 10, 20);
		$this->assertSame(1, $res);
	}

	/**
	 * Very strange method
	 *
	 * fixed
	 * - documented return type (mixed)
	 * - argument order
	 * - missing underscore
	 * - naming scheme
	 *
	 * @return void
	 */
	public function testStrLastChr() {
		$res = Str::lastChr('some', 'more some text');
		$expected = 'some text';
		$this->assertSame($expected, $res);

		// WTF?
		$res = Str::lastChr('some', 'more som text');
		$expected = 'som text';
		$this->assertSame($expected, $res);

		$res = Str::lastChr('xome', 'more som text');
		$expected = 'xt';
		$this->assertSame($expected, $res);

		$res = Str::lastChr('abc', 'more som text');
		$expected = false;
		$this->assertSame($expected, $res);

		$res = Str::lastChr(120, 'more som text');
		$expected = 'xt';
		$this->assertSame($expected, $res);
	}

}
