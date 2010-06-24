<?php
/*

TODO (with API):
- following: /user/show/:user/following
- followers
- network: /repos/show/:user/:repo/network
- ...

*/

/**
 * access to github
 *
 * derived from Philip Sturgeon
 *
 * @author Mark Scherer
 * @info http://develop.github.com/
 * 2010-06-24 ms
 */
class GithubLib {

	const JSON_URL = 'http://github.com/api/v2/json/';

	// First tries with curl, then cake, then php
	var $use = array('curl' => true, 'cake'=> true, 'php' => true);


  /**
   * Grab all issues for a specific repository
   *
   * @access	public
   * @param	string - a GitHub user
   * @param	string - a repository name
   * @param	string - the state of the issues to pull (open/closed)
   * @return	object - an object with all the repository's issues
   */
	public function projectIssues($user = '', $repo = '', $state = 'open') {
		$response = $this->_fetch('issues/list/'.$user.'/'.$repo.'/'.$state);

		if(empty($response->issues)) {
			return false;
		}
		return $response->issues;
	}


  /**
   * Grab the info for a repository
   *
   * @access	public
   * @param	string - a GitHub user
   * @param	string - a repository name
   * @return array with all the repository's info
   */
  public function repoInfo($user, $repo) {
  	$response = $this->_fetch('repos/show/'.$user.'/'.$repo);

  	if(empty($response->repository)) {
  		return false;
  	}
  	return (array)$response->repository;
  }


  /**
   * Grab all refs for a specific repository
   *
   * @access	public
   * @param	string - a GitHub user
   * @param	string - a repository name
   * @param	string - the repository reference to pull (tags/branches)
   * @return array with all the repository's references
   */
  public function repoRefs($user, $repo, $ref = 'tags') {
  	$response = $this->_fetch('repos/show/'.$user.'/'.$repo.'/'.$ref);

  	if(empty($response->$ref)) {
  		return false;
  	}
  	return (array)$response->$ref;
  }


	/**
   * Grab the info for a specific user
   *
   * @access	public
   * @param	string - a GitHub user
   * @return array with all infos (gravatar_id, name, company, location, blog, id, login, email, ...)
   */
  public function userInfo($user) {
  	$response = $this->_fetch('user/show/'.$user);

  	if(empty($response->user)) {
  		return false;
  	}
  	return (array)$response->user;
  }


	/**
   * Grab all commits by a user to a specific repository
   *
   * @access public
   * @param	string - a GitHub user
   * @param	string - a repository name
   * @param	string - the branch name (master by default)
   * @return object - an object with all the branch's commits (array[array[parents, author, url, id, comitter, ...]])
   */
  public function userTimeline($user, $repo, $branch = 'master') {
  	$response = $this->_fetch('commits/list/'.$user.'/'.$repo.'/'.$branch);

  	if(empty($response->commits)) {
  		return false;
  	}

  	return $response->commits;
  }


	/**
	 * get the last commits with message and date
   * @access public
   * @param	string - a GitHub user
   * @param	string - a repository name
   * @param	string - the branch name (master by default)
	 * @return array (url, commited, message)
	 * 2010-06-24 ms
	 */
	function lastCommits($user, $repo, $branch = 'master', $limit = 10) {
		if (!($response = $this->userTimeline($user, $repo, $branch))) {
			return false;
		}
		$result = array();
		foreach ($response as $c) {
			if ($limit-- == 0) {
				break;
			}
			$result[] = array('url'=>$c->url, 'committed' =>$c->committed_date, 'message' => $c->message);
		}
		return $result;
	}


  /**
   * Search GitHub
   *
   * @access	public
   * @param	string - the term to search for
   * @param	string - the language
   * @return	array  - an array with all the search results
   */
  public function search($term, $language = null) {
  	if(!empty($language) && is_string($language)) {
  		$language = strtolower($language);
  	}

  	$response = $this->_fetch('search/'.$term);

  	if(empty($response->repositories) or !is_array($response->repositories)) {
  		return false;
  	}

  	$results = array();

  	foreach($response->repositories as &$result) {
  		if($language != strtolower($result->language)) {
  			continue;
  		}
  		$results[] = $result;
  	}
  	return $results;
  }


	/**
	 * fetches url with curl if available
	 * fallbacks: cake and php
	 * note: expects url with json encoded content
	 * @access private
	 **/
	function _fetch($url) {
		$url = self::JSON_URL.$url;

		if ($this->use['curl'] && function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'cakephp github lib');
			$response = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close ($ch);
			if ($status != '200') {
				return false;
			}
			return json_decode($response);

		} elseif($this->use['cake'] && App::import('Core', 'HttpSocket')) {
			$HttpSocket = new HttpSocket(array('timeout' => 5));
			$response = $HttpSocket->get($url);
			if (empty($response)) { //TODO: status 200?
				return false;
			}
			return json_decode($response);

		} elseif($this->use['php'] || true) {
			$response = file_get_contents($url, 'r');
			//TODO: status 200?
			if (empty($response)) {
				return false;
			}
			return json_decode($response);
		}
	}

}

?>