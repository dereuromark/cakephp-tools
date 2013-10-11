<?php

App::uses('Model', 'Model');
App::uses('Controller', 'Controller');

class LinkableBehaviorTest extends CakeTestCase {

	public $fixtures = array(
		'plugin.tools.linkable_user',
		'plugin.tools.linkable_profile',
		'plugin.tools.generic',
		'plugin.tools.linkable_comment',
		'plugin.tools.blog_post',
		'plugin.tools.blog_posts_linkable_tag',
		'plugin.tools.linkable_tag',
		'plugin.tools.legacy_product',
		'plugin.tools.legacy_company',
		'plugin.tools.shipment',
		'plugin.tools.order_item',
		'plugin.tools.news_article',
		'plugin.tools.news_category',
		'plugin.tools.news_articles_news_category',
	);

	public $User;

	public function setUp() {
		parent::setUp();

		$this->User = ClassRegistry::init('LinkableUser');
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->User);
	}

	public function testBelongsTo() {
		$arrayExpected = array(
			'LinkableUser' => array('id' => 1, 'username' => 'CakePHP'),
			'LinkableProfile' => array('id' => 1, 'user_id' => 1, 'biography' => 'CakePHP is a rapid development framework for PHP that provides an extensible architecture for developing, maintaining, and deploying applications.')
		);

		$arrayResult = $this->User->find('first', array(
			'contain' => array(
				'LinkableProfile'
			)
		));

		$arrayExpectedTmp = $arrayExpected;
		$arrayExpectedTmp['LinkableUser']['role_id'] = 1;
		$this->assertTrue(isset($arrayResult['LinkableProfile']), 'belongsTo association via Containable: %s');
		$this->assertEquals($arrayExpectedTmp, $arrayResult, 'belongsTo association via Containable: %s');

		// Same association, but this time with Linkable
		$arrayResult = $this->User->find('first', array(
			'fields' => array(
				'id',
				'username'
			),
			'contain' => false,
			'link' => array(
				'LinkableProfile' => array(
					'fields' => array(
						'id',
						'user_id',
						'biography'
					)
				)
			)
		));

		$this->assertTrue(isset($arrayResult['LinkableProfile']), 'belongsTo association via Linkable: %s');
		$this->assertTrue(!empty($arrayResult['LinkableProfile']), 'belongsTo association via Linkable: %s');
		$this->assertEquals($arrayExpected, $arrayResult, 'belongsTo association via Linkable: %s');

		// Linkable association, no field lists
		$arrayResult = $this->User->find('first', array(
			'contain' => false,
			'link' => array(
				'LinkableProfile'
			)
		));

		$arrayExpectedTmp = $arrayExpected;
		$arrayExpectedTmp['LinkableUser']['role_id'] = 1;
		$this->assertTrue(isset($arrayResult['LinkableProfile']), 'belongsTo association via Linkable (automatic fields): %s');
		$this->assertEquals($arrayExpectedTmp, $arrayResult, 'belongsTo association via Linkable (automatic fields): %s');

		// On-the-fly association via Linkable
		$arrayExpected = array(
			'LinkableUser' => array('id' => 1, 'username' => 'CakePHP'),
			'Generic' => array('id' => 1, 'text' => '')
		);

		$arrayResult = $this->User->find('first', array(
			'contain' => false,
			'link' => array(
				'Generic' => array(
					'class' => 'Generic',
					'conditions' => array('exactly' => 'LinkableUser.id = Generic.id'),
					'fields' => array(
						'id',
						'text'
					)
				)
			)
		));

		$arrayExpectedTmp = $arrayExpected;
		$arrayExpectedTmp['LinkableUser']['role_id'] = 1;
		$this->assertTrue(isset($arrayResult['Generic']), 'On-the-fly belongsTo association via Linkable: %s');
		$this->assertEquals($arrayExpectedTmp, $arrayResult, 'On-the-fly belongsTo association via Linkable: %s');

		// On-the-fly association via Linkable, with order on the associations' row and using array conditions instead of plain string
		$arrayExpected = array(
			'LinkableUser' => array('id' => 4, 'username' => 'CodeIgniter'),
			'Generic' => array('id' => 4, 'text' => '')
		);

		$arrayResult = $this->User->find('first', array(
			'contain' => false,
			'link' => array(
				'Generic' => array(
					'class' => 'Generic',
					'conditions' => array('exactly' => array('LinkableUser.id = Generic.id')),
					'fields' => array(
						'id',
						'text'
					)
				)
			),
			'order' => 'Generic.id DESC'
		));

		$arrayExpectedTmp = $arrayExpected;
		$arrayExpectedTmp['LinkableUser']['role_id'] = 3;
		$this->assertEquals($arrayExpectedTmp, $arrayResult, 'On-the-fly belongsTo association via Linkable, with order: %s');
	}

	/**
	 * HasMany association via Containable. Should still work when Linkable is loaded.
	 *
	 * @return void
	 */
	public function testHasMany() {
		$arrayExpected = array(
			'LinkableUser' => array('id' => 1, 'username' => 'CakePHP'),
			'LinkableComment' => array(
				0 => array(
					'id' => 1,
					'user_id' => 1,
					'body' => 'Text'
				),
				1 => array(
					'id' => 2,
					'user_id' => 1,
					'body' => 'Text'
				),
			)
		);

		$arrayResult = $this->User->find('first', array(
			'contain' => array(
				'LinkableComment'
			),
			'order' => 'LinkableUser.id ASC'
		));

		$arrayExpectedTmp = $arrayExpected;
		$arrayExpectedTmp['LinkableUser']['role_id'] = 1;
		$this->assertTrue(isset($arrayResult['LinkableComment']), 'hasMany association via Containable: %s');
		$this->assertEquals($arrayExpectedTmp, $arrayResult, 'hasMany association via Containable: %s');

		// Same association, but this time with Linkable
		$arrayExpected = array(
			'LinkableUser' => array('id' => 1, 'username' => 'CakePHP'),
			'LinkableComment' => array(
				'id' => 1,
				'user_id' => 1,
				'body' => 'Text'
			)
		);

		$arrayResult = $this->User->find('first', array(
			'fields' => array(
				'id',
				'username'
			),
			'contain' => false,
			'link' => array(
				'LinkableComment' => array(
					'fields' => array(
						'id',
						'user_id',
						'body'
					)
				)
			),
			'order' => 'LinkableUser.id ASC',
			'group' => 'LinkableUser.id'
		));

		$this->assertEquals($arrayExpected, $arrayResult, 'hasMany association via Linkable: %s');
	}

	public function testComplexAssociations() {
		$this->BlogPost = ClassRegistry::init('BlogPost');

		$arrayExpected = array(
			'BlogPost' => array('id' => 1, 'title' => 'Post 1', 'user_id' => 1),
			'LinkableTag' => array('name' => 'General'),
			'LinkableProfile' => array('biography' => 'CakePHP is a rapid development framework for PHP that provides an extensible architecture for developing, maintaining, and deploying applications.'),
			'MainLinkableTag' => array('name' => 'General'),
			'Generic' => array('id' => 1, 'text' => ''),
			'LinkableUser' => array('id' => 1, 'username' => 'CakePHP')
		);

		$arrayResult = $this->BlogPost->find('first', array(
			'conditions' => array(
				'MainLinkableTag.id' => 1
			),
			'link' => array(
				'LinkableUser' => array(
					'LinkableProfile' => array(
						'fields' => array(
							'biography'
						),
						'Generic' => array(
							'class' => 'Generic',
							'conditions' => array('exactly' => 'LinkableUser.id = Generic.id'),
						)
					)
				),
				'LinkableTag' => array(
					'table' => 'linkable_tags',
					'fields' => array(
						'name'
					)
				),
				'MainLinkableTag' => array(
					'class' => 'LinkableTag',
					'conditions' => array('exactly' => 'BlogPostsLinkableTag.blog_post_id = BlogPost.id'),
					'fields' => array(
						'MainLinkableTag.name'
					)
				)
			)
		));

		$arrayExpectedTmp = $arrayExpected;
		$arrayExpectedTmp['LinkableUser']['role_id'] = 1;
		$this->assertEquals($arrayExpectedTmp, $arrayResult, 'Complex find: %s');

		// Linkable and Containable combined
		$arrayExpected = array(
			'BlogPost' => array('id' => 1, 'title' => 'Post 1', 'user_id' => 1),
			'LinkableUser' => array('id' => 1, 'username' => 'CakePHP'),
			'LinkableTag' => array(
				array('id' => 1, 'name' => 'General', 'parent_id' => null, 'BlogPostsLinkableTag' => array('id' => 1, 'blog_post_id' => 1, 'tag_id' => 1, 'main' => 0)),
				//array('id' => 2, 'name' => 'Test I', 'parent_id' => 1, 'BlogPostsLinkableTag' => array('id' => 2, 'blog_post_id' => 1, 'tag_id' => 2, 'main' => 1))
			),
		);

		$arrayResult = $this->BlogPost->find('first', array(
			'contain' => array(
				'LinkableTag'
			),
			'link' => array(
				'LinkableUser'
			)
		));

		$arrayExpectedTmp = $arrayExpected;
		$arrayExpectedTmp['LinkableUser']['role_id'] = 1;
		$this->assertEquals($arrayExpectedTmp, $arrayResult, 'Linkable and Containable combined: %s');
	}

	public function testPagination() {
		$objController = new Controller(new CakeRequest(), new CakeResponse());
		$objController->layout = 'ajax';
		$objController->uses = array('LinkableUser');
		$objController->constructClasses();
		$objController->request->url = '/';

		$objController->paginate = array(
			'fields' => array(
				'username'
			),
			'contain' => false,
			'link' => array(
				'LinkableProfile' => array(
					'fields' => array(
						'biography'
					)
				)
			),
			'limit' => 2
		);

		$arrayResult = $objController->paginate('LinkableUser');

		$this->assertEquals(4, $objController->params['paging']['LinkableUser']['count'], 'Paging: total records count: %s');

		// Pagination with order on a row from table joined with Linkable
		$objController->paginate = array(
			'fields' => array(
				'id'
			),
			'contain' => false,
			'link' => array(
				'LinkableProfile' => array(
					'fields' => array(
						'user_id'
					)
				)
			),
			'limit' => 2,
			'order' => 'LinkableProfile.user_id DESC'
		);

		$arrayResult = $objController->paginate('LinkableUser');

		$arrayExpected = array(
			0 => array(
				'LinkableUser' => array(
					'id' => 4
				),
				'LinkableProfile' => array('user_id' => 4)
			),
			1 => array(
				'LinkableUser' => array(
					'id' => 3
				),
				'LinkableProfile' => array('user_id' => 3)
			)
		);

		$this->assertEquals($arrayExpected, $arrayResult, 'Paging with order on join table row: %s');

		// Pagination without specifying any fields
		$objController->paginate = array(
			'contain' => false,
			'link' => array(
				'LinkableProfile'
			),
			'limit' => 2,
			'order' => 'LinkableProfile.user_id DESC'
		);

		$arrayResult = $objController->paginate('LinkableUser');
		$this->assertEquals(4, $objController->params['paging']['LinkableUser']['count'], 'Paging without any field lists: total records count: %s');
	}

	/**
	 * Series of tests that assert if Linkable can adapt to assocations that
	 * have aliases different from their standard model names.
	 *
	 * @return void
	 */
	public function testNonstandardAssociationNames() {
		$this->LinkableTag = ClassRegistry::init('LinkableTag');

		$arrayExpected = array(
			'LinkableTag' => array(
				'name' => 'Test I'
			),
			'Parent' => array(
				'name' => 'General'
			)
		);

		$arrayResult = $this->LinkableTag->find('first', array(
			'fields' => array(
				'name'
			),
			'conditions' => array(
				'LinkableTag.id' => 2
			),
			'link' => array(
				'Parent' => array(
					'fields' => array(
						'name'
					)
				)
			)
		));

		$this->assertEquals($arrayExpected, $arrayResult, 'Association with non-standard name: %s');

		$this->LegacyProduct = ClassRegistry::init('LegacyProduct');

		$arrayExpected = array(
			'LegacyProduct' => array(
				'name' => 'Velocipede'
			),
			'Maker' => array(
				'company_name' => 'Vintage Stuff Manufactory'
			),
			'Transporter' => array(
				'company_name' => 'Joe & Co Crate Shipping Company'
			)
		);

		$arrayResult = $this->LegacyProduct->find('first', array(
			'fields' => array(
				'name'
			),
			'conditions' => array(
				'LegacyProduct.product_id' => 1
			),
			'link' => array(
				'Maker' => array(
					'fields' => array(
						'company_name'
					)
				),
				'Transporter' => array(
					'fields' => array(
						'company_name'
					)
				)
			)
		));

		$this->assertEquals($arrayExpected, $arrayResult, 'belongsTo associations with custom foreignKey: %s');

		$arrayExpected = array(
			'ProductsMade' => array(
				'name' => 'Velocipede'
			),
			'Maker' => array(
				'company_name' => 'Vintage Stuff Manufactory'
			)
		);

		$arrayResult = $this->LegacyProduct->Maker->find('first', array(
			'fields' => array(
				'company_name'
			),
			'conditions' => array(
				'Maker.company_id' => 1
			),
			'link' => array(
				'ProductsMade' => array(
					'fields' => array(
						'name'
					)
				)
			)
		));

		$this->assertEquals($arrayExpected, $arrayResult, 'hasMany association with custom foreignKey: %s');
	}

	public function testAliasedBelongsToWithSameModelAsHasMany() {
		$this->OrderItem = ClassRegistry::init('OrderItem');

		$arrayExpected = array(
			0 => array(
				'OrderItem' => array(
					'id' => 50,
					'active_shipment_id' => 320
				),
				'ActiveShipment' => array(
					'id' => 320,
					'ship_date' => '2011-01-07',
					'order_item_id' => 50
				)
			)
		);

		$arrayResult = $this->OrderItem->find('all', array(
			'recursive' => -1,
			'conditions' => array(
				'ActiveShipment.ship_date' => date('2011-01-07'),
			),
			'link' => array('ActiveShipment'),
		));

		$this->assertEquals($arrayExpected, $arrayResult, 'belongsTo association with alias (requested), with hasMany to the same model without alias: %s');
	}

	/**
	 * Ensure that the correct habtm keys are read from the relationship in the models
	 *
	 * @author David Yell <neon1024@gmail.com>
	 * @return void
	 */
	public function testHasAndBelongsToManyNonConvention() {
		$this->NewsArticle = ClassRegistry::init('NewsArticle');

		$expected = array(
			array(
				'NewsArticle' => array(
					'id' => '1',
					'title' => 'CakePHP the best framework'
				),
				'NewsCategory' => array(
					'id' => '1',
					'name' => 'Development'
				)
			)
		);

		$result = $this->NewsArticle->find('all', array(
			'link' => array(
				'NewsCategory'
			),
			'conditions' => array(
				'NewsCategory.id' => 1
			)
		));

		$this->assertEqual($expected, $result);
	}
}

