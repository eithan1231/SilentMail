<?php

class blocks
{
	/**
	* Checks if ip is blocked from logging in.
	*/
	public static function isLoginBlocked()
	{
		return sql::query("
			SELECT `date`
			FROM `blocks_login`
			WHERE
				`ip` = ". sql::quote(clientIp) ." AND
				`expiry` > ". sql::quote(time) ."
		")->num_rows > 0;
	}

	/**
	* Blocks user ip from logging in
	*/
	public static function blockLogin(string $ip = clientIp, $duration = 900 /* 15 minutes */)
	{
		return sql::query("
			INSERT INTO `blocks_login`
			(`date`, `expiry`, `ip`)
			VALUES (
				". sql::quote(time) .",
				". sql::quote(time + $duration) .",
				". sql::quote($ip) ."
			)
		") != false;
	}

	/**
	* Checks if ip is blocked from registering in.
	*/
	public static function isRegisterBlocked()
	{
		return sql::query("
			SELECT `date`
			FROM `blocks_register`
			WHERE
				`ip` = ". sql::quote(clientIp) ." AND
				`expiry` > ". sql::quote(time) ."
		")->num_rows > 0;
	}

	/**
	* Blocks user ip from registering
	*/
	public static function blockRegister(string $ip = clientIp, $duration = time_hour)
	{
		return sql::query("
			INSERT INTO `blocks_register`
			(`date`, `expiry`, `ip`)
			VALUES (
				". sql::quote(time) .",
				". sql::quote(time + $duration) .",
				". sql::quote($ip) ."
			)
		") != false;
	}

	/**
	* Checks if ip is blocked from everything
	*/
	public static function isIpBlocked()
	{
		return sql::query("
			SELECT `date`
			FROM `blocks_ip`
			WHERE
				`ip` = ". sql::quote(clientIp) ." AND
				`expiry` > ". sql::quote(time) ."
		")->num_rows > 0;
	}

	/**
	* Blocks user ip from everything
	*/
	public static function blockIp(string $ip = clientIp, $duration = time_week)
	{
		return sql::query("
			INSERT INTO `blocks_ip`
			(`date`, `expiry`, `ip`)
			VALUES (
				". sql::quote(time) .",
				". sql::quote(time + $duration) .",
				". sql::quote($ip) ."
			)
		") != false;
	}

	/**
	* Checks if account is blocked from logging in.
	*/
	public static function isAccountBlocked(string $username)
	{
		return sql::query("
			SELECT `date`
			FROM `blocks_account`
			WHERE
				`username_lower` = ". sql::quote(strtolower($username)) ." AND
				`expiry` > ". sql::quote(time) ."
		")->num_rows > 0;
	}

	/**
	* Blocks account from logging in
	*/
	public static function blockAccount(string $username, $duration = 900 /* 900 seconds is 15 mintues */)
	{
		return sql::query("
			INSERT INTO `blocks_account`
			(`username`, `username_lower`, `date`, `expiry`)
			VALUES (
				". sql::quote($username) .",
				". sql::quote(strtolower($username)) .",
				". sql::quote(time) .",
				". sql::quote(time + $duration) ."
			)
		") != false;
	}
}
