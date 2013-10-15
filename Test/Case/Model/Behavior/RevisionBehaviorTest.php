<?php
App::uses('RevisionBehavior', 'Tools.Model/Behavior');

class RevisionBehaviorTest extends CakeTestCase {

	public $RevisionBehavior;

	public $autoFixtures = false;

	public $fixtures = array(
		'plugin.tools.revision_article',
		'plugin.tools.revision_articles_rev',
		'plugin.tools.revision_post',
		'plugin.tools.revision_posts_rev',
		'plugin.tools.revision_user',
		'plugin.tools.revision_comment',
		'plugin.tools.revision_comments_rev',
		'plugin.tools.revision_vote',
		'plugin.tools.revision_votes_rev',
		'plugin.tools.revision_comments_revision_tag',
		'plugin.tools.revision_comments_revision_tags_rev',
		'plugin.tools.revision_tag',
		'plugin.tools.revision_tags_rev');

	public function setUp() {
		parent::setUp();
		$this->RevisionBehavior = new RevisionBehavior();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->RevisionBehavior));
		$this->assertInstanceOf('RevisionBehavior', $this->RevisionBehavior);
	}

	public function tearDown($method = null) {
		unset($this->RevisionBehavior);
		parent::tearDown($method);
	}

	public function testSavePost() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = array('Post' => array('title' => 'New Post', 'content' => 'First post!'));
		$Post->save($data);
		$Post->id = 4;
		$result = $Post->newest(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')));
		$expected = array('Post' => array(
				'id' => 4,
				'title' => 'New Post',
				'content' => 'First post!',
				'version_id' => 4));
		$this->assertEquals($expected, $result);
	}

	public function testSaveWithoutChange() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->id = 1;
		$this->assertTrue((bool)$Post->createRevision());

		$Post->id = 1;
		$count = $Post->ShadowModel->find('count', array('conditions' => array('id' => 1)));
		$this->assertEquals($count, 2);

		$Post->id = 1;
		$data = $Post->read();
		$Post->save($data);

		$Post->id = 1;
		$count = $Post->ShadowModel->find('count', array('conditions' => array('id' => 1)));
		$this->assertEquals($count, 2);
	}

	public function testEditPost() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = array('Post' => array('title' => 'New Post'));
		$Post->create();
		$Post->save($data);
		$Post->create();
		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post'));
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->newest(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')));
		$expected = array('Post' => array(
				'id' => 1,
				'title' => 'Edited Post',
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
				'version_id' => 5));
		$this->assertEquals($expected, $result);
	}

	public function testShadow() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create(array('Post' => array('title' => 'Non Used Post', 'content' => 'Whatever')));
		$Post->save();
		$postId = $Post->id;

		$Post->create(array('Post' => array('title' => 'New Post 1', 'content' => 'nada')));
		$Post->save();

		$Post->save(array('Post' => array('id' => 5, 'title' => 'Edit Post 2')));

		$Post->save(array('Post' => array('id' => 5, 'title' => 'Edit Post 3')));

		$result = $Post->ShadowModel->find('first', array('fields' => array(
				'version_id',
				'id',
				'title',
				'content')));
		$expected = array('Post' => array(
				'version_id' => 7,
				'id' => 5,
				'title' => 'Edit Post 3',
				'content' => 'nada'));
		$this->assertEquals($expected, $result);

		$Post->id = $postId;
		$result = $Post->newest();
		$this->assertEquals($result['Post']['title'], 'Non Used Post');
		$this->assertEquals($result['Post']['version_id'], 4);

		$result = $Post->ShadowModel->find('first', array('conditions' => array('version_id' => 4), 'fields' => array(
				'version_id',
				'id',
				'title',
				'content')));

		$expected = array('Post' => array(
				'version_id' => 4,
				'id' => 4,
				'title' => 'Non Used Post',
				'content' => 'Whatever'));
		$this->assertEquals($expected, $result);
	}

	public function testCurrentPost() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create();
		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post'));
		$Post->save($data);

		$Post->create();
		$data = array('Post' => array('id' => 1, 'title' => 'Re-edited Post'));
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->newest(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')));
		$expected = array('Post' => array(
				'id' => 1,
				'title' => 'Re-edited Post',
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
				'version_id' => 5));
		$this->assertEquals($expected, $result);
	}

	public function testRevisionsPost() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create();
		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post'));
		$Post->save($data);

		$Post->create();
		$data = array('Post' => array('id' => 1, 'title' => 'Re-edited Post'));
		$Post->save($data);
		$Post->create();
		$data = array('Post' => array('id' => 1, 'title' => 'Newest edited Post'));
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->revisions(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')));
		$expected = array(
			0 => array('Post' => array(
					'id' => 1,
					'title' => 'Re-edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 5)),
			1 => array('Post' => array(
					'id' => 1,
					'title' => 'Edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 4), ),
			2 => array('Post' => array(
					'id' => 1,
					'title' => 'Lorem ipsum dolor sit amet',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 1), ));
		$this->assertEquals($expected, $result);

		$Post->id = 1;
		$result = $Post->revisions(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')), true);
		$expected = array(
			0 => array('Post' => array(
					'id' => 1,
					'title' => 'Newest edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 6)),
			1 => array('Post' => array(
					'id' => 1,
					'title' => 'Re-edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 5)),
			2 => array('Post' => array(
					'id' => 1,
					'title' => 'Edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 4), ),
			3 => array('Post' => array(
					'id' => 1,
					'title' => 'Lorem ipsum dolor sit amet',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 1), ));
		$this->assertEquals($expected, $result);
	}

	public function testDiff() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post 1'));
		$Post->save($data);
		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post 2'));
		$Post->save($data);
		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post 3'));
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->diff(null, null, array('fields' => array(
				'version_id',
				'id',
				'title',
				'content')));
		$expected = array('Post' => array(
				'version_id' => array(
					6,
					5,
					4,
					1),
				'id' => 1,
				'title' => array(
					'Edited Post 3',
					'Edited Post 2',
					'Edited Post 1',
					'Lorem ipsum dolor sit amet'),
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'));
		$this->assertEquals($expected, $result);
	}

	public function testDiffMultipleFields() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = array('Post' => array('id' => 1, 'title' => 'Edited title 1'));
		$Post->save($data);
		$data = array('Post' => array('id' => 1, 'content' => 'Edited content'));
		$Post->save($data);
		$data = array('Post' => array('id' => 1, 'title' => 'Edited title 2'));
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->diff(null, null, array('fields' => array(
				'version_id',
				'id',
				'title',
				'content')));
		$expected = array('Post' => array(
				'version_id' => array(
					6,
					5,
					4,
					1),
				'id' => 1,
				'title' => array(
					0 => 'Edited title 2',
					2 => 'Edited title 1',
					3 => 'Lorem ipsum dolor sit amet'),
				'content' => array(1 => 'Edited content', 3 => 'Lorem ipsum dolor sit amet, aliquet feugiat.')));
		$this->assertEquals($expected, $result);
	}

	public function testPrevious() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->id = 1;
		$this->assertSame(array(), $Post->previous());

		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post 2'));
		$Post->save($data);
		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post 3'));
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->previous(array('fields' => array(
				'version_id',
				'id',
				'title')));
		$expected = array('Post' => array(
				'version_id' => 4,
				'id' => 1,
				'title' => 'Edited Post 2'));
		$this->assertEquals($expected, $result);
	}

	public function testUndoEdit() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post 1'));
		$Post->save($data);
		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post 2'));
		$Post->save($data);
		$data = array('Post' => array('id' => 1, 'title' => 'Edited Post 3'));
		$Post->save($data);

		$Post->id = 1;
		$success = $Post->undo();
		$this->assertTrue((bool)$success);

		$result = $Post->find('first', array('fields' => array(
				'id',
				'title',
				'content')));
		$expected = array('Post' => array(
				'id' => 1,
				'title' => 'Edited Post 2',
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.'));
		$this->assertEquals($expected, $result);
	}

	public function testUndoCreate() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create(array('Post' => array('title' => 'New post', 'content' => 'asd')));
		$Post->save();

		$result = $Post->read();
		$this->assertEquals($result['Post']['title'], 'New post');
		$id = $Post->id;

		$Post->undo();

		$Post->id = $id;
		$this->assertEmpty($Post->read());

		$Post->undelete();
		$result = $Post->read();
		$this->assertEquals($result['Post']['title'], 'New post');
	}

	public function testRevertTo() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->save(array('Post' => array('id' => 1, 'title' => 'Edited Post 1')));
		$Post->save(array('Post' => array('id' => 1, 'title' => 'Edited Post 2')));
		$Post->save(array('Post' => array('id' => 1, 'title' => 'Edited Post 3')));

		$Post->id = 1;
		$result = $Post->previous();
		$this->assertEquals($result['Post']['title'], 'Edited Post 2');

		$versionId = $result['Post']['version_id'];
		$result = $Post->revertTo($versionId);
		$this->assertTrue((bool)$result);

		$result = $Post->find('first', array('fields' => array(
				'id',
				'title',
				'content')));
		$this->assertEquals($result['Post']['title'], 'Edited Post 2');
	}

	public function testLimit() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = array('Post' => array('id' => 2, 'title' => 'Edited Post 1'));
		$Post->save($data);
		$data = array('Post' => array('id' => 2, 'title' => 'Edited Post 2'));
		$Post->save($data);
		$data = array('Post' => array('id' => 2, 'title' => 'Edited Post 3'));
		$Post->save($data);
		$data = array('Post' => array('id' => 2, 'title' => 'Edited Post 4'));
		$Post->save($data);
		$data = array('Post' => array('id' => 2, 'title' => 'Edited Post 5'));
		$Post->save($data);
		$data = array('Post' => array('id' => 2, 'title' => 'Edited Post 6'));
		$Post->save($data);

		$data = array('Post' => array('id' => 2, 'title' => 'Edited Post 6'));
		$Post->save($data);

		$Post->id = 2;

		$result = $Post->revisions(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')), true);
		$expected = array(
			0 => array('Post' => array(
					'id' => 2,
					'title' => 'Edited Post 6',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 9)),
			1 => array('Post' => array(
					'id' => 2,
					'title' => 'Edited Post 5',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 8), ),
			2 => array('Post' => array(
					'id' => 2,
					'title' => 'Edited Post 4',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 7)),
			3 => array('Post' => array(
					'id' => 2,
					'title' => 'Edited Post 3',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 6), ),
			4 => array('Post' => array(
					'id' => 2,
					'title' => 'Edited Post 2',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 5)));
		$this->assertEquals($expected, $result);
	}

	public function testTree() {
		$this->loadFixtures('RevisionArticle', 'RevisionArticlesRev');

		$Article = new RevisionArticle();

		$Article->initializeRevisions();

		$Article->save(array('Article' => array('id' => 3, 'content' => 'Re-edited Article')));
		$this->assertNoErrors('Save() with tree problem : %s');

		$Article->moveUp(3);
		$this->assertNoErrors('moveUp() with tree problem : %s');

		$Article->id = 3;
		$result = $Article->newest(array('fields' => array('id', 'version_id')));
		$this->assertEquals($result['Article']['version_id'], 4);

		$Article->create(array(
			'title' => 'midten',
			'content' => 'stuff',
			'parent_id' => 2));
		$Article->save();
		$this->assertNoErrors('Save() with tree problem : %s');

		$result = $Article->find('all', array('fields' => array(
				'id',
				'lft',
				'rght',
				'parent_id')));
		$expected = array(
			'id' => 1,
			'lft' => 1,
			'rght' => 8,
			'parent_id' => null);
		$this->assertEquals($result[0]['Article'], $expected);
		$expected = array(
			'id' => 2,
			'lft' => 4,
			'rght' => 7,
			'parent_id' => 1);
		$this->assertEquals($result[1]['Article'], $expected);
		$expected = array(
			'id' => 3,
			'lft' => 2,
			'rght' => 3,
			'parent_id' => 1);
		$this->assertEquals($result[2]['Article'], $expected);
		$expected = array(
			'id' => 4,
			'lft' => 5,
			'rght' => 6,
			'parent_id' => 2);
		$this->assertEquals($result[3]['Article'], $expected);
	}

	public function testIgnore() {
		$this->loadFixtures('RevisionArticle', 'RevisionArticlesRev');

		$Article = new RevisionArticle();

		$data = array('Article' => array(
				'id' => 3,
				'title' => 'New title',
				'content' => 'Edited'));
		$Article->save($data);
		$data = array('Article' => array('id' => 3, 'title' => 'Re-edited title'));
		$Article->save($data);

		$Article->id = 3;
		$result = $Article->newest(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')));
		$expected = array('Article' => array(
				'id' => 3,
				'title' => 'New title',
				'content' => 'Edited',
				'version_id' => 1));
		$this->assertEquals($expected, $result);
	}

	public function testWithoutShadowTable() {
		$this->loadFixtures('RevisionUser');

		$User = new RevisionUser();

		$data = array('User' => array('id' => 1, 'name' => 'New name'));
		$success = $User->save($data);
		$this->assertNoErrors();
		$this->assertTrue((bool)$success);
	}

	public function testRevertToDate() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = array('Post' => array('id' => 3, 'title' => 'Edited Post 6'));
		$Post->save($data);
		$result = $Post->revertToDate(date('Y-m-d H:i:s', strtotime('yesterday')));
		$this->assertTrue((bool)$result);

		$result = $Post->newest(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')));
		$expected = array('Post' => array(
				'id' => 3,
				'title' => 'Post 3',
				'content' => 'Lorem ipsum dolor sit.',
				'version_id' => 5));
		$this->assertEquals($expected, $result);
	}

	public function testCascade() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionVote', 'RevisionVotesRev');

		$Comment = new RevisionComment();

		$originalComments = $Comment->find('all');

		$data = array('Vote' => array(
				'id' => 3,
				'title' => 'Edited Vote',
				'revision_comment_id' => 1));
		$Comment->Vote->save($data);

		$this->assertTrue((bool)$Comment->Vote->revertToDate('2008-12-09'));
		$Comment->Vote->id = 3;
		$result = $Comment->Vote->newest(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')));

		$expected = array('Vote' => array(
				'id' => 3,
				'title' => 'Stuff',
				'content' => 'Lorem ipsum dolor sit.',
				'version_id' => 5));

		$this->assertEquals($expected, $result);

		$data = array('Comment' => array('id' => 2, 'title' => 'Edited Comment'));
		$Comment->save($data);

		$this->assertTrue((bool)$Comment->revertToDate('2008-12-09'));

		$revertedComments = $Comment->find('all');

		$this->assertEquals($originalComments, $revertedComments);
	}

	public function testCreateRevision() {
		$this->loadFixtures('RevisionArticle', 'RevisionArticlesRev');

		$Article = new RevisionArticle();

		$data = array('Article' => array(
				'id' => 3,
				'title' => 'New title',
				'content' => 'Edited'));
		$Article->save($data);
		$data = array('Article' => array('id' => 3, 'title' => 'Re-edited title'));
		$Article->save($data);

		$Article->id = 3;
		$result = $Article->newest(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')));
		$expected = array('Article' => array(
				'id' => 3,
				'title' => 'New title',
				'content' => 'Edited',
				'version_id' => 1));
		$this->assertEquals($expected, $result);

		$Article->id = 3;
		$this->assertTrue((bool)$Article->createRevision());
		$result = $Article->newest(array('fields' => array(
				'id',
				'title',
				'content',
				'version_id')));
		$expected = array('Article' => array(
				'id' => 3,
				'title' => 'Re-edited title',
				'content' => 'Edited',
				'version_id' => 2));
		$this->assertEquals($expected, $result);
	}

	public function testUndelete() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->id = 3;
		$result = $Post->undelete();
		$this->assertFalse($result);

		$Post->delete(3);

		$result = $Post->find('count', array('conditions' => array('id' => 3)));
		$this->assertEquals($result, 0);

		$Post->id = 3;
		$Post->undelete();

		$result = $Post->find('first', array('conditions' => array('id' => 3), 'fields' => array(
				'id',
				'title',
				'content')));

		$expected = array('Post' => array(
				'id' => 3,
				'title' => 'Post 3',
				'content' => 'Lorem ipsum dolor sit.'));
		$this->assertEquals($expected, $result);
	}

	public function testUndeleteCallbacks() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->id = 3;
		$result = $Post->undelete();
		$this->assertFalse($result);

		$Post->delete(3);

		$result = $Post->find('first', array('conditions' => array('id' => 3)));
		$this->assertEmpty($result);

		$Post->id = 3;
		$this->assertTrue($Post->undelete());
		$this->assertTrue($Post->beforeUndelete);
		$this->assertTrue($Post->afterUndelete);

		$result = $Post->find('first', array('conditions' => array('id' => 3)));

		$expected = array('Post' => array(
				'id' => 3,
				'title' => 'Post 3',
				'content' => 'Lorem ipsum dolor sit.',
				));
		$this->assertEquals($expected, $result);
		$this->assertNoErrors();
	}

	public function testUndeleteTree1() {
		$this->loadFixtures('RevisionArticle', 'RevisionArticlesRev');

		$Article = new RevisionArticle();

		$Article->initializeRevisions();

		$Article->delete(3);

		$Article->id = 3;
		$Article->undelete();

		$result = $Article->find('all');

		$this->assertEquals(sizeof($result), 3);
		$this->assertEquals($result[0]['Article']['lft'], 1);
		$this->assertEquals($result[0]['Article']['rght'], 6);

		$this->assertEquals($result[1]['Article']['lft'], 2);
		$this->assertEquals($result[1]['Article']['rght'], 3);

		$this->assertEquals($result[2]['Article']['id'], 3);
		$this->assertEquals($result[2]['Article']['lft'], 4);
		$this->assertEquals($result[2]['Article']['rght'], 5);
	}

	public function testUndeleteTree2() {
		$this->loadFixtures('RevisionArticle', 'RevisionArticlesRev');

		$Article = new RevisionArticle();

		$Article->initializeRevisions();

		$Article->create(array(
			'title' => 'første barn',
			'content' => 'stuff',
			'parent_id' => 3,
			'user_id' => 1));
		$Article->save();
		$Article->create(array(
			'title' => 'andre barn',
			'content' => 'stuff',
			'parent_id' => 4,
			'user_id' => 1));
		$Article->save();

		$Article->delete(3);

		$Article->id = 3;
		$Article->undelete();

		$result = $Article->find('all');
		// Test that children are also "returned" to their undeleted father
		$this->assertEquals(sizeof($result), 5);
		$this->assertEquals($result[0]['Article']['lft'], 1);
		$this->assertEquals($result[0]['Article']['rght'], 10);

		$this->assertEquals($result[1]['Article']['lft'], 2);
		$this->assertEquals($result[1]['Article']['rght'], 3);

		$this->assertEquals($result[2]['Article']['id'], 3);
		$this->assertEquals($result[2]['Article']['lft'], 4);
		$this->assertEquals($result[2]['Article']['rght'], 9);

		$this->assertEquals($result[3]['Article']['id'], 4);
		$this->assertEquals($result[3]['Article']['lft'], 5);
		$this->assertEquals($result[3]['Article']['rght'], 8);

		$this->assertEquals($result[4]['Article']['id'], 5);
		$this->assertEquals($result[4]['Article']['lft'], 6);
		$this->assertEquals($result[4]['Article']['rght'], 7);
	}

	public function testInitializeRevisionsWithLimit() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev', 'RevisionArticle', 'RevisionArticlesRev', 'RevisionComment',
			'RevisionCommentsRev', 'RevisionCommentsRevisionTag', 'RevisionVote', 'RevisionVotesRev', 'RevisionTag',
			'RevisionTagsRev');

		$Comment = new RevisionComment();
		$Post = new RevisionPost();
		$Article = new RevisionArticle();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag', 'with' =>
						'CommentsTag'))), false);

		$this->assertFalse($Post->initializeRevisions());
		$this->assertTrue($Article->initializeRevisions());
		$this->assertFalse($Comment->initializeRevisions());
		$this->assertFalse($Comment->Vote->initializeRevisions());
		$this->assertFalse($Comment->Tag->initializeRevisions());
	}

	public function testInitializeRevisions() {
		$this->loadFixtures('RevisionPost');

		$Post = new RevisionPost();

		$this->assertTrue($Post->initializeRevisions(2));

		$result = $Post->ShadowModel->find('all');

		$this->assertEquals(sizeof($result), 3);
	}

	public function testRevertAll() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->save(array('id' => 1, 'title' => 'tullball1'));
		$Post->save(array('id' => 3, 'title' => 'tullball3'));
		$Post->create(array('title' => 'new post', 'content' => 'stuff'));
		$Post->save();

		$result = $Post->find('all');
		$this->assertEquals($result[0]['Post']['title'], 'tullball1');
		$this->assertEquals($result[1]['Post']['title'], 'Post 2');
		$this->assertEquals($result[2]['Post']['title'], 'tullball3');
		$this->assertEquals($result[3]['Post']['title'], 'new post');

		$this->assertTrue($Post->revertAll(array('date' => date('Y-m-d H:i:s', strtotime('yesterday')))));

		$result = $Post->find('all');
		$this->assertEquals($result[0]['Post']['title'], 'Lorem ipsum dolor sit amet');
		$this->assertEquals($result[1]['Post']['title'], 'Post 2');
		$this->assertEquals($result[2]['Post']['title'], 'Post 3');
		$this->assertEquals(sizeof($result), 3);
	}

	public function testRevertAllConditions() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->save(array('id' => 1, 'title' => 'tullball1'));
		$Post->save(array('id' => 3, 'title' => 'tullball3'));
		$Post->create();
		$Post->save(array('title' => 'new post', 'content' => 'stuff'));

		$result = $Post->find('all');
		$this->assertEquals($result[0]['Post']['title'], 'tullball1');
		$this->assertEquals($result[1]['Post']['title'], 'Post 2');
		$this->assertEquals($result[2]['Post']['title'], 'tullball3');
		$this->assertEquals($result[3]['Post']['title'], 'new post');

		$this->assertTrue($Post->revertAll(array('conditions' => array('Post.id' => array(
					1,
					2,
					4)), 'date' => date('Y-m-d H:i:s', strtotime('yesterday')))));

		$result = $Post->find('all');
		$this->assertEquals($result[0]['Post']['title'], 'Lorem ipsum dolor sit amet');
		$this->assertEquals($result[1]['Post']['title'], 'Post 2');
		$this->assertEquals($result[2]['Post']['title'], 'tullball3');
		$this->assertEquals(sizeof($result), 3);
	}

	public function testOnWithModel() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag', 'with' =>
						'CommentsTag'))), false);
		$result = $Comment->find('first', array('contain' => array('Tag' => array('id', 'title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Trick');
	}

	public function testHABTMRelatedUndoed() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag', 'with' =>
						'CommentsTag'))), false);
		$Comment->Tag->id = 3;
		$Comment->Tag->undo();
		$result = $Comment->find('first', array('contain' => array('Tag' => array('id', 'title'))));
		$this->assertEquals($result['Tag'][2]['title'], 'Tricks');
	}

	public function testOnWithModelUndoed() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag', 'with' =>
						'CommentsTag'))), false);
		$Comment->CommentsTag->delete(3);
		$result = $Comment->find('first', array('contain' => array('Tag' => array('id', 'title'))));
		$this->assertEquals(sizeof($result['Tag']), 2);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');

		$Comment->CommentsTag->id = 3;
		$this->assertTrue($Comment->CommentsTag->undelete(), 'Undelete unsuccessful');

		$result = $Comment->find('first', array('contain' => array('Tag' => array('id', 'title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Trick');
		$this->assertNoErrors('Third Tag not back : %s');
	}

	public function testHabtmRevSave() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$result = $Comment->find('first', array('contain' => array('Tag' => array('id', 'title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Trick');

		$currentIds = Set::extract($result, 'Tag.{n}.id');
		$expected = implode(',', $currentIds);
		$Comment->id = 1;
		$result = $Comment->newest();
		$this->assertEquals($expected, $result['Comment']['Tag']);

		$Comment->save(array('Comment' => array('id' => 1), 'Tag' => array('Tag' => array(2, 4))));

		$result = $Comment->find('first', array('contain' => array('Tag' => array('id', 'title'))));
		$this->assertEquals(sizeof($result['Tag']), 2);
		//TODO: assert
		$this->assertEquals($result['Tag'][0]['title'], 'News');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');

		$currentIds = Set::extract($result, 'Tag.{n}.id');
		$expected = implode(',', $currentIds);
		$Comment->id = 1;
		$result = $Comment->newest();
		$this->assertEquals(4, $result['Comment']['version_id']);
		$this->assertEquals($expected, $result['Comment']['Tag']);
	}

	public function testHabtmRevCreate() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$result = $Comment->find('first', array('contain' => array('Tag' => array('id', 'title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Trick');

		$Comment->create(array('Comment' => array('title' => 'Comment 4'), 'Tag' => array('Tag' => array(2, 4))));

		$Comment->save();

		$result = $Comment->newest();
		$this->assertEquals('2,4', $result['Comment']['Tag']);
	}

	public function testHabtmRevIgnore() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->Behaviors->unload('Revision');
		$Comment->Behaviors->load('Revision', array('ignore' => array('Tag')));

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$Comment->id = 1;
		$originalResult = $Comment->newest();

		$Comment->save(array('Comment' => array('id' => 1), 'Tag' => array('Tag' => array(2, 4))));

		$result = $Comment->newest();
		$this->assertEquals($originalResult, $result);
	}

	public function testHabtmRevUndo() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$Comment->save(array('Comment' => array('id' => 1, 'title' => 'edit'), 'Tag' => array('Tag' => array(2, 4))));

		$Comment->id = 1;
		$Comment->undo();
		$result = $Comment->find('first', array('recursive' => 1)); //'contain' => array('Tag' => array('id','title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		//TODO: assert
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Fun');
		$this->assertNoErrors('3 tags : %s');
	}

	public function testHabtmRevUndoJustHabtmChanges() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$Comment->save(array('Comment' => array('id' => 1), 'Tag' => array('Tag' => array(2, 4))));

		$Comment->id = 1;
		$Comment->undo();
		$result = $Comment->find('first', array('recursive' => 1)); //'contain' => array('Tag' => array('id','title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Fun');
		$this->assertNoErrors('3 tags : %s');
	}

	public function testHabtmRevRevert() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$Comment->save(array('Comment' => array('id' => 1), 'Tag' => array('Tag' => array(2, 4))));

		$Comment->id = 1;
		$Comment->revertTo(1);

		$result = $Comment->find('first', array('recursive' => 1)); //'contain' => array('Tag' => array('id','title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Fun');
		$this->assertNoErrors('3 tags : %s');
	}

	public function testRevertToHabtm2() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$commentOne = $Comment->find('first', array('conditions' => array('Comment.id' => 1), 'contain' => 'Tag'));
		$this->assertEquals($commentOne['Comment']['title'], 'Comment 1');
		$this->assertEquals(Set::extract($commentOne, 'Tag.{n}.id'), array(
			1,
			2,
			3));
		$Comment->id = 1;
		$revOne = $Comment->newest();
		$this->assertEquals($revOne['Comment']['title'], 'Comment 1');
		$this->assertEquals($revOne['Comment']['Tag'], '1,2,3');
		$versionId = $revOne['Comment']['version_id'];

		$Comment->create(array('Comment' => array('id' => 1, 'title' => 'Edited')));
		$Comment->save();

		$commentOne = $Comment->find('first', array('conditions' => array('Comment.id' => 1), 'contain' => 'Tag'));
		$this->assertEquals($commentOne['Comment']['title'], 'Edited');
		$result = Set::extract($commentOne, 'Tag.{n}.id');
		$expected = array(
			1,
			2,
			3);
		$this->assertEquals($expected, $result);
		$Comment->id = 1;
		$revOne = $Comment->newest();
		$this->assertEquals($revOne['Comment']['title'], 'Edited');
		$this->assertEquals($revOne['Comment']['Tag'], '1,2,3');

		$Comment->revertTo(1);

		$commentOne = $Comment->find('first', array('conditions' => array('Comment.id' => 1), 'contain' => 'Tag'));
		$this->assertEquals($commentOne['Comment']['title'], 'Comment 1');
		$result = Set::extract($commentOne, 'Tag.{n}.id');
		//TODO: assert
		$this->assertEquals($result, array(
			3,
			2,
			1));
		$Comment->id = 1;
		$revOne = $Comment->newest();
		$this->assertEquals($revOne['Comment']['title'], 'Comment 1');
		$this->assertEquals($revOne['Comment']['Tag'], '1,2,3');
	}

	public function testHabtmRevRevertToDate() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$Comment->save(array('Comment' => array('id' => 1), 'Tag' => array('Tag' => array(2, 4))));

		$Comment->id = 1;
		$Comment->revertToDate(date('Y-m-d H:i:s', strtotime('yesterday')));

		$result = $Comment->find('first', array('recursive' => 1));
		$this->assertEquals(sizeof($result['Tag']), 3);
		//TODO: assert
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Fun');
		$this->assertNoErrors('3 tags : %s');
	}

	public function testRevertToTheTagsCommentHadBefore() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$result = $Comment->find('first', array('conditions' => array('Comment.id' => 2), 'contain' => array('Tag' => array('id',
						'title'))));
		$this->assertEquals(sizeof($result['Tag']), 2);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Trick');

		$Comment->save(array('Comment' => array('id' => 2), 'Tag' => array('Tag' => array(
					2,
					3,
					4))));

		$result = $Comment->find('first', array('conditions' => array('Comment.id' => 2), 'contain' => array('Tag' => array('id',
						'title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		//TODO: assert
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'News');

		// revert Tags on comment logic
		$Comment->id = 2;
		$this->assertTrue((bool)$Comment->revertToDate(date('Y-m-d H:i:s', strtotime('yesterday'))),
			'revertHabtmToDate unsuccessful : %s');

		$result = $Comment->find('first', array('conditions' => array('Comment.id' => 2), 'contain' => array('Tag' => array('id',
						'title'))));
		$this->assertEquals(sizeof($result['Tag']), 2);
		//TODO: assert
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Fun');
	}

	public function testSaveWithOutTags() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag'))), false);

		$Comment->id = 1;
		$newest = $Comment->newest();

		$Comment->save(array('Comment' => array('id' => 1, 'title' => 'spam')));

		$result = $Comment->newest();
		$this->assertEquals($newest['Comment']['Tag'], $result['Comment']['Tag']);
	}

	public function testRevertToDeletedTag() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(array('hasAndBelongsToMany' => array('Tag' => array('className' => 'RevisionTag', 'with' =>
						'CommentsTag'))), false);

		$Comment->Tag->delete(1);

		$result = $Comment->ShadowModel->find('all', array('conditions' => array('version_id' => array(4, 5))));
		//TODO: assert/fixme
		//debug($result);
		//$this->assertEquals($result[0]['Comment']['Tag'], '3');
		//$this->assertEquals($result[1]['Comment']['Tag'], '2,3');
	}

	/**
	 * @expectedException PHPUNIT_FRAMEWORK_ERROR_WARNING
	 */
	public function testBadKittyForgotId() {
		$Comment = new RevisionComment();

		$this->assertNull($Comment->createRevision(), 'createRevision() : %s');
		$this->assertError(true);
		$this->assertNull($Comment->diff(), 'diff() : %s');
		$this->assertError(true);
		$this->assertNull($Comment->undelete(), 'undelete() : %s');
		$this->assertError(true);
		$this->assertNull($Comment->undo(), 'undo() : %s');
		$this->assertError(true);
		$this->assertNull($Comment->newest(), 'newest() : %s');
		$this->assertError(true);
		$this->assertNull($Comment->oldest(), 'oldest() : %s');
		$this->assertError(true);
		$this->assertNull($Comment->previous(), 'previous() : %s');
		$this->assertError(true);
		$this->assertNull($Comment->revertTo(10), 'revertTo() : %s');
		$this->assertError(true);
		$this->assertNull($Comment->revertToDate(date('Y-m-d H:i:s', strtotime('yesterday')), 'revertTo() : %s'));
		$this->assertError(true);
		$this->assertNull($Comment->revisions(), 'revisions() : %s');
		$this->assertError(true);
	}

	public function testBadKittyMakesUpStuff() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->id = 1;
		$this->assertFalse($Comment->revertTo(10), 'revertTo() : %s');
		$this->assertSame(array(), $Comment->diff(1, 4), 'diff() between existing and non-existing : %s');
		$this->assertSame(array(), $Comment->diff(10, 4), 'diff() between two non existing : %s');
	}

	/**
	 * @expectedException PHPUNIT_FRAMEWORK_ERROR_WARNING
	 */
	public function testMethodsOnNonRevisedModel() {
		$User = new RevisionUser();

		$User->id = 1;
		$this->assertFalse($User->createRevision());
		$this->assertError();
		$this->assertNull($User->diff());
		$this->assertError();
		$this->assertFalse($User->initializeRevisions());
		$this->assertError();
		$this->assertNull($User->newest());
		$this->assertError();
		$this->assertNull($User->oldest());
		$this->assertError();
		$this->assertFalse($User->previous());
		$this->assertError();
		$this->assertFalse($User->revertAll(array('date' => '1970-01-01')));
		$this->assertError();
		$this->assertFalse($User->revertTo(2));
		$this->assertError();
		$this->assertTrue((bool)$User->revertToDate('1970-01-01'));
		$this->assertNoErrors();
		$this->assertFalse($User->revisions());
		$this->assertError();
		$this->assertFalse($User->undo());
		$this->assertError();
		$this->assertFalse($User->undelete());
		$this->assertError();
		$this->assertFalse($User->updateRevisions());
		$this->assertError();
	}

	public function testRevisions() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create(array('Post' => array('title' => 'Stuff (1)', 'content' => 'abc')));
		$Post->save();
		$postID = $Post->id;

		$Post->data = null;
		$Post->id = null;
		$Post->save(array('Post' => array('id' => $postID, 'title' => 'Things (2)')));

		$Post->data = null;
		$Post->id = null;
		$Post->save(array('Post' => array('id' => $postID, 'title' => 'Machines (3)')));

		$Post->bindModel(array('hasMany' => array('Revision' => array(
					'className' => 'RevisionPostsRev',
					'foreignKey' => 'id',
					'order' => 'version_id DESC'))));
		$result = $Post->read(null, $postID);
		$this->assertEquals('Machines (3)', $result['Post']['title']);
		$this->assertSame(3, sizeof($result['Revision']));
		$this->assertEquals('Machines (3)', $result['Revision'][0]['title']);
		$this->assertEquals('Things (2)', $result['Revision'][1]['title']);
		$this->assertEquals('Stuff (1)', $result['Revision'][2]['title']);

		$result = $Post->revisions();
		$this->assertSame(2, sizeof($result));
		$this->assertEquals('Things (2)', $result[0]['Post']['title']);
		$this->assertEquals('Stuff (1)', $result[1]['Post']['title']);

		$result = $Post->revisions(array(), true);
		$this->assertSame(3, sizeof($result));
		$this->assertEquals('Machines (3)', $result[0]['Post']['title']);
		$this->assertEquals('Things (2)', $result[1]['Post']['title']);
		$this->assertEquals('Stuff (1)', $result[2]['Post']['title']);
	}

}

