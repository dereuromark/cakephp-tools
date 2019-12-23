<?php

namespace Tools\Test\TestCase\View\Widget;

use ArrayObject;
use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use Tools\View\Widget\DatalistWidget;

class DatalistWidgetTest extends TestCase {

	/**
	 * @var \Cake\View\Form\ContextInterface
	 */
	protected $context;

	/**
	 * @var \Cake\View\StringTemplate
	 */
	protected $templates;

	/**
	 * setup method.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$templates = [
			'datalist' => '<input type="text" list="datalist-{{id}}"{{inputAttrs}}><datalist id="datalist-{{id}}"{{datalistAttrs}}>{{content}}</datalist>',
			'select' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
			'option' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
			'optgroup' => '<optgroup label="{{label}}"{{attrs}}>{{content}}</optgroup>',
		];
		$this->context = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
		$this->templates = new StringTemplate($templates);
	}

	/**
	 * test render no options
	 *
	 * @return void
	 */
	public function testRenderNoOptions() {
		$select = new DatalistWidget($this->templates);
		$data = [
			'id' => 'BirdName',
			'name' => 'Birds[name]',
			'options' => [],
		];
		$result = $select->render($data, $this->context);
		$expected = [
			'input' => ['type' => 'text', 'list' => 'datalist-BirdName', 'id' => 'BirdName', 'name' => 'Birds[name]', 'autocomplete' => 'off'],
			'datalist' => ['id' => 'datalist-BirdName'],
			'/datalist',
		];
		$this->assertHtml($expected, $result);
	}

	/**
	 * test simple rendering
	 *
	 * @return void
	 */
	public function testRenderSimple() {
		$select = new DatalistWidget($this->templates);
		$data = [
			'id' => 'BirdName',
			'name' => 'Birds[name]',
			'options' => ['a' => 'Albatross', 'b' => 'Budgie'],
		];
		$result = $select->render($data, $this->context);
		$expected = [
			'input' => ['type' => 'text', 'list' => 'datalist-BirdName', 'id' => 'BirdName', 'name' => 'Birds[name]', 'autocomplete' => 'off'],
			'datalist' => ['id' => 'datalist-BirdName'],
			['option' => ['data-value' => 'a']], 'Albatross', '/option',
			['option' => ['data-value' => 'b']], 'Budgie', '/option',
			'/datalist',
		];
		$this->assertHtml($expected, $result);
	}

	/**
	 * test simple iterator rendering
	 *
	 * @return void
	 */
	public function testRenderSimpleIterator() {
		$select = new DatalistWidget($this->templates);
		$options = new ArrayObject(['a' => 'Albatross', 'b' => 'Budgie']);
		$data = [
			'name' => 'Birds[name]',
			'options' => $options,
		];
		$result = $select->render($data, $this->context);
		$expected = [
			'input' => ['type' => 'text', 'list' => 'datalist-Birds-name', 'id' => 'Birds-name', 'name' => 'Birds[name]', 'autocomplete' => 'off'],
			'datalist' => ['id' => 'datalist-Birds-name'],
			['option' => ['data-value' => 'a']], 'Albatross', '/option',
			['option' => ['data-value' => 'b']], 'Budgie', '/option',
			'/datalist',
		];
		$this->assertHtml($expected, $result);
	}

