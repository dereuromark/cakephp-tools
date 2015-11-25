<?php
App::uses('RevisionBehavior', 'Tools.Model/Behavior');
App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

class RevisionBehaviorTest extends CakeTestCase {

	public $RevisionBehavior;

	public $autoFixtures = false;

	public $fixtures = [
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
		'plugin.tools.revision_tags_rev'];

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

	/**
	 * RevisionBehaviorTest::testSavePost()
	 *
	 * @return void
	 */
	public function testSavePost() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = ['Post' => ['title' => 'New Post', 'content' => 'First post!']];
		$Post->save($data);
		$Post->id = 4;
		$result = $Post->newest(['fields' => [
				'id',
				'title',
				'content',
				'version_id']]);
		$expected = ['Post' => [
				'id' => 4,
				'title' => 'New Post',
				'content' => 'First post!',
				'version_id' => 4]];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testSaveWithoutChange()
	 *
	 * @return void
	 */
	public function testSaveWithoutChange() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->id = 1;
		$this->assertTrue((bool)$Post->createRevision());

		$Post->id = 1;
		$count = $Post->ShadowModel->find('count', ['conditions' => ['id' => 1]]);
		$this->assertEquals($count, 2);

		$Post->id = 1;
		$data = $Post->read();
		$Post->save($data);

