<?php

namespace Tools\Controller\Admin;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use IntlChar;
use Tools\Utility\Text;

/**
 * Display format helper specific debug info
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class HelperController extends AppController {

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function chars() {
		if ($this->request->is(['post', 'put'])) {
			$string = $this->request->getData('string');
			$result = $this->analyzeString($string);
			$this->set(compact('string', 'result'));
		}
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function bitmasks() {
		$Table = TableRegistry::getTableLocator()->get('Table');

		if ($this->request->is(['post', 'put'])) {
			$matrix = $this->request->getData('matrix');
			$modelClass = $this->request->getData('model');
			$fieldName = $this->request->getData('field');
			$matrixArray = explode(PHP_EOL, $matrix);

			$result = [];
			foreach ($matrixArray as $value) {
				if (!str_contains($value, ':')) {
					continue;
				}
				[$from, $to] = explode(':', $value, 2);
				$tmp = [
					'from' => Text::tokenize($from),
					'to' => Text::tokenize($to),
				];
				$result[] = $tmp;
			}
			$bits = Hash::extract($result, '{n}.from');
			if (empty($bits)) {
				//$Table->invalidate('Tools.matrix');
			} else {
				$Table->addBehavior('Tools.Bitmasked', ['bits' => $bits]);
				foreach ($result as $key => $value) {
					$result[$key]['sql'] = $this->_bitmaskUpdateSnippet($value['from'], $value['to'], $modelClass, $fieldName);
				}
			}
			$result = array_reverse($result);
			$this->set(compact('result'));
		}
	}

	/**
	 * @param string $string
	 * @return array
	 */
	protected function analyzeString(string $string): array {
		$length = mb_strlen($string);

		$result = [];
		for ($i = 0; $i < $length; $i++) {
			$char = mb_substr($string, $i, 1);
			$unicodeHex = strtoupper(bin2hex((string)mb_convert_encoding($char, 'UTF-32', 'UTF-8')));
			$codePoint = 'U+' . ltrim($unicodeHex, '0');
			$description = $this->describeChar($char);

			$result[] = [
				'index' => $i,
				'char' => $char,
				'code' => $codePoint,
				'name' => $description['name'],
				'type' => $description['type'],
			];
		}

		return $result;
	}

	// 🔍 Character description logic

	/**
	 * @param string $char
	 * @return string[]
	 */
	protected function describeChar(string $char): array {
		// Emoji ranges (simplified)
		$ord = IntlChar::ord($char);
		if ($ord >= 0x1F600 && $ord <= 0x1F64F) {
			return ['type' => 'emoji', 'name' => 'Emoticons'];
		}

		// Check common whitespace
		switch ($char) {
			case ' ':
				return ['type' => 'space', 'name' => 'Space (U+0020)'];
			case "\n":
				return ['type' => 'newline', 'name' => 'Line Feed (U+000A) "\n"'];
			case "\r":
				return ['type' => 'carriage return', 'name' => 'Carriage Return (U+000D) "\r"'];
			case "\t":
				return ['type' => 'tab', 'name' => 'Horizontal Tab (U+0009) "\t"'];
		}

		// Use PHP intl extension to get name
		$name = IntlChar::charName($char) ?: 'Unknown';
		$type = IntlChar::isalpha($char) ? 'letter' :
			(IntlChar::isdigit($char) ? 'digit' : 'symbol');

		return ['type' => $type, 'name' => $name];
	}

	/**
	 * @param mixed $from
	 * @param mixed $to
	 * @param mixed $modelClass
	 * @param mixed $fieldName
	 * @return string
	 */
	protected function _bitmaskUpdateSnippet($from, $to, $modelClass, $fieldName) {
		//[$class, $modelName] = pluginSplit($modelClass);
		$Model = TableRegistry::getTableLocator()->get($modelClass);
		$tableName = $Model->getTable(); // $dbo->fullTableName($Model);
		$fieldName = '`' . $fieldName . '`';
		$res = $fieldName;
		$conditions = [];

		$sql = [];
		foreach ($from as $value) {
			$conditions[] = $fieldName . ' & ' . $value . ' = ' . $value;
			if (in_array($value, $to)) {
				continue;
			}
			$res .= ' & ~' . $value;
		}
		$conditions = implode(' OR ', $conditions);

		foreach ($to as $value) {
			$res .= ' | ' . $value;
		}
		$sql[] = 'UPDATE ' . $tableName . ' SET ' . $fieldName . ' = ' . $res . ' WHERE ' . $conditions . ';';

		return implode(PHP_EOL, $sql);
	}

}
