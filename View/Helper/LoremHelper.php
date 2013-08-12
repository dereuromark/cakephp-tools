<?php
App::uses('AppHelper', 'View/Helper');

class LoremHelper extends AppHelper {

	public $helpers = array('Html');

	public $words = array();

	/**
	 * Return placeholder text. By default, a single html-formatted paragraph.
	 * For a brief history of "lorem ipsum", see http://en.wikipedia.org/wiki/Lorem_ipsum
	 * also, thanks http://www.lipsum.org for all the faithful placeholder
	 *
	 * @param integer $number depending on the
	 * @param string $type trigger used to switch between words only, paragraph(s), or lists (ol/ul)
	 * @param array $attributes Additional HTML attributes of the list (ol/ul) tag, or paragraph (when applicable)
	 * @param array $itemAttributes Additional HTML attributes of the list item (LI) tag (when applicable)
	 * @return string placeholder text
	 */
	public function ipsum($number = 1, $type = 'p', $attributes = array(), $itemAttributes = array()) {
		if (!$this->words) {
			$this->words = explode(' ', 'lorem ipsum dolor sit amet consectetur adipisicing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum');
		}
		switch ($type) {
			// Words
			case 'w':
			case 'words':
				$string = $this->_sentence($number, $number, false);
			break;
			// Unordered list
			case 'l':
			case 'ul':
			case 'list':
			// ordered list too!
			case 'ol':
				for ($li = 0; $li < $number; $li++) {
					$list[] = $this->_sentence();
				}
				$string = $this->Html->nestedList($list, $attributes, $itemAttributes, ($type === 'ol') ? 'ol' : 'ul');
				break;
			// everything else paragraphs
			default:
				for ($p = 0; $p < $number; $p++) {
					$paraText = '';
					$numberSentences = rand(16, 20);
					for ($s = 0; $s < $numberSentences; $s++) {
						$paraText .= $this->_sentence();
					}
					$paras[] = $this->Html->para(null, $paraText, $attributes);
				}
				$string = implode("\n", $paras);
				break;
		}
		return trim($string);
	}

	/**
	 * Internal function to return a greeked sentence
	 *
	 * @param integer $maxWords maximum number of words for this sentence
	 * @param integer $minWords minimum number of words for this sentence
	 * @param boolean $punctuation if false it will not append random commas and ending period
	 * @return string greeked sentence
	 */
	protected function _sentence($maxWords = 10, $minWords = 4, $punctuation = true) {
		$string = '';
		$numWords = rand($minWords, $maxWords);
		for ($w = 0; $w < $numWords; $w++) {
			$word = $this->words[rand(0, (count($this->words) - 1))];
			// if first word capitalize letter...
			if ($w === 0) {
				$word = ucwords($word);
			}
			$string .= $word;
			// if not the last word,
			if ($w !== ($numWords - 1)) {
				// 5% chance of a comma...
				if (rand(0, 99) < 5) {
					$string .= ', ';
				} else {
					$string .= ' ';
				}
			}
		}
		$string .= '. ';
		return $string;
	}

}