		$Post->id = 1;
		$count = $Post->ShadowModel->find('count', ['conditions' => ['id' => 1]]);
		$this->assertEquals($count, 2);
	}

	/**
	 * RevisionBehaviorTest::testEditPost()
	 *
	 * @return void
	 */
	public function testEditPost() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = ['Post' => ['title' => 'New Post']];
		$Post->create();
		$Post->save($data);
		$Post->create();
		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post']];
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->newest(['fields' => [
				'id',
				'title',
				'content',
				'version_id']]);
		$expected = ['Post' => [
				'id' => 1,
				'title' => 'Edited Post',
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
				'version_id' => 5]];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testShadow()
	 *
	 * @return void
	 */
	public function testShadow() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create(['Post' => ['title' => 'Non Used Post', 'content' => 'Whatever']]);
		$Post->save();
		$postId = $Post->id;

		$Post->create(['Post' => ['title' => 'New Post 1', 'content' => 'nada']]);
		$Post->save();

		$Post->save(['Post' => ['id' => 5, 'title' => 'Edit Post 2']]);

		$Post->save(['Post' => ['id' => 5, 'title' => 'Edit Post 3']]);

		$result = $Post->ShadowModel->find('first', ['fields' => [
				'version_id',
				'id',
				'title',
				'content']]);
		$expected = ['Post' => [
				'version_id' => 7,
				'id' => 5,
				'title' => 'Edit Post 3',
				'content' => 'nada']];
		$this->assertEquals($expected, $result);

		$Post->id = $postId;
		$result = $Post->newest();
		$this->assertEquals($result['Post']['title'], 'Non Used Post');
		$this->assertEquals($result['Post']['version_id'], 4);

		$result = $Post->ShadowModel->find('first', ['conditions' => ['version_id' => 4], 'fields' => [
				'version_id',
				'id',
				'title',
				'content']]);

		$expected = ['Post' => [
				'version_id' => 4,
				'id' => 4,
				'title' => 'Non Used Post',
				'content' => 'Whatever']];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testCurrentPost()
	 *
	 * @return void
	 */
	public function testCurrentPost() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create();
		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post']];
		$Post->save($data);

		$Post->create();
		$data = ['Post' => ['id' => 1, 'title' => 'Re-edited Post']];
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->newest(['fields' => [
				'id',
				'title',
				'content',
				'version_id']]);
		$expected = ['Post' => [
				'id' => 1,
				'title' => 'Re-edited Post',
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
				'version_id' => 5]];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testRevisionsPost()
	 *
	 * @return void
	 */
	public function testRevisionsPost() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create();
		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post']];
		$Post->save($data);

		$Post->create();
		$data = ['Post' => ['id' => 1, 'title' => 'Re-edited Post']];
		$Post->save($data);
		$Post->create();
		$data = ['Post' => ['id' => 1, 'title' => 'Newest edited Post']];
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->revisions(['fields' => [
				'id',
				'title',
				'content',
				'version_id']]);
		$expected = [
			0 => ['Post' => [
					'id' => 1,
					'title' => 'Re-edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 5]],
			1 => ['Post' => [
					'id' => 1,
					'title' => 'Edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 4], ],
			2 => ['Post' => [
					'id' => 1,
					'title' => 'Lorem ipsum dolor sit amet',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 1], ]];
		$this->assertEquals($expected, $result);

		$Post->id = 1;
		$result = $Post->revisions(['fields' => [
				'id',
				'title',
				'content',
				'version_id']], true);
		$expected = [
			0 => ['Post' => [
					'id' => 1,
					'title' => 'Newest edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 6]],
			1 => ['Post' => [
					'id' => 1,
					'title' => 'Re-edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 5]],
			2 => ['Post' => [
					'id' => 1,
					'title' => 'Edited Post',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 4], ],
			3 => ['Post' => [
					'id' => 1,
					'title' => 'Lorem ipsum dolor sit amet',
					'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
					'version_id' => 1], ]];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testDiff()
	 *
	 * @return void
	 */
	public function testDiff() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post 1']];
		$Post->save($data);
		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post 2']];
		$Post->save($data);
		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post 3']];
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->diff(null, null, ['fields' => [
				'version_id',
				'id',
				'title',
				'content']]);
		$expected = ['Post' => [
				'version_id' => [
					6,
					5,
					4,
					1],
				'id' => 1,
				'title' => [
					'Edited Post 3',
					'Edited Post 2',
					'Edited Post 1',
					'Lorem ipsum dolor sit amet'],
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.']];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testDiffMultipleFields()
	 *
	 * @return void
	 */
	public function testDiffMultipleFields() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = ['Post' => ['id' => 1, 'title' => 'Edited title 1']];
		$Post->save($data);
		$data = ['Post' => ['id' => 1, 'content' => 'Edited content']];
		$Post->save($data);
		$data = ['Post' => ['id' => 1, 'title' => 'Edited title 2']];
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->diff(null, null, ['fields' => [
				'version_id',
				'id',
				'title',
				'content']]);
		$expected = ['Post' => [
				'version_id' => [
					6,
					5,
					4,
					1],
				'id' => 1,
				'title' => [
					0 => 'Edited title 2',
					2 => 'Edited title 1',
					3 => 'Lorem ipsum dolor sit amet'],
				'content' => [1 => 'Edited content', 3 => 'Lorem ipsum dolor sit amet, aliquet feugiat.']]];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testPrevious()
	 *
	 * @return void
	 */
	public function testPrevious() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->id = 1;
		$this->assertSame([], $Post->previous());

		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post 2']];
		$Post->save($data);
		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post 3']];
		$Post->save($data);

		$Post->id = 1;
		$result = $Post->previous(['fields' => [
				'version_id',
				'id',
				'title']]);
		$expected = ['Post' => [
				'version_id' => 4,
				'id' => 1,
				'title' => 'Edited Post 2']];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testUndoEdit()
	 *
	 * @return void
	 */
	public function testUndoEdit() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post 1']];
		$Post->save($data);
		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post 2']];
		$Post->save($data);
		$data = ['Post' => ['id' => 1, 'title' => 'Edited Post 3']];
		$Post->save($data);

		$Post->id = 1;
		$success = $Post->undo();
		$this->assertTrue((bool)$success);

		$result = $Post->find('first', ['fields' => [
				'id',
				'title',
				'content']]);
		$expected = ['Post' => [
				'id' => 1,
				'title' => 'Edited Post 2',
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat.']];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testUndoCreate()
	 *
	 * @return void
	 */
	public function testUndoCreate() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create(['Post' => ['title' => 'New post', 'content' => 'asd']]);
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

	/**
	 * RevisionBehaviorTest::testRevertTo()
	 *
	 * @return void
	 */
	public function testRevertTo() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->save(['Post' => ['id' => 1, 'title' => 'Edited Post 1']]);
		$Post->save(['Post' => ['id' => 1, 'title' => 'Edited Post 2']]);
		$Post->save(['Post' => ['id' => 1, 'title' => 'Edited Post 3']]);

		$Post->id = 1;
		$result = $Post->previous();
		$this->assertEquals($result['Post']['title'], 'Edited Post 2');

		$versionId = $result['Post']['version_id'];
		$result = $Post->revertTo($versionId);
		$this->assertTrue((bool)$result);

		$result = $Post->find('first', ['fields' => [
				'id',
				'title',
				'content']]);
		$this->assertEquals($result['Post']['title'], 'Edited Post 2');
	}

	/**
	 * RevisionBehaviorTest::testLimit()
	 *
	 * @return void
	 */
	public function testLimit() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = ['Post' => ['id' => 2, 'title' => 'Edited Post 1']];
		$Post->save($data);
		$data = ['Post' => ['id' => 2, 'title' => 'Edited Post 2']];
		$Post->save($data);
		$data = ['Post' => ['id' => 2, 'title' => 'Edited Post 3']];
		$Post->save($data);
		$data = ['Post' => ['id' => 2, 'title' => 'Edited Post 4']];
		$Post->save($data);
		$data = ['Post' => ['id' => 2, 'title' => 'Edited Post 5']];
		$Post->save($data);
		$data = ['Post' => ['id' => 2, 'title' => 'Edited Post 6']];
		$Post->save($data);

		$data = ['Post' => ['id' => 2, 'title' => 'Edited Post 6']];
		$Post->save($data);

		$Post->id = 2;

		$result = $Post->revisions(['fields' => [
				'id',
				'title',
				'content',
				'version_id']], true);
		$expected = [
			0 => ['Post' => [
					'id' => 2,
					'title' => 'Edited Post 6',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 9]],
			1 => ['Post' => [
					'id' => 2,
					'title' => 'Edited Post 5',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 8], ],
			2 => ['Post' => [
					'id' => 2,
					'title' => 'Edited Post 4',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 7]],
			3 => ['Post' => [
					'id' => 2,
					'title' => 'Edited Post 3',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 6], ],
			4 => ['Post' => [
					'id' => 2,
					'title' => 'Edited Post 2',
					'content' => 'Lorem ipsum dolor sit.',
					'version_id' => 5]]];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testTree()
	 *
	 * @return void
	 */
	public function testTree() {
		$this->loadFixtures('RevisionArticle', 'RevisionArticlesRev');

		$Article = new RevisionArticle();

		$Article->initializeRevisions();

		$Article->save(['Article' => ['id' => 3, 'content' => 'Re-edited Article']]);
		$this->assertNoErrors('Save() with tree problem : %s');

		$Article->moveUp(3);
		$this->assertNoErrors('moveUp() with tree problem : %s');

		$Article->id = 3;
		$result = $Article->newest(['fields' => ['id', 'version_id']]);
		$this->assertEquals($result['Article']['version_id'], 4);

		$Article->create([
			'title' => 'midten',
			'content' => 'stuff',
			'parent_id' => 2]);
		$Article->save();
		$this->assertNoErrors('Save() with tree problem : %s');

		$result = $Article->find('all', ['fields' => [
				'id',
				'lft',
				'rght',
				'parent_id']]);
		$expected = [
			'id' => 1,
			'lft' => 1,
			'rght' => 8,
			'parent_id' => null];
		$this->assertEquals($result[0]['Article'], $expected);
		$expected = [
			'id' => 2,
			'lft' => 4,
			'rght' => 7,
			'parent_id' => 1];
		$this->assertEquals($result[1]['Article'], $expected);
		$expected = [
			'id' => 3,
			'lft' => 2,
			'rght' => 3,
			'parent_id' => 1];
		$this->assertEquals($result[2]['Article'], $expected);
		$expected = [
			'id' => 4,
			'lft' => 5,
			'rght' => 6,
			'parent_id' => 2];
		$this->assertEquals($result[3]['Article'], $expected);
	}

	/**
	 * RevisionBehaviorTest::testIgnore()
	 *
	 * @return void
	 */
	public function testIgnore() {
		$this->loadFixtures('RevisionArticle', 'RevisionArticlesRev');

		$Article = new RevisionArticle();

		$data = ['Article' => [
				'id' => 3,
				'title' => 'New title',
				'content' => 'Edited']];
		$Article->save($data);
		$data = ['Article' => ['id' => 3, 'title' => 'Re-edited title']];
		$Article->save($data);

		$Article->id = 3;
		$result = $Article->newest(['fields' => [
				'id',
				'title',
				'content',
				'version_id']]);
		$expected = ['Article' => [
				'id' => 3,
				'title' => 'New title',
				'content' => 'Edited',
				'version_id' => 1]];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testWithoutShadowTable()
	 *
	 * @return void
	 */
	public function testWithoutShadowTable() {
		$this->loadFixtures('RevisionUser');

		$User = new RevisionUser();

		$data = ['User' => ['id' => 1, 'name' => 'New name']];
		$success = $User->save($data);
		$this->assertNoErrors();
		$this->assertTrue((bool)$success);
	}

	/**
	 * RevisionBehaviorTest::testRevertToDate()
	 *
	 * @return void
	 */
	public function testRevertToDate() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$data = ['Post' => ['id' => 3, 'title' => 'Edited Post 6']];
		$Post->save($data);
		$result = $Post->revertToDate(date('Y-m-d H:i:s', strtotime('yesterday')));
		$this->assertTrue((bool)$result);

		$result = $Post->newest(['fields' => [
				'id',
				'title',
				'content',
				'version_id']]);
		$expected = ['Post' => [
				'id' => 3,
				'title' => 'Post 3',
				'content' => 'Lorem ipsum dolor sit.',
				'version_id' => 5]];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testCascade()
	 *
	 * @return void
	 */
	public function testCascade() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionVote', 'RevisionVotesRev');

		$Comment = new RevisionComment();

		$originalComments = $Comment->find('all');

		$data = ['Vote' => [
				'id' => 3,
				'title' => 'Edited Vote',
				'revision_comment_id' => 1]];
		$Comment->Vote->save($data);

		$this->assertTrue((bool)$Comment->Vote->revertToDate('2008-12-09'));
		$Comment->Vote->id = 3;
		$result = $Comment->Vote->newest(['fields' => [
				'id',
				'title',
				'content',
				'version_id']]);

		$expected = ['Vote' => [
				'id' => 3,
				'title' => 'Stuff',
				'content' => 'Lorem ipsum dolor sit.',
				'version_id' => 5]];

		$this->assertEquals($expected, $result);

		$data = ['Comment' => ['id' => 2, 'title' => 'Edited Comment']];
		$Comment->save($data);

		$this->assertTrue((bool)$Comment->revertToDate('2008-12-09'));

		$revertedComments = $Comment->find('all');

		$this->assertEquals($originalComments, $revertedComments);
	}

	/**
	 * RevisionBehaviorTest::testCreateRevision()
	 *
	 * @return void
	 */
	public function testCreateRevision() {
		$this->loadFixtures('RevisionArticle', 'RevisionArticlesRev');

		$Article = new RevisionArticle();

		$data = ['Article' => [
				'id' => 3,
				'title' => 'New title',
				'content' => 'Edited']];
		$Article->save($data);
		$data = ['Article' => ['id' => 3, 'title' => 'Re-edited title']];
		$Article->save($data);

		$Article->id = 3;
		$result = $Article->newest(['fields' => [
				'id',
				'title',
				'content',
				'version_id']]);
		$expected = ['Article' => [
				'id' => 3,
				'title' => 'New title',
				'content' => 'Edited',
				'version_id' => 1]];
		$this->assertEquals($expected, $result);

		$Article->id = 3;
		$this->assertTrue((bool)$Article->createRevision());
		$result = $Article->newest(['fields' => [
				'id',
				'title',
				'content',
				'version_id']]);
		$expected = ['Article' => [
				'id' => 3,
				'title' => 'Re-edited title',
				'content' => 'Edited',
				'version_id' => 2]];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testUndelete()
	 *
	 * @return void
	 */
	public function testUndelete() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->id = 3;
		$result = $Post->undelete();
		$this->assertFalse($result);

		$Post->delete(3);

		$result = $Post->find('count', ['conditions' => ['id' => 3]]);
		$this->assertEquals($result, 0);

		$Post->id = 3;
		$Post->undelete();

		$result = $Post->find('first', ['conditions' => ['id' => 3], 'fields' => [
				'id',
				'title',
				'content']]);

		$expected = ['Post' => [
				'id' => 3,
				'title' => 'Post 3',
				'content' => 'Lorem ipsum dolor sit.']];
		$this->assertEquals($expected, $result);
	}

	/**
	 * RevisionBehaviorTest::testUndeleteCallbacks()
	 *
	 * @return void
	 */
	public function testUndeleteCallbacks() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->id = 3;
		$result = $Post->undelete();
		$this->assertFalse($result);

		$Post->delete(3);

		$result = $Post->find('first', ['conditions' => ['id' => 3]]);
		$this->assertEmpty($result);

		$Post->id = 3;
		$this->assertTrue($Post->undelete());
		$this->assertTrue($Post->beforeUndelete);
		$this->assertTrue($Post->afterUndelete);

		$result = $Post->find('first', ['conditions' => ['id' => 3]]);

		$expected = ['Post' => [
				'id' => 3,
				'title' => 'Post 3',
				'content' => 'Lorem ipsum dolor sit.',
				]];
		$this->assertEquals($expected, $result);
		$this->assertNoErrors();
	}

	/**
	 * RevisionBehaviorTest::testUndeleteTree1()
	 *
	 * @return void
	 */
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

	/**
	 * RevisionBehaviorTest::testUndeleteTree2()
	 *
	 * @return void
	 */
	public function testUndeleteTree2() {
		$this->loadFixtures('RevisionArticle', 'RevisionArticlesRev');

		$Article = new RevisionArticle();

		$Article->initializeRevisions();

		$Article->create([
			'title' => 'første barn',
			'content' => 'stuff',
			'parent_id' => 3,
			'user_id' => 1]);
		$Article->save();
		$Article->create([
			'title' => 'andre barn',
			'content' => 'stuff',
			'parent_id' => 4,
			'user_id' => 1]);
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

	/**
	 * RevisionBehaviorTest::testInitializeRevisionsWithLimit()
	 *
	 * @return void
	 */
	public function testInitializeRevisionsWithLimit() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev', 'RevisionArticle', 'RevisionArticlesRev', 'RevisionComment',
			'RevisionCommentsRev', 'RevisionCommentsRevisionTag', 'RevisionVote', 'RevisionVotesRev', 'RevisionTag',
			'RevisionTagsRev');

		$Comment = new RevisionComment();
		$Post = new RevisionPost();
		$Article = new RevisionArticle();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag', 'with' =>
						'CommentsTag']]], false);

		$this->assertFalse($Post->initializeRevisions());
		$this->assertTrue($Article->initializeRevisions());
		$this->assertFalse($Comment->initializeRevisions());
		$this->assertFalse($Comment->Vote->initializeRevisions());
		$this->assertFalse($Comment->Tag->initializeRevisions());
	}

	/**
	 * RevisionBehaviorTest::testInitializeRevisions()
	 *
	 * @return void
	 */
	public function testInitializeRevisions() {
		$this->loadFixtures('RevisionPost');

		$Post = new RevisionPost();

		$this->assertTrue($Post->initializeRevisions(2));

		$result = $Post->ShadowModel->find('all');

		$this->assertEquals(sizeof($result), 3);
	}

	/**
	 * RevisionBehaviorTest::testRevertAll()
	 *
	 * @return void
	 */
	public function testRevertAll() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->save(['id' => 1, 'title' => 'tullball1']);
		$Post->save(['id' => 3, 'title' => 'tullball3']);
		$Post->create(['title' => 'new post', 'content' => 'stuff']);
		$Post->save();

		$result = $Post->find('all');
		$this->assertEquals($result[0]['Post']['title'], 'tullball1');
		$this->assertEquals($result[1]['Post']['title'], 'Post 2');
		$this->assertEquals($result[2]['Post']['title'], 'tullball3');
		$this->assertEquals($result[3]['Post']['title'], 'new post');

		$this->assertTrue($Post->revertAll(['date' => date('Y-m-d H:i:s', strtotime('yesterday'))]));

		$result = $Post->find('all');
		$this->assertEquals($result[0]['Post']['title'], 'Lorem ipsum dolor sit amet');
		$this->assertEquals($result[1]['Post']['title'], 'Post 2');
		$this->assertEquals($result[2]['Post']['title'], 'Post 3');
		$this->assertEquals(sizeof($result), 3);
	}

	/**
	 * RevisionBehaviorTest::testRevertAllConditions()
	 *
	 * @return void
	 */
	public function testRevertAllConditions() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->save(['id' => 1, 'title' => 'tullball1']);
		$Post->save(['id' => 3, 'title' => 'tullball3']);
		$Post->create();
		$Post->save(['title' => 'new post', 'content' => 'stuff']);

		$result = $Post->find('all');
		$this->assertEquals($result[0]['Post']['title'], 'tullball1');
		$this->assertEquals($result[1]['Post']['title'], 'Post 2');
		$this->assertEquals($result[2]['Post']['title'], 'tullball3');
		$this->assertEquals($result[3]['Post']['title'], 'new post');

		$this->assertTrue($Post->revertAll(['conditions' => ['Post.id' => [
					1,
					2,
					4]], 'date' => date('Y-m-d H:i:s', strtotime('yesterday'))]));

		$result = $Post->find('all');
		$this->assertEquals($result[0]['Post']['title'], 'Lorem ipsum dolor sit amet');
		$this->assertEquals($result[1]['Post']['title'], 'Post 2');
		$this->assertEquals($result[2]['Post']['title'], 'tullball3');
		$this->assertEquals(sizeof($result), 3);
	}

	/**
	 * RevisionBehaviorTest::testOnWithModel()
	 *
	 * @return void
	 */
	public function testOnWithModel() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag', 'with' =>
						'CommentsTag']]], false);
		$result = $Comment->find('first', ['contain' => ['Tag' => ['id', 'title']]]);
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Trick');
	}

	/**
	 * RevisionBehaviorTest::testHABTMRelatedUndoed()
	 *
	 * @return void
	 */
	public function testHABTMRelatedUndoed() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag', 'with' =>
						'CommentsTag']]], false);
		$Comment->Tag->id = 3;
		$Comment->Tag->undo();
		$result = $Comment->find('first', ['contain' => ['Tag' => ['id', 'title']]]);
		$this->assertEquals($result['Tag'][2]['title'], 'Tricks');
	}

	/**
	 * RevisionBehaviorTest::testOnWithModelUndoed()
	 *
	 * @return void
	 */
	public function testOnWithModelUndoed() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag', 'with' =>
						'CommentsTag']]], false);
		$Comment->CommentsTag->delete(3);
		$result = $Comment->find('first', ['contain' => ['Tag' => ['id', 'title']]]);
		$this->assertEquals(sizeof($result['Tag']), 2);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');

		$Comment->CommentsTag->id = 3;
		$this->assertTrue($Comment->CommentsTag->undelete(), 'Undelete unsuccessful');

		$result = $Comment->find('first', ['contain' => ['Tag' => ['id', 'title']]]);
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Trick');
		$this->assertNoErrors('Third Tag not back : %s');
	}

	/**
	 * RevisionBehaviorTest::testHabtmRevSave()
	 *
	 * @return void
	 */
	public function testHabtmRevSave() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$result = $Comment->find('first', ['contain' => ['Tag' => ['id', 'title']]]);
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Trick');

		$currentIds = Set::extract($result, 'Tag.{n}.id');
		$expected = implode(',', $currentIds);
		$Comment->id = 1;
		$result = $Comment->newest();
		$this->assertEquals($expected, $result['Comment']['Tag']);

		$Comment->save(['Comment' => ['id' => 1], 'Tag' => ['Tag' => [2, 4]]]);

		$result = $Comment->find('first', ['contain' => ['Tag' => ['id', 'title']]]);
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

	/**
	 * RevisionBehaviorTest::testHabtmRevCreate()
	 *
	 * @return void
	 */
	public function testHabtmRevCreate() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$result = $Comment->find('first', ['contain' => ['Tag' => ['id', 'title']]]);
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Trick');

		$Comment->create(['Comment' => ['title' => 'Comment 4'], 'Tag' => ['Tag' => [2, 4]]]);

		$Comment->save();

		$result = $Comment->newest();
		$this->assertEquals('2,4', $result['Comment']['Tag']);
	}

	/**
	 * RevisionBehaviorTest::testHabtmRevIgnore()
	 *
	 * @return void
	 */
	public function testHabtmRevIgnore() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->Behaviors->unload('Revision');
		$Comment->Behaviors->load('Revision', ['ignore' => ['Tag']]);

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$Comment->id = 1;
		$originalResult = $Comment->newest();

		$Comment->save(['Comment' => ['id' => 1], 'Tag' => ['Tag' => [2, 4]]]);

		$result = $Comment->newest();
		$this->assertEquals($originalResult, $result);
	}

	/**
	 * RevisionBehaviorTest::testHabtmRevUndo()
	 *
	 * @return void
	 */
	public function testHabtmRevUndo() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$Comment->save(['Comment' => ['id' => 1, 'title' => 'edit'], 'Tag' => ['Tag' => [2, 4]]]);

		$Comment->id = 1;
		$Comment->undo();
		$result = $Comment->find('first', ['recursive' => 1]); //'contain' => array('Tag' => array('id','title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		//TODO: assert
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Fun');
		$this->assertNoErrors('3 tags : %s');
	}

	/**
	 * RevisionBehaviorTest::testHabtmRevUndoJustHabtmChanges()
	 *
	 * @return void
	 */
	public function testHabtmRevUndoJustHabtmChanges() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$Comment->save(['Comment' => ['id' => 1], 'Tag' => ['Tag' => [2, 4]]]);

		$Comment->id = 1;
		$Comment->undo();
		$result = $Comment->find('first', ['recursive' => 1]); //'contain' => array('Tag' => array('id','title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Fun');
		$this->assertNoErrors('3 tags : %s');
	}

	/**
	 * RevisionBehaviorTest::testHabtmRevRevert()
	 *
	 * @return void
	 */
	public function testHabtmRevRevert() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$Comment->save(['Comment' => ['id' => 1], 'Tag' => ['Tag' => [2, 4]]]);

		$Comment->id = 1;
		$Comment->revertTo(1);

		$result = $Comment->find('first', ['recursive' => 1]); //'contain' => array('Tag' => array('id','title'))));
		$this->assertEquals(sizeof($result['Tag']), 3);
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Fun');
		$this->assertNoErrors('3 tags : %s');
	}

	/**
	 * RevisionBehaviorTest::testRevertToHabtm2()
	 *
	 * @return void
	 */
	public function testRevertToHabtm2() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$commentOne = $Comment->find('first', ['conditions' => ['Comment.id' => 1], 'contain' => 'Tag']);
		$this->assertEquals($commentOne['Comment']['title'], 'Comment 1');
		$this->assertEquals(Set::extract($commentOne, 'Tag.{n}.id'), [
			1,
			2,
			3]);
		$Comment->id = 1;
		$revOne = $Comment->newest();
		$this->assertEquals($revOne['Comment']['title'], 'Comment 1');
		$this->assertEquals($revOne['Comment']['Tag'], '1,2,3');
		$versionId = $revOne['Comment']['version_id'];

		$Comment->create(['Comment' => ['id' => 1, 'title' => 'Edited']]);
		$Comment->save();

		$commentOne = $Comment->find('first', ['conditions' => ['Comment.id' => 1], 'contain' => 'Tag']);
		$this->assertEquals($commentOne['Comment']['title'], 'Edited');
		$result = Set::extract($commentOne, 'Tag.{n}.id');
		$expected = [
			1,
			2,
			3];
		$this->assertEquals($expected, $result);
		$Comment->id = 1;
		$revOne = $Comment->newest();
		$this->assertEquals($revOne['Comment']['title'], 'Edited');
		$this->assertEquals($revOne['Comment']['Tag'], '1,2,3');

		$Comment->revertTo(1);

		$commentOne = $Comment->find('first', ['conditions' => ['Comment.id' => 1], 'contain' => 'Tag']);
		$this->assertEquals($commentOne['Comment']['title'], 'Comment 1');
		$result = Set::extract($commentOne, 'Tag.{n}.id');
		//TODO: assert
		$this->assertEquals($result, [
			3,
			2,
			1]);
		$Comment->id = 1;
		$revOne = $Comment->newest();
		$this->assertEquals($revOne['Comment']['title'], 'Comment 1');
		$this->assertEquals($revOne['Comment']['Tag'], '1,2,3');
	}

	/**
	 * RevisionBehaviorTest::testHabtmRevRevertToDate()
	 *
	 * @return void
	 */
	public function testHabtmRevRevertToDate() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$Comment->save(['Comment' => ['id' => 1], 'Tag' => ['Tag' => [2, 4]]]);

		$Comment->id = 1;
		$Comment->revertToDate(date('Y-m-d H:i:s', strtotime('yesterday')));

		$result = $Comment->find('first', ['recursive' => 1]);
		$this->assertEquals(sizeof($result['Tag']), 3);
		//TODO: assert
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'Fun');
		$this->assertNoErrors('3 tags : %s');
	}

	/**
	 * RevisionBehaviorTest::testRevertToTheTagsCommentHadBefore()
	 *
	 * @return void
	 */
	public function testRevertToTheTagsCommentHadBefore() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$result = $Comment->find('first', ['conditions' => ['Comment.id' => 2], 'contain' => ['Tag' => ['id',
						'title']]]);
		$this->assertEquals(sizeof($result['Tag']), 2);
		$this->assertEquals($result['Tag'][0]['title'], 'Fun');
		$this->assertEquals($result['Tag'][1]['title'], 'Trick');

		$Comment->save(['Comment' => ['id' => 2], 'Tag' => ['Tag' => [
					2,
					3,
					4]]]);

		$result = $Comment->find('first', ['conditions' => ['Comment.id' => 2], 'contain' => ['Tag' => ['id',
						'title']]]);
		$this->assertEquals(sizeof($result['Tag']), 3);
		//TODO: assert
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Hard');
		$this->assertEquals($result['Tag'][2]['title'], 'News');

		// revert Tags on comment logic
		$Comment->id = 2;
		$this->assertTrue((bool)$Comment->revertToDate(date('Y-m-d H:i:s', strtotime('yesterday'))),
			'revertHabtmToDate unsuccessful : %s');

		$result = $Comment->find('first', ['conditions' => ['Comment.id' => 2], 'contain' => ['Tag' => ['id',
						'title']]]);
		$this->assertEquals(sizeof($result['Tag']), 2);
		//TODO: assert
		$this->assertEquals($result['Tag'][0]['title'], 'Trick');
		$this->assertEquals($result['Tag'][1]['title'], 'Fun');
	}

	/**
	 * RevisionBehaviorTest::testSaveWithOutTags()
	 *
	 * @return void
	 */
	public function testSaveWithOutTags() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag']]], false);

		$Comment->id = 1;
		$newest = $Comment->newest();

		$Comment->save(['Comment' => ['id' => 1, 'title' => 'spam']]);

		$result = $Comment->newest();
		$this->assertEquals($newest['Comment']['Tag'], $result['Comment']['Tag']);
	}

	/**
	 * RevisionBehaviorTest::testRevertToDeletedTag()
	 *
	 * @return void
	 */
	public function testRevertToDeletedTag() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->bindModel(['hasAndBelongsToMany' => ['Tag' => ['className' => 'RevisionTag', 'with' =>
						'CommentsTag']]], false);

		$Comment->Tag->delete(1);

		$result = $Comment->ShadowModel->find('all', ['conditions' => ['version_id' => [4, 5]]]);
		//TODO: assert/fixme
		//debug($result);
		//$this->assertEquals($result[0]['Comment']['Tag'], '3');
		//$this->assertEquals($result[1]['Comment']['Tag'], '2,3');
	}

	/**
	 * @expectedException PHPUNIT_FRAMEWORK_ERROR_WARNING
	 * @return void
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

	/**
	 * RevisionBehaviorTest::testBadKittyMakesUpStuff()
	 *
	 * @return void
	 */
	public function testBadKittyMakesUpStuff() {
		$this->loadFixtures('RevisionComment', 'RevisionCommentsRev', 'RevisionCommentsRevisionTag',
			'RevisionCommentsRevisionTagsRev', 'RevisionTag', 'RevisionTagsRev');

		$Comment = new RevisionComment();

		$Comment->id = 1;
		$this->assertFalse($Comment->revertTo(10), 'revertTo() : %s');
		$this->assertSame([], $Comment->diff(1, 4), 'diff() between existing and non-existing : %s');
		$this->assertSame([], $Comment->diff(10, 4), 'diff() between two non existing : %s');
	}

	/**
	 * @expectedException PHPUNIT_FRAMEWORK_ERROR_WARNING
	 * @return void
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
		$this->assertFalse($User->revertAll(['date' => '1970-01-01']));
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

	/**
	 * RevisionBehaviorTest::testRevisions()
	 *
	 * @return void
	 */
	public function testRevisions() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$Post = new RevisionPost();

		$Post->create(['Post' => ['title' => 'Stuff (1)', 'content' => 'abc']]);
		$Post->save();
		$postID = $Post->id;

		$Post->data = null;
		$Post->id = null;
		$Post->save(['Post' => ['id' => $postID, 'title' => 'Things (2)']]);

		$Post->data = null;
		$Post->id = null;
		$Post->save(['Post' => ['id' => $postID, 'title' => 'Machines (3)']]);

		$Post->bindModel(['hasMany' => ['Revision' => [
					'className' => 'RevisionPostsRev',
					'foreignKey' => 'id',
					'order' => 'version_id DESC']]]);
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

		$result = $Post->revisions([], true);
		$this->assertSame(3, sizeof($result));
		$this->assertEquals('Machines (3)', $result[0]['Post']['title']);
		$this->assertEquals('Things (2)', $result[1]['Post']['title']);
		$this->assertEquals('Stuff (1)', $result[2]['Post']['title']);
	}

	/**
	 * RevisionBehaviorTest::testFoo()
	 *
	 * @return void
	 */
	public function testNoAlias() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');

		$this->Controller = new Controller(new CakeRequest(null, false), new CakeResponse());
		$this->Controller->loadModel('RevisionPost');
		$this->Controller->RevisionPost->validate['title'] = [
			'minLength' => [
				'rule' => ['minLength', 6],
				'message' => 'mL',
			]
		];
		$data = ['Post' => ['title' => 'S (1)', 'content' => 'abc']];
		$this->Controller->RevisionPost->create();
		$result = $this->Controller->RevisionPost->save($data);
		$this->assertFalse($result);
		$expected = ['title' => ['mL']];
		$this->assertEquals($expected, $this->Controller->RevisionPost->validationErrors);

		$this->Controller->render(false, false);
		$this->assertEquals([], $this->Controller->View->validationErrors['Post']);
	}

	/**
	 * RevisionBehaviorTest::testFoo()
	 *
	 * @return void
	 */
	public function testAlias() {
		$this->loadFixtures('RevisionPost', 'RevisionPostsRev');
		Configure::write('Revision.alias', true);

		$this->Controller = new Controller(new CakeRequest(null, false), new CakeResponse());
		$this->Controller->loadModel('RevisionPost');

		$this->Controller->RevisionPost->validate['title'] = [
			'minLength' => [
				'rule' => ['minLength', 6],
				'message' => 'mL',
			]
		];
		$data = ['Post' => ['title' => 'S (1)', 'content' => 'abc']];
		$this->Controller->RevisionPost->create();
		$result = $this->Controller->RevisionPost->save($data);
		$this->assertFalse($result);
		$expected = ['title' => ['mL']];
		$this->assertEquals($expected, $this->Controller->RevisionPost->validationErrors);

		$this->Controller->render(false, false);
		$this->assertEquals($expected, $this->Controller->View->validationErrors['Post']);
		$this->assertEquals([], $this->Controller->View->validationErrors['PostShadow']);
	}

}

