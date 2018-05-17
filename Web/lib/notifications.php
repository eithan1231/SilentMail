<?php

class notifications
{
	/**
	* Gets the notification redirect to a route
	*/
	public static function getRedirectRoute($id)
	{
		return router::instance()->getRoutePath('notification_redirect', [
			'id' => $id
		], time);
	}

	/**
	* Pushes notification to user.
	*/
	public static function push($text, $link = '#', $user_id = ses_user_id)
	{
		$text_length = strlen($text);
		$link_length = strlen($link);

		if($text_length > 512) {
			$text = substr($text, 0, 512);
		}

		if($link_length > 512) {
			$link = substr($link, 0, 512);
		}

		return sql::query("
			INSERT INTO `notification`
			(`id`, `user_id`, `date`, `has_seen`, `text`, `link`)
			VALUES (
				NULL,
				". sql::quote($user_id) .",
				". sql::quote(time) .",
				0,
				". sql::quote($text) .",
				". sql::quote($link) ."
			)
		") !== false;
	}

	/**
	* Marks a notification as read
	*/
	public static function markRead($id, $user_id = ses_user_id)
	{
		return sql::query("
			UPDATE `notification`
			SET `has_seen` = 1
			WHERE
				`id` = ". sql::quote($id) ." AND
				`user_id` = ". sql::quote($user_id) ."
		") !== false;
	}

	public static function getNotification($id, $user_id = ses_user_id)
	{
		return sql::query_fetch("
			SELECT `date`, `has_seen`, `text`, `link`
			FROM `notification`
			WHERE
				`id` = ". sql::quote($id) ." AND
				`user_id` = ". sql::quote($user_id) ."
			LIMIT 1
		");
	}

	/**
	* Gets notifications linked with a user id
	*/
	public static function get($user_id = ses_user_id)
	{
		$result = sql::query_fetch_all("
			SELECT `id`, `date`, `text`, `link`
			FROM `notification`
			WHERE
				`user_id` = ". sql::quote($user_id). "
			ORDER BY `date` DESC
			LIMIT 1000
		");

		return $result;
	}

	public static function getUnread($user_id = ses_user_id)
	{
		$result = sql::query_fetch_all("
			SELECT `id`, `date`, `text`, `link`
			FROM `notification`
			WHERE
				`user_id` = ". sql::quote($user_id) ." AND
				`has_seen` = 0
			ORDER BY `date` DESC
			LIMIT 100
		");

		return $result;
	}
}
