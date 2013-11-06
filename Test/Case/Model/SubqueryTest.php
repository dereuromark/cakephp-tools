<?php
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('Controller', 'Controller');

/**
 *
 * @link http://bakery.cakephp.org/articles/lucaswxp/2011/02/11/easy_and_simple_subquery_cakephp
 * @link http://www.sql-und-xml.de/sql-tutorial/subqueries-unterabfragen.html
 */
class SubqueryTest extends MyCakeTestCase {

	public $fixtures = array('plugin.tools.country', 'plugin.tools.country_province');

	public $Model;

	public function setUp() {
		$this->Model = ClassRegistry::init('Country');

		parent::setUp();
	}

	/**
	 * SubqueryTest::testSubquery()
	 *
	 * @return void
	 */
	public function testSubquery() {
		$res = $this->Model->find('all', array('conditions' => array()));
		$this->debug(count($res));
		$this->assertEquals(10, count($res));

		$res = $this->Model->subquery('count');
		$this->debug($res);

		$res = $this->Model->find('all', array('conditions' => array('lat <=' => $this->Model->subquery('count'))));
		$this->debug(count($res));
		$this->assertEquals(0, count($res));

		$subqueryOptions = array('fields' => array('MAX(lat)'));
		$res = $this->Model->subquery('all', $subqueryOptions);
		$this->debug($res);

		$subqueryOptions = array('fields' => array('MAX(lat)'));
		$res = $this->Model->find('all', array('conditions' => array('lat <=' => $this->Model->subquery('first', $subqueryOptions))));
		$this->debug(count($res));
		$this->assertEquals(0, count($res));

		$subqueryOptions = array('fields' => array('id'), 'conditions' => array('id' => 1));
		$res = $this->Model->subquery('first', $subqueryOptions);
		$this->debug($res);

		$res = $this->Model->find('all', array('conditions' => array('id NOT IN ' . $this->Model->subquery('all', $subqueryOptions))));
		$this->debug(count($res));
		$this->debug($res);
		$this->assertEquals(9, count($res));
	}

	/**
	 * SubqueryTest::testSubqueryPaginated()
	 *
	 * @return void
	 */
	public function testSubqueryPaginated() {
		$Controller = new CountriesTestsController(new CakeRequest(null, false), new CakeResponse());
		$Controller->constructClasses();
		$source = $Controller->Country->getDataSource();
		$database = $source->config['database'];

		$subquery = $Controller->Country->subquery('list', array('conditions' => array('NOT' => array('SubCountry.id' => array(1, 2, 3)))));
		$expected = '(SELECT SubCountry.id FROM `' . $database . '`.`countries` AS `SubCountry`   WHERE NOT (`SubCountry`.`id` IN (1, 2, 3)))';
		$this->assertEquals($expected, $subquery);

		$res = $Controller->Country->query($subquery);
		$this->assertTrue(count($res) === 7);

		$Controller->paginate = array(
			'conditions' => array('Country.id IN ' . $subquery)
		);

		$res = $Controller->paginate();
		$this->assertTrue(count($res) === 7);
		$this->debug($res);
	}

}

class CountriesTestsController extends Controller {

	public $uses = array('Country');

}
