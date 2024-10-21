<?php

namespace Tools\Controller\Admin;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
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