class LinkableTestModel extends CakeTestModel {

	public $recursive = -1;

	public $actsAs = array(
		'Containable',
		'Tools.Linkable',
	);
}

class LinkableUser extends LinkableTestModel {

	public $hasOne = array(
		'LinkableProfile' => array(
			'className' => 'LinkableProfile',
			'foreignKey' => 'user_id'
		)
	);

	public $hasMany = array(
		'LinkableComment' => array(
			'className' => 'LinkableComment',
			'foreignKey' => 'user_id'
		),
		'BlogPost' => array(
			'foreignKey' => 'user_id'
		)
	);
}

class LinkableComment extends LinkableTestModel {

}

class LinkableProfile extends LinkableTestModel {

	public $belongsTo = array(
		'LinkableUser' => array(
			'className' => 'LinkableUser',
			'foreignKey' => 'user_id'
		)
	);
}

class BlogPost extends LinkableTestModel {

	public $belongsTo = array(
		'LinkableUser' => array(
			'className' => 'LinkableUser',
			'foreignKey' => 'user_id'
		),
	);

	public $hasAndBelongsToMany = array(
		'LinkableTag' => array(
			'foreignKey' => 'tag_id',
			'associationForeignKey' => 'blog_post_id',
		)
	);
}

class BlogPostLinkableTag extends LinkableTestModel {
}

