<?php

class api
{
	/**
	* Authenticates a api key
	*
	* @param string $key
	*		The authentication token
	*/
	public static function authenticate(string $key)
	{
		$result sql::query("
			SELECT `id`
			FROM `api_auth`
			WHERE
				`token` = ". sql::quote($key) ." AND
				`enabled` = 1
		")->num_rows > 0;

		return function_response($result, false);
	}

	/**
	* Deactivates an api key
	*
	* @param string $key
	*		The key to be deactivated
	* @param integer $user_id
	*		Creator of the API key
	*/
	public static function deactivateToken(string $key, $user_id = ses_user_id)
	{
		return sql::query("
			UPDATE `api_auth`
			SET `enabled` = 0
			WHERE
				`token` = ". sql::quote($key) ." AND
				`creator` = ". sql::quote($user_id) ."
		") !== false;
	}

	/**
	* Activates an api key
	*
	* @param string $key
	*		The key to be Activated
	* @param integer $user_id
	*		Creator of the API key
	*/
	public static function activateToken(string $key, $user_id = ses_user_id)
	{
		return sql::query("
			UPDATE `api_auth`
			SET `enabled` = 1
			WHERE
				`token` = ". sql::quote($key) ." AND
				`creator` = ". sql::quote($user_id) ."
		") !== false;
	}

	/**
	* Creates a new API key
	*
	* @param string $user_id
	*		Creator of the key
	*/
	public static function createKey($user_id = ses_user_id)
	{
		$key = api::generateKey();

		$result = sql::query("
			INSERT INTO `api_auth`
			(`id`, `token`, `creator`, `date`, `enabled`)
			VALUES (
				NULL,
				". sql::quote($key) .",
				". sql::quote($user_id) .",
				". sql::quote(time) .",
				1
			)
		");

		if($result === false) {
			return function_response(false, [
				'message' => 'Failed to insert'
			]);
		}

		$id = sql::query_fetch("SELECT LAST_INSERT_ID() AS id")['id'];

		return function_response(true, [
			'message' => '',
			'id' => $id,
			'key' => $key
		])
	}

	/**
	* Generates a random 64 character api key
	*/
	public static function generateKey()
	{
		$ret = '';

		while(true) {
			$ret = cryptography::randomString(64);
			if(!api::keyExists($ret)) {
				break;
			}
		}

		return $ret;
	}

	/**
	* Checks if a api key exists
	*
	* @param string $key
	*		Key you want to check exists
	*/
	public static function keyExists(string $key)
	{
		if(strlen($key) !== 64) {
			return false;
		}

		return sql::query("
			SELECT `id`
			FROM `api_auth`
			WHERE
				`token` = ". sql::quote($key) ."
			LIMIT 1
			")->num_rows > 0;
	}
}