class RevisionTestModel extends CakeTestModel {

	public $logableAction;
}

class RevisionPost extends RevisionTestModel {

	public $name = 'RevisionPost';

	public $alias = 'Post';

	public $actsAs = ['Revision' => ['limit' => 5]];

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

	public $actsAs = ['Tree', 'Revision' => ['ignore' => ['title']]];

	/**
	 * Example of using this callback to undelete children
	 * of a deleted node.
	 */
	public function afterUndelete() {
		$formerChildren = $this->ShadowModel->find('list', [
			'conditions' => ['parent_id' => $this->id],
			'distinct' => true,
			'order' => 'version_created DESC, version_id DESC']);
		foreach (array_keys($formerChildren) as $cid) {
			$this->id = $cid;
			$this->undelete();
		}
	}
}

class RevisionUser extends RevisionTestModel {

	public $name = 'RevisionUser';

	public $alias = 'User';

	public $actsAs = ['Revision'];
}

class RevisionComment extends RevisionTestModel {

	public $name = 'RevisionComment';

	public $alias = 'Comment';

	public $actsAs = ['Containable', 'Revision'];

	public $hasMany = ['Vote' => [
			'className' => 'RevisionVote',
			'foreignKey' => 'revision_comment_id',
			'dependent' => true]];
}

class RevisionVote extends RevisionTestModel {

	public $name = 'RevisionVote';

	public $alias = 'Vote';

	public $actsAs = ['Revision'];
}

class RevisionTag extends RevisionTestModel {

	public $name = 'RevisionTag';

	public $alias = 'Tag';

	public $actsAs = ['Revision'];

	public $hasAndBelongsToMany = ['Comment' => ['className' => 'RevisionComment']];
}

class CommentsTag extends RevisionTestModel {

	public $name = 'CommentsTag';

	public $useTable = 'revision_comments_revision_tags';

	public $actsAs = ['Revision'];
}
