<?php

class BlogPostsLinkableTagFixture extends CakeTestFixture {

	public $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary'),
		'blog_post_id'		=> array('type' => 'integer'),
		'tag_id'		=> array('type' => 'integer'),
		'main'			=> array('type' => 'integer')
	);

	public $records = array(
		array ('id' => 1, 'blog_post_id' => 1, 'tag_id' => 1, 'main' => 0),
		array ('id' => 2, 'blog_post_id' => 1, 'tag_id' => 2, 'main' => 1),
		array ('id' => 3, 'blog_post_id' => 2, 'tag_id' => 3, 'main' => 0),
		array ('id' => 4, 'blog_post_id' => 2, 'tag_id' => 4, 'main' => 0),
	);

}
