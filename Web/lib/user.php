<?php

class user
{
	private static $cache = [];

	/**
	* Checks if a user has password history
	*
	* @param integer $user_id
	*		The user who want to to check has password hsitory
	*/
	public static function hasPasswordHistory($user_id = ses_user_id)
	{
		$result = sql::query_fetch("
			SELECT count(1) as history_size
			FROM `password_history`
			WHERE
				`user_id` = ". sql::quote($user_id) ."
		");

		if($result === false) {
			return false;
		}

		return $result['history_size'] > 0;
	}

	/**
	* This function gets a users password history
	*
	* @param integer $user_id
	*		The user id of whose password history we want to get
	*/
	public static function getPasswordHistory($user_id = ses_user_id)
	{
		$result = sql::query_fetch_all("
			SELECT `password`, `salt`, `date`, `ip`, `user_agent`
			FROM `password_history`
			WHERE
				`user_id` = ". sql::quote($user_id) ."
			ORDER BY `date` DESC
		");

		if($result !== false) {
			return function_response(true, $result);
		}
		else {
			return function_response(false, false);
		}
	}

	/**
	* Gets information on the user tale linked with the user_id parameter
	*/
	public static function getUserInformation($user_id)
	{
		$result = sql::query_fetch("
			SELECT
				`id`,
				`username`,
				`name_first`,
				`name_last`,
				CONCAT(name_first, ' ', name_last) AS name_full,
				`group_id`,
				`force_security`,
				`manageable`
			FROM `user`
			WHERE
				`id` = ". sql::quote($user_id) ."
		");

		if($result !== false) {
			return function_response(true, $result);
		}
		else {
			return function_response(false, false);
		}
	}

	/**
	* Forces a user accout to have additional security checks after logging in.
	*/
	public static function forceSecurityCheck($user_id = ses_user_id)
	{
		sql::query("
			UPDATE `user`
			SET `force_security` = 1
			WHERE
				`id` = ". sql::quote($user_id) ."
		");
	}

	/**
	* Remvoes a forced security check
	*/
	public static function removeForcedSecurityChecks($user_id = ses_user_id)
	{
		sql::query("
			UPDATE `user`
			SET `force_security` = 0
			WHERE
				`id` = ". sql::quote($user_id) ."
		");
	}

	/**
	* Gets a user id from a username
	*/
	public static function getUserId($username)
	{
		$username_lower = strtolower($username);

		if(isset(self::$cache[__FUNCTION__][$username_lower])) {
			return self::$cache[__FUNCTION__][$username_lower];
		}

		$result = sql::query_fetch("
			SELECT `id`
			FROM `user`
			WHERE
				`username_lower` = ". sql::quote($username_lower) ."
		");

		if($result === false) {
			return false;
		}

		return (self::$cache[__FUNCTION__][$username_lower] = $result['id']);
	}
}
