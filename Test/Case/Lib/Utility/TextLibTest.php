<?php

App::uses('TextLib', 'Tools.Utility');

/**
 * 2010-07-14 ms
 */
class TextLibTest extends CakeTestCase {

	public function setUp() {
		$this->TextLib = new TextLib(null);
	}

	public function testScreamFont() {
		$strings = array(
			'Some Äext' => false,
			'SOME ÄEXT' => true,
			'ST' => true,
			'SOme TExt' => true,
			'SOme Text' => false,
		);

		foreach ($strings as $string => $expected) {
			$this->TextLib = new TextLib($string);
			$is = $this->TextLib->isScreamFont();
			//pr($is);
			$this->assertSame($expected, $is);
		}
	}


	public function testConvertToOrd() {
		$this->TextLib = new TextLib('h H');
		$is = $this->TextLib->convertToOrd();
		pr($is);
		$this->assertEquals($is, '0-104-32-72-0');

		$is = $this->TextLib->convertToOrd('x'.NL.'x'.LF.'x'.PHP_EOL.'x'.CR.'x'.TB.'x');
		pr($is);
	}

	public function testConvertToOrdTable() {
		$is = $this->TextLib->convertToOrdTable('x'.NL.'x'.LF.'x'.PHP_EOL.'x'.CR.'x'.TB.'x');
		pr($is);
	}


	public function testWords() {
		$this->TextLib = new TextLib('Hochhaus, Unter dem Bau von ae Äußeren Einflüssen - und von Autos.');
		$is = $this->TextLib->words(array('min_char'=>3));
		pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 9);

	}

	public function testWordCount() {
		$this->TextLib = new TextLib('Hochhaus, Unter dem Bau von ae Äußeren Einflüssen - und von Autos.');
		$is = $this->TextLib->wordCount(array('min_char'=>3));
		pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 9);


		$this->TextLib = new TextLib('Hochhaus, Unter dem Bau von ae Äußeren Einflüssen - und von Autos.');
		$is = $this->TextLib->wordCount(array('min_char'=>3, 'sort'=>'DESC', 'limit'=>5));
		pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 5);

	}

/** Start **/

	public function testOccurances() {
		$this->TextLib = new TextLib('Hochhaus');
		$is = $this->TextLib->occurrences();
		pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 6);

		$is = $this->TextLib->occurrences(null, true);
		pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 7);

		$is = $this->TextLib->occurrences('h');
		pr($is);
		$expected = 3;
		$this->assertEquals($is, $expected);

		$is = $this->TextLib->occurrences('h', true);
		pr($is);
		$expected = 2;
		$this->assertEquals($is, $expected);
	}

	public function testMaxOccurances() {
		$is = $this->TextLib->maxOccurrences();
		pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 1);

		$this->TextLib = new TextLib('Radfahren');
		$is = $this->TextLib->maxOccurrences();
		pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 2);

	}

	//TODO: rest of the test functions


/*
//give it the text
$text = new TextParse(
"PHP:Hypertext Preprocessor is a widely used, general-purpose scripting language that was originally designed for web development to produce dynamic web pages... .. .. For this purpose,PHP code is embedded into the HTML source document and interpreted by a web server with a PHP processor module, which generates the web page document.




As a general-purpose programming language, PHP code(PHP CODE)is processed by an interpreter application in command-line mode performing desired operating system operations and producing program output on its standard output channel.It may also function as a graphical application...... PHP is available as a processor for most modern web servers and as standalone interpreter on most operating systems and computing platforms."
);

echo "Lenght:".		$text->getLenght()		."\n";	//the Lenght

echo "Character:".	$text->getCharacter()	."\n";	//Character count

echo "Letter:".		$text->getLetter()		."\n";	//Letter count

echo "Space:".		$text->getSpace()		."\n";	//Space count

echo "Symbol:".		$text->getSymbol()		."\n";	//Symbol count(non letter / space / \n / \r)

echo "Word:".		$text->getWord()		."\n";	//Word count
echo "The Words:";
print_r($text->getWord(1));	//the Words
echo "\n";

echo "Sentence:".		$text->getSentence()		."\n";	//Sentence count
echo "The Sentences:";
print_r($text->getSentence(1));	//the Sentences
echo "\n";

echo "Paragraph:".		$text->getParagraph()		."\n";	//Paragraph count
echo "The Paragraphs:";
print_r($text->getParagraph(1));	//the Paragraphs
echo "\n";

echo "Beautified Text:\n".	$text->beautify(80);	//beautify the text, wordwrap=80
*/

}

