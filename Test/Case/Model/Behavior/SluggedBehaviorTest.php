<?php
/**
 * Slugged behavior test case
 *
 * This test case is big. very big. You'll need 36mb or more allocated to php
 * to be able to load it (most likely only relevant for cli users).
 *
 * PHP version 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * @copyright Copyright (c) 2008, Andy Dawson
 * @link www.ad7six.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::uses('SluggedBehavior', 'Tools.Model/Behavior');
App::uses('HttpSocket', 'Network/Http');
App::uses('File', 'Utility');

/**
 * SluggedBehaviorTest class
 *
 */
class SluggedBehaviorTest extends CakeTestCase {

	/**
	 * Fixtures property
	 *
	 * @var array
	 */
	public $fixtures = array('plugin.tools.message');

	/**
	 * SkipSetupTests property
	 *
	 * @var boolean
	 */
	public $skipSetupTests = true;

	/**
	 * GetTests method
	 *
	 * This test case is very intensive - the logic contained here can be manipulated to limit the range of
	 * of characters used in the test case by default there is no limit imposed and the memory limit is increased
	 * to allow the test to complete
	 *
	 * @return void
	 */
	public function getTests() {
		ini_set('memory_limit', '256M');
		$memoryLimit = (int)ini_get('memory_limit');
		$max = 0;
		$classMethods = get_class_methods(get_class($this));
		$counter = 0;
		$methods = array();
		foreach ($classMethods as $method) {
			if ($max) {
				if (strpos($method, 'testSection') !== false) {
					$counter++;
					if ($counter < 0 || $counter > $max) {
						continue;
					}
				}
			}
			if ($this->_isTest($method)) {
				$methods[] = $method;
			}
		}
		return array_merge(array_merge(array('start', 'startCase'), $methods), array('endCase', 'end'));
	}

	/**
	 * IsTest method
	 *
	 * Prevent intensive W3 test, and the build tests (used to generate the testSection tests) unless
	 * explicitly specified
	 *
	 * @param mixed $method
	 * @return void
	 */
	protected function _isTest($method) {
		if (strtolower($method) === 'testaction') {
			return false;
		}
		$buildTests = array('testw3validity', 'testbuildregex', 'testbuildtest');
		if (in_array(strtolower($method), $buildTests )) {
			return !$this->skipSetupTests;
		}
		if (strtolower(substr($method, 0, 4)) === 'test') {
			if (!$this->skipSetupTests) {
				return false;
			}
			return !SimpleTestCompatibility::isA($this, strtolower($method));
		}
		return false;
	}

	/**
	 * Start method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Model = new MessageSlugged();

		Configure::write('Config.language', 'eng');
	}

	/**
	 * Test slug generation/update based on trigger
	 *
	 * @return void
	 */
	public function testSlugGenerationBasedOnTrigger() {
		$this->Model->Behaviors->unload('Slugged');
		$this->Model->Behaviors->load('Tools.Slugged', array(
			'trigger' => 'generateSlug', 'overwrite' => true));

		$this->Model->generateSlug = false;
		$this->Model->create(array('name' => 'Some Article 25271'));
		$result = $this->Model->save();

		$result[$this->Model->alias]['id'] = $this->Model->id;
		$this->assertTrue(empty($result[$this->Model->alias]['slug']));
		$this->Model->generateSlug = true;
		$result = $this->Model->save($result);
		$this->assertEquals('Some-Article-25271', $result[$this->Model->alias]['slug']);
	}

	/**
	 * Test slug generation with i18n replacement pieces
	 *
	 * @return void
	 */
	public function testSlugGenerationI18nReplacementPieces() {
		$this->Model->Behaviors->unload('Slugged');
		$this->Model->Behaviors->load('Tools.Slugged', array(
			'overwrite' => true));

		$this->Model->create(array('name' => 'Some & More'));
		$result = $this->Model->save();
		$this->assertEquals('Some-' . __('and') . '-More', $result[$this->Model->alias]['slug']);
	}

	/**
	 * Test dynamic slug overwrite
	 *
	 * @return void
	 */
	public function testSlugDynamicOverwrite() {
		$this->Model->Behaviors->unload('Slugged');
		$this->Model->Behaviors->load('Tools.Slugged', array(
			'overwrite' => false, 'overwriteField' => 'overwrite_my_slug'));

		$this->Model->create();
		$data = array('name' => 'Some Cool String', 'overwrite_my_slug' => false);
		$result = $this->Model->save($data);
		$this->assertEquals('Some-Cool-String', $result[$this->Model->alias]['slug']);

		$data = array('name' => 'Some Cool Other String', 'overwrite_my_slug' => false, 'id' => $this->Model->id);
		$result = $this->Model->save($data);
		$this->assertTrue(empty($result[$this->Model->alias]['slug']));

		$data = array('name' => 'Some Cool Other String', 'overwrite_my_slug' => true, 'id' => $this->Model->id);
		$result = $this->Model->save($data);
		$this->assertEquals('Some-Cool-Other-String', $result[$this->Model->alias]['slug']);
	}

	/**
	 * Test slug generation/update based on scope
	 *
	 * @return void
	 */
	public function testSlugGenerationWithScope() {
		$this->Model->Behaviors->unload('Slugged');
		$this->Model->Behaviors->load('Tools.Slugged', array('unique' => true));

		$data = array('name' => 'Some Article 12345', 'section' => 0);

		$this->Model->create();
		$result = $this->Model->save($data);
		$this->assertTrue((bool)$result);
		$this->assertEquals('Some-Article-12345', $result[$this->Model->alias]['slug']);

		$this->Model->create();
		$result = $this->Model->save($data);
		$this->assertTrue((bool)$result);
		$this->assertEquals('Some-Article-12345-1', $result[$this->Model->alias]['slug']);

		$this->Model->Behaviors->unload('Slugged');
		$this->Model->Behaviors->load('Tools.Slugged', array('unique' => true, 'scope' => array('section' => 1)));

		$data = array('name' => 'Some Article 12345', 'section' => 1);

		$this->Model->create();
		$result = $this->Model->save($data);
		$this->assertTrue((bool)$result);
		$this->assertEquals('Some-Article-12345', $result[$this->Model->alias]['slug']);
	}

	/**
	 * Test remove stop words
	 */
	public function testRemoveStopWords() {
		$this->skipIf(true, 'Does not work anymore');

		$skip = false;
		$lang = Configure::read('Config.language');
		if (!$lang) {
			$lang = 'eng';
		}
		if (
			!App::import('Vendor', 'stop_words_' . $lang, array('file' => "stop_words" . DS . "$lang.txt")) &&
			!App::import('Vendor', 'Tools.stop_words_' . $lang, array('file' => "stop_words" . DS . "$lang.txt"))
		) {
			$skip = true;
		}
		$this->skipIf($skip, 'no stop_words/' . $lang . '.txt file found');

		$array = $this->Model->removeStopWords('My name is Michael Paine, and I am a nosey neighbour');
		$expected = array(
			'Michael Paine',
			'nosey neighbour'
		);
		$this->assertEquals($expected, $array);

		$wordList = $this->Model->removeStopWords('My name is Michael Paine, and I am a nosey neighbour', array(
			'splitOnStopWord' => false
		));
		$expected = array(
			'Michael',
			'Paine',
			'nosey',
			'neighbour',
		);
		$this->assertEquals($expected, array_values($wordList));

		$string = $this->Model->removeStopWords('My name is Michael Paine, and I am a nosey neighbour', array(
			'return' => 'string'
		));
		$expected = 'Michael Paine nosey neighbour';
		$this->assertEquals($expected, $string);
	}

	/**
	 * TestBuildRegex method
	 *
	 * This 'test' is used to compare with the existing, and to optimize the regex pattern
	 *
	 * @return void
	 */
	public function testBuildRegex() {
		$chars = array();
		$string = '';
		for ($hex1 = 0; $hex1 < 16; $hex1++) {
			for ($hex2 = 0; $hex2 < 16; $hex2++) {
				for ($hex3 = 0; $hex3 < 16; $hex3++) {
					$string .= dechex($hex1) . dechex($hex2) . dechex($hex3) . dechex(0);
					for ($hex4 = 0; $hex4 < 16; $hex4++) {
						$hexCode = dechex($hex1) . dechex($hex2) . dechex($hex3) . dechex($hex4);
						$decCode = hexdec($hexCode);
						$string .= $display = $char = html_entity_decode('&#' . $decCode . ';', ENT_NOQUOTES, 'UTF-8');
						$char = $this->Model->slug($char);
						if ($display != $char) {
							$chars[] = $hexCode;
						}
					}
					$string .= "\n";
				}
			}
		}
		foreach ($chars as $i => $code) {
			if (!$i) {
				$codeRegex = $charRegex = "\x{{$code}}";
				continue;
			}
			$prevTest = hexdec($code) - 1;
			$nextTest = hexdec($code) + 1;
			if ($prevTest == hexdec($chars[$i - 1]) &&
				isset($chars[$i + 1]) && $nextTest == hexdec($chars[$i + 1])) {
				continue;
			} elseif ($prevTest == hexdec($chars[$i - 1])) {
				$codeRegex .= "-\x{{$code}}";
				$charRegex .= '-' . html_entity_decode('&#' . hexdec($code) . ';', ENT_NOQUOTES, 'UTF-8');
			} else {
				$codeRegex .= "\x{{$code}}";
				$charRegex .= html_entity_decode('&#' . hexdec($code) . ';', ENT_NOQUOTES, 'UTF-8');
			}
		}
	}

	/**
	 * TestBuildTest method
	 *
	 * This method generates a temporary file containing a test class with the slug tests in it
	 *
	 * @return void
	 */
	public function testBuildTest() {
		$this->_buildTest();
	}

	/**
	 * BuildTest method
	 *
	 * @param integer $hex1Limit
	 * @param integer $hex2Limit
	 * @param integer $hex1Start
	 * @param integer $hex2Start
	 * @return void
	 */
	protected function _buildTest($hex1Limit = 16, $hex2Limit = 16, $hex1Start = 16, $hex2Start = 0) {
		$skip = array(15, 16);
		$path = TMP . 'tests' . DS . 'slug_test.php';
		@unlink($path);
		$file = new File($path, true);
		$file->append('class SluggedTest extends CakeTestCase {' . "\n");
		for ($hex1 = $hex1Start; $hex1 < $hex1Limit; $hex1++) {
			if (in_array($hex1, $skip)) {
				continue;
			}
			$file->append($this->_buildTestFunction(dechex($hex1), $hex2Limit, $hex2Start));
		}
		//$file->write($out);
		$file->close();
	}

	/**
	 * BuildTestFunction method
	 *
	 * @param mixed $section
	 * @return void
	 */
	protected function _buildTestFunction($section, $limit = 16, $start = 0) {
		$out = "\tfunction testSection$section() {\n";
		$allEmpty = true;
		for ($hex1 = $start; $hex1 < $limit; $hex1++) {
			for ($hex2 = 0; $hex2 < 16; $hex2++) {
				$string = '';
				for ($hex3 = 0; $hex3 < 16; $hex3++) {
					$hexCode = $section . dechex($hex1) . dechex($hex2) . dechex($hex3);
					$decCode = hexdec($hexCode);
					if ($decCode <= 31 || ($decCode >= 127 && $decCode <= 159) || ($hexCode >= 'd800' && $hexCode <= 'dfff') || $hexCode >= 'fffe') {
						continue;
					}
					$string .= html_entity_decode('&#' . $decCode . ';', ENT_NOQUOTES, 'UTF-8');
				}
				if ($string) {
					$slugged = $this->Model->slug($string, false);
					if ($slugged !== '----------------') {
							$allEmpty = false;
					}
					$out .= "\t\t" . '$string = \'' . str_replace("'", "\'", $string) . '\';' . "\n";
					$out .= "\t\t" . '$expects = \'' . $slugged . '\';' . "\n";
					$out .= "\t\t" . '$result = $this->Model->slug($string, false);' . "\n";
					$out .= "\t\t" . '$this->assertEquals($expects, $result);' . "\n";
					$out .= "\n";
				}
			}
		}
		$out .= "\t}\n";
		if ($allEmpty) {
			return '';
		}
		return $out;
	}

	/**
	 * TestW3Validity method
	 *
	 * For each of the slugged behavior modes, generate (a) test file(s) and submit them to the W3 validator
	 * service. WARNING: this test is extremely slow (not to mention intensive on the validator service) therefore
	 * it is advisable to run it only for one mode and a limited range unless testing a code modification
	 * there are 2 overrides in the code to limit the duration of the test if it /is/ run.
	 *
	 * @return void
	 */
	public function testW3Validity() {
		$this->skipIf(true);

		$modes = array('display', 'url', 'class', 'id');
		$modes = array('id'); // overriden
		$this->Socket = new HttpSocket('http://validator.w3.org:80');
		foreach ($modes as $mode) {
			$this->Model->Behaviors->load('Slugged', array('mode' => $mode));
			$this->_testMode($mode, 1, 1); // overriden parameters
		}
	}

	/**
	 * TestMode method
	 *
	 * The limit and start points (limit parameters are first to allow calling as _testMode(x, 1, 1)) are passed
	 * as parameters to allow selective/reduced/focused testing. Testing the whole range and all modes is very time
	 * consuming.
	 *
	 * @param mixed $mode
	 * @param integer $hex1Limit
	 * @param integer $hex2Limit
	 * @param integer $hex1Start
	 * @param integer $hex2Start
	 * @return void
	 */
	protected function _testMode($mode, $hex1Limit = 16, $hex2Limit = 16, $hex1Start = 0, $hex2Start = 0) {
		for ($hex1 = $hex1Start; $hex1 < $hex1Limit; $hex1++) {
			$suffix = dechex($hex1) . dechex($hex2Start) . '_' . dechex($hex1) . dechex($hex2Limit - 1);
			$full = TMP . 'tests' . DS . 'slug_' . $mode . '_' . $suffix . '.html';
			$file = new File($full, true);
			$this->_writeHeader($file, $hex1);
			for ($hex2 = $hex2Start; $hex2 < $hex2Limit; $hex2++) {
				$part = file_get_contents($this->_createTestFile(dechex($hex1) . dechex($hex2), $mode));
				preg_match('@<table>(.*)</table>@Us', $part, $test);
				$file->append($test[1] . "\n");
			}
			$file->append('</table></body></html>');
			$file->close();
			$this->_testFile($full);
		}
	}

	/**
	 * TestModeRange method
	 *
	 * Send the test file to the W3 Validator service, if the result is invalid trigger parseW3Response
	 *
	 * @param mixed $mode
	 * @param mixed $hex1
	 * @param mixed $hex2
	 * @return void
	 */
	protected function _testFile($path) {
		$request = array(
			'method' => 'POST',
			'uri' => 'http://validator.w3.org/check',
			'body' => array(
				'docType' => 'Inline',
				'fragment' => file_get_contents($path),
				'group' => 0,
				'prefil' => 0,
				'prefil_doctype' => 'html401'
			)
		);
		$response = $this->Socket->request($request);
		$this->assertTrue($response !== false);
		if (!$response) {
			return;
		}
		$responsePath = str_replace('.html', '_response.html', $path);
		$file = new File($responsePath, true);
		$file->write($response);
		$test = preg_replace('@\s*@', '', strip_tags($response->body));
		$passed = strpos($test, 'Result:Passed');
		$this->assertTrue($passed);
		if (!$passed) {
			$this->_parseW3Response($response, $test, $path);
		}
	}

	/**
	 * ParseW3Response method
	 *
	 * If W3 gave back an error response, parse out the character code point and build a list of illegal characters.
	 * Use this list to echo out a partial regex match to be used in the slug behavior to capture and slug these
	 * illegal characters in future
	 *
	 * @param mixed $response
	 * @param mixed $test
	 * @param mixed $inputFile
	 * @return void
	 */
	protected function _parseW3Response($response, $test, $inputFile) {
		preg_match_all('@<span class="err_type">.*</span>.*<em>Line (.*),.*</em>@sU', $response, $result);
		if (!$result[1]) {
			trigger_error('couldn\'t parse the error messages generated for ' . $inputFile);
			return;
		}
		$result[1] = array_unique($result[1]);
		$input = file($inputFile);
		foreach ($result[1] as $nextLine) {
			$line = $nextLine - 1;
			preg_match('@title=\'([a-fA-F0-9]+)@', $input[$line], $char);
			if ($char) {
				$this->illegalChars[] = (string)$char[1];
			} else {
				trigger_error($input[$line]);
			}
		}
		foreach ($this->illegalChars as $i => $code) {
			if (!$i) {
				$string = "\x{{$code}}";
				continue;
			}
			$prevTest = hexdec($code) - 1;
			$nextTest = hexdec($code) + 1;
			if ($prevTest == hexdec($this->illegalChars[$i - 1]) &&
				isset($this->illegalChars[$i + 1]) && $nextTest == hexdec($this->illegalChars[$i + 1])) {
				continue;
			} elseif ($prevTest == hexdec($this->illegalChars[$i - 1])) {
				$string .= "-\x{{$code}}";
			} else {
				$string .= "\x{{$code}}";
			}
		}
	}

	/**
	 * CreateTestFile method
	 *
	 * Create a single test file, for the specified range/section
	 *
	 * @param mixed $section
	 * @param string $mode
	 * @return string file path
	 */
	protected function _createTestFile($section, $mode = 'display') {
		$path = TMP . 'tests' . DS . '_slug_' . $mode . '_' . $section . '.html';
		$file = new File($path, true);
		$this->_writeHeader($file, $section);
		for ($hex1 = -1; $hex1 < 16; $hex1++) {
			if ($hex1 == -1) {
				$row = array('<b>' . $section . '</b>');
			} else {
				$row = array($section . dechex($hex1) . 0);
			}
			for ($hex2 = 0; $hex2 < 16; $hex2++) {
				if ($hex1 == -1) {
					$row[] = $section . dechex($hex2);
				} else {
					$hexCode = $section . dechex($hex1) . dechex($hex2);
					$decCode = hexdec($hexCode);
					$row[] = $this->_renderChar($hexCode, $mode);
				}
			}
			$file->append('<tr><td>' . implode($row, "</td>\n<td>") . "</td></tr>\n");
		}
		$file->append('</table></body></html>');
		$file->close();
		return $path;
	}

	/**
	 * WriteHeader method
	 *
	 * Generate the file header
	 *
	 * @param mixed $file
	 * @param mixed $title
	 * @return void
	 */
	protected function _writeHeader($file, $title) {
		$file->write('<!DOCTYPE html>');
		$file->append('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">' . "\n");
		$file->append('<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><style type="text/css">table {width:100%}.slugged{background:yellow}.illegal{background:red}</style>');
		$file->append('<title>Section ' . $title . '</title></head>' . "\n");
		$file->append('<body><table>' . "\n");
	}

	/**
	 * RenderChar method
	 *
	 * Slug the character and generate the output to be put in the test file
	 *
	 * @param mixed $hexCode
	 * @param string $mode
	 * @return string
	 */
	protected function _renderChar($hexCode, $mode = 'id') {
		$decCode = hexdec($hexCode);
		$display = $char = html_entity_decode('&#' . $decCode . ';', ENT_NOQUOTES, 'UTF-8');
		$char = $this->Model->slug($char, false);
		if ($display === $char) {
			if ($mode === 'display') {
				return "<a href='#' title='$hexCode-$decCode'>$display</a>";
			} elseif ($mode === 'url') {
				return "<a href='$char' title='$hexCode-$decCode'>$display</a>";
			} elseif ($mode === 'class') {
				return "<a href='#' class='a$char-$decCode-$hexCode' title='$hexCode-$decCode'>$display</a>";
			} elseif ($mode === 'id') {
				return "<a href='#' id='a$char-$decCode-$hexCode' title='$hexCode-$decCode'>$display</a>";
			}
		} else {
			if ($decCode <= 31 || ($decCode >= 127 && $decCode <= 159) || ($hexCode >= 'd800' && $hexCode <= 'dfff') || $hexCode >= 'fffe') {
				return "<span id='illegal-$hexCode' class='illegal'>x</span>";
			}
			return "<span class='slugged' title='$hexCode-$decCode'><b>&#$decCode;</b></span>";
		}
	}

	/**
	 * TestSection0 method
	 *
	 * Testing characters 0000 - 0fff
	 *
	 * @return void
	 */
	public function testSection0() {
		$string = ' !"#$%&\'()*+,-./';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '0123456789:;<=>?';
		$expects = '0123456789------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '@ABCDEFGHIJKLMNO';
		$expects = '-ABCDEFGHIJKLMNO';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'PQRSTUVWXYZ[\]^_';
		$expects = 'PQRSTUVWXYZ----_';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '`abcdefghijklmno';
		$expects = '-abcdefghijklmno';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'pqrstuvwxyz{|}~';
		$expects = 'pqrstuvwxyz----';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = ' ¡¢£¤¥¦§¨©ª«¬­®¯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '°±²³´µ¶·¸¹º»¼½¾¿';
		$expects = '-------·--------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏ';
		$expects = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß';
		$expects = 'ÐÑÒÓÔÕÖ-ØÙÚÛÜÝÞß';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'àáâãäåæçèéêëìíîï';
		$expects = 'àáâãäåæçèéêëìíîï';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ðñòóôõö÷øùúûüýþÿ';
		$expects = 'ðñòóôõö-øùúûüýþÿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ĀāĂăĄąĆćĈĉĊċČčĎď';
		$expects = 'ĀāĂăĄąĆćĈĉĊċČčĎď';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ĐđĒēĔĕĖėĘęĚěĜĝĞğ';
		$expects = 'ĐđĒēĔĕĖėĘęĚěĜĝĞğ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ĠġĢģĤĥĦħĨĩĪīĬĭĮį';
		$expects = 'ĠġĢģĤĥĦħĨĩĪīĬĭĮį';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'İıĲĳĴĵĶķĸĹĺĻļĽľĿ';
		$expects = 'İı--ĴĵĶķĸĹĺĻļĽľ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ŀŁłŃńŅņŇňŉŊŋŌōŎŏ';
		$expects = '-ŁłŃńŅņŇň-ŊŋŌōŎŏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ŐőŒœŔŕŖŗŘřŚśŜŝŞş';
		$expects = 'ŐőŒœŔŕŖŗŘřŚśŜŝŞş';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ŠšŢţŤťŦŧŨũŪūŬŭŮů';
		$expects = 'ŠšŢţŤťŦŧŨũŪūŬŭŮů';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ŰűŲųŴŵŶŷŸŹźŻżŽžſ';
		$expects = 'ŰűŲųŴŵŶŷŸŹźŻżŽž-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ƀƁƂƃƄƅƆƇƈƉƊƋƌƍƎƏ';
		$expects = 'ƀƁƂƃƄƅƆƇƈƉƊƋƌƍƎƏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ƐƑƒƓƔƕƖƗƘƙƚƛƜƝƞƟ';
		$expects = 'ƐƑƒƓƔƕƖƗƘƙƚƛƜƝƞƟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ƠơƢƣƤƥƦƧƨƩƪƫƬƭƮƯ';
		$expects = 'ƠơƢƣƤƥƦƧƨƩƪƫƬƭƮƯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ưƱƲƳƴƵƶƷƸƹƺƻƼƽƾƿ';
		$expects = 'ưƱƲƳƴƵƶƷƸƹƺƻƼƽƾƿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ǀǁǂǃǄǅǆǇǈǉǊǋǌǍǎǏ';
		$expects = 'ǀǁǂǃ---------ǍǎǏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟ';
		$expects = 'ǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯ';
		$expects = 'ǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ǰǱǲǳǴǵǶǷǸǹǺǻǼǽǾǿ';
		$expects = 'ǰ---Ǵǵ----ǺǻǼǽǾǿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ȀȁȂȃȄȅȆȇȈȉȊȋȌȍȎȏ';
		$expects = 'ȀȁȂȃȄȅȆȇȈȉȊȋȌȍȎȏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ȐȑȒȓȔȕȖȗȘșȚțȜȝȞȟ';
		$expects = 'ȐȑȒȓȔȕȖȗ--------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ȠȡȢȣȤȥȦȧȨȩȪȫȬȭȮȯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ȰȱȲȳȴȵȶȷȸȹȺȻȼȽȾȿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ɀɁɂɃɄɅɆɇɈɉɊɋɌɍɎɏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ɐɑɒɓɔɕɖɗɘəɚɛɜɝɞɟ';
		$expects = 'ɐɑɒɓɔɕɖɗɘəɚɛɜɝɞɟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ɠɡɢɣɤɥɦɧɨɩɪɫɬɭɮɯ';
		$expects = 'ɠɡɢɣɤɥɦɧɨɩɪɫɬɭɮɯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ɰɱɲɳɴɵɶɷɸɹɺɻɼɽɾɿ';
		$expects = 'ɰɱɲɳɴɵɶɷɸɹɺɻɼɽɾɿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ʀʁʂʃʄʅʆʇʈʉʊʋʌʍʎʏ';
		$expects = 'ʀʁʂʃʄʅʆʇʈʉʊʋʌʍʎʏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ʐʑʒʓʔʕʖʗʘʙʚʛʜʝʞʟ';
		$expects = 'ʐʑʒʓʔʕʖʗʘʙʚʛʜʝʞʟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ʠʡʢʣʤʥʦʧʨʩʪʫʬʭʮʯ';
		$expects = 'ʠʡʢʣʤʥʦʧʨ-------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ʰʱʲʳʴʵʶʷʸʹʺʻʼʽʾʿ';
		$expects = '-----------ʻʼʽʾʿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ˀˁ˂˃˄˅ˆˇˈˉˊˋˌˍˎˏ';
		$expects = 'ˀˁ--------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ːˑ˒˓˔˕˖˗˘˙˚˛˜˝˞˟';
		$expects = 'ːˑ--------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ˠˡˢˣˤ˥˦˧˨˩˪˫ˬ˭ˮ˯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '˰˱˲˳˴˵˶˷˸˹˺˻˼˽˾˿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '̀́̂̃̄̅̆̇̈̉̊̋̌̍̎̏';
		$expects = '̀́̂̃̄̅̆̇̈̉̊̋̌̍̎̏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '̛̖̗̘̙̜̝̞̟̐̑̒̓̔̕̚';
		$expects = '̛̖̗̘̙̜̝̞̟̐̑̒̓̔̕̚';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '̡̢̧̨̠̣̤̥̦̩̪̫̬̭̮̯';
		$expects = '̡̢̧̨̠̣̤̥̦̩̪̫̬̭̮̯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '̴̵̶̷̸̰̱̲̳̹̺̻̼̽̾̿';
		$expects = '̴̵̶̷̸̰̱̲̳̹̺̻̼̽̾̿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '͇͈͉͍͎̀́͂̓̈́͆͊͋͌ͅ͏';
		$expects = '̀́͂̓̈́ͅ----------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '͓͔͕͖͙͚͐͑͒͗͛͘͜͟͝͞';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ͣͤͥͦͧͨͩͪͫͬͭͮͯ͢͠͡';
		$expects = '͠͡--------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ͰͱͲͳʹ͵Ͷͷ͸͹ͺͻͼͽ;Ϳ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '΀΁΂΃΄΅Ά·ΈΉΊ΋Ό΍ΎΏ';
		$expects = '------Ά·ΈΉΊ-Ό-ΎΏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ΐΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟ';
		$expects = 'ΐΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ΠΡ΢ΣΤΥΦΧΨΩΪΫάέήί';
		$expects = 'ΠΡ-ΣΤΥΦΧΨΩΪΫάέήί';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ΰαβγδεζηθικλμνξο';
		$expects = 'ΰαβγδεζηθικλμνξο';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'πρςστυφχψωϊϋόύώϏ';
		$expects = 'πρςστυφχψωϊϋόύώ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ϐϑϒϓϔϕϖϗϘϙϚϛϜϝϞϟ';
		$expects = 'ϐϑϒϓϔϕϖ---Ϛ-Ϝ-Ϟ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ϠϡϢϣϤϥϦϧϨϩϪϫϬϭϮϯ';
		$expects = 'Ϡ-ϢϣϤϥϦϧϨϩϪϫϬϭϮϯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ϰϱϲϳϴϵ϶ϷϸϹϺϻϼϽϾϿ';
		$expects = 'ϰϱϲϳ------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ЀЁЂЃЄЅІЇЈЉЊЋЌЍЎЏ';
		$expects = '-ЁЂЃЄЅІЇЈЉЊЋЌ-ЎЏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'АБВГДЕЖЗИЙКЛМНОП';
		$expects = 'АБВГДЕЖЗИЙКЛМНОП';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'РСТУФХЦЧШЩЪЫЬЭЮЯ';
		$expects = 'РСТУФХЦЧШЩЪЫЬЭЮЯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'абвгдежзийклмноп';
		$expects = 'абвгдежзийклмноп';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'рстуфхцчшщъыьэюя';
		$expects = 'рстуфхцчшщъыьэюя';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ѐёђѓєѕіїјљњћќѝўџ';
		$expects = '-ёђѓєѕіїјљњћќ-ўџ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ѠѡѢѣѤѥѦѧѨѩѪѫѬѭѮѯ';
		$expects = 'ѠѡѢѣѤѥѦѧѨѩѪѫѬѭѮѯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ѰѱѲѳѴѵѶѷѸѹѺѻѼѽѾѿ';
		$expects = 'ѰѱѲѳѴѵѶѷѸѹѺѻѼѽѾѿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'Ҁҁ҂҃҄҅҆҇҈҉ҊҋҌҍҎҏ';
		$expects = 'Ҁҁ-҃҄҅҆---------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ҐґҒғҔҕҖҗҘҙҚқҜҝҞҟ';
		$expects = 'ҐґҒғҔҕҖҗҘҙҚқҜҝҞҟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ҠҡҢңҤҥҦҧҨҩҪҫҬҭҮү';
		$expects = 'ҠҡҢңҤҥҦҧҨҩҪҫҬҭҮү';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ҰұҲҳҴҵҶҷҸҹҺһҼҽҾҿ';
		$expects = 'ҰұҲҳҴҵҶҷҸҹҺһҼҽҾҿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ӀӁӂӃӄӅӆӇӈӉӊӋӌӍӎӏ';
		$expects = 'ӀӁӂӃӄ--Ӈӈ--Ӌӌ---';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ӐӑӒӓӔӕӖӗӘәӚӛӜӝӞӟ';
		$expects = 'ӐӑӒӓӔӕӖӗӘәӚӛӜӝӞӟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ӠӡӢӣӤӥӦӧӨөӪӫӬӭӮӯ';
		$expects = 'ӠӡӢӣӤӥӦӧӨөӪӫ--Ӯӯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ӰӱӲӳӴӵӶӷӸӹӺӻӼӽӾӿ';
		$expects = 'ӰӱӲӳӴӵ--Ӹӹ------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ԀԁԂԃԄԅԆԇԈԉԊԋԌԍԎԏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ԐԑԒԓԔԕԖԗԘԙԚԛԜԝԞԟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ԠԡԢԣԤԥԦԧԨԩԪԫԬԭԮԯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '԰ԱԲԳԴԵԶԷԸԹԺԻԼԽԾԿ';
		$expects = '-ԱԲԳԴԵԶԷԸԹԺԻԼԽԾԿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ՀՁՂՃՄՅՆՇՈՉՊՋՌՍՎՏ';
		$expects = 'ՀՁՂՃՄՅՆՇՈՉՊՋՌՍՎՏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ՐՑՒՓՔՕՖ՗՘ՙ՚՛՜՝՞՟';
		$expects = 'ՐՑՒՓՔՕՖ--ՙ------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ՠաբգդեզէըթժիլխծկ';
		$expects = '-աբգդեզէըթժիլխծկ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'հձղճմյնշոչպջռսվտ';
		$expects = 'հձղճմյնշոչպջռսվտ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'րցւփքօֆևֈ։֊֋֌֍֎֏';
		$expects = 'րցւփքօֆ---------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '֐֑֖֛֚֒֓֔֕֗֘֙֜֝֞֟';
		$expects = '-֑֖֛֚֒֓֔֕֗֘֙֜֝֞֟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '֢֣֤֥֦֧֪֭֮֠֡֨֩֫֬֯';
		$expects = '֠֡-֣֤֥֦֧֪֭֮֨֩֫֬֯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ְֱֲֳִֵֶַָֹֺֻּֽ־ֿ';
		$expects = 'ְֱֲֳִֵֶַָֹ-ֻּֽ-ֿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '׀ׁׂ׃ׅׄ׆ׇ׈׉׊׋׌׍׎׏';
		$expects = '-ׁׂ-ׄ-----------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'אבגדהוזחטיךכלםמן';
		$expects = 'אבגדהוזחטיךכלםמן';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'נסעףפץצקרשת׫׬׭׮ׯ';
		$expects = 'נסעףפץצקרשת-----';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'װױײ׳״׵׶׷׸׹׺׻׼׽׾׿';
		$expects = 'װױײ-------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '؀؁؂؃؄؅؆؇؈؉؊؋،؍؎؏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ؘؙؚؐؑؒؓؔؕؖؗ؛؜؝؞؟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ؠءآأؤإئابةتثجحخد';
		$expects = '-ءآأؤإئابةتثجحخد';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ذرزسشصضطظعغػؼؽؾؿ';
		$expects = 'ذرزسشصضطظعغ-----';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ـفقكلمنهوىيًٌٍَُ';
		$expects = 'ـفقكلمنهوىيًٌٍَُ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ِّْٕٖٜٟٓٔٗ٘ٙٚٛٝٞ';
		$expects = 'ِّْ-------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '٠١٢٣٤٥٦٧٨٩٪٫٬٭ٮٯ';
		$expects = '٠١٢٣٤٥٦٧٨٩------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ٰٱٲٳٴٵٶٷٸٹٺٻټٽپٿ';
		$expects = 'ٰٱٲٳٴٵٶٷٸٹٺٻټٽپٿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ڀځڂڃڄڅچڇڈډڊڋڌڍڎڏ';
		$expects = 'ڀځڂڃڄڅچڇڈډڊڋڌڍڎڏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ڐڑڒړڔڕږڗژڙښڛڜڝڞڟ';
		$expects = 'ڐڑڒړڔڕږڗژڙښڛڜڝڞڟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ڠڡڢڣڤڥڦڧڨکڪګڬڭڮگ';
		$expects = 'ڠڡڢڣڤڥڦڧڨکڪګڬڭڮگ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ڰڱڲڳڴڵڶڷڸڹںڻڼڽھڿ';
		$expects = 'ڰڱڲڳڴڵڶڷ--ںڻڼڽھ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ۀہۂۃۄۅۆۇۈۉۊۋیۍێۏ';
		$expects = 'ۀہۂۃۄۅۆۇۈۉۊۋیۍێ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ېۑےۓ۔ەۖۗۘۙۚۛۜ۝۞۟';
		$expects = 'ېۑےۓ-ەۖۗۘۙۚۛۜ۝۞۟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ۣ۠ۡۢۤۥۦۧۨ۩۪ۭ۫۬ۮۯ';
		$expects = 'ۣ۠ۡۢۤۥۦۧۨ-۪ۭ۫۬--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '۰۱۲۳۴۵۶۷۸۹ۺۻۼ۽۾ۿ';
		$expects = '۰۱۲۳۴۵۶۷۸۹------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '܀܁܂܃܄܅܆܇܈܉܊܋܌܍܎܏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ܐܑܒܓܔܕܖܗܘܙܚܛܜܝܞܟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ܠܡܢܣܤܥܦܧܨܩܪܫܬܭܮܯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ܱܴܷܸܹܻܼܾܰܲܳܵܶܺܽܿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '݂݄݆݈݀݁݃݅݇݉݊݋݌ݍݎݏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ݐݑݒݓݔݕݖݗݘݙݚݛݜݝݞݟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ݠݡݢݣݤݥݦݧݨݩݪݫݬݭݮݯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ݰݱݲݳݴݵݶݷݸݹݺݻݼݽݾݿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ހށނރބޅކއވމފދތލގޏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ސޑޒޓޔޕޖޗޘޙޚޛޜޝޞޟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ޠޡޢޣޤޥަާިީުޫެޭޮޯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ްޱ޲޳޴޵޶޷޸޹޺޻޼޽޾޿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '߀߁߂߃߄߅߆߇߈߉ߊߋߌߍߎߏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ߐߑߒߓߔߕߖߗߘߙߚߛߜߝߞߟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ߠߡߢߣߤߥߦߧߨߩߪ߫߬߭߮߯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '߲߰߱߳ߴߵ߶߷߸߹ߺ߻߼߽߾߿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࠀࠁࠂࠃࠄࠅࠆࠇࠈࠉࠊࠋࠌࠍࠎࠏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࠐࠑࠒࠓࠔࠕࠖࠗ࠘࠙ࠚࠛࠜࠝࠞࠟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࠠࠡࠢࠣࠤࠥࠦࠧࠨࠩࠪࠫࠬ࠭࠮࠯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '࠰࠱࠲࠳࠴࠵࠶࠷࠸࠹࠺࠻࠼࠽࠾࠿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࡀࡁࡂࡃࡄࡅࡆࡇࡈࡉࡊࡋࡌࡍࡎࡏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࡐࡑࡒࡓࡔࡕࡖࡗࡘ࡙࡚࡛࡜࡝࡞࡟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࡠࡡࡢࡣࡤࡥࡦࡧࡨࡩࡪ࡫࡬࡭࡮࡯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࡰࡱࡲࡳࡴࡵࡶࡷࡸࡹࡺࡻࡼࡽࡾࡿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࢀࢁࢂࢃࢄࢅࢆࢇ࢈ࢉࢊࢋࢌࢍࢎ࢏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '࢐࢑࢒࢓࢔࢕࢖࢙࢚࢛ࢗ࢘࢜࢝࢞࢟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࢠࢡࢢࢣࢤࢥࢦࢧࢨࢩࢪࢫࢬࢭࢮࢯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࢰࢱࢲࢳࢴࢵࢶࢷࢸࢹࢺࢻࢼࢽࢾࢿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࣀࣁࣂࣃࣄࣅࣆࣇࣈࣉ࣏࣊࣋࣌࣍࣎';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '࣐࣑࣒࣓ࣔࣕࣖࣗࣘࣙࣚࣛࣜࣝࣞࣟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '࣠࣡࣢ࣣࣦࣩ࣭࣮࣯ࣤࣥࣧࣨ࣪࣫࣬';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ࣰࣱࣲࣶࣹࣺࣳࣴࣵࣷࣸࣻࣼࣽࣾࣿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ऀँंःऄअआइईउऊऋऌऍऎए';
		$expects = '-ँंः-अआइईउऊऋऌऍऎए';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ऐऑऒओऔकखगघङचछजझञट';
		$expects = 'ऐऑऒओऔकखगघङचछजझञट';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ठडढणतथदधनऩपफबभमय';
		$expects = 'ठडढणतथदधनऩपफबभमय';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'रऱलळऴवशषसहऺऻ़ऽाि';
		$expects = 'रऱलळऴवशषसह--़ऽाि';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ीुूृॄॅॆेैॉॊोौ्ॎॏ';
		$expects = 'ीुूृॄॅॆेैॉॊोौ्--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ॐ॒॑॓॔ॕॖॗक़ख़ग़ज़ड़ढ़फ़य़';
		$expects = '-॒॑॓॔---क़ख़ग़ज़ड़ढ़फ़य़';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ॠॡॢॣ।॥०१२३४५६७८९';
		$expects = 'ॠॡॢॣ--०१२३४५६७८९';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '॰ॱॲॳॴॵॶॷॸॹॺॻॼॽॾॿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ঀঁংঃ঄অআইঈউঊঋঌ঍঎এ';
		$expects = '-ঁংঃ-অআইঈউঊঋঌ--এ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ঐ঑঒ওঔকখগঘঙচছজঝঞট';
		$expects = 'ঐ--ওঔকখগঘঙচছজঝঞট';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ঠডঢণতথদধন঩পফবভময';
		$expects = 'ঠডঢণতথদধন-পফবভময';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'র঱ল঳঴঵শষসহ঺঻়ঽাি';
		$expects = 'র-ল---শষসহ--়-াি';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ীুূৃৄ৅৆েৈ৉৊োৌ্ৎ৏';
		$expects = 'ীুূৃৄ--েৈ--োৌ্--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '৐৑৒৓৔৕৖ৗ৘৙৚৛ড়ঢ়৞য়';
		$expects = '-------ৗ----ড়ঢ়-য়';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ৠৡৢৣ৤৥০১২৩৪৫৬৭৮৯';
		$expects = 'ৠৡৢৣ--০১২৩৪৫৬৭৮৯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ৰৱ৲৳৴৵৶৷৸৹৺৻ৼ৽৾৿';
		$expects = 'ৰৱ--------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '਀ਁਂਃ਄ਅਆਇਈਉਊ਋਌਍਎ਏ';
		$expects = '--ਂ--ਅਆਇਈਉਊ----ਏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ਐ਑਒ਓਔਕਖਗਘਙਚਛਜਝਞਟ';
		$expects = 'ਐ--ਓਔਕਖਗਘਙਚਛਜਝਞਟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ਠਡਢਣਤਥਦਧਨ਩ਪਫਬਭਮਯ';
		$expects = 'ਠਡਢਣਤਥਦਧਨ-ਪਫਬਭਮਯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ਰ਱ਲਲ਼਴ਵਸ਼਷ਸਹ਺਻਼਽ਾਿ';
		$expects = 'ਰ-ਲਲ਼-ਵਸ਼-ਸਹ--਼-ਾਿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ੀੁੂ੃੄੅੆ੇੈ੉੊ੋੌ੍੎੏';
		$expects = 'ੀੁੂ----ੇੈ--ੋੌ੍--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '੐ੑ੒੓੔੕੖੗੘ਖ਼ਗ਼ਜ਼ੜ੝ਫ਼੟';
		$expects = '---------ਖ਼ਗ਼ਜ਼ੜ-ਫ਼-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '੠੡੢੣੤੥੦੧੨੩੪੫੬੭੮੯';
		$expects = '------੦੧੨੩੪੫੬੭੮੯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ੰੱੲੳੴੵ੶੷੸੹੺੻੼੽੾੿';
		$expects = 'ੰੱੲੳੴ-----------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '઀ઁંઃ઄અઆઇઈઉઊઋઌઍ઎એ';
		$expects = '-ઁંઃ-અઆઇઈઉઊઋ-ઍ-એ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ઐઑ઒ઓઔકખગઘઙચછજઝઞટ';
		$expects = 'ઐઑ-ઓઔકખગઘઙચછજઝઞટ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ઠડઢણતથદધન઩પફબભમય';
		$expects = 'ઠડઢણતથદધન-પફબભમય';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ર઱લળ઴વશષસહ઺઻઼ઽાિ';
		$expects = 'ર-લળ-વશષસહ--઼ઽાિ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ીુૂૃૄૅ૆ેૈૉ૊ોૌ્૎૏';
		$expects = 'ીુૂૃૄૅ-ેૈૉ-ોૌ્--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ૐ૑૒૓૔૕૖૗૘૙૚૛૜૝૞૟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ૠૡૢૣ૤૥૦૧૨૩૪૫૬૭૮૯';
		$expects = 'ૠ-----૦૧૨૩૪૫૬૭૮૯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '૰૱૲૳૴૵૶૷૸ૹૺૻૼ૽૾૿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '଀ଁଂଃ଄ଅଆଇଈଉଊଋଌ଍଎ଏ';
		$expects = '-ଁଂଃ-ଅଆଇଈଉଊଋଌ--ଏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ଐ଑଒ଓଔକଖଗଘଙଚଛଜଝଞଟ';
		$expects = 'ଐ--ଓଔକଖଗଘଙଚଛଜଝଞଟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ଠଡଢଣତଥଦଧନ଩ପଫବଭମଯ';
		$expects = 'ଠଡଢଣତଥଦଧନ-ପଫବଭମଯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ର଱ଲଳ଴ଵଶଷସହ଺଻଼ଽାି';
		$expects = 'ର-ଲଳ--ଶଷସହ--଼ଽାି';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ୀୁୂୃୄ୅୆େୈ୉୊ୋୌ୍୎୏';
		$expects = 'ୀୁୂୃ---େୈ--ୋୌ୍--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '୐୑୒୓୔୕ୖୗ୘୙୚୛ଡ଼ଢ଼୞ୟ';
		$expects = '------ୖୗ----ଡ଼ଢ଼-ୟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ୠୡୢୣ୤୥୦୧୨୩୪୫୬୭୮୯';
		$expects = 'ୠୡ----୦୧୨୩୪୫୬୭୮୯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '୰ୱ୲୳୴୵୶୷୸୹୺୻୼୽୾୿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '஀஁ஂஃ஄அஆஇஈஉஊ஋஌஍எஏ';
		$expects = '--ஂஃ-அஆஇஈஉஊ---எஏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ஐ஑ஒஓஔக஖஗஘ஙச஛ஜ஝ஞட';
		$expects = 'ஐ-ஒஓஔக---ஙச-ஜ-ஞட';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '஠஡஢ணத஥஦஧நனப஫஬஭மய';
		$expects = '---ணத---நனப---மய';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ரறலளழவஶஷஸஹ஺஻஼஽ாி';
		$expects = 'ரறலளழவ-ஷஸஹ----ாி';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ீுூ௃௄௅ெேை௉ொோௌ்௎௏';
		$expects = 'ீுூ---ெேை-ொோௌ்--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ௐ௑௒௓௔௕௖ௗ௘௙௚௛௜௝௞௟';
		$expects = '-------ௗ--------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '௠௡௢௣௤௥௦௧௨௩௪௫௬௭௮௯';
		$expects = '-------௧௨௩௪௫௬௭௮௯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '௰௱௲௳௴௵௶௷௸௹௺௻௼௽௾௿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ఀఁంఃఄఅఆఇఈఉఊఋఌ఍ఎఏ';
		$expects = '-ఁంః-అఆఇఈఉఊఋఌ-ఎఏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ఐ఑ఒఓఔకఖగఘఙచఛజఝఞట';
		$expects = 'ఐ-ఒఓఔకఖగఘఙచఛజఝఞట';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ఠడఢణతథదధన఩పఫబభమయ';
		$expects = 'ఠడఢణతథదధన-పఫబభమయ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'రఱలళఴవశషసహ఺఻఼ఽాి';
		$expects = 'రఱలళ-వశషసహ----ాి';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ీుూృౄ౅ెేై౉ొోౌ్౎౏';
		$expects = 'ీుూృౄ-ెేై-ొోౌ్--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '౐౑౒౓౔ౕౖ౗ౘౙౚ౛౜ౝ౞౟';
		$expects = '-----ౕౖ---------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ౠౡౢౣ౤౥౦౧౨౩౪౫౬౭౮౯';
		$expects = 'ౠౡ----౦౧౨౩౪౫౬౭౮౯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '౰౱౲౳౴౵౶౷౸౹౺౻౼౽౾౿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ಀಁಂಃ಄ಅಆಇಈಉಊಋಌ಍ಎಏ';
		$expects = '--ಂಃ-ಅಆಇಈಉಊಋಌ-ಎಏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ಐ಑ಒಓಔಕಖಗಘಙಚಛಜಝಞಟ';
		$expects = 'ಐ-ಒಓಔಕಖಗಘಙಚಛಜಝಞಟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ಠಡಢಣತಥದಧನ಩ಪಫಬಭಮಯ';
		$expects = 'ಠಡಢಣತಥದಧನ-ಪಫಬಭಮಯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ರಱಲಳ಴ವಶಷಸಹ಺಻಼ಽಾಿ';
		$expects = 'ರಱಲಳ-ವಶಷಸಹ----ಾಿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ೀುೂೃೄ೅ೆೇೈ೉ೊೋೌ್೎೏';
		$expects = 'ೀುೂೃೄ-ೆೇೈ-ೊೋೌ್--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '೐೑೒೓೔ೕೖ೗೘೙೚೛೜ೝೞ೟';
		$expects = '-----ೕೖ-------ೞ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ೠೡೢೣ೤೥೦೧೨೩೪೫೬೭೮೯';
		$expects = 'ೠೡ----೦೧೨೩೪೫೬೭೮೯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '೰ೱೲೳ೴೵೶೷೸೹೺೻೼೽೾೿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ഀഁംഃഄഅആഇഈഉഊഋഌ഍എഏ';
		$expects = '--ംഃ-അആഇഈഉഊഋഌ-എഏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ഐ഑ഒഓഔകഖഗഘങചഛജഝഞട';
		$expects = 'ഐ-ഒഓഔകഖഗഘങചഛജഝഞട';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ഠഡഢണതഥദധനഩപഫബഭമയ';
		$expects = 'ഠഡഢണതഥദധന-പഫബഭമയ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'രറലളഴവശഷസഹഺ഻഼ഽാി';
		$expects = 'രറലളഴവശഷസഹ----ാി';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ീുൂൃൄ൅െേൈ൉ൊോൌ്ൎ൏';
		$expects = 'ീുൂൃ--െേൈ-ൊോൌ്--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '൐൑൒൓ൔൕൖൗ൘൙൚൛൜൝൞ൟ';
		$expects = '-------ൗ--------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ൠൡൢൣ൤൥൦൧൨൩൪൫൬൭൮൯';
		$expects = 'ൠൡ----൦൧൨൩൪൫൬൭൮൯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '൰൱൲൳൴൵൶൷൸൹ൺൻർൽൾൿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '඀ඁංඃ඄අආඇඈඉඊඋඌඍඎඏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ඐඑඒඓඔඕඖ඗඘඙කඛගඝඞඟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'චඡජඣඤඥඦටඨඩඪණඬතථද';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ධන඲ඳපඵබභමඹයර඼ල඾඿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'වශෂසහළෆ෇෈෉්෋෌෍෎ා';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ැෑිීු෕ූ෗ෘෙේෛොෝෞෟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '෠෡෢෣෤෥෦෧෨෩෪෫෬෭෮෯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '෰෱ෲෳ෴෵෶෷෸෹෺෻෼෽෾෿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '฀กขฃคฅฆงจฉชซฌญฎฏ';
		$expects = '-กขฃคฅฆงจฉชซฌญฎฏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ฐฑฒณดตถทธนบปผฝพฟ';
		$expects = 'ฐฑฒณดตถทธนบปผฝพฟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ภมยรฤลฦวศษสหฬอฮฯ';
		$expects = 'ภมยรฤลฦวศษสหฬอฮ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ะัาำิีึืฺุู฻฼฽฾฿';
		$expects = 'ะัาำิีึืฺุู-----';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'เแโใไๅๆ็่้๊๋์ํ๎๏';
		$expects = 'เแโใไๅๆ็่้๊๋์ํ๎-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '๐๑๒๓๔๕๖๗๘๙๚๛๜๝๞๟';
		$expects = '๐๑๒๓๔๕๖๗๘๙------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '๠๡๢๣๤๥๦๧๨๩๪๫๬๭๮๯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '๰๱๲๳๴๵๶๷๸๹๺๻๼๽๾๿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '຀ກຂ຃ຄ຅ຆງຈຉຊ຋ຌຍຎຏ';
		$expects = '-ກຂ-ຄ--ງຈ-ຊ--ຍ--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ຐຑຒຓດຕຖທຘນບປຜຝພຟ';
		$expects = '----ດຕຖທ-ນບປຜຝພຟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ຠມຢຣ຤ລ຦ວຨຩສຫຬອຮຯ';
		$expects = '-ມຢຣ-ລ-ວ--ສຫ-ອຮ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ະັາຳິີຶື຺ຸູົຼຽ຾຿';
		$expects = 'ະັາຳິີຶືຸູ-ົຼຽ--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ເແໂໃໄ໅ໆ໇່້໊໋໌ໍ໎໏';
		$expects = 'ເແໂໃໄ-ໆ-່້໊໋໌ໍ--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '໐໑໒໓໔໕໖໗໘໙໚໛ໜໝໞໟ';
		$expects = '໐໑໒໓໔໕໖໗໘໙------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '໠໡໢໣໤໥໦໧໨໩໪໫໬໭໮໯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '໰໱໲໳໴໵໶໷໸໹໺໻໼໽໾໿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ༀ༁༂༃༄༅༆༇༈༉༊་༌།༎༏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '༐༑༒༓༔༕༖༗༘༙༚༛༜༝༞༟';
		$expects = '--------༘༙------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '༠༡༢༣༤༥༦༧༨༩༪༫༬༭༮༯';
		$expects = '༠༡༢༣༤༥༦༧༨༩------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '༰༱༲༳༴༵༶༷༸༹༺༻༼༽༾༿';
		$expects = '-----༵-༷-༹----༾༿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ཀཁགགྷངཅཆཇ཈ཉཊཋཌཌྷཎཏ';
		$expects = 'ཀཁགགྷངཅཆཇ-ཉཊཋཌཌྷཎཏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ཐདདྷནཔཕབབྷམཙཚཛཛྷཝཞཟ';
		$expects = 'ཐདདྷནཔཕབབྷམཙཚཛཛྷཝཞཟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'འཡརལཤཥསཧཨཀྵཪཫཬ཭཮཯';
		$expects = 'འཡརལཤཥསཧཨཀྵ------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '཰ཱཱཱིིུུྲྀཷླྀཹེཻོཽཾཿ';
		$expects = '-ཱཱཱིིུུྲྀཷླྀཹེཻོཽཾཿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '྄ཱྀྀྂྃ྅྆྇ྈྉྊྋྌྍྎྏ';
		$expects = '྄ཱྀྀྂྃ-྆྇ྈྉྊྋ----';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ྐྑྒྒྷྔྕྖྗ྘ྙྚྛྜྜྷྞྟ';
		$expects = 'ྐྑྒྒྷྔྕ-ྗ-ྙྚྛྜྜྷྞྟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ྠྡྡྷྣྤྥྦྦྷྨྩྪྫྫྷྭྮྯ';
		$expects = 'ྠྡྡྷྣྤྥྦྦྷྨྩྪྫྫྷྭ--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ྰྱྲླྴྵྶྷྸྐྵྺྻྼ྽྾྿';
		$expects = '-ྱྲླྴྵྶྷ-ྐྵ------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '࿀࿁࿂࿃࿄࿅࿆࿇࿈࿉࿊࿋࿌࿍࿎࿏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '࿐࿑࿒࿓࿔࿕࿖࿗࿘࿙࿚࿛࿜࿝࿞࿟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '࿠࿡࿢࿣࿤࿥࿦࿧࿨࿩࿪࿫࿬࿭࿮࿯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '࿰࿱࿲࿳࿴࿵࿶࿷࿸࿹࿺࿻࿼࿽࿾࿿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSection1 method
	 *
	 * Testing characters 1000 - 1fff
	 *
	 * @return void
	 */
	public function testSection1() {
		$string = 'ကခဂဃငစဆဇဈဉညဋဌဍဎဏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'တထဒဓနပဖဗဘမယရလဝသဟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ဠအဢဣဤဥဦဧဨဩဪါာိီု';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ူေဲဳဴဵံ့း္်ျြွှဿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '၀၁၂၃၄၅၆၇၈၉၊။၌၍၎၏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ၐၑၒၓၔၕၖၗၘၙၚၛၜၝၞၟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ၠၡၢၣၤၥၦၧၨၩၪၫၬၭၮၯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ၰၱၲၳၴၵၶၷၸၹၺၻၼၽၾၿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ႀႁႂႃႄႅႆႇႈႉႊႋႌႍႎႏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '႐႑႒႓႔႕႖႗႘႙ႚႛႜႝ႞႟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ႠႡႢႣႤႥႦႧႨႩႪႫႬႭႮႯ';
		$expects = 'ႠႡႢႣႤႥႦႧႨႩႪႫႬႭႮႯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ႰႱႲႳႴႵႶႷႸႹႺႻႼႽႾႿ';
		$expects = 'ႰႱႲႳႴႵႶႷႸႹႺႻႼႽႾႿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ჀჁჂჃჄჅ჆Ⴧ჈჉჊჋჌Ⴭ჎჏';
		$expects = 'ჀჁჂჃჄჅ----------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'აბგდევზთიკლმნოპჟ';
		$expects = 'აბგდევზთიკლმნოპჟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'რსტუფქღყშჩცძწჭხჯ';
		$expects = 'რსტუფქღყშჩცძწჭხჯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ჰჱჲჳჴჵჶჷჸჹჺ჻ჼჽჾჿ';
		$expects = 'ჰჱჲჳჴჵჶ---------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᄀᄁᄂᄃᄄᄅᄆᄇᄈᄉᄊᄋᄌᄍᄎᄏ';
		$expects = 'ᄀ-ᄂᄃ-ᄅᄆᄇ-ᄉ-ᄋᄌ-ᄎᄏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᄐᄑᄒᄓᄔᄕᄖᄗᄘᄙᄚᄛᄜᄝᄞᄟ';
		$expects = 'ᄐᄑᄒ-------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᄠᄡᄢᄣᄤᄥᄦᄧᄨᄩᄪᄫᄬᄭᄮᄯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᄰᄱᄲᄳᄴᄵᄶᄷᄸᄹᄺᄻᄼᄽᄾᄿ';
		$expects = '------------ᄼ-ᄾ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᅀᅁᅂᅃᅄᅅᅆᅇᅈᅉᅊᅋᅌᅍᅎᅏ';
		$expects = 'ᅀ-----------ᅌ-ᅎ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᅐᅑᅒᅓᅔᅕᅖᅗᅘᅙᅚᅛᅜᅝᅞᅟ';
		$expects = 'ᅐ---ᅔᅕ---ᅙ-----ᅟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᅠᅡᅢᅣᅤᅥᅦᅧᅨᅩᅪᅫᅬᅭᅮᅯ';
		$expects = 'ᅠᅡ-ᅣ-ᅥ-ᅧ-ᅩ---ᅭᅮ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᅰᅱᅲᅳᅴᅵᅶᅷᅸᅹᅺᅻᅼᅽᅾᅿ';
		$expects = '--ᅲᅳ-ᅵ----------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᆀᆁᆂᆃᆄᆅᆆᆇᆈᆉᆊᆋᆌᆍᆎᆏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᆐᆑᆒᆓᆔᆕᆖᆗᆘᆙᆚᆛᆜᆝᆞᆟ';
		$expects = '--------------ᆞ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᆠᆡᆢᆣᆤᆥᆦᆧᆨᆩᆪᆫᆬᆭᆮᆯ';
		$expects = '--------ᆨ--ᆫ--ᆮᆯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᆰᆱᆲᆳᆴᆵᆶᆷᆸᆹᆺᆻᆼᆽᆾᆿ';
		$expects = '-------ᆷᆸ-ᆺ-ᆼᆽᆾᆿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᇀᇁᇂᇃᇄᇅᇆᇇᇈᇉᇊᇋᇌᇍᇎᇏ';
		$expects = 'ᇀᇁᇂ-------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᇐᇑᇒᇓᇔᇕᇖᇗᇘᇙᇚᇛᇜᇝᇞᇟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᇠᇡᇢᇣᇤᇥᇦᇧᇨᇩᇪᇫᇬᇭᇮᇯ';
		$expects = '-----------ᇫ----';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᇰᇱᇲᇳᇴᇵᇶᇷᇸᇹᇺᇻᇼᇽᇾᇿ';
		$expects = 'ᇰ--------ᇹ------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ሀሁሂሃሄህሆሇለሉሊላሌልሎሏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ሐሑሒሓሔሕሖሗመሙሚማሜምሞሟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ሠሡሢሣሤሥሦሧረሩሪራሬርሮሯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ሰሱሲሳሴስሶሷሸሹሺሻሼሽሾሿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ቀቁቂቃቄቅቆቇቈ቉ቊቋቌቍ቎቏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ቐቑቒቓቔቕቖ቗ቘ቙ቚቛቜቝ቞቟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'በቡቢባቤብቦቧቨቩቪቫቬቭቮቯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ተቱቲታቴትቶቷቸቹቺቻቼችቾቿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ኀኁኂኃኄኅኆኇኈ኉ኊኋኌኍ኎኏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ነኑኒናኔንኖኗኘኙኚኛኜኝኞኟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'አኡኢኣኤእኦኧከኩኪካኬክኮኯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ኰ኱ኲኳኴኵ኶኷ኸኹኺኻኼኽኾ኿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ዀ዁ዂዃዄዅ዆዇ወዉዊዋዌውዎዏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ዐዑዒዓዔዕዖ዗ዘዙዚዛዜዝዞዟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ዠዡዢዣዤዥዦዧየዩዪያዬይዮዯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ደዱዲዳዴድዶዷዸዹዺዻዼዽዾዿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ጀጁጂጃጄጅጆጇገጉጊጋጌግጎጏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ጐ጑ጒጓጔጕ጖጗ጘጙጚጛጜጝጞጟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ጠጡጢጣጤጥጦጧጨጩጪጫጬጭጮጯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ጰጱጲጳጴጵጶጷጸጹጺጻጼጽጾጿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ፀፁፂፃፄፅፆፇፈፉፊፋፌፍፎፏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ፐፑፒፓፔፕፖፗፘፙፚ፛፜፝፞፟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '፠፡።፣፤፥፦፧፨፩፪፫፬፭፮፯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '፰፱፲፳፴፵፶፷፸፹፺፻፼፽፾፿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᎀᎁᎂᎃᎄᎅᎆᎇᎈᎉᎊᎋᎌᎍᎎᎏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᎐᎑᎒᎓᎔᎕᎖᎗᎘᎙᎚᎛᎜᎝᎞᎟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᎠᎡᎢᎣᎤᎥᎦᎧᎨᎩᎪᎫᎬᎭᎮᎯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᎰᎱᎲᎳᎴᎵᎶᎷᎸᎹᎺᎻᎼᎽᎾᎿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᏀᏁᏂᏃᏄᏅᏆᏇᏈᏉᏊᏋᏌᏍᏎᏏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᏐᏑᏒᏓᏔᏕᏖᏗᏘᏙᏚᏛᏜᏝᏞᏟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᏠᏡᏢᏣᏤᏥᏦᏧᏨᏩᏪᏫᏬᏭᏮᏯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᏰᏱᏲᏳᏴᏵ᏶᏷ᏸᏹᏺᏻᏼᏽ᏾᏿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᐀ᐁᐂᐃᐄᐅᐆᐇᐈᐉᐊᐋᐌᐍᐎᐏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᐐᐑᐒᐓᐔᐕᐖᐗᐘᐙᐚᐛᐜᐝᐞᐟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᐠᐡᐢᐣᐤᐥᐦᐧᐨᐩᐪᐫᐬᐭᐮᐯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᐰᐱᐲᐳᐴᐵᐶᐷᐸᐹᐺᐻᐼᐽᐾᐿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᑀᑁᑂᑃᑄᑅᑆᑇᑈᑉᑊᑋᑌᑍᑎᑏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᑐᑑᑒᑓᑔᑕᑖᑗᑘᑙᑚᑛᑜᑝᑞᑟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᑠᑡᑢᑣᑤᑥᑦᑧᑨᑩᑪᑫᑬᑭᑮᑯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᑰᑱᑲᑳᑴᑵᑶᑷᑸᑹᑺᑻᑼᑽᑾᑿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᒀᒁᒂᒃᒄᒅᒆᒇᒈᒉᒊᒋᒌᒍᒎᒏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᒐᒑᒒᒓᒔᒕᒖᒗᒘᒙᒚᒛᒜᒝᒞᒟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᒠᒡᒢᒣᒤᒥᒦᒧᒨᒩᒪᒫᒬᒭᒮᒯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᒰᒱᒲᒳᒴᒵᒶᒷᒸᒹᒺᒻᒼᒽᒾᒿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᓀᓁᓂᓃᓄᓅᓆᓇᓈᓉᓊᓋᓌᓍᓎᓏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᓐᓑᓒᓓᓔᓕᓖᓗᓘᓙᓚᓛᓜᓝᓞᓟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᓠᓡᓢᓣᓤᓥᓦᓧᓨᓩᓪᓫᓬᓭᓮᓯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᓰᓱᓲᓳᓴᓵᓶᓷᓸᓹᓺᓻᓼᓽᓾᓿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᔀᔁᔂᔃᔄᔅᔆᔇᔈᔉᔊᔋᔌᔍᔎᔏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᔐᔑᔒᔓᔔᔕᔖᔗᔘᔙᔚᔛᔜᔝᔞᔟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᔠᔡᔢᔣᔤᔥᔦᔧᔨᔩᔪᔫᔬᔭᔮᔯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᔰᔱᔲᔳᔴᔵᔶᔷᔸᔹᔺᔻᔼᔽᔾᔿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᕀᕁᕂᕃᕄᕅᕆᕇᕈᕉᕊᕋᕌᕍᕎᕏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᕐᕑᕒᕓᕔᕕᕖᕗᕘᕙᕚᕛᕜᕝᕞᕟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᕠᕡᕢᕣᕤᕥᕦᕧᕨᕩᕪᕫᕬᕭᕮᕯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᕰᕱᕲᕳᕴᕵᕶᕷᕸᕹᕺᕻᕼᕽᕾᕿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᖀᖁᖂᖃᖄᖅᖆᖇᖈᖉᖊᖋᖌᖍᖎᖏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᖐᖑᖒᖓᖔᖕᖖᖗᖘᖙᖚᖛᖜᖝᖞᖟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᖠᖡᖢᖣᖤᖥᖦᖧᖨᖩᖪᖫᖬᖭᖮᖯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᖰᖱᖲᖳᖴᖵᖶᖷᖸᖹᖺᖻᖼᖽᖾᖿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᗀᗁᗂᗃᗄᗅᗆᗇᗈᗉᗊᗋᗌᗍᗎᗏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᗐᗑᗒᗓᗔᗕᗖᗗᗘᗙᗚᗛᗜᗝᗞᗟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᗠᗡᗢᗣᗤᗥᗦᗧᗨᗩᗪᗫᗬᗭᗮᗯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᗰᗱᗲᗳᗴᗵᗶᗷᗸᗹᗺᗻᗼᗽᗾᗿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᘀᘁᘂᘃᘄᘅᘆᘇᘈᘉᘊᘋᘌᘍᘎᘏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᘐᘑᘒᘓᘔᘕᘖᘗᘘᘙᘚᘛᘜᘝᘞᘟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᘠᘡᘢᘣᘤᘥᘦᘧᘨᘩᘪᘫᘬᘭᘮᘯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᘰᘱᘲᘳᘴᘵᘶᘷᘸᘹᘺᘻᘼᘽᘾᘿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᙀᙁᙂᙃᙄᙅᙆᙇᙈᙉᙊᙋᙌᙍᙎᙏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᙐᙑᙒᙓᙔᙕᙖᙗᙘᙙᙚᙛᙜᙝᙞᙟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᙠᙡᙢᙣᙤᙥᙦᙧᙨᙩᙪᙫᙬ᙭᙮ᙯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᙰᙱᙲᙳᙴᙵᙶᙷᙸᙹᙺᙻᙼᙽᙾᙿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = ' ᚁᚂᚃᚄᚅᚆᚇᚈᚉᚊᚋᚌᚍᚎᚏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᚐᚑᚒᚓᚔᚕᚖᚗᚘᚙᚚ᚛᚜᚝᚞᚟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᚠᚡᚢᚣᚤᚥᚦᚧᚨᚩᚪᚫᚬᚭᚮᚯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᚰᚱᚲᚳᚴᚵᚶᚷᚸᚹᚺᚻᚼᚽᚾᚿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᛀᛁᛂᛃᛄᛅᛆᛇᛈᛉᛊᛋᛌᛍᛎᛏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᛐᛑᛒᛓᛔᛕᛖᛗᛘᛙᛚᛛᛜᛝᛞᛟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᛠᛡᛢᛣᛤᛥᛦᛧᛨᛩᛪ᛫᛬᛭ᛮᛯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᛰᛱᛲᛳᛴᛵᛶᛷᛸ᛹᛺᛻᛼᛽᛾᛿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᜀᜁᜂᜃᜄᜅᜆᜇᜈᜉᜊᜋᜌᜍᜎᜏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᜐᜑᜒᜓ᜔᜕᜖᜗᜘᜙᜚᜛᜜᜝᜞ᜟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᜠᜡᜢᜣᜤᜥᜦᜧᜨᜩᜪᜫᜬᜭᜮᜯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᜰᜱᜲᜳ᜴᜵᜶᜷᜸᜹᜺᜻᜼᜽᜾᜿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᝀᝁᝂᝃᝄᝅᝆᝇᝈᝉᝊᝋᝌᝍᝎᝏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᝐᝑᝒᝓ᝔᝕᝖᝗᝘᝙᝚᝛᝜᝝᝞᝟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᝠᝡᝢᝣᝤᝥᝦᝧᝨᝩᝪᝫᝬ᝭ᝮᝯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᝰ᝱ᝲᝳ᝴᝵᝶᝷᝸᝹᝺᝻᝼᝽᝾᝿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'កខគឃងចឆជឈញដឋឌឍណត';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ថទធនបផពភមយរលវឝឞស';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ហឡអឣឤឥឦឧឨឩឪឫឬឭឮឯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ឰឱឲឳ឴឵ាិីឹឺុូួើឿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ៀេែៃោៅំះៈ៉៊់៌៍៎៏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '័៑្៓។៕៖ៗ៘៙៚៛ៜ៝៞៟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '០១២៣៤៥៦៧៨៩៪៫៬៭៮៯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '៰៱៲៳៴៵៶៷៸៹៺៻៼៽៾៿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᠀᠁᠂᠃᠄᠅᠆᠇᠈᠉᠊᠋᠌᠍᠎᠏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᠐᠑᠒᠓᠔᠕᠖᠗᠘᠙᠚᠛᠜᠝᠞᠟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᠠᠡᠢᠣᠤᠥᠦᠧᠨᠩᠪᠫᠬᠭᠮᠯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᠰᠱᠲᠳᠴᠵᠶᠷᠸᠹᠺᠻᠼᠽᠾᠿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᡀᡁᡂᡃᡄᡅᡆᡇᡈᡉᡊᡋᡌᡍᡎᡏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᡐᡑᡒᡓᡔᡕᡖᡗᡘᡙᡚᡛᡜᡝᡞᡟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᡠᡡᡢᡣᡤᡥᡦᡧᡨᡩᡪᡫᡬᡭᡮᡯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᡰᡱᡲᡳᡴᡵᡶᡷᡸ᡹᡺᡻᡼᡽᡾᡿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᢀᢁᢂᢃᢄᢅᢆᢇᢈᢉᢊᢋᢌᢍᢎᢏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᢐᢑᢒᢓᢔᢕᢖᢗᢘᢙᢚᢛᢜᢝᢞᢟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᢠᢡᢢᢣᢤᢥᢦᢧᢨᢩᢪ᢫᢬᢭᢮᢯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᢰᢱᢲᢳᢴᢵᢶᢷᢸᢹᢺᢻᢼᢽᢾᢿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᣀᣁᣂᣃᣄᣅᣆᣇᣈᣉᣊᣋᣌᣍᣎᣏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᣐᣑᣒᣓᣔᣕᣖᣗᣘᣙᣚᣛᣜᣝᣞᣟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᣠᣡᣢᣣᣤᣥᣦᣧᣨᣩᣪᣫᣬᣭᣮᣯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᣰᣱᣲᣳᣴᣵ᣶᣷᣸᣹᣺᣻᣼᣽᣾᣿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᤀᤁᤂᤃᤄᤅᤆᤇᤈᤉᤊᤋᤌᤍᤎᤏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᤐᤑᤒᤓᤔᤕᤖᤗᤘᤙᤚᤛᤜᤝᤞ᤟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᤠᤡᤢᤣᤤᤥᤦᤧᤨᤩᤪᤫ᤬᤭᤮᤯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᤰᤱᤲᤳᤴᤵᤶᤷᤸ᤻᤹᤺᤼᤽᤾᤿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᥀᥁᥂᥃᥄᥅᥆᥇᥈᥉᥊᥋᥌᥍᥎᥏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᥐᥑᥒᥓᥔᥕᥖᥗᥘᥙᥚᥛᥜᥝᥞᥟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᥠᥡᥢᥣᥤᥥᥦᥧᥨᥩᥪᥫᥬᥭ᥮᥯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᥰᥱᥲᥳᥴ᥵᥶᥷᥸᥹᥺᥻᥼᥽᥾᥿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᦀᦁᦂᦃᦄᦅᦆᦇᦈᦉᦊᦋᦌᦍᦎᦏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᦐᦑᦒᦓᦔᦕᦖᦗᦘᦙᦚᦛᦜᦝᦞᦟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᦠᦡᦢᦣᦤᦥᦦᦧᦨᦩᦪᦫ᦬᦭᦮᦯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᦰᦱᦲᦳᦴᦵᦶᦷᦸᦹᦺᦻᦼᦽᦾᦿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᧀᧁᧂᧃᧄᧅᧆᧇᧈᧉ᧊᧋᧌᧍᧎᧏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᧐᧑᧒᧓᧔᧕᧖᧗᧘᧙᧚᧛᧜᧝᧞᧟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᧠᧡᧢᧣᧤᧥᧦᧧᧨᧩᧪᧫᧬᧭᧮᧯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᧰᧱᧲᧳᧴᧵᧶᧷᧸᧹᧺᧻᧼᧽᧾᧿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᨀᨁᨂᨃᨄᨅᨆᨇᨈᨉᨊᨋᨌᨍᨎᨏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᨐᨑᨒᨓᨔᨕᨖᨘᨗᨙᨚᨛ᨜᨝᨞᨟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᨠᨡᨢᨣᨤᨥᨦᨧᨨᨩᨪᨫᨬᨭᨮᨯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᨰᨱᨲᨳᨴᨵᨶᨷᨸᨹᨺᨻᨼᨽᨾᨿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᩀᩁᩂᩃᩄᩅᩆᩇᩈᩉᩊᩋᩌᩍᩎᩏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᩐᩑᩒᩓᩔᩕᩖᩗᩘᩙᩚᩛᩜᩝᩞ᩟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᩠ᩡᩢᩣᩤᩥᩦᩧᩨᩩᩪᩫᩬᩭᩮᩯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᩰᩱᩲᩳᩴ᩵᩶᩷᩸᩹᩺᩻᩼᩽᩾᩿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᪀᪁᪂᪃᪄᪅᪆᪇᪈᪉᪊᪋᪌᪍᪎᪏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᪐᪑᪒᪓᪔᪕᪖᪗᪘᪙᪚᪛᪜᪝᪞᪟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᪠᪡᪢᪣᪤᪥᪦ᪧ᪨᪩᪪᪫᪬᪭᪮᪯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᪵᪶᪷᪸᪹᪺᪽᪰᪱᪲᪳᪴᪻᪼᪾ᪿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᫀ᫃᫄᫊᫁᫂᫅᫆᫇᫈᫉᫋ᫌᫍᫎ᫏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᫐᫑᫒᫓᫔᫕᫖᫗᫘᫙᫚᫛᫜᫝᫞᫟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᫠᫡᫢᫣᫤᫥᫦᫧᫨᫩᫪᫫᫬᫭᫮᫯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᫰᫱᫲᫳᫴᫵᫶᫷᫸᫹᫺᫻᫼᫽᫾᫿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᬀᬁᬂᬃᬄᬅᬆᬇᬈᬉᬊᬋᬌᬍᬎᬏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᬐᬑᬒᬓᬔᬕᬖᬗᬘᬙᬚᬛᬜᬝᬞᬟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᬠᬡᬢᬣᬤᬥᬦᬧᬨᬩᬪᬫᬬᬭᬮᬯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᬰᬱᬲᬳ᬴ᬵᬶᬷᬸᬹᬺᬻᬼᬽᬾᬿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᭀᭁᭂᭃ᭄ᭅᭆᭇᭈᭉᭊᭋᭌ᭍᭎᭏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᭐᭑᭒᭓᭔᭕᭖᭗᭘᭙᭚᭛᭜᭝᭞᭟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᭠᭡᭢᭣᭤᭥᭦᭧᭨᭩᭪᭬᭫᭭᭮᭯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᭰᭱᭲᭳᭴᭵᭶᭷᭸᭹᭺᭻᭼᭽᭾᭿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᮀᮁᮂᮃᮄᮅᮆᮇᮈᮉᮊᮋᮌᮍᮎᮏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᮐᮑᮒᮓᮔᮕᮖᮗᮘᮙᮚᮛᮜᮝᮞᮟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᮠᮡᮢᮣᮤᮥᮦᮧᮨᮩ᮪᮫ᮬᮭᮮᮯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᮰᮱᮲᮳᮴᮵᮶᮷᮸᮹ᮺᮻᮼᮽᮾᮿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᯀᯁᯂᯃᯄᯅᯆᯇᯈᯉᯊᯋᯌᯍᯎᯏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᯐᯑᯒᯓᯔᯕᯖᯗᯘᯙᯚᯛᯜᯝᯞᯟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᯠᯡᯢᯣᯤᯥ᯦ᯧᯨᯩᯪᯫᯬᯭᯮᯯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᯰᯱ᯲᯳᯴᯵᯶᯷᯸᯹᯺᯻᯼᯽᯾᯿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᰀᰁᰂᰃᰄᰅᰆᰇᰈᰉᰊᰋᰌᰍᰎᰏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᰐᰑᰒᰓᰔᰕᰖᰗᰘᰙᰚᰛᰜᰝᰞᰟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᰠᰡᰢᰣᰤᰥᰦᰧᰨᰩᰪᰫᰬᰭᰮᰯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᰰᰱᰲᰳᰴᰵᰶ᰷᰸᰹᰺᰻᰼᰽᰾᰿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᱀᱁᱂᱃᱄᱅᱆᱇᱈᱉᱊᱋᱌ᱍᱎᱏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᱐᱑᱒᱓᱔᱕᱖᱗᱘᱙ᱚᱛᱜᱝᱞᱟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᱠᱡᱢᱣᱤᱥᱦᱧᱨᱩᱪᱫᱬᱭᱮᱯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᱰᱱᱲᱳᱴᱵᱶᱷᱸᱹᱺᱻᱼᱽ᱾᱿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᲀᲁᲂᲃᲄᲅᲆᲇᲈᲉᲊ᲋᲌᲍᲎᲏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᲐᲑᲒᲓᲔᲕᲖᲗᲘᲙᲚᲛᲜᲝᲞᲟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᲠᲡᲢᲣᲤᲥᲦᲧᲨᲩᲪᲫᲬᲭᲮᲯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᲰᲱᲲᲳᲴᲵᲶᲷᲸᲹᲺ᲻᲼ᲽᲾᲿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᳀᳁᳂᳃᳄᳅᳆᳇᳈᳉᳊᳋᳌᳍᳎᳏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᳐᳑᳒᳓᳔᳕᳖᳗᳘᳙᳜᳝᳞᳟᳚᳛';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᳠᳡᳢᳣᳤᳥᳦᳧᳨ᳩᳪᳫᳬ᳭ᳮᳯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᳰᳱᳲᳳ᳴ᳵᳶ᳷᳸᳹ᳺ᳻᳼᳽᳾᳿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᴀᴁᴂᴃᴄᴅᴆᴇᴈᴉᴊᴋᴌᴍᴎᴏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᴐᴑᴒᴓᴔᴕᴖᴗᴘᴙᴚᴛᴜᴝᴞᴟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᴠᴡᴢᴣᴤᴥᴦᴧᴨᴩᴪᴫᴬᴭᴮᴯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᴰᴱᴲᴳᴴᴵᴶᴷᴸᴹᴺᴻᴼᴽᴾᴿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᵀᵁᵂᵃᵄᵅᵆᵇᵈᵉᵊᵋᵌᵍᵎᵏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᵐᵑᵒᵓᵔᵕᵖᵗᵘᵙᵚᵛᵜᵝᵞᵟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᵠᵡᵢᵣᵤᵥᵦᵧᵨᵩᵪᵫᵬᵭᵮᵯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᵰᵱᵲᵳᵴᵵᵶᵷᵸᵹᵺᵻᵼᵽᵾᵿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᶀᶁᶂᶃᶄᶅᶆᶇᶈᶉᶊᶋᶌᶍᶎᶏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᶐᶑᶒᶓᶔᶕᶖᶗᶘᶙᶚᶛᶜᶝᶞᶟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᶠᶡᶢᶣᶤᶥᶦᶧᶨᶩᶪᶫᶬᶭᶮᶯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᶰᶱᶲᶳᶴᶵᶶᶷᶸᶹᶺᶻᶼᶽᶾᶿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᷎᷂᷊᷏᷀᷁᷃᷄᷅᷆᷇᷈᷉᷋᷌᷍';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᷐᷑᷒ᷓᷔᷕᷖᷗᷘᷙᷚᷛᷜᷝᷞᷟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᷠᷡᷢᷣᷤᷥᷦᷧᷨᷩᷪᷫᷬᷭᷮᷯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '᷺᷹᷽᷿᷷᷸ᷰᷱᷲᷳᷴ᷵᷻᷾᷶᷼';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ḀḁḂḃḄḅḆḇḈḉḊḋḌḍḎḏ';
		$expects = 'ḀḁḂḃḄḅḆḇḈḉḊḋḌḍḎḏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ḐḑḒḓḔḕḖḗḘḙḚḛḜḝḞḟ';
		$expects = 'ḐḑḒḓḔḕḖḗḘḙḚḛḜḝḞḟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ḠḡḢḣḤḥḦḧḨḩḪḫḬḭḮḯ';
		$expects = 'ḠḡḢḣḤḥḦḧḨḩḪḫḬḭḮḯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ḰḱḲḳḴḵḶḷḸḹḺḻḼḽḾḿ';
		$expects = 'ḰḱḲḳḴḵḶḷḸḹḺḻḼḽḾḿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ṀṁṂṃṄṅṆṇṈṉṊṋṌṍṎṏ';
		$expects = 'ṀṁṂṃṄṅṆṇṈṉṊṋṌṍṎṏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ṐṑṒṓṔṕṖṗṘṙṚṛṜṝṞṟ';
		$expects = 'ṐṑṒṓṔṕṖṗṘṙṚṛṜṝṞṟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ṠṡṢṣṤṥṦṧṨṩṪṫṬṭṮṯ';
		$expects = 'ṠṡṢṣṤṥṦṧṨṩṪṫṬṭṮṯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ṰṱṲṳṴṵṶṷṸṹṺṻṼṽṾṿ';
		$expects = 'ṰṱṲṳṴṵṶṷṸṹṺṻṼṽṾṿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ẀẁẂẃẄẅẆẇẈẉẊẋẌẍẎẏ';
		$expects = 'ẀẁẂẃẄẅẆẇẈẉẊẋẌẍẎẏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ẐẑẒẓẔẕẖẗẘẙẚẛẜẝẞẟ';
		$expects = 'ẐẑẒẓẔẕẖẗẘẙẚẛ----';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ẠạẢảẤấẦầẨẩẪẫẬậẮắ';
		$expects = 'ẠạẢảẤấẦầẨẩẪẫẬậẮắ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ẰằẲẳẴẵẶặẸẹẺẻẼẽẾế';
		$expects = 'ẰằẲẳẴẵẶặẸẹẺẻẼẽẾế';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ỀềỂểỄễỆệỈỉỊịỌọỎỏ';
		$expects = 'ỀềỂểỄễỆệỈỉỊịỌọỎỏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ỐốỒồỔổỖỗỘộỚớỜờỞở';
		$expects = 'ỐốỒồỔổỖỗỘộỚớỜờỞở';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ỠỡỢợỤụỦủỨứỪừỬửỮữ';
		$expects = 'ỠỡỢợỤụỦủỨứỪừỬửỮữ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ỰựỲỳỴỵỶỷỸỹỺỻỼỽỾỿ';
		$expects = 'ỰựỲỳỴỵỶỷỸỹ------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ἀἁἂἃἄἅἆἇἈἉἊἋἌἍἎἏ';
		$expects = 'ἀἁἂἃἄἅἆἇἈἉἊἋἌἍἎἏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ἐἑἒἓἔἕ἖἗ἘἙἚἛἜἝ἞἟';
		$expects = 'ἐἑἒἓἔἕ--ἘἙἚἛἜἝ--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ἠἡἢἣἤἥἦἧἨἩἪἫἬἭἮἯ';
		$expects = 'ἠἡἢἣἤἥἦἧἨἩἪἫἬἭἮἯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ἰἱἲἳἴἵἶἷἸἹἺἻἼἽἾἿ';
		$expects = 'ἰἱἲἳἴἵἶἷἸἹἺἻἼἽἾἿ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ὀὁὂὃὄὅ὆὇ὈὉὊὋὌὍ὎὏';
		$expects = 'ὀὁὂὃὄὅ--ὈὉὊὋὌὍ--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ὐὑὒὓὔὕὖὗ὘Ὑ὚Ὓ὜Ὕ὞Ὗ';
		$expects = 'ὐὑὒὓὔὕὖὗ-Ὑ-Ὓ-Ὕ-Ὗ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ὠὡὢὣὤὥὦὧὨὩὪὫὬὭὮὯ';
		$expects = 'ὠὡὢὣὤὥὦὧὨὩὪὫὬὭὮὯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ὰάὲέὴήὶίὸόὺύὼώ὾὿';
		$expects = 'ὰάὲέὴήὶίὸόὺύὼώ--';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᾀᾁᾂᾃᾄᾅᾆᾇᾈᾉᾊᾋᾌᾍᾎᾏ';
		$expects = 'ᾀᾁᾂᾃᾄᾅᾆᾇᾈᾉᾊᾋᾌᾍᾎᾏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᾐᾑᾒᾓᾔᾕᾖᾗᾘᾙᾚᾛᾜᾝᾞᾟ';
		$expects = 'ᾐᾑᾒᾓᾔᾕᾖᾗᾘᾙᾚᾛᾜᾝᾞᾟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᾠᾡᾢᾣᾤᾥᾦᾧᾨᾩᾪᾫᾬᾭᾮᾯ';
		$expects = 'ᾠᾡᾢᾣᾤᾥᾦᾧᾨᾩᾪᾫᾬᾭᾮᾯ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ᾰᾱᾲᾳᾴ᾵ᾶᾷᾸᾹᾺΆᾼ᾽ι᾿';
		$expects = 'ᾰᾱᾲᾳᾴ-ᾶᾷᾸᾹᾺΆᾼ-ι-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '῀῁ῂῃῄ῅ῆῇῈΈῊΉῌ῍῎῏';
		$expects = '--ῂῃῄ-ῆῇῈΈῊΉῌ---';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ῐῑῒΐ῔῕ῖῗῘῙῚΊ῜῝῞῟';
		$expects = 'ῐῑῒΐ--ῖῗῘῙῚΊ----';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ῠῡῢΰῤῥῦῧῨῩῪΎῬ῭΅`';
		$expects = 'ῠῡῢΰῤῥῦῧῨῩῪΎῬ---';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '῰῱ῲῳῴ῵ῶῷῸΌῺΏῼ´῾῿';
		$expects = '--ῲῳῴ-ῶῷῸΌῺΏῼ---';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSection2 method
	 *
	 * Testing characters 2000 - 2fff
	 *
	 * @return void
	 */
	public function testSection2() {
		$string = '           ​‌‍‎‏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '‐‑‒–—―‖‗‘’‚‛“”„‟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '†‡•‣․‥…‧

‪‫‬‭‮ ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '‰‱′″‴‵‶‷‸‹›※‼‽‾‿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⁀⁁⁂⁃⁄⁅⁆⁇⁈⁉⁊⁋⁌⁍⁎⁏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⁐⁑⁒⁓⁔⁕⁖⁗⁘⁙⁚⁛⁜⁝⁞ ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⁠⁡⁢⁣⁤⁥⁦⁧⁨⁩⁪⁫⁬⁭⁮⁯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⁰ⁱ⁲⁳⁴⁵⁶⁷⁸⁹⁺⁻⁼⁽⁾ⁿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '₀₁₂₃₄₅₆₇₈₉₊₋₌₍₎₏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ₐₑₒₓₔₕₖₗₘₙₚₛₜ₝₞₟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '₠₡₢₣₤₥₦₧₨₩₪₫€₭₮₯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '₰₱₲₳₴₵₶₷₸₹₺₻₼₽₾₿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⃀⃁⃂⃃⃄⃅⃆⃇⃈⃉⃊⃋⃌⃍⃎⃏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⃒⃓⃘⃙⃚⃐⃑⃔⃕⃖⃗⃛⃜⃝⃞⃟';
		$expects = '⃒⃓⃘⃙⃚⃐⃑⃔⃕⃖⃗⃛⃜---';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⃠⃡⃢⃣⃤⃥⃦⃪⃫⃨⃬⃭⃮⃯⃧⃩';
		$expects = '-⃡--------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⃰⃱⃲⃳⃴⃵⃶⃷⃸⃹⃺⃻⃼⃽⃾⃿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '℀℁ℂ℃℄℅℆ℇ℈℉ℊℋℌℍℎℏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ℐℑℒℓ℔ℕ№℗℘ℙℚℛℜℝ℞℟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '℠℡™℣ℤ℥Ω℧ℨ℩KÅℬℭ℮ℯ';
		$expects = '------Ω---KÅ--℮-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ℰℱℲℳℴℵℶℷℸℹ℺℻ℼℽℾℿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⅀⅁⅂⅃⅄ⅅⅆⅇⅈⅉ⅊⅋⅌⅍ⅎ⅏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⅐⅑⅒⅓⅔⅕⅖⅗⅘⅙⅚⅛⅜⅝⅞⅟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⅠⅡⅢⅣⅤⅥⅦⅧⅨⅩⅪⅫⅬⅭⅮⅯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⅰⅱⅲⅳⅴⅵⅶⅷⅸⅹⅺⅻⅼⅽⅾⅿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ↀↁↂↃↄↅↆↇↈ↉↊↋↌↍↎↏';
		$expects = 'ↀↁↂ-------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '←↑→↓↔↕↖↗↘↙↚↛↜↝↞↟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '↠↡↢↣↤↥↦↧↨↩↪↫↬↭↮↯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '↰↱↲↳↴↵↶↷↸↹↺↻↼↽↾↿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⇀⇁⇂⇃⇄⇅⇆⇇⇈⇉⇊⇋⇌⇍⇎⇏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⇐⇑⇒⇓⇔⇕⇖⇗⇘⇙⇚⇛⇜⇝⇞⇟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⇠⇡⇢⇣⇤⇥⇦⇧⇨⇩⇪⇫⇬⇭⇮⇯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⇰⇱⇲⇳⇴⇵⇶⇷⇸⇹⇺⇻⇼⇽⇾⇿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '∀∁∂∃∄∅∆∇∈∉∊∋∌∍∎∏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '∐∑−∓∔∕∖∗∘∙√∛∜∝∞∟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '∠∡∢∣∤∥∦∧∨∩∪∫∬∭∮∯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '∰∱∲∳∴∵∶∷∸∹∺∻∼∽∾∿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '≀≁≂≃≄≅≆≇≈≉≊≋≌≍≎≏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '≐≑≒≓≔≕≖≗≘≙≚≛≜≝≞≟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '≠≡≢≣≤≥≦≧≨≩≪≫≬≭≮≯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '≰≱≲≳≴≵≶≷≸≹≺≻≼≽≾≿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⊀⊁⊂⊃⊄⊅⊆⊇⊈⊉⊊⊋⊌⊍⊎⊏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⊐⊑⊒⊓⊔⊕⊖⊗⊘⊙⊚⊛⊜⊝⊞⊟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⊠⊡⊢⊣⊤⊥⊦⊧⊨⊩⊪⊫⊬⊭⊮⊯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⊰⊱⊲⊳⊴⊵⊶⊷⊸⊹⊺⊻⊼⊽⊾⊿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⋀⋁⋂⋃⋄⋅⋆⋇⋈⋉⋊⋋⋌⋍⋎⋏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⋐⋑⋒⋓⋔⋕⋖⋗⋘⋙⋚⋛⋜⋝⋞⋟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⋠⋡⋢⋣⋤⋥⋦⋧⋨⋩⋪⋫⋬⋭⋮⋯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⋰⋱⋲⋳⋴⋵⋶⋷⋸⋹⋺⋻⋼⋽⋾⋿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⌀⌁⌂⌃⌄⌅⌆⌇⌈⌉⌊⌋⌌⌍⌎⌏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⌐⌑⌒⌓⌔⌕⌖⌗⌘⌙⌚⌛⌜⌝⌞⌟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⌠⌡⌢⌣⌤⌥⌦⌧⌨〈〉⌫⌬⌭⌮⌯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⌰⌱⌲⌳⌴⌵⌶⌷⌸⌹⌺⌻⌼⌽⌾⌿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⍀⍁⍂⍃⍄⍅⍆⍇⍈⍉⍊⍋⍌⍍⍎⍏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⍐⍑⍒⍓⍔⍕⍖⍗⍘⍙⍚⍛⍜⍝⍞⍟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⍠⍡⍢⍣⍤⍥⍦⍧⍨⍩⍪⍫⍬⍭⍮⍯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⍰⍱⍲⍳⍴⍵⍶⍷⍸⍹⍺⍻⍼⍽⍾⍿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⎀⎁⎂⎃⎄⎅⎆⎇⎈⎉⎊⎋⎌⎍⎎⎏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⎐⎑⎒⎓⎔⎕⎖⎗⎘⎙⎚⎛⎜⎝⎞⎟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⎠⎡⎢⎣⎤⎥⎦⎧⎨⎩⎪⎫⎬⎭⎮⎯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⎰⎱⎲⎳⎴⎵⎶⎷⎸⎹⎺⎻⎼⎽⎾⎿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⏀⏁⏂⏃⏄⏅⏆⏇⏈⏉⏊⏋⏌⏍⏎⏏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⏐⏑⏒⏓⏔⏕⏖⏗⏘⏙⏚⏛⏜⏝⏞⏟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⏠⏡⏢⏣⏤⏥⏦⏧⏨⏩⏪⏫⏬⏭⏮⏯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⏰⏱⏲⏳⏴⏵⏶⏷⏸⏹⏺⏻⏼⏽⏾⏿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '␀␁␂␃␄␅␆␇␈␉␊␋␌␍␎␏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '␐␑␒␓␔␕␖␗␘␙␚␛␜␝␞␟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '␠␡␢␣␤␥␦␧␨␩␪␫␬␭␮␯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '␰␱␲␳␴␵␶␷␸␹␺␻␼␽␾␿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⑀⑁⑂⑃⑄⑅⑆⑇⑈⑉⑊⑋⑌⑍⑎⑏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⑐⑑⑒⑓⑔⑕⑖⑗⑘⑙⑚⑛⑜⑝⑞⑟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⑰⑱⑲⑳⑴⑵⑶⑷⑸⑹⑺⑻⑼⑽⑾⑿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⒀⒁⒂⒃⒄⒅⒆⒇⒈⒉⒊⒋⒌⒍⒎⒏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⒐⒑⒒⒓⒔⒕⒖⒗⒘⒙⒚⒛⒜⒝⒞⒟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⒠⒡⒢⒣⒤⒥⒦⒧⒨⒩⒪⒫⒬⒭⒮⒯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⒰⒱⒲⒳⒴⒵ⒶⒷⒸⒹⒺⒻⒼⒽⒾⒿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⓀⓁⓂⓃⓄⓅⓆⓇⓈⓉⓊⓋⓌⓍⓎⓏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⓐⓑⓒⓓⓔⓕⓖⓗⓘⓙⓚⓛⓜⓝⓞⓟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⓠⓡⓢⓣⓤⓥⓦⓧⓨⓩ⓪⓫⓬⓭⓮⓯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⓰⓱⓲⓳⓴⓵⓶⓷⓸⓹⓺⓻⓼⓽⓾⓿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '─━│┃┄┅┆┇┈┉┊┋┌┍┎┏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '┐┑┒┓└┕┖┗┘┙┚┛├┝┞┟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '┠┡┢┣┤┥┦┧┨┩┪┫┬┭┮┯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '┰┱┲┳┴┵┶┷┸┹┺┻┼┽┾┿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '╀╁╂╃╄╅╆╇╈╉╊╋╌╍╎╏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '═║╒╓╔╕╖╗╘╙╚╛╜╝╞╟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '╠╡╢╣╤╥╦╧╨╩╪╫╬╭╮╯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '╰╱╲╳╴╵╶╷╸╹╺╻╼╽╾╿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '▀▁▂▃▄▅▆▇█▉▊▋▌▍▎▏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '▐░▒▓▔▕▖▗▘▙▚▛▜▝▞▟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '■□▢▣▤▥▦▧▨▩▪▫▬▭▮▯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '▰▱▲△▴▵▶▷▸▹►▻▼▽▾▿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '◀◁◂◃◄◅◆◇◈◉◊○◌◍◎●';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '◐◑◒◓◔◕◖◗◘◙◚◛◜◝◞◟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '◠◡◢◣◤◥◦◧◨◩◪◫◬◭◮◯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '◰◱◲◳◴◵◶◷◸◹◺◻◼◽◾◿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '☀☁☂☃☄★☆☇☈☉☊☋☌☍☎☏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '☐☑☒☓☔☕☖☗☘☙☚☛☜☝☞☟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '☠☡☢☣☤☥☦☧☨☩☪☫☬☭☮☯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '☰☱☲☳☴☵☶☷☸☹☺☻☼☽☾☿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '♀♁♂♃♄♅♆♇♈♉♊♋♌♍♎♏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '♐♑♒♓♔♕♖♗♘♙♚♛♜♝♞♟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '♠♡♢♣♤♥♦♧♨♩♪♫♬♭♮♯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '♰♱♲♳♴♵♶♷♸♹♺♻♼♽♾♿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⚀⚁⚂⚃⚄⚅⚆⚇⚈⚉⚊⚋⚌⚍⚎⚏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⚐⚑⚒⚓⚔⚕⚖⚗⚘⚙⚚⚛⚜⚝⚞⚟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⚠⚡⚢⚣⚤⚥⚦⚧⚨⚩⚪⚫⚬⚭⚮⚯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⚰⚱⚲⚳⚴⚵⚶⚷⚸⚹⚺⚻⚼⚽⚾⚿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⛀⛁⛂⛃⛄⛅⛆⛇⛈⛉⛊⛋⛌⛍⛎⛏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⛐⛑⛒⛓⛔⛕⛖⛗⛘⛙⛚⛛⛜⛝⛞⛟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⛠⛡⛢⛣⛤⛥⛦⛧⛨⛩⛪⛫⛬⛭⛮⛯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⛰⛱⛲⛳⛴⛵⛶⛷⛸⛹⛺⛻⛼⛽⛾⛿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '✀✁✂✃✄✅✆✇✈✉✊✋✌✍✎✏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '✐✑✒✓✔✕✖✗✘✙✚✛✜✝✞✟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '✠✡✢✣✤✥✦✧✨✩✪✫✬✭✮✯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '✰✱✲✳✴✵✶✷✸✹✺✻✼✽✾✿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '❀❁❂❃❄❅❆❇❈❉❊❋❌❍❎❏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '❐❑❒❓❔❕❖❗❘❙❚❛❜❝❞❟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '❠❡❢❣❤❥❦❧❨❩❪❫❬❭❮❯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '❰❱❲❳❴❵❶❷❸❹❺❻❼❽❾❿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '➀➁➂➃➄➅➆➇➈➉➊➋➌➍➎➏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '➐➑➒➓➔➕➖➗➘➙➚➛➜➝➞➟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '➠➡➢➣➤➥➦➧➨➩➪➫➬➭➮➯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '➰➱➲➳➴➵➶➷➸➹➺➻➼➽➾➿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⟀⟁⟂⟃⟄⟅⟆⟇⟈⟉⟊⟋⟌⟍⟎⟏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⟐⟑⟒⟓⟔⟕⟖⟗⟘⟙⟚⟛⟜⟝⟞⟟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⟠⟡⟢⟣⟤⟥⟦⟧⟨⟩⟪⟫⟬⟭⟮⟯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⟰⟱⟲⟳⟴⟵⟶⟷⟸⟹⟺⟻⟼⟽⟾⟿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⠀⠁⠂⠃⠄⠅⠆⠇⠈⠉⠊⠋⠌⠍⠎⠏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⠐⠑⠒⠓⠔⠕⠖⠗⠘⠙⠚⠛⠜⠝⠞⠟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⠠⠡⠢⠣⠤⠥⠦⠧⠨⠩⠪⠫⠬⠭⠮⠯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⠰⠱⠲⠳⠴⠵⠶⠷⠸⠹⠺⠻⠼⠽⠾⠿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⡀⡁⡂⡃⡄⡅⡆⡇⡈⡉⡊⡋⡌⡍⡎⡏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⡐⡑⡒⡓⡔⡕⡖⡗⡘⡙⡚⡛⡜⡝⡞⡟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⡠⡡⡢⡣⡤⡥⡦⡧⡨⡩⡪⡫⡬⡭⡮⡯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⡰⡱⡲⡳⡴⡵⡶⡷⡸⡹⡺⡻⡼⡽⡾⡿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⢀⢁⢂⢃⢄⢅⢆⢇⢈⢉⢊⢋⢌⢍⢎⢏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⢐⢑⢒⢓⢔⢕⢖⢗⢘⢙⢚⢛⢜⢝⢞⢟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⢠⢡⢢⢣⢤⢥⢦⢧⢨⢩⢪⢫⢬⢭⢮⢯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⢰⢱⢲⢳⢴⢵⢶⢷⢸⢹⢺⢻⢼⢽⢾⢿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⣀⣁⣂⣃⣄⣅⣆⣇⣈⣉⣊⣋⣌⣍⣎⣏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⣐⣑⣒⣓⣔⣕⣖⣗⣘⣙⣚⣛⣜⣝⣞⣟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⣠⣡⣢⣣⣤⣥⣦⣧⣨⣩⣪⣫⣬⣭⣮⣯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⣰⣱⣲⣳⣴⣵⣶⣷⣸⣹⣺⣻⣼⣽⣾⣿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⤀⤁⤂⤃⤄⤅⤆⤇⤈⤉⤊⤋⤌⤍⤎⤏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⤐⤑⤒⤓⤔⤕⤖⤗⤘⤙⤚⤛⤜⤝⤞⤟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⤠⤡⤢⤣⤤⤥⤦⤧⤨⤩⤪⤫⤬⤭⤮⤯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⤰⤱⤲⤳⤴⤵⤶⤷⤸⤹⤺⤻⤼⤽⤾⤿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⥀⥁⥂⥃⥄⥅⥆⥇⥈⥉⥊⥋⥌⥍⥎⥏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⥐⥑⥒⥓⥔⥕⥖⥗⥘⥙⥚⥛⥜⥝⥞⥟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⥠⥡⥢⥣⥤⥥⥦⥧⥨⥩⥪⥫⥬⥭⥮⥯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⥰⥱⥲⥳⥴⥵⥶⥷⥸⥹⥺⥻⥼⥽⥾⥿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⦀⦁⦂⦃⦄⦅⦆⦇⦈⦉⦊⦋⦌⦍⦎⦏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⦐⦑⦒⦓⦔⦕⦖⦗⦘⦙⦚⦛⦜⦝⦞⦟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⦠⦡⦢⦣⦤⦥⦦⦧⦨⦩⦪⦫⦬⦭⦮⦯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⦰⦱⦲⦳⦴⦵⦶⦷⦸⦹⦺⦻⦼⦽⦾⦿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⧀⧁⧂⧃⧄⧅⧆⧇⧈⧉⧊⧋⧌⧍⧎⧏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⧐⧑⧒⧓⧔⧕⧖⧗⧘⧙⧚⧛⧜⧝⧞⧟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⧠⧡⧢⧣⧤⧥⧦⧧⧨⧩⧪⧫⧬⧭⧮⧯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⧰⧱⧲⧳⧴⧵⧶⧷⧸⧹⧺⧻⧼⧽⧾⧿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⨀⨁⨂⨃⨄⨅⨆⨇⨈⨉⨊⨋⨌⨍⨎⨏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⨐⨑⨒⨓⨔⨕⨖⨗⨘⨙⨚⨛⨜⨝⨞⨟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⨠⨡⨢⨣⨤⨥⨦⨧⨨⨩⨪⨫⨬⨭⨮⨯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⨰⨱⨲⨳⨴⨵⨶⨷⨸⨹⨺⨻⨼⨽⨾⨿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⩀⩁⩂⩃⩄⩅⩆⩇⩈⩉⩊⩋⩌⩍⩎⩏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⩐⩑⩒⩓⩔⩕⩖⩗⩘⩙⩚⩛⩜⩝⩞⩟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⩠⩡⩢⩣⩤⩥⩦⩧⩨⩩⩪⩫⩬⩭⩮⩯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⩰⩱⩲⩳⩴⩵⩶⩷⩸⩹⩺⩻⩼⩽⩾⩿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⪀⪁⪂⪃⪄⪅⪆⪇⪈⪉⪊⪋⪌⪍⪎⪏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⪐⪑⪒⪓⪔⪕⪖⪗⪘⪙⪚⪛⪜⪝⪞⪟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⪠⪡⪢⪣⪤⪥⪦⪧⪨⪩⪪⪫⪬⪭⪮⪯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⪰⪱⪲⪳⪴⪵⪶⪷⪸⪹⪺⪻⪼⪽⪾⪿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⫀⫁⫂⫃⫄⫅⫆⫇⫈⫉⫊⫋⫌⫍⫎⫏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⫐⫑⫒⫓⫔⫕⫖⫗⫘⫙⫚⫛⫝̸⫝⫞⫟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⫠⫡⫢⫣⫤⫥⫦⫧⫨⫩⫪⫫⫬⫭⫮⫯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⫰⫱⫲⫳⫴⫵⫶⫷⫸⫹⫺⫻⫼⫽⫾⫿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⬀⬁⬂⬃⬄⬅⬆⬇⬈⬉⬊⬋⬌⬍⬎⬏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⬐⬑⬒⬓⬔⬕⬖⬗⬘⬙⬚⬛⬜⬝⬞⬟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⬠⬡⬢⬣⬤⬥⬦⬧⬨⬩⬪⬫⬬⬭⬮⬯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⬰⬱⬲⬳⬴⬵⬶⬷⬸⬹⬺⬻⬼⬽⬾⬿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⭀⭁⭂⭃⭄⭅⭆⭇⭈⭉⭊⭋⭌⭍⭎⭏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⭐⭑⭒⭓⭔⭕⭖⭗⭘⭙⭚⭛⭜⭝⭞⭟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⭠⭡⭢⭣⭤⭥⭦⭧⭨⭩⭪⭫⭬⭭⭮⭯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⭰⭱⭲⭳⭴⭵⭶⭷⭸⭹⭺⭻⭼⭽⭾⭿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⮀⮁⮂⮃⮄⮅⮆⮇⮈⮉⮊⮋⮌⮍⮎⮏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⮐⮑⮒⮓⮔⮕⮖⮗⮘⮙⮚⮛⮜⮝⮞⮟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⮠⮡⮢⮣⮤⮥⮦⮧⮨⮩⮪⮫⮬⮭⮮⮯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⮰⮱⮲⮳⮴⮵⮶⮷⮸⮹⮺⮻⮼⮽⮾⮿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⯀⯁⯂⯃⯄⯅⯆⯇⯈⯉⯊⯋⯌⯍⯎⯏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⯐⯑⯒⯓⯔⯕⯖⯗⯘⯙⯚⯛⯜⯝⯞⯟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⯠⯡⯢⯣⯤⯥⯦⯧⯨⯩⯪⯫⯬⯭⯮⯯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⯰⯱⯲⯳⯴⯵⯶⯷⯸⯹⯺⯻⯼⯽⯾⯿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⰀⰁⰂⰃⰄⰅⰆⰇⰈⰉⰊⰋⰌⰍⰎⰏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⰐⰑⰒⰓⰔⰕⰖⰗⰘⰙⰚⰛⰜⰝⰞⰟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⰠⰡⰢⰣⰤⰥⰦⰧⰨⰩⰪⰫⰬⰭⰮⰯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⰰⰱⰲⰳⰴⰵⰶⰷⰸⰹⰺⰻⰼⰽⰾⰿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⱀⱁⱂⱃⱄⱅⱆⱇⱈⱉⱊⱋⱌⱍⱎⱏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⱐⱑⱒⱓⱔⱕⱖⱗⱘⱙⱚⱛⱜⱝⱞⱟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⱠⱡⱢⱣⱤⱥⱦⱧⱨⱩⱪⱫⱬⱭⱮⱯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⱰⱱⱲⱳⱴⱵⱶⱷⱸⱹⱺⱻⱼⱽⱾⱿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⲀⲁⲂⲃⲄⲅⲆⲇⲈⲉⲊⲋⲌⲍⲎⲏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⲐⲑⲒⲓⲔⲕⲖⲗⲘⲙⲚⲛⲜⲝⲞⲟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⲠⲡⲢⲣⲤⲥⲦⲧⲨⲩⲪⲫⲬⲭⲮⲯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⲰⲱⲲⲳⲴⲵⲶⲷⲸⲹⲺⲻⲼⲽⲾⲿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⳀⳁⳂⳃⳄⳅⳆⳇⳈⳉⳊⳋⳌⳍⳎⳏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⳐⳑⳒⳓⳔⳕⳖⳗⳘⳙⳚⳛⳜⳝⳞⳟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⳠⳡⳢⳣⳤ⳥⳦⳧⳨⳩⳪ⳫⳬⳭⳮ⳯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⳰⳱Ⳳⳳ⳴⳵⳶⳷⳸⳹⳺⳻⳼⳽⳾⳿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⴀⴁⴂⴃⴄⴅⴆⴇⴈⴉⴊⴋⴌⴍⴎⴏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⴐⴑⴒⴓⴔⴕⴖⴗⴘⴙⴚⴛⴜⴝⴞⴟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⴠⴡⴢⴣⴤⴥ⴦ⴧ⴨⴩⴪⴫⴬ⴭ⴮⴯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⴰⴱⴲⴳⴴⴵⴶⴷⴸⴹⴺⴻⴼⴽⴾⴿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⵀⵁⵂⵃⵄⵅⵆⵇⵈⵉⵊⵋⵌⵍⵎⵏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⵐⵑⵒⵓⵔⵕⵖⵗⵘⵙⵚⵛⵜⵝⵞⵟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⵠⵡⵢⵣⵤⵥⵦⵧ⵨⵩⵪⵫⵬⵭⵮ⵯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⵰⵱⵲⵳⵴⵵⵶⵷⵸⵹⵺⵻⵼⵽⵾⵿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⶀⶁⶂⶃⶄⶅⶆⶇⶈⶉⶊⶋⶌⶍⶎⶏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⶐⶑⶒⶓⶔⶕⶖ⶗⶘⶙⶚⶛⶜⶝⶞⶟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⶠⶡⶢⶣⶤⶥⶦ⶧ⶨⶩⶪⶫⶬⶭⶮ⶯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⶰⶱⶲⶳⶴⶵⶶ⶷ⶸⶹⶺⶻⶼⶽⶾ⶿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⷀⷁⷂⷃⷄⷅⷆ⷇ⷈⷉⷊⷋⷌⷍⷎ⷏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⷐⷑⷒⷓⷔⷕⷖ⷗ⷘⷙⷚⷛⷜⷝⷞ⷟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⷠⷡⷢⷣⷤⷥⷦⷧⷨⷩⷪⷫⷬⷭⷮⷯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ⷰⷱⷲⷳⷴⷵⷶⷷⷸⷹⷺⷻⷼⷽⷾⷿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⸀⸁⸂⸃⸄⸅⸆⸇⸈⸉⸊⸋⸌⸍⸎⸏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⸐⸑⸒⸓⸔⸕⸖⸗⸘⸙⸚⸛⸜⸝⸞⸟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⸠⸡⸢⸣⸤⸥⸦⸧⸨⸩⸪⸫⸬⸭⸮ⸯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⸰⸱⸲⸳⸴⸵⸶⸷⸸⸹⸺⸻⸼⸽⸾⸿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⹀⹁⹂⹃⹄⹅⹆⹇⹈⹉⹊⹋⹌⹍⹎⹏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⹐⹑⹒⹓⹔⹕⹖⹗⹘⹙⹚⹛⹜⹝⹞⹟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⹠⹡⹢⹣⹤⹥⹦⹧⹨⹩⹪⹫⹬⹭⹮⹯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⹰⹱⹲⹳⹴⹵⹶⹷⹸⹹⹺⹻⹼⹽⹾⹿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⺀⺁⺂⺃⺄⺅⺆⺇⺈⺉⺊⺋⺌⺍⺎⺏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⺐⺑⺒⺓⺔⺕⺖⺗⺘⺙⺚⺛⺜⺝⺞⺟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⺠⺡⺢⺣⺤⺥⺦⺧⺨⺩⺪⺫⺬⺭⺮⺯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⺰⺱⺲⺳⺴⺵⺶⺷⺸⺹⺺⺻⺼⺽⺾⺿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⻀⻁⻂⻃⻄⻅⻆⻇⻈⻉⻊⻋⻌⻍⻎⻏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⻐⻑⻒⻓⻔⻕⻖⻗⻘⻙⻚⻛⻜⻝⻞⻟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⻠⻡⻢⻣⻤⻥⻦⻧⻨⻩⻪⻫⻬⻭⻮⻯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⻰⻱⻲⻳⻴⻵⻶⻷⻸⻹⻺⻻⻼⻽⻾⻿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⼀⼁⼂⼃⼄⼅⼆⼇⼈⼉⼊⼋⼌⼍⼎⼏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⼐⼑⼒⼓⼔⼕⼖⼗⼘⼙⼚⼛⼜⼝⼞⼟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⼠⼡⼢⼣⼤⼥⼦⼧⼨⼩⼪⼫⼬⼭⼮⼯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⼰⼱⼲⼳⼴⼵⼶⼷⼸⼹⼺⼻⼼⼽⼾⼿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⽀⽁⽂⽃⽄⽅⽆⽇⽈⽉⽊⽋⽌⽍⽎⽏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⽐⽑⽒⽓⽔⽕⽖⽗⽘⽙⽚⽛⽜⽝⽞⽟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⽠⽡⽢⽣⽤⽥⽦⽧⽨⽩⽪⽫⽬⽭⽮⽯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⽰⽱⽲⽳⽴⽵⽶⽷⽸⽹⽺⽻⽼⽽⽾⽿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⾀⾁⾂⾃⾄⾅⾆⾇⾈⾉⾊⾋⾌⾍⾎⾏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⾐⾑⾒⾓⾔⾕⾖⾗⾘⾙⾚⾛⾜⾝⾞⾟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⾠⾡⾢⾣⾤⾥⾦⾧⾨⾩⾪⾫⾬⾭⾮⾯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⾰⾱⾲⾳⾴⾵⾶⾷⾸⾹⾺⾻⾼⾽⾾⾿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⿀⿁⿂⿃⿄⿅⿆⿇⿈⿉⿊⿋⿌⿍⿎⿏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⿐⿑⿒⿓⿔⿕⿖⿗⿘⿙⿚⿛⿜⿝⿞⿟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⿠⿡⿢⿣⿤⿥⿦⿧⿨⿩⿪⿫⿬⿭⿮⿯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '⿰⿱⿲⿳⿴⿵⿶⿷⿸⿹⿺⿻⿼⿽⿾⿿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSection3 method
	 *
	 * Testing characters 3000 - 3fff
	 *
	 * @return void
	 */
	public function testSection3() {
		$string = '　、。〃〄々〆〇〈〉《》「」『』';
		$expects = '-----々-〇--------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '【】〒〓〔〕〖〗〘〙〚〛〜〝〞〟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '〠〡〢〣〤〥〦〧〨〩〪〭〮〯〫〬';
		$expects = '-〡〢〣〤〥〦〧〨〩〪〭〮〯〫〬';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '〰〱〲〳〴〵〶〷〸〹〺〻〼〽〾〿';
		$expects = '-〱〲〳〴〵----------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '぀ぁあぃいぅうぇえぉおかがきぎく';
		$expects = '-ぁあぃいぅうぇえぉおかがきぎく';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ぐけげこごさざしじすずせぜそぞた';
		$expects = 'ぐけげこごさざしじすずせぜそぞた';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'だちぢっつづてでとどなにぬねのは';
		$expects = 'だちぢっつづてでとどなにぬねのは';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ばぱひびぴふぶぷへべぺほぼぽまみ';
		$expects = 'ばぱひびぴふぶぷへべぺほぼぽまみ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'むめもゃやゅゆょよらりるれろゎわ';
		$expects = 'むめもゃやゅゆょよらりるれろゎわ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ゐゑをんゔゕゖ゗゘゙゚゛゜ゝゞゟ';
		$expects = 'ゐゑをんゔ----゙゚--ゝゞ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '゠ァアィイゥウェエォオカガキギク';
		$expects = '-ァアィイゥウェエォオカガキギク';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'グケゲコゴサザシジスズセゼソゾタ';
		$expects = 'グケゲコゴサザシジスズセゼソゾタ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ダチヂッツヅテデトドナニヌネノハ';
		$expects = 'ダチヂッツヅテデトドナニヌネノハ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'バパヒビピフブプヘベペホボポマミ';
		$expects = 'バパヒビピフブプヘベペホボポマミ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ムメモャヤュユョヨラリルレロヮワ';
		$expects = 'ムメモャヤュユョヨラリルレロヮワ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ヰヱヲンヴヵヶヷヸヹヺ・ーヽヾヿ';
		$expects = 'ヰヱヲンヴヵヶヷヸヹヺ-ーヽヾ-';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㄀㄁㄂㄃㄄ㄅㄆㄇㄈㄉㄊㄋㄌㄍㄎㄏ';
		$expects = '-----ㄅㄆㄇㄈㄉㄊㄋㄌㄍㄎㄏ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㄐㄑㄒㄓㄔㄕㄖㄗㄘㄙㄚㄛㄜㄝㄞㄟ';
		$expects = 'ㄐㄑㄒㄓㄔㄕㄖㄗㄘㄙㄚㄛㄜㄝㄞㄟ';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㄠㄡㄢㄣㄤㄥㄦㄧㄨㄩㄪㄫㄬㄭㄮㄯ';
		$expects = 'ㄠㄡㄢㄣㄤㄥㄦㄧㄨㄩㄪㄫㄬ---';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㄰ㄱㄲㄳㄴㄵㄶㄷㄸㄹㄺㄻㄼㄽㄾㄿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㅀㅁㅂㅃㅄㅅㅆㅇㅈㅉㅊㅋㅌㅍㅎㅏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㅐㅑㅒㅓㅔㅕㅖㅗㅘㅙㅚㅛㅜㅝㅞㅟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㅠㅡㅢㅣㅤㅥㅦㅧㅨㅩㅪㅫㅬㅭㅮㅯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㅰㅱㅲㅳㅴㅵㅶㅷㅸㅹㅺㅻㅼㅽㅾㅿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㆀㆁㆂㆃㆄㆅㆆㆇㆈㆉㆊㆋㆌㆍㆎ㆏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㆐㆑㆒㆓㆔㆕㆖㆗㆘㆙㆚㆛㆜㆝㆞㆟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㆠㆡㆢㆣㆤㆥㆦㆧㆨㆩㆪㆫㆬㆭㆮㆯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㆰㆱㆲㆳㆴㆵㆶㆷㆸㆹㆺㆻㆼㆽㆾㆿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㇀㇁㇂㇃㇄㇅㇆㇇㇈㇉㇊㇋㇌㇍㇎㇏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㇐㇑㇒㇓㇔㇕㇖㇗㇘㇙㇚㇛㇜㇝㇞㇟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㇠㇡㇢㇣㇤㇥㇦㇧㇨㇩㇪㇫㇬㇭㇮㇯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ㇰㇱㇲㇳㇴㇵㇶㇷㇸㇹㇺㇻㇼㇽㇾㇿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㈀㈁㈂㈃㈄㈅㈆㈇㈈㈉㈊㈋㈌㈍㈎㈏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㈐㈑㈒㈓㈔㈕㈖㈗㈘㈙㈚㈛㈜㈝㈞㈟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㈠㈡㈢㈣㈤㈥㈦㈧㈨㈩㈪㈫㈬㈭㈮㈯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㈰㈱㈲㈳㈴㈵㈶㈷㈸㈹㈺㈻㈼㈽㈾㈿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㉀㉁㉂㉃㉄㉅㉆㉇㉈㉉㉊㉋㉌㉍㉎㉏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㉐㉑㉒㉓㉔㉕㉖㉗㉘㉙㉚㉛㉜㉝㉞㉟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㉠㉡㉢㉣㉤㉥㉦㉧㉨㉩㉪㉫㉬㉭㉮㉯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㉰㉱㉲㉳㉴㉵㉶㉷㉸㉹㉺㉻㉼㉽㉾㉿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㊀㊁㊂㊃㊄㊅㊆㊇㊈㊉㊊㊋㊌㊍㊎㊏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㊐㊑㊒㊓㊔㊕㊖㊗㊘㊙㊚㊛㊜㊝㊞㊟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㊠㊡㊢㊣㊤㊥㊦㊧㊨㊩㊪㊫㊬㊭㊮㊯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㊰㊱㊲㊳㊴㊵㊶㊷㊸㊹㊺㊻㊼㊽㊾㊿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㋀㋁㋂㋃㋄㋅㋆㋇㋈㋉㋊㋋㋌㋍㋎㋏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㋐㋑㋒㋓㋔㋕㋖㋗㋘㋙㋚㋛㋜㋝㋞㋟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㋠㋡㋢㋣㋤㋥㋦㋧㋨㋩㋪㋫㋬㋭㋮㋯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㋰㋱㋲㋳㋴㋵㋶㋷㋸㋹㋺㋻㋼㋽㋾㋿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㌀㌁㌂㌃㌄㌅㌆㌇㌈㌉㌊㌋㌌㌍㌎㌏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㌐㌑㌒㌓㌔㌕㌖㌗㌘㌙㌚㌛㌜㌝㌞㌟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㌠㌡㌢㌣㌤㌥㌦㌧㌨㌩㌪㌫㌬㌭㌮㌯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㌰㌱㌲㌳㌴㌵㌶㌷㌸㌹㌺㌻㌼㌽㌾㌿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㍀㍁㍂㍃㍄㍅㍆㍇㍈㍉㍊㍋㍌㍍㍎㍏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㍐㍑㍒㍓㍔㍕㍖㍗㍘㍙㍚㍛㍜㍝㍞㍟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㍠㍡㍢㍣㍤㍥㍦㍧㍨㍩㍪㍫㍬㍭㍮㍯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㍰㍱㍲㍳㍴㍵㍶㍷㍸㍹㍺㍻㍼㍽㍾㍿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㎀㎁㎂㎃㎄㎅㎆㎇㎈㎉㎊㎋㎌㎍㎎㎏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㎐㎑㎒㎓㎔㎕㎖㎗㎘㎙㎚㎛㎜㎝㎞㎟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㎠㎡㎢㎣㎤㎥㎦㎧㎨㎩㎪㎫㎬㎭㎮㎯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㎰㎱㎲㎳㎴㎵㎶㎷㎸㎹㎺㎻㎼㎽㎾㎿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㏀㏁㏂㏃㏄㏅㏆㏇㏈㏉㏊㏋㏌㏍㏎㏏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㏐㏑㏒㏓㏔㏕㏖㏗㏘㏙㏚㏛㏜㏝㏞㏟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㏠㏡㏢㏣㏤㏥㏦㏧㏨㏩㏪㏫㏬㏭㏮㏯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㏰㏱㏲㏳㏴㏵㏶㏷㏸㏹㏺㏻㏼㏽㏾㏿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㐀㐁㐂㐃㐄㐅㐆㐇㐈㐉㐊㐋㐌㐍㐎㐏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㐐㐑㐒㐓㐔㐕㐖㐗㐘㐙㐚㐛㐜㐝㐞㐟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㐠㐡㐢㐣㐤㐥㐦㐧㐨㐩㐪㐫㐬㐭㐮㐯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㐰㐱㐲㐳㐴㐵㐶㐷㐸㐹㐺㐻㐼㐽㐾㐿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㑀㑁㑂㑃㑄㑅㑆㑇㑈㑉㑊㑋㑌㑍㑎㑏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㑐㑑㑒㑓㑔㑕㑖㑗㑘㑙㑚㑛㑜㑝㑞㑟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㑠㑡㑢㑣㑤㑥㑦㑧㑨㑩㑪㑫㑬㑭㑮㑯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㑰㑱㑲㑳㑴㑵㑶㑷㑸㑹㑺㑻㑼㑽㑾㑿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㒀㒁㒂㒃㒄㒅㒆㒇㒈㒉㒊㒋㒌㒍㒎㒏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㒐㒑㒒㒓㒔㒕㒖㒗㒘㒙㒚㒛㒜㒝㒞㒟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㒠㒡㒢㒣㒤㒥㒦㒧㒨㒩㒪㒫㒬㒭㒮㒯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㒰㒱㒲㒳㒴㒵㒶㒷㒸㒹㒺㒻㒼㒽㒾㒿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㓀㓁㓂㓃㓄㓅㓆㓇㓈㓉㓊㓋㓌㓍㓎㓏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㓐㓑㓒㓓㓔㓕㓖㓗㓘㓙㓚㓛㓜㓝㓞㓟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㓠㓡㓢㓣㓤㓥㓦㓧㓨㓩㓪㓫㓬㓭㓮㓯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㓰㓱㓲㓳㓴㓵㓶㓷㓸㓹㓺㓻㓼㓽㓾㓿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㔀㔁㔂㔃㔄㔅㔆㔇㔈㔉㔊㔋㔌㔍㔎㔏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㔐㔑㔒㔓㔔㔕㔖㔗㔘㔙㔚㔛㔜㔝㔞㔟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㔠㔡㔢㔣㔤㔥㔦㔧㔨㔩㔪㔫㔬㔭㔮㔯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㔰㔱㔲㔳㔴㔵㔶㔷㔸㔹㔺㔻㔼㔽㔾㔿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㕀㕁㕂㕃㕄㕅㕆㕇㕈㕉㕊㕋㕌㕍㕎㕏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㕐㕑㕒㕓㕔㕕㕖㕗㕘㕙㕚㕛㕜㕝㕞㕟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㕠㕡㕢㕣㕤㕥㕦㕧㕨㕩㕪㕫㕬㕭㕮㕯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㕰㕱㕲㕳㕴㕵㕶㕷㕸㕹㕺㕻㕼㕽㕾㕿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㖀㖁㖂㖃㖄㖅㖆㖇㖈㖉㖊㖋㖌㖍㖎㖏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㖐㖑㖒㖓㖔㖕㖖㖗㖘㖙㖚㖛㖜㖝㖞㖟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㖠㖡㖢㖣㖤㖥㖦㖧㖨㖩㖪㖫㖬㖭㖮㖯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㖰㖱㖲㖳㖴㖵㖶㖷㖸㖹㖺㖻㖼㖽㖾㖿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㗀㗁㗂㗃㗄㗅㗆㗇㗈㗉㗊㗋㗌㗍㗎㗏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㗐㗑㗒㗓㗔㗕㗖㗗㗘㗙㗚㗛㗜㗝㗞㗟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㗠㗡㗢㗣㗤㗥㗦㗧㗨㗩㗪㗫㗬㗭㗮㗯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㗰㗱㗲㗳㗴㗵㗶㗷㗸㗹㗺㗻㗼㗽㗾㗿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㘀㘁㘂㘃㘄㘅㘆㘇㘈㘉㘊㘋㘌㘍㘎㘏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㘐㘑㘒㘓㘔㘕㘖㘗㘘㘙㘚㘛㘜㘝㘞㘟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㘠㘡㘢㘣㘤㘥㘦㘧㘨㘩㘪㘫㘬㘭㘮㘯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㘰㘱㘲㘳㘴㘵㘶㘷㘸㘹㘺㘻㘼㘽㘾㘿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㙀㙁㙂㙃㙄㙅㙆㙇㙈㙉㙊㙋㙌㙍㙎㙏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㙐㙑㙒㙓㙔㙕㙖㙗㙘㙙㙚㙛㙜㙝㙞㙟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㙠㙡㙢㙣㙤㙥㙦㙧㙨㙩㙪㙫㙬㙭㙮㙯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㙰㙱㙲㙳㙴㙵㙶㙷㙸㙹㙺㙻㙼㙽㙾㙿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㚀㚁㚂㚃㚄㚅㚆㚇㚈㚉㚊㚋㚌㚍㚎㚏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㚐㚑㚒㚓㚔㚕㚖㚗㚘㚙㚚㚛㚜㚝㚞㚟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㚠㚡㚢㚣㚤㚥㚦㚧㚨㚩㚪㚫㚬㚭㚮㚯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㚰㚱㚲㚳㚴㚵㚶㚷㚸㚹㚺㚻㚼㚽㚾㚿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㛀㛁㛂㛃㛄㛅㛆㛇㛈㛉㛊㛋㛌㛍㛎㛏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㛐㛑㛒㛓㛔㛕㛖㛗㛘㛙㛚㛛㛜㛝㛞㛟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㛠㛡㛢㛣㛤㛥㛦㛧㛨㛩㛪㛫㛬㛭㛮㛯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㛰㛱㛲㛳㛴㛵㛶㛷㛸㛹㛺㛻㛼㛽㛾㛿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㜀㜁㜂㜃㜄㜅㜆㜇㜈㜉㜊㜋㜌㜍㜎㜏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㜐㜑㜒㜓㜔㜕㜖㜗㜘㜙㜚㜛㜜㜝㜞㜟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㜠㜡㜢㜣㜤㜥㜦㜧㜨㜩㜪㜫㜬㜭㜮㜯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㜰㜱㜲㜳㜴㜵㜶㜷㜸㜹㜺㜻㜼㜽㜾㜿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㝀㝁㝂㝃㝄㝅㝆㝇㝈㝉㝊㝋㝌㝍㝎㝏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㝐㝑㝒㝓㝔㝕㝖㝗㝘㝙㝚㝛㝜㝝㝞㝟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㝠㝡㝢㝣㝤㝥㝦㝧㝨㝩㝪㝫㝬㝭㝮㝯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㝰㝱㝲㝳㝴㝵㝶㝷㝸㝹㝺㝻㝼㝽㝾㝿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㞀㞁㞂㞃㞄㞅㞆㞇㞈㞉㞊㞋㞌㞍㞎㞏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㞐㞑㞒㞓㞔㞕㞖㞗㞘㞙㞚㞛㞜㞝㞞㞟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㞠㞡㞢㞣㞤㞥㞦㞧㞨㞩㞪㞫㞬㞭㞮㞯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㞰㞱㞲㞳㞴㞵㞶㞷㞸㞹㞺㞻㞼㞽㞾㞿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㟀㟁㟂㟃㟄㟅㟆㟇㟈㟉㟊㟋㟌㟍㟎㟏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㟐㟑㟒㟓㟔㟕㟖㟗㟘㟙㟚㟛㟜㟝㟞㟟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㟠㟡㟢㟣㟤㟥㟦㟧㟨㟩㟪㟫㟬㟭㟮㟯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㟰㟱㟲㟳㟴㟵㟶㟷㟸㟹㟺㟻㟼㟽㟾㟿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㠀㠁㠂㠃㠄㠅㠆㠇㠈㠉㠊㠋㠌㠍㠎㠏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㠐㠑㠒㠓㠔㠕㠖㠗㠘㠙㠚㠛㠜㠝㠞㠟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㠠㠡㠢㠣㠤㠥㠦㠧㠨㠩㠪㠫㠬㠭㠮㠯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㠰㠱㠲㠳㠴㠵㠶㠷㠸㠹㠺㠻㠼㠽㠾㠿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㡀㡁㡂㡃㡄㡅㡆㡇㡈㡉㡊㡋㡌㡍㡎㡏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㡐㡑㡒㡓㡔㡕㡖㡗㡘㡙㡚㡛㡜㡝㡞㡟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㡠㡡㡢㡣㡤㡥㡦㡧㡨㡩㡪㡫㡬㡭㡮㡯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㡰㡱㡲㡳㡴㡵㡶㡷㡸㡹㡺㡻㡼㡽㡾㡿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㢀㢁㢂㢃㢄㢅㢆㢇㢈㢉㢊㢋㢌㢍㢎㢏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㢐㢑㢒㢓㢔㢕㢖㢗㢘㢙㢚㢛㢜㢝㢞㢟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㢠㢡㢢㢣㢤㢥㢦㢧㢨㢩㢪㢫㢬㢭㢮㢯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㢰㢱㢲㢳㢴㢵㢶㢷㢸㢹㢺㢻㢼㢽㢾㢿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㣀㣁㣂㣃㣄㣅㣆㣇㣈㣉㣊㣋㣌㣍㣎㣏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㣐㣑㣒㣓㣔㣕㣖㣗㣘㣙㣚㣛㣜㣝㣞㣟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㣠㣡㣢㣣㣤㣥㣦㣧㣨㣩㣪㣫㣬㣭㣮㣯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㣰㣱㣲㣳㣴㣵㣶㣷㣸㣹㣺㣻㣼㣽㣾㣿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㤀㤁㤂㤃㤄㤅㤆㤇㤈㤉㤊㤋㤌㤍㤎㤏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㤐㤑㤒㤓㤔㤕㤖㤗㤘㤙㤚㤛㤜㤝㤞㤟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㤠㤡㤢㤣㤤㤥㤦㤧㤨㤩㤪㤫㤬㤭㤮㤯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㤰㤱㤲㤳㤴㤵㤶㤷㤸㤹㤺㤻㤼㤽㤾㤿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㥀㥁㥂㥃㥄㥅㥆㥇㥈㥉㥊㥋㥌㥍㥎㥏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㥐㥑㥒㥓㥔㥕㥖㥗㥘㥙㥚㥛㥜㥝㥞㥟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㥠㥡㥢㥣㥤㥥㥦㥧㥨㥩㥪㥫㥬㥭㥮㥯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㥰㥱㥲㥳㥴㥵㥶㥷㥸㥹㥺㥻㥼㥽㥾㥿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㦀㦁㦂㦃㦄㦅㦆㦇㦈㦉㦊㦋㦌㦍㦎㦏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㦐㦑㦒㦓㦔㦕㦖㦗㦘㦙㦚㦛㦜㦝㦞㦟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㦠㦡㦢㦣㦤㦥㦦㦧㦨㦩㦪㦫㦬㦭㦮㦯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㦰㦱㦲㦳㦴㦵㦶㦷㦸㦹㦺㦻㦼㦽㦾㦿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㧀㧁㧂㧃㧄㧅㧆㧇㧈㧉㧊㧋㧌㧍㧎㧏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㧐㧑㧒㧓㧔㧕㧖㧗㧘㧙㧚㧛㧜㧝㧞㧟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㧠㧡㧢㧣㧤㧥㧦㧧㧨㧩㧪㧫㧬㧭㧮㧯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㧰㧱㧲㧳㧴㧵㧶㧷㧸㧹㧺㧻㧼㧽㧾㧿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㨀㨁㨂㨃㨄㨅㨆㨇㨈㨉㨊㨋㨌㨍㨎㨏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㨐㨑㨒㨓㨔㨕㨖㨗㨘㨙㨚㨛㨜㨝㨞㨟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㨠㨡㨢㨣㨤㨥㨦㨧㨨㨩㨪㨫㨬㨭㨮㨯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㨰㨱㨲㨳㨴㨵㨶㨷㨸㨹㨺㨻㨼㨽㨾㨿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㩀㩁㩂㩃㩄㩅㩆㩇㩈㩉㩊㩋㩌㩍㩎㩏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㩐㩑㩒㩓㩔㩕㩖㩗㩘㩙㩚㩛㩜㩝㩞㩟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㩠㩡㩢㩣㩤㩥㩦㩧㩨㩩㩪㩫㩬㩭㩮㩯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㩰㩱㩲㩳㩴㩵㩶㩷㩸㩹㩺㩻㩼㩽㩾㩿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㪀㪁㪂㪃㪄㪅㪆㪇㪈㪉㪊㪋㪌㪍㪎㪏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㪐㪑㪒㪓㪔㪕㪖㪗㪘㪙㪚㪛㪜㪝㪞㪟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㪠㪡㪢㪣㪤㪥㪦㪧㪨㪩㪪㪫㪬㪭㪮㪯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㪰㪱㪲㪳㪴㪵㪶㪷㪸㪹㪺㪻㪼㪽㪾㪿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㫀㫁㫂㫃㫄㫅㫆㫇㫈㫉㫊㫋㫌㫍㫎㫏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㫐㫑㫒㫓㫔㫕㫖㫗㫘㫙㫚㫛㫜㫝㫞㫟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㫠㫡㫢㫣㫤㫥㫦㫧㫨㫩㫪㫫㫬㫭㫮㫯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㫰㫱㫲㫳㫴㫵㫶㫷㫸㫹㫺㫻㫼㫽㫾㫿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㬀㬁㬂㬃㬄㬅㬆㬇㬈㬉㬊㬋㬌㬍㬎㬏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㬐㬑㬒㬓㬔㬕㬖㬗㬘㬙㬚㬛㬜㬝㬞㬟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㬠㬡㬢㬣㬤㬥㬦㬧㬨㬩㬪㬫㬬㬭㬮㬯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㬰㬱㬲㬳㬴㬵㬶㬷㬸㬹㬺㬻㬼㬽㬾㬿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㭀㭁㭂㭃㭄㭅㭆㭇㭈㭉㭊㭋㭌㭍㭎㭏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㭐㭑㭒㭓㭔㭕㭖㭗㭘㭙㭚㭛㭜㭝㭞㭟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㭠㭡㭢㭣㭤㭥㭦㭧㭨㭩㭪㭫㭬㭭㭮㭯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㭰㭱㭲㭳㭴㭵㭶㭷㭸㭹㭺㭻㭼㭽㭾㭿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㮀㮁㮂㮃㮄㮅㮆㮇㮈㮉㮊㮋㮌㮍㮎㮏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㮐㮑㮒㮓㮔㮕㮖㮗㮘㮙㮚㮛㮜㮝㮞㮟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㮠㮡㮢㮣㮤㮥㮦㮧㮨㮩㮪㮫㮬㮭㮮㮯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㮰㮱㮲㮳㮴㮵㮶㮷㮸㮹㮺㮻㮼㮽㮾㮿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㯀㯁㯂㯃㯄㯅㯆㯇㯈㯉㯊㯋㯌㯍㯎㯏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㯐㯑㯒㯓㯔㯕㯖㯗㯘㯙㯚㯛㯜㯝㯞㯟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㯠㯡㯢㯣㯤㯥㯦㯧㯨㯩㯪㯫㯬㯭㯮㯯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㯰㯱㯲㯳㯴㯵㯶㯷㯸㯹㯺㯻㯼㯽㯾㯿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㰀㰁㰂㰃㰄㰅㰆㰇㰈㰉㰊㰋㰌㰍㰎㰏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㰐㰑㰒㰓㰔㰕㰖㰗㰘㰙㰚㰛㰜㰝㰞㰟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㰠㰡㰢㰣㰤㰥㰦㰧㰨㰩㰪㰫㰬㰭㰮㰯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㰰㰱㰲㰳㰴㰵㰶㰷㰸㰹㰺㰻㰼㰽㰾㰿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㱀㱁㱂㱃㱄㱅㱆㱇㱈㱉㱊㱋㱌㱍㱎㱏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㱐㱑㱒㱓㱔㱕㱖㱗㱘㱙㱚㱛㱜㱝㱞㱟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㱠㱡㱢㱣㱤㱥㱦㱧㱨㱩㱪㱫㱬㱭㱮㱯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㱰㱱㱲㱳㱴㱵㱶㱷㱸㱹㱺㱻㱼㱽㱾㱿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㲀㲁㲂㲃㲄㲅㲆㲇㲈㲉㲊㲋㲌㲍㲎㲏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㲐㲑㲒㲓㲔㲕㲖㲗㲘㲙㲚㲛㲜㲝㲞㲟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㲠㲡㲢㲣㲤㲥㲦㲧㲨㲩㲪㲫㲬㲭㲮㲯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㲰㲱㲲㲳㲴㲵㲶㲷㲸㲹㲺㲻㲼㲽㲾㲿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㳀㳁㳂㳃㳄㳅㳆㳇㳈㳉㳊㳋㳌㳍㳎㳏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㳐㳑㳒㳓㳔㳕㳖㳗㳘㳙㳚㳛㳜㳝㳞㳟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㳠㳡㳢㳣㳤㳥㳦㳧㳨㳩㳪㳫㳬㳭㳮㳯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㳰㳱㳲㳳㳴㳵㳶㳷㳸㳹㳺㳻㳼㳽㳾㳿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㴀㴁㴂㴃㴄㴅㴆㴇㴈㴉㴊㴋㴌㴍㴎㴏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㴐㴑㴒㴓㴔㴕㴖㴗㴘㴙㴚㴛㴜㴝㴞㴟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㴠㴡㴢㴣㴤㴥㴦㴧㴨㴩㴪㴫㴬㴭㴮㴯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㴰㴱㴲㴳㴴㴵㴶㴷㴸㴹㴺㴻㴼㴽㴾㴿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㵀㵁㵂㵃㵄㵅㵆㵇㵈㵉㵊㵋㵌㵍㵎㵏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㵐㵑㵒㵓㵔㵕㵖㵗㵘㵙㵚㵛㵜㵝㵞㵟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㵠㵡㵢㵣㵤㵥㵦㵧㵨㵩㵪㵫㵬㵭㵮㵯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㵰㵱㵲㵳㵴㵵㵶㵷㵸㵹㵺㵻㵼㵽㵾㵿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㶀㶁㶂㶃㶄㶅㶆㶇㶈㶉㶊㶋㶌㶍㶎㶏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㶐㶑㶒㶓㶔㶕㶖㶗㶘㶙㶚㶛㶜㶝㶞㶟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㶠㶡㶢㶣㶤㶥㶦㶧㶨㶩㶪㶫㶬㶭㶮㶯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㶰㶱㶲㶳㶴㶵㶶㶷㶸㶹㶺㶻㶼㶽㶾㶿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㷀㷁㷂㷃㷄㷅㷆㷇㷈㷉㷊㷋㷌㷍㷎㷏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㷐㷑㷒㷓㷔㷕㷖㷗㷘㷙㷚㷛㷜㷝㷞㷟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㷠㷡㷢㷣㷤㷥㷦㷧㷨㷩㷪㷫㷬㷭㷮㷯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㷰㷱㷲㷳㷴㷵㷶㷷㷸㷹㷺㷻㷼㷽㷾㷿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㸀㸁㸂㸃㸄㸅㸆㸇㸈㸉㸊㸋㸌㸍㸎㸏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㸐㸑㸒㸓㸔㸕㸖㸗㸘㸙㸚㸛㸜㸝㸞㸟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㸠㸡㸢㸣㸤㸥㸦㸧㸨㸩㸪㸫㸬㸭㸮㸯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㸰㸱㸲㸳㸴㸵㸶㸷㸸㸹㸺㸻㸼㸽㸾㸿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㹀㹁㹂㹃㹄㹅㹆㹇㹈㹉㹊㹋㹌㹍㹎㹏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㹐㹑㹒㹓㹔㹕㹖㹗㹘㹙㹚㹛㹜㹝㹞㹟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㹠㹡㹢㹣㹤㹥㹦㹧㹨㹩㹪㹫㹬㹭㹮㹯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㹰㹱㹲㹳㹴㹵㹶㹷㹸㹹㹺㹻㹼㹽㹾㹿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㺀㺁㺂㺃㺄㺅㺆㺇㺈㺉㺊㺋㺌㺍㺎㺏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㺐㺑㺒㺓㺔㺕㺖㺗㺘㺙㺚㺛㺜㺝㺞㺟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㺠㺡㺢㺣㺤㺥㺦㺧㺨㺩㺪㺫㺬㺭㺮㺯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㺰㺱㺲㺳㺴㺵㺶㺷㺸㺹㺺㺻㺼㺽㺾㺿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㻀㻁㻂㻃㻄㻅㻆㻇㻈㻉㻊㻋㻌㻍㻎㻏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㻐㻑㻒㻓㻔㻕㻖㻗㻘㻙㻚㻛㻜㻝㻞㻟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㻠㻡㻢㻣㻤㻥㻦㻧㻨㻩㻪㻫㻬㻭㻮㻯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㻰㻱㻲㻳㻴㻵㻶㻷㻸㻹㻺㻻㻼㻽㻾㻿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㼀㼁㼂㼃㼄㼅㼆㼇㼈㼉㼊㼋㼌㼍㼎㼏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㼐㼑㼒㼓㼔㼕㼖㼗㼘㼙㼚㼛㼜㼝㼞㼟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㼠㼡㼢㼣㼤㼥㼦㼧㼨㼩㼪㼫㼬㼭㼮㼯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㼰㼱㼲㼳㼴㼵㼶㼷㼸㼹㼺㼻㼼㼽㼾㼿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㽀㽁㽂㽃㽄㽅㽆㽇㽈㽉㽊㽋㽌㽍㽎㽏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㽐㽑㽒㽓㽔㽕㽖㽗㽘㽙㽚㽛㽜㽝㽞㽟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㽠㽡㽢㽣㽤㽥㽦㽧㽨㽩㽪㽫㽬㽭㽮㽯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㽰㽱㽲㽳㽴㽵㽶㽷㽸㽹㽺㽻㽼㽽㽾㽿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㾀㾁㾂㾃㾄㾅㾆㾇㾈㾉㾊㾋㾌㾍㾎㾏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㾐㾑㾒㾓㾔㾕㾖㾗㾘㾙㾚㾛㾜㾝㾞㾟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㾠㾡㾢㾣㾤㾥㾦㾧㾨㾩㾪㾫㾬㾭㾮㾯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㾰㾱㾲㾳㾴㾵㾶㾷㾸㾹㾺㾻㾼㾽㾾㾿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㿀㿁㿂㿃㿄㿅㿆㿇㿈㿉㿊㿋㿌㿍㿎㿏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㿐㿑㿒㿓㿔㿕㿖㿗㿘㿙㿚㿛㿜㿝㿞㿟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㿠㿡㿢㿣㿤㿥㿦㿧㿨㿩㿪㿫㿬㿭㿮㿯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '㿰㿱㿲㿳㿴㿵㿶㿷㿸㿹㿺㿻㿼㿽㿾㿿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSection4 method
	 *
	 * Testing characters 4000 - 4fff
	 *
	 * @return void
	 */
	public function testSection4() {
		$string = '䀀䀁䀂䀃䀄䀅䀆䀇䀈䀉䀊䀋䀌䀍䀎䀏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䀐䀑䀒䀓䀔䀕䀖䀗䀘䀙䀚䀛䀜䀝䀞䀟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䀠䀡䀢䀣䀤䀥䀦䀧䀨䀩䀪䀫䀬䀭䀮䀯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䀰䀱䀲䀳䀴䀵䀶䀷䀸䀹䀺䀻䀼䀽䀾䀿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䁀䁁䁂䁃䁄䁅䁆䁇䁈䁉䁊䁋䁌䁍䁎䁏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䁐䁑䁒䁓䁔䁕䁖䁗䁘䁙䁚䁛䁜䁝䁞䁟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䁠䁡䁢䁣䁤䁥䁦䁧䁨䁩䁪䁫䁬䁭䁮䁯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䁰䁱䁲䁳䁴䁵䁶䁷䁸䁹䁺䁻䁼䁽䁾䁿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䂀䂁䂂䂃䂄䂅䂆䂇䂈䂉䂊䂋䂌䂍䂎䂏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䂐䂑䂒䂓䂔䂕䂖䂗䂘䂙䂚䂛䂜䂝䂞䂟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䂠䂡䂢䂣䂤䂥䂦䂧䂨䂩䂪䂫䂬䂭䂮䂯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䂰䂱䂲䂳䂴䂵䂶䂷䂸䂹䂺䂻䂼䂽䂾䂿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䃀䃁䃂䃃䃄䃅䃆䃇䃈䃉䃊䃋䃌䃍䃎䃏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䃐䃑䃒䃓䃔䃕䃖䃗䃘䃙䃚䃛䃜䃝䃞䃟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䃠䃡䃢䃣䃤䃥䃦䃧䃨䃩䃪䃫䃬䃭䃮䃯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䃰䃱䃲䃳䃴䃵䃶䃷䃸䃹䃺䃻䃼䃽䃾䃿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䄀䄁䄂䄃䄄䄅䄆䄇䄈䄉䄊䄋䄌䄍䄎䄏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䄐䄑䄒䄓䄔䄕䄖䄗䄘䄙䄚䄛䄜䄝䄞䄟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䄠䄡䄢䄣䄤䄥䄦䄧䄨䄩䄪䄫䄬䄭䄮䄯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䄰䄱䄲䄳䄴䄵䄶䄷䄸䄹䄺䄻䄼䄽䄾䄿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䅀䅁䅂䅃䅄䅅䅆䅇䅈䅉䅊䅋䅌䅍䅎䅏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䅐䅑䅒䅓䅔䅕䅖䅗䅘䅙䅚䅛䅜䅝䅞䅟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䅠䅡䅢䅣䅤䅥䅦䅧䅨䅩䅪䅫䅬䅭䅮䅯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䅰䅱䅲䅳䅴䅵䅶䅷䅸䅹䅺䅻䅼䅽䅾䅿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䆀䆁䆂䆃䆄䆅䆆䆇䆈䆉䆊䆋䆌䆍䆎䆏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䆐䆑䆒䆓䆔䆕䆖䆗䆘䆙䆚䆛䆜䆝䆞䆟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䆠䆡䆢䆣䆤䆥䆦䆧䆨䆩䆪䆫䆬䆭䆮䆯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䆰䆱䆲䆳䆴䆵䆶䆷䆸䆹䆺䆻䆼䆽䆾䆿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䇀䇁䇂䇃䇄䇅䇆䇇䇈䇉䇊䇋䇌䇍䇎䇏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䇐䇑䇒䇓䇔䇕䇖䇗䇘䇙䇚䇛䇜䇝䇞䇟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䇠䇡䇢䇣䇤䇥䇦䇧䇨䇩䇪䇫䇬䇭䇮䇯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䇰䇱䇲䇳䇴䇵䇶䇷䇸䇹䇺䇻䇼䇽䇾䇿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䈀䈁䈂䈃䈄䈅䈆䈇䈈䈉䈊䈋䈌䈍䈎䈏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䈐䈑䈒䈓䈔䈕䈖䈗䈘䈙䈚䈛䈜䈝䈞䈟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䈠䈡䈢䈣䈤䈥䈦䈧䈨䈩䈪䈫䈬䈭䈮䈯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䈰䈱䈲䈳䈴䈵䈶䈷䈸䈹䈺䈻䈼䈽䈾䈿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䉀䉁䉂䉃䉄䉅䉆䉇䉈䉉䉊䉋䉌䉍䉎䉏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䉐䉑䉒䉓䉔䉕䉖䉗䉘䉙䉚䉛䉜䉝䉞䉟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䉠䉡䉢䉣䉤䉥䉦䉧䉨䉩䉪䉫䉬䉭䉮䉯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䉰䉱䉲䉳䉴䉵䉶䉷䉸䉹䉺䉻䉼䉽䉾䉿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䊀䊁䊂䊃䊄䊅䊆䊇䊈䊉䊊䊋䊌䊍䊎䊏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䊐䊑䊒䊓䊔䊕䊖䊗䊘䊙䊚䊛䊜䊝䊞䊟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䊠䊡䊢䊣䊤䊥䊦䊧䊨䊩䊪䊫䊬䊭䊮䊯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䊰䊱䊲䊳䊴䊵䊶䊷䊸䊹䊺䊻䊼䊽䊾䊿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䋀䋁䋂䋃䋄䋅䋆䋇䋈䋉䋊䋋䋌䋍䋎䋏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䋐䋑䋒䋓䋔䋕䋖䋗䋘䋙䋚䋛䋜䋝䋞䋟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䋠䋡䋢䋣䋤䋥䋦䋧䋨䋩䋪䋫䋬䋭䋮䋯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䋰䋱䋲䋳䋴䋵䋶䋷䋸䋹䋺䋻䋼䋽䋾䋿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䌀䌁䌂䌃䌄䌅䌆䌇䌈䌉䌊䌋䌌䌍䌎䌏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䌐䌑䌒䌓䌔䌕䌖䌗䌘䌙䌚䌛䌜䌝䌞䌟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䌠䌡䌢䌣䌤䌥䌦䌧䌨䌩䌪䌫䌬䌭䌮䌯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䌰䌱䌲䌳䌴䌵䌶䌷䌸䌹䌺䌻䌼䌽䌾䌿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䍀䍁䍂䍃䍄䍅䍆䍇䍈䍉䍊䍋䍌䍍䍎䍏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䍐䍑䍒䍓䍔䍕䍖䍗䍘䍙䍚䍛䍜䍝䍞䍟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䍠䍡䍢䍣䍤䍥䍦䍧䍨䍩䍪䍫䍬䍭䍮䍯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䍰䍱䍲䍳䍴䍵䍶䍷䍸䍹䍺䍻䍼䍽䍾䍿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䎀䎁䎂䎃䎄䎅䎆䎇䎈䎉䎊䎋䎌䎍䎎䎏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䎐䎑䎒䎓䎔䎕䎖䎗䎘䎙䎚䎛䎜䎝䎞䎟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䎠䎡䎢䎣䎤䎥䎦䎧䎨䎩䎪䎫䎬䎭䎮䎯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䎰䎱䎲䎳䎴䎵䎶䎷䎸䎹䎺䎻䎼䎽䎾䎿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䏀䏁䏂䏃䏄䏅䏆䏇䏈䏉䏊䏋䏌䏍䏎䏏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䏐䏑䏒䏓䏔䏕䏖䏗䏘䏙䏚䏛䏜䏝䏞䏟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䏠䏡䏢䏣䏤䏥䏦䏧䏨䏩䏪䏫䏬䏭䏮䏯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䏰䏱䏲䏳䏴䏵䏶䏷䏸䏹䏺䏻䏼䏽䏾䏿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䐀䐁䐂䐃䐄䐅䐆䐇䐈䐉䐊䐋䐌䐍䐎䐏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䐐䐑䐒䐓䐔䐕䐖䐗䐘䐙䐚䐛䐜䐝䐞䐟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䐠䐡䐢䐣䐤䐥䐦䐧䐨䐩䐪䐫䐬䐭䐮䐯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䐰䐱䐲䐳䐴䐵䐶䐷䐸䐹䐺䐻䐼䐽䐾䐿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䑀䑁䑂䑃䑄䑅䑆䑇䑈䑉䑊䑋䑌䑍䑎䑏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䑐䑑䑒䑓䑔䑕䑖䑗䑘䑙䑚䑛䑜䑝䑞䑟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䑠䑡䑢䑣䑤䑥䑦䑧䑨䑩䑪䑫䑬䑭䑮䑯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䑰䑱䑲䑳䑴䑵䑶䑷䑸䑹䑺䑻䑼䑽䑾䑿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䒀䒁䒂䒃䒄䒅䒆䒇䒈䒉䒊䒋䒌䒍䒎䒏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䒐䒑䒒䒓䒔䒕䒖䒗䒘䒙䒚䒛䒜䒝䒞䒟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䒠䒡䒢䒣䒤䒥䒦䒧䒨䒩䒪䒫䒬䒭䒮䒯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䒰䒱䒲䒳䒴䒵䒶䒷䒸䒹䒺䒻䒼䒽䒾䒿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䓀䓁䓂䓃䓄䓅䓆䓇䓈䓉䓊䓋䓌䓍䓎䓏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䓐䓑䓒䓓䓔䓕䓖䓗䓘䓙䓚䓛䓜䓝䓞䓟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䓠䓡䓢䓣䓤䓥䓦䓧䓨䓩䓪䓫䓬䓭䓮䓯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䓰䓱䓲䓳䓴䓵䓶䓷䓸䓹䓺䓻䓼䓽䓾䓿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䔀䔁䔂䔃䔄䔅䔆䔇䔈䔉䔊䔋䔌䔍䔎䔏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䔐䔑䔒䔓䔔䔕䔖䔗䔘䔙䔚䔛䔜䔝䔞䔟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䔠䔡䔢䔣䔤䔥䔦䔧䔨䔩䔪䔫䔬䔭䔮䔯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䔰䔱䔲䔳䔴䔵䔶䔷䔸䔹䔺䔻䔼䔽䔾䔿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䕀䕁䕂䕃䕄䕅䕆䕇䕈䕉䕊䕋䕌䕍䕎䕏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䕐䕑䕒䕓䕔䕕䕖䕗䕘䕙䕚䕛䕜䕝䕞䕟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䕠䕡䕢䕣䕤䕥䕦䕧䕨䕩䕪䕫䕬䕭䕮䕯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䕰䕱䕲䕳䕴䕵䕶䕷䕸䕹䕺䕻䕼䕽䕾䕿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䖀䖁䖂䖃䖄䖅䖆䖇䖈䖉䖊䖋䖌䖍䖎䖏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䖐䖑䖒䖓䖔䖕䖖䖗䖘䖙䖚䖛䖜䖝䖞䖟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䖠䖡䖢䖣䖤䖥䖦䖧䖨䖩䖪䖫䖬䖭䖮䖯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䖰䖱䖲䖳䖴䖵䖶䖷䖸䖹䖺䖻䖼䖽䖾䖿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䗀䗁䗂䗃䗄䗅䗆䗇䗈䗉䗊䗋䗌䗍䗎䗏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䗐䗑䗒䗓䗔䗕䗖䗗䗘䗙䗚䗛䗜䗝䗞䗟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䗠䗡䗢䗣䗤䗥䗦䗧䗨䗩䗪䗫䗬䗭䗮䗯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䗰䗱䗲䗳䗴䗵䗶䗷䗸䗹䗺䗻䗼䗽䗾䗿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䘀䘁䘂䘃䘄䘅䘆䘇䘈䘉䘊䘋䘌䘍䘎䘏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䘐䘑䘒䘓䘔䘕䘖䘗䘘䘙䘚䘛䘜䘝䘞䘟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䘠䘡䘢䘣䘤䘥䘦䘧䘨䘩䘪䘫䘬䘭䘮䘯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䘰䘱䘲䘳䘴䘵䘶䘷䘸䘹䘺䘻䘼䘽䘾䘿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䙀䙁䙂䙃䙄䙅䙆䙇䙈䙉䙊䙋䙌䙍䙎䙏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䙐䙑䙒䙓䙔䙕䙖䙗䙘䙙䙚䙛䙜䙝䙞䙟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䙠䙡䙢䙣䙤䙥䙦䙧䙨䙩䙪䙫䙬䙭䙮䙯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䙰䙱䙲䙳䙴䙵䙶䙷䙸䙹䙺䙻䙼䙽䙾䙿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䚀䚁䚂䚃䚄䚅䚆䚇䚈䚉䚊䚋䚌䚍䚎䚏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䚐䚑䚒䚓䚔䚕䚖䚗䚘䚙䚚䚛䚜䚝䚞䚟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䚠䚡䚢䚣䚤䚥䚦䚧䚨䚩䚪䚫䚬䚭䚮䚯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䚰䚱䚲䚳䚴䚵䚶䚷䚸䚹䚺䚻䚼䚽䚾䚿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䛀䛁䛂䛃䛄䛅䛆䛇䛈䛉䛊䛋䛌䛍䛎䛏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䛐䛑䛒䛓䛔䛕䛖䛗䛘䛙䛚䛛䛜䛝䛞䛟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䛠䛡䛢䛣䛤䛥䛦䛧䛨䛩䛪䛫䛬䛭䛮䛯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䛰䛱䛲䛳䛴䛵䛶䛷䛸䛹䛺䛻䛼䛽䛾䛿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䜀䜁䜂䜃䜄䜅䜆䜇䜈䜉䜊䜋䜌䜍䜎䜏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䜐䜑䜒䜓䜔䜕䜖䜗䜘䜙䜚䜛䜜䜝䜞䜟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䜠䜡䜢䜣䜤䜥䜦䜧䜨䜩䜪䜫䜬䜭䜮䜯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䜰䜱䜲䜳䜴䜵䜶䜷䜸䜹䜺䜻䜼䜽䜾䜿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䝀䝁䝂䝃䝄䝅䝆䝇䝈䝉䝊䝋䝌䝍䝎䝏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䝐䝑䝒䝓䝔䝕䝖䝗䝘䝙䝚䝛䝜䝝䝞䝟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䝠䝡䝢䝣䝤䝥䝦䝧䝨䝩䝪䝫䝬䝭䝮䝯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䝰䝱䝲䝳䝴䝵䝶䝷䝸䝹䝺䝻䝼䝽䝾䝿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䞀䞁䞂䞃䞄䞅䞆䞇䞈䞉䞊䞋䞌䞍䞎䞏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䞐䞑䞒䞓䞔䞕䞖䞗䞘䞙䞚䞛䞜䞝䞞䞟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䞠䞡䞢䞣䞤䞥䞦䞧䞨䞩䞪䞫䞬䞭䞮䞯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䞰䞱䞲䞳䞴䞵䞶䞷䞸䞹䞺䞻䞼䞽䞾䞿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䟀䟁䟂䟃䟄䟅䟆䟇䟈䟉䟊䟋䟌䟍䟎䟏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䟐䟑䟒䟓䟔䟕䟖䟗䟘䟙䟚䟛䟜䟝䟞䟟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䟠䟡䟢䟣䟤䟥䟦䟧䟨䟩䟪䟫䟬䟭䟮䟯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䟰䟱䟲䟳䟴䟵䟶䟷䟸䟹䟺䟻䟼䟽䟾䟿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䠀䠁䠂䠃䠄䠅䠆䠇䠈䠉䠊䠋䠌䠍䠎䠏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䠐䠑䠒䠓䠔䠕䠖䠗䠘䠙䠚䠛䠜䠝䠞䠟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䠠䠡䠢䠣䠤䠥䠦䠧䠨䠩䠪䠫䠬䠭䠮䠯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䠰䠱䠲䠳䠴䠵䠶䠷䠸䠹䠺䠻䠼䠽䠾䠿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䡀䡁䡂䡃䡄䡅䡆䡇䡈䡉䡊䡋䡌䡍䡎䡏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䡐䡑䡒䡓䡔䡕䡖䡗䡘䡙䡚䡛䡜䡝䡞䡟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䡠䡡䡢䡣䡤䡥䡦䡧䡨䡩䡪䡫䡬䡭䡮䡯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䡰䡱䡲䡳䡴䡵䡶䡷䡸䡹䡺䡻䡼䡽䡾䡿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䢀䢁䢂䢃䢄䢅䢆䢇䢈䢉䢊䢋䢌䢍䢎䢏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䢐䢑䢒䢓䢔䢕䢖䢗䢘䢙䢚䢛䢜䢝䢞䢟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䢠䢡䢢䢣䢤䢥䢦䢧䢨䢩䢪䢫䢬䢭䢮䢯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䢰䢱䢲䢳䢴䢵䢶䢷䢸䢹䢺䢻䢼䢽䢾䢿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䣀䣁䣂䣃䣄䣅䣆䣇䣈䣉䣊䣋䣌䣍䣎䣏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䣐䣑䣒䣓䣔䣕䣖䣗䣘䣙䣚䣛䣜䣝䣞䣟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䣠䣡䣢䣣䣤䣥䣦䣧䣨䣩䣪䣫䣬䣭䣮䣯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䣰䣱䣲䣳䣴䣵䣶䣷䣸䣹䣺䣻䣼䣽䣾䣿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䤀䤁䤂䤃䤄䤅䤆䤇䤈䤉䤊䤋䤌䤍䤎䤏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䤐䤑䤒䤓䤔䤕䤖䤗䤘䤙䤚䤛䤜䤝䤞䤟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䤠䤡䤢䤣䤤䤥䤦䤧䤨䤩䤪䤫䤬䤭䤮䤯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䤰䤱䤲䤳䤴䤵䤶䤷䤸䤹䤺䤻䤼䤽䤾䤿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䥀䥁䥂䥃䥄䥅䥆䥇䥈䥉䥊䥋䥌䥍䥎䥏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䥐䥑䥒䥓䥔䥕䥖䥗䥘䥙䥚䥛䥜䥝䥞䥟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䥠䥡䥢䥣䥤䥥䥦䥧䥨䥩䥪䥫䥬䥭䥮䥯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䥰䥱䥲䥳䥴䥵䥶䥷䥸䥹䥺䥻䥼䥽䥾䥿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䦀䦁䦂䦃䦄䦅䦆䦇䦈䦉䦊䦋䦌䦍䦎䦏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䦐䦑䦒䦓䦔䦕䦖䦗䦘䦙䦚䦛䦜䦝䦞䦟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䦠䦡䦢䦣䦤䦥䦦䦧䦨䦩䦪䦫䦬䦭䦮䦯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䦰䦱䦲䦳䦴䦵䦶䦷䦸䦹䦺䦻䦼䦽䦾䦿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䧀䧁䧂䧃䧄䧅䧆䧇䧈䧉䧊䧋䧌䧍䧎䧏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䧐䧑䧒䧓䧔䧕䧖䧗䧘䧙䧚䧛䧜䧝䧞䧟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䧠䧡䧢䧣䧤䧥䧦䧧䧨䧩䧪䧫䧬䧭䧮䧯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䧰䧱䧲䧳䧴䧵䧶䧷䧸䧹䧺䧻䧼䧽䧾䧿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䨀䨁䨂䨃䨄䨅䨆䨇䨈䨉䨊䨋䨌䨍䨎䨏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䨐䨑䨒䨓䨔䨕䨖䨗䨘䨙䨚䨛䨜䨝䨞䨟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䨠䨡䨢䨣䨤䨥䨦䨧䨨䨩䨪䨫䨬䨭䨮䨯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䨰䨱䨲䨳䨴䨵䨶䨷䨸䨹䨺䨻䨼䨽䨾䨿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䩀䩁䩂䩃䩄䩅䩆䩇䩈䩉䩊䩋䩌䩍䩎䩏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䩐䩑䩒䩓䩔䩕䩖䩗䩘䩙䩚䩛䩜䩝䩞䩟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䩠䩡䩢䩣䩤䩥䩦䩧䩨䩩䩪䩫䩬䩭䩮䩯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䩰䩱䩲䩳䩴䩵䩶䩷䩸䩹䩺䩻䩼䩽䩾䩿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䪀䪁䪂䪃䪄䪅䪆䪇䪈䪉䪊䪋䪌䪍䪎䪏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䪐䪑䪒䪓䪔䪕䪖䪗䪘䪙䪚䪛䪜䪝䪞䪟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䪠䪡䪢䪣䪤䪥䪦䪧䪨䪩䪪䪫䪬䪭䪮䪯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䪰䪱䪲䪳䪴䪵䪶䪷䪸䪹䪺䪻䪼䪽䪾䪿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䫀䫁䫂䫃䫄䫅䫆䫇䫈䫉䫊䫋䫌䫍䫎䫏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䫐䫑䫒䫓䫔䫕䫖䫗䫘䫙䫚䫛䫜䫝䫞䫟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䫠䫡䫢䫣䫤䫥䫦䫧䫨䫩䫪䫫䫬䫭䫮䫯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䫰䫱䫲䫳䫴䫵䫶䫷䫸䫹䫺䫻䫼䫽䫾䫿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䬀䬁䬂䬃䬄䬅䬆䬇䬈䬉䬊䬋䬌䬍䬎䬏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䬐䬑䬒䬓䬔䬕䬖䬗䬘䬙䬚䬛䬜䬝䬞䬟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䬠䬡䬢䬣䬤䬥䬦䬧䬨䬩䬪䬫䬬䬭䬮䬯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䬰䬱䬲䬳䬴䬵䬶䬷䬸䬹䬺䬻䬼䬽䬾䬿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䭀䭁䭂䭃䭄䭅䭆䭇䭈䭉䭊䭋䭌䭍䭎䭏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䭐䭑䭒䭓䭔䭕䭖䭗䭘䭙䭚䭛䭜䭝䭞䭟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䭠䭡䭢䭣䭤䭥䭦䭧䭨䭩䭪䭫䭬䭭䭮䭯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䭰䭱䭲䭳䭴䭵䭶䭷䭸䭹䭺䭻䭼䭽䭾䭿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䮀䮁䮂䮃䮄䮅䮆䮇䮈䮉䮊䮋䮌䮍䮎䮏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䮐䮑䮒䮓䮔䮕䮖䮗䮘䮙䮚䮛䮜䮝䮞䮟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䮠䮡䮢䮣䮤䮥䮦䮧䮨䮩䮪䮫䮬䮭䮮䮯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䮰䮱䮲䮳䮴䮵䮶䮷䮸䮹䮺䮻䮼䮽䮾䮿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䯀䯁䯂䯃䯄䯅䯆䯇䯈䯉䯊䯋䯌䯍䯎䯏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䯐䯑䯒䯓䯔䯕䯖䯗䯘䯙䯚䯛䯜䯝䯞䯟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䯠䯡䯢䯣䯤䯥䯦䯧䯨䯩䯪䯫䯬䯭䯮䯯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䯰䯱䯲䯳䯴䯵䯶䯷䯸䯹䯺䯻䯼䯽䯾䯿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䰀䰁䰂䰃䰄䰅䰆䰇䰈䰉䰊䰋䰌䰍䰎䰏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䰐䰑䰒䰓䰔䰕䰖䰗䰘䰙䰚䰛䰜䰝䰞䰟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䰠䰡䰢䰣䰤䰥䰦䰧䰨䰩䰪䰫䰬䰭䰮䰯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䰰䰱䰲䰳䰴䰵䰶䰷䰸䰹䰺䰻䰼䰽䰾䰿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䱀䱁䱂䱃䱄䱅䱆䱇䱈䱉䱊䱋䱌䱍䱎䱏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䱐䱑䱒䱓䱔䱕䱖䱗䱘䱙䱚䱛䱜䱝䱞䱟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䱠䱡䱢䱣䱤䱥䱦䱧䱨䱩䱪䱫䱬䱭䱮䱯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䱰䱱䱲䱳䱴䱵䱶䱷䱸䱹䱺䱻䱼䱽䱾䱿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䲀䲁䲂䲃䲄䲅䲆䲇䲈䲉䲊䲋䲌䲍䲎䲏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䲐䲑䲒䲓䲔䲕䲖䲗䲘䲙䲚䲛䲜䲝䲞䲟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䲠䲡䲢䲣䲤䲥䲦䲧䲨䲩䲪䲫䲬䲭䲮䲯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䲰䲱䲲䲳䲴䲵䲶䲷䲸䲹䲺䲻䲼䲽䲾䲿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䳀䳁䳂䳃䳄䳅䳆䳇䳈䳉䳊䳋䳌䳍䳎䳏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䳐䳑䳒䳓䳔䳕䳖䳗䳘䳙䳚䳛䳜䳝䳞䳟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䳠䳡䳢䳣䳤䳥䳦䳧䳨䳩䳪䳫䳬䳭䳮䳯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䳰䳱䳲䳳䳴䳵䳶䳷䳸䳹䳺䳻䳼䳽䳾䳿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䴀䴁䴂䴃䴄䴅䴆䴇䴈䴉䴊䴋䴌䴍䴎䴏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䴐䴑䴒䴓䴔䴕䴖䴗䴘䴙䴚䴛䴜䴝䴞䴟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䴠䴡䴢䴣䴤䴥䴦䴧䴨䴩䴪䴫䴬䴭䴮䴯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䴰䴱䴲䴳䴴䴵䴶䴷䴸䴹䴺䴻䴼䴽䴾䴿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䵀䵁䵂䵃䵄䵅䵆䵇䵈䵉䵊䵋䵌䵍䵎䵏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䵐䵑䵒䵓䵔䵕䵖䵗䵘䵙䵚䵛䵜䵝䵞䵟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䵠䵡䵢䵣䵤䵥䵦䵧䵨䵩䵪䵫䵬䵭䵮䵯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䵰䵱䵲䵳䵴䵵䵶䵷䵸䵹䵺䵻䵼䵽䵾䵿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䶀䶁䶂䶃䶄䶅䶆䶇䶈䶉䶊䶋䶌䶍䶎䶏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䶐䶑䶒䶓䶔䶕䶖䶗䶘䶙䶚䶛䶜䶝䶞䶟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䶠䶡䶢䶣䶤䶥䶦䶧䶨䶩䶪䶫䶬䶭䶮䶯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䶰䶱䶲䶳䶴䶵䶶䶷䶸䶹䶺䶻䶼䶽䶾䶿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䷀䷁䷂䷃䷄䷅䷆䷇䷈䷉䷊䷋䷌䷍䷎䷏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䷐䷑䷒䷓䷔䷕䷖䷗䷘䷙䷚䷛䷜䷝䷞䷟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䷠䷡䷢䷣䷤䷥䷦䷧䷨䷩䷪䷫䷬䷭䷮䷯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '䷰䷱䷲䷳䷴䷵䷶䷷䷸䷹䷺䷻䷼䷽䷾䷿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '一丁丂七丄丅丆万丈三上下丌不与丏';
		$expects = '一丁丂七丄丅丆万丈三上下丌不与丏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '丐丑丒专且丕世丗丘丙业丛东丝丞丟';
		$expects = '丐丑丒专且丕世丗丘丙业丛东丝丞丟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '丠両丢丣两严並丧丨丩个丫丬中丮丯';
		$expects = '丠両丢丣两严並丧丨丩个丫丬中丮丯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '丰丱串丳临丵丶丷丸丹为主丼丽举丿';
		$expects = '丰丱串丳临丵丶丷丸丹为主丼丽举丿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '乀乁乂乃乄久乆乇么义乊之乌乍乎乏';
		$expects = '乀乁乂乃乄久乆乇么义乊之乌乍乎乏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '乐乑乒乓乔乕乖乗乘乙乚乛乜九乞也';
		$expects = '乐乑乒乓乔乕乖乗乘乙乚乛乜九乞也';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '习乡乢乣乤乥书乧乨乩乪乫乬乭乮乯';
		$expects = '习乡乢乣乤乥书乧乨乩乪乫乬乭乮乯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '买乱乲乳乴乵乶乷乸乹乺乻乼乽乾乿';
		$expects = '买乱乲乳乴乵乶乷乸乹乺乻乼乽乾乿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '亀亁亂亃亄亅了亇予争亊事二亍于亏';
		$expects = '亀亁亂亃亄亅了亇予争亊事二亍于亏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '亐云互亓五井亖亗亘亙亚些亜亝亞亟';
		$expects = '亐云互亓五井亖亗亘亙亚些亜亝亞亟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '亠亡亢亣交亥亦产亨亩亪享京亭亮亯';
		$expects = '亠亡亢亣交亥亦产亨亩亪享京亭亮亯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '亰亱亲亳亴亵亶亷亸亹人亻亼亽亾亿';
		$expects = '亰亱亲亳亴亵亶亷亸亹人亻亼亽亾亿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '什仁仂仃仄仅仆仇仈仉今介仌仍从仏';
		$expects = '什仁仂仃仄仅仆仇仈仉今介仌仍从仏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '仐仑仒仓仔仕他仗付仙仚仛仜仝仞仟';
		$expects = '仐仑仒仓仔仕他仗付仙仚仛仜仝仞仟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '仠仡仢代令以仦仧仨仩仪仫们仭仮仯';
		$expects = '仠仡仢代令以仦仧仨仩仪仫们仭仮仯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '仰仱仲仳仴仵件价仸仹仺任仼份仾仿';
		$expects = '仰仱仲仳仴仵件价仸仹仺任仼份仾仿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '伀企伂伃伄伅伆伇伈伉伊伋伌伍伎伏';
		$expects = '伀企伂伃伄伅伆伇伈伉伊伋伌伍伎伏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '伐休伒伓伔伕伖众优伙会伛伜伝伞伟';
		$expects = '伐休伒伓伔伕伖众优伙会伛伜伝伞伟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '传伡伢伣伤伥伦伧伨伩伪伫伬伭伮伯';
		$expects = '传伡伢伣伤伥伦伧伨伩伪伫伬伭伮伯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '估伱伲伳伴伵伶伷伸伹伺伻似伽伾伿';
		$expects = '估伱伲伳伴伵伶伷伸伹伺伻似伽伾伿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '佀佁佂佃佄佅但佇佈佉佊佋佌位低住';
		$expects = '佀佁佂佃佄佅但佇佈佉佊佋佌位低住';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '佐佑佒体佔何佖佗佘余佚佛作佝佞佟';
		$expects = '佐佑佒体佔何佖佗佘余佚佛作佝佞佟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '你佡佢佣佤佥佦佧佨佩佪佫佬佭佮佯';
		$expects = '你佡佢佣佤佥佦佧佨佩佪佫佬佭佮佯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '佰佱佲佳佴併佶佷佸佹佺佻佼佽佾使';
		$expects = '佰佱佲佳佴併佶佷佸佹佺佻佼佽佾使';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '侀侁侂侃侄侅來侇侈侉侊例侌侍侎侏';
		$expects = '侀侁侂侃侄侅來侇侈侉侊例侌侍侎侏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '侐侑侒侓侔侕侖侗侘侙侚供侜依侞侟';
		$expects = '侐侑侒侓侔侕侖侗侘侙侚供侜依侞侟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '侠価侢侣侤侥侦侧侨侩侪侫侬侭侮侯';
		$expects = '侠価侢侣侤侥侦侧侨侩侪侫侬侭侮侯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '侰侱侲侳侴侵侶侷侸侹侺侻侼侽侾便';
		$expects = '侰侱侲侳侴侵侶侷侸侹侺侻侼侽侾便';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '俀俁係促俄俅俆俇俈俉俊俋俌俍俎俏';
		$expects = '俀俁係促俄俅俆俇俈俉俊俋俌俍俎俏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '俐俑俒俓俔俕俖俗俘俙俚俛俜保俞俟';
		$expects = '俐俑俒俓俔俕俖俗俘俙俚俛俜保俞俟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '俠信俢俣俤俥俦俧俨俩俪俫俬俭修俯';
		$expects = '俠信俢俣俤俥俦俧俨俩俪俫俬俭修俯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '俰俱俲俳俴俵俶俷俸俹俺俻俼俽俾俿';
		$expects = '俰俱俲俳俴俵俶俷俸俹俺俻俼俽俾俿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSection5 method
	 *
	 * Testing characters 5000 - 5fff
	 *
	 * @return void
	 */
	public function testSection5() {
		$string = '倀倁倂倃倄倅倆倇倈倉倊個倌倍倎倏';
		$expects = '倀倁倂倃倄倅倆倇倈倉倊個倌倍倎倏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '倐們倒倓倔倕倖倗倘候倚倛倜倝倞借';
		$expects = '倐們倒倓倔倕倖倗倘候倚倛倜倝倞借';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '倠倡倢倣値倥倦倧倨倩倪倫倬倭倮倯';
		$expects = '倠倡倢倣値倥倦倧倨倩倪倫倬倭倮倯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '倰倱倲倳倴倵倶倷倸倹债倻值倽倾倿';
		$expects = '倰倱倲倳倴倵倶倷倸倹债倻值倽倾倿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '偀偁偂偃偄偅偆假偈偉偊偋偌偍偎偏';
		$expects = '偀偁偂偃偄偅偆假偈偉偊偋偌偍偎偏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '偐偑偒偓偔偕偖偗偘偙做偛停偝偞偟';
		$expects = '偐偑偒偓偔偕偖偗偘偙做偛停偝偞偟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '偠偡偢偣偤健偦偧偨偩偪偫偬偭偮偯';
		$expects = '偠偡偢偣偤健偦偧偨偩偪偫偬偭偮偯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '偰偱偲偳側偵偶偷偸偹偺偻偼偽偾偿';
		$expects = '偰偱偲偳側偵偶偷偸偹偺偻偼偽偾偿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '傀傁傂傃傄傅傆傇傈傉傊傋傌傍傎傏';
		$expects = '傀傁傂傃傄傅傆傇傈傉傊傋傌傍傎傏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '傐傑傒傓傔傕傖傗傘備傚傛傜傝傞傟';
		$expects = '傐傑傒傓傔傕傖傗傘備傚傛傜傝傞傟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '傠傡傢傣傤傥傦傧储傩傪傫催傭傮傯';
		$expects = '傠傡傢傣傤傥傦傧储傩傪傫催傭傮傯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '傰傱傲傳傴債傶傷傸傹傺傻傼傽傾傿';
		$expects = '傰傱傲傳傴債傶傷傸傹傺傻傼傽傾傿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '僀僁僂僃僄僅僆僇僈僉僊僋僌働僎像';
		$expects = '僀僁僂僃僄僅僆僇僈僉僊僋僌働僎像';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '僐僑僒僓僔僕僖僗僘僙僚僛僜僝僞僟';
		$expects = '僐僑僒僓僔僕僖僗僘僙僚僛僜僝僞僟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '僠僡僢僣僤僥僦僧僨僩僪僫僬僭僮僯';
		$expects = '僠僡僢僣僤僥僦僧僨僩僪僫僬僭僮僯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '僰僱僲僳僴僵僶僷僸價僺僻僼僽僾僿';
		$expects = '僰僱僲僳僴僵僶僷僸價僺僻僼僽僾僿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '儀儁儂儃億儅儆儇儈儉儊儋儌儍儎儏';
		$expects = '儀儁儂儃億儅儆儇儈儉儊儋儌儍儎儏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '儐儑儒儓儔儕儖儗儘儙儚儛儜儝儞償';
		$expects = '儐儑儒儓儔儕儖儗儘儙儚儛儜儝儞償';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '儠儡儢儣儤儥儦儧儨儩優儫儬儭儮儯';
		$expects = '儠儡儢儣儤儥儦儧儨儩優儫儬儭儮儯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '儰儱儲儳儴儵儶儷儸儹儺儻儼儽儾儿';
		$expects = '儰儱儲儳儴儵儶儷儸儹儺儻儼儽儾儿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '兀允兂元兄充兆兇先光兊克兌免兎兏';
		$expects = '兀允兂元兄充兆兇先光兊克兌免兎兏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '児兑兒兓兔兕兖兗兘兙党兛兜兝兞兟';
		$expects = '児兑兒兓兔兕兖兗兘兙党兛兜兝兞兟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '兠兡兢兣兤入兦內全兩兪八公六兮兯';
		$expects = '兠兡兢兣兤入兦內全兩兪八公六兮兯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '兰共兲关兴兵其具典兹兺养兼兽兾兿';
		$expects = '兰共兲关兴兵其具典兹兺养兼兽兾兿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '冀冁冂冃冄内円冇冈冉冊冋册再冎冏';
		$expects = '冀冁冂冃冄内円冇冈冉冊冋册再冎冏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '冐冑冒冓冔冕冖冗冘写冚军农冝冞冟';
		$expects = '冐冑冒冓冔冕冖冗冘写冚军农冝冞冟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '冠冡冢冣冤冥冦冧冨冩冪冫冬冭冮冯';
		$expects = '冠冡冢冣冤冥冦冧冨冩冪冫冬冭冮冯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '冰冱冲决冴况冶冷冸冹冺冻冼冽冾冿';
		$expects = '冰冱冲决冴况冶冷冸冹冺冻冼冽冾冿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '净凁凂凃凄凅准凇凈凉凊凋凌凍凎减';
		$expects = '净凁凂凃凄凅准凇凈凉凊凋凌凍凎减';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '凐凑凒凓凔凕凖凗凘凙凚凛凜凝凞凟';
		$expects = '凐凑凒凓凔凕凖凗凘凙凚凛凜凝凞凟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '几凡凢凣凤凥処凧凨凩凪凫凬凭凮凯';
		$expects = '几凡凢凣凤凥処凧凨凩凪凫凬凭凮凯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '凰凱凲凳凴凵凶凷凸凹出击凼函凾凿';
		$expects = '凰凱凲凳凴凵凶凷凸凹出击凼函凾凿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '刀刁刂刃刄刅分切刈刉刊刋刌刍刎刏';
		$expects = '刀刁刂刃刄刅分切刈刉刊刋刌刍刎刏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '刐刑划刓刔刕刖列刘则刚创刜初刞刟';
		$expects = '刐刑划刓刔刕刖列刘则刚创刜初刞刟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '删刡刢刣判別刦刧刨利刪别刬刭刮刯';
		$expects = '删刡刢刣判別刦刧刨利刪别刬刭刮刯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '到刱刲刳刴刵制刷券刹刺刻刼刽刾刿';
		$expects = '到刱刲刳刴刵制刷券刹刺刻刼刽刾刿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '剀剁剂剃剄剅剆則剈剉削剋剌前剎剏';
		$expects = '剀剁剂剃剄剅剆則剈剉削剋剌前剎剏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '剐剑剒剓剔剕剖剗剘剙剚剛剜剝剞剟';
		$expects = '剐剑剒剓剔剕剖剗剘剙剚剛剜剝剞剟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '剠剡剢剣剤剥剦剧剨剩剪剫剬剭剮副';
		$expects = '剠剡剢剣剤剥剦剧剨剩剪剫剬剭剮副';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '剰剱割剳剴創剶剷剸剹剺剻剼剽剾剿';
		$expects = '剰剱割剳剴創剶剷剸剹剺剻剼剽剾剿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '劀劁劂劃劄劅劆劇劈劉劊劋劌劍劎劏';
		$expects = '劀劁劂劃劄劅劆劇劈劉劊劋劌劍劎劏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '劐劑劒劓劔劕劖劗劘劙劚力劜劝办功';
		$expects = '劐劑劒劓劔劕劖劗劘劙劚力劜劝办功';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '加务劢劣劤劥劦劧动助努劫劬劭劮劯';
		$expects = '加务劢劣劤劥劦劧动助努劫劬劭劮劯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '劰励劲劳労劵劶劷劸効劺劻劼劽劾势';
		$expects = '劰励劲劳労劵劶劷劸効劺劻劼劽劾势';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '勀勁勂勃勄勅勆勇勈勉勊勋勌勍勎勏';
		$expects = '勀勁勂勃勄勅勆勇勈勉勊勋勌勍勎勏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '勐勑勒勓勔動勖勗勘務勚勛勜勝勞募';
		$expects = '勐勑勒勓勔動勖勗勘務勚勛勜勝勞募';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '勠勡勢勣勤勥勦勧勨勩勪勫勬勭勮勯';
		$expects = '勠勡勢勣勤勥勦勧勨勩勪勫勬勭勮勯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '勰勱勲勳勴勵勶勷勸勹勺勻勼勽勾勿';
		$expects = '勰勱勲勳勴勵勶勷勸勹勺勻勼勽勾勿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '匀匁匂匃匄包匆匇匈匉匊匋匌匍匎匏';
		$expects = '匀匁匂匃匄包匆匇匈匉匊匋匌匍匎匏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '匐匑匒匓匔匕化北匘匙匚匛匜匝匞匟';
		$expects = '匐匑匒匓匔匕化北匘匙匚匛匜匝匞匟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '匠匡匢匣匤匥匦匧匨匩匪匫匬匭匮匯';
		$expects = '匠匡匢匣匤匥匦匧匨匩匪匫匬匭匮匯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '匰匱匲匳匴匵匶匷匸匹区医匼匽匾匿';
		$expects = '匰匱匲匳匴匵匶匷匸匹区医匼匽匾匿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '區十卂千卄卅卆升午卉半卋卌卍华协';
		$expects = '區十卂千卄卅卆升午卉半卋卌卍华协';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '卐卑卒卓協单卖南単卙博卛卜卝卞卟';
		$expects = '卐卑卒卓協单卖南単卙博卛卜卝卞卟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '占卡卢卣卤卥卦卧卨卩卪卫卬卭卮卯';
		$expects = '占卡卢卣卤卥卦卧卨卩卪卫卬卭卮卯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '印危卲即却卵卶卷卸卹卺卻卼卽卾卿';
		$expects = '印危卲即却卵卶卷卸卹卺卻卼卽卾卿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '厀厁厂厃厄厅历厇厈厉厊压厌厍厎厏';
		$expects = '厀厁厂厃厄厅历厇厈厉厊压厌厍厎厏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '厐厑厒厓厔厕厖厗厘厙厚厛厜厝厞原';
		$expects = '厐厑厒厓厔厕厖厗厘厙厚厛厜厝厞原';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '厠厡厢厣厤厥厦厧厨厩厪厫厬厭厮厯';
		$expects = '厠厡厢厣厤厥厦厧厨厩厪厫厬厭厮厯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '厰厱厲厳厴厵厶厷厸厹厺去厼厽厾县';
		$expects = '厰厱厲厳厴厵厶厷厸厹厺去厼厽厾县';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '叀叁参參叄叅叆叇又叉及友双反収叏';
		$expects = '叀叁参參叄叅叆叇又叉及友双反収叏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '叐发叒叓叔叕取受变叙叚叛叜叝叞叟';
		$expects = '叐发叒叓叔叕取受变叙叚叛叜叝叞叟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '叠叡叢口古句另叧叨叩只叫召叭叮可';
		$expects = '叠叡叢口古句另叧叨叩只叫召叭叮可';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '台叱史右叴叵叶号司叹叺叻叼叽叾叿';
		$expects = '台叱史右叴叵叶号司叹叺叻叼叽叾叿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '吀吁吂吃各吅吆吇合吉吊吋同名后吏';
		$expects = '吀吁吂吃各吅吆吇合吉吊吋同名后吏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '吐向吒吓吔吕吖吗吘吙吚君吜吝吞吟';
		$expects = '吐向吒吓吔吕吖吗吘吙吚君吜吝吞吟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '吠吡吢吣吤吥否吧吨吩吪含听吭吮启';
		$expects = '吠吡吢吣吤吥否吧吨吩吪含听吭吮启';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '吰吱吲吳吴吵吶吷吸吹吺吻吼吽吾吿';
		$expects = '吰吱吲吳吴吵吶吷吸吹吺吻吼吽吾吿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '呀呁呂呃呄呅呆呇呈呉告呋呌呍呎呏';
		$expects = '呀呁呂呃呄呅呆呇呈呉告呋呌呍呎呏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '呐呑呒呓呔呕呖呗员呙呚呛呜呝呞呟';
		$expects = '呐呑呒呓呔呕呖呗员呙呚呛呜呝呞呟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '呠呡呢呣呤呥呦呧周呩呪呫呬呭呮呯';
		$expects = '呠呡呢呣呤呥呦呧周呩呪呫呬呭呮呯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '呰呱呲味呴呵呶呷呸呹呺呻呼命呾呿';
		$expects = '呰呱呲味呴呵呶呷呸呹呺呻呼命呾呿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '咀咁咂咃咄咅咆咇咈咉咊咋和咍咎咏';
		$expects = '咀咁咂咃咄咅咆咇咈咉咊咋和咍咎咏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '咐咑咒咓咔咕咖咗咘咙咚咛咜咝咞咟';
		$expects = '咐咑咒咓咔咕咖咗咘咙咚咛咜咝咞咟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '咠咡咢咣咤咥咦咧咨咩咪咫咬咭咮咯';
		$expects = '咠咡咢咣咤咥咦咧咨咩咪咫咬咭咮咯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '咰咱咲咳咴咵咶咷咸咹咺咻咼咽咾咿';
		$expects = '咰咱咲咳咴咵咶咷咸咹咺咻咼咽咾咿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '哀品哂哃哄哅哆哇哈哉哊哋哌响哎哏';
		$expects = '哀品哂哃哄哅哆哇哈哉哊哋哌响哎哏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '哐哑哒哓哔哕哖哗哘哙哚哛哜哝哞哟';
		$expects = '哐哑哒哓哔哕哖哗哘哙哚哛哜哝哞哟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '哠員哢哣哤哥哦哧哨哩哪哫哬哭哮哯';
		$expects = '哠員哢哣哤哥哦哧哨哩哪哫哬哭哮哯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '哰哱哲哳哴哵哶哷哸哹哺哻哼哽哾哿';
		$expects = '哰哱哲哳哴哵哶哷哸哹哺哻哼哽哾哿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '唀唁唂唃唄唅唆唇唈唉唊唋唌唍唎唏';
		$expects = '唀唁唂唃唄唅唆唇唈唉唊唋唌唍唎唏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '唐唑唒唓唔唕唖唗唘唙唚唛唜唝唞唟';
		$expects = '唐唑唒唓唔唕唖唗唘唙唚唛唜唝唞唟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '唠唡唢唣唤唥唦唧唨唩唪唫唬唭售唯';
		$expects = '唠唡唢唣唤唥唦唧唨唩唪唫唬唭售唯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '唰唱唲唳唴唵唶唷唸唹唺唻唼唽唾唿';
		$expects = '唰唱唲唳唴唵唶唷唸唹唺唻唼唽唾唿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '啀啁啂啃啄啅商啇啈啉啊啋啌啍啎問';
		$expects = '啀啁啂啃啄啅商啇啈啉啊啋啌啍啎問';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '啐啑啒啓啔啕啖啗啘啙啚啛啜啝啞啟';
		$expects = '啐啑啒啓啔啕啖啗啘啙啚啛啜啝啞啟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '啠啡啢啣啤啥啦啧啨啩啪啫啬啭啮啯';
		$expects = '啠啡啢啣啤啥啦啧啨啩啪啫啬啭啮啯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '啰啱啲啳啴啵啶啷啸啹啺啻啼啽啾啿';
		$expects = '啰啱啲啳啴啵啶啷啸啹啺啻啼啽啾啿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '喀喁喂喃善喅喆喇喈喉喊喋喌喍喎喏';
		$expects = '喀喁喂喃善喅喆喇喈喉喊喋喌喍喎喏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '喐喑喒喓喔喕喖喗喘喙喚喛喜喝喞喟';
		$expects = '喐喑喒喓喔喕喖喗喘喙喚喛喜喝喞喟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '喠喡喢喣喤喥喦喧喨喩喪喫喬喭單喯';
		$expects = '喠喡喢喣喤喥喦喧喨喩喪喫喬喭單喯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '喰喱喲喳喴喵営喷喸喹喺喻喼喽喾喿';
		$expects = '喰喱喲喳喴喵営喷喸喹喺喻喼喽喾喿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嗀嗁嗂嗃嗄嗅嗆嗇嗈嗉嗊嗋嗌嗍嗎嗏';
		$expects = '嗀嗁嗂嗃嗄嗅嗆嗇嗈嗉嗊嗋嗌嗍嗎嗏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嗐嗑嗒嗓嗔嗕嗖嗗嗘嗙嗚嗛嗜嗝嗞嗟';
		$expects = '嗐嗑嗒嗓嗔嗕嗖嗗嗘嗙嗚嗛嗜嗝嗞嗟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嗠嗡嗢嗣嗤嗥嗦嗧嗨嗩嗪嗫嗬嗭嗮嗯';
		$expects = '嗠嗡嗢嗣嗤嗥嗦嗧嗨嗩嗪嗫嗬嗭嗮嗯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嗰嗱嗲嗳嗴嗵嗶嗷嗸嗹嗺嗻嗼嗽嗾嗿';
		$expects = '嗰嗱嗲嗳嗴嗵嗶嗷嗸嗹嗺嗻嗼嗽嗾嗿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嘀嘁嘂嘃嘄嘅嘆嘇嘈嘉嘊嘋嘌嘍嘎嘏';
		$expects = '嘀嘁嘂嘃嘄嘅嘆嘇嘈嘉嘊嘋嘌嘍嘎嘏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嘐嘑嘒嘓嘔嘕嘖嘗嘘嘙嘚嘛嘜嘝嘞嘟';
		$expects = '嘐嘑嘒嘓嘔嘕嘖嘗嘘嘙嘚嘛嘜嘝嘞嘟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嘠嘡嘢嘣嘤嘥嘦嘧嘨嘩嘪嘫嘬嘭嘮嘯';
		$expects = '嘠嘡嘢嘣嘤嘥嘦嘧嘨嘩嘪嘫嘬嘭嘮嘯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嘰嘱嘲嘳嘴嘵嘶嘷嘸嘹嘺嘻嘼嘽嘾嘿';
		$expects = '嘰嘱嘲嘳嘴嘵嘶嘷嘸嘹嘺嘻嘼嘽嘾嘿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '噀噁噂噃噄噅噆噇噈噉噊噋噌噍噎噏';
		$expects = '噀噁噂噃噄噅噆噇噈噉噊噋噌噍噎噏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '噐噑噒噓噔噕噖噗噘噙噚噛噜噝噞噟';
		$expects = '噐噑噒噓噔噕噖噗噘噙噚噛噜噝噞噟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '噠噡噢噣噤噥噦噧器噩噪噫噬噭噮噯';
		$expects = '噠噡噢噣噤噥噦噧器噩噪噫噬噭噮噯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '噰噱噲噳噴噵噶噷噸噹噺噻噼噽噾噿';
		$expects = '噰噱噲噳噴噵噶噷噸噹噺噻噼噽噾噿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嚀嚁嚂嚃嚄嚅嚆嚇嚈嚉嚊嚋嚌嚍嚎嚏';
		$expects = '嚀嚁嚂嚃嚄嚅嚆嚇嚈嚉嚊嚋嚌嚍嚎嚏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嚐嚑嚒嚓嚔嚕嚖嚗嚘嚙嚚嚛嚜嚝嚞嚟';
		$expects = '嚐嚑嚒嚓嚔嚕嚖嚗嚘嚙嚚嚛嚜嚝嚞嚟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嚠嚡嚢嚣嚤嚥嚦嚧嚨嚩嚪嚫嚬嚭嚮嚯';
		$expects = '嚠嚡嚢嚣嚤嚥嚦嚧嚨嚩嚪嚫嚬嚭嚮嚯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嚰嚱嚲嚳嚴嚵嚶嚷嚸嚹嚺嚻嚼嚽嚾嚿';
		$expects = '嚰嚱嚲嚳嚴嚵嚶嚷嚸嚹嚺嚻嚼嚽嚾嚿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '囀囁囂囃囄囅囆囇囈囉囊囋囌囍囎囏';
		$expects = '囀囁囂囃囄囅囆囇囈囉囊囋囌囍囎囏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '囐囑囒囓囔囕囖囗囘囙囚四囜囝回囟';
		$expects = '囐囑囒囓囔囕囖囗囘囙囚四囜囝回囟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '因囡团団囤囥囦囧囨囩囪囫囬园囮囯';
		$expects = '因囡团団囤囥囦囧囨囩囪囫囬园囮囯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '困囱囲図围囵囶囷囸囹固囻囼国图囿';
		$expects = '困囱囲図围囵囶囷囸囹固囻囼国图囿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '圀圁圂圃圄圅圆圇圈圉圊國圌圍圎圏';
		$expects = '圀圁圂圃圄圅圆圇圈圉圊國圌圍圎圏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '圐圑園圓圔圕圖圗團圙圚圛圜圝圞土';
		$expects = '圐圑園圓圔圕圖圗團圙圚圛圜圝圞土';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '圠圡圢圣圤圥圦圧在圩圪圫圬圭圮圯';
		$expects = '圠圡圢圣圤圥圦圧在圩圪圫圬圭圮圯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '地圱圲圳圴圵圶圷圸圹场圻圼圽圾圿';
		$expects = '地圱圲圳圴圵圶圷圸圹场圻圼圽圾圿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '址坁坂坃坄坅坆均坈坉坊坋坌坍坎坏';
		$expects = '址坁坂坃坄坅坆均坈坉坊坋坌坍坎坏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '坐坑坒坓坔坕坖块坘坙坚坛坜坝坞坟';
		$expects = '坐坑坒坓坔坕坖块坘坙坚坛坜坝坞坟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '坠坡坢坣坤坥坦坧坨坩坪坫坬坭坮坯';
		$expects = '坠坡坢坣坤坥坦坧坨坩坪坫坬坭坮坯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '坰坱坲坳坴坵坶坷坸坹坺坻坼坽坾坿';
		$expects = '坰坱坲坳坴坵坶坷坸坹坺坻坼坽坾坿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '垀垁垂垃垄垅垆垇垈垉垊型垌垍垎垏';
		$expects = '垀垁垂垃垄垅垆垇垈垉垊型垌垍垎垏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '垐垑垒垓垔垕垖垗垘垙垚垛垜垝垞垟';
		$expects = '垐垑垒垓垔垕垖垗垘垙垚垛垜垝垞垟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '垠垡垢垣垤垥垦垧垨垩垪垫垬垭垮垯';
		$expects = '垠垡垢垣垤垥垦垧垨垩垪垫垬垭垮垯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '垰垱垲垳垴垵垶垷垸垹垺垻垼垽垾垿';
		$expects = '垰垱垲垳垴垵垶垷垸垹垺垻垼垽垾垿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '埀埁埂埃埄埅埆埇埈埉埊埋埌埍城埏';
		$expects = '埀埁埂埃埄埅埆埇埈埉埊埋埌埍城埏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '埐埑埒埓埔埕埖埗埘埙埚埛埜埝埞域';
		$expects = '埐埑埒埓埔埕埖埗埘埙埚埛埜埝埞域';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '埠埡埢埣埤埥埦埧埨埩埪埫埬埭埮埯';
		$expects = '埠埡埢埣埤埥埦埧埨埩埪埫埬埭埮埯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '埰埱埲埳埴埵埶執埸培基埻埼埽埾埿';
		$expects = '埰埱埲埳埴埵埶執埸培基埻埼埽埾埿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '堀堁堂堃堄堅堆堇堈堉堊堋堌堍堎堏';
		$expects = '堀堁堂堃堄堅堆堇堈堉堊堋堌堍堎堏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '堐堑堒堓堔堕堖堗堘堙堚堛堜堝堞堟';
		$expects = '堐堑堒堓堔堕堖堗堘堙堚堛堜堝堞堟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '堠堡堢堣堤堥堦堧堨堩堪堫堬堭堮堯';
		$expects = '堠堡堢堣堤堥堦堧堨堩堪堫堬堭堮堯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '堰報堲堳場堵堶堷堸堹堺堻堼堽堾堿';
		$expects = '堰報堲堳場堵堶堷堸堹堺堻堼堽堾堿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '塀塁塂塃塄塅塆塇塈塉塊塋塌塍塎塏';
		$expects = '塀塁塂塃塄塅塆塇塈塉塊塋塌塍塎塏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '塐塑塒塓塔塕塖塗塘塙塚塛塜塝塞塟';
		$expects = '塐塑塒塓塔塕塖塗塘塙塚塛塜塝塞塟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '塠塡塢塣塤塥塦塧塨塩塪填塬塭塮塯';
		$expects = '塠塡塢塣塤塥塦塧塨塩塪填塬塭塮塯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '塰塱塲塳塴塵塶塷塸塹塺塻塼塽塾塿';
		$expects = '塰塱塲塳塴塵塶塷塸塹塺塻塼塽塾塿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '墀墁墂境墄墅墆墇墈墉墊墋墌墍墎墏';
		$expects = '墀墁墂境墄墅墆墇墈墉墊墋墌墍墎墏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '墐墑墒墓墔墕墖増墘墙墚墛墜墝增墟';
		$expects = '墐墑墒墓墔墕墖増墘墙墚墛墜墝增墟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '墠墡墢墣墤墥墦墧墨墩墪墫墬墭墮墯';
		$expects = '墠墡墢墣墤墥墦墧墨墩墪墫墬墭墮墯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '墰墱墲墳墴墵墶墷墸墹墺墻墼墽墾墿';
		$expects = '墰墱墲墳墴墵墶墷墸墹墺墻墼墽墾墿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '壀壁壂壃壄壅壆壇壈壉壊壋壌壍壎壏';
		$expects = '壀壁壂壃壄壅壆壇壈壉壊壋壌壍壎壏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '壐壑壒壓壔壕壖壗壘壙壚壛壜壝壞壟';
		$expects = '壐壑壒壓壔壕壖壗壘壙壚壛壜壝壞壟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '壠壡壢壣壤壥壦壧壨壩壪士壬壭壮壯';
		$expects = '壠壡壢壣壤壥壦壧壨壩壪士壬壭壮壯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '声壱売壳壴壵壶壷壸壹壺壻壼壽壾壿';
		$expects = '声壱売壳壴壵壶壷壸壹壺壻壼壽壾壿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '夀夁夂夃处夅夆备夈変夊夋夌复夎夏';
		$expects = '夀夁夂夃处夅夆备夈変夊夋夌复夎夏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '夐夑夒夓夔夕外夗夘夙多夛夜夝夞够';
		$expects = '夐夑夒夓夔夕外夗夘夙多夛夜夝夞够';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '夠夡夢夣夤夥夦大夨天太夫夬夭央夯';
		$expects = '夠夡夢夣夤夥夦大夨天太夫夬夭央夯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '夰失夲夳头夵夶夷夸夹夺夻夼夽夾夿';
		$expects = '夰失夲夳头夵夶夷夸夹夺夻夼夽夾夿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '奀奁奂奃奄奅奆奇奈奉奊奋奌奍奎奏';
		$expects = '奀奁奂奃奄奅奆奇奈奉奊奋奌奍奎奏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '奐契奒奓奔奕奖套奘奙奚奛奜奝奞奟';
		$expects = '奐契奒奓奔奕奖套奘奙奚奛奜奝奞奟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '奠奡奢奣奤奥奦奧奨奩奪奫奬奭奮奯';
		$expects = '奠奡奢奣奤奥奦奧奨奩奪奫奬奭奮奯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '奰奱奲女奴奵奶奷奸她奺奻奼好奾奿';
		$expects = '奰奱奲女奴奵奶奷奸她奺奻奼好奾奿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '妀妁如妃妄妅妆妇妈妉妊妋妌妍妎妏';
		$expects = '妀妁如妃妄妅妆妇妈妉妊妋妌妍妎妏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '妐妑妒妓妔妕妖妗妘妙妚妛妜妝妞妟';
		$expects = '妐妑妒妓妔妕妖妗妘妙妚妛妜妝妞妟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '妠妡妢妣妤妥妦妧妨妩妪妫妬妭妮妯';
		$expects = '妠妡妢妣妤妥妦妧妨妩妪妫妬妭妮妯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '妰妱妲妳妴妵妶妷妸妹妺妻妼妽妾妿';
		$expects = '妰妱妲妳妴妵妶妷妸妹妺妻妼妽妾妿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '姀姁姂姃姄姅姆姇姈姉姊始姌姍姎姏';
		$expects = '姀姁姂姃姄姅姆姇姈姉姊始姌姍姎姏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '姐姑姒姓委姕姖姗姘姙姚姛姜姝姞姟';
		$expects = '姐姑姒姓委姕姖姗姘姙姚姛姜姝姞姟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '姠姡姢姣姤姥姦姧姨姩姪姫姬姭姮姯';
		$expects = '姠姡姢姣姤姥姦姧姨姩姪姫姬姭姮姯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '姰姱姲姳姴姵姶姷姸姹姺姻姼姽姾姿';
		$expects = '姰姱姲姳姴姵姶姷姸姹姺姻姼姽姾姿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '娀威娂娃娄娅娆娇娈娉娊娋娌娍娎娏';
		$expects = '娀威娂娃娄娅娆娇娈娉娊娋娌娍娎娏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '娐娑娒娓娔娕娖娗娘娙娚娛娜娝娞娟';
		$expects = '娐娑娒娓娔娕娖娗娘娙娚娛娜娝娞娟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '娠娡娢娣娤娥娦娧娨娩娪娫娬娭娮娯';
		$expects = '娠娡娢娣娤娥娦娧娨娩娪娫娬娭娮娯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '娰娱娲娳娴娵娶娷娸娹娺娻娼娽娾娿';
		$expects = '娰娱娲娳娴娵娶娷娸娹娺娻娼娽娾娿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '婀婁婂婃婄婅婆婇婈婉婊婋婌婍婎婏';
		$expects = '婀婁婂婃婄婅婆婇婈婉婊婋婌婍婎婏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '婐婑婒婓婔婕婖婗婘婙婚婛婜婝婞婟';
		$expects = '婐婑婒婓婔婕婖婗婘婙婚婛婜婝婞婟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '婠婡婢婣婤婥婦婧婨婩婪婫婬婭婮婯';
		$expects = '婠婡婢婣婤婥婦婧婨婩婪婫婬婭婮婯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '婰婱婲婳婴婵婶婷婸婹婺婻婼婽婾婿';
		$expects = '婰婱婲婳婴婵婶婷婸婹婺婻婼婽婾婿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '媀媁媂媃媄媅媆媇媈媉媊媋媌媍媎媏';
		$expects = '媀媁媂媃媄媅媆媇媈媉媊媋媌媍媎媏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '媐媑媒媓媔媕媖媗媘媙媚媛媜媝媞媟';
		$expects = '媐媑媒媓媔媕媖媗媘媙媚媛媜媝媞媟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '媠媡媢媣媤媥媦媧媨媩媪媫媬媭媮媯';
		$expects = '媠媡媢媣媤媥媦媧媨媩媪媫媬媭媮媯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '媰媱媲媳媴媵媶媷媸媹媺媻媼媽媾媿';
		$expects = '媰媱媲媳媴媵媶媷媸媹媺媻媼媽媾媿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嫀嫁嫂嫃嫄嫅嫆嫇嫈嫉嫊嫋嫌嫍嫎嫏';
		$expects = '嫀嫁嫂嫃嫄嫅嫆嫇嫈嫉嫊嫋嫌嫍嫎嫏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嫐嫑嫒嫓嫔嫕嫖嫗嫘嫙嫚嫛嫜嫝嫞嫟';
		$expects = '嫐嫑嫒嫓嫔嫕嫖嫗嫘嫙嫚嫛嫜嫝嫞嫟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嫠嫡嫢嫣嫤嫥嫦嫧嫨嫩嫪嫫嫬嫭嫮嫯';
		$expects = '嫠嫡嫢嫣嫤嫥嫦嫧嫨嫩嫪嫫嫬嫭嫮嫯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嫰嫱嫲嫳嫴嫵嫶嫷嫸嫹嫺嫻嫼嫽嫾嫿';
		$expects = '嫰嫱嫲嫳嫴嫵嫶嫷嫸嫹嫺嫻嫼嫽嫾嫿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嬀嬁嬂嬃嬄嬅嬆嬇嬈嬉嬊嬋嬌嬍嬎嬏';
		$expects = '嬀嬁嬂嬃嬄嬅嬆嬇嬈嬉嬊嬋嬌嬍嬎嬏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嬐嬑嬒嬓嬔嬕嬖嬗嬘嬙嬚嬛嬜嬝嬞嬟';
		$expects = '嬐嬑嬒嬓嬔嬕嬖嬗嬘嬙嬚嬛嬜嬝嬞嬟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嬠嬡嬢嬣嬤嬥嬦嬧嬨嬩嬪嬫嬬嬭嬮嬯';
		$expects = '嬠嬡嬢嬣嬤嬥嬦嬧嬨嬩嬪嬫嬬嬭嬮嬯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嬰嬱嬲嬳嬴嬵嬶嬷嬸嬹嬺嬻嬼嬽嬾嬿';
		$expects = '嬰嬱嬲嬳嬴嬵嬶嬷嬸嬹嬺嬻嬼嬽嬾嬿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '孀孁孂孃孄孅孆孇孈孉孊孋孌孍孎孏';
		$expects = '孀孁孂孃孄孅孆孇孈孉孊孋孌孍孎孏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '子孑孒孓孔孕孖字存孙孚孛孜孝孞孟';
		$expects = '子孑孒孓孔孕孖字存孙孚孛孜孝孞孟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '孠孡孢季孤孥学孧孨孩孪孫孬孭孮孯';
		$expects = '孠孡孢季孤孥学孧孨孩孪孫孬孭孮孯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '孰孱孲孳孴孵孶孷學孹孺孻孼孽孾孿';
		$expects = '孰孱孲孳孴孵孶孷學孹孺孻孼孽孾孿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '宀宁宂它宄宅宆宇守安宊宋完宍宎宏';
		$expects = '宀宁宂它宄宅宆宇守安宊宋完宍宎宏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '宐宑宒宓宔宕宖宗官宙定宛宜宝实実';
		$expects = '宐宑宒宓宔宕宖宗官宙定宛宜宝实実';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '宠审客宣室宥宦宧宨宩宪宫宬宭宮宯';
		$expects = '宠审客宣室宥宦宧宨宩宪宫宬宭宮宯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '宰宱宲害宴宵家宷宸容宺宻宼宽宾宿';
		$expects = '宰宱宲害宴宵家宷宸容宺宻宼宽宾宿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '寀寁寂寃寄寅密寇寈寉寊寋富寍寎寏';
		$expects = '寀寁寂寃寄寅密寇寈寉寊寋富寍寎寏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '寐寑寒寓寔寕寖寗寘寙寚寛寜寝寞察';
		$expects = '寐寑寒寓寔寕寖寗寘寙寚寛寜寝寞察';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '寠寡寢寣寤寥實寧寨審寪寫寬寭寮寯';
		$expects = '寠寡寢寣寤寥實寧寨審寪寫寬寭寮寯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '寰寱寲寳寴寵寶寷寸对寺寻导寽対寿';
		$expects = '寰寱寲寳寴寵寶寷寸对寺寻导寽対寿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '尀封専尃射尅将將專尉尊尋尌對導小';
		$expects = '尀封専尃射尅将將專尉尊尋尌對導小';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '尐少尒尓尔尕尖尗尘尙尚尛尜尝尞尟';
		$expects = '尐少尒尓尔尕尖尗尘尙尚尛尜尝尞尟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '尠尡尢尣尤尥尦尧尨尩尪尫尬尭尮尯';
		$expects = '尠尡尢尣尤尥尦尧尨尩尪尫尬尭尮尯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '尰就尲尳尴尵尶尷尸尹尺尻尼尽尾尿';
		$expects = '尰就尲尳尴尵尶尷尸尹尺尻尼尽尾尿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '局屁层屃屄居屆屇屈屉届屋屌屍屎屏';
		$expects = '局屁层屃屄居屆屇屈屉届屋屌屍屎屏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '屐屑屒屓屔展屖屗屘屙屚屛屜屝属屟';
		$expects = '屐屑屒屓屔展屖屗屘屙屚屛屜屝属屟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '屠屡屢屣層履屦屧屨屩屪屫屬屭屮屯';
		$expects = '屠屡屢屣層履屦屧屨屩屪屫屬屭屮屯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '屰山屲屳屴屵屶屷屸屹屺屻屼屽屾屿';
		$expects = '屰山屲屳屴屵屶屷屸屹屺屻屼屽屾屿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '岀岁岂岃岄岅岆岇岈岉岊岋岌岍岎岏';
		$expects = '岀岁岂岃岄岅岆岇岈岉岊岋岌岍岎岏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '岐岑岒岓岔岕岖岗岘岙岚岛岜岝岞岟';
		$expects = '岐岑岒岓岔岕岖岗岘岙岚岛岜岝岞岟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '岠岡岢岣岤岥岦岧岨岩岪岫岬岭岮岯';
		$expects = '岠岡岢岣岤岥岦岧岨岩岪岫岬岭岮岯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '岰岱岲岳岴岵岶岷岸岹岺岻岼岽岾岿';
		$expects = '岰岱岲岳岴岵岶岷岸岹岺岻岼岽岾岿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '峀峁峂峃峄峅峆峇峈峉峊峋峌峍峎峏';
		$expects = '峀峁峂峃峄峅峆峇峈峉峊峋峌峍峎峏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '峐峑峒峓峔峕峖峗峘峙峚峛峜峝峞峟';
		$expects = '峐峑峒峓峔峕峖峗峘峙峚峛峜峝峞峟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '峠峡峢峣峤峥峦峧峨峩峪峫峬峭峮峯';
		$expects = '峠峡峢峣峤峥峦峧峨峩峪峫峬峭峮峯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '峰峱峲峳峴峵島峷峸峹峺峻峼峽峾峿';
		$expects = '峰峱峲峳峴峵島峷峸峹峺峻峼峽峾峿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '崀崁崂崃崄崅崆崇崈崉崊崋崌崍崎崏';
		$expects = '崀崁崂崃崄崅崆崇崈崉崊崋崌崍崎崏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '崐崑崒崓崔崕崖崗崘崙崚崛崜崝崞崟';
		$expects = '崐崑崒崓崔崕崖崗崘崙崚崛崜崝崞崟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '崠崡崢崣崤崥崦崧崨崩崪崫崬崭崮崯';
		$expects = '崠崡崢崣崤崥崦崧崨崩崪崫崬崭崮崯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '崰崱崲崳崴崵崶崷崸崹崺崻崼崽崾崿';
		$expects = '崰崱崲崳崴崵崶崷崸崹崺崻崼崽崾崿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嵀嵁嵂嵃嵄嵅嵆嵇嵈嵉嵊嵋嵌嵍嵎嵏';
		$expects = '嵀嵁嵂嵃嵄嵅嵆嵇嵈嵉嵊嵋嵌嵍嵎嵏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嵐嵑嵒嵓嵔嵕嵖嵗嵘嵙嵚嵛嵜嵝嵞嵟';
		$expects = '嵐嵑嵒嵓嵔嵕嵖嵗嵘嵙嵚嵛嵜嵝嵞嵟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嵠嵡嵢嵣嵤嵥嵦嵧嵨嵩嵪嵫嵬嵭嵮嵯';
		$expects = '嵠嵡嵢嵣嵤嵥嵦嵧嵨嵩嵪嵫嵬嵭嵮嵯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嵰嵱嵲嵳嵴嵵嵶嵷嵸嵹嵺嵻嵼嵽嵾嵿';
		$expects = '嵰嵱嵲嵳嵴嵵嵶嵷嵸嵹嵺嵻嵼嵽嵾嵿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嶀嶁嶂嶃嶄嶅嶆嶇嶈嶉嶊嶋嶌嶍嶎嶏';
		$expects = '嶀嶁嶂嶃嶄嶅嶆嶇嶈嶉嶊嶋嶌嶍嶎嶏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嶐嶑嶒嶓嶔嶕嶖嶗嶘嶙嶚嶛嶜嶝嶞嶟';
		$expects = '嶐嶑嶒嶓嶔嶕嶖嶗嶘嶙嶚嶛嶜嶝嶞嶟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嶠嶡嶢嶣嶤嶥嶦嶧嶨嶩嶪嶫嶬嶭嶮嶯';
		$expects = '嶠嶡嶢嶣嶤嶥嶦嶧嶨嶩嶪嶫嶬嶭嶮嶯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '嶰嶱嶲嶳嶴嶵嶶嶷嶸嶹嶺嶻嶼嶽嶾嶿';
		$expects = '嶰嶱嶲嶳嶴嶵嶶嶷嶸嶹嶺嶻嶼嶽嶾嶿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '巀巁巂巃巄巅巆巇巈巉巊巋巌巍巎巏';
		$expects = '巀巁巂巃巄巅巆巇巈巉巊巋巌巍巎巏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '巐巑巒巓巔巕巖巗巘巙巚巛巜川州巟';
		$expects = '巐巑巒巓巔巕巖巗巘巙巚巛巜川州巟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '巠巡巢巣巤工左巧巨巩巪巫巬巭差巯';
		$expects = '巠巡巢巣巤工左巧巨巩巪巫巬巭差巯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '巰己已巳巴巵巶巷巸巹巺巻巼巽巾巿';
		$expects = '巰己已巳巴巵巶巷巸巹巺巻巼巽巾巿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '帀币市布帄帅帆帇师帉帊帋希帍帎帏';
		$expects = '帀币市布帄帅帆帇师帉帊帋希帍帎帏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '帐帑帒帓帔帕帖帗帘帙帚帛帜帝帞帟';
		$expects = '帐帑帒帓帔帕帖帗帘帙帚帛帜帝帞帟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '帠帡帢帣帤帥带帧帨帩帪師帬席帮帯';
		$expects = '帠帡帢帣帤帥带帧帨帩帪師帬席帮帯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '帰帱帲帳帴帵帶帷常帹帺帻帼帽帾帿';
		$expects = '帰帱帲帳帴帵帶帷常帹帺帻帼帽帾帿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '幀幁幂幃幄幅幆幇幈幉幊幋幌幍幎幏';
		$expects = '幀幁幂幃幄幅幆幇幈幉幊幋幌幍幎幏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '幐幑幒幓幔幕幖幗幘幙幚幛幜幝幞幟';
		$expects = '幐幑幒幓幔幕幖幗幘幙幚幛幜幝幞幟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '幠幡幢幣幤幥幦幧幨幩幪幫幬幭幮幯';
		$expects = '幠幡幢幣幤幥幦幧幨幩幪幫幬幭幮幯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '幰幱干平年幵并幷幸幹幺幻幼幽幾广';
		$expects = '幰幱干平年幵并幷幸幹幺幻幼幽幾广';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '庀庁庂広庄庅庆庇庈庉床庋庌庍庎序';
		$expects = '庀庁庂広庄庅庆庇庈庉床庋庌庍庎序';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '庐庑庒库应底庖店庘庙庚庛府庝庞废';
		$expects = '庐庑庒库应底庖店庘庙庚庛府庝庞废';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '庠庡庢庣庤庥度座庨庩庪庫庬庭庮庯';
		$expects = '庠庡庢庣庤庥度座庨庩庪庫庬庭庮庯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '庰庱庲庳庴庵庶康庸庹庺庻庼庽庾庿';
		$expects = '庰庱庲庳庴庵庶康庸庹庺庻庼庽庾庿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '廀廁廂廃廄廅廆廇廈廉廊廋廌廍廎廏';
		$expects = '廀廁廂廃廄廅廆廇廈廉廊廋廌廍廎廏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '廐廑廒廓廔廕廖廗廘廙廚廛廜廝廞廟';
		$expects = '廐廑廒廓廔廕廖廗廘廙廚廛廜廝廞廟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '廠廡廢廣廤廥廦廧廨廩廪廫廬廭廮廯';
		$expects = '廠廡廢廣廤廥廦廧廨廩廪廫廬廭廮廯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '廰廱廲廳廴廵延廷廸廹建廻廼廽廾廿';
		$expects = '廰廱廲廳廴廵延廷廸廹建廻廼廽廾廿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '开弁异弃弄弅弆弇弈弉弊弋弌弍弎式';
		$expects = '开弁异弃弄弅弆弇弈弉弊弋弌弍弎式';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '弐弑弒弓弔引弖弗弘弙弚弛弜弝弞弟';
		$expects = '弐弑弒弓弔引弖弗弘弙弚弛弜弝弞弟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '张弡弢弣弤弥弦弧弨弩弪弫弬弭弮弯';
		$expects = '张弡弢弣弤弥弦弧弨弩弪弫弬弭弮弯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '弰弱弲弳弴張弶強弸弹强弻弼弽弾弿';
		$expects = '弰弱弲弳弴張弶強弸弹强弻弼弽弾弿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '彀彁彂彃彄彅彆彇彈彉彊彋彌彍彎彏';
		$expects = '彀彁彂彃彄彅彆彇彈彉彊彋彌彍彎彏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '彐彑归当彔录彖彗彘彙彚彛彜彝彞彟';
		$expects = '彐彑归当彔录彖彗彘彙彚彛彜彝彞彟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '彠彡形彣彤彥彦彧彨彩彪彫彬彭彮彯';
		$expects = '彠彡形彣彤彥彦彧彨彩彪彫彬彭彮彯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '彰影彲彳彴彵彶彷彸役彺彻彼彽彾彿';
		$expects = '彰影彲彳彴彵彶彷彸役彺彻彼彽彾彿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '往征徂徃径待徆徇很徉徊律後徍徎徏';
		$expects = '往征徂徃径待徆徇很徉徊律後徍徎徏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '徐徑徒従徔徕徖得徘徙徚徛徜徝從徟';
		$expects = '徐徑徒従徔徕徖得徘徙徚徛徜徝從徟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '徠御徢徣徤徥徦徧徨復循徫徬徭微徯';
		$expects = '徠御徢徣徤徥徦徧徨復循徫徬徭微徯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '徰徱徲徳徴徵徶德徸徹徺徻徼徽徾徿';
		$expects = '徰徱徲徳徴徵徶德徸徹徺徻徼徽徾徿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '忀忁忂心忄必忆忇忈忉忊忋忌忍忎忏';
		$expects = '忀忁忂心忄必忆忇忈忉忊忋忌忍忎忏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '忐忑忒忓忔忕忖志忘忙忚忛応忝忞忟';
		$expects = '忐忑忒忓忔忕忖志忘忙忚忛応忝忞忟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '忠忡忢忣忤忥忦忧忨忩忪快忬忭忮忯';
		$expects = '忠忡忢忣忤忥忦忧忨忩忪快忬忭忮忯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '忰忱忲忳忴念忶忷忸忹忺忻忼忽忾忿';
		$expects = '忰忱忲忳忴念忶忷忸忹忺忻忼忽忾忿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSection6 method
	 *
	 * Testing characters 6000 - 6fff
	 *
	 * @return void
	 */
	public function testSection6() {
		$string = '怀态怂怃怄怅怆怇怈怉怊怋怌怍怎怏';
		$expects = '怀态怂怃怄怅怆怇怈怉怊怋怌怍怎怏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '怐怑怒怓怔怕怖怗怘怙怚怛怜思怞怟';
		$expects = '怐怑怒怓怔怕怖怗怘怙怚怛怜思怞怟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '怠怡怢怣怤急怦性怨怩怪怫怬怭怮怯';
		$expects = '怠怡怢怣怤急怦性怨怩怪怫怬怭怮怯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '怰怱怲怳怴怵怶怷怸怹怺总怼怽怾怿';
		$expects = '怰怱怲怳怴怵怶怷怸怹怺总怼怽怾怿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '恀恁恂恃恄恅恆恇恈恉恊恋恌恍恎恏';
		$expects = '恀恁恂恃恄恅恆恇恈恉恊恋恌恍恎恏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '恐恑恒恓恔恕恖恗恘恙恚恛恜恝恞恟';
		$expects = '恐恑恒恓恔恕恖恗恘恙恚恛恜恝恞恟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '恠恡恢恣恤恥恦恧恨恩恪恫恬恭恮息';
		$expects = '恠恡恢恣恤恥恦恧恨恩恪恫恬恭恮息';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '恰恱恲恳恴恵恶恷恸恹恺恻恼恽恾恿';
		$expects = '恰恱恲恳恴恵恶恷恸恹恺恻恼恽恾恿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '悀悁悂悃悄悅悆悇悈悉悊悋悌悍悎悏';
		$expects = '悀悁悂悃悄悅悆悇悈悉悊悋悌悍悎悏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '悐悑悒悓悔悕悖悗悘悙悚悛悜悝悞悟';
		$expects = '悐悑悒悓悔悕悖悗悘悙悚悛悜悝悞悟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '悠悡悢患悤悥悦悧您悩悪悫悬悭悮悯';
		$expects = '悠悡悢患悤悥悦悧您悩悪悫悬悭悮悯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '悰悱悲悳悴悵悶悷悸悹悺悻悼悽悾悿';
		$expects = '悰悱悲悳悴悵悶悷悸悹悺悻悼悽悾悿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '惀惁惂惃惄情惆惇惈惉惊惋惌惍惎惏';
		$expects = '惀惁惂惃惄情惆惇惈惉惊惋惌惍惎惏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '惐惑惒惓惔惕惖惗惘惙惚惛惜惝惞惟';
		$expects = '惐惑惒惓惔惕惖惗惘惙惚惛惜惝惞惟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '惠惡惢惣惤惥惦惧惨惩惪惫惬惭惮惯';
		$expects = '惠惡惢惣惤惥惦惧惨惩惪惫惬惭惮惯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '惰惱惲想惴惵惶惷惸惹惺惻惼惽惾惿';
		$expects = '惰惱惲想惴惵惶惷惸惹惺惻惼惽惾惿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '愀愁愂愃愄愅愆愇愈愉愊愋愌愍愎意';
		$expects = '愀愁愂愃愄愅愆愇愈愉愊愋愌愍愎意';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '愐愑愒愓愔愕愖愗愘愙愚愛愜愝愞感';
		$expects = '愐愑愒愓愔愕愖愗愘愙愚愛愜愝愞感';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '愠愡愢愣愤愥愦愧愨愩愪愫愬愭愮愯';
		$expects = '愠愡愢愣愤愥愦愧愨愩愪愫愬愭愮愯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '愰愱愲愳愴愵愶愷愸愹愺愻愼愽愾愿';
		$expects = '愰愱愲愳愴愵愶愷愸愹愺愻愼愽愾愿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '慀慁慂慃慄慅慆慇慈慉慊態慌慍慎慏';
		$expects = '慀慁慂慃慄慅慆慇慈慉慊態慌慍慎慏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '慐慑慒慓慔慕慖慗慘慙慚慛慜慝慞慟';
		$expects = '慐慑慒慓慔慕慖慗慘慙慚慛慜慝慞慟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '慠慡慢慣慤慥慦慧慨慩慪慫慬慭慮慯';
		$expects = '慠慡慢慣慤慥慦慧慨慩慪慫慬慭慮慯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '慰慱慲慳慴慵慶慷慸慹慺慻慼慽慾慿';
		$expects = '慰慱慲慳慴慵慶慷慸慹慺慻慼慽慾慿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '憀憁憂憃憄憅憆憇憈憉憊憋憌憍憎憏';
		$expects = '憀憁憂憃憄憅憆憇憈憉憊憋憌憍憎憏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '憐憑憒憓憔憕憖憗憘憙憚憛憜憝憞憟';
		$expects = '憐憑憒憓憔憕憖憗憘憙憚憛憜憝憞憟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '憠憡憢憣憤憥憦憧憨憩憪憫憬憭憮憯';
		$expects = '憠憡憢憣憤憥憦憧憨憩憪憫憬憭憮憯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '憰憱憲憳憴憵憶憷憸憹憺憻憼憽憾憿';
		$expects = '憰憱憲憳憴憵憶憷憸憹憺憻憼憽憾憿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '懀懁懂懃懄懅懆懇懈應懊懋懌懍懎懏';
		$expects = '懀懁懂懃懄懅懆懇懈應懊懋懌懍懎懏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '懐懑懒懓懔懕懖懗懘懙懚懛懜懝懞懟';
		$expects = '懐懑懒懓懔懕懖懗懘懙懚懛懜懝懞懟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '懠懡懢懣懤懥懦懧懨懩懪懫懬懭懮懯';
		$expects = '懠懡懢懣懤懥懦懧懨懩懪懫懬懭懮懯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '懰懱懲懳懴懵懶懷懸懹懺懻懼懽懾懿';
		$expects = '懰懱懲懳懴懵懶懷懸懹懺懻懼懽懾懿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '戀戁戂戃戄戅戆戇戈戉戊戋戌戍戎戏';
		$expects = '戀戁戂戃戄戅戆戇戈戉戊戋戌戍戎戏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '成我戒戓戔戕或戗战戙戚戛戜戝戞戟';
		$expects = '成我戒戓戔戕或戗战戙戚戛戜戝戞戟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '戠戡戢戣戤戥戦戧戨戩截戫戬戭戮戯';
		$expects = '戠戡戢戣戤戥戦戧戨戩截戫戬戭戮戯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '戰戱戲戳戴戵戶户戸戹戺戻戼戽戾房';
		$expects = '戰戱戲戳戴戵戶户戸戹戺戻戼戽戾房';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '所扁扂扃扄扅扆扇扈扉扊手扌才扎扏';
		$expects = '所扁扂扃扄扅扆扇扈扉扊手扌才扎扏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '扐扑扒打扔払扖扗托扙扚扛扜扝扞扟';
		$expects = '扐扑扒打扔払扖扗托扙扚扛扜扝扞扟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '扠扡扢扣扤扥扦执扨扩扪扫扬扭扮扯';
		$expects = '扠扡扢扣扤扥扦执扨扩扪扫扬扭扮扯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '扰扱扲扳扴扵扶扷扸批扺扻扼扽找承';
		$expects = '扰扱扲扳扴扵扶扷扸批扺扻扼扽找承';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '技抁抂抃抄抅抆抇抈抉把抋抌抍抎抏';
		$expects = '技抁抂抃抄抅抆抇抈抉把抋抌抍抎抏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '抐抑抒抓抔投抖抗折抙抚抛抜抝択抟';
		$expects = '抐抑抒抓抔投抖抗折抙抚抛抜抝択抟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '抠抡抢抣护报抦抧抨抩抪披抬抭抮抯';
		$expects = '抠抡抢抣护报抦抧抨抩抪披抬抭抮抯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '抰抱抲抳抴抵抶抷抸抹抺抻押抽抾抿';
		$expects = '抰抱抲抳抴抵抶抷抸抹抺抻押抽抾抿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '拀拁拂拃拄担拆拇拈拉拊拋拌拍拎拏';
		$expects = '拀拁拂拃拄担拆拇拈拉拊拋拌拍拎拏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '拐拑拒拓拔拕拖拗拘拙拚招拜拝拞拟';
		$expects = '拐拑拒拓拔拕拖拗拘拙拚招拜拝拞拟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '拠拡拢拣拤拥拦拧拨择拪拫括拭拮拯';
		$expects = '拠拡拢拣拤拥拦拧拨择拪拫括拭拮拯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '拰拱拲拳拴拵拶拷拸拹拺拻拼拽拾拿';
		$expects = '拰拱拲拳拴拵拶拷拸拹拺拻拼拽拾拿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '挀持挂挃挄挅挆指挈按挊挋挌挍挎挏';
		$expects = '挀持挂挃挄挅挆指挈按挊挋挌挍挎挏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '挐挑挒挓挔挕挖挗挘挙挚挛挜挝挞挟';
		$expects = '挐挑挒挓挔挕挖挗挘挙挚挛挜挝挞挟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '挠挡挢挣挤挥挦挧挨挩挪挫挬挭挮振';
		$expects = '挠挡挢挣挤挥挦挧挨挩挪挫挬挭挮振';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '挰挱挲挳挴挵挶挷挸挹挺挻挼挽挾挿';
		$expects = '挰挱挲挳挴挵挶挷挸挹挺挻挼挽挾挿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '捀捁捂捃捄捅捆捇捈捉捊捋捌捍捎捏';
		$expects = '捀捁捂捃捄捅捆捇捈捉捊捋捌捍捎捏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '捐捑捒捓捔捕捖捗捘捙捚捛捜捝捞损';
		$expects = '捐捑捒捓捔捕捖捗捘捙捚捛捜捝捞损';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '捠捡换捣捤捥捦捧捨捩捪捫捬捭据捯';
		$expects = '捠捡换捣捤捥捦捧捨捩捪捫捬捭据捯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '捰捱捲捳捴捵捶捷捸捹捺捻捼捽捾捿';
		$expects = '捰捱捲捳捴捵捶捷捸捹捺捻捼捽捾捿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '掀掁掂掃掄掅掆掇授掉掊掋掌掍掎掏';
		$expects = '掀掁掂掃掄掅掆掇授掉掊掋掌掍掎掏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '掐掑排掓掔掕掖掗掘掙掚掛掜掝掞掟';
		$expects = '掐掑排掓掔掕掖掗掘掙掚掛掜掝掞掟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '掠採探掣掤接掦控推掩措掫掬掭掮掯';
		$expects = '掠採探掣掤接掦控推掩措掫掬掭掮掯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '掰掱掲掳掴掵掶掷掸掹掺掻掼掽掾掿';
		$expects = '掰掱掲掳掴掵掶掷掸掹掺掻掼掽掾掿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '揀揁揂揃揄揅揆揇揈揉揊揋揌揍揎描';
		$expects = '揀揁揂揃揄揅揆揇揈揉揊揋揌揍揎描';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '提揑插揓揔揕揖揗揘揙揚換揜揝揞揟';
		$expects = '提揑插揓揔揕揖揗揘揙揚換揜揝揞揟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '揠握揢揣揤揥揦揧揨揩揪揫揬揭揮揯';
		$expects = '揠握揢揣揤揥揦揧揨揩揪揫揬揭揮揯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '揰揱揲揳援揵揶揷揸揹揺揻揼揽揾揿';
		$expects = '揰揱揲揳援揵揶揷揸揹揺揻揼揽揾揿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '搀搁搂搃搄搅搆搇搈搉搊搋搌損搎搏';
		$expects = '搀搁搂搃搄搅搆搇搈搉搊搋搌損搎搏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '搐搑搒搓搔搕搖搗搘搙搚搛搜搝搞搟';
		$expects = '搐搑搒搓搔搕搖搗搘搙搚搛搜搝搞搟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '搠搡搢搣搤搥搦搧搨搩搪搫搬搭搮搯';
		$expects = '搠搡搢搣搤搥搦搧搨搩搪搫搬搭搮搯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '搰搱搲搳搴搵搶搷搸搹携搻搼搽搾搿';
		$expects = '搰搱搲搳搴搵搶搷搸搹携搻搼搽搾搿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '摀摁摂摃摄摅摆摇摈摉摊摋摌摍摎摏';
		$expects = '摀摁摂摃摄摅摆摇摈摉摊摋摌摍摎摏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '摐摑摒摓摔摕摖摗摘摙摚摛摜摝摞摟';
		$expects = '摐摑摒摓摔摕摖摗摘摙摚摛摜摝摞摟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '摠摡摢摣摤摥摦摧摨摩摪摫摬摭摮摯';
		$expects = '摠摡摢摣摤摥摦摧摨摩摪摫摬摭摮摯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '摰摱摲摳摴摵摶摷摸摹摺摻摼摽摾摿';
		$expects = '摰摱摲摳摴摵摶摷摸摹摺摻摼摽摾摿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '撀撁撂撃撄撅撆撇撈撉撊撋撌撍撎撏';
		$expects = '撀撁撂撃撄撅撆撇撈撉撊撋撌撍撎撏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '撐撑撒撓撔撕撖撗撘撙撚撛撜撝撞撟';
		$expects = '撐撑撒撓撔撕撖撗撘撙撚撛撜撝撞撟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '撠撡撢撣撤撥撦撧撨撩撪撫撬播撮撯';
		$expects = '撠撡撢撣撤撥撦撧撨撩撪撫撬播撮撯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '撰撱撲撳撴撵撶撷撸撹撺撻撼撽撾撿';
		$expects = '撰撱撲撳撴撵撶撷撸撹撺撻撼撽撾撿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '擀擁擂擃擄擅擆擇擈擉擊擋擌操擎擏';
		$expects = '擀擁擂擃擄擅擆擇擈擉擊擋擌操擎擏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '擐擑擒擓擔擕擖擗擘擙據擛擜擝擞擟';
		$expects = '擐擑擒擓擔擕擖擗擘擙據擛擜擝擞擟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '擠擡擢擣擤擥擦擧擨擩擪擫擬擭擮擯';
		$expects = '擠擡擢擣擤擥擦擧擨擩擪擫擬擭擮擯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '擰擱擲擳擴擵擶擷擸擹擺擻擼擽擾擿';
		$expects = '擰擱擲擳擴擵擶擷擸擹擺擻擼擽擾擿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '攀攁攂攃攄攅攆攇攈攉攊攋攌攍攎攏';
		$expects = '攀攁攂攃攄攅攆攇攈攉攊攋攌攍攎攏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '攐攑攒攓攔攕攖攗攘攙攚攛攜攝攞攟';
		$expects = '攐攑攒攓攔攕攖攗攘攙攚攛攜攝攞攟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '攠攡攢攣攤攥攦攧攨攩攪攫攬攭攮支';
		$expects = '攠攡攢攣攤攥攦攧攨攩攪攫攬攭攮支';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '攰攱攲攳攴攵收攷攸改攺攻攼攽放政';
		$expects = '攰攱攲攳攴攵收攷攸改攺攻攼攽放政';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '敀敁敂敃敄故敆敇效敉敊敋敌敍敎敏';
		$expects = '敀敁敂敃敄故敆敇效敉敊敋敌敍敎敏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '敐救敒敓敔敕敖敗敘教敚敛敜敝敞敟';
		$expects = '敐救敒敓敔敕敖敗敘教敚敛敜敝敞敟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '敠敡敢散敤敥敦敧敨敩敪敫敬敭敮敯';
		$expects = '敠敡敢散敤敥敦敧敨敩敪敫敬敭敮敯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '数敱敲敳整敵敶敷數敹敺敻敼敽敾敿';
		$expects = '数敱敲敳整敵敶敷數敹敺敻敼敽敾敿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '斀斁斂斃斄斅斆文斈斉斊斋斌斍斎斏';
		$expects = '斀斁斂斃斄斅斆文斈斉斊斋斌斍斎斏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '斐斑斒斓斔斕斖斗斘料斚斛斜斝斞斟';
		$expects = '斐斑斒斓斔斕斖斗斘料斚斛斜斝斞斟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '斠斡斢斣斤斥斦斧斨斩斪斫斬断斮斯';
		$expects = '斠斡斢斣斤斥斦斧斨斩斪斫斬断斮斯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '新斱斲斳斴斵斶斷斸方斺斻於施斾斿';
		$expects = '新斱斲斳斴斵斶斷斸方斺斻於施斾斿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '旀旁旂旃旄旅旆旇旈旉旊旋旌旍旎族';
		$expects = '旀旁旂旃旄旅旆旇旈旉旊旋旌旍旎族';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '旐旑旒旓旔旕旖旗旘旙旚旛旜旝旞旟';
		$expects = '旐旑旒旓旔旕旖旗旘旙旚旛旜旝旞旟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '无旡既旣旤日旦旧旨早旪旫旬旭旮旯';
		$expects = '无旡既旣旤日旦旧旨早旪旫旬旭旮旯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '旰旱旲旳旴旵时旷旸旹旺旻旼旽旾旿';
		$expects = '旰旱旲旳旴旵时旷旸旹旺旻旼旽旾旿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '昀昁昂昃昄昅昆昇昈昉昊昋昌昍明昏';
		$expects = '昀昁昂昃昄昅昆昇昈昉昊昋昌昍明昏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '昐昑昒易昔昕昖昗昘昙昚昛昜昝昞星';
		$expects = '昐昑昒易昔昕昖昗昘昙昚昛昜昝昞星';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '映昡昢昣昤春昦昧昨昩昪昫昬昭昮是';
		$expects = '映昡昢昣昤春昦昧昨昩昪昫昬昭昮是';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '昰昱昲昳昴昵昶昷昸昹昺昻昼昽显昿';
		$expects = '昰昱昲昳昴昵昶昷昸昹昺昻昼昽显昿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '晀晁時晃晄晅晆晇晈晉晊晋晌晍晎晏';
		$expects = '晀晁時晃晄晅晆晇晈晉晊晋晌晍晎晏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '晐晑晒晓晔晕晖晗晘晙晚晛晜晝晞晟';
		$expects = '晐晑晒晓晔晕晖晗晘晙晚晛晜晝晞晟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '晠晡晢晣晤晥晦晧晨晩晪晫晬晭普景';
		$expects = '晠晡晢晣晤晥晦晧晨晩晪晫晬晭普景';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '晰晱晲晳晴晵晶晷晸晹智晻晼晽晾晿';
		$expects = '晰晱晲晳晴晵晶晷晸晹智晻晼晽晾晿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '暀暁暂暃暄暅暆暇暈暉暊暋暌暍暎暏';
		$expects = '暀暁暂暃暄暅暆暇暈暉暊暋暌暍暎暏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '暐暑暒暓暔暕暖暗暘暙暚暛暜暝暞暟';
		$expects = '暐暑暒暓暔暕暖暗暘暙暚暛暜暝暞暟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '暠暡暢暣暤暥暦暧暨暩暪暫暬暭暮暯';
		$expects = '暠暡暢暣暤暥暦暧暨暩暪暫暬暭暮暯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '暰暱暲暳暴暵暶暷暸暹暺暻暼暽暾暿';
		$expects = '暰暱暲暳暴暵暶暷暸暹暺暻暼暽暾暿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '曀曁曂曃曄曅曆曇曈曉曊曋曌曍曎曏';
		$expects = '曀曁曂曃曄曅曆曇曈曉曊曋曌曍曎曏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '曐曑曒曓曔曕曖曗曘曙曚曛曜曝曞曟';
		$expects = '曐曑曒曓曔曕曖曗曘曙曚曛曜曝曞曟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '曠曡曢曣曤曥曦曧曨曩曪曫曬曭曮曯';
		$expects = '曠曡曢曣曤曥曦曧曨曩曪曫曬曭曮曯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '曰曱曲曳更曵曶曷書曹曺曻曼曽曾替';
		$expects = '曰曱曲曳更曵曶曷書曹曺曻曼曽曾替';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '最朁朂會朄朅朆朇月有朊朋朌服朎朏';
		$expects = '最朁朂會朄朅朆朇月有朊朋朌服朎朏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '朐朑朒朓朔朕朖朗朘朙朚望朜朝朞期';
		$expects = '朐朑朒朓朔朕朖朗朘朙朚望朜朝朞期';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '朠朡朢朣朤朥朦朧木朩未末本札朮术';
		$expects = '朠朡朢朣朤朥朦朧木朩未末本札朮术';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '朰朱朲朳朴朵朶朷朸朹机朻朼朽朾朿';
		$expects = '朰朱朲朳朴朵朶朷朸朹机朻朼朽朾朿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '杀杁杂权杄杅杆杇杈杉杊杋杌杍李杏';
		$expects = '杀杁杂权杄杅杆杇杈杉杊杋杌杍李杏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '材村杒杓杔杕杖杗杘杙杚杛杜杝杞束';
		$expects = '材村杒杓杔杕杖杗杘杙杚杛杜杝杞束';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '杠条杢杣杤来杦杧杨杩杪杫杬杭杮杯';
		$expects = '杠条杢杣杤来杦杧杨杩杪杫杬杭杮杯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '杰東杲杳杴杵杶杷杸杹杺杻杼杽松板';
		$expects = '杰東杲杳杴杵杶杷杸杹杺杻杼杽松板';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '枀极枂枃构枅枆枇枈枉枊枋枌枍枎枏';
		$expects = '枀极枂枃构枅枆枇枈枉枊枋枌枍枎枏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '析枑枒枓枔枕枖林枘枙枚枛果枝枞枟';
		$expects = '析枑枒枓枔枕枖林枘枙枚枛果枝枞枟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '枠枡枢枣枤枥枦枧枨枩枪枫枬枭枮枯';
		$expects = '枠枡枢枣枤枥枦枧枨枩枪枫枬枭枮枯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '枰枱枲枳枴枵架枷枸枹枺枻枼枽枾枿';
		$expects = '枰枱枲枳枴枵架枷枸枹枺枻枼枽枾枿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '柀柁柂柃柄柅柆柇柈柉柊柋柌柍柎柏';
		$expects = '柀柁柂柃柄柅柆柇柈柉柊柋柌柍柎柏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '某柑柒染柔柕柖柗柘柙柚柛柜柝柞柟';
		$expects = '某柑柒染柔柕柖柗柘柙柚柛柜柝柞柟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '柠柡柢柣柤查柦柧柨柩柪柫柬柭柮柯';
		$expects = '柠柡柢柣柤查柦柧柨柩柪柫柬柭柮柯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '柰柱柲柳柴柵柶柷柸柹柺査柼柽柾柿';
		$expects = '柰柱柲柳柴柵柶柷柸柹柺査柼柽柾柿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '栀栁栂栃栄栅栆标栈栉栊栋栌栍栎栏';
		$expects = '栀栁栂栃栄栅栆标栈栉栊栋栌栍栎栏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '栐树栒栓栔栕栖栗栘栙栚栛栜栝栞栟';
		$expects = '栐树栒栓栔栕栖栗栘栙栚栛栜栝栞栟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '栠校栢栣栤栥栦栧栨栩株栫栬栭栮栯';
		$expects = '栠校栢栣栤栥栦栧栨栩株栫栬栭栮栯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '栰栱栲栳栴栵栶样核根栺栻格栽栾栿';
		$expects = '栰栱栲栳栴栵栶样核根栺栻格栽栾栿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '桀桁桂桃桄桅框桇案桉桊桋桌桍桎桏';
		$expects = '桀桁桂桃桄桅框桇案桉桊桋桌桍桎桏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '桐桑桒桓桔桕桖桗桘桙桚桛桜桝桞桟';
		$expects = '桐桑桒桓桔桕桖桗桘桙桚桛桜桝桞桟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '桠桡桢档桤桥桦桧桨桩桪桫桬桭桮桯';
		$expects = '桠桡桢档桤桥桦桧桨桩桪桫桬桭桮桯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '桰桱桲桳桴桵桶桷桸桹桺桻桼桽桾桿';
		$expects = '桰桱桲桳桴桵桶桷桸桹桺桻桼桽桾桿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '梀梁梂梃梄梅梆梇梈梉梊梋梌梍梎梏';
		$expects = '梀梁梂梃梄梅梆梇梈梉梊梋梌梍梎梏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '梐梑梒梓梔梕梖梗梘梙梚梛梜條梞梟';
		$expects = '梐梑梒梓梔梕梖梗梘梙梚梛梜條梞梟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '梠梡梢梣梤梥梦梧梨梩梪梫梬梭梮梯';
		$expects = '梠梡梢梣梤梥梦梧梨梩梪梫梬梭梮梯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '械梱梲梳梴梵梶梷梸梹梺梻梼梽梾梿';
		$expects = '械梱梲梳梴梵梶梷梸梹梺梻梼梽梾梿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '检棁棂棃棄棅棆棇棈棉棊棋棌棍棎棏';
		$expects = '检棁棂棃棄棅棆棇棈棉棊棋棌棍棎棏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '棐棑棒棓棔棕棖棗棘棙棚棛棜棝棞棟';
		$expects = '棐棑棒棓棔棕棖棗棘棙棚棛棜棝棞棟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '棠棡棢棣棤棥棦棧棨棩棪棫棬棭森棯';
		$expects = '棠棡棢棣棤棥棦棧棨棩棪棫棬棭森棯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '棰棱棲棳棴棵棶棷棸棹棺棻棼棽棾棿';
		$expects = '棰棱棲棳棴棵棶棷棸棹棺棻棼棽棾棿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '椀椁椂椃椄椅椆椇椈椉椊椋椌植椎椏';
		$expects = '椀椁椂椃椄椅椆椇椈椉椊椋椌植椎椏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '椐椑椒椓椔椕椖椗椘椙椚椛検椝椞椟';
		$expects = '椐椑椒椓椔椕椖椗椘椙椚椛検椝椞椟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '椠椡椢椣椤椥椦椧椨椩椪椫椬椭椮椯';
		$expects = '椠椡椢椣椤椥椦椧椨椩椪椫椬椭椮椯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '椰椱椲椳椴椵椶椷椸椹椺椻椼椽椾椿';
		$expects = '椰椱椲椳椴椵椶椷椸椹椺椻椼椽椾椿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '楀楁楂楃楄楅楆楇楈楉楊楋楌楍楎楏';
		$expects = '楀楁楂楃楄楅楆楇楈楉楊楋楌楍楎楏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '楐楑楒楓楔楕楖楗楘楙楚楛楜楝楞楟';
		$expects = '楐楑楒楓楔楕楖楗楘楙楚楛楜楝楞楟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '楠楡楢楣楤楥楦楧楨楩楪楫楬業楮楯';
		$expects = '楠楡楢楣楤楥楦楧楨楩楪楫楬業楮楯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '楰楱楲楳楴極楶楷楸楹楺楻楼楽楾楿';
		$expects = '楰楱楲楳楴極楶楷楸楹楺楻楼楽楾楿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '榀榁概榃榄榅榆榇榈榉榊榋榌榍榎榏';
		$expects = '榀榁概榃榄榅榆榇榈榉榊榋榌榍榎榏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '榐榑榒榓榔榕榖榗榘榙榚榛榜榝榞榟';
		$expects = '榐榑榒榓榔榕榖榗榘榙榚榛榜榝榞榟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '榠榡榢榣榤榥榦榧榨榩榪榫榬榭榮榯';
		$expects = '榠榡榢榣榤榥榦榧榨榩榪榫榬榭榮榯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '榰榱榲榳榴榵榶榷榸榹榺榻榼榽榾榿';
		$expects = '榰榱榲榳榴榵榶榷榸榹榺榻榼榽榾榿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '槀槁槂槃槄槅槆槇槈槉槊構槌槍槎槏';
		$expects = '槀槁槂槃槄槅槆槇槈槉槊構槌槍槎槏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '槐槑槒槓槔槕槖槗様槙槚槛槜槝槞槟';
		$expects = '槐槑槒槓槔槕槖槗様槙槚槛槜槝槞槟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '槠槡槢槣槤槥槦槧槨槩槪槫槬槭槮槯';
		$expects = '槠槡槢槣槤槥槦槧槨槩槪槫槬槭槮槯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '槰槱槲槳槴槵槶槷槸槹槺槻槼槽槾槿';
		$expects = '槰槱槲槳槴槵槶槷槸槹槺槻槼槽槾槿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '樀樁樂樃樄樅樆樇樈樉樊樋樌樍樎樏';
		$expects = '樀樁樂樃樄樅樆樇樈樉樊樋樌樍樎樏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '樐樑樒樓樔樕樖樗樘標樚樛樜樝樞樟';
		$expects = '樐樑樒樓樔樕樖樗樘標樚樛樜樝樞樟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '樠模樢樣樤樥樦樧樨権横樫樬樭樮樯';
		$expects = '樠模樢樣樤樥樦樧樨権横樫樬樭樮樯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '樰樱樲樳樴樵樶樷樸樹樺樻樼樽樾樿';
		$expects = '樰樱樲樳樴樵樶樷樸樹樺樻樼樽樾樿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '橀橁橂橃橄橅橆橇橈橉橊橋橌橍橎橏';
		$expects = '橀橁橂橃橄橅橆橇橈橉橊橋橌橍橎橏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '橐橑橒橓橔橕橖橗橘橙橚橛橜橝橞機';
		$expects = '橐橑橒橓橔橕橖橗橘橙橚橛橜橝橞機';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '橠橡橢橣橤橥橦橧橨橩橪橫橬橭橮橯';
		$expects = '橠橡橢橣橤橥橦橧橨橩橪橫橬橭橮橯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '橰橱橲橳橴橵橶橷橸橹橺橻橼橽橾橿';
		$expects = '橰橱橲橳橴橵橶橷橸橹橺橻橼橽橾橿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '檀檁檂檃檄檅檆檇檈檉檊檋檌檍檎檏';
		$expects = '檀檁檂檃檄檅檆檇檈檉檊檋檌檍檎檏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '檐檑檒檓檔檕檖檗檘檙檚檛檜檝檞檟';
		$expects = '檐檑檒檓檔檕檖檗檘檙檚檛檜檝檞檟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '檠檡檢檣檤檥檦檧檨檩檪檫檬檭檮檯';
		$expects = '檠檡檢檣檤檥檦檧檨檩檪檫檬檭檮檯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '檰檱檲檳檴檵檶檷檸檹檺檻檼檽檾檿';
		$expects = '檰檱檲檳檴檵檶檷檸檹檺檻檼檽檾檿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '櫀櫁櫂櫃櫄櫅櫆櫇櫈櫉櫊櫋櫌櫍櫎櫏';
		$expects = '櫀櫁櫂櫃櫄櫅櫆櫇櫈櫉櫊櫋櫌櫍櫎櫏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '櫐櫑櫒櫓櫔櫕櫖櫗櫘櫙櫚櫛櫜櫝櫞櫟';
		$expects = '櫐櫑櫒櫓櫔櫕櫖櫗櫘櫙櫚櫛櫜櫝櫞櫟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '櫠櫡櫢櫣櫤櫥櫦櫧櫨櫩櫪櫫櫬櫭櫮櫯';
		$expects = '櫠櫡櫢櫣櫤櫥櫦櫧櫨櫩櫪櫫櫬櫭櫮櫯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '櫰櫱櫲櫳櫴櫵櫶櫷櫸櫹櫺櫻櫼櫽櫾櫿';
		$expects = '櫰櫱櫲櫳櫴櫵櫶櫷櫸櫹櫺櫻櫼櫽櫾櫿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '欀欁欂欃欄欅欆欇欈欉權欋欌欍欎欏';
		$expects = '欀欁欂欃欄欅欆欇欈欉權欋欌欍欎欏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '欐欑欒欓欔欕欖欗欘欙欚欛欜欝欞欟';
		$expects = '欐欑欒欓欔欕欖欗欘欙欚欛欜欝欞欟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '欠次欢欣欤欥欦欧欨欩欪欫欬欭欮欯';
		$expects = '欠次欢欣欤欥欦欧欨欩欪欫欬欭欮欯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '欰欱欲欳欴欵欶欷欸欹欺欻欼欽款欿';
		$expects = '欰欱欲欳欴欵欶欷欸欹欺欻欼欽款欿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '歀歁歂歃歄歅歆歇歈歉歊歋歌歍歎歏';
		$expects = '歀歁歂歃歄歅歆歇歈歉歊歋歌歍歎歏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '歐歑歒歓歔歕歖歗歘歙歚歛歜歝歞歟';
		$expects = '歐歑歒歓歔歕歖歗歘歙歚歛歜歝歞歟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '歠歡止正此步武歧歨歩歪歫歬歭歮歯';
		$expects = '歠歡止正此步武歧歨歩歪歫歬歭歮歯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '歰歱歲歳歴歵歶歷歸歹歺死歼歽歾歿';
		$expects = '歰歱歲歳歴歵歶歷歸歹歺死歼歽歾歿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '殀殁殂殃殄殅殆殇殈殉殊残殌殍殎殏';
		$expects = '殀殁殂殃殄殅殆殇殈殉殊残殌殍殎殏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '殐殑殒殓殔殕殖殗殘殙殚殛殜殝殞殟';
		$expects = '殐殑殒殓殔殕殖殗殘殙殚殛殜殝殞殟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '殠殡殢殣殤殥殦殧殨殩殪殫殬殭殮殯';
		$expects = '殠殡殢殣殤殥殦殧殨殩殪殫殬殭殮殯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '殰殱殲殳殴段殶殷殸殹殺殻殼殽殾殿';
		$expects = '殰殱殲殳殴段殶殷殸殹殺殻殼殽殾殿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '毀毁毂毃毄毅毆毇毈毉毊毋毌母毎每';
		$expects = '毀毁毂毃毄毅毆毇毈毉毊毋毌母毎每';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '毐毑毒毓比毕毖毗毘毙毚毛毜毝毞毟';
		$expects = '毐毑毒毓比毕毖毗毘毙毚毛毜毝毞毟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '毠毡毢毣毤毥毦毧毨毩毪毫毬毭毮毯';
		$expects = '毠毡毢毣毤毥毦毧毨毩毪毫毬毭毮毯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '毰毱毲毳毴毵毶毷毸毹毺毻毼毽毾毿';
		$expects = '毰毱毲毳毴毵毶毷毸毹毺毻毼毽毾毿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '氀氁氂氃氄氅氆氇氈氉氊氋氌氍氎氏';
		$expects = '氀氁氂氃氄氅氆氇氈氉氊氋氌氍氎氏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '氐民氒氓气氕氖気氘氙氚氛氜氝氞氟';
		$expects = '氐民氒氓气氕氖気氘氙氚氛氜氝氞氟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '氠氡氢氣氤氥氦氧氨氩氪氫氬氭氮氯';
		$expects = '氠氡氢氣氤氥氦氧氨氩氪氫氬氭氮氯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '氰氱氲氳水氵氶氷永氹氺氻氼氽氾氿';
		$expects = '氰氱氲氳水氵氶氷永氹氺氻氼氽氾氿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '汀汁求汃汄汅汆汇汈汉汊汋汌汍汎汏';
		$expects = '汀汁求汃汄汅汆汇汈汉汊汋汌汍汎汏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '汐汑汒汓汔汕汖汗汘汙汚汛汜汝汞江';
		$expects = '汐汑汒汓汔汕汖汗汘汙汚汛汜汝汞江';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '池污汢汣汤汥汦汧汨汩汪汫汬汭汮汯';
		$expects = '池污汢汣汤汥汦汧汨汩汪汫汬汭汮汯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '汰汱汲汳汴汵汶汷汸汹決汻汼汽汾汿';
		$expects = '汰汱汲汳汴汵汶汷汸汹決汻汼汽汾汿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '沀沁沂沃沄沅沆沇沈沉沊沋沌沍沎沏';
		$expects = '沀沁沂沃沄沅沆沇沈沉沊沋沌沍沎沏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '沐沑沒沓沔沕沖沗沘沙沚沛沜沝沞沟';
		$expects = '沐沑沒沓沔沕沖沗沘沙沚沛沜沝沞沟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '沠没沢沣沤沥沦沧沨沩沪沫沬沭沮沯';
		$expects = '沠没沢沣沤沥沦沧沨沩沪沫沬沭沮沯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '沰沱沲河沴沵沶沷沸油沺治沼沽沾沿';
		$expects = '沰沱沲河沴沵沶沷沸油沺治沼沽沾沿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '泀況泂泃泄泅泆泇泈泉泊泋泌泍泎泏';
		$expects = '泀況泂泃泄泅泆泇泈泉泊泋泌泍泎泏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '泐泑泒泓泔法泖泗泘泙泚泛泜泝泞泟';
		$expects = '泐泑泒泓泔法泖泗泘泙泚泛泜泝泞泟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '泠泡波泣泤泥泦泧注泩泪泫泬泭泮泯';
		$expects = '泠泡波泣泤泥泦泧注泩泪泫泬泭泮泯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '泰泱泲泳泴泵泶泷泸泹泺泻泼泽泾泿';
		$expects = '泰泱泲泳泴泵泶泷泸泹泺泻泼泽泾泿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '洀洁洂洃洄洅洆洇洈洉洊洋洌洍洎洏';
		$expects = '洀洁洂洃洄洅洆洇洈洉洊洋洌洍洎洏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '洐洑洒洓洔洕洖洗洘洙洚洛洜洝洞洟';
		$expects = '洐洑洒洓洔洕洖洗洘洙洚洛洜洝洞洟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '洠洡洢洣洤津洦洧洨洩洪洫洬洭洮洯';
		$expects = '洠洡洢洣洤津洦洧洨洩洪洫洬洭洮洯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '洰洱洲洳洴洵洶洷洸洹洺活洼洽派洿';
		$expects = '洰洱洲洳洴洵洶洷洸洹洺活洼洽派洿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '浀流浂浃浄浅浆浇浈浉浊测浌浍济浏';
		$expects = '浀流浂浃浄浅浆浇浈浉浊测浌浍济浏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '浐浑浒浓浔浕浖浗浘浙浚浛浜浝浞浟';
		$expects = '浐浑浒浓浔浕浖浗浘浙浚浛浜浝浞浟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '浠浡浢浣浤浥浦浧浨浩浪浫浬浭浮浯';
		$expects = '浠浡浢浣浤浥浦浧浨浩浪浫浬浭浮浯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '浰浱浲浳浴浵浶海浸浹浺浻浼浽浾浿';
		$expects = '浰浱浲浳浴浵浶海浸浹浺浻浼浽浾浿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '涀涁涂涃涄涅涆涇消涉涊涋涌涍涎涏';
		$expects = '涀涁涂涃涄涅涆涇消涉涊涋涌涍涎涏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '涐涑涒涓涔涕涖涗涘涙涚涛涜涝涞涟';
		$expects = '涐涑涒涓涔涕涖涗涘涙涚涛涜涝涞涟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '涠涡涢涣涤涥润涧涨涩涪涫涬涭涮涯';
		$expects = '涠涡涢涣涤涥润涧涨涩涪涫涬涭涮涯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '涰涱液涳涴涵涶涷涸涹涺涻涼涽涾涿';
		$expects = '涰涱液涳涴涵涶涷涸涹涺涻涼涽涾涿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '淀淁淂淃淄淅淆淇淈淉淊淋淌淍淎淏';
		$expects = '淀淁淂淃淄淅淆淇淈淉淊淋淌淍淎淏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '淐淑淒淓淔淕淖淗淘淙淚淛淜淝淞淟';
		$expects = '淐淑淒淓淔淕淖淗淘淙淚淛淜淝淞淟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '淠淡淢淣淤淥淦淧淨淩淪淫淬淭淮淯';
		$expects = '淠淡淢淣淤淥淦淧淨淩淪淫淬淭淮淯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '淰深淲淳淴淵淶混淸淹淺添淼淽淾淿';
		$expects = '淰深淲淳淴淵淶混淸淹淺添淼淽淾淿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '渀渁渂渃渄清渆渇済渉渊渋渌渍渎渏';
		$expects = '渀渁渂渃渄清渆渇済渉渊渋渌渍渎渏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '渐渑渒渓渔渕渖渗渘渙渚減渜渝渞渟';
		$expects = '渐渑渒渓渔渕渖渗渘渙渚減渜渝渞渟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '渠渡渢渣渤渥渦渧渨温渪渫測渭渮港';
		$expects = '渠渡渢渣渤渥渦渧渨温渪渫測渭渮港';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '渰渱渲渳渴渵渶渷游渹渺渻渼渽渾渿';
		$expects = '渰渱渲渳渴渵渶渷游渹渺渻渼渽渾渿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '湀湁湂湃湄湅湆湇湈湉湊湋湌湍湎湏';
		$expects = '湀湁湂湃湄湅湆湇湈湉湊湋湌湍湎湏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '湐湑湒湓湔湕湖湗湘湙湚湛湜湝湞湟';
		$expects = '湐湑湒湓湔湕湖湗湘湙湚湛湜湝湞湟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '湠湡湢湣湤湥湦湧湨湩湪湫湬湭湮湯';
		$expects = '湠湡湢湣湤湥湦湧湨湩湪湫湬湭湮湯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '湰湱湲湳湴湵湶湷湸湹湺湻湼湽湾湿';
		$expects = '湰湱湲湳湴湵湶湷湸湹湺湻湼湽湾湿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '満溁溂溃溄溅溆溇溈溉溊溋溌溍溎溏';
		$expects = '満溁溂溃溄溅溆溇溈溉溊溋溌溍溎溏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '源溑溒溓溔溕準溗溘溙溚溛溜溝溞溟';
		$expects = '源溑溒溓溔溕準溗溘溙溚溛溜溝溞溟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '溠溡溢溣溤溥溦溧溨溩溪溫溬溭溮溯';
		$expects = '溠溡溢溣溤溥溦溧溨溩溪溫溬溭溮溯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '溰溱溲溳溴溵溶溷溸溹溺溻溼溽溾溿';
		$expects = '溰溱溲溳溴溵溶溷溸溹溺溻溼溽溾溿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '滀滁滂滃滄滅滆滇滈滉滊滋滌滍滎滏';
		$expects = '滀滁滂滃滄滅滆滇滈滉滊滋滌滍滎滏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '滐滑滒滓滔滕滖滗滘滙滚滛滜滝滞滟';
		$expects = '滐滑滒滓滔滕滖滗滘滙滚滛滜滝滞滟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '滠满滢滣滤滥滦滧滨滩滪滫滬滭滮滯';
		$expects = '滠满滢滣滤滥滦滧滨滩滪滫滬滭滮滯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '滰滱滲滳滴滵滶滷滸滹滺滻滼滽滾滿';
		$expects = '滰滱滲滳滴滵滶滷滸滹滺滻滼滽滾滿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '漀漁漂漃漄漅漆漇漈漉漊漋漌漍漎漏';
		$expects = '漀漁漂漃漄漅漆漇漈漉漊漋漌漍漎漏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '漐漑漒漓演漕漖漗漘漙漚漛漜漝漞漟';
		$expects = '漐漑漒漓演漕漖漗漘漙漚漛漜漝漞漟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '漠漡漢漣漤漥漦漧漨漩漪漫漬漭漮漯';
		$expects = '漠漡漢漣漤漥漦漧漨漩漪漫漬漭漮漯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '漰漱漲漳漴漵漶漷漸漹漺漻漼漽漾漿';
		$expects = '漰漱漲漳漴漵漶漷漸漹漺漻漼漽漾漿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '潀潁潂潃潄潅潆潇潈潉潊潋潌潍潎潏';
		$expects = '潀潁潂潃潄潅潆潇潈潉潊潋潌潍潎潏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '潐潑潒潓潔潕潖潗潘潙潚潛潜潝潞潟';
		$expects = '潐潑潒潓潔潕潖潗潘潙潚潛潜潝潞潟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '潠潡潢潣潤潥潦潧潨潩潪潫潬潭潮潯';
		$expects = '潠潡潢潣潤潥潦潧潨潩潪潫潬潭潮潯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '潰潱潲潳潴潵潶潷潸潹潺潻潼潽潾潿';
		$expects = '潰潱潲潳潴潵潶潷潸潹潺潻潼潽潾潿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '澀澁澂澃澄澅澆澇澈澉澊澋澌澍澎澏';
		$expects = '澀澁澂澃澄澅澆澇澈澉澊澋澌澍澎澏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '澐澑澒澓澔澕澖澗澘澙澚澛澜澝澞澟';
		$expects = '澐澑澒澓澔澕澖澗澘澙澚澛澜澝澞澟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '澠澡澢澣澤澥澦澧澨澩澪澫澬澭澮澯';
		$expects = '澠澡澢澣澤澥澦澧澨澩澪澫澬澭澮澯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '澰澱澲澳澴澵澶澷澸澹澺澻澼澽澾澿';
		$expects = '澰澱澲澳澴澵澶澷澸澹澺澻澼澽澾澿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '激濁濂濃濄濅濆濇濈濉濊濋濌濍濎濏';
		$expects = '激濁濂濃濄濅濆濇濈濉濊濋濌濍濎濏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '濐濑濒濓濔濕濖濗濘濙濚濛濜濝濞濟';
		$expects = '濐濑濒濓濔濕濖濗濘濙濚濛濜濝濞濟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '濠濡濢濣濤濥濦濧濨濩濪濫濬濭濮濯';
		$expects = '濠濡濢濣濤濥濦濧濨濩濪濫濬濭濮濯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '濰濱濲濳濴濵濶濷濸濹濺濻濼濽濾濿';
		$expects = '濰濱濲濳濴濵濶濷濸濹濺濻濼濽濾濿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSection7 method
	 *
	 * Testing characters 7000 - 7fff
	 *
	 * @return void
	 */
	public function testSection7() {
		$string = '瀀瀁瀂瀃瀄瀅瀆瀇瀈瀉瀊瀋瀌瀍瀎瀏';
		$expects = '瀀瀁瀂瀃瀄瀅瀆瀇瀈瀉瀊瀋瀌瀍瀎瀏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瀐瀑瀒瀓瀔瀕瀖瀗瀘瀙瀚瀛瀜瀝瀞瀟';
		$expects = '瀐瀑瀒瀓瀔瀕瀖瀗瀘瀙瀚瀛瀜瀝瀞瀟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瀠瀡瀢瀣瀤瀥瀦瀧瀨瀩瀪瀫瀬瀭瀮瀯';
		$expects = '瀠瀡瀢瀣瀤瀥瀦瀧瀨瀩瀪瀫瀬瀭瀮瀯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瀰瀱瀲瀳瀴瀵瀶瀷瀸瀹瀺瀻瀼瀽瀾瀿';
		$expects = '瀰瀱瀲瀳瀴瀵瀶瀷瀸瀹瀺瀻瀼瀽瀾瀿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '灀灁灂灃灄灅灆灇灈灉灊灋灌灍灎灏';
		$expects = '灀灁灂灃灄灅灆灇灈灉灊灋灌灍灎灏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '灐灑灒灓灔灕灖灗灘灙灚灛灜灝灞灟';
		$expects = '灐灑灒灓灔灕灖灗灘灙灚灛灜灝灞灟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '灠灡灢灣灤灥灦灧灨灩灪火灬灭灮灯';
		$expects = '灠灡灢灣灤灥灦灧灨灩灪火灬灭灮灯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '灰灱灲灳灴灵灶灷灸灹灺灻灼災灾灿';
		$expects = '灰灱灲灳灴灵灶灷灸灹灺灻灼災灾灿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '炀炁炂炃炄炅炆炇炈炉炊炋炌炍炎炏';
		$expects = '炀炁炂炃炄炅炆炇炈炉炊炋炌炍炎炏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '炐炑炒炓炔炕炖炗炘炙炚炛炜炝炞炟';
		$expects = '炐炑炒炓炔炕炖炗炘炙炚炛炜炝炞炟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '炠炡炢炣炤炥炦炧炨炩炪炫炬炭炮炯';
		$expects = '炠炡炢炣炤炥炦炧炨炩炪炫炬炭炮炯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '炰炱炲炳炴炵炶炷炸点為炻炼炽炾炿';
		$expects = '炰炱炲炳炴炵炶炷炸点為炻炼炽炾炿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '烀烁烂烃烄烅烆烇烈烉烊烋烌烍烎烏';
		$expects = '烀烁烂烃烄烅烆烇烈烉烊烋烌烍烎烏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '烐烑烒烓烔烕烖烗烘烙烚烛烜烝烞烟';
		$expects = '烐烑烒烓烔烕烖烗烘烙烚烛烜烝烞烟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '烠烡烢烣烤烥烦烧烨烩烪烫烬热烮烯';
		$expects = '烠烡烢烣烤烥烦烧烨烩烪烫烬热烮烯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '烰烱烲烳烴烵烶烷烸烹烺烻烼烽烾烿';
		$expects = '烰烱烲烳烴烵烶烷烸烹烺烻烼烽烾烿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '焀焁焂焃焄焅焆焇焈焉焊焋焌焍焎焏';
		$expects = '焀焁焂焃焄焅焆焇焈焉焊焋焌焍焎焏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '焐焑焒焓焔焕焖焗焘焙焚焛焜焝焞焟';
		$expects = '焐焑焒焓焔焕焖焗焘焙焚焛焜焝焞焟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '焠無焢焣焤焥焦焧焨焩焪焫焬焭焮焯';
		$expects = '焠無焢焣焤焥焦焧焨焩焪焫焬焭焮焯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '焰焱焲焳焴焵然焷焸焹焺焻焼焽焾焿';
		$expects = '焰焱焲焳焴焵然焷焸焹焺焻焼焽焾焿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '煀煁煂煃煄煅煆煇煈煉煊煋煌煍煎煏';
		$expects = '煀煁煂煃煄煅煆煇煈煉煊煋煌煍煎煏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '煐煑煒煓煔煕煖煗煘煙煚煛煜煝煞煟';
		$expects = '煐煑煒煓煔煕煖煗煘煙煚煛煜煝煞煟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '煠煡煢煣煤煥煦照煨煩煪煫煬煭煮煯';
		$expects = '煠煡煢煣煤煥煦照煨煩煪煫煬煭煮煯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '煰煱煲煳煴煵煶煷煸煹煺煻煼煽煾煿';
		$expects = '煰煱煲煳煴煵煶煷煸煹煺煻煼煽煾煿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '熀熁熂熃熄熅熆熇熈熉熊熋熌熍熎熏';
		$expects = '熀熁熂熃熄熅熆熇熈熉熊熋熌熍熎熏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '熐熑熒熓熔熕熖熗熘熙熚熛熜熝熞熟';
		$expects = '熐熑熒熓熔熕熖熗熘熙熚熛熜熝熞熟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '熠熡熢熣熤熥熦熧熨熩熪熫熬熭熮熯';
		$expects = '熠熡熢熣熤熥熦熧熨熩熪熫熬熭熮熯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '熰熱熲熳熴熵熶熷熸熹熺熻熼熽熾熿';
		$expects = '熰熱熲熳熴熵熶熷熸熹熺熻熼熽熾熿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '燀燁燂燃燄燅燆燇燈燉燊燋燌燍燎燏';
		$expects = '燀燁燂燃燄燅燆燇燈燉燊燋燌燍燎燏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '燐燑燒燓燔燕燖燗燘燙燚燛燜燝燞營';
		$expects = '燐燑燒燓燔燕燖燗燘燙燚燛燜燝燞營';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '燠燡燢燣燤燥燦燧燨燩燪燫燬燭燮燯';
		$expects = '燠燡燢燣燤燥燦燧燨燩燪燫燬燭燮燯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '燰燱燲燳燴燵燶燷燸燹燺燻燼燽燾燿';
		$expects = '燰燱燲燳燴燵燶燷燸燹燺燻燼燽燾燿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '爀爁爂爃爄爅爆爇爈爉爊爋爌爍爎爏';
		$expects = '爀爁爂爃爄爅爆爇爈爉爊爋爌爍爎爏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '爐爑爒爓爔爕爖爗爘爙爚爛爜爝爞爟';
		$expects = '爐爑爒爓爔爕爖爗爘爙爚爛爜爝爞爟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '爠爡爢爣爤爥爦爧爨爩爪爫爬爭爮爯';
		$expects = '爠爡爢爣爤爥爦爧爨爩爪爫爬爭爮爯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '爰爱爲爳爴爵父爷爸爹爺爻爼爽爾爿';
		$expects = '爰爱爲爳爴爵父爷爸爹爺爻爼爽爾爿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '牀牁牂牃牄牅牆片版牉牊牋牌牍牎牏';
		$expects = '牀牁牂牃牄牅牆片版牉牊牋牌牍牎牏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '牐牑牒牓牔牕牖牗牘牙牚牛牜牝牞牟';
		$expects = '牐牑牒牓牔牕牖牗牘牙牚牛牜牝牞牟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '牠牡牢牣牤牥牦牧牨物牪牫牬牭牮牯';
		$expects = '牠牡牢牣牤牥牦牧牨物牪牫牬牭牮牯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '牰牱牲牳牴牵牶牷牸特牺牻牼牽牾牿';
		$expects = '牰牱牲牳牴牵牶牷牸特牺牻牼牽牾牿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '犀犁犂犃犄犅犆犇犈犉犊犋犌犍犎犏';
		$expects = '犀犁犂犃犄犅犆犇犈犉犊犋犌犍犎犏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '犐犑犒犓犔犕犖犗犘犙犚犛犜犝犞犟';
		$expects = '犐犑犒犓犔犕犖犗犘犙犚犛犜犝犞犟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '犠犡犢犣犤犥犦犧犨犩犪犫犬犭犮犯';
		$expects = '犠犡犢犣犤犥犦犧犨犩犪犫犬犭犮犯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '犰犱犲犳犴犵状犷犸犹犺犻犼犽犾犿';
		$expects = '犰犱犲犳犴犵状犷犸犹犺犻犼犽犾犿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '狀狁狂狃狄狅狆狇狈狉狊狋狌狍狎狏';
		$expects = '狀狁狂狃狄狅狆狇狈狉狊狋狌狍狎狏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '狐狑狒狓狔狕狖狗狘狙狚狛狜狝狞狟';
		$expects = '狐狑狒狓狔狕狖狗狘狙狚狛狜狝狞狟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '狠狡狢狣狤狥狦狧狨狩狪狫独狭狮狯';
		$expects = '狠狡狢狣狤狥狦狧狨狩狪狫独狭狮狯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '狰狱狲狳狴狵狶狷狸狹狺狻狼狽狾狿';
		$expects = '狰狱狲狳狴狵狶狷狸狹狺狻狼狽狾狿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '猀猁猂猃猄猅猆猇猈猉猊猋猌猍猎猏';
		$expects = '猀猁猂猃猄猅猆猇猈猉猊猋猌猍猎猏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '猐猑猒猓猔猕猖猗猘猙猚猛猜猝猞猟';
		$expects = '猐猑猒猓猔猕猖猗猘猙猚猛猜猝猞猟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '猠猡猢猣猤猥猦猧猨猩猪猫猬猭献猯';
		$expects = '猠猡猢猣猤猥猦猧猨猩猪猫猬猭献猯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '猰猱猲猳猴猵猶猷猸猹猺猻猼猽猾猿';
		$expects = '猰猱猲猳猴猵猶猷猸猹猺猻猼猽猾猿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '獀獁獂獃獄獅獆獇獈獉獊獋獌獍獎獏';
		$expects = '獀獁獂獃獄獅獆獇獈獉獊獋獌獍獎獏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '獐獑獒獓獔獕獖獗獘獙獚獛獜獝獞獟';
		$expects = '獐獑獒獓獔獕獖獗獘獙獚獛獜獝獞獟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '獠獡獢獣獤獥獦獧獨獩獪獫獬獭獮獯';
		$expects = '獠獡獢獣獤獥獦獧獨獩獪獫獬獭獮獯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '獰獱獲獳獴獵獶獷獸獹獺獻獼獽獾獿';
		$expects = '獰獱獲獳獴獵獶獷獸獹獺獻獼獽獾獿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '玀玁玂玃玄玅玆率玈玉玊王玌玍玎玏';
		$expects = '玀玁玂玃玄玅玆率玈玉玊王玌玍玎玏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '玐玑玒玓玔玕玖玗玘玙玚玛玜玝玞玟';
		$expects = '玐玑玒玓玔玕玖玗玘玙玚玛玜玝玞玟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '玠玡玢玣玤玥玦玧玨玩玪玫玬玭玮环';
		$expects = '玠玡玢玣玤玥玦玧玨玩玪玫玬玭玮环';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '现玱玲玳玴玵玶玷玸玹玺玻玼玽玾玿';
		$expects = '现玱玲玳玴玵玶玷玸玹玺玻玼玽玾玿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '珀珁珂珃珄珅珆珇珈珉珊珋珌珍珎珏';
		$expects = '珀珁珂珃珄珅珆珇珈珉珊珋珌珍珎珏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '珐珑珒珓珔珕珖珗珘珙珚珛珜珝珞珟';
		$expects = '珐珑珒珓珔珕珖珗珘珙珚珛珜珝珞珟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '珠珡珢珣珤珥珦珧珨珩珪珫珬班珮珯';
		$expects = '珠珡珢珣珤珥珦珧珨珩珪珫珬班珮珯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '珰珱珲珳珴珵珶珷珸珹珺珻珼珽現珿';
		$expects = '珰珱珲珳珴珵珶珷珸珹珺珻珼珽現珿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '琀琁琂球琄琅理琇琈琉琊琋琌琍琎琏';
		$expects = '琀琁琂球琄琅理琇琈琉琊琋琌琍琎琏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '琐琑琒琓琔琕琖琗琘琙琚琛琜琝琞琟';
		$expects = '琐琑琒琓琔琕琖琗琘琙琚琛琜琝琞琟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '琠琡琢琣琤琥琦琧琨琩琪琫琬琭琮琯';
		$expects = '琠琡琢琣琤琥琦琧琨琩琪琫琬琭琮琯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '琰琱琲琳琴琵琶琷琸琹琺琻琼琽琾琿';
		$expects = '琰琱琲琳琴琵琶琷琸琹琺琻琼琽琾琿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瑀瑁瑂瑃瑄瑅瑆瑇瑈瑉瑊瑋瑌瑍瑎瑏';
		$expects = '瑀瑁瑂瑃瑄瑅瑆瑇瑈瑉瑊瑋瑌瑍瑎瑏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瑐瑑瑒瑓瑔瑕瑖瑗瑘瑙瑚瑛瑜瑝瑞瑟';
		$expects = '瑐瑑瑒瑓瑔瑕瑖瑗瑘瑙瑚瑛瑜瑝瑞瑟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瑠瑡瑢瑣瑤瑥瑦瑧瑨瑩瑪瑫瑬瑭瑮瑯';
		$expects = '瑠瑡瑢瑣瑤瑥瑦瑧瑨瑩瑪瑫瑬瑭瑮瑯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瑰瑱瑲瑳瑴瑵瑶瑷瑸瑹瑺瑻瑼瑽瑾瑿';
		$expects = '瑰瑱瑲瑳瑴瑵瑶瑷瑸瑹瑺瑻瑼瑽瑾瑿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '璀璁璂璃璄璅璆璇璈璉璊璋璌璍璎璏';
		$expects = '璀璁璂璃璄璅璆璇璈璉璊璋璌璍璎璏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '璐璑璒璓璔璕璖璗璘璙璚璛璜璝璞璟';
		$expects = '璐璑璒璓璔璕璖璗璘璙璚璛璜璝璞璟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '璠璡璢璣璤璥璦璧璨璩璪璫璬璭璮璯';
		$expects = '璠璡璢璣璤璥璦璧璨璩璪璫璬璭璮璯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '環璱璲璳璴璵璶璷璸璹璺璻璼璽璾璿';
		$expects = '環璱璲璳璴璵璶璷璸璹璺璻璼璽璾璿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瓀瓁瓂瓃瓄瓅瓆瓇瓈瓉瓊瓋瓌瓍瓎瓏';
		$expects = '瓀瓁瓂瓃瓄瓅瓆瓇瓈瓉瓊瓋瓌瓍瓎瓏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瓐瓑瓒瓓瓔瓕瓖瓗瓘瓙瓚瓛瓜瓝瓞瓟';
		$expects = '瓐瓑瓒瓓瓔瓕瓖瓗瓘瓙瓚瓛瓜瓝瓞瓟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瓠瓡瓢瓣瓤瓥瓦瓧瓨瓩瓪瓫瓬瓭瓮瓯';
		$expects = '瓠瓡瓢瓣瓤瓥瓦瓧瓨瓩瓪瓫瓬瓭瓮瓯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瓰瓱瓲瓳瓴瓵瓶瓷瓸瓹瓺瓻瓼瓽瓾瓿';
		$expects = '瓰瓱瓲瓳瓴瓵瓶瓷瓸瓹瓺瓻瓼瓽瓾瓿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '甀甁甂甃甄甅甆甇甈甉甊甋甌甍甎甏';
		$expects = '甀甁甂甃甄甅甆甇甈甉甊甋甌甍甎甏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '甐甑甒甓甔甕甖甗甘甙甚甛甜甝甞生';
		$expects = '甐甑甒甓甔甕甖甗甘甙甚甛甜甝甞生';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '甠甡產産甤甥甦甧用甩甪甫甬甭甮甯';
		$expects = '甠甡產産甤甥甦甧用甩甪甫甬甭甮甯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '田由甲申甴电甶男甸甹町画甼甽甾甿';
		$expects = '田由甲申甴电甶男甸甹町画甼甽甾甿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '畀畁畂畃畄畅畆畇畈畉畊畋界畍畎畏';
		$expects = '畀畁畂畃畄畅畆畇畈畉畊畋界畍畎畏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '畐畑畒畓畔畕畖畗畘留畚畛畜畝畞畟';
		$expects = '畐畑畒畓畔畕畖畗畘留畚畛畜畝畞畟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '畠畡畢畣畤略畦畧畨畩番畫畬畭畮畯';
		$expects = '畠畡畢畣畤略畦畧畨畩番畫畬畭畮畯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '異畱畲畳畴畵當畷畸畹畺畻畼畽畾畿';
		$expects = '異畱畲畳畴畵當畷畸畹畺畻畼畽畾畿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '疀疁疂疃疄疅疆疇疈疉疊疋疌疍疎疏';
		$expects = '疀疁疂疃疄疅疆疇疈疉疊疋疌疍疎疏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '疐疑疒疓疔疕疖疗疘疙疚疛疜疝疞疟';
		$expects = '疐疑疒疓疔疕疖疗疘疙疚疛疜疝疞疟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '疠疡疢疣疤疥疦疧疨疩疪疫疬疭疮疯';
		$expects = '疠疡疢疣疤疥疦疧疨疩疪疫疬疭疮疯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '疰疱疲疳疴疵疶疷疸疹疺疻疼疽疾疿';
		$expects = '疰疱疲疳疴疵疶疷疸疹疺疻疼疽疾疿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '痀痁痂痃痄病痆症痈痉痊痋痌痍痎痏';
		$expects = '痀痁痂痃痄病痆症痈痉痊痋痌痍痎痏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '痐痑痒痓痔痕痖痗痘痙痚痛痜痝痞痟';
		$expects = '痐痑痒痓痔痕痖痗痘痙痚痛痜痝痞痟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '痠痡痢痣痤痥痦痧痨痩痪痫痬痭痮痯';
		$expects = '痠痡痢痣痤痥痦痧痨痩痪痫痬痭痮痯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '痰痱痲痳痴痵痶痷痸痹痺痻痼痽痾痿';
		$expects = '痰痱痲痳痴痵痶痷痸痹痺痻痼痽痾痿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瘀瘁瘂瘃瘄瘅瘆瘇瘈瘉瘊瘋瘌瘍瘎瘏';
		$expects = '瘀瘁瘂瘃瘄瘅瘆瘇瘈瘉瘊瘋瘌瘍瘎瘏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瘐瘑瘒瘓瘔瘕瘖瘗瘘瘙瘚瘛瘜瘝瘞瘟';
		$expects = '瘐瘑瘒瘓瘔瘕瘖瘗瘘瘙瘚瘛瘜瘝瘞瘟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瘠瘡瘢瘣瘤瘥瘦瘧瘨瘩瘪瘫瘬瘭瘮瘯';
		$expects = '瘠瘡瘢瘣瘤瘥瘦瘧瘨瘩瘪瘫瘬瘭瘮瘯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瘰瘱瘲瘳瘴瘵瘶瘷瘸瘹瘺瘻瘼瘽瘾瘿';
		$expects = '瘰瘱瘲瘳瘴瘵瘶瘷瘸瘹瘺瘻瘼瘽瘾瘿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '癀癁療癃癄癅癆癇癈癉癊癋癌癍癎癏';
		$expects = '癀癁療癃癄癅癆癇癈癉癊癋癌癍癎癏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '癐癑癒癓癔癕癖癗癘癙癚癛癜癝癞癟';
		$expects = '癐癑癒癓癔癕癖癗癘癙癚癛癜癝癞癟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '癠癡癢癣癤癥癦癧癨癩癪癫癬癭癮癯';
		$expects = '癠癡癢癣癤癥癦癧癨癩癪癫癬癭癮癯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '癰癱癲癳癴癵癶癷癸癹発登發白百癿';
		$expects = '癰癱癲癳癴癵癶癷癸癹発登發白百癿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '皀皁皂皃的皅皆皇皈皉皊皋皌皍皎皏';
		$expects = '皀皁皂皃的皅皆皇皈皉皊皋皌皍皎皏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '皐皑皒皓皔皕皖皗皘皙皚皛皜皝皞皟';
		$expects = '皐皑皒皓皔皕皖皗皘皙皚皛皜皝皞皟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '皠皡皢皣皤皥皦皧皨皩皪皫皬皭皮皯';
		$expects = '皠皡皢皣皤皥皦皧皨皩皪皫皬皭皮皯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '皰皱皲皳皴皵皶皷皸皹皺皻皼皽皾皿';
		$expects = '皰皱皲皳皴皵皶皷皸皹皺皻皼皽皾皿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '盀盁盂盃盄盅盆盇盈盉益盋盌盍盎盏';
		$expects = '盀盁盂盃盄盅盆盇盈盉益盋盌盍盎盏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '盐监盒盓盔盕盖盗盘盙盚盛盜盝盞盟';
		$expects = '盐监盒盓盔盕盖盗盘盙盚盛盜盝盞盟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '盠盡盢監盤盥盦盧盨盩盪盫盬盭目盯';
		$expects = '盠盡盢監盤盥盦盧盨盩盪盫盬盭目盯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '盰盱盲盳直盵盶盷相盹盺盻盼盽盾盿';
		$expects = '盰盱盲盳直盵盶盷相盹盺盻盼盽盾盿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '眀省眂眃眄眅眆眇眈眉眊看県眍眎眏';
		$expects = '眀省眂眃眄眅眆眇眈眉眊看県眍眎眏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '眐眑眒眓眔眕眖眗眘眙眚眛眜眝眞真';
		$expects = '眐眑眒眓眔眕眖眗眘眙眚眛眜眝眞真';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '眠眡眢眣眤眥眦眧眨眩眪眫眬眭眮眯';
		$expects = '眠眡眢眣眤眥眦眧眨眩眪眫眬眭眮眯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '眰眱眲眳眴眵眶眷眸眹眺眻眼眽眾眿';
		$expects = '眰眱眲眳眴眵眶眷眸眹眺眻眼眽眾眿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '着睁睂睃睄睅睆睇睈睉睊睋睌睍睎睏';
		$expects = '着睁睂睃睄睅睆睇睈睉睊睋睌睍睎睏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '睐睑睒睓睔睕睖睗睘睙睚睛睜睝睞睟';
		$expects = '睐睑睒睓睔睕睖睗睘睙睚睛睜睝睞睟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '睠睡睢督睤睥睦睧睨睩睪睫睬睭睮睯';
		$expects = '睠睡睢督睤睥睦睧睨睩睪睫睬睭睮睯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '睰睱睲睳睴睵睶睷睸睹睺睻睼睽睾睿';
		$expects = '睰睱睲睳睴睵睶睷睸睹睺睻睼睽睾睿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瞀瞁瞂瞃瞄瞅瞆瞇瞈瞉瞊瞋瞌瞍瞎瞏';
		$expects = '瞀瞁瞂瞃瞄瞅瞆瞇瞈瞉瞊瞋瞌瞍瞎瞏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瞐瞑瞒瞓瞔瞕瞖瞗瞘瞙瞚瞛瞜瞝瞞瞟';
		$expects = '瞐瞑瞒瞓瞔瞕瞖瞗瞘瞙瞚瞛瞜瞝瞞瞟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瞠瞡瞢瞣瞤瞥瞦瞧瞨瞩瞪瞫瞬瞭瞮瞯';
		$expects = '瞠瞡瞢瞣瞤瞥瞦瞧瞨瞩瞪瞫瞬瞭瞮瞯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '瞰瞱瞲瞳瞴瞵瞶瞷瞸瞹瞺瞻瞼瞽瞾瞿';
		$expects = '瞰瞱瞲瞳瞴瞵瞶瞷瞸瞹瞺瞻瞼瞽瞾瞿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '矀矁矂矃矄矅矆矇矈矉矊矋矌矍矎矏';
		$expects = '矀矁矂矃矄矅矆矇矈矉矊矋矌矍矎矏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '矐矑矒矓矔矕矖矗矘矙矚矛矜矝矞矟';
		$expects = '矐矑矒矓矔矕矖矗矘矙矚矛矜矝矞矟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '矠矡矢矣矤知矦矧矨矩矪矫矬短矮矯';
		$expects = '矠矡矢矣矤知矦矧矨矩矪矫矬短矮矯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '矰矱矲石矴矵矶矷矸矹矺矻矼矽矾矿';
		$expects = '矰矱矲石矴矵矶矷矸矹矺矻矼矽矾矿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '砀码砂砃砄砅砆砇砈砉砊砋砌砍砎砏';
		$expects = '砀码砂砃砄砅砆砇砈砉砊砋砌砍砎砏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '砐砑砒砓研砕砖砗砘砙砚砛砜砝砞砟';
		$expects = '砐砑砒砓研砕砖砗砘砙砚砛砜砝砞砟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '砠砡砢砣砤砥砦砧砨砩砪砫砬砭砮砯';
		$expects = '砠砡砢砣砤砥砦砧砨砩砪砫砬砭砮砯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '砰砱砲砳破砵砶砷砸砹砺砻砼砽砾砿';
		$expects = '砰砱砲砳破砵砶砷砸砹砺砻砼砽砾砿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '础硁硂硃硄硅硆硇硈硉硊硋硌硍硎硏';
		$expects = '础硁硂硃硄硅硆硇硈硉硊硋硌硍硎硏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '硐硑硒硓硔硕硖硗硘硙硚硛硜硝硞硟';
		$expects = '硐硑硒硓硔硕硖硗硘硙硚硛硜硝硞硟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '硠硡硢硣硤硥硦硧硨硩硪硫硬硭确硯';
		$expects = '硠硡硢硣硤硥硦硧硨硩硪硫硬硭确硯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '硰硱硲硳硴硵硶硷硸硹硺硻硼硽硾硿';
		$expects = '硰硱硲硳硴硵硶硷硸硹硺硻硼硽硾硿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '碀碁碂碃碄碅碆碇碈碉碊碋碌碍碎碏';
		$expects = '碀碁碂碃碄碅碆碇碈碉碊碋碌碍碎碏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '碐碑碒碓碔碕碖碗碘碙碚碛碜碝碞碟';
		$expects = '碐碑碒碓碔碕碖碗碘碙碚碛碜碝碞碟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '碠碡碢碣碤碥碦碧碨碩碪碫碬碭碮碯';
		$expects = '碠碡碢碣碤碥碦碧碨碩碪碫碬碭碮碯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '碰碱碲碳碴碵碶碷碸碹確碻碼碽碾碿';
		$expects = '碰碱碲碳碴碵碶碷碸碹確碻碼碽碾碿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '磀磁磂磃磄磅磆磇磈磉磊磋磌磍磎磏';
		$expects = '磀磁磂磃磄磅磆磇磈磉磊磋磌磍磎磏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '磐磑磒磓磔磕磖磗磘磙磚磛磜磝磞磟';
		$expects = '磐磑磒磓磔磕磖磗磘磙磚磛磜磝磞磟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '磠磡磢磣磤磥磦磧磨磩磪磫磬磭磮磯';
		$expects = '磠磡磢磣磤磥磦磧磨磩磪磫磬磭磮磯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '磰磱磲磳磴磵磶磷磸磹磺磻磼磽磾磿';
		$expects = '磰磱磲磳磴磵磶磷磸磹磺磻磼磽磾磿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '礀礁礂礃礄礅礆礇礈礉礊礋礌礍礎礏';
		$expects = '礀礁礂礃礄礅礆礇礈礉礊礋礌礍礎礏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '礐礑礒礓礔礕礖礗礘礙礚礛礜礝礞礟';
		$expects = '礐礑礒礓礔礕礖礗礘礙礚礛礜礝礞礟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '礠礡礢礣礤礥礦礧礨礩礪礫礬礭礮礯';
		$expects = '礠礡礢礣礤礥礦礧礨礩礪礫礬礭礮礯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '礰礱礲礳礴礵礶礷礸礹示礻礼礽社礿';
		$expects = '礰礱礲礳礴礵礶礷礸礹示礻礼礽社礿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '祀祁祂祃祄祅祆祇祈祉祊祋祌祍祎祏';
		$expects = '祀祁祂祃祄祅祆祇祈祉祊祋祌祍祎祏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '祐祑祒祓祔祕祖祗祘祙祚祛祜祝神祟';
		$expects = '祐祑祒祓祔祕祖祗祘祙祚祛祜祝神祟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '祠祡祢祣祤祥祦祧票祩祪祫祬祭祮祯';
		$expects = '祠祡祢祣祤祥祦祧票祩祪祫祬祭祮祯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '祰祱祲祳祴祵祶祷祸祹祺祻祼祽祾祿';
		$expects = '祰祱祲祳祴祵祶祷祸祹祺祻祼祽祾祿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '禀禁禂禃禄禅禆禇禈禉禊禋禌禍禎福';
		$expects = '禀禁禂禃禄禅禆禇禈禉禊禋禌禍禎福';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '禐禑禒禓禔禕禖禗禘禙禚禛禜禝禞禟';
		$expects = '禐禑禒禓禔禕禖禗禘禙禚禛禜禝禞禟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '禠禡禢禣禤禥禦禧禨禩禪禫禬禭禮禯';
		$expects = '禠禡禢禣禤禥禦禧禨禩禪禫禬禭禮禯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '禰禱禲禳禴禵禶禷禸禹禺离禼禽禾禿';
		$expects = '禰禱禲禳禴禵禶禷禸禹禺离禼禽禾禿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '秀私秂秃秄秅秆秇秈秉秊秋秌种秎秏';
		$expects = '秀私秂秃秄秅秆秇秈秉秊秋秌种秎秏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '秐科秒秓秔秕秖秗秘秙秚秛秜秝秞租';
		$expects = '秐科秒秓秔秕秖秗秘秙秚秛秜秝秞租';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '秠秡秢秣秤秥秦秧秨秩秪秫秬秭秮积';
		$expects = '秠秡秢秣秤秥秦秧秨秩秪秫秬秭秮积';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '称秱秲秳秴秵秶秷秸秹秺移秼秽秾秿';
		$expects = '称秱秲秳秴秵秶秷秸秹秺移秼秽秾秿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '稀稁稂稃稄稅稆稇稈稉稊程稌稍税稏';
		$expects = '稀稁稂稃稄稅稆稇稈稉稊程稌稍税稏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '稐稑稒稓稔稕稖稗稘稙稚稛稜稝稞稟';
		$expects = '稐稑稒稓稔稕稖稗稘稙稚稛稜稝稞稟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '稠稡稢稣稤稥稦稧稨稩稪稫稬稭種稯';
		$expects = '稠稡稢稣稤稥稦稧稨稩稪稫稬稭種稯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '稰稱稲稳稴稵稶稷稸稹稺稻稼稽稾稿';
		$expects = '稰稱稲稳稴稵稶稷稸稹稺稻稼稽稾稿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '穀穁穂穃穄穅穆穇穈穉穊穋穌積穎穏';
		$expects = '穀穁穂穃穄穅穆穇穈穉穊穋穌積穎穏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '穐穑穒穓穔穕穖穗穘穙穚穛穜穝穞穟';
		$expects = '穐穑穒穓穔穕穖穗穘穙穚穛穜穝穞穟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '穠穡穢穣穤穥穦穧穨穩穪穫穬穭穮穯';
		$expects = '穠穡穢穣穤穥穦穧穨穩穪穫穬穭穮穯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '穰穱穲穳穴穵究穷穸穹空穻穼穽穾穿';
		$expects = '穰穱穲穳穴穵究穷穸穹空穻穼穽穾穿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '窀突窂窃窄窅窆窇窈窉窊窋窌窍窎窏';
		$expects = '窀突窂窃窄窅窆窇窈窉窊窋窌窍窎窏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '窐窑窒窓窔窕窖窗窘窙窚窛窜窝窞窟';
		$expects = '窐窑窒窓窔窕窖窗窘窙窚窛窜窝窞窟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '窠窡窢窣窤窥窦窧窨窩窪窫窬窭窮窯';
		$expects = '窠窡窢窣窤窥窦窧窨窩窪窫窬窭窮窯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '窰窱窲窳窴窵窶窷窸窹窺窻窼窽窾窿';
		$expects = '窰窱窲窳窴窵窶窷窸窹窺窻窼窽窾窿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '竀竁竂竃竄竅竆竇竈竉竊立竌竍竎竏';
		$expects = '竀竁竂竃竄竅竆竇竈竉竊立竌竍竎竏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '竐竑竒竓竔竕竖竗竘站竚竛竜竝竞竟';
		$expects = '竐竑竒竓竔竕竖竗竘站竚竛竜竝竞竟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '章竡竢竣竤童竦竧竨竩竪竫竬竭竮端';
		$expects = '章竡竢竣竤童竦竧竨竩竪竫竬竭竮端';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '竰竱竲竳竴竵競竷竸竹竺竻竼竽竾竿';
		$expects = '竰竱竲竳竴竵競竷竸竹竺竻竼竽竾竿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '笀笁笂笃笄笅笆笇笈笉笊笋笌笍笎笏';
		$expects = '笀笁笂笃笄笅笆笇笈笉笊笋笌笍笎笏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '笐笑笒笓笔笕笖笗笘笙笚笛笜笝笞笟';
		$expects = '笐笑笒笓笔笕笖笗笘笙笚笛笜笝笞笟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '笠笡笢笣笤笥符笧笨笩笪笫第笭笮笯';
		$expects = '笠笡笢笣笤笥符笧笨笩笪笫第笭笮笯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '笰笱笲笳笴笵笶笷笸笹笺笻笼笽笾笿';
		$expects = '笰笱笲笳笴笵笶笷笸笹笺笻笼笽笾笿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '筀筁筂筃筄筅筆筇筈等筊筋筌筍筎筏';
		$expects = '筀筁筂筃筄筅筆筇筈等筊筋筌筍筎筏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '筐筑筒筓答筕策筗筘筙筚筛筜筝筞筟';
		$expects = '筐筑筒筓答筕策筗筘筙筚筛筜筝筞筟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '筠筡筢筣筤筥筦筧筨筩筪筫筬筭筮筯';
		$expects = '筠筡筢筣筤筥筦筧筨筩筪筫筬筭筮筯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '筰筱筲筳筴筵筶筷筸筹筺筻筼筽签筿';
		$expects = '筰筱筲筳筴筵筶筷筸筹筺筻筼筽签筿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '简箁箂箃箄箅箆箇箈箉箊箋箌箍箎箏';
		$expects = '简箁箂箃箄箅箆箇箈箉箊箋箌箍箎箏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '箐箑箒箓箔箕箖算箘箙箚箛箜箝箞箟';
		$expects = '箐箑箒箓箔箕箖算箘箙箚箛箜箝箞箟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '箠管箢箣箤箥箦箧箨箩箪箫箬箭箮箯';
		$expects = '箠管箢箣箤箥箦箧箨箩箪箫箬箭箮箯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '箰箱箲箳箴箵箶箷箸箹箺箻箼箽箾箿';
		$expects = '箰箱箲箳箴箵箶箷箸箹箺箻箼箽箾箿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '節篁篂篃範篅篆篇篈築篊篋篌篍篎篏';
		$expects = '節篁篂篃範篅篆篇篈築篊篋篌篍篎篏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '篐篑篒篓篔篕篖篗篘篙篚篛篜篝篞篟';
		$expects = '篐篑篒篓篔篕篖篗篘篙篚篛篜篝篞篟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '篠篡篢篣篤篥篦篧篨篩篪篫篬篭篮篯';
		$expects = '篠篡篢篣篤篥篦篧篨篩篪篫篬篭篮篯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '篰篱篲篳篴篵篶篷篸篹篺篻篼篽篾篿';
		$expects = '篰篱篲篳篴篵篶篷篸篹篺篻篼篽篾篿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '簀簁簂簃簄簅簆簇簈簉簊簋簌簍簎簏';
		$expects = '簀簁簂簃簄簅簆簇簈簉簊簋簌簍簎簏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '簐簑簒簓簔簕簖簗簘簙簚簛簜簝簞簟';
		$expects = '簐簑簒簓簔簕簖簗簘簙簚簛簜簝簞簟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '簠簡簢簣簤簥簦簧簨簩簪簫簬簭簮簯';
		$expects = '簠簡簢簣簤簥簦簧簨簩簪簫簬簭簮簯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '簰簱簲簳簴簵簶簷簸簹簺簻簼簽簾簿';
		$expects = '簰簱簲簳簴簵簶簷簸簹簺簻簼簽簾簿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '籀籁籂籃籄籅籆籇籈籉籊籋籌籍籎籏';
		$expects = '籀籁籂籃籄籅籆籇籈籉籊籋籌籍籎籏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '籐籑籒籓籔籕籖籗籘籙籚籛籜籝籞籟';
		$expects = '籐籑籒籓籔籕籖籗籘籙籚籛籜籝籞籟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '籠籡籢籣籤籥籦籧籨籩籪籫籬籭籮籯';
		$expects = '籠籡籢籣籤籥籦籧籨籩籪籫籬籭籮籯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '籰籱籲米籴籵籶籷籸籹籺类籼籽籾籿';
		$expects = '籰籱籲米籴籵籶籷籸籹籺类籼籽籾籿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '粀粁粂粃粄粅粆粇粈粉粊粋粌粍粎粏';
		$expects = '粀粁粂粃粄粅粆粇粈粉粊粋粌粍粎粏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '粐粑粒粓粔粕粖粗粘粙粚粛粜粝粞粟';
		$expects = '粐粑粒粓粔粕粖粗粘粙粚粛粜粝粞粟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '粠粡粢粣粤粥粦粧粨粩粪粫粬粭粮粯';
		$expects = '粠粡粢粣粤粥粦粧粨粩粪粫粬粭粮粯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '粰粱粲粳粴粵粶粷粸粹粺粻粼粽精粿';
		$expects = '粰粱粲粳粴粵粶粷粸粹粺粻粼粽精粿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '糀糁糂糃糄糅糆糇糈糉糊糋糌糍糎糏';
		$expects = '糀糁糂糃糄糅糆糇糈糉糊糋糌糍糎糏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '糐糑糒糓糔糕糖糗糘糙糚糛糜糝糞糟';
		$expects = '糐糑糒糓糔糕糖糗糘糙糚糛糜糝糞糟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '糠糡糢糣糤糥糦糧糨糩糪糫糬糭糮糯';
		$expects = '糠糡糢糣糤糥糦糧糨糩糪糫糬糭糮糯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '糰糱糲糳糴糵糶糷糸糹糺系糼糽糾糿';
		$expects = '糰糱糲糳糴糵糶糷糸糹糺系糼糽糾糿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '紀紁紂紃約紅紆紇紈紉紊紋紌納紎紏';
		$expects = '紀紁紂紃約紅紆紇紈紉紊紋紌納紎紏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '紐紑紒紓純紕紖紗紘紙級紛紜紝紞紟';
		$expects = '紐紑紒紓純紕紖紗紘紙級紛紜紝紞紟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '素紡索紣紤紥紦紧紨紩紪紫紬紭紮累';
		$expects = '素紡索紣紤紥紦紧紨紩紪紫紬紭紮累';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '細紱紲紳紴紵紶紷紸紹紺紻紼紽紾紿';
		$expects = '細紱紲紳紴紵紶紷紸紹紺紻紼紽紾紿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '絀絁終絃組絅絆絇絈絉絊絋経絍絎絏';
		$expects = '絀絁終絃組絅絆絇絈絉絊絋経絍絎絏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '結絑絒絓絔絕絖絗絘絙絚絛絜絝絞絟';
		$expects = '結絑絒絓絔絕絖絗絘絙絚絛絜絝絞絟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '絠絡絢絣絤絥給絧絨絩絪絫絬絭絮絯';
		$expects = '絠絡絢絣絤絥給絧絨絩絪絫絬絭絮絯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '絰統絲絳絴絵絶絷絸絹絺絻絼絽絾絿';
		$expects = '絰統絲絳絴絵絶絷絸絹絺絻絼絽絾絿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '綀綁綂綃綄綅綆綇綈綉綊綋綌綍綎綏';
		$expects = '綀綁綂綃綄綅綆綇綈綉綊綋綌綍綎綏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '綐綑綒經綔綕綖綗綘継続綛綜綝綞綟';
		$expects = '綐綑綒經綔綕綖綗綘継続綛綜綝綞綟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '綠綡綢綣綤綥綦綧綨綩綪綫綬維綮綯';
		$expects = '綠綡綢綣綤綥綦綧綨綩綪綫綬維綮綯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '綰綱網綳綴綵綶綷綸綹綺綻綼綽綾綿';
		$expects = '綰綱網綳綴綵綶綷綸綹綺綻綼綽綾綿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '緀緁緂緃緄緅緆緇緈緉緊緋緌緍緎総';
		$expects = '緀緁緂緃緄緅緆緇緈緉緊緋緌緍緎総';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '緐緑緒緓緔緕緖緗緘緙線緛緜緝緞緟';
		$expects = '緐緑緒緓緔緕緖緗緘緙線緛緜緝緞緟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '締緡緢緣緤緥緦緧編緩緪緫緬緭緮緯';
		$expects = '締緡緢緣緤緥緦緧編緩緪緫緬緭緮緯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '緰緱緲緳練緵緶緷緸緹緺緻緼緽緾緿';
		$expects = '緰緱緲緳練緵緶緷緸緹緺緻緼緽緾緿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '縀縁縂縃縄縅縆縇縈縉縊縋縌縍縎縏';
		$expects = '縀縁縂縃縄縅縆縇縈縉縊縋縌縍縎縏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '縐縑縒縓縔縕縖縗縘縙縚縛縜縝縞縟';
		$expects = '縐縑縒縓縔縕縖縗縘縙縚縛縜縝縞縟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '縠縡縢縣縤縥縦縧縨縩縪縫縬縭縮縯';
		$expects = '縠縡縢縣縤縥縦縧縨縩縪縫縬縭縮縯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '縰縱縲縳縴縵縶縷縸縹縺縻縼總績縿';
		$expects = '縰縱縲縳縴縵縶縷縸縹縺縻縼總績縿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '繀繁繂繃繄繅繆繇繈繉繊繋繌繍繎繏';
		$expects = '繀繁繂繃繄繅繆繇繈繉繊繋繌繍繎繏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '繐繑繒繓織繕繖繗繘繙繚繛繜繝繞繟';
		$expects = '繐繑繒繓織繕繖繗繘繙繚繛繜繝繞繟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '繠繡繢繣繤繥繦繧繨繩繪繫繬繭繮繯';
		$expects = '繠繡繢繣繤繥繦繧繨繩繪繫繬繭繮繯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '繰繱繲繳繴繵繶繷繸繹繺繻繼繽繾繿';
		$expects = '繰繱繲繳繴繵繶繷繸繹繺繻繼繽繾繿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '纀纁纂纃纄纅纆纇纈纉纊纋續纍纎纏';
		$expects = '纀纁纂纃纄纅纆纇纈纉纊纋續纍纎纏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '纐纑纒纓纔纕纖纗纘纙纚纛纜纝纞纟';
		$expects = '纐纑纒纓纔纕纖纗纘纙纚纛纜纝纞纟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '纠纡红纣纤纥约级纨纩纪纫纬纭纮纯';
		$expects = '纠纡红纣纤纥约级纨纩纪纫纬纭纮纯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '纰纱纲纳纴纵纶纷纸纹纺纻纼纽纾线';
		$expects = '纰纱纲纳纴纵纶纷纸纹纺纻纼纽纾线';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '绀绁绂练组绅细织终绉绊绋绌绍绎经';
		$expects = '绀绁绂练组绅细织终绉绊绋绌绍绎经';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '绐绑绒结绔绕绖绗绘给绚绛络绝绞统';
		$expects = '绐绑绒结绔绕绖绗绘给绚绛络绝绞统';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '绠绡绢绣绤绥绦继绨绩绪绫绬续绮绯';
		$expects = '绠绡绢绣绤绥绦继绨绩绪绫绬续绮绯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '绰绱绲绳维绵绶绷绸绹绺绻综绽绾绿';
		$expects = '绰绱绲绳维绵绶绷绸绹绺绻综绽绾绿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '缀缁缂缃缄缅缆缇缈缉缊缋缌缍缎缏';
		$expects = '缀缁缂缃缄缅缆缇缈缉缊缋缌缍缎缏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '缐缑缒缓缔缕编缗缘缙缚缛缜缝缞缟';
		$expects = '缐缑缒缓缔缕编缗缘缙缚缛缜缝缞缟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '缠缡缢缣缤缥缦缧缨缩缪缫缬缭缮缯';
		$expects = '缠缡缢缣缤缥缦缧缨缩缪缫缬缭缮缯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '缰缱缲缳缴缵缶缷缸缹缺缻缼缽缾缿';
		$expects = '缰缱缲缳缴缵缶缷缸缹缺缻缼缽缾缿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '罀罁罂罃罄罅罆罇罈罉罊罋罌罍罎罏';
		$expects = '罀罁罂罃罄罅罆罇罈罉罊罋罌罍罎罏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '罐网罒罓罔罕罖罗罘罙罚罛罜罝罞罟';
		$expects = '罐网罒罓罔罕罖罗罘罙罚罛罜罝罞罟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '罠罡罢罣罤罥罦罧罨罩罪罫罬罭置罯';
		$expects = '罠罡罢罣罤罥罦罧罨罩罪罫罬罭置罯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '罰罱署罳罴罵罶罷罸罹罺罻罼罽罾罿';
		$expects = '罰罱署罳罴罵罶罷罸罹罺罻罼罽罾罿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '羀羁羂羃羄羅羆羇羈羉羊羋羌羍美羏';
		$expects = '羀羁羂羃羄羅羆羇羈羉羊羋羌羍美羏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '羐羑羒羓羔羕羖羗羘羙羚羛羜羝羞羟';
		$expects = '羐羑羒羓羔羕羖羗羘羙羚羛羜羝羞羟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '羠羡羢羣群羥羦羧羨義羪羫羬羭羮羯';
		$expects = '羠羡羢羣群羥羦羧羨義羪羫羬羭羮羯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '羰羱羲羳羴羵羶羷羸羹羺羻羼羽羾羿';
		$expects = '羰羱羲羳羴羵羶羷羸羹羺羻羼羽羾羿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '翀翁翂翃翄翅翆翇翈翉翊翋翌翍翎翏';
		$expects = '翀翁翂翃翄翅翆翇翈翉翊翋翌翍翎翏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '翐翑習翓翔翕翖翗翘翙翚翛翜翝翞翟';
		$expects = '翐翑習翓翔翕翖翗翘翙翚翛翜翝翞翟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '翠翡翢翣翤翥翦翧翨翩翪翫翬翭翮翯';
		$expects = '翠翡翢翣翤翥翦翧翨翩翪翫翬翭翮翯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '翰翱翲翳翴翵翶翷翸翹翺翻翼翽翾翿';
		$expects = '翰翱翲翳翴翵翶翷翸翹翺翻翼翽翾翿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSection8 method
	 *
	 * Testing characters 8000 - 8fff
	 *
	 * @return void
	 */
	public function testSection8() {
		$string = '耀老耂考耄者耆耇耈耉耊耋而耍耎耏';
		$expects = '耀老耂考耄者耆耇耈耉耊耋而耍耎耏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '耐耑耒耓耔耕耖耗耘耙耚耛耜耝耞耟';
		$expects = '耐耑耒耓耔耕耖耗耘耙耚耛耜耝耞耟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '耠耡耢耣耤耥耦耧耨耩耪耫耬耭耮耯';
		$expects = '耠耡耢耣耤耥耦耧耨耩耪耫耬耭耮耯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '耰耱耲耳耴耵耶耷耸耹耺耻耼耽耾耿';
		$expects = '耰耱耲耳耴耵耶耷耸耹耺耻耼耽耾耿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '聀聁聂聃聄聅聆聇聈聉聊聋职聍聎聏';
		$expects = '聀聁聂聃聄聅聆聇聈聉聊聋职聍聎聏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '聐聑聒聓联聕聖聗聘聙聚聛聜聝聞聟';
		$expects = '聐聑聒聓联聕聖聗聘聙聚聛聜聝聞聟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '聠聡聢聣聤聥聦聧聨聩聪聫聬聭聮聯';
		$expects = '聠聡聢聣聤聥聦聧聨聩聪聫聬聭聮聯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '聰聱聲聳聴聵聶職聸聹聺聻聼聽聾聿';
		$expects = '聰聱聲聳聴聵聶職聸聹聺聻聼聽聾聿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '肀肁肂肃肄肅肆肇肈肉肊肋肌肍肎肏';
		$expects = '肀肁肂肃肄肅肆肇肈肉肊肋肌肍肎肏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '肐肑肒肓肔肕肖肗肘肙肚肛肜肝肞肟';
		$expects = '肐肑肒肓肔肕肖肗肘肙肚肛肜肝肞肟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '肠股肢肣肤肥肦肧肨肩肪肫肬肭肮肯';
		$expects = '肠股肢肣肤肥肦肧肨肩肪肫肬肭肮肯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '肰肱育肳肴肵肶肷肸肹肺肻肼肽肾肿';
		$expects = '肰肱育肳肴肵肶肷肸肹肺肻肼肽肾肿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '胀胁胂胃胄胅胆胇胈胉胊胋背胍胎胏';
		$expects = '胀胁胂胃胄胅胆胇胈胉胊胋背胍胎胏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '胐胑胒胓胔胕胖胗胘胙胚胛胜胝胞胟';
		$expects = '胐胑胒胓胔胕胖胗胘胙胚胛胜胝胞胟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '胠胡胢胣胤胥胦胧胨胩胪胫胬胭胮胯';
		$expects = '胠胡胢胣胤胥胦胧胨胩胪胫胬胭胮胯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '胰胱胲胳胴胵胶胷胸胹胺胻胼能胾胿';
		$expects = '胰胱胲胳胴胵胶胷胸胹胺胻胼能胾胿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '脀脁脂脃脄脅脆脇脈脉脊脋脌脍脎脏';
		$expects = '脀脁脂脃脄脅脆脇脈脉脊脋脌脍脎脏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '脐脑脒脓脔脕脖脗脘脙脚脛脜脝脞脟';
		$expects = '脐脑脒脓脔脕脖脗脘脙脚脛脜脝脞脟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '脠脡脢脣脤脥脦脧脨脩脪脫脬脭脮脯';
		$expects = '脠脡脢脣脤脥脦脧脨脩脪脫脬脭脮脯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '脰脱脲脳脴脵脶脷脸脹脺脻脼脽脾脿';
		$expects = '脰脱脲脳脴脵脶脷脸脹脺脻脼脽脾脿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '腀腁腂腃腄腅腆腇腈腉腊腋腌腍腎腏';
		$expects = '腀腁腂腃腄腅腆腇腈腉腊腋腌腍腎腏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '腐腑腒腓腔腕腖腗腘腙腚腛腜腝腞腟';
		$expects = '腐腑腒腓腔腕腖腗腘腙腚腛腜腝腞腟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '腠腡腢腣腤腥腦腧腨腩腪腫腬腭腮腯';
		$expects = '腠腡腢腣腤腥腦腧腨腩腪腫腬腭腮腯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '腰腱腲腳腴腵腶腷腸腹腺腻腼腽腾腿';
		$expects = '腰腱腲腳腴腵腶腷腸腹腺腻腼腽腾腿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '膀膁膂膃膄膅膆膇膈膉膊膋膌膍膎膏';
		$expects = '膀膁膂膃膄膅膆膇膈膉膊膋膌膍膎膏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '膐膑膒膓膔膕膖膗膘膙膚膛膜膝膞膟';
		$expects = '膐膑膒膓膔膕膖膗膘膙膚膛膜膝膞膟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '膠膡膢膣膤膥膦膧膨膩膪膫膬膭膮膯';
		$expects = '膠膡膢膣膤膥膦膧膨膩膪膫膬膭膮膯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '膰膱膲膳膴膵膶膷膸膹膺膻膼膽膾膿';
		$expects = '膰膱膲膳膴膵膶膷膸膹膺膻膼膽膾膿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '臀臁臂臃臄臅臆臇臈臉臊臋臌臍臎臏';
		$expects = '臀臁臂臃臄臅臆臇臈臉臊臋臌臍臎臏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '臐臑臒臓臔臕臖臗臘臙臚臛臜臝臞臟';
		$expects = '臐臑臒臓臔臕臖臗臘臙臚臛臜臝臞臟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '臠臡臢臣臤臥臦臧臨臩自臫臬臭臮臯';
		$expects = '臠臡臢臣臤臥臦臧臨臩自臫臬臭臮臯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '臰臱臲至致臵臶臷臸臹臺臻臼臽臾臿';
		$expects = '臰臱臲至致臵臶臷臸臹臺臻臼臽臾臿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '舀舁舂舃舄舅舆與興舉舊舋舌舍舎舏';
		$expects = '舀舁舂舃舄舅舆與興舉舊舋舌舍舎舏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '舐舑舒舓舔舕舖舗舘舙舚舛舜舝舞舟';
		$expects = '舐舑舒舓舔舕舖舗舘舙舚舛舜舝舞舟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '舠舡舢舣舤舥舦舧舨舩航舫般舭舮舯';
		$expects = '舠舡舢舣舤舥舦舧舨舩航舫般舭舮舯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '舰舱舲舳舴舵舶舷舸船舺舻舼舽舾舿';
		$expects = '舰舱舲舳舴舵舶舷舸船舺舻舼舽舾舿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '艀艁艂艃艄艅艆艇艈艉艊艋艌艍艎艏';
		$expects = '艀艁艂艃艄艅艆艇艈艉艊艋艌艍艎艏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '艐艑艒艓艔艕艖艗艘艙艚艛艜艝艞艟';
		$expects = '艐艑艒艓艔艕艖艗艘艙艚艛艜艝艞艟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '艠艡艢艣艤艥艦艧艨艩艪艫艬艭艮良';
		$expects = '艠艡艢艣艤艥艦艧艨艩艪艫艬艭艮良';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '艰艱色艳艴艵艶艷艸艹艺艻艼艽艾艿';
		$expects = '艰艱色艳艴艵艶艷艸艹艺艻艼艽艾艿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '芀芁节芃芄芅芆芇芈芉芊芋芌芍芎芏';
		$expects = '芀芁节芃芄芅芆芇芈芉芊芋芌芍芎芏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '芐芑芒芓芔芕芖芗芘芙芚芛芜芝芞芟';
		$expects = '芐芑芒芓芔芕芖芗芘芙芚芛芜芝芞芟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '芠芡芢芣芤芥芦芧芨芩芪芫芬芭芮芯';
		$expects = '芠芡芢芣芤芥芦芧芨芩芪芫芬芭芮芯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '芰花芲芳芴芵芶芷芸芹芺芻芼芽芾芿';
		$expects = '芰花芲芳芴芵芶芷芸芹芺芻芼芽芾芿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '苀苁苂苃苄苅苆苇苈苉苊苋苌苍苎苏';
		$expects = '苀苁苂苃苄苅苆苇苈苉苊苋苌苍苎苏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '苐苑苒苓苔苕苖苗苘苙苚苛苜苝苞苟';
		$expects = '苐苑苒苓苔苕苖苗苘苙苚苛苜苝苞苟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '苠苡苢苣苤若苦苧苨苩苪苫苬苭苮苯';
		$expects = '苠苡苢苣苤若苦苧苨苩苪苫苬苭苮苯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '苰英苲苳苴苵苶苷苸苹苺苻苼苽苾苿';
		$expects = '苰英苲苳苴苵苶苷苸苹苺苻苼苽苾苿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '茀茁茂范茄茅茆茇茈茉茊茋茌茍茎茏';
		$expects = '茀茁茂范茄茅茆茇茈茉茊茋茌茍茎茏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '茐茑茒茓茔茕茖茗茘茙茚茛茜茝茞茟';
		$expects = '茐茑茒茓茔茕茖茗茘茙茚茛茜茝茞茟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '茠茡茢茣茤茥茦茧茨茩茪茫茬茭茮茯';
		$expects = '茠茡茢茣茤茥茦茧茨茩茪茫茬茭茮茯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '茰茱茲茳茴茵茶茷茸茹茺茻茼茽茾茿';
		$expects = '茰茱茲茳茴茵茶茷茸茹茺茻茼茽茾茿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '荀荁荂荃荄荅荆荇荈草荊荋荌荍荎荏';
		$expects = '荀荁荂荃荄荅荆荇荈草荊荋荌荍荎荏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '荐荑荒荓荔荕荖荗荘荙荚荛荜荝荞荟';
		$expects = '荐荑荒荓荔荕荖荗荘荙荚荛荜荝荞荟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '荠荡荢荣荤荥荦荧荨荩荪荫荬荭荮药';
		$expects = '荠荡荢荣荤荥荦荧荨荩荪荫荬荭荮药';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '荰荱荲荳荴荵荶荷荸荹荺荻荼荽荾荿';
		$expects = '荰荱荲荳荴荵荶荷荸荹荺荻荼荽荾荿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '莀莁莂莃莄莅莆莇莈莉莊莋莌莍莎莏';
		$expects = '莀莁莂莃莄莅莆莇莈莉莊莋莌莍莎莏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '莐莑莒莓莔莕莖莗莘莙莚莛莜莝莞莟';
		$expects = '莐莑莒莓莔莕莖莗莘莙莚莛莜莝莞莟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '莠莡莢莣莤莥莦莧莨莩莪莫莬莭莮莯';
		$expects = '莠莡莢莣莤莥莦莧莨莩莪莫莬莭莮莯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '莰莱莲莳莴莵莶获莸莹莺莻莼莽莾莿';
		$expects = '莰莱莲莳莴莵莶获莸莹莺莻莼莽莾莿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '菀菁菂菃菄菅菆菇菈菉菊菋菌菍菎菏';
		$expects = '菀菁菂菃菄菅菆菇菈菉菊菋菌菍菎菏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '菐菑菒菓菔菕菖菗菘菙菚菛菜菝菞菟';
		$expects = '菐菑菒菓菔菕菖菗菘菙菚菛菜菝菞菟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '菠菡菢菣菤菥菦菧菨菩菪菫菬菭菮華';
		$expects = '菠菡菢菣菤菥菦菧菨菩菪菫菬菭菮華';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '菰菱菲菳菴菵菶菷菸菹菺菻菼菽菾菿';
		$expects = '菰菱菲菳菴菵菶菷菸菹菺菻菼菽菾菿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '萀萁萂萃萄萅萆萇萈萉萊萋萌萍萎萏';
		$expects = '萀萁萂萃萄萅萆萇萈萉萊萋萌萍萎萏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '萐萑萒萓萔萕萖萗萘萙萚萛萜萝萞萟';
		$expects = '萐萑萒萓萔萕萖萗萘萙萚萛萜萝萞萟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '萠萡萢萣萤营萦萧萨萩萪萫萬萭萮萯';
		$expects = '萠萡萢萣萤营萦萧萨萩萪萫萬萭萮萯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '萰萱萲萳萴萵萶萷萸萹萺萻萼落萾萿';
		$expects = '萰萱萲萳萴萵萶萷萸萹萺萻萼落萾萿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '葀葁葂葃葄葅葆葇葈葉葊葋葌葍葎葏';
		$expects = '葀葁葂葃葄葅葆葇葈葉葊葋葌葍葎葏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '葐葑葒葓葔葕葖著葘葙葚葛葜葝葞葟';
		$expects = '葐葑葒葓葔葕葖著葘葙葚葛葜葝葞葟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '葠葡葢董葤葥葦葧葨葩葪葫葬葭葮葯';
		$expects = '葠葡葢董葤葥葦葧葨葩葪葫葬葭葮葯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '葰葱葲葳葴葵葶葷葸葹葺葻葼葽葾葿';
		$expects = '葰葱葲葳葴葵葶葷葸葹葺葻葼葽葾葿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蒀蒁蒂蒃蒄蒅蒆蒇蒈蒉蒊蒋蒌蒍蒎蒏';
		$expects = '蒀蒁蒂蒃蒄蒅蒆蒇蒈蒉蒊蒋蒌蒍蒎蒏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蒐蒑蒒蒓蒔蒕蒖蒗蒘蒙蒚蒛蒜蒝蒞蒟';
		$expects = '蒐蒑蒒蒓蒔蒕蒖蒗蒘蒙蒚蒛蒜蒝蒞蒟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蒠蒡蒢蒣蒤蒥蒦蒧蒨蒩蒪蒫蒬蒭蒮蒯';
		$expects = '蒠蒡蒢蒣蒤蒥蒦蒧蒨蒩蒪蒫蒬蒭蒮蒯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蒰蒱蒲蒳蒴蒵蒶蒷蒸蒹蒺蒻蒼蒽蒾蒿';
		$expects = '蒰蒱蒲蒳蒴蒵蒶蒷蒸蒹蒺蒻蒼蒽蒾蒿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蓀蓁蓂蓃蓄蓅蓆蓇蓈蓉蓊蓋蓌蓍蓎蓏';
		$expects = '蓀蓁蓂蓃蓄蓅蓆蓇蓈蓉蓊蓋蓌蓍蓎蓏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蓐蓑蓒蓓蓔蓕蓖蓗蓘蓙蓚蓛蓜蓝蓞蓟';
		$expects = '蓐蓑蓒蓓蓔蓕蓖蓗蓘蓙蓚蓛蓜蓝蓞蓟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蓠蓡蓢蓣蓤蓥蓦蓧蓨蓩蓪蓫蓬蓭蓮蓯';
		$expects = '蓠蓡蓢蓣蓤蓥蓦蓧蓨蓩蓪蓫蓬蓭蓮蓯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蓰蓱蓲蓳蓴蓵蓶蓷蓸蓹蓺蓻蓼蓽蓾蓿';
		$expects = '蓰蓱蓲蓳蓴蓵蓶蓷蓸蓹蓺蓻蓼蓽蓾蓿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蔀蔁蔂蔃蔄蔅蔆蔇蔈蔉蔊蔋蔌蔍蔎蔏';
		$expects = '蔀蔁蔂蔃蔄蔅蔆蔇蔈蔉蔊蔋蔌蔍蔎蔏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蔐蔑蔒蔓蔔蔕蔖蔗蔘蔙蔚蔛蔜蔝蔞蔟';
		$expects = '蔐蔑蔒蔓蔔蔕蔖蔗蔘蔙蔚蔛蔜蔝蔞蔟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蔠蔡蔢蔣蔤蔥蔦蔧蔨蔩蔪蔫蔬蔭蔮蔯';
		$expects = '蔠蔡蔢蔣蔤蔥蔦蔧蔨蔩蔪蔫蔬蔭蔮蔯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蔰蔱蔲蔳蔴蔵蔶蔷蔸蔹蔺蔻蔼蔽蔾蔿';
		$expects = '蔰蔱蔲蔳蔴蔵蔶蔷蔸蔹蔺蔻蔼蔽蔾蔿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蕀蕁蕂蕃蕄蕅蕆蕇蕈蕉蕊蕋蕌蕍蕎蕏';
		$expects = '蕀蕁蕂蕃蕄蕅蕆蕇蕈蕉蕊蕋蕌蕍蕎蕏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蕐蕑蕒蕓蕔蕕蕖蕗蕘蕙蕚蕛蕜蕝蕞蕟';
		$expects = '蕐蕑蕒蕓蕔蕕蕖蕗蕘蕙蕚蕛蕜蕝蕞蕟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蕠蕡蕢蕣蕤蕥蕦蕧蕨蕩蕪蕫蕬蕭蕮蕯';
		$expects = '蕠蕡蕢蕣蕤蕥蕦蕧蕨蕩蕪蕫蕬蕭蕮蕯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蕰蕱蕲蕳蕴蕵蕶蕷蕸蕹蕺蕻蕼蕽蕾蕿';
		$expects = '蕰蕱蕲蕳蕴蕵蕶蕷蕸蕹蕺蕻蕼蕽蕾蕿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '薀薁薂薃薄薅薆薇薈薉薊薋薌薍薎薏';
		$expects = '薀薁薂薃薄薅薆薇薈薉薊薋薌薍薎薏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '薐薑薒薓薔薕薖薗薘薙薚薛薜薝薞薟';
		$expects = '薐薑薒薓薔薕薖薗薘薙薚薛薜薝薞薟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '薠薡薢薣薤薥薦薧薨薩薪薫薬薭薮薯';
		$expects = '薠薡薢薣薤薥薦薧薨薩薪薫薬薭薮薯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '薰薱薲薳薴薵薶薷薸薹薺薻薼薽薾薿';
		$expects = '薰薱薲薳薴薵薶薷薸薹薺薻薼薽薾薿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '藀藁藂藃藄藅藆藇藈藉藊藋藌藍藎藏';
		$expects = '藀藁藂藃藄藅藆藇藈藉藊藋藌藍藎藏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '藐藑藒藓藔藕藖藗藘藙藚藛藜藝藞藟';
		$expects = '藐藑藒藓藔藕藖藗藘藙藚藛藜藝藞藟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '藠藡藢藣藤藥藦藧藨藩藪藫藬藭藮藯';
		$expects = '藠藡藢藣藤藥藦藧藨藩藪藫藬藭藮藯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '藰藱藲藳藴藵藶藷藸藹藺藻藼藽藾藿';
		$expects = '藰藱藲藳藴藵藶藷藸藹藺藻藼藽藾藿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蘀蘁蘂蘃蘄蘅蘆蘇蘈蘉蘊蘋蘌蘍蘎蘏';
		$expects = '蘀蘁蘂蘃蘄蘅蘆蘇蘈蘉蘊蘋蘌蘍蘎蘏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蘐蘑蘒蘓蘔蘕蘖蘗蘘蘙蘚蘛蘜蘝蘞蘟';
		$expects = '蘐蘑蘒蘓蘔蘕蘖蘗蘘蘙蘚蘛蘜蘝蘞蘟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蘠蘡蘢蘣蘤蘥蘦蘧蘨蘩蘪蘫蘬蘭蘮蘯';
		$expects = '蘠蘡蘢蘣蘤蘥蘦蘧蘨蘩蘪蘫蘬蘭蘮蘯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蘰蘱蘲蘳蘴蘵蘶蘷蘸蘹蘺蘻蘼蘽蘾蘿';
		$expects = '蘰蘱蘲蘳蘴蘵蘶蘷蘸蘹蘺蘻蘼蘽蘾蘿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '虀虁虂虃虄虅虆虇虈虉虊虋虌虍虎虏';
		$expects = '虀虁虂虃虄虅虆虇虈虉虊虋虌虍虎虏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '虐虑虒虓虔處虖虗虘虙虚虛虜虝虞號';
		$expects = '虐虑虒虓虔處虖虗虘虙虚虛虜虝虞號';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '虠虡虢虣虤虥虦虧虨虩虪虫虬虭虮虯';
		$expects = '虠虡虢虣虤虥虦虧虨虩虪虫虬虭虮虯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '虰虱虲虳虴虵虶虷虸虹虺虻虼虽虾虿';
		$expects = '虰虱虲虳虴虵虶虷虸虹虺虻虼虽虾虿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蚀蚁蚂蚃蚄蚅蚆蚇蚈蚉蚊蚋蚌蚍蚎蚏';
		$expects = '蚀蚁蚂蚃蚄蚅蚆蚇蚈蚉蚊蚋蚌蚍蚎蚏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蚐蚑蚒蚓蚔蚕蚖蚗蚘蚙蚚蚛蚜蚝蚞蚟';
		$expects = '蚐蚑蚒蚓蚔蚕蚖蚗蚘蚙蚚蚛蚜蚝蚞蚟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蚠蚡蚢蚣蚤蚥蚦蚧蚨蚩蚪蚫蚬蚭蚮蚯';
		$expects = '蚠蚡蚢蚣蚤蚥蚦蚧蚨蚩蚪蚫蚬蚭蚮蚯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蚰蚱蚲蚳蚴蚵蚶蚷蚸蚹蚺蚻蚼蚽蚾蚿';
		$expects = '蚰蚱蚲蚳蚴蚵蚶蚷蚸蚹蚺蚻蚼蚽蚾蚿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蛀蛁蛂蛃蛄蛅蛆蛇蛈蛉蛊蛋蛌蛍蛎蛏';
		$expects = '蛀蛁蛂蛃蛄蛅蛆蛇蛈蛉蛊蛋蛌蛍蛎蛏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蛐蛑蛒蛓蛔蛕蛖蛗蛘蛙蛚蛛蛜蛝蛞蛟';
		$expects = '蛐蛑蛒蛓蛔蛕蛖蛗蛘蛙蛚蛛蛜蛝蛞蛟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蛠蛡蛢蛣蛤蛥蛦蛧蛨蛩蛪蛫蛬蛭蛮蛯';
		$expects = '蛠蛡蛢蛣蛤蛥蛦蛧蛨蛩蛪蛫蛬蛭蛮蛯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蛰蛱蛲蛳蛴蛵蛶蛷蛸蛹蛺蛻蛼蛽蛾蛿';
		$expects = '蛰蛱蛲蛳蛴蛵蛶蛷蛸蛹蛺蛻蛼蛽蛾蛿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蜀蜁蜂蜃蜄蜅蜆蜇蜈蜉蜊蜋蜌蜍蜎蜏';
		$expects = '蜀蜁蜂蜃蜄蜅蜆蜇蜈蜉蜊蜋蜌蜍蜎蜏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蜐蜑蜒蜓蜔蜕蜖蜗蜘蜙蜚蜛蜜蜝蜞蜟';
		$expects = '蜐蜑蜒蜓蜔蜕蜖蜗蜘蜙蜚蜛蜜蜝蜞蜟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蜠蜡蜢蜣蜤蜥蜦蜧蜨蜩蜪蜫蜬蜭蜮蜯';
		$expects = '蜠蜡蜢蜣蜤蜥蜦蜧蜨蜩蜪蜫蜬蜭蜮蜯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蜰蜱蜲蜳蜴蜵蜶蜷蜸蜹蜺蜻蜼蜽蜾蜿';
		$expects = '蜰蜱蜲蜳蜴蜵蜶蜷蜸蜹蜺蜻蜼蜽蜾蜿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蝀蝁蝂蝃蝄蝅蝆蝇蝈蝉蝊蝋蝌蝍蝎蝏';
		$expects = '蝀蝁蝂蝃蝄蝅蝆蝇蝈蝉蝊蝋蝌蝍蝎蝏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蝐蝑蝒蝓蝔蝕蝖蝗蝘蝙蝚蝛蝜蝝蝞蝟';
		$expects = '蝐蝑蝒蝓蝔蝕蝖蝗蝘蝙蝚蝛蝜蝝蝞蝟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蝠蝡蝢蝣蝤蝥蝦蝧蝨蝩蝪蝫蝬蝭蝮蝯';
		$expects = '蝠蝡蝢蝣蝤蝥蝦蝧蝨蝩蝪蝫蝬蝭蝮蝯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蝰蝱蝲蝳蝴蝵蝶蝷蝸蝹蝺蝻蝼蝽蝾蝿';
		$expects = '蝰蝱蝲蝳蝴蝵蝶蝷蝸蝹蝺蝻蝼蝽蝾蝿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '螀螁螂螃螄螅螆螇螈螉螊螋螌融螎螏';
		$expects = '螀螁螂螃螄螅螆螇螈螉螊螋螌融螎螏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '螐螑螒螓螔螕螖螗螘螙螚螛螜螝螞螟';
		$expects = '螐螑螒螓螔螕螖螗螘螙螚螛螜螝螞螟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '螠螡螢螣螤螥螦螧螨螩螪螫螬螭螮螯';
		$expects = '螠螡螢螣螤螥螦螧螨螩螪螫螬螭螮螯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '螰螱螲螳螴螵螶螷螸螹螺螻螼螽螾螿';
		$expects = '螰螱螲螳螴螵螶螷螸螹螺螻螼螽螾螿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蟀蟁蟂蟃蟄蟅蟆蟇蟈蟉蟊蟋蟌蟍蟎蟏';
		$expects = '蟀蟁蟂蟃蟄蟅蟆蟇蟈蟉蟊蟋蟌蟍蟎蟏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蟐蟑蟒蟓蟔蟕蟖蟗蟘蟙蟚蟛蟜蟝蟞蟟';
		$expects = '蟐蟑蟒蟓蟔蟕蟖蟗蟘蟙蟚蟛蟜蟝蟞蟟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蟠蟡蟢蟣蟤蟥蟦蟧蟨蟩蟪蟫蟬蟭蟮蟯';
		$expects = '蟠蟡蟢蟣蟤蟥蟦蟧蟨蟩蟪蟫蟬蟭蟮蟯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蟰蟱蟲蟳蟴蟵蟶蟷蟸蟹蟺蟻蟼蟽蟾蟿';
		$expects = '蟰蟱蟲蟳蟴蟵蟶蟷蟸蟹蟺蟻蟼蟽蟾蟿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蠀蠁蠂蠃蠄蠅蠆蠇蠈蠉蠊蠋蠌蠍蠎蠏';
		$expects = '蠀蠁蠂蠃蠄蠅蠆蠇蠈蠉蠊蠋蠌蠍蠎蠏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蠐蠑蠒蠓蠔蠕蠖蠗蠘蠙蠚蠛蠜蠝蠞蠟';
		$expects = '蠐蠑蠒蠓蠔蠕蠖蠗蠘蠙蠚蠛蠜蠝蠞蠟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蠠蠡蠢蠣蠤蠥蠦蠧蠨蠩蠪蠫蠬蠭蠮蠯';
		$expects = '蠠蠡蠢蠣蠤蠥蠦蠧蠨蠩蠪蠫蠬蠭蠮蠯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蠰蠱蠲蠳蠴蠵蠶蠷蠸蠹蠺蠻蠼蠽蠾蠿';
		$expects = '蠰蠱蠲蠳蠴蠵蠶蠷蠸蠹蠺蠻蠼蠽蠾蠿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '血衁衂衃衄衅衆衇衈衉衊衋行衍衎衏';
		$expects = '血衁衂衃衄衅衆衇衈衉衊衋行衍衎衏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '衐衑衒術衔衕衖街衘衙衚衛衜衝衞衟';
		$expects = '衐衑衒術衔衕衖街衘衙衚衛衜衝衞衟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '衠衡衢衣衤补衦衧表衩衪衫衬衭衮衯';
		$expects = '衠衡衢衣衤补衦衧表衩衪衫衬衭衮衯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '衰衱衲衳衴衵衶衷衸衹衺衻衼衽衾衿';
		$expects = '衰衱衲衳衴衵衶衷衸衹衺衻衼衽衾衿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '袀袁袂袃袄袅袆袇袈袉袊袋袌袍袎袏';
		$expects = '袀袁袂袃袄袅袆袇袈袉袊袋袌袍袎袏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '袐袑袒袓袔袕袖袗袘袙袚袛袜袝袞袟';
		$expects = '袐袑袒袓袔袕袖袗袘袙袚袛袜袝袞袟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '袠袡袢袣袤袥袦袧袨袩袪被袬袭袮袯';
		$expects = '袠袡袢袣袤袥袦袧袨袩袪被袬袭袮袯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '袰袱袲袳袴袵袶袷袸袹袺袻袼袽袾袿';
		$expects = '袰袱袲袳袴袵袶袷袸袹袺袻袼袽袾袿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '裀裁裂裃裄装裆裇裈裉裊裋裌裍裎裏';
		$expects = '裀裁裂裃裄装裆裇裈裉裊裋裌裍裎裏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '裐裑裒裓裔裕裖裗裘裙裚裛補裝裞裟';
		$expects = '裐裑裒裓裔裕裖裗裘裙裚裛補裝裞裟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '裠裡裢裣裤裥裦裧裨裩裪裫裬裭裮裯';
		$expects = '裠裡裢裣裤裥裦裧裨裩裪裫裬裭裮裯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '裰裱裲裳裴裵裶裷裸裹裺裻裼製裾裿';
		$expects = '裰裱裲裳裴裵裶裷裸裹裺裻裼製裾裿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '褀褁褂褃褄褅褆複褈褉褊褋褌褍褎褏';
		$expects = '褀褁褂褃褄褅褆複褈褉褊褋褌褍褎褏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '褐褑褒褓褔褕褖褗褘褙褚褛褜褝褞褟';
		$expects = '褐褑褒褓褔褕褖褗褘褙褚褛褜褝褞褟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '褠褡褢褣褤褥褦褧褨褩褪褫褬褭褮褯';
		$expects = '褠褡褢褣褤褥褦褧褨褩褪褫褬褭褮褯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '褰褱褲褳褴褵褶褷褸褹褺褻褼褽褾褿';
		$expects = '褰褱褲褳褴褵褶褷褸褹褺褻褼褽褾褿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '襀襁襂襃襄襅襆襇襈襉襊襋襌襍襎襏';
		$expects = '襀襁襂襃襄襅襆襇襈襉襊襋襌襍襎襏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '襐襑襒襓襔襕襖襗襘襙襚襛襜襝襞襟';
		$expects = '襐襑襒襓襔襕襖襗襘襙襚襛襜襝襞襟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '襠襡襢襣襤襥襦襧襨襩襪襫襬襭襮襯';
		$expects = '襠襡襢襣襤襥襦襧襨襩襪襫襬襭襮襯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '襰襱襲襳襴襵襶襷襸襹襺襻襼襽襾西';
		$expects = '襰襱襲襳襴襵襶襷襸襹襺襻襼襽襾西';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '覀要覂覃覄覅覆覇覈覉覊見覌覍覎規';
		$expects = '覀要覂覃覄覅覆覇覈覉覊見覌覍覎規';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '覐覑覒覓覔覕視覗覘覙覚覛覜覝覞覟';
		$expects = '覐覑覒覓覔覕視覗覘覙覚覛覜覝覞覟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '覠覡覢覣覤覥覦覧覨覩親覫覬覭覮覯';
		$expects = '覠覡覢覣覤覥覦覧覨覩親覫覬覭覮覯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '覰覱覲観覴覵覶覷覸覹覺覻覼覽覾覿';
		$expects = '覰覱覲観覴覵覶覷覸覹覺覻覼覽覾覿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '觀见观觃规觅视觇览觉觊觋觌觍觎觏';
		$expects = '觀见观觃规觅视觇览觉觊觋觌觍觎觏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '觐觑角觓觔觕觖觗觘觙觚觛觜觝觞觟';
		$expects = '觐觑角觓觔觕觖觗觘觙觚觛觜觝觞觟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '觠觡觢解觤觥触觧觨觩觪觫觬觭觮觯';
		$expects = '觠觡觢解觤觥触觧觨觩觪觫觬觭觮觯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '觰觱觲觳觴觵觶觷觸觹觺觻觼觽觾觿';
		$expects = '觰觱觲觳觴觵觶觷觸觹觺觻觼觽觾觿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '言訁訂訃訄訅訆訇計訉訊訋訌訍討訏';
		$expects = '言訁訂訃訄訅訆訇計訉訊訋訌訍討訏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '訐訑訒訓訔訕訖託記訙訚訛訜訝訞訟';
		$expects = '訐訑訒訓訔訕訖託記訙訚訛訜訝訞訟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '訠訡訢訣訤訥訦訧訨訩訪訫訬設訮訯';
		$expects = '訠訡訢訣訤訥訦訧訨訩訪訫訬設訮訯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '訰許訲訳訴訵訶訷訸訹診註証訽訾訿';
		$expects = '訰許訲訳訴訵訶訷訸訹診註証訽訾訿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '詀詁詂詃詄詅詆詇詈詉詊詋詌詍詎詏';
		$expects = '詀詁詂詃詄詅詆詇詈詉詊詋詌詍詎詏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '詐詑詒詓詔評詖詗詘詙詚詛詜詝詞詟';
		$expects = '詐詑詒詓詔評詖詗詘詙詚詛詜詝詞詟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '詠詡詢詣詤詥試詧詨詩詪詫詬詭詮詯';
		$expects = '詠詡詢詣詤詥試詧詨詩詪詫詬詭詮詯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '詰話該詳詴詵詶詷詸詹詺詻詼詽詾詿';
		$expects = '詰話該詳詴詵詶詷詸詹詺詻詼詽詾詿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '誀誁誂誃誄誅誆誇誈誉誊誋誌認誎誏';
		$expects = '誀誁誂誃誄誅誆誇誈誉誊誋誌認誎誏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '誐誑誒誓誔誕誖誗誘誙誚誛誜誝語誟';
		$expects = '誐誑誒誓誔誕誖誗誘誙誚誛誜誝語誟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '誠誡誢誣誤誥誦誧誨誩說誫説読誮誯';
		$expects = '誠誡誢誣誤誥誦誧誨誩說誫説読誮誯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '誰誱課誳誴誵誶誷誸誹誺誻誼誽誾調';
		$expects = '誰誱課誳誴誵誶誷誸誹誺誻誼誽誾調';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '諀諁諂諃諄諅諆談諈諉諊請諌諍諎諏';
		$expects = '諀諁諂諃諄諅諆談諈諉諊請諌諍諎諏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '諐諑諒諓諔諕論諗諘諙諚諛諜諝諞諟';
		$expects = '諐諑諒諓諔諕論諗諘諙諚諛諜諝諞諟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '諠諡諢諣諤諥諦諧諨諩諪諫諬諭諮諯';
		$expects = '諠諡諢諣諤諥諦諧諨諩諪諫諬諭諮諯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '諰諱諲諳諴諵諶諷諸諹諺諻諼諽諾諿';
		$expects = '諰諱諲諳諴諵諶諷諸諹諺諻諼諽諾諿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '謀謁謂謃謄謅謆謇謈謉謊謋謌謍謎謏';
		$expects = '謀謁謂謃謄謅謆謇謈謉謊謋謌謍謎謏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '謐謑謒謓謔謕謖謗謘謙謚講謜謝謞謟';
		$expects = '謐謑謒謓謔謕謖謗謘謙謚講謜謝謞謟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '謠謡謢謣謤謥謦謧謨謩謪謫謬謭謮謯';
		$expects = '謠謡謢謣謤謥謦謧謨謩謪謫謬謭謮謯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '謰謱謲謳謴謵謶謷謸謹謺謻謼謽謾謿';
		$expects = '謰謱謲謳謴謵謶謷謸謹謺謻謼謽謾謿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '譀譁譂譃譄譅譆譇譈證譊譋譌譍譎譏';
		$expects = '譀譁譂譃譄譅譆譇譈證譊譋譌譍譎譏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '譐譑譒譓譔譕譖譗識譙譚譛譜譝譞譟';
		$expects = '譐譑譒譓譔譕譖譗識譙譚譛譜譝譞譟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '譠譡譢譣譤譥警譧譨譩譪譫譬譭譮譯';
		$expects = '譠譡譢譣譤譥警譧譨譩譪譫譬譭譮譯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '議譱譲譳譴譵譶護譸譹譺譻譼譽譾譿';
		$expects = '議譱譲譳譴譵譶護譸譹譺譻譼譽譾譿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '讀讁讂讃讄讅讆讇讈讉變讋讌讍讎讏';
		$expects = '讀讁讂讃讄讅讆讇讈讉變讋讌讍讎讏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '讐讑讒讓讔讕讖讗讘讙讚讛讜讝讞讟';
		$expects = '讐讑讒讓讔讕讖讗讘讙讚讛讜讝讞讟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '讠计订讣认讥讦讧讨让讪讫讬训议讯';
		$expects = '讠计订讣认讥讦讧讨让讪讫讬训议讯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '记讱讲讳讴讵讶讷许讹论讻讼讽设访';
		$expects = '记讱讲讳讴讵讶讷许讹论讻讼讽设访';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '诀证诂诃评诅识诇诈诉诊诋诌词诎诏';
		$expects = '诀证诂诃评诅识诇诈诉诊诋诌词诎诏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '诐译诒诓诔试诖诗诘诙诚诛诜话诞诟';
		$expects = '诐译诒诓诔试诖诗诘诙诚诛诜话诞诟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '诠诡询诣诤该详诧诨诩诪诫诬语诮误';
		$expects = '诠诡询诣诤该详诧诨诩诪诫诬语诮误';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '诰诱诲诳说诵诶请诸诹诺读诼诽课诿';
		$expects = '诰诱诲诳说诵诶请诸诹诺读诼诽课诿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '谀谁谂调谄谅谆谇谈谉谊谋谌谍谎谏';
		$expects = '谀谁谂调谄谅谆谇谈谉谊谋谌谍谎谏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '谐谑谒谓谔谕谖谗谘谙谚谛谜谝谞谟';
		$expects = '谐谑谒谓谔谕谖谗谘谙谚谛谜谝谞谟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '谠谡谢谣谤谥谦谧谨谩谪谫谬谭谮谯';
		$expects = '谠谡谢谣谤谥谦谧谨谩谪谫谬谭谮谯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '谰谱谲谳谴谵谶谷谸谹谺谻谼谽谾谿';
		$expects = '谰谱谲谳谴谵谶谷谸谹谺谻谼谽谾谿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '豀豁豂豃豄豅豆豇豈豉豊豋豌豍豎豏';
		$expects = '豀豁豂豃豄豅豆豇豈豉豊豋豌豍豎豏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '豐豑豒豓豔豕豖豗豘豙豚豛豜豝豞豟';
		$expects = '豐豑豒豓豔豕豖豗豘豙豚豛豜豝豞豟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '豠象豢豣豤豥豦豧豨豩豪豫豬豭豮豯';
		$expects = '豠象豢豣豤豥豦豧豨豩豪豫豬豭豮豯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '豰豱豲豳豴豵豶豷豸豹豺豻豼豽豾豿';
		$expects = '豰豱豲豳豴豵豶豷豸豹豺豻豼豽豾豿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '貀貁貂貃貄貅貆貇貈貉貊貋貌貍貎貏';
		$expects = '貀貁貂貃貄貅貆貇貈貉貊貋貌貍貎貏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '貐貑貒貓貔貕貖貗貘貙貚貛貜貝貞貟';
		$expects = '貐貑貒貓貔貕貖貗貘貙貚貛貜貝貞貟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '負財貢貣貤貥貦貧貨販貪貫責貭貮貯';
		$expects = '負財貢貣貤貥貦貧貨販貪貫責貭貮貯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '貰貱貲貳貴貵貶買貸貹貺費貼貽貾貿';
		$expects = '貰貱貲貳貴貵貶買貸貹貺費貼貽貾貿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '賀賁賂賃賄賅賆資賈賉賊賋賌賍賎賏';
		$expects = '賀賁賂賃賄賅賆資賈賉賊賋賌賍賎賏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '賐賑賒賓賔賕賖賗賘賙賚賛賜賝賞賟';
		$expects = '賐賑賒賓賔賕賖賗賘賙賚賛賜賝賞賟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '賠賡賢賣賤賥賦賧賨賩質賫賬賭賮賯';
		$expects = '賠賡賢賣賤賥賦賧賨賩質賫賬賭賮賯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '賰賱賲賳賴賵賶賷賸賹賺賻購賽賾賿';
		$expects = '賰賱賲賳賴賵賶賷賸賹賺賻購賽賾賿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '贀贁贂贃贄贅贆贇贈贉贊贋贌贍贎贏';
		$expects = '贀贁贂贃贄贅贆贇贈贉贊贋贌贍贎贏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '贐贑贒贓贔贕贖贗贘贙贚贛贜贝贞负';
		$expects = '贐贑贒贓贔贕贖贗贘贙贚贛贜贝贞负';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '贠贡财责贤败账货质贩贪贫贬购贮贯';
		$expects = '贠贡财责贤败账货质贩贪贫贬购贮贯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '贰贱贲贳贴贵贶贷贸费贺贻贼贽贾贿';
		$expects = '贰贱贲贳贴贵贶贷贸费贺贻贼贽贾贿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '赀赁赂赃资赅赆赇赈赉赊赋赌赍赎赏';
		$expects = '赀赁赂赃资赅赆赇赈赉赊赋赌赍赎赏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '赐赑赒赓赔赕赖赗赘赙赚赛赜赝赞赟';
		$expects = '赐赑赒赓赔赕赖赗赘赙赚赛赜赝赞赟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '赠赡赢赣赤赥赦赧赨赩赪赫赬赭赮赯';
		$expects = '赠赡赢赣赤赥赦赧赨赩赪赫赬赭赮赯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '走赱赲赳赴赵赶起赸赹赺赻赼赽赾赿';
		$expects = '走赱赲赳赴赵赶起赸赹赺赻赼赽赾赿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '趀趁趂趃趄超趆趇趈趉越趋趌趍趎趏';
		$expects = '趀趁趂趃趄超趆趇趈趉越趋趌趍趎趏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '趐趑趒趓趔趕趖趗趘趙趚趛趜趝趞趟';
		$expects = '趐趑趒趓趔趕趖趗趘趙趚趛趜趝趞趟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '趠趡趢趣趤趥趦趧趨趩趪趫趬趭趮趯';
		$expects = '趠趡趢趣趤趥趦趧趨趩趪趫趬趭趮趯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '趰趱趲足趴趵趶趷趸趹趺趻趼趽趾趿';
		$expects = '趰趱趲足趴趵趶趷趸趹趺趻趼趽趾趿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '跀跁跂跃跄跅跆跇跈跉跊跋跌跍跎跏';
		$expects = '跀跁跂跃跄跅跆跇跈跉跊跋跌跍跎跏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '跐跑跒跓跔跕跖跗跘跙跚跛跜距跞跟';
		$expects = '跐跑跒跓跔跕跖跗跘跙跚跛跜距跞跟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '跠跡跢跣跤跥跦跧跨跩跪跫跬跭跮路';
		$expects = '跠跡跢跣跤跥跦跧跨跩跪跫跬跭跮路';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '跰跱跲跳跴践跶跷跸跹跺跻跼跽跾跿';
		$expects = '跰跱跲跳跴践跶跷跸跹跺跻跼跽跾跿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '踀踁踂踃踄踅踆踇踈踉踊踋踌踍踎踏';
		$expects = '踀踁踂踃踄踅踆踇踈踉踊踋踌踍踎踏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '踐踑踒踓踔踕踖踗踘踙踚踛踜踝踞踟';
		$expects = '踐踑踒踓踔踕踖踗踘踙踚踛踜踝踞踟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '踠踡踢踣踤踥踦踧踨踩踪踫踬踭踮踯';
		$expects = '踠踡踢踣踤踥踦踧踨踩踪踫踬踭踮踯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '踰踱踲踳踴踵踶踷踸踹踺踻踼踽踾踿';
		$expects = '踰踱踲踳踴踵踶踷踸踹踺踻踼踽踾踿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蹀蹁蹂蹃蹄蹅蹆蹇蹈蹉蹊蹋蹌蹍蹎蹏';
		$expects = '蹀蹁蹂蹃蹄蹅蹆蹇蹈蹉蹊蹋蹌蹍蹎蹏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蹐蹑蹒蹓蹔蹕蹖蹗蹘蹙蹚蹛蹜蹝蹞蹟';
		$expects = '蹐蹑蹒蹓蹔蹕蹖蹗蹘蹙蹚蹛蹜蹝蹞蹟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蹠蹡蹢蹣蹤蹥蹦蹧蹨蹩蹪蹫蹬蹭蹮蹯';
		$expects = '蹠蹡蹢蹣蹤蹥蹦蹧蹨蹩蹪蹫蹬蹭蹮蹯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '蹰蹱蹲蹳蹴蹵蹶蹷蹸蹹蹺蹻蹼蹽蹾蹿';
		$expects = '蹰蹱蹲蹳蹴蹵蹶蹷蹸蹹蹺蹻蹼蹽蹾蹿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '躀躁躂躃躄躅躆躇躈躉躊躋躌躍躎躏';
		$expects = '躀躁躂躃躄躅躆躇躈躉躊躋躌躍躎躏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '躐躑躒躓躔躕躖躗躘躙躚躛躜躝躞躟';
		$expects = '躐躑躒躓躔躕躖躗躘躙躚躛躜躝躞躟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '躠躡躢躣躤躥躦躧躨躩躪身躬躭躮躯';
		$expects = '躠躡躢躣躤躥躦躧躨躩躪身躬躭躮躯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '躰躱躲躳躴躵躶躷躸躹躺躻躼躽躾躿';
		$expects = '躰躱躲躳躴躵躶躷躸躹躺躻躼躽躾躿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '軀軁軂軃軄軅軆軇軈軉車軋軌軍軎軏';
		$expects = '軀軁軂軃軄軅軆軇軈軉車軋軌軍軎軏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '軐軑軒軓軔軕軖軗軘軙軚軛軜軝軞軟';
		$expects = '軐軑軒軓軔軕軖軗軘軙軚軛軜軝軞軟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '軠軡転軣軤軥軦軧軨軩軪軫軬軭軮軯';
		$expects = '軠軡転軣軤軥軦軧軨軩軪軫軬軭軮軯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '軰軱軲軳軴軵軶軷軸軹軺軻軼軽軾軿';
		$expects = '軰軱軲軳軴軵軶軷軸軹軺軻軼軽軾軿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '輀輁輂較輄輅輆輇輈載輊輋輌輍輎輏';
		$expects = '輀輁輂較輄輅輆輇輈載輊輋輌輍輎輏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '輐輑輒輓輔輕輖輗輘輙輚輛輜輝輞輟';
		$expects = '輐輑輒輓輔輕輖輗輘輙輚輛輜輝輞輟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '輠輡輢輣輤輥輦輧輨輩輪輫輬輭輮輯';
		$expects = '輠輡輢輣輤輥輦輧輨輩輪輫輬輭輮輯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '輰輱輲輳輴輵輶輷輸輹輺輻輼輽輾輿';
		$expects = '輰輱輲輳輴輵輶輷輸輹輺輻輼輽輾輿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '轀轁轂轃轄轅轆轇轈轉轊轋轌轍轎轏';
		$expects = '轀轁轂轃轄轅轆轇轈轉轊轋轌轍轎轏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '轐轑轒轓轔轕轖轗轘轙轚轛轜轝轞轟';
		$expects = '轐轑轒轓轔轕轖轗轘轙轚轛轜轝轞轟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '轠轡轢轣轤轥车轧轨轩轪轫转轭轮软';
		$expects = '轠轡轢轣轤轥车轧轨轩轪轫转轭轮软';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '轰轱轲轳轴轵轶轷轸轹轺轻轼载轾轿';
		$expects = '轰轱轲轳轴轵轶轷轸轹轺轻轼载轾轿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '辀辁辂较辄辅辆辇辈辉辊辋辌辍辎辏';
		$expects = '辀辁辂较辄辅辆辇辈辉辊辋辌辍辎辏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '辐辑辒输辔辕辖辗辘辙辚辛辜辝辞辟';
		$expects = '辐辑辒输辔辕辖辗辘辙辚辛辜辝辞辟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '辠辡辢辣辤辥辦辧辨辩辪辫辬辭辮辯';
		$expects = '辠辡辢辣辤辥辦辧辨辩辪辫辬辭辮辯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '辰辱農辳辴辵辶辷辸边辺辻込辽达辿';
		$expects = '辰辱農辳辴辵辶辷辸边辺辻込辽达辿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '迀迁迂迃迄迅迆过迈迉迊迋迌迍迎迏';
		$expects = '迀迁迂迃迄迅迆过迈迉迊迋迌迍迎迏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '运近迒迓返迕迖迗还这迚进远违连迟';
		$expects = '运近迒迓返迕迖迗还这迚进远违连迟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '迠迡迢迣迤迥迦迧迨迩迪迫迬迭迮迯';
		$expects = '迠迡迢迣迤迥迦迧迨迩迪迫迬迭迮迯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '述迱迲迳迴迵迶迷迸迹迺迻迼追迾迿';
		$expects = '述迱迲迳迴迵迶迷迸迹迺迻迼追迾迿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSection9 method
	 *
	 * Testing characters 9000 - 9fff
	 *
	 * @return void
	 */
	public function testSection9() {
		$string = '退送适逃逄逅逆逇逈选逊逋逌逍逎透';
		$expects = '退送适逃逄逅逆逇逈选逊逋逌逍逎透';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '逐逑递逓途逕逖逗逘這通逛逜逝逞速';
		$expects = '逐逑递逓途逕逖逗逘這通逛逜逝逞速';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '造逡逢連逤逥逦逧逨逩逪逫逬逭逮逯';
		$expects = '造逡逢連逤逥逦逧逨逩逪逫逬逭逮逯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '逰週進逳逴逵逶逷逸逹逺逻逼逽逾逿';
		$expects = '逰週進逳逴逵逶逷逸逹逺逻逼逽逾逿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '遀遁遂遃遄遅遆遇遈遉遊運遌遍過遏';
		$expects = '遀遁遂遃遄遅遆遇遈遉遊運遌遍過遏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '遐遑遒道達違遖遗遘遙遚遛遜遝遞遟';
		$expects = '遐遑遒道達違遖遗遘遙遚遛遜遝遞遟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '遠遡遢遣遤遥遦遧遨適遪遫遬遭遮遯';
		$expects = '遠遡遢遣遤遥遦遧遨適遪遫遬遭遮遯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '遰遱遲遳遴遵遶遷選遹遺遻遼遽遾避';
		$expects = '遰遱遲遳遴遵遶遷選遹遺遻遼遽遾避';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '邀邁邂邃還邅邆邇邈邉邊邋邌邍邎邏';
		$expects = '邀邁邂邃還邅邆邇邈邉邊邋邌邍邎邏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '邐邑邒邓邔邕邖邗邘邙邚邛邜邝邞邟';
		$expects = '邐邑邒邓邔邕邖邗邘邙邚邛邜邝邞邟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '邠邡邢那邤邥邦邧邨邩邪邫邬邭邮邯';
		$expects = '邠邡邢那邤邥邦邧邨邩邪邫邬邭邮邯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '邰邱邲邳邴邵邶邷邸邹邺邻邼邽邾邿';
		$expects = '邰邱邲邳邴邵邶邷邸邹邺邻邼邽邾邿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '郀郁郂郃郄郅郆郇郈郉郊郋郌郍郎郏';
		$expects = '郀郁郂郃郄郅郆郇郈郉郊郋郌郍郎郏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '郐郑郒郓郔郕郖郗郘郙郚郛郜郝郞郟';
		$expects = '郐郑郒郓郔郕郖郗郘郙郚郛郜郝郞郟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '郠郡郢郣郤郥郦郧部郩郪郫郬郭郮郯';
		$expects = '郠郡郢郣郤郥郦郧部郩郪郫郬郭郮郯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '郰郱郲郳郴郵郶郷郸郹郺郻郼都郾郿';
		$expects = '郰郱郲郳郴郵郶郷郸郹郺郻郼都郾郿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鄀鄁鄂鄃鄄鄅鄆鄇鄈鄉鄊鄋鄌鄍鄎鄏';
		$expects = '鄀鄁鄂鄃鄄鄅鄆鄇鄈鄉鄊鄋鄌鄍鄎鄏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鄐鄑鄒鄓鄔鄕鄖鄗鄘鄙鄚鄛鄜鄝鄞鄟';
		$expects = '鄐鄑鄒鄓鄔鄕鄖鄗鄘鄙鄚鄛鄜鄝鄞鄟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鄠鄡鄢鄣鄤鄥鄦鄧鄨鄩鄪鄫鄬鄭鄮鄯';
		$expects = '鄠鄡鄢鄣鄤鄥鄦鄧鄨鄩鄪鄫鄬鄭鄮鄯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鄰鄱鄲鄳鄴鄵鄶鄷鄸鄹鄺鄻鄼鄽鄾鄿';
		$expects = '鄰鄱鄲鄳鄴鄵鄶鄷鄸鄹鄺鄻鄼鄽鄾鄿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '酀酁酂酃酄酅酆酇酈酉酊酋酌配酎酏';
		$expects = '酀酁酂酃酄酅酆酇酈酉酊酋酌配酎酏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '酐酑酒酓酔酕酖酗酘酙酚酛酜酝酞酟';
		$expects = '酐酑酒酓酔酕酖酗酘酙酚酛酜酝酞酟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '酠酡酢酣酤酥酦酧酨酩酪酫酬酭酮酯';
		$expects = '酠酡酢酣酤酥酦酧酨酩酪酫酬酭酮酯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '酰酱酲酳酴酵酶酷酸酹酺酻酼酽酾酿';
		$expects = '酰酱酲酳酴酵酶酷酸酹酺酻酼酽酾酿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '醀醁醂醃醄醅醆醇醈醉醊醋醌醍醎醏';
		$expects = '醀醁醂醃醄醅醆醇醈醉醊醋醌醍醎醏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '醐醑醒醓醔醕醖醗醘醙醚醛醜醝醞醟';
		$expects = '醐醑醒醓醔醕醖醗醘醙醚醛醜醝醞醟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '醠醡醢醣醤醥醦醧醨醩醪醫醬醭醮醯';
		$expects = '醠醡醢醣醤醥醦醧醨醩醪醫醬醭醮醯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '醰醱醲醳醴醵醶醷醸醹醺醻醼醽醾醿';
		$expects = '醰醱醲醳醴醵醶醷醸醹醺醻醼醽醾醿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '釀釁釂釃釄釅釆采釈釉释釋里重野量';
		$expects = '釀釁釂釃釄釅釆采釈釉释釋里重野量';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '釐金釒釓釔釕釖釗釘釙釚釛釜針釞釟';
		$expects = '釐金釒釓釔釕釖釗釘釙釚釛釜針釞釟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '釠釡釢釣釤釥釦釧釨釩釪釫釬釭釮釯';
		$expects = '釠釡釢釣釤釥釦釧釨釩釪釫釬釭釮釯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '釰釱釲釳釴釵釶釷釸釹釺釻釼釽釾釿';
		$expects = '釰釱釲釳釴釵釶釷釸釹釺釻釼釽釾釿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鈀鈁鈂鈃鈄鈅鈆鈇鈈鈉鈊鈋鈌鈍鈎鈏';
		$expects = '鈀鈁鈂鈃鈄鈅鈆鈇鈈鈉鈊鈋鈌鈍鈎鈏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鈐鈑鈒鈓鈔鈕鈖鈗鈘鈙鈚鈛鈜鈝鈞鈟';
		$expects = '鈐鈑鈒鈓鈔鈕鈖鈗鈘鈙鈚鈛鈜鈝鈞鈟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鈠鈡鈢鈣鈤鈥鈦鈧鈨鈩鈪鈫鈬鈭鈮鈯';
		$expects = '鈠鈡鈢鈣鈤鈥鈦鈧鈨鈩鈪鈫鈬鈭鈮鈯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鈰鈱鈲鈳鈴鈵鈶鈷鈸鈹鈺鈻鈼鈽鈾鈿';
		$expects = '鈰鈱鈲鈳鈴鈵鈶鈷鈸鈹鈺鈻鈼鈽鈾鈿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鉀鉁鉂鉃鉄鉅鉆鉇鉈鉉鉊鉋鉌鉍鉎鉏';
		$expects = '鉀鉁鉂鉃鉄鉅鉆鉇鉈鉉鉊鉋鉌鉍鉎鉏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鉐鉑鉒鉓鉔鉕鉖鉗鉘鉙鉚鉛鉜鉝鉞鉟';
		$expects = '鉐鉑鉒鉓鉔鉕鉖鉗鉘鉙鉚鉛鉜鉝鉞鉟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鉠鉡鉢鉣鉤鉥鉦鉧鉨鉩鉪鉫鉬鉭鉮鉯';
		$expects = '鉠鉡鉢鉣鉤鉥鉦鉧鉨鉩鉪鉫鉬鉭鉮鉯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鉰鉱鉲鉳鉴鉵鉶鉷鉸鉹鉺鉻鉼鉽鉾鉿';
		$expects = '鉰鉱鉲鉳鉴鉵鉶鉷鉸鉹鉺鉻鉼鉽鉾鉿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '銀銁銂銃銄銅銆銇銈銉銊銋銌銍銎銏';
		$expects = '銀銁銂銃銄銅銆銇銈銉銊銋銌銍銎銏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '銐銑銒銓銔銕銖銗銘銙銚銛銜銝銞銟';
		$expects = '銐銑銒銓銔銕銖銗銘銙銚銛銜銝銞銟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '銠銡銢銣銤銥銦銧銨銩銪銫銬銭銮銯';
		$expects = '銠銡銢銣銤銥銦銧銨銩銪銫銬銭銮銯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '銰銱銲銳銴銵銶銷銸銹銺銻銼銽銾銿';
		$expects = '銰銱銲銳銴銵銶銷銸銹銺銻銼銽銾銿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鋀鋁鋂鋃鋄鋅鋆鋇鋈鋉鋊鋋鋌鋍鋎鋏';
		$expects = '鋀鋁鋂鋃鋄鋅鋆鋇鋈鋉鋊鋋鋌鋍鋎鋏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鋐鋑鋒鋓鋔鋕鋖鋗鋘鋙鋚鋛鋜鋝鋞鋟';
		$expects = '鋐鋑鋒鋓鋔鋕鋖鋗鋘鋙鋚鋛鋜鋝鋞鋟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鋠鋡鋢鋣鋤鋥鋦鋧鋨鋩鋪鋫鋬鋭鋮鋯';
		$expects = '鋠鋡鋢鋣鋤鋥鋦鋧鋨鋩鋪鋫鋬鋭鋮鋯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鋰鋱鋲鋳鋴鋵鋶鋷鋸鋹鋺鋻鋼鋽鋾鋿';
		$expects = '鋰鋱鋲鋳鋴鋵鋶鋷鋸鋹鋺鋻鋼鋽鋾鋿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '錀錁錂錃錄錅錆錇錈錉錊錋錌錍錎錏';
		$expects = '錀錁錂錃錄錅錆錇錈錉錊錋錌錍錎錏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '錐錑錒錓錔錕錖錗錘錙錚錛錜錝錞錟';
		$expects = '錐錑錒錓錔錕錖錗錘錙錚錛錜錝錞錟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '錠錡錢錣錤錥錦錧錨錩錪錫錬錭錮錯';
		$expects = '錠錡錢錣錤錥錦錧錨錩錪錫錬錭錮錯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '錰錱録錳錴錵錶錷錸錹錺錻錼錽錾錿';
		$expects = '錰錱録錳錴錵錶錷錸錹錺錻錼錽錾錿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鍀鍁鍂鍃鍄鍅鍆鍇鍈鍉鍊鍋鍌鍍鍎鍏';
		$expects = '鍀鍁鍂鍃鍄鍅鍆鍇鍈鍉鍊鍋鍌鍍鍎鍏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鍐鍑鍒鍓鍔鍕鍖鍗鍘鍙鍚鍛鍜鍝鍞鍟';
		$expects = '鍐鍑鍒鍓鍔鍕鍖鍗鍘鍙鍚鍛鍜鍝鍞鍟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鍠鍡鍢鍣鍤鍥鍦鍧鍨鍩鍪鍫鍬鍭鍮鍯';
		$expects = '鍠鍡鍢鍣鍤鍥鍦鍧鍨鍩鍪鍫鍬鍭鍮鍯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鍰鍱鍲鍳鍴鍵鍶鍷鍸鍹鍺鍻鍼鍽鍾鍿';
		$expects = '鍰鍱鍲鍳鍴鍵鍶鍷鍸鍹鍺鍻鍼鍽鍾鍿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鎀鎁鎂鎃鎄鎅鎆鎇鎈鎉鎊鎋鎌鎍鎎鎏';
		$expects = '鎀鎁鎂鎃鎄鎅鎆鎇鎈鎉鎊鎋鎌鎍鎎鎏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鎐鎑鎒鎓鎔鎕鎖鎗鎘鎙鎚鎛鎜鎝鎞鎟';
		$expects = '鎐鎑鎒鎓鎔鎕鎖鎗鎘鎙鎚鎛鎜鎝鎞鎟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鎠鎡鎢鎣鎤鎥鎦鎧鎨鎩鎪鎫鎬鎭鎮鎯';
		$expects = '鎠鎡鎢鎣鎤鎥鎦鎧鎨鎩鎪鎫鎬鎭鎮鎯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鎰鎱鎲鎳鎴鎵鎶鎷鎸鎹鎺鎻鎼鎽鎾鎿';
		$expects = '鎰鎱鎲鎳鎴鎵鎶鎷鎸鎹鎺鎻鎼鎽鎾鎿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鏀鏁鏂鏃鏄鏅鏆鏇鏈鏉鏊鏋鏌鏍鏎鏏';
		$expects = '鏀鏁鏂鏃鏄鏅鏆鏇鏈鏉鏊鏋鏌鏍鏎鏏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鏐鏑鏒鏓鏔鏕鏖鏗鏘鏙鏚鏛鏜鏝鏞鏟';
		$expects = '鏐鏑鏒鏓鏔鏕鏖鏗鏘鏙鏚鏛鏜鏝鏞鏟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鏠鏡鏢鏣鏤鏥鏦鏧鏨鏩鏪鏫鏬鏭鏮鏯';
		$expects = '鏠鏡鏢鏣鏤鏥鏦鏧鏨鏩鏪鏫鏬鏭鏮鏯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鏰鏱鏲鏳鏴鏵鏶鏷鏸鏹鏺鏻鏼鏽鏾鏿';
		$expects = '鏰鏱鏲鏳鏴鏵鏶鏷鏸鏹鏺鏻鏼鏽鏾鏿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鐀鐁鐂鐃鐄鐅鐆鐇鐈鐉鐊鐋鐌鐍鐎鐏';
		$expects = '鐀鐁鐂鐃鐄鐅鐆鐇鐈鐉鐊鐋鐌鐍鐎鐏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鐐鐑鐒鐓鐔鐕鐖鐗鐘鐙鐚鐛鐜鐝鐞鐟';
		$expects = '鐐鐑鐒鐓鐔鐕鐖鐗鐘鐙鐚鐛鐜鐝鐞鐟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鐠鐡鐢鐣鐤鐥鐦鐧鐨鐩鐪鐫鐬鐭鐮鐯';
		$expects = '鐠鐡鐢鐣鐤鐥鐦鐧鐨鐩鐪鐫鐬鐭鐮鐯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鐰鐱鐲鐳鐴鐵鐶鐷鐸鐹鐺鐻鐼鐽鐾鐿';
		$expects = '鐰鐱鐲鐳鐴鐵鐶鐷鐸鐹鐺鐻鐼鐽鐾鐿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鑀鑁鑂鑃鑄鑅鑆鑇鑈鑉鑊鑋鑌鑍鑎鑏';
		$expects = '鑀鑁鑂鑃鑄鑅鑆鑇鑈鑉鑊鑋鑌鑍鑎鑏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鑐鑑鑒鑓鑔鑕鑖鑗鑘鑙鑚鑛鑜鑝鑞鑟';
		$expects = '鑐鑑鑒鑓鑔鑕鑖鑗鑘鑙鑚鑛鑜鑝鑞鑟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鑠鑡鑢鑣鑤鑥鑦鑧鑨鑩鑪鑫鑬鑭鑮鑯';
		$expects = '鑠鑡鑢鑣鑤鑥鑦鑧鑨鑩鑪鑫鑬鑭鑮鑯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鑰鑱鑲鑳鑴鑵鑶鑷鑸鑹鑺鑻鑼鑽鑾鑿';
		$expects = '鑰鑱鑲鑳鑴鑵鑶鑷鑸鑹鑺鑻鑼鑽鑾鑿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '钀钁钂钃钄钅钆钇针钉钊钋钌钍钎钏';
		$expects = '钀钁钂钃钄钅钆钇针钉钊钋钌钍钎钏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '钐钑钒钓钔钕钖钗钘钙钚钛钜钝钞钟';
		$expects = '钐钑钒钓钔钕钖钗钘钙钚钛钜钝钞钟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '钠钡钢钣钤钥钦钧钨钩钪钫钬钭钮钯';
		$expects = '钠钡钢钣钤钥钦钧钨钩钪钫钬钭钮钯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '钰钱钲钳钴钵钶钷钸钹钺钻钼钽钾钿';
		$expects = '钰钱钲钳钴钵钶钷钸钹钺钻钼钽钾钿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '铀铁铂铃铄铅铆铇铈铉铊铋铌铍铎铏';
		$expects = '铀铁铂铃铄铅铆铇铈铉铊铋铌铍铎铏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '铐铑铒铓铔铕铖铗铘铙铚铛铜铝铞铟';
		$expects = '铐铑铒铓铔铕铖铗铘铙铚铛铜铝铞铟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '铠铡铢铣铤铥铦铧铨铩铪铫铬铭铮铯';
		$expects = '铠铡铢铣铤铥铦铧铨铩铪铫铬铭铮铯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '铰铱铲铳铴铵银铷铸铹铺铻铼铽链铿';
		$expects = '铰铱铲铳铴铵银铷铸铹铺铻铼铽链铿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '销锁锂锃锄锅锆锇锈锉锊锋锌锍锎锏';
		$expects = '销锁锂锃锄锅锆锇锈锉锊锋锌锍锎锏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '锐锑锒锓锔锕锖锗锘错锚锛锜锝锞锟';
		$expects = '锐锑锒锓锔锕锖锗锘错锚锛锜锝锞锟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '锠锡锢锣锤锥锦锧锨锩锪锫锬锭键锯';
		$expects = '锠锡锢锣锤锥锦锧锨锩锪锫锬锭键锯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '锰锱锲锳锴锵锶锷锸锹锺锻锼锽锾锿';
		$expects = '锰锱锲锳锴锵锶锷锸锹锺锻锼锽锾锿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '镀镁镂镃镄镅镆镇镈镉镊镋镌镍镎镏';
		$expects = '镀镁镂镃镄镅镆镇镈镉镊镋镌镍镎镏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '镐镑镒镓镔镕镖镗镘镙镚镛镜镝镞镟';
		$expects = '镐镑镒镓镔镕镖镗镘镙镚镛镜镝镞镟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '镠镡镢镣镤镥镦镧镨镩镪镫镬镭镮镯';
		$expects = '镠镡镢镣镤镥镦镧镨镩镪镫镬镭镮镯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '镰镱镲镳镴镵镶長镸镹镺镻镼镽镾长';
		$expects = '镰镱镲镳镴镵镶長镸镹镺镻镼镽镾长';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '門閁閂閃閄閅閆閇閈閉閊開閌閍閎閏';
		$expects = '門閁閂閃閄閅閆閇閈閉閊開閌閍閎閏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '閐閑閒間閔閕閖閗閘閙閚閛閜閝閞閟';
		$expects = '閐閑閒間閔閕閖閗閘閙閚閛閜閝閞閟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '閠閡関閣閤閥閦閧閨閩閪閫閬閭閮閯';
		$expects = '閠閡関閣閤閥閦閧閨閩閪閫閬閭閮閯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '閰閱閲閳閴閵閶閷閸閹閺閻閼閽閾閿';
		$expects = '閰閱閲閳閴閵閶閷閸閹閺閻閼閽閾閿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '闀闁闂闃闄闅闆闇闈闉闊闋闌闍闎闏';
		$expects = '闀闁闂闃闄闅闆闇闈闉闊闋闌闍闎闏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '闐闑闒闓闔闕闖闗闘闙闚闛關闝闞闟';
		$expects = '闐闑闒闓闔闕闖闗闘闙闚闛關闝闞闟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '闠闡闢闣闤闥闦闧门闩闪闫闬闭问闯';
		$expects = '闠闡闢闣闤闥闦闧门闩闪闫闬闭问闯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '闰闱闲闳间闵闶闷闸闹闺闻闼闽闾闿';
		$expects = '闰闱闲闳间闵闶闷闸闹闺闻闼闽闾闿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '阀阁阂阃阄阅阆阇阈阉阊阋阌阍阎阏';
		$expects = '阀阁阂阃阄阅阆阇阈阉阊阋阌阍阎阏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '阐阑阒阓阔阕阖阗阘阙阚阛阜阝阞队';
		$expects = '阐阑阒阓阔阕阖阗阘阙阚阛阜阝阞队';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '阠阡阢阣阤阥阦阧阨阩阪阫阬阭阮阯';
		$expects = '阠阡阢阣阤阥阦阧阨阩阪阫阬阭阮阯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '阰阱防阳阴阵阶阷阸阹阺阻阼阽阾阿';
		$expects = '阰阱防阳阴阵阶阷阸阹阺阻阼阽阾阿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '陀陁陂陃附际陆陇陈陉陊陋陌降陎陏';
		$expects = '陀陁陂陃附际陆陇陈陉陊陋陌降陎陏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '限陑陒陓陔陕陖陗陘陙陚陛陜陝陞陟';
		$expects = '限陑陒陓陔陕陖陗陘陙陚陛陜陝陞陟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '陠陡院陣除陥陦陧陨险陪陫陬陭陮陯';
		$expects = '陠陡院陣除陥陦陧陨险陪陫陬陭陮陯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '陰陱陲陳陴陵陶陷陸陹険陻陼陽陾陿';
		$expects = '陰陱陲陳陴陵陶陷陸陹険陻陼陽陾陿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '隀隁隂隃隄隅隆隇隈隉隊隋隌隍階随';
		$expects = '隀隁隂隃隄隅隆隇隈隉隊隋隌隍階随';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '隐隑隒隓隔隕隖隗隘隙隚際障隝隞隟';
		$expects = '隐隑隒隓隔隕隖隗隘隙隚際障隝隞隟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '隠隡隢隣隤隥隦隧隨隩險隫隬隭隮隯';
		$expects = '隠隡隢隣隤隥隦隧隨隩險隫隬隭隮隯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '隰隱隲隳隴隵隶隷隸隹隺隻隼隽难隿';
		$expects = '隰隱隲隳隴隵隶隷隸隹隺隻隼隽难隿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '雀雁雂雃雄雅集雇雈雉雊雋雌雍雎雏';
		$expects = '雀雁雂雃雄雅集雇雈雉雊雋雌雍雎雏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '雐雑雒雓雔雕雖雗雘雙雚雛雜雝雞雟';
		$expects = '雐雑雒雓雔雕雖雗雘雙雚雛雜雝雞雟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '雠雡離難雤雥雦雧雨雩雪雫雬雭雮雯';
		$expects = '雠雡離難雤雥雦雧雨雩雪雫雬雭雮雯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '雰雱雲雳雴雵零雷雸雹雺電雼雽雾雿';
		$expects = '雰雱雲雳雴雵零雷雸雹雺電雼雽雾雿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '需霁霂霃霄霅霆震霈霉霊霋霌霍霎霏';
		$expects = '需霁霂霃霄霅霆震霈霉霊霋霌霍霎霏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '霐霑霒霓霔霕霖霗霘霙霚霛霜霝霞霟';
		$expects = '霐霑霒霓霔霕霖霗霘霙霚霛霜霝霞霟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '霠霡霢霣霤霥霦霧霨霩霪霫霬霭霮霯';
		$expects = '霠霡霢霣霤霥霦霧霨霩霪霫霬霭霮霯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '霰霱露霳霴霵霶霷霸霹霺霻霼霽霾霿';
		$expects = '霰霱露霳霴霵霶霷霸霹霺霻霼霽霾霿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '靀靁靂靃靄靅靆靇靈靉靊靋靌靍靎靏';
		$expects = '靀靁靂靃靄靅靆靇靈靉靊靋靌靍靎靏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '靐靑青靓靔靕靖靗靘静靚靛靜靝非靟';
		$expects = '靐靑青靓靔靕靖靗靘静靚靛靜靝非靟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '靠靡面靣靤靥靦靧靨革靪靫靬靭靮靯';
		$expects = '靠靡面靣靤靥靦靧靨革靪靫靬靭靮靯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '靰靱靲靳靴靵靶靷靸靹靺靻靼靽靾靿';
		$expects = '靰靱靲靳靴靵靶靷靸靹靺靻靼靽靾靿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鞀鞁鞂鞃鞄鞅鞆鞇鞈鞉鞊鞋鞌鞍鞎鞏';
		$expects = '鞀鞁鞂鞃鞄鞅鞆鞇鞈鞉鞊鞋鞌鞍鞎鞏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鞐鞑鞒鞓鞔鞕鞖鞗鞘鞙鞚鞛鞜鞝鞞鞟';
		$expects = '鞐鞑鞒鞓鞔鞕鞖鞗鞘鞙鞚鞛鞜鞝鞞鞟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鞠鞡鞢鞣鞤鞥鞦鞧鞨鞩鞪鞫鞬鞭鞮鞯';
		$expects = '鞠鞡鞢鞣鞤鞥鞦鞧鞨鞩鞪鞫鞬鞭鞮鞯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鞰鞱鞲鞳鞴鞵鞶鞷鞸鞹鞺鞻鞼鞽鞾鞿';
		$expects = '鞰鞱鞲鞳鞴鞵鞶鞷鞸鞹鞺鞻鞼鞽鞾鞿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '韀韁韂韃韄韅韆韇韈韉韊韋韌韍韎韏';
		$expects = '韀韁韂韃韄韅韆韇韈韉韊韋韌韍韎韏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '韐韑韒韓韔韕韖韗韘韙韚韛韜韝韞韟';
		$expects = '韐韑韒韓韔韕韖韗韘韙韚韛韜韝韞韟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '韠韡韢韣韤韥韦韧韨韩韪韫韬韭韮韯';
		$expects = '韠韡韢韣韤韥韦韧韨韩韪韫韬韭韮韯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '韰韱韲音韴韵韶韷韸韹韺韻韼韽韾響';
		$expects = '韰韱韲音韴韵韶韷韸韹韺韻韼韽韾響';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '頀頁頂頃頄項順頇須頉頊頋頌頍頎頏';
		$expects = '頀頁頂頃頄項順頇須頉頊頋頌頍頎頏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '預頑頒頓頔頕頖頗領頙頚頛頜頝頞頟';
		$expects = '預頑頒頓頔頕頖頗領頙頚頛頜頝頞頟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '頠頡頢頣頤頥頦頧頨頩頪頫頬頭頮頯';
		$expects = '頠頡頢頣頤頥頦頧頨頩頪頫頬頭頮頯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '頰頱頲頳頴頵頶頷頸頹頺頻頼頽頾頿';
		$expects = '頰頱頲頳頴頵頶頷頸頹頺頻頼頽頾頿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '顀顁顂顃顄顅顆顇顈顉顊顋題額顎顏';
		$expects = '顀顁顂顃顄顅顆顇顈顉顊顋題額顎顏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '顐顑顒顓顔顕顖顗願顙顚顛顜顝類顟';
		$expects = '顐顑顒顓顔顕顖顗願顙顚顛顜顝類顟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '顠顡顢顣顤顥顦顧顨顩顪顫顬顭顮顯';
		$expects = '顠顡顢顣顤顥顦顧顨顩顪顫顬顭顮顯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '顰顱顲顳顴页顶顷顸项顺须顼顽顾顿';
		$expects = '顰顱顲顳顴页顶顷顸项顺须顼顽顾顿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '颀颁颂颃预颅领颇颈颉颊颋颌颍颎颏';
		$expects = '颀颁颂颃预颅领颇颈颉颊颋颌颍颎颏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '颐频颒颓颔颕颖颗题颙颚颛颜额颞颟';
		$expects = '颐频颒颓颔颕颖颗题颙颚颛颜额颞颟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '颠颡颢颣颤颥颦颧風颩颪颫颬颭颮颯';
		$expects = '颠颡颢颣颤颥颦颧風颩颪颫颬颭颮颯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '颰颱颲颳颴颵颶颷颸颹颺颻颼颽颾颿';
		$expects = '颰颱颲颳颴颵颶颷颸颹颺颻颼颽颾颿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '飀飁飂飃飄飅飆飇飈飉飊飋飌飍风飏';
		$expects = '飀飁飂飃飄飅飆飇飈飉飊飋飌飍风飏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '飐飑飒飓飔飕飖飗飘飙飚飛飜飝飞食';
		$expects = '飐飑飒飓飔飕飖飗飘飙飚飛飜飝飞食';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '飠飡飢飣飤飥飦飧飨飩飪飫飬飭飮飯';
		$expects = '飠飡飢飣飤飥飦飧飨飩飪飫飬飭飮飯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '飰飱飲飳飴飵飶飷飸飹飺飻飼飽飾飿';
		$expects = '飰飱飲飳飴飵飶飷飸飹飺飻飼飽飾飿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '餀餁餂餃餄餅餆餇餈餉養餋餌餍餎餏';
		$expects = '餀餁餂餃餄餅餆餇餈餉養餋餌餍餎餏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '餐餑餒餓餔餕餖餗餘餙餚餛餜餝餞餟';
		$expects = '餐餑餒餓餔餕餖餗餘餙餚餛餜餝餞餟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '餠餡餢餣餤餥餦餧館餩餪餫餬餭餮餯';
		$expects = '餠餡餢餣餤餥餦餧館餩餪餫餬餭餮餯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '餰餱餲餳餴餵餶餷餸餹餺餻餼餽餾餿';
		$expects = '餰餱餲餳餴餵餶餷餸餹餺餻餼餽餾餿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '饀饁饂饃饄饅饆饇饈饉饊饋饌饍饎饏';
		$expects = '饀饁饂饃饄饅饆饇饈饉饊饋饌饍饎饏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '饐饑饒饓饔饕饖饗饘饙饚饛饜饝饞饟';
		$expects = '饐饑饒饓饔饕饖饗饘饙饚饛饜饝饞饟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '饠饡饢饣饤饥饦饧饨饩饪饫饬饭饮饯';
		$expects = '饠饡饢饣饤饥饦饧饨饩饪饫饬饭饮饯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '饰饱饲饳饴饵饶饷饸饹饺饻饼饽饾饿';
		$expects = '饰饱饲饳饴饵饶饷饸饹饺饻饼饽饾饿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '馀馁馂馃馄馅馆馇馈馉馊馋馌馍馎馏';
		$expects = '馀馁馂馃馄馅馆馇馈馉馊馋馌馍馎馏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '馐馑馒馓馔馕首馗馘香馚馛馜馝馞馟';
		$expects = '馐馑馒馓馔馕首馗馘香馚馛馜馝馞馟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '馠馡馢馣馤馥馦馧馨馩馪馫馬馭馮馯';
		$expects = '馠馡馢馣馤馥馦馧馨馩馪馫馬馭馮馯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '馰馱馲馳馴馵馶馷馸馹馺馻馼馽馾馿';
		$expects = '馰馱馲馳馴馵馶馷馸馹馺馻馼馽馾馿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '駀駁駂駃駄駅駆駇駈駉駊駋駌駍駎駏';
		$expects = '駀駁駂駃駄駅駆駇駈駉駊駋駌駍駎駏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '駐駑駒駓駔駕駖駗駘駙駚駛駜駝駞駟';
		$expects = '駐駑駒駓駔駕駖駗駘駙駚駛駜駝駞駟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '駠駡駢駣駤駥駦駧駨駩駪駫駬駭駮駯';
		$expects = '駠駡駢駣駤駥駦駧駨駩駪駫駬駭駮駯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '駰駱駲駳駴駵駶駷駸駹駺駻駼駽駾駿';
		$expects = '駰駱駲駳駴駵駶駷駸駹駺駻駼駽駾駿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '騀騁騂騃騄騅騆騇騈騉騊騋騌騍騎騏';
		$expects = '騀騁騂騃騄騅騆騇騈騉騊騋騌騍騎騏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '騐騑騒験騔騕騖騗騘騙騚騛騜騝騞騟';
		$expects = '騐騑騒験騔騕騖騗騘騙騚騛騜騝騞騟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '騠騡騢騣騤騥騦騧騨騩騪騫騬騭騮騯';
		$expects = '騠騡騢騣騤騥騦騧騨騩騪騫騬騭騮騯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '騰騱騲騳騴騵騶騷騸騹騺騻騼騽騾騿';
		$expects = '騰騱騲騳騴騵騶騷騸騹騺騻騼騽騾騿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '驀驁驂驃驄驅驆驇驈驉驊驋驌驍驎驏';
		$expects = '驀驁驂驃驄驅驆驇驈驉驊驋驌驍驎驏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '驐驑驒驓驔驕驖驗驘驙驚驛驜驝驞驟';
		$expects = '驐驑驒驓驔驕驖驗驘驙驚驛驜驝驞驟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '驠驡驢驣驤驥驦驧驨驩驪驫马驭驮驯';
		$expects = '驠驡驢驣驤驥驦驧驨驩驪驫马驭驮驯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '驰驱驲驳驴驵驶驷驸驹驺驻驼驽驾驿';
		$expects = '驰驱驲驳驴驵驶驷驸驹驺驻驼驽驾驿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '骀骁骂骃骄骅骆骇骈骉骊骋验骍骎骏';
		$expects = '骀骁骂骃骄骅骆骇骈骉骊骋验骍骎骏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '骐骑骒骓骔骕骖骗骘骙骚骛骜骝骞骟';
		$expects = '骐骑骒骓骔骕骖骗骘骙骚骛骜骝骞骟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '骠骡骢骣骤骥骦骧骨骩骪骫骬骭骮骯';
		$expects = '骠骡骢骣骤骥骦骧骨骩骪骫骬骭骮骯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '骰骱骲骳骴骵骶骷骸骹骺骻骼骽骾骿';
		$expects = '骰骱骲骳骴骵骶骷骸骹骺骻骼骽骾骿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '髀髁髂髃髄髅髆髇髈髉髊髋髌髍髎髏';
		$expects = '髀髁髂髃髄髅髆髇髈髉髊髋髌髍髎髏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '髐髑髒髓體髕髖髗高髙髚髛髜髝髞髟';
		$expects = '髐髑髒髓體髕髖髗高髙髚髛髜髝髞髟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '髠髡髢髣髤髥髦髧髨髩髪髫髬髭髮髯';
		$expects = '髠髡髢髣髤髥髦髧髨髩髪髫髬髭髮髯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '髰髱髲髳髴髵髶髷髸髹髺髻髼髽髾髿';
		$expects = '髰髱髲髳髴髵髶髷髸髹髺髻髼髽髾髿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鬀鬁鬂鬃鬄鬅鬆鬇鬈鬉鬊鬋鬌鬍鬎鬏';
		$expects = '鬀鬁鬂鬃鬄鬅鬆鬇鬈鬉鬊鬋鬌鬍鬎鬏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鬐鬑鬒鬓鬔鬕鬖鬗鬘鬙鬚鬛鬜鬝鬞鬟';
		$expects = '鬐鬑鬒鬓鬔鬕鬖鬗鬘鬙鬚鬛鬜鬝鬞鬟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鬠鬡鬢鬣鬤鬥鬦鬧鬨鬩鬪鬫鬬鬭鬮鬯';
		$expects = '鬠鬡鬢鬣鬤鬥鬦鬧鬨鬩鬪鬫鬬鬭鬮鬯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鬰鬱鬲鬳鬴鬵鬶鬷鬸鬹鬺鬻鬼鬽鬾鬿';
		$expects = '鬰鬱鬲鬳鬴鬵鬶鬷鬸鬹鬺鬻鬼鬽鬾鬿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '魀魁魂魃魄魅魆魇魈魉魊魋魌魍魎魏';
		$expects = '魀魁魂魃魄魅魆魇魈魉魊魋魌魍魎魏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '魐魑魒魓魔魕魖魗魘魙魚魛魜魝魞魟';
		$expects = '魐魑魒魓魔魕魖魗魘魙魚魛魜魝魞魟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '魠魡魢魣魤魥魦魧魨魩魪魫魬魭魮魯';
		$expects = '魠魡魢魣魤魥魦魧魨魩魪魫魬魭魮魯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '魰魱魲魳魴魵魶魷魸魹魺魻魼魽魾魿';
		$expects = '魰魱魲魳魴魵魶魷魸魹魺魻魼魽魾魿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鮀鮁鮂鮃鮄鮅鮆鮇鮈鮉鮊鮋鮌鮍鮎鮏';
		$expects = '鮀鮁鮂鮃鮄鮅鮆鮇鮈鮉鮊鮋鮌鮍鮎鮏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鮐鮑鮒鮓鮔鮕鮖鮗鮘鮙鮚鮛鮜鮝鮞鮟';
		$expects = '鮐鮑鮒鮓鮔鮕鮖鮗鮘鮙鮚鮛鮜鮝鮞鮟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鮠鮡鮢鮣鮤鮥鮦鮧鮨鮩鮪鮫鮬鮭鮮鮯';
		$expects = '鮠鮡鮢鮣鮤鮥鮦鮧鮨鮩鮪鮫鮬鮭鮮鮯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鮰鮱鮲鮳鮴鮵鮶鮷鮸鮹鮺鮻鮼鮽鮾鮿';
		$expects = '鮰鮱鮲鮳鮴鮵鮶鮷鮸鮹鮺鮻鮼鮽鮾鮿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鯀鯁鯂鯃鯄鯅鯆鯇鯈鯉鯊鯋鯌鯍鯎鯏';
		$expects = '鯀鯁鯂鯃鯄鯅鯆鯇鯈鯉鯊鯋鯌鯍鯎鯏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鯐鯑鯒鯓鯔鯕鯖鯗鯘鯙鯚鯛鯜鯝鯞鯟';
		$expects = '鯐鯑鯒鯓鯔鯕鯖鯗鯘鯙鯚鯛鯜鯝鯞鯟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鯠鯡鯢鯣鯤鯥鯦鯧鯨鯩鯪鯫鯬鯭鯮鯯';
		$expects = '鯠鯡鯢鯣鯤鯥鯦鯧鯨鯩鯪鯫鯬鯭鯮鯯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鯰鯱鯲鯳鯴鯵鯶鯷鯸鯹鯺鯻鯼鯽鯾鯿';
		$expects = '鯰鯱鯲鯳鯴鯵鯶鯷鯸鯹鯺鯻鯼鯽鯾鯿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鰀鰁鰂鰃鰄鰅鰆鰇鰈鰉鰊鰋鰌鰍鰎鰏';
		$expects = '鰀鰁鰂鰃鰄鰅鰆鰇鰈鰉鰊鰋鰌鰍鰎鰏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鰐鰑鰒鰓鰔鰕鰖鰗鰘鰙鰚鰛鰜鰝鰞鰟';
		$expects = '鰐鰑鰒鰓鰔鰕鰖鰗鰘鰙鰚鰛鰜鰝鰞鰟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鰠鰡鰢鰣鰤鰥鰦鰧鰨鰩鰪鰫鰬鰭鰮鰯';
		$expects = '鰠鰡鰢鰣鰤鰥鰦鰧鰨鰩鰪鰫鰬鰭鰮鰯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鰰鰱鰲鰳鰴鰵鰶鰷鰸鰹鰺鰻鰼鰽鰾鰿';
		$expects = '鰰鰱鰲鰳鰴鰵鰶鰷鰸鰹鰺鰻鰼鰽鰾鰿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鱀鱁鱂鱃鱄鱅鱆鱇鱈鱉鱊鱋鱌鱍鱎鱏';
		$expects = '鱀鱁鱂鱃鱄鱅鱆鱇鱈鱉鱊鱋鱌鱍鱎鱏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鱐鱑鱒鱓鱔鱕鱖鱗鱘鱙鱚鱛鱜鱝鱞鱟';
		$expects = '鱐鱑鱒鱓鱔鱕鱖鱗鱘鱙鱚鱛鱜鱝鱞鱟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鱠鱡鱢鱣鱤鱥鱦鱧鱨鱩鱪鱫鱬鱭鱮鱯';
		$expects = '鱠鱡鱢鱣鱤鱥鱦鱧鱨鱩鱪鱫鱬鱭鱮鱯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鱰鱱鱲鱳鱴鱵鱶鱷鱸鱹鱺鱻鱼鱽鱾鱿';
		$expects = '鱰鱱鱲鱳鱴鱵鱶鱷鱸鱹鱺鱻鱼鱽鱾鱿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鲀鲁鲂鲃鲄鲅鲆鲇鲈鲉鲊鲋鲌鲍鲎鲏';
		$expects = '鲀鲁鲂鲃鲄鲅鲆鲇鲈鲉鲊鲋鲌鲍鲎鲏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鲐鲑鲒鲓鲔鲕鲖鲗鲘鲙鲚鲛鲜鲝鲞鲟';
		$expects = '鲐鲑鲒鲓鲔鲕鲖鲗鲘鲙鲚鲛鲜鲝鲞鲟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鲠鲡鲢鲣鲤鲥鲦鲧鲨鲩鲪鲫鲬鲭鲮鲯';
		$expects = '鲠鲡鲢鲣鲤鲥鲦鲧鲨鲩鲪鲫鲬鲭鲮鲯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鲰鲱鲲鲳鲴鲵鲶鲷鲸鲹鲺鲻鲼鲽鲾鲿';
		$expects = '鲰鲱鲲鲳鲴鲵鲶鲷鲸鲹鲺鲻鲼鲽鲾鲿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鳀鳁鳂鳃鳄鳅鳆鳇鳈鳉鳊鳋鳌鳍鳎鳏';
		$expects = '鳀鳁鳂鳃鳄鳅鳆鳇鳈鳉鳊鳋鳌鳍鳎鳏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鳐鳑鳒鳓鳔鳕鳖鳗鳘鳙鳚鳛鳜鳝鳞鳟';
		$expects = '鳐鳑鳒鳓鳔鳕鳖鳗鳘鳙鳚鳛鳜鳝鳞鳟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鳠鳡鳢鳣鳤鳥鳦鳧鳨鳩鳪鳫鳬鳭鳮鳯';
		$expects = '鳠鳡鳢鳣鳤鳥鳦鳧鳨鳩鳪鳫鳬鳭鳮鳯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鳰鳱鳲鳳鳴鳵鳶鳷鳸鳹鳺鳻鳼鳽鳾鳿';
		$expects = '鳰鳱鳲鳳鳴鳵鳶鳷鳸鳹鳺鳻鳼鳽鳾鳿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鴀鴁鴂鴃鴄鴅鴆鴇鴈鴉鴊鴋鴌鴍鴎鴏';
		$expects = '鴀鴁鴂鴃鴄鴅鴆鴇鴈鴉鴊鴋鴌鴍鴎鴏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鴐鴑鴒鴓鴔鴕鴖鴗鴘鴙鴚鴛鴜鴝鴞鴟';
		$expects = '鴐鴑鴒鴓鴔鴕鴖鴗鴘鴙鴚鴛鴜鴝鴞鴟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鴠鴡鴢鴣鴤鴥鴦鴧鴨鴩鴪鴫鴬鴭鴮鴯';
		$expects = '鴠鴡鴢鴣鴤鴥鴦鴧鴨鴩鴪鴫鴬鴭鴮鴯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鴰鴱鴲鴳鴴鴵鴶鴷鴸鴹鴺鴻鴼鴽鴾鴿';
		$expects = '鴰鴱鴲鴳鴴鴵鴶鴷鴸鴹鴺鴻鴼鴽鴾鴿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鵀鵁鵂鵃鵄鵅鵆鵇鵈鵉鵊鵋鵌鵍鵎鵏';
		$expects = '鵀鵁鵂鵃鵄鵅鵆鵇鵈鵉鵊鵋鵌鵍鵎鵏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鵐鵑鵒鵓鵔鵕鵖鵗鵘鵙鵚鵛鵜鵝鵞鵟';
		$expects = '鵐鵑鵒鵓鵔鵕鵖鵗鵘鵙鵚鵛鵜鵝鵞鵟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鵠鵡鵢鵣鵤鵥鵦鵧鵨鵩鵪鵫鵬鵭鵮鵯';
		$expects = '鵠鵡鵢鵣鵤鵥鵦鵧鵨鵩鵪鵫鵬鵭鵮鵯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鵰鵱鵲鵳鵴鵵鵶鵷鵸鵹鵺鵻鵼鵽鵾鵿';
		$expects = '鵰鵱鵲鵳鵴鵵鵶鵷鵸鵹鵺鵻鵼鵽鵾鵿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鶀鶁鶂鶃鶄鶅鶆鶇鶈鶉鶊鶋鶌鶍鶎鶏';
		$expects = '鶀鶁鶂鶃鶄鶅鶆鶇鶈鶉鶊鶋鶌鶍鶎鶏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鶐鶑鶒鶓鶔鶕鶖鶗鶘鶙鶚鶛鶜鶝鶞鶟';
		$expects = '鶐鶑鶒鶓鶔鶕鶖鶗鶘鶙鶚鶛鶜鶝鶞鶟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鶠鶡鶢鶣鶤鶥鶦鶧鶨鶩鶪鶫鶬鶭鶮鶯';
		$expects = '鶠鶡鶢鶣鶤鶥鶦鶧鶨鶩鶪鶫鶬鶭鶮鶯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鶰鶱鶲鶳鶴鶵鶶鶷鶸鶹鶺鶻鶼鶽鶾鶿';
		$expects = '鶰鶱鶲鶳鶴鶵鶶鶷鶸鶹鶺鶻鶼鶽鶾鶿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鷀鷁鷂鷃鷄鷅鷆鷇鷈鷉鷊鷋鷌鷍鷎鷏';
		$expects = '鷀鷁鷂鷃鷄鷅鷆鷇鷈鷉鷊鷋鷌鷍鷎鷏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鷐鷑鷒鷓鷔鷕鷖鷗鷘鷙鷚鷛鷜鷝鷞鷟';
		$expects = '鷐鷑鷒鷓鷔鷕鷖鷗鷘鷙鷚鷛鷜鷝鷞鷟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鷠鷡鷢鷣鷤鷥鷦鷧鷨鷩鷪鷫鷬鷭鷮鷯';
		$expects = '鷠鷡鷢鷣鷤鷥鷦鷧鷨鷩鷪鷫鷬鷭鷮鷯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鷰鷱鷲鷳鷴鷵鷶鷷鷸鷹鷺鷻鷼鷽鷾鷿';
		$expects = '鷰鷱鷲鷳鷴鷵鷶鷷鷸鷹鷺鷻鷼鷽鷾鷿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鸀鸁鸂鸃鸄鸅鸆鸇鸈鸉鸊鸋鸌鸍鸎鸏';
		$expects = '鸀鸁鸂鸃鸄鸅鸆鸇鸈鸉鸊鸋鸌鸍鸎鸏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鸐鸑鸒鸓鸔鸕鸖鸗鸘鸙鸚鸛鸜鸝鸞鸟';
		$expects = '鸐鸑鸒鸓鸔鸕鸖鸗鸘鸙鸚鸛鸜鸝鸞鸟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鸠鸡鸢鸣鸤鸥鸦鸧鸨鸩鸪鸫鸬鸭鸮鸯';
		$expects = '鸠鸡鸢鸣鸤鸥鸦鸧鸨鸩鸪鸫鸬鸭鸮鸯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鸰鸱鸲鸳鸴鸵鸶鸷鸸鸹鸺鸻鸼鸽鸾鸿';
		$expects = '鸰鸱鸲鸳鸴鸵鸶鸷鸸鸹鸺鸻鸼鸽鸾鸿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鹀鹁鹂鹃鹄鹅鹆鹇鹈鹉鹊鹋鹌鹍鹎鹏';
		$expects = '鹀鹁鹂鹃鹄鹅鹆鹇鹈鹉鹊鹋鹌鹍鹎鹏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鹐鹑鹒鹓鹔鹕鹖鹗鹘鹙鹚鹛鹜鹝鹞鹟';
		$expects = '鹐鹑鹒鹓鹔鹕鹖鹗鹘鹙鹚鹛鹜鹝鹞鹟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鹠鹡鹢鹣鹤鹥鹦鹧鹨鹩鹪鹫鹬鹭鹮鹯';
		$expects = '鹠鹡鹢鹣鹤鹥鹦鹧鹨鹩鹪鹫鹬鹭鹮鹯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鹰鹱鹲鹳鹴鹵鹶鹷鹸鹹鹺鹻鹼鹽鹾鹿';
		$expects = '鹰鹱鹲鹳鹴鹵鹶鹷鹸鹹鹺鹻鹼鹽鹾鹿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '麀麁麂麃麄麅麆麇麈麉麊麋麌麍麎麏';
		$expects = '麀麁麂麃麄麅麆麇麈麉麊麋麌麍麎麏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '麐麑麒麓麔麕麖麗麘麙麚麛麜麝麞麟';
		$expects = '麐麑麒麓麔麕麖麗麘麙麚麛麜麝麞麟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '麠麡麢麣麤麥麦麧麨麩麪麫麬麭麮麯';
		$expects = '麠麡麢麣麤麥麦麧麨麩麪麫麬麭麮麯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '麰麱麲麳麴麵麶麷麸麹麺麻麼麽麾麿';
		$expects = '麰麱麲麳麴麵麶麷麸麹麺麻麼麽麾麿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '黀黁黂黃黄黅黆黇黈黉黊黋黌黍黎黏';
		$expects = '黀黁黂黃黄黅黆黇黈黉黊黋黌黍黎黏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '黐黑黒黓黔黕黖黗默黙黚黛黜黝點黟';
		$expects = '黐黑黒黓黔黕黖黗默黙黚黛黜黝點黟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '黠黡黢黣黤黥黦黧黨黩黪黫黬黭黮黯';
		$expects = '黠黡黢黣黤黥黦黧黨黩黪黫黬黭黮黯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '黰黱黲黳黴黵黶黷黸黹黺黻黼黽黾黿';
		$expects = '黰黱黲黳黴黵黶黷黸黹黺黻黼黽黾黿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鼀鼁鼂鼃鼄鼅鼆鼇鼈鼉鼊鼋鼌鼍鼎鼏';
		$expects = '鼀鼁鼂鼃鼄鼅鼆鼇鼈鼉鼊鼋鼌鼍鼎鼏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鼐鼑鼒鼓鼔鼕鼖鼗鼘鼙鼚鼛鼜鼝鼞鼟';
		$expects = '鼐鼑鼒鼓鼔鼕鼖鼗鼘鼙鼚鼛鼜鼝鼞鼟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鼠鼡鼢鼣鼤鼥鼦鼧鼨鼩鼪鼫鼬鼭鼮鼯';
		$expects = '鼠鼡鼢鼣鼤鼥鼦鼧鼨鼩鼪鼫鼬鼭鼮鼯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鼰鼱鼲鼳鼴鼵鼶鼷鼸鼹鼺鼻鼼鼽鼾鼿';
		$expects = '鼰鼱鼲鼳鼴鼵鼶鼷鼸鼹鼺鼻鼼鼽鼾鼿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '齀齁齂齃齄齅齆齇齈齉齊齋齌齍齎齏';
		$expects = '齀齁齂齃齄齅齆齇齈齉齊齋齌齍齎齏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '齐齑齒齓齔齕齖齗齘齙齚齛齜齝齞齟';
		$expects = '齐齑齒齓齔齕齖齗齘齙齚齛齜齝齞齟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '齠齡齢齣齤齥齦齧齨齩齪齫齬齭齮齯';
		$expects = '齠齡齢齣齤齥齦齧齨齩齪齫齬齭齮齯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '齰齱齲齳齴齵齶齷齸齹齺齻齼齽齾齿';
		$expects = '齰齱齲齳齴齵齶齷齸齹齺齻齼齽齾齿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '龀龁龂龃龄龅龆龇龈龉龊龋龌龍龎龏';
		$expects = '龀龁龂龃龄龅龆龇龈龉龊龋龌龍龎龏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '龐龑龒龓龔龕龖龗龘龙龚龛龜龝龞龟';
		$expects = '龐龑龒龓龔龕龖龗龘龙龚龛龜龝龞龟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '龠龡龢龣龤龥龦龧龨龩龪龫龬龭龮龯';
		$expects = '龠龡龢龣龤龥----------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '龰龱龲龳龴龵龶龷龸龹龺龻龼龽龾龿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鿀鿁鿂鿃鿄鿅鿆鿇鿈鿉鿊鿋鿌鿍鿎鿏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鿐鿑鿒鿓鿔鿕鿖鿗鿘鿙鿚鿛鿜鿝鿞鿟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鿠鿡鿢鿣鿤鿥鿦鿧鿨鿩鿪鿫鿬鿭鿮鿯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '鿰鿱鿲鿳鿴鿵鿶鿷鿸鿹鿺鿻鿼鿽鿾鿿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSectiona method
	 *
	 * Testing characters a000 - afff
	 *
	 * @return void
	 */
	public function testSectiona() {
		$string = 'ꀀꀁꀂꀃꀄꀅꀆꀇꀈꀉꀊꀋꀌꀍꀎꀏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꀐꀑꀒꀓꀔꀕꀖꀗꀘꀙꀚꀛꀜꀝꀞꀟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꀠꀡꀢꀣꀤꀥꀦꀧꀨꀩꀪꀫꀬꀭꀮꀯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꀰꀱꀲꀳꀴꀵꀶꀷꀸꀹꀺꀻꀼꀽꀾꀿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꁀꁁꁂꁃꁄꁅꁆꁇꁈꁉꁊꁋꁌꁍꁎꁏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꁐꁑꁒꁓꁔꁕꁖꁗꁘꁙꁚꁛꁜꁝꁞꁟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꁠꁡꁢꁣꁤꁥꁦꁧꁨꁩꁪꁫꁬꁭꁮꁯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꁰꁱꁲꁳꁴꁵꁶꁷꁸꁹꁺꁻꁼꁽꁾꁿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꂀꂁꂂꂃꂄꂅꂆꂇꂈꂉꂊꂋꂌꂍꂎꂏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꂐꂑꂒꂓꂔꂕꂖꂗꂘꂙꂚꂛꂜꂝꂞꂟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꂠꂡꂢꂣꂤꂥꂦꂧꂨꂩꂪꂫꂬꂭꂮꂯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꂰꂱꂲꂳꂴꂵꂶꂷꂸꂹꂺꂻꂼꂽꂾꂿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꃀꃁꃂꃃꃄꃅꃆꃇꃈꃉꃊꃋꃌꃍꃎꃏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꃐꃑꃒꃓꃔꃕꃖꃗꃘꃙꃚꃛꃜꃝꃞꃟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꃠꃡꃢꃣꃤꃥꃦꃧꃨꃩꃪꃫꃬꃭꃮꃯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꃰꃱꃲꃳꃴꃵꃶꃷꃸꃹꃺꃻꃼꃽꃾꃿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꄀꄁꄂꄃꄄꄅꄆꄇꄈꄉꄊꄋꄌꄍꄎꄏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꄐꄑꄒꄓꄔꄕꄖꄗꄘꄙꄚꄛꄜꄝꄞꄟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꄠꄡꄢꄣꄤꄥꄦꄧꄨꄩꄪꄫꄬꄭꄮꄯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꄰꄱꄲꄳꄴꄵꄶꄷꄸꄹꄺꄻꄼꄽꄾꄿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꅀꅁꅂꅃꅄꅅꅆꅇꅈꅉꅊꅋꅌꅍꅎꅏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꅐꅑꅒꅓꅔꅕꅖꅗꅘꅙꅚꅛꅜꅝꅞꅟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꅠꅡꅢꅣꅤꅥꅦꅧꅨꅩꅪꅫꅬꅭꅮꅯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꅰꅱꅲꅳꅴꅵꅶꅷꅸꅹꅺꅻꅼꅽꅾꅿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꆀꆁꆂꆃꆄꆅꆆꆇꆈꆉꆊꆋꆌꆍꆎꆏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꆐꆑꆒꆓꆔꆕꆖꆗꆘꆙꆚꆛꆜꆝꆞꆟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꆠꆡꆢꆣꆤꆥꆦꆧꆨꆩꆪꆫꆬꆭꆮꆯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꆰꆱꆲꆳꆴꆵꆶꆷꆸꆹꆺꆻꆼꆽꆾꆿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꇀꇁꇂꇃꇄꇅꇆꇇꇈꇉꇊꇋꇌꇍꇎꇏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꇐꇑꇒꇓꇔꇕꇖꇗꇘꇙꇚꇛꇜꇝꇞꇟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꇠꇡꇢꇣꇤꇥꇦꇧꇨꇩꇪꇫꇬꇭꇮꇯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꇰꇱꇲꇳꇴꇵꇶꇷꇸꇹꇺꇻꇼꇽꇾꇿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꈀꈁꈂꈃꈄꈅꈆꈇꈈꈉꈊꈋꈌꈍꈎꈏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꈐꈑꈒꈓꈔꈕꈖꈗꈘꈙꈚꈛꈜꈝꈞꈟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꈠꈡꈢꈣꈤꈥꈦꈧꈨꈩꈪꈫꈬꈭꈮꈯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꈰꈱꈲꈳꈴꈵꈶꈷꈸꈹꈺꈻꈼꈽꈾꈿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꉀꉁꉂꉃꉄꉅꉆꉇꉈꉉꉊꉋꉌꉍꉎꉏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꉐꉑꉒꉓꉔꉕꉖꉗꉘꉙꉚꉛꉜꉝꉞꉟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꉠꉡꉢꉣꉤꉥꉦꉧꉨꉩꉪꉫꉬꉭꉮꉯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꉰꉱꉲꉳꉴꉵꉶꉷꉸꉹꉺꉻꉼꉽꉾꉿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꊀꊁꊂꊃꊄꊅꊆꊇꊈꊉꊊꊋꊌꊍꊎꊏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꊐꊑꊒꊓꊔꊕꊖꊗꊘꊙꊚꊛꊜꊝꊞꊟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꊠꊡꊢꊣꊤꊥꊦꊧꊨꊩꊪꊫꊬꊭꊮꊯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꊰꊱꊲꊳꊴꊵꊶꊷꊸꊹꊺꊻꊼꊽꊾꊿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꋀꋁꋂꋃꋄꋅꋆꋇꋈꋉꋊꋋꋌꋍꋎꋏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꋐꋑꋒꋓꋔꋕꋖꋗꋘꋙꋚꋛꋜꋝꋞꋟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꋠꋡꋢꋣꋤꋥꋦꋧꋨꋩꋪꋫꋬꋭꋮꋯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꋰꋱꋲꋳꋴꋵꋶꋷꋸꋹꋺꋻꋼꋽꋾꋿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꌀꌁꌂꌃꌄꌅꌆꌇꌈꌉꌊꌋꌌꌍꌎꌏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꌐꌑꌒꌓꌔꌕꌖꌗꌘꌙꌚꌛꌜꌝꌞꌟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꌠꌡꌢꌣꌤꌥꌦꌧꌨꌩꌪꌫꌬꌭꌮꌯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꌰꌱꌲꌳꌴꌵꌶꌷꌸꌹꌺꌻꌼꌽꌾꌿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꍀꍁꍂꍃꍄꍅꍆꍇꍈꍉꍊꍋꍌꍍꍎꍏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꍐꍑꍒꍓꍔꍕꍖꍗꍘꍙꍚꍛꍜꍝꍞꍟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꍠꍡꍢꍣꍤꍥꍦꍧꍨꍩꍪꍫꍬꍭꍮꍯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꍰꍱꍲꍳꍴꍵꍶꍷꍸꍹꍺꍻꍼꍽꍾꍿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꎀꎁꎂꎃꎄꎅꎆꎇꎈꎉꎊꎋꎌꎍꎎꎏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꎐꎑꎒꎓꎔꎕꎖꎗꎘꎙꎚꎛꎜꎝꎞꎟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꎠꎡꎢꎣꎤꎥꎦꎧꎨꎩꎪꎫꎬꎭꎮꎯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꎰꎱꎲꎳꎴꎵꎶꎷꎸꎹꎺꎻꎼꎽꎾꎿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꏀꏁꏂꏃꏄꏅꏆꏇꏈꏉꏊꏋꏌꏍꏎꏏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꏐꏑꏒꏓꏔꏕꏖꏗꏘꏙꏚꏛꏜꏝꏞꏟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꏠꏡꏢꏣꏤꏥꏦꏧꏨꏩꏪꏫꏬꏭꏮꏯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꏰꏱꏲꏳꏴꏵꏶꏷꏸꏹꏺꏻꏼꏽꏾꏿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꐀꐁꐂꐃꐄꐅꐆꐇꐈꐉꐊꐋꐌꐍꐎꐏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꐐꐑꐒꐓꐔꐕꐖꐗꐘꐙꐚꐛꐜꐝꐞꐟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꐠꐡꐢꐣꐤꐥꐦꐧꐨꐩꐪꐫꐬꐭꐮꐯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꐰꐱꐲꐳꐴꐵꐶꐷꐸꐹꐺꐻꐼꐽꐾꐿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꑀꑁꑂꑃꑄꑅꑆꑇꑈꑉꑊꑋꑌꑍꑎꑏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꑐꑑꑒꑓꑔꑕꑖꑗꑘꑙꑚꑛꑜꑝꑞꑟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꑠꑡꑢꑣꑤꑥꑦꑧꑨꑩꑪꑫꑬꑭꑮꑯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꑰꑱꑲꑳꑴꑵꑶꑷꑸꑹꑺꑻꑼꑽꑾꑿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꒀꒁꒂꒃꒄꒅꒆꒇꒈꒉꒊꒋꒌ꒍꒎꒏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꒐꒑꒒꒓꒔꒕꒖꒗꒘꒙꒚꒛꒜꒝꒞꒟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꒠꒡꒢꒣꒤꒥꒦꒧꒨꒩꒪꒫꒬꒭꒮꒯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꒰꒱꒲꒳꒴꒵꒶꒷꒸꒹꒺꒻꒼꒽꒾꒿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꓀꓁꓂꓃꓄꓅꓆꓇꓈꓉꓊꓋꓌꓍꓎꓏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꓐꓑꓒꓓꓔꓕꓖꓗꓘꓙꓚꓛꓜꓝꓞꓟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꓠꓡꓢꓣꓤꓥꓦꓧꓨꓩꓪꓫꓬꓭꓮꓯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꓰꓱꓲꓳꓴꓵꓶꓷꓸꓹꓺꓻꓼꓽ꓾꓿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꔀꔁꔂꔃꔄꔅꔆꔇꔈꔉꔊꔋꔌꔍꔎꔏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꔐꔑꔒꔓꔔꔕꔖꔗꔘꔙꔚꔛꔜꔝꔞꔟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꔠꔡꔢꔣꔤꔥꔦꔧꔨꔩꔪꔫꔬꔭꔮꔯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꔰꔱꔲꔳꔴꔵꔶꔷꔸꔹꔺꔻꔼꔽꔾꔿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꕀꕁꕂꕃꕄꕅꕆꕇꕈꕉꕊꕋꕌꕍꕎꕏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꕐꕑꕒꕓꕔꕕꕖꕗꕘꕙꕚꕛꕜꕝꕞꕟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꕠꕡꕢꕣꕤꕥꕦꕧꕨꕩꕪꕫꕬꕭꕮꕯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꕰꕱꕲꕳꕴꕵꕶꕷꕸꕹꕺꕻꕼꕽꕾꕿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꖀꖁꖂꖃꖄꖅꖆꖇꖈꖉꖊꖋꖌꖍꖎꖏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꖐꖑꖒꖓꖔꖕꖖꖗꖘꖙꖚꖛꖜꖝꖞꖟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꖠꖡꖢꖣꖤꖥꖦꖧꖨꖩꖪꖫꖬꖭꖮꖯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꖰꖱꖲꖳꖴꖵꖶꖷꖸꖹꖺꖻꖼꖽꖾꖿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꗀꗁꗂꗃꗄꗅꗆꗇꗈꗉꗊꗋꗌꗍꗎꗏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꗐꗑꗒꗓꗔꗕꗖꗗꗘꗙꗚꗛꗜꗝꗞꗟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꗠꗡꗢꗣꗤꗥꗦꗧꗨꗩꗪꗫꗬꗭꗮꗯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꗰꗱꗲꗳꗴꗵꗶꗷꗸꗹꗺꗻꗼꗽꗾꗿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꘀꘁꘂꘃꘄꘅꘆꘇꘈꘉꘊꘋꘌ꘍꘎꘏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꘐꘑꘒꘓꘔꘕꘖꘗꘘꘙꘚꘛꘜꘝꘞꘟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꘠꘡꘢꘣꘤꘥꘦꘧꘨꘩ꘪꘫ꘬꘭꘮꘯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꘰꘱꘲꘳꘴꘵꘶꘷꘸꘹꘺꘻꘼꘽꘾꘿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꙀꙁꙂꙃꙄꙅꙆꙇꙈꙉꙊꙋꙌꙍꙎꙏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꙐꙑꙒꙓꙔꙕꙖꙗꙘꙙꙚꙛꙜꙝꙞꙟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꙠꙡꙢꙣꙤꙥꙦꙧꙨꙩꙪꙫꙬꙭꙮ꙯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꙰꙱꙲꙳ꙴꙵꙶꙷꙸꙹꙺꙻ꙼꙽꙾ꙿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꚀꚁꚂꚃꚄꚅꚆꚇꚈꚉꚊꚋꚌꚍꚎꚏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꚐꚑꚒꚓꚔꚕꚖꚗꚘꚙꚚꚛꚜꚝꚞꚟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꚠꚡꚢꚣꚤꚥꚦꚧꚨꚩꚪꚫꚬꚭꚮꚯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꚰꚱꚲꚳꚴꚵꚶꚷꚸꚹꚺꚻꚼꚽꚾꚿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꛀꛁꛂꛃꛄꛅꛆꛇꛈꛉꛊꛋꛌꛍꛎꛏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꛐꛑꛒꛓꛔꛕꛖꛗꛘꛙꛚꛛꛜꛝꛞꛟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꛠꛡꛢꛣꛤꛥꛦꛧꛨꛩꛪꛫꛬꛭꛮꛯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꛰꛱꛲꛳꛴꛵꛶꛷꛸꛹꛺꛻꛼꛽꛾꛿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꜀꜁꜂꜃꜄꜅꜆꜇꜈꜉꜊꜋꜌꜍꜎꜏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꜐꜑꜒꜓꜔꜕꜖ꜗꜘꜙꜚꜛꜜꜝꜞꜟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꜠꜡ꜢꜣꜤꜥꜦꜧꜨꜩꜪꜫꜬꜭꜮꜯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꜰꜱꜲꜳꜴꜵꜶꜷꜸꜹꜺꜻꜼꜽꜾꜿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꝀꝁꝂꝃꝄꝅꝆꝇꝈꝉꝊꝋꝌꝍꝎꝏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꝐꝑꝒꝓꝔꝕꝖꝗꝘꝙꝚꝛꝜꝝꝞꝟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꝠꝡꝢꝣꝤꝥꝦꝧꝨꝩꝪꝫꝬꝭꝮꝯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꝰꝱꝲꝳꝴꝵꝶꝷꝸꝹꝺꝻꝼꝽꝾꝿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꞀꞁꞂꞃꞄꞅꞆꞇꞈ꞉꞊ꞋꞌꞍꞎꞏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꞐꞑꞒꞓꞔꞕꞖꞗꞘꞙꞚꞛꞜꞝꞞꞟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꞠꞡꞢꞣꞤꞥꞦꞧꞨꞩꞪꞫꞬꞭꞮꞯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꞰꞱꞲꞳꞴꞵꞶꞷꞸꞹꞺꞻꞼꞽꞾꞿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꟀꟁꟂꟃꟄꟅꟆꟇꟈꟉꟊꟋꟌꟍ꟎꟏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'Ꟑꟑ꟒ꟓ꟔ꟕꟖꟗꟘꟙꟚꟛꟜ꟝꟞꟟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꟠꟡꟢꟣꟤꟥꟦꟧꟨꟩꟪꟫꟬꟭꟮꟯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꟰꟱ꟲꟳꟴꟵꟶꟷꟸꟹꟺꟻꟼꟽꟾꟿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꠀꠁꠂꠃꠄꠅ꠆ꠇꠈꠉꠊꠋꠌꠍꠎꠏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꠐꠑꠒꠓꠔꠕꠖꠗꠘꠙꠚꠛꠜꠝꠞꠟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꠠꠡꠢꠣꠤꠥꠦꠧ꠨꠩꠪꠫꠬꠭꠮꠯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꠰꠱꠲꠳꠴꠵꠶꠷꠸꠹꠺꠻꠼꠽꠾꠿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꡀꡁꡂꡃꡄꡅꡆꡇꡈꡉꡊꡋꡌꡍꡎꡏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꡐꡑꡒꡓꡔꡕꡖꡗꡘꡙꡚꡛꡜꡝꡞꡟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꡠꡡꡢꡣꡤꡥꡦꡧꡨꡩꡪꡫꡬꡭꡮꡯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꡰꡱꡲꡳ꡴꡵꡶꡷꡸꡹꡺꡻꡼꡽꡾꡿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꢀꢁꢂꢃꢄꢅꢆꢇꢈꢉꢊꢋꢌꢍꢎꢏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꢐꢑꢒꢓꢔꢕꢖꢗꢘꢙꢚꢛꢜꢝꢞꢟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꢠꢡꢢꢣꢤꢥꢦꢧꢨꢩꢪꢫꢬꢭꢮꢯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꢰꢱꢲꢳꢴꢵꢶꢷꢸꢹꢺꢻꢼꢽꢾꢿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꣀꣁꣂꣃ꣄ꣅ꣆꣇꣈꣉꣊꣋꣌꣍꣎꣏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꣐꣑꣒꣓꣔꣕꣖꣗꣘꣙꣚꣛꣜꣝꣞꣟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꣠꣡꣢꣣꣤꣥꣦꣧꣨꣩꣪꣫꣬꣭꣮꣯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꣰꣱ꣲꣳꣴꣵꣶꣷ꣸꣹꣺ꣻ꣼ꣽꣾꣿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꤀꤁꤂꤃꤄꤅꤆꤇꤈꤉ꤊꤋꤌꤍꤎꤏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꤐꤑꤒꤓꤔꤕꤖꤗꤘꤙꤚꤛꤜꤝꤞꤟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꤠꤡꤢꤣꤤꤥꤦꤧꤨꤩꤪ꤫꤬꤭꤮꤯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꤰꤱꤲꤳꤴꤵꤶꤷꤸꤹꤺꤻꤼꤽꤾꤿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꥀꥁꥂꥃꥄꥅꥆꥇꥈꥉꥊꥋꥌꥍꥎꥏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꥐꥑꥒ꥓꥔꥕꥖꥗꥘꥙꥚꥛꥜꥝꥞꥟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꥠꥡꥢꥣꥤꥥꥦꥧꥨꥩꥪꥫꥬꥭꥮꥯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꥰꥱꥲꥳꥴꥵꥶꥷꥸꥹꥺꥻꥼ꥽꥾꥿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꦀꦁꦂꦃꦄꦅꦆꦇꦈꦉꦊꦋꦌꦍꦎꦏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꦐꦑꦒꦓꦔꦕꦖꦗꦘꦙꦚꦛꦜꦝꦞꦟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꦠꦡꦢꦣꦤꦥꦦꦧꦨꦩꦪꦫꦬꦭꦮꦯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꦰꦱꦲ꦳ꦴꦵꦶꦷꦸꦹꦺꦻꦼꦽꦾꦿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꧀꧁꧂꧃꧄꧅꧆꧇꧈꧉꧊꧋꧌꧍꧎ꧏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꧐꧑꧒꧓꧔꧕꧖꧗꧘꧙꧚꧛꧜꧝꧞꧟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꧠꧡꧢꧣꧤꧥꧦꧧꧨꧩꧪꧫꧬꧭꧮꧯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꧰꧱꧲꧳꧴꧵꧶꧷꧸꧹ꧺꧻꧼꧽꧾ꧿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꨀꨁꨂꨃꨄꨅꨆꨇꨈꨉꨊꨋꨌꨍꨎꨏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꨐꨑꨒꨓꨔꨕꨖꨗꨘꨙꨚꨛꨜꨝꨞꨟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꨠꨡꨢꨣꨤꨥꨦꨧꨨꨩꨪꨫꨬꨭꨮꨯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꨰꨱꨲꨳꨴꨵꨶ꨷꨸꨹꨺꨻꨼꨽꨾꨿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꩀꩁꩂꩃꩄꩅꩆꩇꩈꩉꩊꩋꩌꩍ꩎꩏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꩐꩑꩒꩓꩔꩕꩖꩗꩘꩙꩚꩛꩜꩝꩞꩟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꩠꩡꩢꩣꩤꩥꩦꩧꩨꩩꩪꩫꩬꩭꩮꩯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꩰꩱꩲꩳꩴꩵꩶ꩷꩸꩹ꩺꩻꩼꩽꩾꩿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꪀꪁꪂꪃꪄꪅꪆꪇꪈꪉꪊꪋꪌꪍꪎꪏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꪐꪑꪒꪓꪔꪕꪖꪗꪘꪙꪚꪛꪜꪝꪞꪟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꪠꪡꪢꪣꪤꪥꪦꪧꪨꪩꪪꪫꪬꪭꪮꪯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꪰꪱꪴꪲꪳꪵꪶꪷꪸꪹꪺꪻꪼꪽꪾ꪿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꫀ꫁ꫂ꫃꫄꫅꫆꫇꫈꫉꫊꫋꫌꫍꫎꫏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꫐꫑꫒꫓꫔꫕꫖꫗꫘꫙꫚ꫛꫜꫝ꫞꫟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꫠꫡꫢꫣꫤꫥꫦꫧꫨꫩꫪꫫꫬꫭꫮꫯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꫰꫱ꫲꫳꫴꫵ꫶꫷꫸꫹꫺꫻꫼꫽꫾꫿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꬀ꬁꬂꬃꬄꬅꬆ꬇꬈ꬉꬊꬋꬌꬍꬎ꬏';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꬐ꬑꬒꬓꬔꬕꬖ꬗꬘꬙꬚꬛꬜꬝꬞꬟';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꬠꬡꬢꬣꬤꬥꬦ꬧ꬨꬩꬪꬫꬬꬭꬮ꬯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꬰꬱꬲꬳꬴꬵꬶꬷꬸꬹꬺꬻꬼꬽꬾꬿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꭀꭁꭂꭃꭄꭅꭆꭇꭈꭉꭊꭋꭌꭍꭎꭏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꭐꭑꭒꭓꭔꭕꭖꭗꭘꭙꭚ꭛ꭜꭝꭞꭟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꭠꭡꭢꭣꭤꭥꭦꭧꭨꭩ꭪꭫꭬꭭꭮꭯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꭰꭱꭲꭳꭴꭵꭶꭷꭸꭹꭺꭻꭼꭽꭾꭿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꮀꮁꮂꮃꮄꮅꮆꮇꮈꮉꮊꮋꮌꮍꮎꮏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꮐꮑꮒꮓꮔꮕꮖꮗꮘꮙꮚꮛꮜꮝꮞꮟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꮠꮡꮢꮣꮤꮥꮦꮧꮨꮩꮪꮫꮬꮭꮮꮯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꮰꮱꮲꮳꮴꮵꮶꮷꮸꮹꮺꮻꮼꮽꮾꮿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꯀꯁꯂꯃꯄꯅꯆꯇꯈꯉꯊꯋꯌꯍꯎꯏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꯐꯑꯒꯓꯔꯕꯖꯗꯘꯙꯚꯛꯜꯝꯞꯟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ꯠꯡꯢꯣꯤꯥꯦꯧꯨꯩꯪ꯫꯬꯭꯮꯯';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꯰꯱꯲꯳꯴꯵꯶꯷꯸꯹꯺꯻꯼꯽꯾꯿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '가각갂갃간갅갆갇갈갉갊갋갌갍갎갏';
		$expects = '가각갂갃간갅갆갇갈갉갊갋갌갍갎갏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '감갑값갓갔강갖갗갘같갚갛개객갞갟';
		$expects = '감갑값갓갔강갖갗갘같갚갛개객갞갟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '갠갡갢갣갤갥갦갧갨갩갪갫갬갭갮갯';
		$expects = '갠갡갢갣갤갥갦갧갨갩갪갫갬갭갮갯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '갰갱갲갳갴갵갶갷갸갹갺갻갼갽갾갿';
		$expects = '갰갱갲갳갴갵갶갷갸갹갺갻갼갽갾갿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '걀걁걂걃걄걅걆걇걈걉걊걋걌걍걎걏';
		$expects = '걀걁걂걃걄걅걆걇걈걉걊걋걌걍걎걏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '걐걑걒걓걔걕걖걗걘걙걚걛걜걝걞걟';
		$expects = '걐걑걒걓걔걕걖걗걘걙걚걛걜걝걞걟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '걠걡걢걣걤걥걦걧걨걩걪걫걬걭걮걯';
		$expects = '걠걡걢걣걤걥걦걧걨걩걪걫걬걭걮걯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '거걱걲걳건걵걶걷걸걹걺걻걼걽걾걿';
		$expects = '거걱걲걳건걵걶걷걸걹걺걻걼걽걾걿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '검겁겂것겄겅겆겇겈겉겊겋게겍겎겏';
		$expects = '검겁겂것겄겅겆겇겈겉겊겋게겍겎겏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '겐겑겒겓겔겕겖겗겘겙겚겛겜겝겞겟';
		$expects = '겐겑겒겓겔겕겖겗겘겙겚겛겜겝겞겟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '겠겡겢겣겤겥겦겧겨격겪겫견겭겮겯';
		$expects = '겠겡겢겣겤겥겦겧겨격겪겫견겭겮겯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '결겱겲겳겴겵겶겷겸겹겺겻겼경겾겿';
		$expects = '결겱겲겳겴겵겶겷겸겹겺겻겼경겾겿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '곀곁곂곃계곅곆곇곈곉곊곋곌곍곎곏';
		$expects = '곀곁곂곃계곅곆곇곈곉곊곋곌곍곎곏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '곐곑곒곓곔곕곖곗곘곙곚곛곜곝곞곟';
		$expects = '곐곑곒곓곔곕곖곗곘곙곚곛곜곝곞곟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '고곡곢곣곤곥곦곧골곩곪곫곬곭곮곯';
		$expects = '고곡곢곣곤곥곦곧골곩곪곫곬곭곮곯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '곰곱곲곳곴공곶곷곸곹곺곻과곽곾곿';
		$expects = '곰곱곲곳곴공곶곷곸곹곺곻과곽곾곿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '관괁괂괃괄괅괆괇괈괉괊괋괌괍괎괏';
		$expects = '관괁괂괃괄괅괆괇괈괉괊괋괌괍괎괏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '괐광괒괓괔괕괖괗괘괙괚괛괜괝괞괟';
		$expects = '괐광괒괓괔괕괖괗괘괙괚괛괜괝괞괟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '괠괡괢괣괤괥괦괧괨괩괪괫괬괭괮괯';
		$expects = '괠괡괢괣괤괥괦괧괨괩괪괫괬괭괮괯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '괰괱괲괳괴괵괶괷괸괹괺괻괼괽괾괿';
		$expects = '괰괱괲괳괴괵괶괷괸괹괺괻괼괽괾괿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '굀굁굂굃굄굅굆굇굈굉굊굋굌굍굎굏';
		$expects = '굀굁굂굃굄굅굆굇굈굉굊굋굌굍굎굏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '교굑굒굓굔굕굖굗굘굙굚굛굜굝굞굟';
		$expects = '교굑굒굓굔굕굖굗굘굙굚굛굜굝굞굟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '굠굡굢굣굤굥굦굧굨굩굪굫구국굮굯';
		$expects = '굠굡굢굣굤굥굦굧굨굩굪굫구국굮굯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '군굱굲굳굴굵굶굷굸굹굺굻굼굽굾굿';
		$expects = '군굱굲굳굴굵굶굷굸굹굺굻굼굽굾굿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '궀궁궂궃궄궅궆궇궈궉궊궋권궍궎궏';
		$expects = '궀궁궂궃궄궅궆궇궈궉궊궋권궍궎궏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '궐궑궒궓궔궕궖궗궘궙궚궛궜궝궞궟';
		$expects = '궐궑궒궓궔궕궖궗궘궙궚궛궜궝궞궟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '궠궡궢궣궤궥궦궧궨궩궪궫궬궭궮궯';
		$expects = '궠궡궢궣궤궥궦궧궨궩궪궫궬궭궮궯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '궰궱궲궳궴궵궶궷궸궹궺궻궼궽궾궿';
		$expects = '궰궱궲궳궴궵궶궷궸궹궺궻궼궽궾궿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '귀귁귂귃귄귅귆귇귈귉귊귋귌귍귎귏';
		$expects = '귀귁귂귃귄귅귆귇귈귉귊귋귌귍귎귏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '귐귑귒귓귔귕귖귗귘귙귚귛규귝귞귟';
		$expects = '귐귑귒귓귔귕귖귗귘귙귚귛규귝귞귟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '균귡귢귣귤귥귦귧귨귩귪귫귬귭귮귯';
		$expects = '균귡귢귣귤귥귦귧귨귩귪귫귬귭귮귯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '귰귱귲귳귴귵귶귷그극귺귻근귽귾귿';
		$expects = '귰귱귲귳귴귵귶귷그극귺귻근귽귾귿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '글긁긂긃긄긅긆긇금급긊긋긌긍긎긏';
		$expects = '글긁긂긃긄긅긆긇금급긊긋긌긍긎긏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '긐긑긒긓긔긕긖긗긘긙긚긛긜긝긞긟';
		$expects = '긐긑긒긓긔긕긖긗긘긙긚긛긜긝긞긟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '긠긡긢긣긤긥긦긧긨긩긪긫긬긭긮긯';
		$expects = '긠긡긢긣긤긥긦긧긨긩긪긫긬긭긮긯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '기긱긲긳긴긵긶긷길긹긺긻긼긽긾긿';
		$expects = '기긱긲긳긴긵긶긷길긹긺긻긼긽긾긿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '김깁깂깃깄깅깆깇깈깉깊깋까깍깎깏';
		$expects = '김깁깂깃깄깅깆깇깈깉깊깋까깍깎깏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '깐깑깒깓깔깕깖깗깘깙깚깛깜깝깞깟';
		$expects = '깐깑깒깓깔깕깖깗깘깙깚깛깜깝깞깟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '깠깡깢깣깤깥깦깧깨깩깪깫깬깭깮깯';
		$expects = '깠깡깢깣깤깥깦깧깨깩깪깫깬깭깮깯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '깰깱깲깳깴깵깶깷깸깹깺깻깼깽깾깿';
		$expects = '깰깱깲깳깴깵깶깷깸깹깺깻깼깽깾깿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꺀꺁꺂꺃꺄꺅꺆꺇꺈꺉꺊꺋꺌꺍꺎꺏';
		$expects = '꺀꺁꺂꺃꺄꺅꺆꺇꺈꺉꺊꺋꺌꺍꺎꺏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꺐꺑꺒꺓꺔꺕꺖꺗꺘꺙꺚꺛꺜꺝꺞꺟';
		$expects = '꺐꺑꺒꺓꺔꺕꺖꺗꺘꺙꺚꺛꺜꺝꺞꺟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꺠꺡꺢꺣꺤꺥꺦꺧꺨꺩꺪꺫꺬꺭꺮꺯';
		$expects = '꺠꺡꺢꺣꺤꺥꺦꺧꺨꺩꺪꺫꺬꺭꺮꺯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꺰꺱꺲꺳꺴꺵꺶꺷꺸꺹꺺꺻꺼꺽꺾꺿';
		$expects = '꺰꺱꺲꺳꺴꺵꺶꺷꺸꺹꺺꺻꺼꺽꺾꺿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '껀껁껂껃껄껅껆껇껈껉껊껋껌껍껎껏';
		$expects = '껀껁껂껃껄껅껆껇껈껉껊껋껌껍껎껏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '껐껑껒껓껔껕껖껗께껙껚껛껜껝껞껟';
		$expects = '껐껑껒껓껔껕껖껗께껙껚껛껜껝껞껟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '껠껡껢껣껤껥껦껧껨껩껪껫껬껭껮껯';
		$expects = '껠껡껢껣껤껥껦껧껨껩껪껫껬껭껮껯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '껰껱껲껳껴껵껶껷껸껹껺껻껼껽껾껿';
		$expects = '껰껱껲껳껴껵껶껷껸껹껺껻껼껽껾껿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꼀꼁꼂꼃꼄꼅꼆꼇꼈꼉꼊꼋꼌꼍꼎꼏';
		$expects = '꼀꼁꼂꼃꼄꼅꼆꼇꼈꼉꼊꼋꼌꼍꼎꼏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꼐꼑꼒꼓꼔꼕꼖꼗꼘꼙꼚꼛꼜꼝꼞꼟';
		$expects = '꼐꼑꼒꼓꼔꼕꼖꼗꼘꼙꼚꼛꼜꼝꼞꼟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꼠꼡꼢꼣꼤꼥꼦꼧꼨꼩꼪꼫꼬꼭꼮꼯';
		$expects = '꼠꼡꼢꼣꼤꼥꼦꼧꼨꼩꼪꼫꼬꼭꼮꼯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꼰꼱꼲꼳꼴꼵꼶꼷꼸꼹꼺꼻꼼꼽꼾꼿';
		$expects = '꼰꼱꼲꼳꼴꼵꼶꼷꼸꼹꼺꼻꼼꼽꼾꼿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꽀꽁꽂꽃꽄꽅꽆꽇꽈꽉꽊꽋꽌꽍꽎꽏';
		$expects = '꽀꽁꽂꽃꽄꽅꽆꽇꽈꽉꽊꽋꽌꽍꽎꽏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꽐꽑꽒꽓꽔꽕꽖꽗꽘꽙꽚꽛꽜꽝꽞꽟';
		$expects = '꽐꽑꽒꽓꽔꽕꽖꽗꽘꽙꽚꽛꽜꽝꽞꽟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꽠꽡꽢꽣꽤꽥꽦꽧꽨꽩꽪꽫꽬꽭꽮꽯';
		$expects = '꽠꽡꽢꽣꽤꽥꽦꽧꽨꽩꽪꽫꽬꽭꽮꽯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꽰꽱꽲꽳꽴꽵꽶꽷꽸꽹꽺꽻꽼꽽꽾꽿';
		$expects = '꽰꽱꽲꽳꽴꽵꽶꽷꽸꽹꽺꽻꽼꽽꽾꽿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꾀꾁꾂꾃꾄꾅꾆꾇꾈꾉꾊꾋꾌꾍꾎꾏';
		$expects = '꾀꾁꾂꾃꾄꾅꾆꾇꾈꾉꾊꾋꾌꾍꾎꾏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꾐꾑꾒꾓꾔꾕꾖꾗꾘꾙꾚꾛꾜꾝꾞꾟';
		$expects = '꾐꾑꾒꾓꾔꾕꾖꾗꾘꾙꾚꾛꾜꾝꾞꾟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꾠꾡꾢꾣꾤꾥꾦꾧꾨꾩꾪꾫꾬꾭꾮꾯';
		$expects = '꾠꾡꾢꾣꾤꾥꾦꾧꾨꾩꾪꾫꾬꾭꾮꾯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꾰꾱꾲꾳꾴꾵꾶꾷꾸꾹꾺꾻꾼꾽꾾꾿';
		$expects = '꾰꾱꾲꾳꾴꾵꾶꾷꾸꾹꾺꾻꾼꾽꾾꾿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꿀꿁꿂꿃꿄꿅꿆꿇꿈꿉꿊꿋꿌꿍꿎꿏';
		$expects = '꿀꿁꿂꿃꿄꿅꿆꿇꿈꿉꿊꿋꿌꿍꿎꿏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꿐꿑꿒꿓꿔꿕꿖꿗꿘꿙꿚꿛꿜꿝꿞꿟';
		$expects = '꿐꿑꿒꿓꿔꿕꿖꿗꿘꿙꿚꿛꿜꿝꿞꿟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꿠꿡꿢꿣꿤꿥꿦꿧꿨꿩꿪꿫꿬꿭꿮꿯';
		$expects = '꿠꿡꿢꿣꿤꿥꿦꿧꿨꿩꿪꿫꿬꿭꿮꿯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '꿰꿱꿲꿳꿴꿵꿶꿷꿸꿹꿺꿻꿼꿽꿾꿿';
		$expects = '꿰꿱꿲꿳꿴꿵꿶꿷꿸꿹꿺꿻꿼꿽꿾꿿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSectionb method
	 *
	 * Testing characters b000 - bfff
	 *
	 * @return void
	 */
	public function testSectionb() {
		$string = '뀀뀁뀂뀃뀄뀅뀆뀇뀈뀉뀊뀋뀌뀍뀎뀏';
		$expects = '뀀뀁뀂뀃뀄뀅뀆뀇뀈뀉뀊뀋뀌뀍뀎뀏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뀐뀑뀒뀓뀔뀕뀖뀗뀘뀙뀚뀛뀜뀝뀞뀟';
		$expects = '뀐뀑뀒뀓뀔뀕뀖뀗뀘뀙뀚뀛뀜뀝뀞뀟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뀠뀡뀢뀣뀤뀥뀦뀧뀨뀩뀪뀫뀬뀭뀮뀯';
		$expects = '뀠뀡뀢뀣뀤뀥뀦뀧뀨뀩뀪뀫뀬뀭뀮뀯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뀰뀱뀲뀳뀴뀵뀶뀷뀸뀹뀺뀻뀼뀽뀾뀿';
		$expects = '뀰뀱뀲뀳뀴뀵뀶뀷뀸뀹뀺뀻뀼뀽뀾뀿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '끀끁끂끃끄끅끆끇끈끉끊끋끌끍끎끏';
		$expects = '끀끁끂끃끄끅끆끇끈끉끊끋끌끍끎끏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '끐끑끒끓끔끕끖끗끘끙끚끛끜끝끞끟';
		$expects = '끐끑끒끓끔끕끖끗끘끙끚끛끜끝끞끟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '끠끡끢끣끤끥끦끧끨끩끪끫끬끭끮끯';
		$expects = '끠끡끢끣끤끥끦끧끨끩끪끫끬끭끮끯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '끰끱끲끳끴끵끶끷끸끹끺끻끼끽끾끿';
		$expects = '끰끱끲끳끴끵끶끷끸끹끺끻끼끽끾끿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '낀낁낂낃낄낅낆낇낈낉낊낋낌낍낎낏';
		$expects = '낀낁낂낃낄낅낆낇낈낉낊낋낌낍낎낏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '낐낑낒낓낔낕낖낗나낙낚낛난낝낞낟';
		$expects = '낐낑낒낓낔낕낖낗나낙낚낛난낝낞낟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '날낡낢낣낤낥낦낧남납낪낫났낭낮낯';
		$expects = '날낡낢낣낤낥낦낧남납낪낫났낭낮낯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '낰낱낲낳내낵낶낷낸낹낺낻낼낽낾낿';
		$expects = '낰낱낲낳내낵낶낷낸낹낺낻낼낽낾낿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '냀냁냂냃냄냅냆냇냈냉냊냋냌냍냎냏';
		$expects = '냀냁냂냃냄냅냆냇냈냉냊냋냌냍냎냏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '냐냑냒냓냔냕냖냗냘냙냚냛냜냝냞냟';
		$expects = '냐냑냒냓냔냕냖냗냘냙냚냛냜냝냞냟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '냠냡냢냣냤냥냦냧냨냩냪냫냬냭냮냯';
		$expects = '냠냡냢냣냤냥냦냧냨냩냪냫냬냭냮냯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '냰냱냲냳냴냵냶냷냸냹냺냻냼냽냾냿';
		$expects = '냰냱냲냳냴냵냶냷냸냹냺냻냼냽냾냿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '넀넁넂넃넄넅넆넇너넉넊넋넌넍넎넏';
		$expects = '넀넁넂넃넄넅넆넇너넉넊넋넌넍넎넏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '널넑넒넓넔넕넖넗넘넙넚넛넜넝넞넟';
		$expects = '널넑넒넓넔넕넖넗넘넙넚넛넜넝넞넟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '넠넡넢넣네넥넦넧넨넩넪넫넬넭넮넯';
		$expects = '넠넡넢넣네넥넦넧넨넩넪넫넬넭넮넯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '넰넱넲넳넴넵넶넷넸넹넺넻넼넽넾넿';
		$expects = '넰넱넲넳넴넵넶넷넸넹넺넻넼넽넾넿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '녀녁녂녃년녅녆녇녈녉녊녋녌녍녎녏';
		$expects = '녀녁녂녃년녅녆녇녈녉녊녋녌녍녎녏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '념녑녒녓녔녕녖녗녘녙녚녛녜녝녞녟';
		$expects = '념녑녒녓녔녕녖녗녘녙녚녛녜녝녞녟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '녠녡녢녣녤녥녦녧녨녩녪녫녬녭녮녯';
		$expects = '녠녡녢녣녤녥녦녧녨녩녪녫녬녭녮녯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '녰녱녲녳녴녵녶녷노녹녺녻논녽녾녿';
		$expects = '녰녱녲녳녴녵녶녷노녹녺녻논녽녾녿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '놀놁놂놃놄놅놆놇놈놉놊놋놌농놎놏';
		$expects = '놀놁놂놃놄놅놆놇놈놉놊놋놌농놎놏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '놐놑높놓놔놕놖놗놘놙놚놛놜놝놞놟';
		$expects = '놐놑높놓놔놕놖놗놘놙놚놛놜놝놞놟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '놠놡놢놣놤놥놦놧놨놩놪놫놬놭놮놯';
		$expects = '놠놡놢놣놤놥놦놧놨놩놪놫놬놭놮놯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '놰놱놲놳놴놵놶놷놸놹놺놻놼놽놾놿';
		$expects = '놰놱놲놳놴놵놶놷놸놹놺놻놼놽놾놿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뇀뇁뇂뇃뇄뇅뇆뇇뇈뇉뇊뇋뇌뇍뇎뇏';
		$expects = '뇀뇁뇂뇃뇄뇅뇆뇇뇈뇉뇊뇋뇌뇍뇎뇏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뇐뇑뇒뇓뇔뇕뇖뇗뇘뇙뇚뇛뇜뇝뇞뇟';
		$expects = '뇐뇑뇒뇓뇔뇕뇖뇗뇘뇙뇚뇛뇜뇝뇞뇟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뇠뇡뇢뇣뇤뇥뇦뇧뇨뇩뇪뇫뇬뇭뇮뇯';
		$expects = '뇠뇡뇢뇣뇤뇥뇦뇧뇨뇩뇪뇫뇬뇭뇮뇯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뇰뇱뇲뇳뇴뇵뇶뇷뇸뇹뇺뇻뇼뇽뇾뇿';
		$expects = '뇰뇱뇲뇳뇴뇵뇶뇷뇸뇹뇺뇻뇼뇽뇾뇿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '눀눁눂눃누눅눆눇눈눉눊눋눌눍눎눏';
		$expects = '눀눁눂눃누눅눆눇눈눉눊눋눌눍눎눏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '눐눑눒눓눔눕눖눗눘눙눚눛눜눝눞눟';
		$expects = '눐눑눒눓눔눕눖눗눘눙눚눛눜눝눞눟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '눠눡눢눣눤눥눦눧눨눩눪눫눬눭눮눯';
		$expects = '눠눡눢눣눤눥눦눧눨눩눪눫눬눭눮눯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '눰눱눲눳눴눵눶눷눸눹눺눻눼눽눾눿';
		$expects = '눰눱눲눳눴눵눶눷눸눹눺눻눼눽눾눿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뉀뉁뉂뉃뉄뉅뉆뉇뉈뉉뉊뉋뉌뉍뉎뉏';
		$expects = '뉀뉁뉂뉃뉄뉅뉆뉇뉈뉉뉊뉋뉌뉍뉎뉏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뉐뉑뉒뉓뉔뉕뉖뉗뉘뉙뉚뉛뉜뉝뉞뉟';
		$expects = '뉐뉑뉒뉓뉔뉕뉖뉗뉘뉙뉚뉛뉜뉝뉞뉟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뉠뉡뉢뉣뉤뉥뉦뉧뉨뉩뉪뉫뉬뉭뉮뉯';
		$expects = '뉠뉡뉢뉣뉤뉥뉦뉧뉨뉩뉪뉫뉬뉭뉮뉯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뉰뉱뉲뉳뉴뉵뉶뉷뉸뉹뉺뉻뉼뉽뉾뉿';
		$expects = '뉰뉱뉲뉳뉴뉵뉶뉷뉸뉹뉺뉻뉼뉽뉾뉿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '늀늁늂늃늄늅늆늇늈늉늊늋늌늍늎늏';
		$expects = '늀늁늂늃늄늅늆늇늈늉늊늋늌늍늎늏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '느늑늒늓는늕늖늗늘늙늚늛늜늝늞늟';
		$expects = '느늑늒늓는늕늖늗늘늙늚늛늜늝늞늟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '늠늡늢늣늤능늦늧늨늩늪늫늬늭늮늯';
		$expects = '늠늡늢늣늤능늦늧늨늩늪늫늬늭늮늯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '늰늱늲늳늴늵늶늷늸늹늺늻늼늽늾늿';
		$expects = '늰늱늲늳늴늵늶늷늸늹늺늻늼늽늾늿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '닀닁닂닃닄닅닆닇니닉닊닋닌닍닎닏';
		$expects = '닀닁닂닃닄닅닆닇니닉닊닋닌닍닎닏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '닐닑닒닓닔닕닖닗님닙닚닛닜닝닞닟';
		$expects = '닐닑닒닓닔닕닖닗님닙닚닛닜닝닞닟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '닠닡닢닣다닥닦닧단닩닪닫달닭닮닯';
		$expects = '닠닡닢닣다닥닦닧단닩닪닫달닭닮닯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '닰닱닲닳담답닶닷닸당닺닻닼닽닾닿';
		$expects = '닰닱닲닳담답닶닷닸당닺닻닼닽닾닿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '대댁댂댃댄댅댆댇댈댉댊댋댌댍댎댏';
		$expects = '대댁댂댃댄댅댆댇댈댉댊댋댌댍댎댏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '댐댑댒댓댔댕댖댗댘댙댚댛댜댝댞댟';
		$expects = '댐댑댒댓댔댕댖댗댘댙댚댛댜댝댞댟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '댠댡댢댣댤댥댦댧댨댩댪댫댬댭댮댯';
		$expects = '댠댡댢댣댤댥댦댧댨댩댪댫댬댭댮댯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '댰댱댲댳댴댵댶댷댸댹댺댻댼댽댾댿';
		$expects = '댰댱댲댳댴댵댶댷댸댹댺댻댼댽댾댿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '덀덁덂덃덄덅덆덇덈덉덊덋덌덍덎덏';
		$expects = '덀덁덂덃덄덅덆덇덈덉덊덋덌덍덎덏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '덐덑덒덓더덕덖덗던덙덚덛덜덝덞덟';
		$expects = '덐덑덒덓더덕덖덗던덙덚덛덜덝덞덟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '덠덡덢덣덤덥덦덧덨덩덪덫덬덭덮덯';
		$expects = '덠덡덢덣덤덥덦덧덨덩덪덫덬덭덮덯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '데덱덲덳덴덵덶덷델덹덺덻덼덽덾덿';
		$expects = '데덱덲덳덴덵덶덷델덹덺덻덼덽덾덿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뎀뎁뎂뎃뎄뎅뎆뎇뎈뎉뎊뎋뎌뎍뎎뎏';
		$expects = '뎀뎁뎂뎃뎄뎅뎆뎇뎈뎉뎊뎋뎌뎍뎎뎏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뎐뎑뎒뎓뎔뎕뎖뎗뎘뎙뎚뎛뎜뎝뎞뎟';
		$expects = '뎐뎑뎒뎓뎔뎕뎖뎗뎘뎙뎚뎛뎜뎝뎞뎟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뎠뎡뎢뎣뎤뎥뎦뎧뎨뎩뎪뎫뎬뎭뎮뎯';
		$expects = '뎠뎡뎢뎣뎤뎥뎦뎧뎨뎩뎪뎫뎬뎭뎮뎯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뎰뎱뎲뎳뎴뎵뎶뎷뎸뎹뎺뎻뎼뎽뎾뎿';
		$expects = '뎰뎱뎲뎳뎴뎵뎶뎷뎸뎹뎺뎻뎼뎽뎾뎿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '돀돁돂돃도독돆돇돈돉돊돋돌돍돎돏';
		$expects = '돀돁돂돃도독돆돇돈돉돊돋돌돍돎돏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '돐돑돒돓돔돕돖돗돘동돚돛돜돝돞돟';
		$expects = '돐돑돒돓돔돕돖돗돘동돚돛돜돝돞돟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '돠돡돢돣돤돥돦돧돨돩돪돫돬돭돮돯';
		$expects = '돠돡돢돣돤돥돦돧돨돩돪돫돬돭돮돯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '돰돱돲돳돴돵돶돷돸돹돺돻돼돽돾돿';
		$expects = '돰돱돲돳돴돵돶돷돸돹돺돻돼돽돾돿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '됀됁됂됃됄됅됆됇됈됉됊됋됌됍됎됏';
		$expects = '됀됁됂됃됄됅됆됇됈됉됊됋됌됍됎됏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '됐됑됒됓됔됕됖됗되됙됚됛된됝됞됟';
		$expects = '됐됑됒됓됔됕됖됗되됙됚됛된됝됞됟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '될됡됢됣됤됥됦됧됨됩됪됫됬됭됮됯';
		$expects = '될됡됢됣됤됥됦됧됨됩됪됫됬됭됮됯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '됰됱됲됳됴됵됶됷됸됹됺됻됼됽됾됿';
		$expects = '됰됱됲됳됴됵됶됷됸됹됺됻됼됽됾됿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '둀둁둂둃둄둅둆둇둈둉둊둋둌둍둎둏';
		$expects = '둀둁둂둃둄둅둆둇둈둉둊둋둌둍둎둏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '두둑둒둓둔둕둖둗둘둙둚둛둜둝둞둟';
		$expects = '두둑둒둓둔둕둖둗둘둙둚둛둜둝둞둟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '둠둡둢둣둤둥둦둧둨둩둪둫둬둭둮둯';
		$expects = '둠둡둢둣둤둥둦둧둨둩둪둫둬둭둮둯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '둰둱둲둳둴둵둶둷둸둹둺둻둼둽둾둿';
		$expects = '둰둱둲둳둴둵둶둷둸둹둺둻둼둽둾둿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뒀뒁뒂뒃뒄뒅뒆뒇뒈뒉뒊뒋뒌뒍뒎뒏';
		$expects = '뒀뒁뒂뒃뒄뒅뒆뒇뒈뒉뒊뒋뒌뒍뒎뒏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뒐뒑뒒뒓뒔뒕뒖뒗뒘뒙뒚뒛뒜뒝뒞뒟';
		$expects = '뒐뒑뒒뒓뒔뒕뒖뒗뒘뒙뒚뒛뒜뒝뒞뒟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뒠뒡뒢뒣뒤뒥뒦뒧뒨뒩뒪뒫뒬뒭뒮뒯';
		$expects = '뒠뒡뒢뒣뒤뒥뒦뒧뒨뒩뒪뒫뒬뒭뒮뒯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뒰뒱뒲뒳뒴뒵뒶뒷뒸뒹뒺뒻뒼뒽뒾뒿';
		$expects = '뒰뒱뒲뒳뒴뒵뒶뒷뒸뒹뒺뒻뒼뒽뒾뒿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '듀듁듂듃듄듅듆듇듈듉듊듋듌듍듎듏';
		$expects = '듀듁듂듃듄듅듆듇듈듉듊듋듌듍듎듏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '듐듑듒듓듔듕듖듗듘듙듚듛드득듞듟';
		$expects = '듐듑듒듓듔듕듖듗듘듙듚듛드득듞듟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '든듡듢듣들듥듦듧듨듩듪듫듬듭듮듯';
		$expects = '든듡듢듣들듥듦듧듨듩듪듫듬듭듮듯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '듰등듲듳듴듵듶듷듸듹듺듻듼듽듾듿';
		$expects = '듰등듲듳듴듵듶듷듸듹듺듻듼듽듾듿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '딀딁딂딃딄딅딆딇딈딉딊딋딌딍딎딏';
		$expects = '딀딁딂딃딄딅딆딇딈딉딊딋딌딍딎딏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '딐딑딒딓디딕딖딗딘딙딚딛딜딝딞딟';
		$expects = '딐딑딒딓디딕딖딗딘딙딚딛딜딝딞딟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '딠딡딢딣딤딥딦딧딨딩딪딫딬딭딮딯';
		$expects = '딠딡딢딣딤딥딦딧딨딩딪딫딬딭딮딯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '따딱딲딳딴딵딶딷딸딹딺딻딼딽딾딿';
		$expects = '따딱딲딳딴딵딶딷딸딹딺딻딼딽딾딿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '땀땁땂땃땄땅땆땇땈땉땊땋때땍땎땏';
		$expects = '땀땁땂땃땄땅땆땇땈땉땊땋때땍땎땏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '땐땑땒땓땔땕땖땗땘땙땚땛땜땝땞땟';
		$expects = '땐땑땒땓땔땕땖땗땘땙땚땛땜땝땞땟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '땠땡땢땣땤땥땦땧땨땩땪땫땬땭땮땯';
		$expects = '땠땡땢땣땤땥땦땧땨땩땪땫땬땭땮땯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '땰땱땲땳땴땵땶땷땸땹땺땻땼땽땾땿';
		$expects = '땰땱땲땳땴땵땶땷땸땹땺땻땼땽땾땿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '떀떁떂떃떄떅떆떇떈떉떊떋떌떍떎떏';
		$expects = '떀떁떂떃떄떅떆떇떈떉떊떋떌떍떎떏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '떐떑떒떓떔떕떖떗떘떙떚떛떜떝떞떟';
		$expects = '떐떑떒떓떔떕떖떗떘떙떚떛떜떝떞떟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '떠떡떢떣떤떥떦떧떨떩떪떫떬떭떮떯';
		$expects = '떠떡떢떣떤떥떦떧떨떩떪떫떬떭떮떯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '떰떱떲떳떴떵떶떷떸떹떺떻떼떽떾떿';
		$expects = '떰떱떲떳떴떵떶떷떸떹떺떻떼떽떾떿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뗀뗁뗂뗃뗄뗅뗆뗇뗈뗉뗊뗋뗌뗍뗎뗏';
		$expects = '뗀뗁뗂뗃뗄뗅뗆뗇뗈뗉뗊뗋뗌뗍뗎뗏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뗐뗑뗒뗓뗔뗕뗖뗗뗘뗙뗚뗛뗜뗝뗞뗟';
		$expects = '뗐뗑뗒뗓뗔뗕뗖뗗뗘뗙뗚뗛뗜뗝뗞뗟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뗠뗡뗢뗣뗤뗥뗦뗧뗨뗩뗪뗫뗬뗭뗮뗯';
		$expects = '뗠뗡뗢뗣뗤뗥뗦뗧뗨뗩뗪뗫뗬뗭뗮뗯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뗰뗱뗲뗳뗴뗵뗶뗷뗸뗹뗺뗻뗼뗽뗾뗿';
		$expects = '뗰뗱뗲뗳뗴뗵뗶뗷뗸뗹뗺뗻뗼뗽뗾뗿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '똀똁똂똃똄똅똆똇똈똉똊똋똌똍똎똏';
		$expects = '똀똁똂똃똄똅똆똇똈똉똊똋똌똍똎똏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '또똑똒똓똔똕똖똗똘똙똚똛똜똝똞똟';
		$expects = '또똑똒똓똔똕똖똗똘똙똚똛똜똝똞똟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '똠똡똢똣똤똥똦똧똨똩똪똫똬똭똮똯';
		$expects = '똠똡똢똣똤똥똦똧똨똩똪똫똬똭똮똯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '똰똱똲똳똴똵똶똷똸똹똺똻똼똽똾똿';
		$expects = '똰똱똲똳똴똵똶똷똸똹똺똻똼똽똾똿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뙀뙁뙂뙃뙄뙅뙆뙇뙈뙉뙊뙋뙌뙍뙎뙏';
		$expects = '뙀뙁뙂뙃뙄뙅뙆뙇뙈뙉뙊뙋뙌뙍뙎뙏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뙐뙑뙒뙓뙔뙕뙖뙗뙘뙙뙚뙛뙜뙝뙞뙟';
		$expects = '뙐뙑뙒뙓뙔뙕뙖뙗뙘뙙뙚뙛뙜뙝뙞뙟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뙠뙡뙢뙣뙤뙥뙦뙧뙨뙩뙪뙫뙬뙭뙮뙯';
		$expects = '뙠뙡뙢뙣뙤뙥뙦뙧뙨뙩뙪뙫뙬뙭뙮뙯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뙰뙱뙲뙳뙴뙵뙶뙷뙸뙹뙺뙻뙼뙽뙾뙿';
		$expects = '뙰뙱뙲뙳뙴뙵뙶뙷뙸뙹뙺뙻뙼뙽뙾뙿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뚀뚁뚂뚃뚄뚅뚆뚇뚈뚉뚊뚋뚌뚍뚎뚏';
		$expects = '뚀뚁뚂뚃뚄뚅뚆뚇뚈뚉뚊뚋뚌뚍뚎뚏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뚐뚑뚒뚓뚔뚕뚖뚗뚘뚙뚚뚛뚜뚝뚞뚟';
		$expects = '뚐뚑뚒뚓뚔뚕뚖뚗뚘뚙뚚뚛뚜뚝뚞뚟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뚠뚡뚢뚣뚤뚥뚦뚧뚨뚩뚪뚫뚬뚭뚮뚯';
		$expects = '뚠뚡뚢뚣뚤뚥뚦뚧뚨뚩뚪뚫뚬뚭뚮뚯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뚰뚱뚲뚳뚴뚵뚶뚷뚸뚹뚺뚻뚼뚽뚾뚿';
		$expects = '뚰뚱뚲뚳뚴뚵뚶뚷뚸뚹뚺뚻뚼뚽뚾뚿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뛀뛁뛂뛃뛄뛅뛆뛇뛈뛉뛊뛋뛌뛍뛎뛏';
		$expects = '뛀뛁뛂뛃뛄뛅뛆뛇뛈뛉뛊뛋뛌뛍뛎뛏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뛐뛑뛒뛓뛔뛕뛖뛗뛘뛙뛚뛛뛜뛝뛞뛟';
		$expects = '뛐뛑뛒뛓뛔뛕뛖뛗뛘뛙뛚뛛뛜뛝뛞뛟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뛠뛡뛢뛣뛤뛥뛦뛧뛨뛩뛪뛫뛬뛭뛮뛯';
		$expects = '뛠뛡뛢뛣뛤뛥뛦뛧뛨뛩뛪뛫뛬뛭뛮뛯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뛰뛱뛲뛳뛴뛵뛶뛷뛸뛹뛺뛻뛼뛽뛾뛿';
		$expects = '뛰뛱뛲뛳뛴뛵뛶뛷뛸뛹뛺뛻뛼뛽뛾뛿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뜀뜁뜂뜃뜄뜅뜆뜇뜈뜉뜊뜋뜌뜍뜎뜏';
		$expects = '뜀뜁뜂뜃뜄뜅뜆뜇뜈뜉뜊뜋뜌뜍뜎뜏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뜐뜑뜒뜓뜔뜕뜖뜗뜘뜙뜚뜛뜜뜝뜞뜟';
		$expects = '뜐뜑뜒뜓뜔뜕뜖뜗뜘뜙뜚뜛뜜뜝뜞뜟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뜠뜡뜢뜣뜤뜥뜦뜧뜨뜩뜪뜫뜬뜭뜮뜯';
		$expects = '뜠뜡뜢뜣뜤뜥뜦뜧뜨뜩뜪뜫뜬뜭뜮뜯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뜰뜱뜲뜳뜴뜵뜶뜷뜸뜹뜺뜻뜼뜽뜾뜿';
		$expects = '뜰뜱뜲뜳뜴뜵뜶뜷뜸뜹뜺뜻뜼뜽뜾뜿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '띀띁띂띃띄띅띆띇띈띉띊띋띌띍띎띏';
		$expects = '띀띁띂띃띄띅띆띇띈띉띊띋띌띍띎띏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '띐띑띒띓띔띕띖띗띘띙띚띛띜띝띞띟';
		$expects = '띐띑띒띓띔띕띖띗띘띙띚띛띜띝띞띟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '띠띡띢띣띤띥띦띧띨띩띪띫띬띭띮띯';
		$expects = '띠띡띢띣띤띥띦띧띨띩띪띫띬띭띮띯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '띰띱띲띳띴띵띶띷띸띹띺띻라락띾띿';
		$expects = '띰띱띲띳띴띵띶띷띸띹띺띻라락띾띿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '란랁랂랃랄랅랆랇랈랉랊랋람랍랎랏';
		$expects = '란랁랂랃랄랅랆랇랈랉랊랋람랍랎랏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '랐랑랒랓랔랕랖랗래랙랚랛랜랝랞랟';
		$expects = '랐랑랒랓랔랕랖랗래랙랚랛랜랝랞랟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '랠랡랢랣랤랥랦랧램랩랪랫랬랭랮랯';
		$expects = '랠랡랢랣랤랥랦랧램랩랪랫랬랭랮랯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '랰랱랲랳랴략랶랷랸랹랺랻랼랽랾랿';
		$expects = '랰랱랲랳랴략랶랷랸랹랺랻랼랽랾랿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '럀럁럂럃럄럅럆럇럈량럊럋럌럍럎럏';
		$expects = '럀럁럂럃럄럅럆럇럈량럊럋럌럍럎럏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '럐럑럒럓럔럕럖럗럘럙럚럛럜럝럞럟';
		$expects = '럐럑럒럓럔럕럖럗럘럙럚럛럜럝럞럟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '럠럡럢럣럤럥럦럧럨럩럪럫러럭럮럯';
		$expects = '럠럡럢럣럤럥럦럧럨럩럪럫러럭럮럯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '런럱럲럳럴럵럶럷럸럹럺럻럼럽럾럿';
		$expects = '런럱럲럳럴럵럶럷럸럹럺럻럼럽럾럿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '렀렁렂렃렄렅렆렇레렉렊렋렌렍렎렏';
		$expects = '렀렁렂렃렄렅렆렇레렉렊렋렌렍렎렏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '렐렑렒렓렔렕렖렗렘렙렚렛렜렝렞렟';
		$expects = '렐렑렒렓렔렕렖렗렘렙렚렛렜렝렞렟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '렠렡렢렣려력렦렧련렩렪렫렬렭렮렯';
		$expects = '렠렡렢렣려력렦렧련렩렪렫렬렭렮렯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '렰렱렲렳렴렵렶렷렸령렺렻렼렽렾렿';
		$expects = '렰렱렲렳렴렵렶렷렸령렺렻렼렽렾렿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '례롁롂롃롄롅롆롇롈롉롊롋롌롍롎롏';
		$expects = '례롁롂롃롄롅롆롇롈롉롊롋롌롍롎롏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '롐롑롒롓롔롕롖롗롘롙롚롛로록롞롟';
		$expects = '롐롑롒롓롔롕롖롗롘롙롚롛로록롞롟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '론롡롢롣롤롥롦롧롨롩롪롫롬롭롮롯';
		$expects = '론롡롢롣롤롥롦롧롨롩롪롫롬롭롮롯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '롰롱롲롳롴롵롶롷롸롹롺롻롼롽롾롿';
		$expects = '롰롱롲롳롴롵롶롷롸롹롺롻롼롽롾롿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뢀뢁뢂뢃뢄뢅뢆뢇뢈뢉뢊뢋뢌뢍뢎뢏';
		$expects = '뢀뢁뢂뢃뢄뢅뢆뢇뢈뢉뢊뢋뢌뢍뢎뢏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뢐뢑뢒뢓뢔뢕뢖뢗뢘뢙뢚뢛뢜뢝뢞뢟';
		$expects = '뢐뢑뢒뢓뢔뢕뢖뢗뢘뢙뢚뢛뢜뢝뢞뢟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뢠뢡뢢뢣뢤뢥뢦뢧뢨뢩뢪뢫뢬뢭뢮뢯';
		$expects = '뢠뢡뢢뢣뢤뢥뢦뢧뢨뢩뢪뢫뢬뢭뢮뢯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뢰뢱뢲뢳뢴뢵뢶뢷뢸뢹뢺뢻뢼뢽뢾뢿';
		$expects = '뢰뢱뢲뢳뢴뢵뢶뢷뢸뢹뢺뢻뢼뢽뢾뢿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '룀룁룂룃룄룅룆룇룈룉룊룋료룍룎룏';
		$expects = '룀룁룂룃룄룅룆룇룈룉룊룋료룍룎룏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '룐룑룒룓룔룕룖룗룘룙룚룛룜룝룞룟';
		$expects = '룐룑룒룓룔룕룖룗룘룙룚룛룜룝룞룟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '룠룡룢룣룤룥룦룧루룩룪룫룬룭룮룯';
		$expects = '룠룡룢룣룤룥룦룧루룩룪룫룬룭룮룯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '룰룱룲룳룴룵룶룷룸룹룺룻룼룽룾룿';
		$expects = '룰룱룲룳룴룵룶룷룸룹룺룻룼룽룾룿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뤀뤁뤂뤃뤄뤅뤆뤇뤈뤉뤊뤋뤌뤍뤎뤏';
		$expects = '뤀뤁뤂뤃뤄뤅뤆뤇뤈뤉뤊뤋뤌뤍뤎뤏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뤐뤑뤒뤓뤔뤕뤖뤗뤘뤙뤚뤛뤜뤝뤞뤟';
		$expects = '뤐뤑뤒뤓뤔뤕뤖뤗뤘뤙뤚뤛뤜뤝뤞뤟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뤠뤡뤢뤣뤤뤥뤦뤧뤨뤩뤪뤫뤬뤭뤮뤯';
		$expects = '뤠뤡뤢뤣뤤뤥뤦뤧뤨뤩뤪뤫뤬뤭뤮뤯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뤰뤱뤲뤳뤴뤵뤶뤷뤸뤹뤺뤻뤼뤽뤾뤿';
		$expects = '뤰뤱뤲뤳뤴뤵뤶뤷뤸뤹뤺뤻뤼뤽뤾뤿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '륀륁륂륃륄륅륆륇륈륉륊륋륌륍륎륏';
		$expects = '륀륁륂륃륄륅륆륇륈륉륊륋륌륍륎륏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '륐륑륒륓륔륕륖륗류륙륚륛륜륝륞륟';
		$expects = '륐륑륒륓륔륕륖륗류륙륚륛륜륝륞륟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '률륡륢륣륤륥륦륧륨륩륪륫륬륭륮륯';
		$expects = '률륡륢륣륤륥륦륧륨륩륪륫륬륭륮륯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '륰륱륲륳르륵륶륷른륹륺륻를륽륾륿';
		$expects = '륰륱륲륳르륵륶륷른륹륺륻를륽륾륿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '릀릁릂릃름릅릆릇릈릉릊릋릌릍릎릏';
		$expects = '릀릁릂릃름릅릆릇릈릉릊릋릌릍릎릏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '릐릑릒릓릔릕릖릗릘릙릚릛릜릝릞릟';
		$expects = '릐릑릒릓릔릕릖릗릘릙릚릛릜릝릞릟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '릠릡릢릣릤릥릦릧릨릩릪릫리릭릮릯';
		$expects = '릠릡릢릣릤릥릦릧릨릩릪릫리릭릮릯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '린릱릲릳릴릵릶릷릸릹릺릻림립릾릿';
		$expects = '린릱릲릳릴릵릶릷릸릹릺릻림립릾릿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '맀링맂맃맄맅맆맇마막맊맋만맍많맏';
		$expects = '맀링맂맃맄맅맆맇마막맊맋만맍많맏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '말맑맒맓맔맕맖맗맘맙맚맛맜망맞맟';
		$expects = '말맑맒맓맔맕맖맗맘맙맚맛맜망맞맟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '맠맡맢맣매맥맦맧맨맩맪맫맬맭맮맯';
		$expects = '맠맡맢맣매맥맦맧맨맩맪맫맬맭맮맯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '맰맱맲맳맴맵맶맷맸맹맺맻맼맽맾맿';
		$expects = '맰맱맲맳맴맵맶맷맸맹맺맻맼맽맾맿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '먀먁먂먃먄먅먆먇먈먉먊먋먌먍먎먏';
		$expects = '먀먁먂먃먄먅먆먇먈먉먊먋먌먍먎먏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '먐먑먒먓먔먕먖먗먘먙먚먛먜먝먞먟';
		$expects = '먐먑먒먓먔먕먖먗먘먙먚먛먜먝먞먟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '먠먡먢먣먤먥먦먧먨먩먪먫먬먭먮먯';
		$expects = '먠먡먢먣먤먥먦먧먨먩먪먫먬먭먮먯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '먰먱먲먳먴먵먶먷머먹먺먻먼먽먾먿';
		$expects = '먰먱먲먳먴먵먶먷머먹먺먻먼먽먾먿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '멀멁멂멃멄멅멆멇멈멉멊멋멌멍멎멏';
		$expects = '멀멁멂멃멄멅멆멇멈멉멊멋멌멍멎멏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '멐멑멒멓메멕멖멗멘멙멚멛멜멝멞멟';
		$expects = '멐멑멒멓메멕멖멗멘멙멚멛멜멝멞멟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '멠멡멢멣멤멥멦멧멨멩멪멫멬멭멮멯';
		$expects = '멠멡멢멣멤멥멦멧멨멩멪멫멬멭멮멯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '며멱멲멳면멵멶멷멸멹멺멻멼멽멾멿';
		$expects = '며멱멲멳면멵멶멷멸멹멺멻멼멽멾멿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '몀몁몂몃몄명몆몇몈몉몊몋몌몍몎몏';
		$expects = '몀몁몂몃몄명몆몇몈몉몊몋몌몍몎몏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '몐몑몒몓몔몕몖몗몘몙몚몛몜몝몞몟';
		$expects = '몐몑몒몓몔몕몖몗몘몙몚몛몜몝몞몟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '몠몡몢몣몤몥몦몧모목몪몫몬몭몮몯';
		$expects = '몠몡몢몣몤몥몦몧모목몪몫몬몭몮몯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '몰몱몲몳몴몵몶몷몸몹몺못몼몽몾몿';
		$expects = '몰몱몲몳몴몵몶몷몸몹몺못몼몽몾몿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뫀뫁뫂뫃뫄뫅뫆뫇뫈뫉뫊뫋뫌뫍뫎뫏';
		$expects = '뫀뫁뫂뫃뫄뫅뫆뫇뫈뫉뫊뫋뫌뫍뫎뫏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뫐뫑뫒뫓뫔뫕뫖뫗뫘뫙뫚뫛뫜뫝뫞뫟';
		$expects = '뫐뫑뫒뫓뫔뫕뫖뫗뫘뫙뫚뫛뫜뫝뫞뫟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뫠뫡뫢뫣뫤뫥뫦뫧뫨뫩뫪뫫뫬뫭뫮뫯';
		$expects = '뫠뫡뫢뫣뫤뫥뫦뫧뫨뫩뫪뫫뫬뫭뫮뫯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뫰뫱뫲뫳뫴뫵뫶뫷뫸뫹뫺뫻뫼뫽뫾뫿';
		$expects = '뫰뫱뫲뫳뫴뫵뫶뫷뫸뫹뫺뫻뫼뫽뫾뫿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '묀묁묂묃묄묅묆묇묈묉묊묋묌묍묎묏';
		$expects = '묀묁묂묃묄묅묆묇묈묉묊묋묌묍묎묏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '묐묑묒묓묔묕묖묗묘묙묚묛묜묝묞묟';
		$expects = '묐묑묒묓묔묕묖묗묘묙묚묛묜묝묞묟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '묠묡묢묣묤묥묦묧묨묩묪묫묬묭묮묯';
		$expects = '묠묡묢묣묤묥묦묧묨묩묪묫묬묭묮묯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '묰묱묲묳무묵묶묷문묹묺묻물묽묾묿';
		$expects = '묰묱묲묳무묵묶묷문묹묺묻물묽묾묿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뭀뭁뭂뭃뭄뭅뭆뭇뭈뭉뭊뭋뭌뭍뭎뭏';
		$expects = '뭀뭁뭂뭃뭄뭅뭆뭇뭈뭉뭊뭋뭌뭍뭎뭏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뭐뭑뭒뭓뭔뭕뭖뭗뭘뭙뭚뭛뭜뭝뭞뭟';
		$expects = '뭐뭑뭒뭓뭔뭕뭖뭗뭘뭙뭚뭛뭜뭝뭞뭟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뭠뭡뭢뭣뭤뭥뭦뭧뭨뭩뭪뭫뭬뭭뭮뭯';
		$expects = '뭠뭡뭢뭣뭤뭥뭦뭧뭨뭩뭪뭫뭬뭭뭮뭯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뭰뭱뭲뭳뭴뭵뭶뭷뭸뭹뭺뭻뭼뭽뭾뭿';
		$expects = '뭰뭱뭲뭳뭴뭵뭶뭷뭸뭹뭺뭻뭼뭽뭾뭿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뮀뮁뮂뮃뮄뮅뮆뮇뮈뮉뮊뮋뮌뮍뮎뮏';
		$expects = '뮀뮁뮂뮃뮄뮅뮆뮇뮈뮉뮊뮋뮌뮍뮎뮏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뮐뮑뮒뮓뮔뮕뮖뮗뮘뮙뮚뮛뮜뮝뮞뮟';
		$expects = '뮐뮑뮒뮓뮔뮕뮖뮗뮘뮙뮚뮛뮜뮝뮞뮟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뮠뮡뮢뮣뮤뮥뮦뮧뮨뮩뮪뮫뮬뮭뮮뮯';
		$expects = '뮠뮡뮢뮣뮤뮥뮦뮧뮨뮩뮪뮫뮬뮭뮮뮯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뮰뮱뮲뮳뮴뮵뮶뮷뮸뮹뮺뮻뮼뮽뮾뮿';
		$expects = '뮰뮱뮲뮳뮴뮵뮶뮷뮸뮹뮺뮻뮼뮽뮾뮿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '므믁믂믃믄믅믆믇믈믉믊믋믌믍믎믏';
		$expects = '므믁믂믃믄믅믆믇믈믉믊믋믌믍믎믏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '믐믑믒믓믔믕믖믗믘믙믚믛믜믝믞믟';
		$expects = '믐믑믒믓믔믕믖믗믘믙믚믛믜믝믞믟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '믠믡믢믣믤믥믦믧믨믩믪믫믬믭믮믯';
		$expects = '믠믡믢믣믤믥믦믧믨믩믪믫믬믭믮믯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '믰믱믲믳믴믵믶믷미믹믺믻민믽믾믿';
		$expects = '믰믱믲믳믴믵믶믷미믹믺믻민믽믾믿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '밀밁밂밃밄밅밆밇밈밉밊밋밌밍밎및';
		$expects = '밀밁밂밃밄밅밆밇밈밉밊밋밌밍밎및';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '밐밑밒밓바박밖밗반밙밚받발밝밞밟';
		$expects = '밐밑밒밓바박밖밗반밙밚받발밝밞밟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '밠밡밢밣밤밥밦밧밨방밪밫밬밭밮밯';
		$expects = '밠밡밢밣밤밥밦밧밨방밪밫밬밭밮밯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '배백밲밳밴밵밶밷밸밹밺밻밼밽밾밿';
		$expects = '배백밲밳밴밵밶밷밸밹밺밻밼밽밾밿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뱀뱁뱂뱃뱄뱅뱆뱇뱈뱉뱊뱋뱌뱍뱎뱏';
		$expects = '뱀뱁뱂뱃뱄뱅뱆뱇뱈뱉뱊뱋뱌뱍뱎뱏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뱐뱑뱒뱓뱔뱕뱖뱗뱘뱙뱚뱛뱜뱝뱞뱟';
		$expects = '뱐뱑뱒뱓뱔뱕뱖뱗뱘뱙뱚뱛뱜뱝뱞뱟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뱠뱡뱢뱣뱤뱥뱦뱧뱨뱩뱪뱫뱬뱭뱮뱯';
		$expects = '뱠뱡뱢뱣뱤뱥뱦뱧뱨뱩뱪뱫뱬뱭뱮뱯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뱰뱱뱲뱳뱴뱵뱶뱷뱸뱹뱺뱻뱼뱽뱾뱿';
		$expects = '뱰뱱뱲뱳뱴뱵뱶뱷뱸뱹뱺뱻뱼뱽뱾뱿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '벀벁벂벃버벅벆벇번벉벊벋벌벍벎벏';
		$expects = '벀벁벂벃버벅벆벇번벉벊벋벌벍벎벏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '벐벑벒벓범법벖벗벘벙벚벛벜벝벞벟';
		$expects = '벐벑벒벓범법벖벗벘벙벚벛벜벝벞벟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '베벡벢벣벤벥벦벧벨벩벪벫벬벭벮벯';
		$expects = '베벡벢벣벤벥벦벧벨벩벪벫벬벭벮벯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '벰벱벲벳벴벵벶벷벸벹벺벻벼벽벾벿';
		$expects = '벰벱벲벳벴벵벶벷벸벹벺벻벼벽벾벿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '변볁볂볃별볅볆볇볈볉볊볋볌볍볎볏';
		$expects = '변볁볂볃별볅볆볇볈볉볊볋볌볍볎볏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '볐병볒볓볔볕볖볗볘볙볚볛볜볝볞볟';
		$expects = '볐병볒볓볔볕볖볗볘볙볚볛볜볝볞볟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '볠볡볢볣볤볥볦볧볨볩볪볫볬볭볮볯';
		$expects = '볠볡볢볣볤볥볦볧볨볩볪볫볬볭볮볯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '볰볱볲볳보복볶볷본볹볺볻볼볽볾볿';
		$expects = '볰볱볲볳보복볶볷본볹볺볻볼볽볾볿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '봀봁봂봃봄봅봆봇봈봉봊봋봌봍봎봏';
		$expects = '봀봁봂봃봄봅봆봇봈봉봊봋봌봍봎봏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '봐봑봒봓봔봕봖봗봘봙봚봛봜봝봞봟';
		$expects = '봐봑봒봓봔봕봖봗봘봙봚봛봜봝봞봟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '봠봡봢봣봤봥봦봧봨봩봪봫봬봭봮봯';
		$expects = '봠봡봢봣봤봥봦봧봨봩봪봫봬봭봮봯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '봰봱봲봳봴봵봶봷봸봹봺봻봼봽봾봿';
		$expects = '봰봱봲봳봴봵봶봷봸봹봺봻봼봽봾봿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뵀뵁뵂뵃뵄뵅뵆뵇뵈뵉뵊뵋뵌뵍뵎뵏';
		$expects = '뵀뵁뵂뵃뵄뵅뵆뵇뵈뵉뵊뵋뵌뵍뵎뵏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뵐뵑뵒뵓뵔뵕뵖뵗뵘뵙뵚뵛뵜뵝뵞뵟';
		$expects = '뵐뵑뵒뵓뵔뵕뵖뵗뵘뵙뵚뵛뵜뵝뵞뵟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뵠뵡뵢뵣뵤뵥뵦뵧뵨뵩뵪뵫뵬뵭뵮뵯';
		$expects = '뵠뵡뵢뵣뵤뵥뵦뵧뵨뵩뵪뵫뵬뵭뵮뵯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뵰뵱뵲뵳뵴뵵뵶뵷뵸뵹뵺뵻뵼뵽뵾뵿';
		$expects = '뵰뵱뵲뵳뵴뵵뵶뵷뵸뵹뵺뵻뵼뵽뵾뵿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '부북붂붃분붅붆붇불붉붊붋붌붍붎붏';
		$expects = '부북붂붃분붅붆붇불붉붊붋붌붍붎붏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '붐붑붒붓붔붕붖붗붘붙붚붛붜붝붞붟';
		$expects = '붐붑붒붓붔붕붖붗붘붙붚붛붜붝붞붟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '붠붡붢붣붤붥붦붧붨붩붪붫붬붭붮붯';
		$expects = '붠붡붢붣붤붥붦붧붨붩붪붫붬붭붮붯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '붰붱붲붳붴붵붶붷붸붹붺붻붼붽붾붿';
		$expects = '붰붱붲붳붴붵붶붷붸붹붺붻붼붽붾붿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뷀뷁뷂뷃뷄뷅뷆뷇뷈뷉뷊뷋뷌뷍뷎뷏';
		$expects = '뷀뷁뷂뷃뷄뷅뷆뷇뷈뷉뷊뷋뷌뷍뷎뷏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뷐뷑뷒뷓뷔뷕뷖뷗뷘뷙뷚뷛뷜뷝뷞뷟';
		$expects = '뷐뷑뷒뷓뷔뷕뷖뷗뷘뷙뷚뷛뷜뷝뷞뷟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뷠뷡뷢뷣뷤뷥뷦뷧뷨뷩뷪뷫뷬뷭뷮뷯';
		$expects = '뷠뷡뷢뷣뷤뷥뷦뷧뷨뷩뷪뷫뷬뷭뷮뷯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뷰뷱뷲뷳뷴뷵뷶뷷뷸뷹뷺뷻뷼뷽뷾뷿';
		$expects = '뷰뷱뷲뷳뷴뷵뷶뷷뷸뷹뷺뷻뷼뷽뷾뷿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '븀븁븂븃븄븅븆븇븈븉븊븋브븍븎븏';
		$expects = '븀븁븂븃븄븅븆븇븈븉븊븋브븍븎븏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '븐븑븒븓블븕븖븗븘븙븚븛븜븝븞븟';
		$expects = '븐븑븒븓블븕븖븗븘븙븚븛븜븝븞븟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '븠븡븢븣븤븥븦븧븨븩븪븫븬븭븮븯';
		$expects = '븠븡븢븣븤븥븦븧븨븩븪븫븬븭븮븯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '븰븱븲븳븴븵븶븷븸븹븺븻븼븽븾븿';
		$expects = '븰븱븲븳븴븵븶븷븸븹븺븻븼븽븾븿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '빀빁빂빃비빅빆빇빈빉빊빋빌빍빎빏';
		$expects = '빀빁빂빃비빅빆빇빈빉빊빋빌빍빎빏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '빐빑빒빓빔빕빖빗빘빙빚빛빜빝빞빟';
		$expects = '빐빑빒빓빔빕빖빗빘빙빚빛빜빝빞빟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '빠빡빢빣빤빥빦빧빨빩빪빫빬빭빮빯';
		$expects = '빠빡빢빣빤빥빦빧빨빩빪빫빬빭빮빯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '빰빱빲빳빴빵빶빷빸빹빺빻빼빽빾빿';
		$expects = '빰빱빲빳빴빵빶빷빸빹빺빻빼빽빾빿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뺀뺁뺂뺃뺄뺅뺆뺇뺈뺉뺊뺋뺌뺍뺎뺏';
		$expects = '뺀뺁뺂뺃뺄뺅뺆뺇뺈뺉뺊뺋뺌뺍뺎뺏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뺐뺑뺒뺓뺔뺕뺖뺗뺘뺙뺚뺛뺜뺝뺞뺟';
		$expects = '뺐뺑뺒뺓뺔뺕뺖뺗뺘뺙뺚뺛뺜뺝뺞뺟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뺠뺡뺢뺣뺤뺥뺦뺧뺨뺩뺪뺫뺬뺭뺮뺯';
		$expects = '뺠뺡뺢뺣뺤뺥뺦뺧뺨뺩뺪뺫뺬뺭뺮뺯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뺰뺱뺲뺳뺴뺵뺶뺷뺸뺹뺺뺻뺼뺽뺾뺿';
		$expects = '뺰뺱뺲뺳뺴뺵뺶뺷뺸뺹뺺뺻뺼뺽뺾뺿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뻀뻁뻂뻃뻄뻅뻆뻇뻈뻉뻊뻋뻌뻍뻎뻏';
		$expects = '뻀뻁뻂뻃뻄뻅뻆뻇뻈뻉뻊뻋뻌뻍뻎뻏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뻐뻑뻒뻓뻔뻕뻖뻗뻘뻙뻚뻛뻜뻝뻞뻟';
		$expects = '뻐뻑뻒뻓뻔뻕뻖뻗뻘뻙뻚뻛뻜뻝뻞뻟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뻠뻡뻢뻣뻤뻥뻦뻧뻨뻩뻪뻫뻬뻭뻮뻯';
		$expects = '뻠뻡뻢뻣뻤뻥뻦뻧뻨뻩뻪뻫뻬뻭뻮뻯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뻰뻱뻲뻳뻴뻵뻶뻷뻸뻹뻺뻻뻼뻽뻾뻿';
		$expects = '뻰뻱뻲뻳뻴뻵뻶뻷뻸뻹뻺뻻뻼뻽뻾뻿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뼀뼁뼂뼃뼄뼅뼆뼇뼈뼉뼊뼋뼌뼍뼎뼏';
		$expects = '뼀뼁뼂뼃뼄뼅뼆뼇뼈뼉뼊뼋뼌뼍뼎뼏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뼐뼑뼒뼓뼔뼕뼖뼗뼘뼙뼚뼛뼜뼝뼞뼟';
		$expects = '뼐뼑뼒뼓뼔뼕뼖뼗뼘뼙뼚뼛뼜뼝뼞뼟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뼠뼡뼢뼣뼤뼥뼦뼧뼨뼩뼪뼫뼬뼭뼮뼯';
		$expects = '뼠뼡뼢뼣뼤뼥뼦뼧뼨뼩뼪뼫뼬뼭뼮뼯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뼰뼱뼲뼳뼴뼵뼶뼷뼸뼹뼺뼻뼼뼽뼾뼿';
		$expects = '뼰뼱뼲뼳뼴뼵뼶뼷뼸뼹뼺뼻뼼뼽뼾뼿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뽀뽁뽂뽃뽄뽅뽆뽇뽈뽉뽊뽋뽌뽍뽎뽏';
		$expects = '뽀뽁뽂뽃뽄뽅뽆뽇뽈뽉뽊뽋뽌뽍뽎뽏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뽐뽑뽒뽓뽔뽕뽖뽗뽘뽙뽚뽛뽜뽝뽞뽟';
		$expects = '뽐뽑뽒뽓뽔뽕뽖뽗뽘뽙뽚뽛뽜뽝뽞뽟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뽠뽡뽢뽣뽤뽥뽦뽧뽨뽩뽪뽫뽬뽭뽮뽯';
		$expects = '뽠뽡뽢뽣뽤뽥뽦뽧뽨뽩뽪뽫뽬뽭뽮뽯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뽰뽱뽲뽳뽴뽵뽶뽷뽸뽹뽺뽻뽼뽽뽾뽿';
		$expects = '뽰뽱뽲뽳뽴뽵뽶뽷뽸뽹뽺뽻뽼뽽뽾뽿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뾀뾁뾂뾃뾄뾅뾆뾇뾈뾉뾊뾋뾌뾍뾎뾏';
		$expects = '뾀뾁뾂뾃뾄뾅뾆뾇뾈뾉뾊뾋뾌뾍뾎뾏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뾐뾑뾒뾓뾔뾕뾖뾗뾘뾙뾚뾛뾜뾝뾞뾟';
		$expects = '뾐뾑뾒뾓뾔뾕뾖뾗뾘뾙뾚뾛뾜뾝뾞뾟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뾠뾡뾢뾣뾤뾥뾦뾧뾨뾩뾪뾫뾬뾭뾮뾯';
		$expects = '뾠뾡뾢뾣뾤뾥뾦뾧뾨뾩뾪뾫뾬뾭뾮뾯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뾰뾱뾲뾳뾴뾵뾶뾷뾸뾹뾺뾻뾼뾽뾾뾿';
		$expects = '뾰뾱뾲뾳뾴뾵뾶뾷뾸뾹뾺뾻뾼뾽뾾뾿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뿀뿁뿂뿃뿄뿅뿆뿇뿈뿉뿊뿋뿌뿍뿎뿏';
		$expects = '뿀뿁뿂뿃뿄뿅뿆뿇뿈뿉뿊뿋뿌뿍뿎뿏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뿐뿑뿒뿓뿔뿕뿖뿗뿘뿙뿚뿛뿜뿝뿞뿟';
		$expects = '뿐뿑뿒뿓뿔뿕뿖뿗뿘뿙뿚뿛뿜뿝뿞뿟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뿠뿡뿢뿣뿤뿥뿦뿧뿨뿩뿪뿫뿬뿭뿮뿯';
		$expects = '뿠뿡뿢뿣뿤뿥뿦뿧뿨뿩뿪뿫뿬뿭뿮뿯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '뿰뿱뿲뿳뿴뿵뿶뿷뿸뿹뿺뿻뿼뿽뿾뿿';
		$expects = '뿰뿱뿲뿳뿴뿵뿶뿷뿸뿹뿺뿻뿼뿽뿾뿿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSectionc method
	 *
	 * Testing characters c000 - cfff
	 *
	 * @return void
	 */
	public function testSectionc() {
		$string = '쀀쀁쀂쀃쀄쀅쀆쀇쀈쀉쀊쀋쀌쀍쀎쀏';
		$expects = '쀀쀁쀂쀃쀄쀅쀆쀇쀈쀉쀊쀋쀌쀍쀎쀏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쀐쀑쀒쀓쀔쀕쀖쀗쀘쀙쀚쀛쀜쀝쀞쀟';
		$expects = '쀐쀑쀒쀓쀔쀕쀖쀗쀘쀙쀚쀛쀜쀝쀞쀟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쀠쀡쀢쀣쀤쀥쀦쀧쀨쀩쀪쀫쀬쀭쀮쀯';
		$expects = '쀠쀡쀢쀣쀤쀥쀦쀧쀨쀩쀪쀫쀬쀭쀮쀯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쀰쀱쀲쀳쀴쀵쀶쀷쀸쀹쀺쀻쀼쀽쀾쀿';
		$expects = '쀰쀱쀲쀳쀴쀵쀶쀷쀸쀹쀺쀻쀼쀽쀾쀿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쁀쁁쁂쁃쁄쁅쁆쁇쁈쁉쁊쁋쁌쁍쁎쁏';
		$expects = '쁀쁁쁂쁃쁄쁅쁆쁇쁈쁉쁊쁋쁌쁍쁎쁏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쁐쁑쁒쁓쁔쁕쁖쁗쁘쁙쁚쁛쁜쁝쁞쁟';
		$expects = '쁐쁑쁒쁓쁔쁕쁖쁗쁘쁙쁚쁛쁜쁝쁞쁟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쁠쁡쁢쁣쁤쁥쁦쁧쁨쁩쁪쁫쁬쁭쁮쁯';
		$expects = '쁠쁡쁢쁣쁤쁥쁦쁧쁨쁩쁪쁫쁬쁭쁮쁯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쁰쁱쁲쁳쁴쁵쁶쁷쁸쁹쁺쁻쁼쁽쁾쁿';
		$expects = '쁰쁱쁲쁳쁴쁵쁶쁷쁸쁹쁺쁻쁼쁽쁾쁿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '삀삁삂삃삄삅삆삇삈삉삊삋삌삍삎삏';
		$expects = '삀삁삂삃삄삅삆삇삈삉삊삋삌삍삎삏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '삐삑삒삓삔삕삖삗삘삙삚삛삜삝삞삟';
		$expects = '삐삑삒삓삔삕삖삗삘삙삚삛삜삝삞삟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '삠삡삢삣삤삥삦삧삨삩삪삫사삭삮삯';
		$expects = '삠삡삢삣삤삥삦삧삨삩삪삫사삭삮삯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '산삱삲삳살삵삶삷삸삹삺삻삼삽삾삿';
		$expects = '산삱삲삳살삵삶삷삸삹삺삻삼삽삾삿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '샀상샂샃샄샅샆샇새색샊샋샌샍샎샏';
		$expects = '샀상샂샃샄샅샆샇새색샊샋샌샍샎샏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '샐샑샒샓샔샕샖샗샘샙샚샛샜생샞샟';
		$expects = '샐샑샒샓샔샕샖샗샘샙샚샛샜생샞샟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '샠샡샢샣샤샥샦샧샨샩샪샫샬샭샮샯';
		$expects = '샠샡샢샣샤샥샦샧샨샩샪샫샬샭샮샯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '샰샱샲샳샴샵샶샷샸샹샺샻샼샽샾샿';
		$expects = '샰샱샲샳샴샵샶샷샸샹샺샻샼샽샾샿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '섀섁섂섃섄섅섆섇섈섉섊섋섌섍섎섏';
		$expects = '섀섁섂섃섄섅섆섇섈섉섊섋섌섍섎섏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '섐섑섒섓섔섕섖섗섘섙섚섛서석섞섟';
		$expects = '섐섑섒섓섔섕섖섗섘섙섚섛서석섞섟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '선섡섢섣설섥섦섧섨섩섪섫섬섭섮섯';
		$expects = '선섡섢섣설섥섦섧섨섩섪섫섬섭섮섯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '섰성섲섳섴섵섶섷세섹섺섻센섽섾섿';
		$expects = '섰성섲섳섴섵섶섷세섹섺섻센섽섾섿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '셀셁셂셃셄셅셆셇셈셉셊셋셌셍셎셏';
		$expects = '셀셁셂셃셄셅셆셇셈셉셊셋셌셍셎셏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '셐셑셒셓셔셕셖셗션셙셚셛셜셝셞셟';
		$expects = '셐셑셒셓셔셕셖셗션셙셚셛셜셝셞셟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '셠셡셢셣셤셥셦셧셨셩셪셫셬셭셮셯';
		$expects = '셠셡셢셣셤셥셦셧셨셩셪셫셬셭셮셯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '셰셱셲셳셴셵셶셷셸셹셺셻셼셽셾셿';
		$expects = '셰셱셲셳셴셵셶셷셸셹셺셻셼셽셾셿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '솀솁솂솃솄솅솆솇솈솉솊솋소속솎솏';
		$expects = '솀솁솂솃솄솅솆솇솈솉솊솋소속솎솏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '손솑솒솓솔솕솖솗솘솙솚솛솜솝솞솟';
		$expects = '손솑솒솓솔솕솖솗솘솙솚솛솜솝솞솟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '솠송솢솣솤솥솦솧솨솩솪솫솬솭솮솯';
		$expects = '솠송솢솣솤솥솦솧솨솩솪솫솬솭솮솯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '솰솱솲솳솴솵솶솷솸솹솺솻솼솽솾솿';
		$expects = '솰솱솲솳솴솵솶솷솸솹솺솻솼솽솾솿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쇀쇁쇂쇃쇄쇅쇆쇇쇈쇉쇊쇋쇌쇍쇎쇏';
		$expects = '쇀쇁쇂쇃쇄쇅쇆쇇쇈쇉쇊쇋쇌쇍쇎쇏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쇐쇑쇒쇓쇔쇕쇖쇗쇘쇙쇚쇛쇜쇝쇞쇟';
		$expects = '쇐쇑쇒쇓쇔쇕쇖쇗쇘쇙쇚쇛쇜쇝쇞쇟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쇠쇡쇢쇣쇤쇥쇦쇧쇨쇩쇪쇫쇬쇭쇮쇯';
		$expects = '쇠쇡쇢쇣쇤쇥쇦쇧쇨쇩쇪쇫쇬쇭쇮쇯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쇰쇱쇲쇳쇴쇵쇶쇷쇸쇹쇺쇻쇼쇽쇾쇿';
		$expects = '쇰쇱쇲쇳쇴쇵쇶쇷쇸쇹쇺쇻쇼쇽쇾쇿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '숀숁숂숃숄숅숆숇숈숉숊숋숌숍숎숏';
		$expects = '숀숁숂숃숄숅숆숇숈숉숊숋숌숍숎숏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '숐숑숒숓숔숕숖숗수숙숚숛순숝숞숟';
		$expects = '숐숑숒숓숔숕숖숗수숙숚숛순숝숞숟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '술숡숢숣숤숥숦숧숨숩숪숫숬숭숮숯';
		$expects = '술숡숢숣숤숥숦숧숨숩숪숫숬숭숮숯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '숰숱숲숳숴숵숶숷숸숹숺숻숼숽숾숿';
		$expects = '숰숱숲숳숴숵숶숷숸숹숺숻숼숽숾숿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쉀쉁쉂쉃쉄쉅쉆쉇쉈쉉쉊쉋쉌쉍쉎쉏';
		$expects = '쉀쉁쉂쉃쉄쉅쉆쉇쉈쉉쉊쉋쉌쉍쉎쉏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쉐쉑쉒쉓쉔쉕쉖쉗쉘쉙쉚쉛쉜쉝쉞쉟';
		$expects = '쉐쉑쉒쉓쉔쉕쉖쉗쉘쉙쉚쉛쉜쉝쉞쉟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쉠쉡쉢쉣쉤쉥쉦쉧쉨쉩쉪쉫쉬쉭쉮쉯';
		$expects = '쉠쉡쉢쉣쉤쉥쉦쉧쉨쉩쉪쉫쉬쉭쉮쉯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쉰쉱쉲쉳쉴쉵쉶쉷쉸쉹쉺쉻쉼쉽쉾쉿';
		$expects = '쉰쉱쉲쉳쉴쉵쉶쉷쉸쉹쉺쉻쉼쉽쉾쉿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '슀슁슂슃슄슅슆슇슈슉슊슋슌슍슎슏';
		$expects = '슀슁슂슃슄슅슆슇슈슉슊슋슌슍슎슏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '슐슑슒슓슔슕슖슗슘슙슚슛슜슝슞슟';
		$expects = '슐슑슒슓슔슕슖슗슘슙슚슛슜슝슞슟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '슠슡슢슣스슥슦슧슨슩슪슫슬슭슮슯';
		$expects = '슠슡슢슣스슥슦슧슨슩슪슫슬슭슮슯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '슰슱슲슳슴습슶슷슸승슺슻슼슽슾슿';
		$expects = '슰슱슲슳슴습슶슷슸승슺슻슼슽슾슿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '싀싁싂싃싄싅싆싇싈싉싊싋싌싍싎싏';
		$expects = '싀싁싂싃싄싅싆싇싈싉싊싋싌싍싎싏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '싐싑싒싓싔싕싖싗싘싙싚싛시식싞싟';
		$expects = '싐싑싒싓싔싕싖싗싘싙싚싛시식싞싟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '신싡싢싣실싥싦싧싨싩싪싫심십싮싯';
		$expects = '신싡싢싣실싥싦싧싨싩싪싫심십싮싯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '싰싱싲싳싴싵싶싷싸싹싺싻싼싽싾싿';
		$expects = '싰싱싲싳싴싵싶싷싸싹싺싻싼싽싾싿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쌀쌁쌂쌃쌄쌅쌆쌇쌈쌉쌊쌋쌌쌍쌎쌏';
		$expects = '쌀쌁쌂쌃쌄쌅쌆쌇쌈쌉쌊쌋쌌쌍쌎쌏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쌐쌑쌒쌓쌔쌕쌖쌗쌘쌙쌚쌛쌜쌝쌞쌟';
		$expects = '쌐쌑쌒쌓쌔쌕쌖쌗쌘쌙쌚쌛쌜쌝쌞쌟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쌠쌡쌢쌣쌤쌥쌦쌧쌨쌩쌪쌫쌬쌭쌮쌯';
		$expects = '쌠쌡쌢쌣쌤쌥쌦쌧쌨쌩쌪쌫쌬쌭쌮쌯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쌰쌱쌲쌳쌴쌵쌶쌷쌸쌹쌺쌻쌼쌽쌾쌿';
		$expects = '쌰쌱쌲쌳쌴쌵쌶쌷쌸쌹쌺쌻쌼쌽쌾쌿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '썀썁썂썃썄썅썆썇썈썉썊썋썌썍썎썏';
		$expects = '썀썁썂썃썄썅썆썇썈썉썊썋썌썍썎썏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '썐썑썒썓썔썕썖썗썘썙썚썛썜썝썞썟';
		$expects = '썐썑썒썓썔썕썖썗썘썙썚썛썜썝썞썟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '썠썡썢썣썤썥썦썧써썩썪썫썬썭썮썯';
		$expects = '썠썡썢썣썤썥썦썧써썩썪썫썬썭썮썯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '썰썱썲썳썴썵썶썷썸썹썺썻썼썽썾썿';
		$expects = '썰썱썲썳썴썵썶썷썸썹썺썻썼썽썾썿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쎀쎁쎂쎃쎄쎅쎆쎇쎈쎉쎊쎋쎌쎍쎎쎏';
		$expects = '쎀쎁쎂쎃쎄쎅쎆쎇쎈쎉쎊쎋쎌쎍쎎쎏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쎐쎑쎒쎓쎔쎕쎖쎗쎘쎙쎚쎛쎜쎝쎞쎟';
		$expects = '쎐쎑쎒쎓쎔쎕쎖쎗쎘쎙쎚쎛쎜쎝쎞쎟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쎠쎡쎢쎣쎤쎥쎦쎧쎨쎩쎪쎫쎬쎭쎮쎯';
		$expects = '쎠쎡쎢쎣쎤쎥쎦쎧쎨쎩쎪쎫쎬쎭쎮쎯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쎰쎱쎲쎳쎴쎵쎶쎷쎸쎹쎺쎻쎼쎽쎾쎿';
		$expects = '쎰쎱쎲쎳쎴쎵쎶쎷쎸쎹쎺쎻쎼쎽쎾쎿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쏀쏁쏂쏃쏄쏅쏆쏇쏈쏉쏊쏋쏌쏍쏎쏏';
		$expects = '쏀쏁쏂쏃쏄쏅쏆쏇쏈쏉쏊쏋쏌쏍쏎쏏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쏐쏑쏒쏓쏔쏕쏖쏗쏘쏙쏚쏛쏜쏝쏞쏟';
		$expects = '쏐쏑쏒쏓쏔쏕쏖쏗쏘쏙쏚쏛쏜쏝쏞쏟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쏠쏡쏢쏣쏤쏥쏦쏧쏨쏩쏪쏫쏬쏭쏮쏯';
		$expects = '쏠쏡쏢쏣쏤쏥쏦쏧쏨쏩쏪쏫쏬쏭쏮쏯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쏰쏱쏲쏳쏴쏵쏶쏷쏸쏹쏺쏻쏼쏽쏾쏿';
		$expects = '쏰쏱쏲쏳쏴쏵쏶쏷쏸쏹쏺쏻쏼쏽쏾쏿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쐀쐁쐂쐃쐄쐅쐆쐇쐈쐉쐊쐋쐌쐍쐎쐏';
		$expects = '쐀쐁쐂쐃쐄쐅쐆쐇쐈쐉쐊쐋쐌쐍쐎쐏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쐐쐑쐒쐓쐔쐕쐖쐗쐘쐙쐚쐛쐜쐝쐞쐟';
		$expects = '쐐쐑쐒쐓쐔쐕쐖쐗쐘쐙쐚쐛쐜쐝쐞쐟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쐠쐡쐢쐣쐤쐥쐦쐧쐨쐩쐪쐫쐬쐭쐮쐯';
		$expects = '쐠쐡쐢쐣쐤쐥쐦쐧쐨쐩쐪쐫쐬쐭쐮쐯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쐰쐱쐲쐳쐴쐵쐶쐷쐸쐹쐺쐻쐼쐽쐾쐿';
		$expects = '쐰쐱쐲쐳쐴쐵쐶쐷쐸쐹쐺쐻쐼쐽쐾쐿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쑀쑁쑂쑃쑄쑅쑆쑇쑈쑉쑊쑋쑌쑍쑎쑏';
		$expects = '쑀쑁쑂쑃쑄쑅쑆쑇쑈쑉쑊쑋쑌쑍쑎쑏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쑐쑑쑒쑓쑔쑕쑖쑗쑘쑙쑚쑛쑜쑝쑞쑟';
		$expects = '쑐쑑쑒쑓쑔쑕쑖쑗쑘쑙쑚쑛쑜쑝쑞쑟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쑠쑡쑢쑣쑤쑥쑦쑧쑨쑩쑪쑫쑬쑭쑮쑯';
		$expects = '쑠쑡쑢쑣쑤쑥쑦쑧쑨쑩쑪쑫쑬쑭쑮쑯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쑰쑱쑲쑳쑴쑵쑶쑷쑸쑹쑺쑻쑼쑽쑾쑿';
		$expects = '쑰쑱쑲쑳쑴쑵쑶쑷쑸쑹쑺쑻쑼쑽쑾쑿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쒀쒁쒂쒃쒄쒅쒆쒇쒈쒉쒊쒋쒌쒍쒎쒏';
		$expects = '쒀쒁쒂쒃쒄쒅쒆쒇쒈쒉쒊쒋쒌쒍쒎쒏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쒐쒑쒒쒓쒔쒕쒖쒗쒘쒙쒚쒛쒜쒝쒞쒟';
		$expects = '쒐쒑쒒쒓쒔쒕쒖쒗쒘쒙쒚쒛쒜쒝쒞쒟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쒠쒡쒢쒣쒤쒥쒦쒧쒨쒩쒪쒫쒬쒭쒮쒯';
		$expects = '쒠쒡쒢쒣쒤쒥쒦쒧쒨쒩쒪쒫쒬쒭쒮쒯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쒰쒱쒲쒳쒴쒵쒶쒷쒸쒹쒺쒻쒼쒽쒾쒿';
		$expects = '쒰쒱쒲쒳쒴쒵쒶쒷쒸쒹쒺쒻쒼쒽쒾쒿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쓀쓁쓂쓃쓄쓅쓆쓇쓈쓉쓊쓋쓌쓍쓎쓏';
		$expects = '쓀쓁쓂쓃쓄쓅쓆쓇쓈쓉쓊쓋쓌쓍쓎쓏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쓐쓑쓒쓓쓔쓕쓖쓗쓘쓙쓚쓛쓜쓝쓞쓟';
		$expects = '쓐쓑쓒쓓쓔쓕쓖쓗쓘쓙쓚쓛쓜쓝쓞쓟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쓠쓡쓢쓣쓤쓥쓦쓧쓨쓩쓪쓫쓬쓭쓮쓯';
		$expects = '쓠쓡쓢쓣쓤쓥쓦쓧쓨쓩쓪쓫쓬쓭쓮쓯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쓰쓱쓲쓳쓴쓵쓶쓷쓸쓹쓺쓻쓼쓽쓾쓿';
		$expects = '쓰쓱쓲쓳쓴쓵쓶쓷쓸쓹쓺쓻쓼쓽쓾쓿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '씀씁씂씃씄씅씆씇씈씉씊씋씌씍씎씏';
		$expects = '씀씁씂씃씄씅씆씇씈씉씊씋씌씍씎씏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '씐씑씒씓씔씕씖씗씘씙씚씛씜씝씞씟';
		$expects = '씐씑씒씓씔씕씖씗씘씙씚씛씜씝씞씟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '씠씡씢씣씤씥씦씧씨씩씪씫씬씭씮씯';
		$expects = '씠씡씢씣씤씥씦씧씨씩씪씫씬씭씮씯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '씰씱씲씳씴씵씶씷씸씹씺씻씼씽씾씿';
		$expects = '씰씱씲씳씴씵씶씷씸씹씺씻씼씽씾씿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '앀앁앂앃아악앆앇안앉않앋알앍앎앏';
		$expects = '앀앁앂앃아악앆앇안앉않앋알앍앎앏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '앐앑앒앓암압앖앗았앙앚앛앜앝앞앟';
		$expects = '앐앑앒앓암압앖앗았앙앚앛앜앝앞앟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '애액앢앣앤앥앦앧앨앩앪앫앬앭앮앯';
		$expects = '애액앢앣앤앥앦앧앨앩앪앫앬앭앮앯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '앰앱앲앳앴앵앶앷앸앹앺앻야약앾앿';
		$expects = '앰앱앲앳앴앵앶앷앸앹앺앻야약앾앿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '얀얁얂얃얄얅얆얇얈얉얊얋얌얍얎얏';
		$expects = '얀얁얂얃얄얅얆얇얈얉얊얋얌얍얎얏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '얐양얒얓얔얕얖얗얘얙얚얛얜얝얞얟';
		$expects = '얐양얒얓얔얕얖얗얘얙얚얛얜얝얞얟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '얠얡얢얣얤얥얦얧얨얩얪얫얬얭얮얯';
		$expects = '얠얡얢얣얤얥얦얧얨얩얪얫얬얭얮얯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '얰얱얲얳어억얶얷언얹얺얻얼얽얾얿';
		$expects = '얰얱얲얳어억얶얷언얹얺얻얼얽얾얿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '엀엁엂엃엄업없엇었엉엊엋엌엍엎엏';
		$expects = '엀엁엂엃엄업없엇었엉엊엋엌엍엎엏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '에엑엒엓엔엕엖엗엘엙엚엛엜엝엞엟';
		$expects = '에엑엒엓엔엕엖엗엘엙엚엛엜엝엞엟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '엠엡엢엣엤엥엦엧엨엩엪엫여역엮엯';
		$expects = '엠엡엢엣엤엥엦엧엨엩엪엫여역엮엯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '연엱엲엳열엵엶엷엸엹엺엻염엽엾엿';
		$expects = '연엱엲엳열엵엶엷엸엹엺엻염엽엾엿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '였영옂옃옄옅옆옇예옉옊옋옌옍옎옏';
		$expects = '였영옂옃옄옅옆옇예옉옊옋옌옍옎옏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '옐옑옒옓옔옕옖옗옘옙옚옛옜옝옞옟';
		$expects = '옐옑옒옓옔옕옖옗옘옙옚옛옜옝옞옟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '옠옡옢옣오옥옦옧온옩옪옫올옭옮옯';
		$expects = '옠옡옢옣오옥옦옧온옩옪옫올옭옮옯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '옰옱옲옳옴옵옶옷옸옹옺옻옼옽옾옿';
		$expects = '옰옱옲옳옴옵옶옷옸옹옺옻옼옽옾옿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '와왁왂왃완왅왆왇왈왉왊왋왌왍왎왏';
		$expects = '와왁왂왃완왅왆왇왈왉왊왋왌왍왎왏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '왐왑왒왓왔왕왖왗왘왙왚왛왜왝왞왟';
		$expects = '왐왑왒왓왔왕왖왗왘왙왚왛왜왝왞왟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '왠왡왢왣왤왥왦왧왨왩왪왫왬왭왮왯';
		$expects = '왠왡왢왣왤왥왦왧왨왩왪왫왬왭왮왯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '왰왱왲왳왴왵왶왷외왹왺왻왼왽왾왿';
		$expects = '왰왱왲왳왴왵왶왷외왹왺왻왼왽왾왿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '욀욁욂욃욄욅욆욇욈욉욊욋욌욍욎욏';
		$expects = '욀욁욂욃욄욅욆욇욈욉욊욋욌욍욎욏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '욐욑욒욓요욕욖욗욘욙욚욛욜욝욞욟';
		$expects = '욐욑욒욓요욕욖욗욘욙욚욛욜욝욞욟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '욠욡욢욣욤욥욦욧욨용욪욫욬욭욮욯';
		$expects = '욠욡욢욣욤욥욦욧욨용욪욫욬욭욮욯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '우욱욲욳운욵욶욷울욹욺욻욼욽욾욿';
		$expects = '우욱욲욳운욵욶욷울욹욺욻욼욽욾욿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '움웁웂웃웄웅웆웇웈웉웊웋워웍웎웏';
		$expects = '움웁웂웃웄웅웆웇웈웉웊웋워웍웎웏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '원웑웒웓월웕웖웗웘웙웚웛웜웝웞웟';
		$expects = '원웑웒웓월웕웖웗웘웙웚웛웜웝웞웟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '웠웡웢웣웤웥웦웧웨웩웪웫웬웭웮웯';
		$expects = '웠웡웢웣웤웥웦웧웨웩웪웫웬웭웮웯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '웰웱웲웳웴웵웶웷웸웹웺웻웼웽웾웿';
		$expects = '웰웱웲웳웴웵웶웷웸웹웺웻웼웽웾웿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '윀윁윂윃위윅윆윇윈윉윊윋윌윍윎윏';
		$expects = '윀윁윂윃위윅윆윇윈윉윊윋윌윍윎윏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '윐윑윒윓윔윕윖윗윘윙윚윛윜윝윞윟';
		$expects = '윐윑윒윓윔윕윖윗윘윙윚윛윜윝윞윟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '유육윢윣윤윥윦윧율윩윪윫윬윭윮윯';
		$expects = '유육윢윣윤윥윦윧율윩윪윫윬윭윮윯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '윰윱윲윳윴융윶윷윸윹윺윻으윽윾윿';
		$expects = '윰윱윲윳윴융윶윷윸윹윺윻으윽윾윿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '은읁읂읃을읅읆읇읈읉읊읋음읍읎읏';
		$expects = '은읁읂읃을읅읆읇읈읉읊읋음읍읎읏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '읐응읒읓읔읕읖읗의읙읚읛읜읝읞읟';
		$expects = '읐응읒읓읔읕읖읗의읙읚읛읜읝읞읟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '읠읡읢읣읤읥읦읧읨읩읪읫읬읭읮읯';
		$expects = '읠읡읢읣읤읥읦읧읨읩읪읫읬읭읮읯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '읰읱읲읳이익읶읷인읹읺읻일읽읾읿';
		$expects = '읰읱읲읳이익읶읷인읹읺읻일읽읾읿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '잀잁잂잃임입잆잇있잉잊잋잌잍잎잏';
		$expects = '잀잁잂잃임입잆잇있잉잊잋잌잍잎잏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '자작잒잓잔잕잖잗잘잙잚잛잜잝잞잟';
		$expects = '자작잒잓잔잕잖잗잘잙잚잛잜잝잞잟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '잠잡잢잣잤장잦잧잨잩잪잫재잭잮잯';
		$expects = '잠잡잢잣잤장잦잧잨잩잪잫재잭잮잯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '잰잱잲잳잴잵잶잷잸잹잺잻잼잽잾잿';
		$expects = '잰잱잲잳잴잵잶잷잸잹잺잻잼잽잾잿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쟀쟁쟂쟃쟄쟅쟆쟇쟈쟉쟊쟋쟌쟍쟎쟏';
		$expects = '쟀쟁쟂쟃쟄쟅쟆쟇쟈쟉쟊쟋쟌쟍쟎쟏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쟐쟑쟒쟓쟔쟕쟖쟗쟘쟙쟚쟛쟜쟝쟞쟟';
		$expects = '쟐쟑쟒쟓쟔쟕쟖쟗쟘쟙쟚쟛쟜쟝쟞쟟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쟠쟡쟢쟣쟤쟥쟦쟧쟨쟩쟪쟫쟬쟭쟮쟯';
		$expects = '쟠쟡쟢쟣쟤쟥쟦쟧쟨쟩쟪쟫쟬쟭쟮쟯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쟰쟱쟲쟳쟴쟵쟶쟷쟸쟹쟺쟻쟼쟽쟾쟿';
		$expects = '쟰쟱쟲쟳쟴쟵쟶쟷쟸쟹쟺쟻쟼쟽쟾쟿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '저적젂젃전젅젆젇절젉젊젋젌젍젎젏';
		$expects = '저적젂젃전젅젆젇절젉젊젋젌젍젎젏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '점접젒젓젔정젖젗젘젙젚젛제젝젞젟';
		$expects = '점접젒젓젔정젖젗젘젙젚젛제젝젞젟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '젠젡젢젣젤젥젦젧젨젩젪젫젬젭젮젯';
		$expects = '젠젡젢젣젤젥젦젧젨젩젪젫젬젭젮젯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '젰젱젲젳젴젵젶젷져젹젺젻젼젽젾젿';
		$expects = '젰젱젲젳젴젵젶젷져젹젺젻젼젽젾젿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '졀졁졂졃졄졅졆졇졈졉졊졋졌졍졎졏';
		$expects = '졀졁졂졃졄졅졆졇졈졉졊졋졌졍졎졏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '졐졑졒졓졔졕졖졗졘졙졚졛졜졝졞졟';
		$expects = '졐졑졒졓졔졕졖졗졘졙졚졛졜졝졞졟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '졠졡졢졣졤졥졦졧졨졩졪졫졬졭졮졯';
		$expects = '졠졡졢졣졤졥졦졧졨졩졪졫졬졭졮졯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '조족졲졳존졵졶졷졸졹졺졻졼졽졾졿';
		$expects = '조족졲졳존졵졶졷졸졹졺졻졼졽졾졿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '좀좁좂좃좄종좆좇좈좉좊좋좌좍좎좏';
		$expects = '좀좁좂좃좄종좆좇좈좉좊좋좌좍좎좏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '좐좑좒좓좔좕좖좗좘좙좚좛좜좝좞좟';
		$expects = '좐좑좒좓좔좕좖좗좘좙좚좛좜좝좞좟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '좠좡좢좣좤좥좦좧좨좩좪좫좬좭좮좯';
		$expects = '좠좡좢좣좤좥좦좧좨좩좪좫좬좭좮좯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '좰좱좲좳좴좵좶좷좸좹좺좻좼좽좾좿';
		$expects = '좰좱좲좳좴좵좶좷좸좹좺좻좼좽좾좿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '죀죁죂죃죄죅죆죇죈죉죊죋죌죍죎죏';
		$expects = '죀죁죂죃죄죅죆죇죈죉죊죋죌죍죎죏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '죐죑죒죓죔죕죖죗죘죙죚죛죜죝죞죟';
		$expects = '죐죑죒죓죔죕죖죗죘죙죚죛죜죝죞죟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '죠죡죢죣죤죥죦죧죨죩죪죫죬죭죮죯';
		$expects = '죠죡죢죣죤죥죦죧죨죩죪죫죬죭죮죯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '죰죱죲죳죴죵죶죷죸죹죺죻주죽죾죿';
		$expects = '죰죱죲죳죴죵죶죷죸죹죺죻주죽죾죿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '준줁줂줃줄줅줆줇줈줉줊줋줌줍줎줏';
		$expects = '준줁줂줃줄줅줆줇줈줉줊줋줌줍줎줏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '줐중줒줓줔줕줖줗줘줙줚줛줜줝줞줟';
		$expects = '줐중줒줓줔줕줖줗줘줙줚줛줜줝줞줟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '줠줡줢줣줤줥줦줧줨줩줪줫줬줭줮줯';
		$expects = '줠줡줢줣줤줥줦줧줨줩줪줫줬줭줮줯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '줰줱줲줳줴줵줶줷줸줹줺줻줼줽줾줿';
		$expects = '줰줱줲줳줴줵줶줷줸줹줺줻줼줽줾줿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쥀쥁쥂쥃쥄쥅쥆쥇쥈쥉쥊쥋쥌쥍쥎쥏';
		$expects = '쥀쥁쥂쥃쥄쥅쥆쥇쥈쥉쥊쥋쥌쥍쥎쥏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쥐쥑쥒쥓쥔쥕쥖쥗쥘쥙쥚쥛쥜쥝쥞쥟';
		$expects = '쥐쥑쥒쥓쥔쥕쥖쥗쥘쥙쥚쥛쥜쥝쥞쥟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쥠쥡쥢쥣쥤쥥쥦쥧쥨쥩쥪쥫쥬쥭쥮쥯';
		$expects = '쥠쥡쥢쥣쥤쥥쥦쥧쥨쥩쥪쥫쥬쥭쥮쥯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쥰쥱쥲쥳쥴쥵쥶쥷쥸쥹쥺쥻쥼쥽쥾쥿';
		$expects = '쥰쥱쥲쥳쥴쥵쥶쥷쥸쥹쥺쥻쥼쥽쥾쥿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '즀즁즂즃즄즅즆즇즈즉즊즋즌즍즎즏';
		$expects = '즀즁즂즃즄즅즆즇즈즉즊즋즌즍즎즏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '즐즑즒즓즔즕즖즗즘즙즚즛즜증즞즟';
		$expects = '즐즑즒즓즔즕즖즗즘즙즚즛즜증즞즟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '즠즡즢즣즤즥즦즧즨즩즪즫즬즭즮즯';
		$expects = '즠즡즢즣즤즥즦즧즨즩즪즫즬즭즮즯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '즰즱즲즳즴즵즶즷즸즹즺즻즼즽즾즿';
		$expects = '즰즱즲즳즴즵즶즷즸즹즺즻즼즽즾즿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '지직짂짃진짅짆짇질짉짊짋짌짍짎짏';
		$expects = '지직짂짃진짅짆짇질짉짊짋짌짍짎짏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '짐집짒짓짔징짖짗짘짙짚짛짜짝짞짟';
		$expects = '짐집짒짓짔징짖짗짘짙짚짛짜짝짞짟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '짠짡짢짣짤짥짦짧짨짩짪짫짬짭짮짯';
		$expects = '짠짡짢짣짤짥짦짧짨짩짪짫짬짭짮짯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '짰짱짲짳짴짵짶짷째짹짺짻짼짽짾짿';
		$expects = '짰짱짲짳짴짵짶짷째짹짺짻짼짽짾짿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쨀쨁쨂쨃쨄쨅쨆쨇쨈쨉쨊쨋쨌쨍쨎쨏';
		$expects = '쨀쨁쨂쨃쨄쨅쨆쨇쨈쨉쨊쨋쨌쨍쨎쨏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쨐쨑쨒쨓쨔쨕쨖쨗쨘쨙쨚쨛쨜쨝쨞쨟';
		$expects = '쨐쨑쨒쨓쨔쨕쨖쨗쨘쨙쨚쨛쨜쨝쨞쨟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쨠쨡쨢쨣쨤쨥쨦쨧쨨쨩쨪쨫쨬쨭쨮쨯';
		$expects = '쨠쨡쨢쨣쨤쨥쨦쨧쨨쨩쨪쨫쨬쨭쨮쨯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쨰쨱쨲쨳쨴쨵쨶쨷쨸쨹쨺쨻쨼쨽쨾쨿';
		$expects = '쨰쨱쨲쨳쨴쨵쨶쨷쨸쨹쨺쨻쨼쨽쨾쨿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쩀쩁쩂쩃쩄쩅쩆쩇쩈쩉쩊쩋쩌쩍쩎쩏';
		$expects = '쩀쩁쩂쩃쩄쩅쩆쩇쩈쩉쩊쩋쩌쩍쩎쩏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쩐쩑쩒쩓쩔쩕쩖쩗쩘쩙쩚쩛쩜쩝쩞쩟';
		$expects = '쩐쩑쩒쩓쩔쩕쩖쩗쩘쩙쩚쩛쩜쩝쩞쩟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쩠쩡쩢쩣쩤쩥쩦쩧쩨쩩쩪쩫쩬쩭쩮쩯';
		$expects = '쩠쩡쩢쩣쩤쩥쩦쩧쩨쩩쩪쩫쩬쩭쩮쩯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쩰쩱쩲쩳쩴쩵쩶쩷쩸쩹쩺쩻쩼쩽쩾쩿';
		$expects = '쩰쩱쩲쩳쩴쩵쩶쩷쩸쩹쩺쩻쩼쩽쩾쩿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쪀쪁쪂쪃쪄쪅쪆쪇쪈쪉쪊쪋쪌쪍쪎쪏';
		$expects = '쪀쪁쪂쪃쪄쪅쪆쪇쪈쪉쪊쪋쪌쪍쪎쪏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쪐쪑쪒쪓쪔쪕쪖쪗쪘쪙쪚쪛쪜쪝쪞쪟';
		$expects = '쪐쪑쪒쪓쪔쪕쪖쪗쪘쪙쪚쪛쪜쪝쪞쪟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쪠쪡쪢쪣쪤쪥쪦쪧쪨쪩쪪쪫쪬쪭쪮쪯';
		$expects = '쪠쪡쪢쪣쪤쪥쪦쪧쪨쪩쪪쪫쪬쪭쪮쪯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쪰쪱쪲쪳쪴쪵쪶쪷쪸쪹쪺쪻쪼쪽쪾쪿';
		$expects = '쪰쪱쪲쪳쪴쪵쪶쪷쪸쪹쪺쪻쪼쪽쪾쪿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쫀쫁쫂쫃쫄쫅쫆쫇쫈쫉쫊쫋쫌쫍쫎쫏';
		$expects = '쫀쫁쫂쫃쫄쫅쫆쫇쫈쫉쫊쫋쫌쫍쫎쫏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쫐쫑쫒쫓쫔쫕쫖쫗쫘쫙쫚쫛쫜쫝쫞쫟';
		$expects = '쫐쫑쫒쫓쫔쫕쫖쫗쫘쫙쫚쫛쫜쫝쫞쫟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쫠쫡쫢쫣쫤쫥쫦쫧쫨쫩쫪쫫쫬쫭쫮쫯';
		$expects = '쫠쫡쫢쫣쫤쫥쫦쫧쫨쫩쫪쫫쫬쫭쫮쫯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쫰쫱쫲쫳쫴쫵쫶쫷쫸쫹쫺쫻쫼쫽쫾쫿';
		$expects = '쫰쫱쫲쫳쫴쫵쫶쫷쫸쫹쫺쫻쫼쫽쫾쫿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쬀쬁쬂쬃쬄쬅쬆쬇쬈쬉쬊쬋쬌쬍쬎쬏';
		$expects = '쬀쬁쬂쬃쬄쬅쬆쬇쬈쬉쬊쬋쬌쬍쬎쬏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쬐쬑쬒쬓쬔쬕쬖쬗쬘쬙쬚쬛쬜쬝쬞쬟';
		$expects = '쬐쬑쬒쬓쬔쬕쬖쬗쬘쬙쬚쬛쬜쬝쬞쬟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쬠쬡쬢쬣쬤쬥쬦쬧쬨쬩쬪쬫쬬쬭쬮쬯';
		$expects = '쬠쬡쬢쬣쬤쬥쬦쬧쬨쬩쬪쬫쬬쬭쬮쬯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쬰쬱쬲쬳쬴쬵쬶쬷쬸쬹쬺쬻쬼쬽쬾쬿';
		$expects = '쬰쬱쬲쬳쬴쬵쬶쬷쬸쬹쬺쬻쬼쬽쬾쬿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쭀쭁쭂쭃쭄쭅쭆쭇쭈쭉쭊쭋쭌쭍쭎쭏';
		$expects = '쭀쭁쭂쭃쭄쭅쭆쭇쭈쭉쭊쭋쭌쭍쭎쭏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쭐쭑쭒쭓쭔쭕쭖쭗쭘쭙쭚쭛쭜쭝쭞쭟';
		$expects = '쭐쭑쭒쭓쭔쭕쭖쭗쭘쭙쭚쭛쭜쭝쭞쭟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쭠쭡쭢쭣쭤쭥쭦쭧쭨쭩쭪쭫쭬쭭쭮쭯';
		$expects = '쭠쭡쭢쭣쭤쭥쭦쭧쭨쭩쭪쭫쭬쭭쭮쭯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쭰쭱쭲쭳쭴쭵쭶쭷쭸쭹쭺쭻쭼쭽쭾쭿';
		$expects = '쭰쭱쭲쭳쭴쭵쭶쭷쭸쭹쭺쭻쭼쭽쭾쭿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쮀쮁쮂쮃쮄쮅쮆쮇쮈쮉쮊쮋쮌쮍쮎쮏';
		$expects = '쮀쮁쮂쮃쮄쮅쮆쮇쮈쮉쮊쮋쮌쮍쮎쮏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쮐쮑쮒쮓쮔쮕쮖쮗쮘쮙쮚쮛쮜쮝쮞쮟';
		$expects = '쮐쮑쮒쮓쮔쮕쮖쮗쮘쮙쮚쮛쮜쮝쮞쮟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쮠쮡쮢쮣쮤쮥쮦쮧쮨쮩쮪쮫쮬쮭쮮쮯';
		$expects = '쮠쮡쮢쮣쮤쮥쮦쮧쮨쮩쮪쮫쮬쮭쮮쮯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쮰쮱쮲쮳쮴쮵쮶쮷쮸쮹쮺쮻쮼쮽쮾쮿';
		$expects = '쮰쮱쮲쮳쮴쮵쮶쮷쮸쮹쮺쮻쮼쮽쮾쮿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쯀쯁쯂쯃쯄쯅쯆쯇쯈쯉쯊쯋쯌쯍쯎쯏';
		$expects = '쯀쯁쯂쯃쯄쯅쯆쯇쯈쯉쯊쯋쯌쯍쯎쯏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쯐쯑쯒쯓쯔쯕쯖쯗쯘쯙쯚쯛쯜쯝쯞쯟';
		$expects = '쯐쯑쯒쯓쯔쯕쯖쯗쯘쯙쯚쯛쯜쯝쯞쯟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쯠쯡쯢쯣쯤쯥쯦쯧쯨쯩쯪쯫쯬쯭쯮쯯';
		$expects = '쯠쯡쯢쯣쯤쯥쯦쯧쯨쯩쯪쯫쯬쯭쯮쯯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쯰쯱쯲쯳쯴쯵쯶쯷쯸쯹쯺쯻쯼쯽쯾쯿';
		$expects = '쯰쯱쯲쯳쯴쯵쯶쯷쯸쯹쯺쯻쯼쯽쯾쯿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '찀찁찂찃찄찅찆찇찈찉찊찋찌찍찎찏';
		$expects = '찀찁찂찃찄찅찆찇찈찉찊찋찌찍찎찏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '찐찑찒찓찔찕찖찗찘찙찚찛찜찝찞찟';
		$expects = '찐찑찒찓찔찕찖찗찘찙찚찛찜찝찞찟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '찠찡찢찣찤찥찦찧차착찪찫찬찭찮찯';
		$expects = '찠찡찢찣찤찥찦찧차착찪찫찬찭찮찯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '찰찱찲찳찴찵찶찷참찹찺찻찼창찾찿';
		$expects = '찰찱찲찳찴찵찶찷참찹찺찻찼창찾찿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '챀챁챂챃채책챆챇챈챉챊챋챌챍챎챏';
		$expects = '챀챁챂챃채책챆챇챈챉챊챋챌챍챎챏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '챐챑챒챓챔챕챖챗챘챙챚챛챜챝챞챟';
		$expects = '챐챑챒챓챔챕챖챗챘챙챚챛챜챝챞챟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '챠챡챢챣챤챥챦챧챨챩챪챫챬챭챮챯';
		$expects = '챠챡챢챣챤챥챦챧챨챩챪챫챬챭챮챯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '챰챱챲챳챴챵챶챷챸챹챺챻챼챽챾챿';
		$expects = '챰챱챲챳챴챵챶챷챸챹챺챻챼챽챾챿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '첀첁첂첃첄첅첆첇첈첉첊첋첌첍첎첏';
		$expects = '첀첁첂첃첄첅첆첇첈첉첊첋첌첍첎첏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '첐첑첒첓첔첕첖첗처척첚첛천첝첞첟';
		$expects = '첐첑첒첓첔첕첖첗처척첚첛천첝첞첟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '철첡첢첣첤첥첦첧첨첩첪첫첬청첮첯';
		$expects = '철첡첢첣첤첥첦첧첨첩첪첫첬청첮첯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '첰첱첲첳체첵첶첷첸첹첺첻첼첽첾첿';
		$expects = '첰첱첲첳체첵첶첷첸첹첺첻첼첽첾첿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쳀쳁쳂쳃쳄쳅쳆쳇쳈쳉쳊쳋쳌쳍쳎쳏';
		$expects = '쳀쳁쳂쳃쳄쳅쳆쳇쳈쳉쳊쳋쳌쳍쳎쳏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쳐쳑쳒쳓쳔쳕쳖쳗쳘쳙쳚쳛쳜쳝쳞쳟';
		$expects = '쳐쳑쳒쳓쳔쳕쳖쳗쳘쳙쳚쳛쳜쳝쳞쳟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쳠쳡쳢쳣쳤쳥쳦쳧쳨쳩쳪쳫쳬쳭쳮쳯';
		$expects = '쳠쳡쳢쳣쳤쳥쳦쳧쳨쳩쳪쳫쳬쳭쳮쳯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쳰쳱쳲쳳쳴쳵쳶쳷쳸쳹쳺쳻쳼쳽쳾쳿';
		$expects = '쳰쳱쳲쳳쳴쳵쳶쳷쳸쳹쳺쳻쳼쳽쳾쳿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '촀촁촂촃촄촅촆촇초촉촊촋촌촍촎촏';
		$expects = '촀촁촂촃촄촅촆촇초촉촊촋촌촍촎촏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '촐촑촒촓촔촕촖촗촘촙촚촛촜총촞촟';
		$expects = '촐촑촒촓촔촕촖촗촘촙촚촛촜총촞촟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '촠촡촢촣촤촥촦촧촨촩촪촫촬촭촮촯';
		$expects = '촠촡촢촣촤촥촦촧촨촩촪촫촬촭촮촯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '촰촱촲촳촴촵촶촷촸촹촺촻촼촽촾촿';
		$expects = '촰촱촲촳촴촵촶촷촸촹촺촻촼촽촾촿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쵀쵁쵂쵃쵄쵅쵆쵇쵈쵉쵊쵋쵌쵍쵎쵏';
		$expects = '쵀쵁쵂쵃쵄쵅쵆쵇쵈쵉쵊쵋쵌쵍쵎쵏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쵐쵑쵒쵓쵔쵕쵖쵗쵘쵙쵚쵛최쵝쵞쵟';
		$expects = '쵐쵑쵒쵓쵔쵕쵖쵗쵘쵙쵚쵛최쵝쵞쵟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쵠쵡쵢쵣쵤쵥쵦쵧쵨쵩쵪쵫쵬쵭쵮쵯';
		$expects = '쵠쵡쵢쵣쵤쵥쵦쵧쵨쵩쵪쵫쵬쵭쵮쵯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쵰쵱쵲쵳쵴쵵쵶쵷쵸쵹쵺쵻쵼쵽쵾쵿';
		$expects = '쵰쵱쵲쵳쵴쵵쵶쵷쵸쵹쵺쵻쵼쵽쵾쵿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '춀춁춂춃춄춅춆춇춈춉춊춋춌춍춎춏';
		$expects = '춀춁춂춃춄춅춆춇춈춉춊춋춌춍춎춏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '춐춑춒춓추축춖춗춘춙춚춛출춝춞춟';
		$expects = '춐춑춒춓추축춖춗춘춙춚춛출춝춞춟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '춠춡춢춣춤춥춦춧춨충춪춫춬춭춮춯';
		$expects = '춠춡춢춣춤춥춦춧춨충춪춫춬춭춮춯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '춰춱춲춳춴춵춶춷춸춹춺춻춼춽춾춿';
		$expects = '춰춱춲춳춴춵춶춷춸춹춺춻춼춽춾춿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '췀췁췂췃췄췅췆췇췈췉췊췋췌췍췎췏';
		$expects = '췀췁췂췃췄췅췆췇췈췉췊췋췌췍췎췏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '췐췑췒췓췔췕췖췗췘췙췚췛췜췝췞췟';
		$expects = '췐췑췒췓췔췕췖췗췘췙췚췛췜췝췞췟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '췠췡췢췣췤췥췦췧취췩췪췫췬췭췮췯';
		$expects = '췠췡췢췣췤췥췦췧취췩췪췫췬췭췮췯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '췰췱췲췳췴췵췶췷췸췹췺췻췼췽췾췿';
		$expects = '췰췱췲췳췴췵췶췷췸췹췺췻췼췽췾췿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '츀츁츂츃츄츅츆츇츈츉츊츋츌츍츎츏';
		$expects = '츀츁츂츃츄츅츆츇츈츉츊츋츌츍츎츏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '츐츑츒츓츔츕츖츗츘츙츚츛츜츝츞츟';
		$expects = '츐츑츒츓츔츕츖츗츘츙츚츛츜츝츞츟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '츠측츢츣츤츥츦츧츨츩츪츫츬츭츮츯';
		$expects = '츠측츢츣츤츥츦츧츨츩츪츫츬츭츮츯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '츰츱츲츳츴층츶츷츸츹츺츻츼츽츾츿';
		$expects = '츰츱츲츳츴층츶츷츸츹츺츻츼츽츾츿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '칀칁칂칃칄칅칆칇칈칉칊칋칌칍칎칏';
		$expects = '칀칁칂칃칄칅칆칇칈칉칊칋칌칍칎칏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '칐칑칒칓칔칕칖칗치칙칚칛친칝칞칟';
		$expects = '칐칑칒칓칔칕칖칗치칙칚칛친칝칞칟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '칠칡칢칣칤칥칦칧침칩칪칫칬칭칮칯';
		$expects = '칠칡칢칣칤칥칦칧침칩칪칫칬칭칮칯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '칰칱칲칳카칵칶칷칸칹칺칻칼칽칾칿';
		$expects = '칰칱칲칳카칵칶칷칸칹칺칻칼칽칾칿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '캀캁캂캃캄캅캆캇캈캉캊캋캌캍캎캏';
		$expects = '캀캁캂캃캄캅캆캇캈캉캊캋캌캍캎캏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '캐캑캒캓캔캕캖캗캘캙캚캛캜캝캞캟';
		$expects = '캐캑캒캓캔캕캖캗캘캙캚캛캜캝캞캟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '캠캡캢캣캤캥캦캧캨캩캪캫캬캭캮캯';
		$expects = '캠캡캢캣캤캥캦캧캨캩캪캫캬캭캮캯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '캰캱캲캳캴캵캶캷캸캹캺캻캼캽캾캿';
		$expects = '캰캱캲캳캴캵캶캷캸캹캺캻캼캽캾캿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '컀컁컂컃컄컅컆컇컈컉컊컋컌컍컎컏';
		$expects = '컀컁컂컃컄컅컆컇컈컉컊컋컌컍컎컏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '컐컑컒컓컔컕컖컗컘컙컚컛컜컝컞컟';
		$expects = '컐컑컒컓컔컕컖컗컘컙컚컛컜컝컞컟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '컠컡컢컣커컥컦컧컨컩컪컫컬컭컮컯';
		$expects = '컠컡컢컣커컥컦컧컨컩컪컫컬컭컮컯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '컰컱컲컳컴컵컶컷컸컹컺컻컼컽컾컿';
		$expects = '컰컱컲컳컴컵컶컷컸컹컺컻컼컽컾컿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '케켁켂켃켄켅켆켇켈켉켊켋켌켍켎켏';
		$expects = '케켁켂켃켄켅켆켇켈켉켊켋켌켍켎켏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '켐켑켒켓켔켕켖켗켘켙켚켛켜켝켞켟';
		$expects = '켐켑켒켓켔켕켖켗켘켙켚켛켜켝켞켟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '켠켡켢켣켤켥켦켧켨켩켪켫켬켭켮켯';
		$expects = '켠켡켢켣켤켥켦켧켨켩켪켫켬켭켮켯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '켰켱켲켳켴켵켶켷켸켹켺켻켼켽켾켿';
		$expects = '켰켱켲켳켴켵켶켷켸켹켺켻켼켽켾켿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '콀콁콂콃콄콅콆콇콈콉콊콋콌콍콎콏';
		$expects = '콀콁콂콃콄콅콆콇콈콉콊콋콌콍콎콏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '콐콑콒콓코콕콖콗콘콙콚콛콜콝콞콟';
		$expects = '콐콑콒콓코콕콖콗콘콙콚콛콜콝콞콟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '콠콡콢콣콤콥콦콧콨콩콪콫콬콭콮콯';
		$expects = '콠콡콢콣콤콥콦콧콨콩콪콫콬콭콮콯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '콰콱콲콳콴콵콶콷콸콹콺콻콼콽콾콿';
		$expects = '콰콱콲콳콴콵콶콷콸콹콺콻콼콽콾콿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쾀쾁쾂쾃쾄쾅쾆쾇쾈쾉쾊쾋쾌쾍쾎쾏';
		$expects = '쾀쾁쾂쾃쾄쾅쾆쾇쾈쾉쾊쾋쾌쾍쾎쾏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쾐쾑쾒쾓쾔쾕쾖쾗쾘쾙쾚쾛쾜쾝쾞쾟';
		$expects = '쾐쾑쾒쾓쾔쾕쾖쾗쾘쾙쾚쾛쾜쾝쾞쾟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쾠쾡쾢쾣쾤쾥쾦쾧쾨쾩쾪쾫쾬쾭쾮쾯';
		$expects = '쾠쾡쾢쾣쾤쾥쾦쾧쾨쾩쾪쾫쾬쾭쾮쾯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쾰쾱쾲쾳쾴쾵쾶쾷쾸쾹쾺쾻쾼쾽쾾쾿';
		$expects = '쾰쾱쾲쾳쾴쾵쾶쾷쾸쾹쾺쾻쾼쾽쾾쾿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쿀쿁쿂쿃쿄쿅쿆쿇쿈쿉쿊쿋쿌쿍쿎쿏';
		$expects = '쿀쿁쿂쿃쿄쿅쿆쿇쿈쿉쿊쿋쿌쿍쿎쿏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쿐쿑쿒쿓쿔쿕쿖쿗쿘쿙쿚쿛쿜쿝쿞쿟';
		$expects = '쿐쿑쿒쿓쿔쿕쿖쿗쿘쿙쿚쿛쿜쿝쿞쿟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쿠쿡쿢쿣쿤쿥쿦쿧쿨쿩쿪쿫쿬쿭쿮쿯';
		$expects = '쿠쿡쿢쿣쿤쿥쿦쿧쿨쿩쿪쿫쿬쿭쿮쿯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '쿰쿱쿲쿳쿴쿵쿶쿷쿸쿹쿺쿻쿼쿽쿾쿿';
		$expects = '쿰쿱쿲쿳쿴쿵쿶쿷쿸쿹쿺쿻쿼쿽쿾쿿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestSectiond method
	 *
	 * Testing characters d000 - dfff
	 *
	 * @return void
	 * @return void
	 */
	public function testSectiond() {
		$string = '퀀퀁퀂퀃퀄퀅퀆퀇퀈퀉퀊퀋퀌퀍퀎퀏';
		$expects = '퀀퀁퀂퀃퀄퀅퀆퀇퀈퀉퀊퀋퀌퀍퀎퀏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퀐퀑퀒퀓퀔퀕퀖퀗퀘퀙퀚퀛퀜퀝퀞퀟';
		$expects = '퀐퀑퀒퀓퀔퀕퀖퀗퀘퀙퀚퀛퀜퀝퀞퀟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퀠퀡퀢퀣퀤퀥퀦퀧퀨퀩퀪퀫퀬퀭퀮퀯';
		$expects = '퀠퀡퀢퀣퀤퀥퀦퀧퀨퀩퀪퀫퀬퀭퀮퀯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퀰퀱퀲퀳퀴퀵퀶퀷퀸퀹퀺퀻퀼퀽퀾퀿';
		$expects = '퀰퀱퀲퀳퀴퀵퀶퀷퀸퀹퀺퀻퀼퀽퀾퀿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '큀큁큂큃큄큅큆큇큈큉큊큋큌큍큎큏';
		$expects = '큀큁큂큃큄큅큆큇큈큉큊큋큌큍큎큏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '큐큑큒큓큔큕큖큗큘큙큚큛큜큝큞큟';
		$expects = '큐큑큒큓큔큕큖큗큘큙큚큛큜큝큞큟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '큠큡큢큣큤큥큦큧큨큩큪큫크큭큮큯';
		$expects = '큠큡큢큣큤큥큦큧큨큩큪큫크큭큮큯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '큰큱큲큳클큵큶큷큸큹큺큻큼큽큾큿';
		$expects = '큰큱큲큳클큵큶큷큸큹큺큻큼큽큾큿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '킀킁킂킃킄킅킆킇킈킉킊킋킌킍킎킏';
		$expects = '킀킁킂킃킄킅킆킇킈킉킊킋킌킍킎킏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '킐킑킒킓킔킕킖킗킘킙킚킛킜킝킞킟';
		$expects = '킐킑킒킓킔킕킖킗킘킙킚킛킜킝킞킟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '킠킡킢킣키킥킦킧킨킩킪킫킬킭킮킯';
		$expects = '킠킡킢킣키킥킦킧킨킩킪킫킬킭킮킯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '킰킱킲킳킴킵킶킷킸킹킺킻킼킽킾킿';
		$expects = '킰킱킲킳킴킵킶킷킸킹킺킻킼킽킾킿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '타탁탂탃탄탅탆탇탈탉탊탋탌탍탎탏';
		$expects = '타탁탂탃탄탅탆탇탈탉탊탋탌탍탎탏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '탐탑탒탓탔탕탖탗탘탙탚탛태택탞탟';
		$expects = '탐탑탒탓탔탕탖탗탘탙탚탛태택탞탟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '탠탡탢탣탤탥탦탧탨탩탪탫탬탭탮탯';
		$expects = '탠탡탢탣탤탥탦탧탨탩탪탫탬탭탮탯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '탰탱탲탳탴탵탶탷탸탹탺탻탼탽탾탿';
		$expects = '탰탱탲탳탴탵탶탷탸탹탺탻탼탽탾탿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '턀턁턂턃턄턅턆턇턈턉턊턋턌턍턎턏';
		$expects = '턀턁턂턃턄턅턆턇턈턉턊턋턌턍턎턏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '턐턑턒턓턔턕턖턗턘턙턚턛턜턝턞턟';
		$expects = '턐턑턒턓턔턕턖턗턘턙턚턛턜턝턞턟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '턠턡턢턣턤턥턦턧턨턩턪턫턬턭턮턯';
		$expects = '턠턡턢턣턤턥턦턧턨턩턪턫턬턭턮턯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '터턱턲턳턴턵턶턷털턹턺턻턼턽턾턿';
		$expects = '터턱턲턳턴턵턶턷털턹턺턻턼턽턾턿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '텀텁텂텃텄텅텆텇텈텉텊텋테텍텎텏';
		$expects = '텀텁텂텃텄텅텆텇텈텉텊텋테텍텎텏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '텐텑텒텓텔텕텖텗텘텙텚텛템텝텞텟';
		$expects = '텐텑텒텓텔텕텖텗텘텙텚텛템텝텞텟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '텠텡텢텣텤텥텦텧텨텩텪텫텬텭텮텯';
		$expects = '텠텡텢텣텤텥텦텧텨텩텪텫텬텭텮텯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '텰텱텲텳텴텵텶텷텸텹텺텻텼텽텾텿';
		$expects = '텰텱텲텳텴텵텶텷텸텹텺텻텼텽텾텿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '톀톁톂톃톄톅톆톇톈톉톊톋톌톍톎톏';
		$expects = '톀톁톂톃톄톅톆톇톈톉톊톋톌톍톎톏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '톐톑톒톓톔톕톖톗톘톙톚톛톜톝톞톟';
		$expects = '톐톑톒톓톔톕톖톗톘톙톚톛톜톝톞톟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '토톡톢톣톤톥톦톧톨톩톪톫톬톭톮톯';
		$expects = '토톡톢톣톤톥톦톧톨톩톪톫톬톭톮톯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '톰톱톲톳톴통톶톷톸톹톺톻톼톽톾톿';
		$expects = '톰톱톲톳톴통톶톷톸톹톺톻톼톽톾톿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퇀퇁퇂퇃퇄퇅퇆퇇퇈퇉퇊퇋퇌퇍퇎퇏';
		$expects = '퇀퇁퇂퇃퇄퇅퇆퇇퇈퇉퇊퇋퇌퇍퇎퇏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퇐퇑퇒퇓퇔퇕퇖퇗퇘퇙퇚퇛퇜퇝퇞퇟';
		$expects = '퇐퇑퇒퇓퇔퇕퇖퇗퇘퇙퇚퇛퇜퇝퇞퇟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퇠퇡퇢퇣퇤퇥퇦퇧퇨퇩퇪퇫퇬퇭퇮퇯';
		$expects = '퇠퇡퇢퇣퇤퇥퇦퇧퇨퇩퇪퇫퇬퇭퇮퇯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퇰퇱퇲퇳퇴퇵퇶퇷퇸퇹퇺퇻퇼퇽퇾퇿';
		$expects = '퇰퇱퇲퇳퇴퇵퇶퇷퇸퇹퇺퇻퇼퇽퇾퇿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '툀툁툂툃툄툅툆툇툈툉툊툋툌툍툎툏';
		$expects = '툀툁툂툃툄툅툆툇툈툉툊툋툌툍툎툏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '툐툑툒툓툔툕툖툗툘툙툚툛툜툝툞툟';
		$expects = '툐툑툒툓툔툕툖툗툘툙툚툛툜툝툞툟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '툠툡툢툣툤툥툦툧툨툩툪툫투툭툮툯';
		$expects = '툠툡툢툣툤툥툦툧툨툩툪툫투툭툮툯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '툰툱툲툳툴툵툶툷툸툹툺툻툼툽툾툿';
		$expects = '툰툱툲툳툴툵툶툷툸툹툺툻툼툽툾툿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퉀퉁퉂퉃퉄퉅퉆퉇퉈퉉퉊퉋퉌퉍퉎퉏';
		$expects = '퉀퉁퉂퉃퉄퉅퉆퉇퉈퉉퉊퉋퉌퉍퉎퉏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퉐퉑퉒퉓퉔퉕퉖퉗퉘퉙퉚퉛퉜퉝퉞퉟';
		$expects = '퉐퉑퉒퉓퉔퉕퉖퉗퉘퉙퉚퉛퉜퉝퉞퉟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퉠퉡퉢퉣퉤퉥퉦퉧퉨퉩퉪퉫퉬퉭퉮퉯';
		$expects = '퉠퉡퉢퉣퉤퉥퉦퉧퉨퉩퉪퉫퉬퉭퉮퉯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퉰퉱퉲퉳퉴퉵퉶퉷퉸퉹퉺퉻퉼퉽퉾퉿';
		$expects = '퉰퉱퉲퉳퉴퉵퉶퉷퉸퉹퉺퉻퉼퉽퉾퉿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '튀튁튂튃튄튅튆튇튈튉튊튋튌튍튎튏';
		$expects = '튀튁튂튃튄튅튆튇튈튉튊튋튌튍튎튏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '튐튑튒튓튔튕튖튗튘튙튚튛튜튝튞튟';
		$expects = '튐튑튒튓튔튕튖튗튘튙튚튛튜튝튞튟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '튠튡튢튣튤튥튦튧튨튩튪튫튬튭튮튯';
		$expects = '튠튡튢튣튤튥튦튧튨튩튪튫튬튭튮튯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '튰튱튲튳튴튵튶튷트특튺튻튼튽튾튿';
		$expects = '튰튱튲튳튴튵튶튷트특튺튻튼튽튾튿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '틀틁틂틃틄틅틆틇틈틉틊틋틌틍틎틏';
		$expects = '틀틁틂틃틄틅틆틇틈틉틊틋틌틍틎틏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '틐틑틒틓틔틕틖틗틘틙틚틛틜틝틞틟';
		$expects = '틐틑틒틓틔틕틖틗틘틙틚틛틜틝틞틟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '틠틡틢틣틤틥틦틧틨틩틪틫틬틭틮틯';
		$expects = '틠틡틢틣틤틥틦틧틨틩틪틫틬틭틮틯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '티틱틲틳틴틵틶틷틸틹틺틻틼틽틾틿';
		$expects = '티틱틲틳틴틵틶틷틸틹틺틻틼틽틾틿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '팀팁팂팃팄팅팆팇팈팉팊팋파팍팎팏';
		$expects = '팀팁팂팃팄팅팆팇팈팉팊팋파팍팎팏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '판팑팒팓팔팕팖팗팘팙팚팛팜팝팞팟';
		$expects = '판팑팒팓팔팕팖팗팘팙팚팛팜팝팞팟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '팠팡팢팣팤팥팦팧패팩팪팫팬팭팮팯';
		$expects = '팠팡팢팣팤팥팦팧패팩팪팫팬팭팮팯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '팰팱팲팳팴팵팶팷팸팹팺팻팼팽팾팿';
		$expects = '팰팱팲팳팴팵팶팷팸팹팺팻팼팽팾팿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퍀퍁퍂퍃퍄퍅퍆퍇퍈퍉퍊퍋퍌퍍퍎퍏';
		$expects = '퍀퍁퍂퍃퍄퍅퍆퍇퍈퍉퍊퍋퍌퍍퍎퍏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퍐퍑퍒퍓퍔퍕퍖퍗퍘퍙퍚퍛퍜퍝퍞퍟';
		$expects = '퍐퍑퍒퍓퍔퍕퍖퍗퍘퍙퍚퍛퍜퍝퍞퍟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퍠퍡퍢퍣퍤퍥퍦퍧퍨퍩퍪퍫퍬퍭퍮퍯';
		$expects = '퍠퍡퍢퍣퍤퍥퍦퍧퍨퍩퍪퍫퍬퍭퍮퍯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퍰퍱퍲퍳퍴퍵퍶퍷퍸퍹퍺퍻퍼퍽퍾퍿';
		$expects = '퍰퍱퍲퍳퍴퍵퍶퍷퍸퍹퍺퍻퍼퍽퍾퍿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '펀펁펂펃펄펅펆펇펈펉펊펋펌펍펎펏';
		$expects = '펀펁펂펃펄펅펆펇펈펉펊펋펌펍펎펏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '펐펑펒펓펔펕펖펗페펙펚펛펜펝펞펟';
		$expects = '펐펑펒펓펔펕펖펗페펙펚펛펜펝펞펟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '펠펡펢펣펤펥펦펧펨펩펪펫펬펭펮펯';
		$expects = '펠펡펢펣펤펥펦펧펨펩펪펫펬펭펮펯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '펰펱펲펳펴펵펶펷편펹펺펻펼펽펾펿';
		$expects = '펰펱펲펳펴펵펶펷편펹펺펻펼펽펾펿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '폀폁폂폃폄폅폆폇폈평폊폋폌폍폎폏';
		$expects = '폀폁폂폃폄폅폆폇폈평폊폋폌폍폎폏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '폐폑폒폓폔폕폖폗폘폙폚폛폜폝폞폟';
		$expects = '폐폑폒폓폔폕폖폗폘폙폚폛폜폝폞폟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '폠폡폢폣폤폥폦폧폨폩폪폫포폭폮폯';
		$expects = '폠폡폢폣폤폥폦폧폨폩폪폫포폭폮폯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '폰폱폲폳폴폵폶폷폸폹폺폻폼폽폾폿';
		$expects = '폰폱폲폳폴폵폶폷폸폹폺폻폼폽폾폿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퐀퐁퐂퐃퐄퐅퐆퐇퐈퐉퐊퐋퐌퐍퐎퐏';
		$expects = '퐀퐁퐂퐃퐄퐅퐆퐇퐈퐉퐊퐋퐌퐍퐎퐏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퐐퐑퐒퐓퐔퐕퐖퐗퐘퐙퐚퐛퐜퐝퐞퐟';
		$expects = '퐐퐑퐒퐓퐔퐕퐖퐗퐘퐙퐚퐛퐜퐝퐞퐟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퐠퐡퐢퐣퐤퐥퐦퐧퐨퐩퐪퐫퐬퐭퐮퐯';
		$expects = '퐠퐡퐢퐣퐤퐥퐦퐧퐨퐩퐪퐫퐬퐭퐮퐯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퐰퐱퐲퐳퐴퐵퐶퐷퐸퐹퐺퐻퐼퐽퐾퐿';
		$expects = '퐰퐱퐲퐳퐴퐵퐶퐷퐸퐹퐺퐻퐼퐽퐾퐿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '푀푁푂푃푄푅푆푇푈푉푊푋푌푍푎푏';
		$expects = '푀푁푂푃푄푅푆푇푈푉푊푋푌푍푎푏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '푐푑푒푓푔푕푖푗푘푙푚푛표푝푞푟';
		$expects = '푐푑푒푓푔푕푖푗푘푙푚푛표푝푞푟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '푠푡푢푣푤푥푦푧푨푩푪푫푬푭푮푯';
		$expects = '푠푡푢푣푤푥푦푧푨푩푪푫푬푭푮푯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '푰푱푲푳푴푵푶푷푸푹푺푻푼푽푾푿';
		$expects = '푰푱푲푳푴푵푶푷푸푹푺푻푼푽푾푿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '풀풁풂풃풄풅풆풇품풉풊풋풌풍풎풏';
		$expects = '풀풁풂풃풄풅풆풇품풉풊풋풌풍풎풏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '풐풑풒풓풔풕풖풗풘풙풚풛풜풝풞풟';
		$expects = '풐풑풒풓풔풕풖풗풘풙풚풛풜풝풞풟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '풠풡풢풣풤풥풦풧풨풩풪풫풬풭풮풯';
		$expects = '풠풡풢풣풤풥풦풧풨풩풪풫풬풭풮풯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '풰풱풲풳풴풵풶풷풸풹풺풻풼풽풾풿';
		$expects = '풰풱풲풳풴풵풶풷풸풹풺풻풼풽풾풿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퓀퓁퓂퓃퓄퓅퓆퓇퓈퓉퓊퓋퓌퓍퓎퓏';
		$expects = '퓀퓁퓂퓃퓄퓅퓆퓇퓈퓉퓊퓋퓌퓍퓎퓏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퓐퓑퓒퓓퓔퓕퓖퓗퓘퓙퓚퓛퓜퓝퓞퓟';
		$expects = '퓐퓑퓒퓓퓔퓕퓖퓗퓘퓙퓚퓛퓜퓝퓞퓟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퓠퓡퓢퓣퓤퓥퓦퓧퓨퓩퓪퓫퓬퓭퓮퓯';
		$expects = '퓠퓡퓢퓣퓤퓥퓦퓧퓨퓩퓪퓫퓬퓭퓮퓯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '퓰퓱퓲퓳퓴퓵퓶퓷퓸퓹퓺퓻퓼퓽퓾퓿';
		$expects = '퓰퓱퓲퓳퓴퓵퓶퓷퓸퓹퓺퓻퓼퓽퓾퓿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '픀픁픂픃프픅픆픇픈픉픊픋플픍픎픏';
		$expects = '픀픁픂픃프픅픆픇픈픉픊픋플픍픎픏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '픐픑픒픓픔픕픖픗픘픙픚픛픜픝픞픟';
		$expects = '픐픑픒픓픔픕픖픗픘픙픚픛픜픝픞픟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '픠픡픢픣픤픥픦픧픨픩픪픫픬픭픮픯';
		$expects = '픠픡픢픣픤픥픦픧픨픩픪픫픬픭픮픯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '픰픱픲픳픴픵픶픷픸픹픺픻피픽픾픿';
		$expects = '픰픱픲픳픴픵픶픷픸픹픺픻피픽픾픿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '핀핁핂핃필핅핆핇핈핉핊핋핌핍핎핏';
		$expects = '핀핁핂핃필핅핆핇핈핉핊핋핌핍핎핏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '핐핑핒핓핔핕핖핗하학핚핛한핝핞핟';
		$expects = '핐핑핒핓핔핕핖핗하학핚핛한핝핞핟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '할핡핢핣핤핥핦핧함합핪핫핬항핮핯';
		$expects = '할핡핢핣핤핥핦핧함합핪핫핬항핮핯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '핰핱핲핳해핵핶핷핸핹핺핻핼핽핾핿';
		$expects = '핰핱핲핳해핵핶핷핸핹핺핻핼핽핾핿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '햀햁햂햃햄햅햆햇했행햊햋햌햍햎햏';
		$expects = '햀햁햂햃햄햅햆햇했행햊햋햌햍햎햏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '햐햑햒햓햔햕햖햗햘햙햚햛햜햝햞햟';
		$expects = '햐햑햒햓햔햕햖햗햘햙햚햛햜햝햞햟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '햠햡햢햣햤향햦햧햨햩햪햫햬햭햮햯';
		$expects = '햠햡햢햣햤향햦햧햨햩햪햫햬햭햮햯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '햰햱햲햳햴햵햶햷햸햹햺햻햼햽햾햿';
		$expects = '햰햱햲햳햴햵햶햷햸햹햺햻햼햽햾햿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '헀헁헂헃헄헅헆헇허헉헊헋헌헍헎헏';
		$expects = '헀헁헂헃헄헅헆헇허헉헊헋헌헍헎헏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '헐헑헒헓헔헕헖헗험헙헚헛헜헝헞헟';
		$expects = '헐헑헒헓헔헕헖헗험헙헚헛헜헝헞헟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '헠헡헢헣헤헥헦헧헨헩헪헫헬헭헮헯';
		$expects = '헠헡헢헣헤헥헦헧헨헩헪헫헬헭헮헯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '헰헱헲헳헴헵헶헷헸헹헺헻헼헽헾헿';
		$expects = '헰헱헲헳헴헵헶헷헸헹헺헻헼헽헾헿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '혀혁혂혃현혅혆혇혈혉혊혋혌혍혎혏';
		$expects = '혀혁혂혃현혅혆혇혈혉혊혋혌혍혎혏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '혐협혒혓혔형혖혗혘혙혚혛혜혝혞혟';
		$expects = '혐협혒혓혔형혖혗혘혙혚혛혜혝혞혟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '혠혡혢혣혤혥혦혧혨혩혪혫혬혭혮혯';
		$expects = '혠혡혢혣혤혥혦혧혨혩혪혫혬혭혮혯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '혰혱혲혳혴혵혶혷호혹혺혻혼혽혾혿';
		$expects = '혰혱혲혳혴혵혶혷호혹혺혻혼혽혾혿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '홀홁홂홃홄홅홆홇홈홉홊홋홌홍홎홏';
		$expects = '홀홁홂홃홄홅홆홇홈홉홊홋홌홍홎홏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '홐홑홒홓화확홖홗환홙홚홛활홝홞홟';
		$expects = '홐홑홒홓화확홖홗환홙홚홛활홝홞홟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '홠홡홢홣홤홥홦홧홨황홪홫홬홭홮홯';
		$expects = '홠홡홢홣홤홥홦홧홨황홪홫홬홭홮홯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '홰홱홲홳홴홵홶홷홸홹홺홻홼홽홾홿';
		$expects = '홰홱홲홳홴홵홶홷홸홹홺홻홼홽홾홿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '횀횁횂횃횄횅횆횇횈횉횊횋회획횎횏';
		$expects = '횀횁횂횃횄횅횆횇횈횉횊횋회획횎횏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '횐횑횒횓횔횕횖횗횘횙횚횛횜횝횞횟';
		$expects = '횐횑횒횓횔횕횖횗횘횙횚횛횜횝횞횟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '횠횡횢횣횤횥횦횧효횩횪횫횬횭횮횯';
		$expects = '횠횡횢횣횤횥횦횧효횩횪횫횬횭횮횯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '횰횱횲횳횴횵횶횷횸횹횺횻횼횽횾횿';
		$expects = '횰횱횲횳횴횵횶횷횸횹횺횻횼횽횾횿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '훀훁훂훃후훅훆훇훈훉훊훋훌훍훎훏';
		$expects = '훀훁훂훃후훅훆훇훈훉훊훋훌훍훎훏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '훐훑훒훓훔훕훖훗훘훙훚훛훜훝훞훟';
		$expects = '훐훑훒훓훔훕훖훗훘훙훚훛훜훝훞훟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '훠훡훢훣훤훥훦훧훨훩훪훫훬훭훮훯';
		$expects = '훠훡훢훣훤훥훦훧훨훩훪훫훬훭훮훯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '훰훱훲훳훴훵훶훷훸훹훺훻훼훽훾훿';
		$expects = '훰훱훲훳훴훵훶훷훸훹훺훻훼훽훾훿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '휀휁휂휃휄휅휆휇휈휉휊휋휌휍휎휏';
		$expects = '휀휁휂휃휄휅휆휇휈휉휊휋휌휍휎휏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '휐휑휒휓휔휕휖휗휘휙휚휛휜휝휞휟';
		$expects = '휐휑휒휓휔휕휖휗휘휙휚휛휜휝휞휟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '휠휡휢휣휤휥휦휧휨휩휪휫휬휭휮휯';
		$expects = '휠휡휢휣휤휥휦휧휨휩휪휫휬휭휮휯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '휰휱휲휳휴휵휶휷휸휹휺휻휼휽휾휿';
		$expects = '휰휱휲휳휴휵휶휷휸휹휺휻휼휽휾휿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '흀흁흂흃흄흅흆흇흈흉흊흋흌흍흎흏';
		$expects = '흀흁흂흃흄흅흆흇흈흉흊흋흌흍흎흏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '흐흑흒흓흔흕흖흗흘흙흚흛흜흝흞흟';
		$expects = '흐흑흒흓흔흕흖흗흘흙흚흛흜흝흞흟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '흠흡흢흣흤흥흦흧흨흩흪흫희흭흮흯';
		$expects = '흠흡흢흣흤흥흦흧흨흩흪흫희흭흮흯';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '흰흱흲흳흴흵흶흷흸흹흺흻흼흽흾흿';
		$expects = '흰흱흲흳흴흵흶흷흸흹흺흻흼흽흾흿';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '힀힁힂힃힄힅힆힇히힉힊힋힌힍힎힏';
		$expects = '힀힁힂힃힄힅힆힇히힉힊힋힌힍힎힏';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '힐힑힒힓힔힕힖힗힘힙힚힛힜힝힞힟';
		$expects = '힐힑힒힓힔힕힖힗힘힙힚힛힜힝힞힟';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = '힠힡힢힣힤힥힦힧힨힩힪힫힬힭힮힯';
		$expects = '힠힡힢힣------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ힰힱힲힳힴힵힶힷힸힹힺힻힼힽힾힿ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ퟀퟁퟂퟃퟄퟅퟆ퟇퟈퟉퟊ퟋퟌퟍퟎퟏ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ퟐퟑퟒퟓퟔퟕퟖퟗퟘퟙퟚퟛퟜퟝퟞퟟ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ퟠퟡퟢퟣퟤퟥퟦퟧퟨퟩퟪퟫퟬퟭퟮퟯ';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);

		$string = 'ퟰퟱퟲퟳퟴퟵퟶퟷퟸퟹퟺퟻ퟼퟽퟾퟿';
		$expects = '----------------';
		$result = $this->Model->slug($string, false);
		$this->assertEquals($expects, $result);
	}

	/**
	 * Test Url method
	 *
	 * @return void
	 */
	public function testUrlMode() {
		$this->Model->Behaviors->load('Slugged', array('mode' => 'url', 'replace' => false));
		$string = 'standard string';
		$expects = 'standard-string';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a \' in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a " in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a / in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a ? in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a < in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a > in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a . in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a $ in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a / in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a : in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a ; in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a ? in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a @ in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a = in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a + in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a & in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a % in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a \ in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);

		$string = 'something with a # in it';
		$expects = 'something-with-a-in-it';
		$result = $this->Model->slug($string);
		$this->assertEquals($expects, $result);
	}

	/**
	 * TestTruncateMultibyte method
	 *
	 * @return void
	 */
	/**
	 * TestTruncateMultibyte method
	 *
	 * Ensure that the first test doesn't cut a multibyte character The test string is:
	 * 	17 chars
	 * 	51 bytes UTF-8 encoded
	 * 	34 bytes SJIS encoded
	 * Ensure that it'll still work with encodings which aren't UTF-8 - note this file is UTF-8
	 *
	 * @return void
	 */
	public function testTruncateMultibyte() {
		$testString = 'モデルのデータベースとデータソース';
		$encoding = Configure::read('App.encoding');
		Configure::write('App.encoding', 'UTF-8');

		$this->Model->Behaviors->load('Slugged', array('length' => 50));
		$result = $this->Model->slug('モデルのデータベースとデータソース');
		$expects = 'モデルのデータベースとデータソー';
		$this->assertEquals($expects, $result);

		Configure::write('App.encoding', 'SJIS');
		$sjisEncoded = mb_convert_encoding($testString, 'SJIS', 'UTF-8');

		$this->Model->Behaviors->load('Slugged', array('length' => 33));
		$result = $this->Model->slug($sjisEncoded);
		$sjisExpects = mb_convert_encoding('モデルのデータベースとデータソー', 'SJIS', 'UTF-8');
		$this->assertEquals($result, $sjisExpects);

		$this->Model->Behaviors->load('Slugged', array('length' => 50, 'encoding' => 'UTF-8'));
		$result = $this->Model->slug($sjisEncoded);
		$expects = 'モデルのデータベースとデータソー';
		$this->assertEquals($expects, $result);

		Configure::write('App,encoding', $encoding);
	}

	/**
	 * TestDuplicateWithLengthRestriction method
	 *
	 * If there's a length restriction - ensure it's respected by the unique slug routine
	 *
	 * @return void
	 */
	public function testDuplicateWithLengthRestriction() {
		$this->Model->Behaviors->load('Slugged', array('label' => 'name', 'length' => 10, 'unique' => true));

		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawson'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawsom'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawsoo'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawso3'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawso4'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawso5'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawso6'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawso7'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawso8'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawso9'));
		$this->Model->create();
		$this->Model->save(array('name' => 'Andy Dawso0'));

		$result = $this->Model->find('list', array(
			'conditions' => array('name LIKE' => 'Andy Daw%'),
			'fields' => array('name', 'slug'),
			'order' => 'name'
		));
		$expects = array(
			'Andy Dawson' => 'Andy-Dawso',
			'Andy Dawsom' => 'Andy-Daw-1',
			'Andy Dawsoo' => 'Andy-Daw-2',
			'Andy Dawso3' => 'Andy-Daw-3',
			'Andy Dawso4' => 'Andy-Daw-4',
			'Andy Dawso5' => 'Andy-Daw-5',
			'Andy Dawso6' => 'Andy-Daw-6',
			'Andy Dawso7' => 'Andy-Daw-7',
			'Andy Dawso8' => 'Andy-Daw-8',
			'Andy Dawso9' => 'Andy-Daw-9',
			'Andy Dawso0' => 'Andy-Da-10'
		);
		$this->assertEquals($expects, $result);
	}

}

/**
 * MessageSlugged class
 *
 * @uses CakeTestModel
 */
class MessageSlugged extends CakeTestModel {

	/**
	 * UseTable property
	 *
	 * @var string 'messages'
	 */
	public $useTable = 'messages';

	/**
	 * ActsAs property
	 *
	 * @var array
	 */
	public $actsAs = array('Tools.Slugged' => array(
		'mode' => 'id',
		'replace' => false
	));

}
