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
	public function testEmailLink() {
		$result = $this->Obfuscate->emailLink('foobar@somedomain.com', 'Mail', ['subject' => 'Hi there', 'body' => 'x'], ['title' => 'Contact']);

		$this->assertStringNotContainsString('<script', $result);
		$this->assertStringNotContainsString('foobar@somedomain.com', $result);
		$this->assertStringNotContainsString('mailto:', $result);
		$this->assertStringNotContainsString('data-tools-obfuscate-text', $result);

		$encoded = $this->extractEncoded($result);
		$this->assertSame('mailto:foobar@somedomain.com?subject=Hi%20there&body=x', $this->decode($encoded));

		$expected = '<a href="#" title="Contact" class="email" data-tools-obfuscate="' . $encoded . '">Mail</a>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testEmailLinkPlaceholder() {
		$result = $this->Obfuscate->emailLink('foo.bar@somedomain.com');

		$this->assertStringNotContainsString('foo.bar@somedomain.com', $result);
		$this->assertStringNotContainsString('mailto:', $result);
		$this->assertStringContainsString('data-tools-obfuscate-text="1"', $result);
		$this->assertStringContainsString('>foo [dot] bar [at] somedomain [dot] com</a>', $result);
		$this->assertSame('mailto:foo.bar@somedomain.com', $this->decode($this->extractEncoded($result)));
	}

	/**
	 * @return void
	 */
	public function testEmailLinkPlaceholderConfig() {
		$this->Obfuscate->setConfig('placeholder', ['@' => '(at)'], false);
		$result = $this->Obfuscate->emailLink('foobar@somedomain.com');

		$this->assertStringContainsString('>foobar(at)somedomain.com</a>', $result);
	}

	/**
	 * @return void
	 */
	public function testEmailLinkEscapesAttributes() {
		$payload = '"><script>alert(1)</script>';
		$result = $this->Obfuscate->emailLink('foobar@somedomain.com', '<b>Mail</b>', [], ['title' => $payload]);

		$this->assertStringNotContainsString('<script', $result);
		$this->assertStringContainsString('title="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $result);
		$this->assertStringContainsString('>&lt;b&gt;Mail&lt;/b&gt;</a>', $result);
	}

	/**
	 * @return void
	 */
	public function testEmailLinkEscapeFalseKeepsPlaceholderEscaped() {
		$result = $this->Obfuscate->emailLink('a<b>@somedomain.com', null, [], ['escape' => false]);

		$this->assertStringNotContainsString('<b>', $result);
		$this->assertStringContainsString('>a&lt;b&gt; [at] somedomain [dot] com</a>', $result);
	}

	/**
	 * @return void
	 */
	public function testEmailLinkAppendsScriptOnce() {
		$request = (new ServerRequest())->withAttribute('cspScriptNonce', 'core-nonce');
		$view = new View($request);
		$obfuscate = new ObfuscateHelper($view);

		$obfuscate->emailLink('foo@somedomain.com');
		$obfuscate->emailLink('bar@somedomain.com', 'Bar');

		$block = $view->fetch('script');
		$this->assertSame(1, substr_count($block, '<script'));
		$this->assertStringContainsString('obfuscate.js', $block);
		$this->assertStringContainsString('nonce="core-nonce"', $block);
	}

	/**
	 * @return void
	 */
	public function testEmailLinkScriptDisabled() {
		$view = new View();
		$obfuscate = new ObfuscateHelper($view, ['script' => false]);

		$obfuscate->emailLink('foo@somedomain.com');

		$this->assertSame('', $view->fetch('script'));
	}

	/**
	 * @param string $result
	 * @return string
	 */
	protected function extractEncoded(string $result): string {
		$this->assertSame(1, preg_match('/data-tools-obfuscate="([A-Za-z0-9+\/=]+)"/', $result, $matches));

		return $matches[1];
	}

	/**
	 * Mirrors decode() in webroot/js/obfuscate.js.
	 *
	 * @param string $encoded
	 * @return string
	 */
	protected function decode(string $encoded): string {
		return (string)base64_decode(strrev($encoded), true);
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
