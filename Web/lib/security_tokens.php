<?php

class security_tokens
{
	/**
	* Creates a new security token
	*/
	public static function createSecurityToken($user_id = false)
	{
		$user_id = ($user_id === false ? 0 : $user_id);
		$expiry = time + config['securityTokenExpiry'];
		$token = security_tokens::generateToken($user_id);

		$result = sql::query("
			INSERT INTO `security_tokens`
			(`token`, `date`, `expiry`, `user_id`)
			VALUES (
				". sql::quote($token) .",
				". sql::quote(time) .",
				". sql::quote($expiry) .",
				". sql::quote($user_id) ."
			)
		");

		if(!$result) {
			throw new Exception("Unable to create security token");
		}

		return [
			'success' => $result !== false,
			'data' => [
				'token' => $token
			]
		];
	}

	/**
	* Verifyes a security token is valid
	*/
	public static function verifySecurityToken($token, $user_id = false)
	{
		$user_id_where = '';
		if($user_id !== false) {
			$user_id_where = " AND `user_id` = ". sql::quote($user_id);
		}

		$result = sql::query_fetch("
			SELECT `token`
			FROM `security_tokens`
			WHERE
				`token` = ". sql::quote($token) ." AND
				`expiry` > ". sql::quote(time) ."
				$user_id_where
		");

		return [
			'success' => true,
			'data' => [
				'valid' => $result !== false,
			]
		];
	}

	/**
	* Checks if a security token exists
	*/
	public static function securityTokenExists($token)
	{
		return sql::query("
			SELECT `token`
			FROM `security_tokens`
			WHERE `token` = ". sql::quote($token) ."
			LIMIT 1
		")->num_rows > 0;
	}

	/**
	* Generates a new token (doesn't insert to dataabse)
	*/
	public static function generateToken($user_id = false)
	{
		$ret = '';

		while(true) {
			if($user_id === false) {
				$ret = cryptography::randomString(256 - strlen(time));
				$ret = time . $ret;
			}
			else {
				$ret = cryptography::randomString(256 - strlen(time) - strlen($user_id));
				$ret = $user_id . time . $ret;
			}

			if(!security_tokens::securityTokenExists($ret)) {
				break;
			}
		}

		return $ret;
	}
}