	/**
	 * test rendering with a selected value
	 *
	 * @return void
	 */
	public function testRenderSelected() {
		$select = new DatalistWidget($this->templates);
		$data = [
			'name' => 'Birds[name]',
			'val' => '1',
			'options' => [
				1 => 'one',
				'1x' => 'one x',
				'2' => 'two',
				'2x' => 'two x',
			],
		];
		$result = $select->render($data, $this->context);
		$expected = [
			'input' => ['type' => 'text', 'list' => 'datalist-Birds-name', 'id' => 'Birds-name', 'name' => 'Birds[name]', 'autocomplete' => 'off', 'value' => '1'],
			'datalist' => ['id' => 'datalist-Birds-name'],
			['option' => ['data-value' => '1', 'selected' => 'selected']], 'one', '/option',
			['option' => ['data-value' => '1x']], 'one x', '/option',
			['option' => ['data-value' => '2']], 'two', '/option',
			['option' => ['data-value' => '2x']], 'two x', '/option',
			'/datalist',
		];
		$this->assertHtml($expected, $result);

		$data['val'] = 2;
		$result = $select->render($data, $this->context);
		$expected = [
			'input' => ['type' => 'text', 'list' => 'datalist-Birds-name', 'id' => 'Birds-name', 'name' => 'Birds[name]', 'autocomplete' => 'off', 'value' => '2'],
			'datalist' => ['id' => 'datalist-Birds-name'],
			['option' => ['data-value' => '1']], 'one', '/option',
			['option' => ['data-value' => '1x']], 'one x', '/option',
			['option' => ['data-value' => '2', 'selected' => 'selected']], 'two', '/option',
			['option' => ['data-value' => '2x']], 'two x', '/option',
			'/datalist',
		];
		$this->assertHtml($expected, $result);
	}

	/**
	 * test rendering with option groups
	 *
	 * @return void
	 */
	public function testRenderOptionGroups() {
		$select = new DatalistWidget($this->templates);
		$data = [
			'name' => 'Birds[name]',
			'options' => [
				'Mammal' => [
					'beaver' => 'Beaver',
					'elk' => 'Elk',
				],
				'Bird' => [
					'budgie' => 'Budgie',
					'eagle' => 'Eagle',
				],
			],
		];
		$result = $select->render($data, $this->context);
		$expected = [
			'input' => ['type' => 'text', 'list' => 'datalist-Birds-name', 'id' => 'Birds-name', 'name' => 'Birds[name]', 'autocomplete' => 'off'],
			'datalist' => ['id' => 'datalist-Birds-name'],
			['optgroup' => ['label' => 'Mammal']],
			['option' => ['data-value' => 'beaver']],
			'Beaver',
			'/option',
			['option' => ['data-value' => 'elk']],
			'Elk',
			'/option',
			'/optgroup',
			['optgroup' => ['label' => 'Bird']],
			['option' => ['data-value' => 'budgie']],
			'Budgie',
			'/option',
			['option' => ['data-value' => 'eagle']],
			'Eagle',
			'/option',
			'/optgroup',
			'/datalist',
		];
		$this->assertHtml($expected, $result);
	}

	/**
	 * test rendering with option groups and escaping
	 *
	 * @return void
	 */
	public function testRenderOptionGroupsEscape() {
		$select = new DatalistWidget($this->templates);
		$data = [
			'name' => 'Birds[name]',
			'options' => [
				'>XSS<' => [
					'1' => 'One>',
				],
			],
		];
		$result = $select->render($data, $this->context);
		$expected = [
			'input' => ['type' => 'text', 'list' => 'datalist-Birds-name', 'id' => 'Birds-name', 'name' => 'Birds[name]', 'autocomplete' => 'off'],
			'datalist' => ['id' => 'datalist-Birds-name'],
			['optgroup' => ['label' => '&gt;XSS&lt;']],
			['option' => ['data-value' => '1']],
			'One&gt;',
			'/option',
			'/optgroup',
			'/datalist',
		];
		$this->assertHtml($expected, $result);

		$data['escape'] = false;
		$result = $select->render($data, $this->context);
		$expected = [
			'input' => ['type' => 'text', 'list' => 'datalist-Birds-name', 'id' => 'Birds-name', 'name' => 'Birds[name]', 'autocomplete' => 'off'],
			'datalist' => ['id' => 'datalist-Birds-name'],
			['optgroup' => ['label' => '>XSS<']],
			['option' => ['data-value' => '1']],
			'One>',
			'/option',
			'/optgroup',
			'/datalist',
		];
		$this->assertHtml($expected, $result);
	}

}
