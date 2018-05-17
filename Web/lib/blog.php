<?php

class blog
{
	/**
	* Gets the route to a category
	*
	* @param int #category
	*		Category you want to get the route of
	*/
	public static function getCategoryRoute($category)
	{
		return router::instance()->getRoutePath("blogCategory", [
			'category' => intval($category)
		]);
	}

	/**
	* Gets the route to a blog thread
	*
	* @param int $category
	*		The parent category
	* @param int $thread
	*		the ID of the blog thread.
	*/
	public static function getThreadRoute($category, $thread)
	{
		return router::instance()->getRoutePath("blogPost", [
			'category' => intval($category),
			'thread' => intval($thread),
		]);
	}

	/**
	* Gets cetegories
	*
	* @return array|boolean
	*		On Success it will return an array, errors will return false.
	*/
	public static function getCategories($index, $count = 32)
	{
		if(!is_numeric($index) || !is_numeric($count)) {
			return false;
		}

		return sql::query_fetch_all("
			SELECT `id`, `name`
			FROM `blog_category`
			LIMIT ". sql::quote($count) ." OFFSET  ". sql::quote($index) ."
		");
	}

	/**
	* Gets the count of categories.
	*
	* @return int
	*		Count of categories.
	*/
	public static function getCategoryCount()
	{
		$result = sql::query_fetch("
			SELECT count(1) as size
			FROM `blog_category`
		");

		if($result === false) {
			return 0;
		}

		return $result['size'];
	}

	/**
	* Gets a list of blog threads.
	*
	* @param int $index
	*		The starting index
	* @param int $count
	*		Amount of blog threads you want to list
	* @param int $category
	*		The category you wish you list thread from.
	*/
	public static function getBlogThreads($index, $count = 32, $category = false, $get_bodies = false, $include_deleted = false)
	{
		$conditions = '';

		$addCondition = function($cond) use(&$conditions) {
			if(strlen($conditions) == 0) {
				$conditions .= " WHERE";
			}
			else {
				$conditions .= " AND";
			}

			$conditions .= " {$cond} ";
		};

		if(!$include_deleted) {
			$addCondition("blog_threads.is_deleted = 0");
		}

		if($category !== false) {
			$addCondition("blog_threads.category = ". sql::quote($category));
		}

		return sql::query_fetch_all("
			SELECT
				blog_threads.id as id,
				blog_threads.category as category,
				blog_threads.creator as creator,
				user.username as creator_username,
				user.name_first as creator_first_name,
				CONCAT(user.name_first, ' ', user.name_last) as creator_full_name,
				group.color as creator_color,
				blog_threads.date as date,
				blog_threads.title as title
				". ($get_bodies ? ", blog_threads.body as body" : false) ."
			FROM `blog_threads`
				RIGHT JOIN `user`
					ON blog_threads.creator = user.id
				RIGHT JOIN `group`
					ON group.id = user.group_id
			{$conditions}
		");
	}

	/**
	* Gets the total count of blog threads.
	*
	* @param int|boolean $category
	*		Category we want the count of. This is an optional parameter.
	*/
	public static function getBlogThreadsCount($category = false)
	{
		$result = sql::query_fetch("
			SELECT count(1) as size
			FROM `blog_threads`
			". ($category !== false ? " WHERE `category` = ". sql::quote($category) : false)."
		");

		if($result === false) {
			return 0;
		}

		return $result['size'];
	}

	/**
	* Gets a blog thread.
	*
	* @return array|false
	*
	* @param int $id
	*		ID of the blog thread.
	* @param int|boolean $category
	*		Id of the parent category. This can be left to default.
	*/
	public static function getBlogThread($id, $category = false)
	{
		return sql::query_fetch_all("
			SELECT `id`, `category`, `creator`, `date`, `title`, `body`
			FROM `blog_threads`
			WHERE
				`id` = ". sql::quote($id) ."
				". ($category !== false ? " AND `category` = ". sql::quote($category) : false) ."
			LIMIT 1
		");
	}

	/**
	* Creates a new blog thread.
	*
	* @return array
	*
	* @param string $category
	*		The category to which this will be listed.
	* @param string $title
	*		Tittle of the blog thread.
	* @param string $body
	*		Body of the thread.
	* @param int $creator
	*		The creator of this blog post.
	*/
	public static function newBlogThread($category, $title, $body, $creator = ses_user_id)
	{
		$group_permissions = group::getGroupInformationByUserId($creator);
		if($group_permissions['succes'] === false) {
			return function_response(false, [
				'message' => $group_permissions['message']
			]);
		}

		// Checking if user has permissions to blog
		if(!$group_permissions['data']['can_blog']) {
			return function_response(false, [
				'message' => "User does not have permissions to blog"
			]);
		}
	}
}
