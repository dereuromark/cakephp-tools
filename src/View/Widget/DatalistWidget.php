<?php

namespace Tools\View\Widget;

use Cake\Utility\Text;
use Cake\View\Form\ContextInterface;
use Cake\View\Widget\SelectBoxWidget;

/**
 * Datalist widget
 *
 * See /docs for usage.
 *
 * Additional config:
 * - keys: Use as true to use the keys of the select options instead of the values.
 * - input: Attributes for input element
 */
class DatalistWidget extends SelectBoxWidget {

	/**
	 * @param array $data
	 * @param \Cake\View\Form\ContextInterface $context
	 * @return string
	 */
	public function render(array $data, ContextInterface $context): string {
		$data += [
			'id' => null,
			'name' => '',
			'empty' => false,
			'escape' => true,
			'options' => [],
			'disabled' => null,
			'val' => null,
			'input' => [],
			'keys' => false,
			'templateVars' => [],
		];

		$options = $this->_renderContent($data);
		if (!$data['keys']) {
			$options = str_replace(
				'value',
				'data-value',
				$options
			);
		}

		$name = $data['name'];
		$id = $data['id'] ?: Text::slug($name);
		$default = isset($data['val']) ? $data['val'] : null;

		$inputData = $data['input'] + [
			'id' => $id,
			'name' => $name,
			'autocomplete' => 'off',
		];

		unset($data['name'], $data['options'], $data['empty'], $data['val'], $data['escape'], $data['keys'], $data['input'], $data['id']);
		if (isset($data['disabled']) && is_array($data['disabled'])) {
			unset($data['disabled']);
		}

		$inputData['value'] = $default;
		$inputAttrs = $this->_templates->formatAttributes($inputData);

		$datalistAttrs = $this->_templates->formatAttributes($data);
		return $this->_templates->format(
			'datalist',
			[
				'name' => $name,
				'inputAttrs' => $inputAttrs,
				'datalistAttrs' => $datalistAttrs,
				'content' => implode('', $options),
				'id' => $id,
			]
		);
	}

}
