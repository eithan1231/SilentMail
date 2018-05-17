<?php

class session
{
	/**
	* Creates a new session
	*/
	public static function createSession($user_id, $duration = time_year)
	{
		$token = session::generateToken();
		$expiry = time + $duration;

		$user_info = user::getUserInformation($user_id);

		if(!$user_info['success']) {
			throw new Exception("User not found");
		}

		$results = sql::query("
			INSERT INTO `sessions`
			(`user_id`, `ip`, `token`, `date`, `expiry`, `enabled`, `awaiting_security_check`)
			VALUES (
				". sql::quote($user_id) .",
				". sql::quote(clientIp) .",
				". sql::quote($token) .",
				". sql::quote(time) .",
				". sql::quote($expiry) .",
				1,
				". sql::quote($user_info['data']['force_security']) ."
			)
		");

		return [
			'success' => $results !== false,
			'data' => [
				'expiry' => $expiry,
				'token' => $token,
				'user_id' => $user_id
			]
		];
	}

	/**
	* Gets the session
	*/
	public static function getSession($token)
	{
		$result = sql::query_fetch("
			SELECT `user_id`, `awaiting_security_check`
			FROM `sessions`
			WHERE
				`token` = ". sql::quote($token) ." AND
				`expiry` > ". sql::quote(time) ." AND
				`ip` = ". sql::quote(clientIp) ." AND
				`enabled` = 1
		");

		if($result !== false) {
			return [
				'success' => true,
				'data' => [
					'user_id' => intval($result['user_id']),
					'awaiting_security_check' => $result['awaiting_security_check']
				]
			];
		}
		else {
			return [
				'success' => false
			];
		}
	}

	/**
	* Call after you complete security check
	*/
	public static function securityCheckComplete($token, $user_id)
	{
		$result = sql::query("
			UPDATE `sessions`
			SET `awaiting_security_check` = 0
			WHERE
				`token` = ". sql::quote($token) ." AND
				`user_id` = ". sql::quote($user_id) ." and
				`enabled` = 1
		");

		return $result != false;
	}

	/**
	* Deactivated a token linked with a user id
	*/
	public static function deactivateToken($token, $user_id)
	{
		sql::query("
			UPDATE `sessions`
			SET `enabled` = 0
			WHERE
				`token` = ". sql::quote($token) ." AND
				`user_id` = ". sql::quote($user_id) ."
		");
	}

	/**
	* Generates a token, this will not insert to database.
	*/
	public static function generateToken()
	{
		$ret = '';

		while(true) {
			$ret = cryptography::randomString(64);

			// Inserting time
			$ret = time . substr($ret, strlen(time));

			if(!session::tokenExists($ret)) {
				break;
			}
		}

		return $ret;
	}

	/**
	* Checks if a token exists
	*/
	public static function tokenExists($token)
	{
		if(strlen($token) > 64) {
			// Maximum token size.
			throw new Exception("token too long");
		}

		return sql::query("
			SELECT `user_id`
			FROM `sessions`
			WHERE
				`token` = ". sql::quote($token) ."
			LIMIT 1
		")->num_rows > 0;
	}
}
