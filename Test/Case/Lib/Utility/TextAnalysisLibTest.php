<?php
App::uses('TextAnalysisLib', 'Tools.Utility');

/**
 */
class TextAnalysisLibTest extends CakeTestCase {

	public $TextAnalysis;

	public function setUp() {
		parent::setUp();

		$this->TextAnalysis = new TextAnalysisLib();
	}

	public function testIsScreamFont() {
		$strings = array(
			'Some Äext' => false,
			'SOME ÄEXT' => true,
			'ST' => true,
			'SOme TExt' => true,
			'SOme Text' => false,
		);

		foreach ($strings as $string => $expected) {
			$this->TextAnalysis = new TextAnalysisLib($string);
			$is = $this->TextAnalysis->isScreamFont();
			//pr($is);
			$this->assertSame($expected, $is);
		}
	}

	public function testWordCount() {
		$this->TextAnalysis = new TextAnalysisLib('Hochhaus, Unter dem Bau von ae Äußeren Einflüssen - und von Autos.');
		$is = $this->TextAnalysis->wordCount(array('min_char' => 3));
		//pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 9);

		$this->TextAnalysis = new TextAnalysisLib('Hochhaus, Unter dem Bau von ae Äußeren Einflüssen - und von Autos.');
		$is = $this->TextAnalysis->wordCount(array('min_char' => 3, 'sort' => 'DESC', 'limit' => 5));
		//pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 5);
	}

/** Start **/

	public function testOccurances() {
		$this->TextAnalysis = new TextAnalysisLib('Hochhaus');
		$is = $this->TextAnalysis->occurrences();
		//pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 6);

		$is = $this->TextAnalysis->occurrences(null, true);
		//pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 7);

		$is = $this->TextAnalysis->occurrences('h');
		//pr($is);
		$expected = 3;
		$this->assertEquals($expected, $is);

		$is = $this->TextAnalysis->occurrences('h', true);
		//pr($is);
		$expected = 2;
		$this->assertEquals($expected, $is);
	}

	public function testMaxOccurances() {
		$is = $this->TextAnalysis->maxOccurrences();
		//pr($is);
		$this->assertTrue(!empty($is) && is_array($is) && count($is) === 1);

		$this->TextAnalysis = new TextAnalysisLib('Radfahren');
		$is = $this->TextAnalysis->maxOccurrences();
		//pr($is);
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
