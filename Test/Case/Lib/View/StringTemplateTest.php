<?php
App::uses('StringTemplate', 'Tools.View');

class StringTemplateTest extends CakeTestCase {

	/**
	 * SetUp
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->template = new StringTemplate();
	}

	/**
	 * Test adding templates through the constructor.
	 *
	 * @return void
	 */
	public function testConstructorAdd() {
		$templates = array(
			'link' => '<a href="{{url}}">{{text}}</a>'
		);
		$template = new StringTemplate($templates);
		debug($template->config('link'));
		$this->assertEquals($templates['link'], $template->config('link'));
	}

	/**
	 * Test adding templates.
	 *
	 * @return void
	 */
	public function testAdd() {
		$templates = array(
			'link' => '<a href="{{url}}">{{text}}</a>'
		);
		$result = $this->template->add($templates);
		$this->assertSame(
			$this->template,
			$result,
			'The same instance should be returned'
		);

		$this->assertEquals($templates['link'], $this->template->config('link'));
	}

	/**
	 * Test remove.
	 *
	 * @return void
	 */
	public function testRemove() {
		$templates = array(
			'link' => '<a href="{{url}}">{{text}}</a>'
		);
		$this->template->add($templates);
		$this->assertNull($this->template->remove('link'), 'No return');
		$this->assertNull($this->template->config('link'), 'Template should be gone.');
	}

	/**
	 * Test formatting strings.
	 *
	 * @return void
	 */
	public function testFormat() {
		$templates = array(
			'link' => '<a href="{{url}}">{{text}}</a>'
		);
		$this->template->add($templates);

		$result = $this->template->format('not there', []);
		$this->assertSame('', $result);

		$result = $this->template->format('link', [
			'url' => '/',
			'text' => 'example'
		]);
		$this->assertEquals('<a href="/">example</a>', $result);
	}

	/**
	 * Test loading templates files in the app.
	 *
	 * @return void
	 */
	public function testLoad() {
		$this->skipIf(true, 'Find a way to mock the path from /Tools/Config to /Tools/Test/test_app/Config');

		$this->template->remove('attribute');
		$this->template->remove('compactAttribute');
		$this->assertEquals([], $this->template->config());
		$this->assertNull($this->template->load('Tools.test_templates'));
		$this->assertEquals('<a href="{{url}}">{{text}}</a>', $this->template->config('link'));
	}

	/**
	 * Test that loading non-existing templates causes errors.
	 *
	 * @expectedException ConfigureException
	 * @expectedExceptionMessage Could not load configuration file
	 */
	public function testLoadErrorNoFile() {
		$this->template->load('no_such_file');
	}

	/**
	 * Test formatting compact attributes.
	 *
	 * @return void
	 */
	public function testFormatAttributesCompact() {
		$attrs = array('disabled' => true, 'selected' => 1, 'checked' => '1', 'multiple' => 'multiple');
		$result = $this->template->formatAttributes($attrs);
		$this->assertEquals(
			' disabled="disabled" selected="selected" checked="checked" multiple="multiple"',
			$result
		);

		$attrs = array('disabled' => false, 'selected' => 0, 'checked' => '0', 'multiple' => null);
		$result = $this->template->formatAttributes($attrs);
		$this->assertEquals(
			'',
			$result
		);
	}

	/**
	 * Test formatting normal attributes.
	 *
	 * @return void
	 */
	public function testFormatAttributes() {
		$attrs = array('name' => 'bruce', 'data-hero' => '<batman>');
		$result = $this->template->formatAttributes($attrs);
		$this->assertEquals(
			' name="bruce" data-hero="&lt;batman&gt;"',
			$result
		);

		$attrs = array('escape' => false, 'name' => 'bruce', 'data-hero' => '<batman>');
		$result = $this->template->formatAttributes($attrs);
		$this->assertEquals(
			' name="bruce" data-hero="<batman>"',
			$result
		);

		$attrs = array('name' => 'bruce', 'data-hero' => '<batman>');
		$result = $this->template->formatAttributes($attrs, array('name'));
		$this->assertEquals(
			' data-hero="&lt;batman&gt;"',
			$result
		);
	}

	/**
	 * Test formatting array attributes.
	 *
	 * @return void
	 */
	public function testFormatAttributesArray() {
		$attrs = array('name' => array('bruce', 'wayne'));
		$result = $this->template->formatAttributes($attrs);
		$this->assertEquals(
			' name="bruce wayne"',
			$result
		);
	}

	/**
	 * Tests that compile information is refreshed on adds and removes
	 *
	 * @return void
	 */
	public function testCopiledInfoRefresh() {
		$compilation = $this->template->compile('link');
		$this->template->add([
			'link' => '<a bar="{{foo}}">{{baz}}</a>'
		]);
		$this->assertNotEquals($compilation, $this->template->compile('link'));
		$this->template->remove('link');
		$this->assertEquals([null, null], $this->template->compile('link'));
	}

}
