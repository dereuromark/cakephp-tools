<?php

class BlogPostsLinkableTagFixture extends CakeTestFixture {

	public $fields = [
		'id'		=> ['type' => 'integer', 'key' => 'primary'],
		'blog_post_id'		=> ['type' => 'integer'],
		'tag_id'		=> ['type' => 'integer'],
		'main'			=> ['type' => 'integer']
	];

	public $records = [
		['id' => 1, 'blog_post_id' => 1, 'tag_id' => 1, 'main' => 0],
		['id' => 2, 'blog_post_id' => 1, 'tag_id' => 2, 'main' => 1],
		['id' => 3, 'blog_post_id' => 2, 'tag_id' => 3, 'main' => 0],
		['id' => 4, 'blog_post_id' => 2, 'tag_id' => 4, 'main' => 0],
	];

}