class LinkableTag extends LinkableTestModel {

	public $hasAndBelongsToMany = array(
		'BlogPost' => array(
			'foreignKey' => 'blog_post_id',
			'associationForeignKey' => 'tag_id',
		)
	);

	public $belongsTo = array(
		'Parent' => array(
			'className' => 'LinkableTag',
			'foreignKey' => 'parent_id'
		)
	);
}

class LegacyProduct extends LinkableTestModel {

	public $primaryKey = 'product_id';

	public $belongsTo = array(
		'Maker' => array(
			'className' => 'LegacyCompany',
			'foreignKey' => 'the_company_that_builds_it_id'
		),
		'Transporter' => array(
			'className' => 'LegacyCompany',
			'foreignKey' => 'the_company_that_delivers_it_id'
		)
	);
}

class LegacyCompany extends LinkableTestModel {

	public $primaryKey = 'company_id';

	public $hasMany = array(
		'ProductsMade' => array(
			'className' => 'LegacyProduct',
			'foreignKey' => 'the_company_that_builds_it_id'
		)
	);
}

class Shipment extends LinkableTestModel {

	public $belongsTo = array(
		'OrderItem'
	);
}

class OrderItem extends LinkableTestModel {

	public $hasMany = array(
		'Shipment'
	);

	public $belongsTo = array(
		'ActiveShipment' => array(
			'className' => 'Shipment',
			'foreignKey' => 'active_shipment_id',
		),
	);

}

class NewsArticle extends LinkableTestModel {

	public $hasAndBelongsToMany = array(
		'NewsCategory' => array(
			'className' => 'NewsCategory',
			'joinTable' => 'news_articles_news_categories',
			'foreignKey' => 'article_id',
			'associationForeignKey' => 'category_id',
			'unique' => 'keepExisting',
		)
	);

}

class NewsCategory extends LinkableTestModel {

	public $hasAndBelongsToMany = array(
		'NewsArticle' => array(
			'className' => 'NewsArticle',
			'joinTable' => 'news_articles_news_categories',
			'foreignKey' => 'category_id',
			'associationForeignKey' => 'article_id',
			'unique' => 'keepExisting',
		)
	);

}
