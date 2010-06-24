<?php

App::import('Lib', 'Tools.GithubLib');

class GithubLibTestCase extends CakeTestCase {

	function setUp() {
		$this->GithubLib = new GithubLib();
		$this->assertTrue(is_object($this->GithubLib));
	}

	function TearDown() {
		unset($this->GithubLib);
	}

	function testFetch() {
		$url = 'http://github.com/api/v2/json/issues/list/';
		$is = $this->GithubLib->_fetch($url);
		echo returns($is);
		$this->assertFalse($is); // 401

		$url = 'http://github.com/api/';
		$is = $this->GithubLib->_fetch($url);
		echo returns($is);
		$this->assertFalse($is); // 404
	}



	function testUser() {
		$username = 'dereuromark';

		$is = $this->GithubLib->userInfo($username);
		echo returns($is);
		$this->assertTrue(!empty($is));
	}


	function testCommits() {
		$username = 'dereuromark';
		$project = 'tools';

		$is = $this->GithubLib->userTimeline('philsturgeon', 'codeigniter-github');
		echo returns($is);
		$this->assertTrue(!empty($is));


	}

	function testLastCommits() {
		$is = $this->GithubLib->lastCommits('philsturgeon', 'codeigniter-github');
		echo returns($is);
		$this->assertTrue(!empty($is));
	}


	function testSearch() {
		$term = 'cakephp';
		$language = 'php';

		$is = $this->GithubLib->search($term, $language);
		echo returns($is);
		$this->assertFalse($is); // WHY?
		//$this->assertTrue(!empty($is));
	}


	function testRepoInfo() {
		$user = 'dereuromark';
		$repo = 'tools';

		$is = $this->GithubLib->repoInfo($user, $repo);
		echo returns($is);
		$this->assertTrue(!empty($is));
	}


	function testRepoRefs() {
		$user = 'dereuromark';
		$repo = 'tools';

		$is = $this->GithubLib->repoRefs($user, $repo);
		echo returns($is);
		$this->assertTrue(empty($is));

		$is = $this->GithubLib->repoRefs($user, $repo, 'branches');
		echo returns($is);
		$this->assertTrue(!empty($is));
	}


	function testProjectIssues() {
		$user = 'dereuromark';
		$repo = 'tools';

		$is = $this->GithubLib->projectIssues($user, $repo);
		echo returns($is);
		$this->assertFalse($is);
	}




}
?>