class RevisionTestModel extends CakeTestModel {

	public $logableAction;
}

class RevisionPost extends RevisionTestModel {

	public $name = 'RevisionPost';

	public $alias = 'Post';

	public $actsAs = array('Revision' => array('limit' => 5));

	public function beforeUndelete() {
		$this->beforeUndelete = true;
		return true;
	}

	public function afterUndelete() {
		$this->afterUndelete = true;
		return true;
	}
}

class RevisionArticle extends RevisionTestModel {

	public $name = 'RevisionArticle';

	public $alias = 'Article';

	public $actsAs = array('Tree', 'Revision' => array('ignore' => array('title')));

	/**
	 * Example of using this callback to undelete children
	 * of a deleted node.
	 */
	public function afterUndelete() {
		$formerChildren = $this->ShadowModel->find('list', array(
			'conditions' => array('parent_id' => $this->id),
			'distinct' => true,
			'order' => 'version_created DESC, version_id DESC'));
		foreach (array_keys($formerChildren) as $cid) {
			$this->id = $cid;
			$this->undelete();
		}
	}
}

class RevisionUser extends RevisionTestModel {

	public $name = 'RevisionUser';

	public $alias = 'User';

	public $actsAs = array('Revision');
}

class RevisionComment extends RevisionTestModel {

	public $name = 'RevisionComment';

	public $alias = 'Comment';

	public $actsAs = array('Containable', 'Revision');

	public $hasMany = array('Vote' => array(
			'className' => 'RevisionVote',
			'foreignKey' => 'revision_comment_id',
			'dependent' => true));
}

class RevisionVote extends RevisionTestModel {

	public $name = 'RevisionVote';

	public $alias = 'Vote';

	public $actsAs = array('Revision');
}

class RevisionTag extends RevisionTestModel {

	public $name = 'RevisionTag';

	public $alias = 'Tag';

	public $actsAs = array('Revision');

	public $hasAndBelongsToMany = array('Comment' => array('className' => 'RevisionComment'));
}

class CommentsTag extends RevisionTestModel {

	public $name = 'CommentsTag';

	public $useTable = 'revision_comments_revision_tags';

	public $actsAs = array('Revision');
}
