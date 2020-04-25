<?php

namespace Tools\Test\TestCase\Utility;

use Shim\TestSuite\TestCase;
use Tools\Utility\L10n;

/**
 * L10nTest class
 */
class L10nTest extends TestCase {

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * testMap method
	 *
	 * @return void
	 */
	public function testMap() {
		$localize = new L10n();

		$result = $localize->map(['afr', 'af']);
		$expected = ['afr' => 'af', 'af' => 'afr'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['sqi', 'sq']);
		$expected = ['sqi' => 'sq', 'sq' => 'sqi'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['alb', 'sq']);
		$expected = ['alb' => 'sq', 'sq' => 'sqi'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ara', 'ar']);
		$expected = ['ara' => 'ar', 'ar' => 'ara'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['hye', 'hy']);
		$expected = ['hye' => 'hy', 'hy' => 'hye'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['eus', 'eu']);
		$expected = ['eus' => 'eu', 'eu' => 'eus'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['baq', 'eu']);
		$expected = ['baq' => 'eu', 'eu' => 'eus'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['bos', 'bs']);
		$expected = ['bos' => 'bs', 'bs' => 'bos'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['bul', 'bg']);
		$expected = ['bul' => 'bg', 'bg' => 'bul'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['bel', 'be']);
		$expected = ['bel' => 'be', 'be' => 'bel'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['cat', 'ca']);
		$expected = ['cat' => 'ca', 'ca' => 'cat'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['chi', 'zh']);
		$expected = ['chi' => 'zh', 'zh' => 'zho'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['zho', 'zh']);
		$expected = ['zho' => 'zh', 'zh' => 'zho'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['hrv', 'hr']);
		$expected = ['hrv' => 'hr', 'hr' => 'hrv'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ces', 'cs']);
		$expected = ['ces' => 'cs', 'cs' => 'ces'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['cze', 'cs']);
		$expected = ['cze' => 'cs', 'cs' => 'ces'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['dan', 'da']);
		$expected = ['dan' => 'da', 'da' => 'dan'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['dut', 'nl']);
		$expected = ['dut' => 'nl', 'nl' => 'nld'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['nld', 'nl']);
		$expected = ['nld' => 'nl', 'nl' => 'nld'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['nld']);
		$expected = ['nld' => 'nl'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['dut']);
		$expected = ['dut' => 'nl'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['eng', 'en']);
		$expected = ['eng' => 'en', 'en' => 'eng'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['est', 'et']);
		$expected = ['est' => 'et', 'et' => 'est'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['fao', 'fo']);
		$expected = ['fao' => 'fo', 'fo' => 'fao'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['fas', 'fa']);
		$expected = ['fas' => 'fa', 'fa' => 'fas'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['per', 'fa']);
		$expected = ['per' => 'fa', 'fa' => 'fas'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['fin', 'fi']);
		$expected = ['fin' => 'fi', 'fi' => 'fin'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['fra', 'fr']);
		$expected = ['fra' => 'fr', 'fr' => 'fra'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['fre', 'fr']);
		$expected = ['fre' => 'fr', 'fr' => 'fra'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['gla', 'gd']);
		$expected = ['gla' => 'gd', 'gd' => 'gla'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['glg', 'gl']);
		$expected = ['glg' => 'gl', 'gl' => 'glg'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['deu', 'de']);
		$expected = ['deu' => 'de', 'de' => 'deu'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ger', 'de']);
		$expected = ['ger' => 'de', 'de' => 'deu'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ell', 'el']);
		$expected = ['ell' => 'el', 'el' => 'gre'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['gre', 'el']);
		$expected = ['gre' => 'el', 'el' => 'gre'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['heb', 'he']);
		$expected = ['heb' => 'he', 'he' => 'heb'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['hin', 'hi']);
		$expected = ['hin' => 'hi', 'hi' => 'hin'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['hun', 'hu']);
		$expected = ['hun' => 'hu', 'hu' => 'hun'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ice', 'is']);
		$expected = ['ice' => 'is', 'is' => 'isl'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['isl', 'is']);
		$expected = ['isl' => 'is', 'is' => 'isl'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ind', 'id']);
		$expected = ['ind' => 'id', 'id' => 'ind'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['gle', 'ga']);
		$expected = ['gle' => 'ga', 'ga' => 'gle'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ita', 'it']);
		$expected = ['ita' => 'it', 'it' => 'ita'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['jpn', 'ja']);
		$expected = ['jpn' => 'ja', 'ja' => 'jpn'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['kaz', 'kk']);
		$expected = ['kaz' => 'kk', 'kk' => 'kaz'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['kor', 'ko']);
		$expected = ['kor' => 'ko', 'ko' => 'kor'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['lav', 'lv']);
		$expected = ['lav' => 'lv', 'lv' => 'lav'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['lit', 'lt']);
		$expected = ['lit' => 'lt', 'lt' => 'lit'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['mac', 'mk']);
		$expected = ['mac' => 'mk', 'mk' => 'mkd'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['mkd', 'mk']);
		$expected = ['mkd' => 'mk', 'mk' => 'mkd'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['may', 'ms']);
		$expected = ['may' => 'ms', 'ms' => 'msa'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['msa', 'ms']);
		$expected = ['msa' => 'ms', 'ms' => 'msa'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['mlt', 'mt']);
		$expected = ['mlt' => 'mt', 'mt' => 'mlt'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['nor', 'no']);
		$expected = ['nor' => 'no', 'no' => 'nor'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['nob', 'nb']);
		$expected = ['nob' => 'nb', 'nb' => 'nob'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['nno', 'nn']);
		$expected = ['nno' => 'nn', 'nn' => 'nno'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['pol', 'pl']);
		$expected = ['pol' => 'pl', 'pl' => 'pol'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['por', 'pt']);
		$expected = ['por' => 'pt', 'pt' => 'por'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['roh', 'rm']);
		$expected = ['roh' => 'rm', 'rm' => 'roh'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ron', 'ro']);
		$expected = ['ron' => 'ro', 'ro' => 'ron'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['rum', 'ro']);
		$expected = ['rum' => 'ro', 'ro' => 'ron'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['rus', 'ru']);
		$expected = ['rus' => 'ru', 'ru' => 'rus'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['sme', 'se']);
		$expected = ['sme' => 'se', 'se' => 'sme'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['srp', 'sr']);
		$expected = ['srp' => 'sr', 'sr' => 'srp'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['slk', 'sk']);
		$expected = ['slk' => 'sk', 'sk' => 'slk'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['slo', 'sk']);
		$expected = ['slo' => 'sk', 'sk' => 'slk'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['slv', 'sl']);
		$expected = ['slv' => 'sl', 'sl' => 'slv'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['wen', 'sb']);
		$expected = ['wen' => 'sb', 'sb' => 'wen'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['spa', 'es']);
		$expected = ['spa' => 'es', 'es' => 'spa'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['swe', 'sv']);
		$expected = ['swe' => 'sv', 'sv' => 'swe'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['tha', 'th']);
		$expected = ['tha' => 'th', 'th' => 'tha'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['tso', 'ts']);
		$expected = ['tso' => 'ts', 'ts' => 'tso'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['tsn', 'tn']);
		$expected = ['tsn' => 'tn', 'tn' => 'tsn'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['tur', 'tr']);
		$expected = ['tur' => 'tr', 'tr' => 'tur'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ukr', 'uk']);
		$expected = ['ukr' => 'uk', 'uk' => 'ukr'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['urd', 'ur']);
		$expected = ['urd' => 'ur', 'ur' => 'urd'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['ven', 've']);
		$expected = ['ven' => 've', 've' => 'ven'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['vie', 'vi']);
		$expected = ['vie' => 'vi', 'vi' => 'vie'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['xho', 'xh']);
		$expected = ['xho' => 'xh', 'xh' => 'xho'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['cy', 'cym']);
		$expected = ['cym' => 'cy', 'cy' => 'cym'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['yid', 'yi']);
		$expected = ['yid' => 'yi', 'yi' => 'yid'];
		$this->assertEquals($expected, $result);

		$result = $localize->map(['zul', 'zu']);
		$expected = ['zul' => 'zu', 'zu' => 'zul'];
		$this->assertEquals($expected, $result);
	}

	/**
	 * testCatalog method
	 *
	 * @return void
	 */
	public function testCatalog() {
		$localize = new L10n();

		$result = $localize->catalog(['af']);
		$expected = [
			'af' => ['language' => 'Afrikaans', 'locale' => 'afr', 'localeFallback' => 'afr', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ar', 'ar-ae', 'ar-bh', 'ar-dz', 'ar-eg', 'ar-iq', 'ar-jo', 'ar-kw', 'ar-lb', 'ar-ly', 'ar-ma',
			'ar-om', 'ar-qa', 'ar-sa', 'ar-sy', 'ar-tn', 'ar-ye']);
		$expected = [
			'ar' => ['language' => 'Arabic', 'locale' => 'ara', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-ae' => ['language' => 'Arabic (U.A.E.)', 'locale' => 'ar_ae', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-bh' => ['language' => 'Arabic (Bahrain)', 'locale' => 'ar_bh', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-dz' => ['language' => 'Arabic (Algeria)', 'locale' => 'ar_dz', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-eg' => ['language' => 'Arabic (Egypt)', 'locale' => 'ar_eg', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-iq' => ['language' => 'Arabic (Iraq)', 'locale' => 'ar_iq', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-jo' => ['language' => 'Arabic (Jordan)', 'locale' => 'ar_jo', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-kw' => ['language' => 'Arabic (Kuwait)', 'locale' => 'ar_kw', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-lb' => ['language' => 'Arabic (Lebanon)', 'locale' => 'ar_lb', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-ly' => ['language' => 'Arabic (Libya)', 'locale' => 'ar_ly', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-ma' => ['language' => 'Arabic (Morocco)', 'locale' => 'ar_ma', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-om' => ['language' => 'Arabic (Oman)', 'locale' => 'ar_om', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-qa' => ['language' => 'Arabic (Qatar)', 'locale' => 'ar_qa', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-sa' => ['language' => 'Arabic (Saudi Arabia)', 'locale' => 'ar_sa', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-sy' => ['language' => 'Arabic (Syria)', 'locale' => 'ar_sy', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-tn' => ['language' => 'Arabic (Tunisia)', 'locale' => 'ar_tn', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'ar-ye' => ['language' => 'Arabic (Yemen)', 'locale' => 'ar_ye', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['be']);
		$expected = [
			'be' => ['language' => 'Byelorussian', 'locale' => 'bel', 'localeFallback' => 'bel', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['bg']);
		$expected = [
			'bg' => ['language' => 'Bulgarian', 'locale' => 'bul', 'localeFallback' => 'bul', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['bs']);
		$expected = [
			'bs' => ['language' => 'Bosnian', 'locale' => 'bos', 'localeFallback' => 'bos', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ca']);
		$expected = [
			'ca' => ['language' => 'Catalan', 'locale' => 'cat', 'localeFallback' => 'cat', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['cs']);
		$expected = [
			'cs' => ['language' => 'Czech', 'locale' => 'ces', 'localeFallback' => 'ces', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['da']);
		$expected = [
			'da' => ['language' => 'Danish', 'locale' => 'dan', 'localeFallback' => 'dan', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['de', 'de-at', 'de-ch', 'de-de', 'de-li', 'de-lu']);
		$expected = [
			'de' => ['language' => 'German (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'de-at' => ['language' => 'German (Austria)', 'locale' => 'de_at', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'de-ch' => ['language' => 'German (Swiss)', 'locale' => 'de_ch', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'de-de' => ['language' => 'German (Germany)', 'locale' => 'de_de', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'de-li' => ['language' => 'German (Liechtenstein)', 'locale' => 'de_li', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'de-lu' => ['language' => 'German (Luxembourg)', 'locale' => 'de_lu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['el']);
		$expected = [
			'el' => ['language' => 'Greek', 'locale' => 'ell', 'localeFallback' => 'ell', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['en', 'en-au', 'en-bz', 'en-ca', 'en-gb', 'en-ie', 'en-jm', 'en-nz', 'en-tt', 'en-us', 'en-za']);
		$expected = [
			'en' => ['language' => 'English', 'locale' => 'eng', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-au' => ['language' => 'English (Australian)', 'locale' => 'en_au', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-bz' => ['language' => 'English (Belize)', 'locale' => 'en_bz', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-ca' => ['language' => 'English (Canadian)', 'locale' => 'en_ca', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-gb' => ['language' => 'English (British)', 'locale' => 'en_gb', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-ie' => ['language' => 'English (Ireland)', 'locale' => 'en_ie', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-jm' => ['language' => 'English (Jamaica)', 'locale' => 'en_jm', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-nz' => ['language' => 'English (New Zealand)', 'locale' => 'en_nz', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-tt' => ['language' => 'English (Trinidad)', 'locale' => 'en_tt', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-us' => ['language' => 'English (United States)', 'locale' => 'en_us', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'en-za' => ['language' => 'English (South Africa)', 'locale' => 'en_za', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['es', 'es-ar', 'es-bo', 'es-cl', 'es-co', 'es-cr', 'es-do', 'es-ec', 'es-es', 'es-gt', 'es-hn',
			'es-mx', 'es-ni', 'es-pa', 'es-pe', 'es-pr', 'es-py', 'es-sv', 'es-uy', 'es-ve']);
		$expected = [
			'es' => ['language' => 'Spanish (Spain - Traditional)', 'locale' => 'spa', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-ar' => ['language' => 'Spanish (Argentina)', 'locale' => 'es_ar', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-bo' => ['language' => 'Spanish (Bolivia)', 'locale' => 'es_bo', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-cl' => ['language' => 'Spanish (Chile)', 'locale' => 'es_cl', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-co' => ['language' => 'Spanish (Colombia)', 'locale' => 'es_co', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-cr' => ['language' => 'Spanish (Costa Rica)', 'locale' => 'es_cr', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-do' => ['language' => 'Spanish (Dominican Republic)', 'locale' => 'es_do', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-ec' => ['language' => 'Spanish (Ecuador)', 'locale' => 'es_ec', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-es' => ['language' => 'Spanish (Spain)', 'locale' => 'es_es', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-gt' => ['language' => 'Spanish (Guatemala)', 'locale' => 'es_gt', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-hn' => ['language' => 'Spanish (Honduras)', 'locale' => 'es_hn', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-mx' => ['language' => 'Spanish (Mexican)', 'locale' => 'es_mx', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-ni' => ['language' => 'Spanish (Nicaragua)', 'locale' => 'es_ni', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-pa' => ['language' => 'Spanish (Panama)', 'locale' => 'es_pa', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-pe' => ['language' => 'Spanish (Peru)', 'locale' => 'es_pe', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-pr' => ['language' => 'Spanish (Puerto Rico)', 'locale' => 'es_pr', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-py' => ['language' => 'Spanish (Paraguay)', 'locale' => 'es_py', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-sv' => ['language' => 'Spanish (El Salvador)', 'locale' => 'es_sv', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-uy' => ['language' => 'Spanish (Uruguay)', 'locale' => 'es_uy', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-ve' => ['language' => 'Spanish (Venezuela)', 'locale' => 'es_ve', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['et']);
		$expected = [
			'et' => ['language' => 'Estonian', 'locale' => 'est', 'localeFallback' => 'est', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['eu']);
		$expected = [
			'eu' => ['language' => 'Basque', 'locale' => 'eus', 'localeFallback' => 'eus', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['fa']);
		$expected = [
			'fa' => ['language' => 'Farsi', 'locale' => 'fas', 'localeFallback' => 'fas', 'charset' => 'utf-8', 'direction' => 'rtl'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['fi']);
		$expected = [
			'fi' => ['language' => 'Finnish', 'locale' => 'fin', 'localeFallback' => 'fin', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['fo']);
		$expected = [
			'fo' => ['language' => 'Faeroese', 'locale' => 'fao', 'localeFallback' => 'fao', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['fr', 'fr-be', 'fr-ca', 'fr-ch', 'fr-fr', 'fr-lu']);
		$expected = [
			'fr' => ['language' => 'French (Standard)', 'locale' => 'fra', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'fr-be' => ['language' => 'French (Belgium)', 'locale' => 'fr_be', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'fr-ca' => ['language' => 'French (Canadian)', 'locale' => 'fr_ca', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'fr-ch' => ['language' => 'French (Swiss)', 'locale' => 'fr_ch', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'fr-fr' => ['language' => 'French (France)', 'locale' => 'fr_fr', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'fr-lu' => ['language' => 'French (Luxembourg)', 'locale' => 'fr_lu', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ga']);
		$expected = [
			'ga' => ['language' => 'Irish', 'locale' => 'gle', 'localeFallback' => 'gle', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['gd', 'gd-ie']);
		$expected = [
			'gd' => ['language' => 'Gaelic (Scots)', 'locale' => 'gla', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'gd-ie' => ['language' => 'Gaelic (Irish)', 'locale' => 'gd_ie', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['gl']);
		$expected = [
			'gl' => ['language' => 'Galician', 'locale' => 'glg', 'localeFallback' => 'glg', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['he']);
		$expected = [
			'he' => ['language' => 'Hebrew', 'locale' => 'heb', 'localeFallback' => 'heb', 'charset' => 'utf-8', 'direction' => 'rtl'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['hi']);
		$expected = [
			'hi' => ['language' => 'Hindi', 'locale' => 'hin', 'localeFallback' => 'hin', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['hr']);
		$expected = [
			'hr' => ['language' => 'Croatian', 'locale' => 'hrv', 'localeFallback' => 'hrv', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['hu']);
		$expected = [
			'hu' => ['language' => 'Hungarian', 'locale' => 'hun', 'localeFallback' => 'hun', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['hy']);
		$expected = [
			'hy' => ['language' => 'Armenian - Armenia', 'locale' => 'hye', 'localeFallback' => 'hye', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['id']);
		$expected = [
			'id' => ['language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['is']);
		$expected = [
			'is' => ['language' => 'Icelandic', 'locale' => 'isl', 'localeFallback' => 'isl', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['it', 'it-ch']);
		$expected = [
			'it' => ['language' => 'Italian', 'locale' => 'ita', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'it-ch' => ['language' => 'Italian (Swiss) ', 'locale' => 'it_ch', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ja']);
		$expected = [
			'ja' => ['language' => 'Japanese', 'locale' => 'jpn', 'localeFallback' => 'jpn', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['kk']);
		$expected = [
			'kk' => ['language' => 'Kazakh', 'locale' => 'kaz', 'localeFallback' => 'kaz', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ko', 'ko-kp', 'ko-kr']);
		$expected = [
			'ko' => ['language' => 'Korean', 'locale' => 'kor', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'],
			'ko-kp' => ['language' => 'Korea (North)', 'locale' => 'ko_kp', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'],
			'ko-kr' => ['language' => 'Korea (South)', 'locale' => 'ko_kr', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['koi8-r', 'ru', 'ru-mo']);
		$expected = [
			'koi8-r' => ['language' => 'Russian', 'locale' => 'koi8_r', 'localeFallback' => 'rus', 'charset' => 'koi8-r', 'direction' => 'ltr'],
			'ru' => ['language' => 'Russian', 'locale' => 'rus', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'ru-mo' => ['language' => 'Russian (Moldavia)', 'locale' => 'ru_mo', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['lt']);
		$expected = [
			'lt' => ['language' => 'Lithuanian', 'locale' => 'lit', 'localeFallback' => 'lit', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['lv']);
		$expected = [
			'lv' => ['language' => 'Latvian', 'locale' => 'lav', 'localeFallback' => 'lav', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['mk', 'mk-mk']);
		$expected = [
			'mk' => ['language' => 'FYRO Macedonian', 'locale' => 'mkd', 'localeFallback' => 'mkd', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'mk-mk' => ['language' => 'Macedonian', 'locale' => 'mk_mk', 'localeFallback' => 'mkd', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ms']);
		$expected = [
			'ms' => ['language' => 'Malaysian', 'locale' => 'msa', 'localeFallback' => 'msa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['mt']);
		$expected = [
			'mt' => ['language' => 'Maltese', 'locale' => 'mlt', 'localeFallback' => 'mlt', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['nl', 'nl-be']);
		$expected = [
			'nl' => ['language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'nl-be' => ['language' => 'Dutch (Belgium)', 'locale' => 'nl_be', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog('nl');
		$expected = ['language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog('nld');
		$expected = ['language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog('dut');
		$expected = ['language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['nb']);
		$expected = [
			'nb' => ['language' => 'Norwegian Bokmal', 'locale' => 'nob', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['nn', 'no']);
		$expected = [
			'nn' => ['language' => 'Norwegian Nynorsk', 'locale' => 'nno', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'no' => ['language' => 'Norwegian', 'locale' => 'nor', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['pl']);
		$expected = [
			'pl' => ['language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['pt', 'pt-br']);
		$expected = [
			'pt' => ['language' => 'Portuguese (Portugal)', 'locale' => 'por', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'pt-br' => ['language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['rm']);
		$expected = [
			'rm' => ['language' => 'Rhaeto-Romanic', 'locale' => 'roh', 'localeFallback' => 'roh', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ro', 'ro-mo']);
		$expected = [
			'ro' => ['language' => 'Romanian', 'locale' => 'ron', 'localeFallback' => 'ron', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'ro-mo' => ['language' => 'Romanian (Moldavia)', 'locale' => 'ro_mo', 'localeFallback' => 'ron', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['sb']);
		$expected = [
			'sb' => ['language' => 'Sorbian', 'locale' => 'wen', 'localeFallback' => 'wen', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['sk']);
		$expected = [
			'sk' => ['language' => 'Slovak', 'locale' => 'slk', 'localeFallback' => 'slk', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['sl']);
		$expected = [
			'sl' => ['language' => 'Slovenian', 'locale' => 'slv', 'localeFallback' => 'slv', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['sq']);
		$expected = [
			'sq' => ['language' => 'Albanian', 'locale' => 'sqi', 'localeFallback' => 'sqi', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['sr']);
		$expected = [
			'sr' => ['language' => 'Serbian', 'locale' => 'srp', 'localeFallback' => 'srp', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['sv', 'sv-fi']);
		$expected = [
			'sv' => ['language' => 'Swedish', 'locale' => 'swe', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'sv-fi' => ['language' => 'Swedish (Finland)', 'locale' => 'sv_fi', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['se']);
		$expected = [
			'se' => ['language' => 'Sami', 'locale' => 'sme', 'localeFallback' => 'sme', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['th']);
		$expected = [
			'th' => ['language' => 'Thai', 'locale' => 'tha', 'localeFallback' => 'tha', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['tn']);
		$expected = [
			'tn' => ['language' => 'Tswana', 'locale' => 'tsn', 'localeFallback' => 'tsn', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['tr']);
		$expected = [
			'tr' => ['language' => 'Turkish', 'locale' => 'tur', 'localeFallback' => 'tur', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ts']);
		$expected = [
			'ts' => ['language' => 'Tsonga', 'locale' => 'tso', 'localeFallback' => 'tso', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['uk']);
		$expected = [
			'uk' => ['language' => 'Ukrainian', 'locale' => 'ukr', 'localeFallback' => 'ukr', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ur']);
		$expected = [
			'ur' => ['language' => 'Urdu', 'locale' => 'urd', 'localeFallback' => 'urd', 'charset' => 'utf-8', 'direction' => 'rtl'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['ve']);
		$expected = [
			've' => ['language' => 'Venda', 'locale' => 'ven', 'localeFallback' => 'ven', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['vi']);
		$expected = [
			'vi' => ['language' => 'Vietnamese', 'locale' => 'vie', 'localeFallback' => 'vie', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['cy']);
		$expected = [
			'cy' => ['language' => 'Welsh', 'locale' => 'cym', 'localeFallback' => 'cym', 'charset' => 'utf-8',
			'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['xh']);
		$expected = [
			'xh' => ['language' => 'Xhosa', 'locale' => 'xho', 'localeFallback' => 'xho', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['yi']);
		$expected = [
			'yi' => ['language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['zh', 'zh-cn', 'zh-hk', 'zh-sg', 'zh-tw']);
		$expected = [
			'zh' => ['language' => 'Chinese', 'locale' => 'zho', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'zh-cn' => ['language' => 'Chinese (PRC)', 'locale' => 'zh_cn', 'localeFallback' => 'zho', 'charset' => 'GB2312', 'direction' => 'ltr'],
			'zh-hk' => ['language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'zh-sg' => ['language' => 'Chinese (Singapore)', 'locale' => 'zh_sg', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'zh-tw' => ['language' => 'Chinese (Taiwan)', 'locale' => 'zh_tw', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['zu']);
		$expected = [
			'zu' => ['language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['en-nz', 'es-do', 'ar-lb', 'zh-hk', 'pt-br']);
		$expected = [
			'en-nz' => ['language' => 'English (New Zealand)', 'locale' => 'en_nz', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'es-do' => ['language' => 'Spanish (Dominican Republic)', 'locale' => 'es_do', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'ar-lb' => ['language' => 'Arabic (Lebanon)', 'locale' => 'ar_lb', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
			'zh-hk' => ['language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'pt-br' => ['language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(['eng', 'deu', 'zho', 'rum', 'zul', 'yid']);
		$expected = [
			'eng' => ['language' => 'English', 'locale' => 'eng', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'deu' => ['language' => 'German (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'zho' => ['language' => 'Chinese', 'locale' => 'zho', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'rum' => ['language' => 'Romanian', 'locale' => 'ron', 'localeFallback' => 'ron', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'zul' => ['language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul', 'charset' => 'utf-8', 'direction' => 'ltr'],
			'yid' => ['language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid', 'charset' => 'utf-8', 'direction' => 'ltr'],
		];
		$this->assertEquals($expected, $result);
	}

}
