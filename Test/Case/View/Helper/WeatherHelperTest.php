<?php

App::uses('WeatherHelper', 'Tools.View/Helper');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 */
class WeatherHelperTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->Weather = new WeatherHelper(new View(null));
		$this->Weather->Html = new HtmlHelper(new View(null));

		$this->skipIf(!Configure::read('Weather.key'));
	}

	/** TODO **/

	public function testDisplay() {
		$res = $this->Weather->get('51.0872,13.8028');
		$res = $this->_displayForecast($res);
		$this->out($res);
		$this->assertTrue(!empty($res));

		$res = $this->Weather->get('Berlin, Deutschland');
		$res = $this->_displayForecast($res);
		$this->out($res);
		$this->assertTrue(!empty($res));

		$res = $this->Weather->get('Schwäbisch Hall, Deutschland');
		$res = $this->_displayForecast($res);
		$this->out($res);
		$this->assertTrue(!empty($res));

		$res = $this->Weather->get('xxxxx');
		$res = $this->_displayForecast($res);
		$this->assertTrue(empty($res));
	}

	public function _displayForecast($w) {
		$res = '';
		if (empty($w['request'])) {
			return $res;
		}

		$res .= '<table><tr>';
		for ($i = 2; $i < 5; $i++) {
			$weather = $w['weather'][$i];

			$res .= '<td>';
			$res .= '<h1>'.date('D', strtotime($weather['date'])).'</h1>';
			$res .= '<div>'.date('M d, Y', strtotime($weather['date'])).'</div>';
			$res .= '<h1>'.$this->Weather->Html->image($weather['weatherIconUrl']).'</h1>';
			$res .= '<div>'.$weather['tempMinC'].'° - '.$weather['tempMaxC'].'°</div>';
			$res .= '<div>'.$weather['weatherDesc'].'</div>';

			$res .= '</td>';
		}
		$res .= '</tr></table>';

		return $res;
	}

}
