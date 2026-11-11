<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\View\Helper\ObfuscateHelper;

class ObfuscateHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\ObfuscateHelper
	 */
	protected $Obfuscate;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Obfuscate = new ObfuscateHelper(new View());
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->Table);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testObject() {
		$this->assertInstanceOf('Tools\View\Helper\ObfuscateHelper', $this->Obfuscate);
	}

	/**
	 * @return void
	 */
	public function testEncodeEmail() {
		$result = $this->Obfuscate->encodeEmail('foobar@somedomain.com');
		$expected = '<span>@</span>';
		$this->assertStringContainsString($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testEncodeEmailUrl() {
		$result = $this->Obfuscate->encodeEmailUrl('foobar@somedomain.com');
		$expected = '<script language=javascript>';
		$this->assertStringContainsString($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testEncodeEmailUrlRendersLink() {
		$result = $this->Obfuscate->encodeEmailUrl('foobar@somedomain.com', 'Mail', ['subject' => 'Hi there', 'body' => 'x'], ['title' => 'Contact']);

		$expected = '<a href="mailto:foobar@somedomain.com?subject=Hi%20there&amp;body=x" title="Contact" class="email">Mail</a>';
		$this->assertSame($expected, $this->renderWrites($result));
	}

	/**
	 * @return void
	 */
	public function testEncodeEmailUrlEscapesAttributes() {
		$payload = '\'");alert(1);//</script><script>alert(2)</script>';
		$result = $this->Obfuscate->encodeEmailUrl('foobar@somedomain.com', 'Mail', [], ['title' => $payload]);

		$this->assertStringNotContainsString($payload, $result);
		$this->assertSame(2, substr_count($result, '</script>'));
		$this->assertSame(2, substr_count(strtolower($result), '<script'));

		$rendered = $this->renderWrites($result);
		$this->assertStringContainsString('title="&#039;&quot;);alert(1);//&lt;/script&gt;&lt;script&gt;alert(2)&lt;/script&gt;"', $rendered);
	}

	/**
	 * @return void
	 */
	public function testEncodeEmailUrlEscapeFalseStaysInsideScriptString() {
		$payload = '\'");alert(1);//</script>';
		$result = $this->Obfuscate->encodeEmailUrl('foobar@somedomain.com', 'Mail', [], ['title' => $payload, 'escape' => false]);

		$this->assertSame(2, substr_count($result, '</script>'));
		$this->assertStringNotContainsString($payload, $result);
		$this->assertStringContainsString('title="' . $payload . '"', $this->renderWrites($result));
	}

	/**
	 * Decodes the document.write() calls the way a browser would and returns the resulting markup.
	 *
	 * @param string $result
	 * @return string
	 */
	protected function renderWrites(string $result): string {
		$parts = explode('</script>', $result);
		$this->assertCount(3, $parts);
		$text = trim(strstr($parts[1], '<script', true) ?: '');

		$html = '';
		foreach ($this->scriptBodies($result) as $i => $body) {
			$this->assertSame(1, preg_match('/document\.write\((.*)\);/s', $body, $matches));
			$expression = trim($matches[1]);
			preg_match_all('/"(?:[^"\\\\]|\\\\.)*"/', $expression, $literals);
			$this->assertSame($expression, implode(' + ', $literals[0]), 'Script contains more than string literals');
			foreach ($literals[0] as $literal) {
				$decoded = json_decode($literal);
				$this->assertIsString($decoded);
				$html .= $decoded;
			}
			if ($i === 0) {
				$html .= $text;
			}
		}

		return $html;
	}

	/**
	 * @param string $result
	 * @return array<string>
	 */
	protected function scriptBodies(string $result): array {
		preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $result, $matches);

		return $matches[1];
	}

	/**
	 * @return void
	 */
	public function testEncodeEmailUrlCspScriptNonce() {
		$request = (new ServerRequest())->withAttribute('cspScriptNonce', 'core-nonce');
		$this->Obfuscate = new ObfuscateHelper(new View($request));

		$result = $this->Obfuscate->encodeEmailUrl('foobar@somedomain.com');
		$this->assertSame(2, substr_count($result, '<script language=javascript nonce="core-nonce">'));
	}

	/**
	 * @return void
	 */
	public function testEncodeEmailUrlCspNonceFallback() {
		$request = (new ServerRequest())->withAttribute('cspNonce', 'custom-nonce');
		$this->Obfuscate = new ObfuscateHelper(new View($request));

		$result = $this->Obfuscate->encodeEmailUrl('foobar@somedomain.com');
		$this->assertSame(2, substr_count($result, '<script language=javascript nonce="custom-nonce">'));
	}

	/**
	 * @return void
	 */
	public function testEncodeText() {
		$result = $this->Obfuscate->encodeText('foobar@somedomain.com');
		$expected = ';&#';
		$this->assertStringContainsString($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testEncodeTextEscapesMarkup() {
		for ($i = 0; $i < 20; $i++) {
			$result = $this->Obfuscate->encodeText('<b>ü</b>');
			$this->assertStringNotContainsString('<', $result);
			$this->assertSame('<b>ü</b>', html_entity_decode($result, ENT_QUOTES | ENT_HTML5));
		}
	}

	/**
	 * @return void
	 */
	public function testHideEmail() {
		$mails = [
			'test@test.de' => 't..t@t..t.de',
			'xx@yy.de' => 'x..x@y..y.de',
			'erk-wf@ve-eeervdg.com' => 'e..f@v..g.com',
		];
		foreach ($mails as $mail => $expected) {
			$res = $this->Obfuscate->hideEmail($mail);

			//echo '\''.$mail.'\' becomes \''.$res.'\' - expected \''.$expected.'\'';
			$this->assertEquals($expected, $res);
		}
	}

	/**
	 * @return void
	 */
	public function testWordCensor() {
		$data = [
			'dfssdfsdj sdkfj sdkfj ksdfj bitch ksdfj' => 'dfssdfsdj sdkfj sdkfj ksdfj ##### ksdfj',
			'122 jsdf ficken Sjdkf sdfj sdf' => '122 jsdf ###### Sjdkf sdfj sdf',
			'122 jsdf FICKEN sjdkf sdfjs sdf' => '122 jsdf ###### sjdkf sdfjs sdf',
			'dddddddddd ARSCH ddddddddddddd' => 'dddddddddd ##### ddddddddddddd',
			//'\';alert(String.fromCharCode(88,83,83))//\';alert(String.fromCharCode(88,83,83))//";alert(String.fromCharCode(88,83,83))//\";alert(String.fromCharCode(88,83,83))//--></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>' => null
		];
		foreach ($data as $value => $expected) {
			$res = $this->Obfuscate->wordCensor($value, ['Arsch', 'Ficken', 'Bitch']);

			//debug('\''.h($value).'\' becomes \''.h($res).'\'', null, false);
			$this->assertEquals($expected ?? $value, $res);
		}
	}

}
