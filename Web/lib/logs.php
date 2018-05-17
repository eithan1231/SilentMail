<?php

class logs
{
	public static function logRequest()
	{
		$user_id = (ses_user_id === false ? 0 : ses_user_id);
		$user_agent = userAgent;
		$hostname = hostName;
		$request_path = requestPath;
		$query_string = queryString;

		subiflen($user_agent, 256);
		subiflen($hostname, 256);
		subiflen($request_path, 4096);
		subiflen($query_string, 4096);

		$result = sql::query("
			INSERT INTO `logs_request`
			(`user_id`, `date`, `ip`, `http_user_agent`, `http_host_name`, `http_path`, `http_query_string`, `is_secure`)
			VALUES (
				". sql::quote($user_id) .",
				". sql::quote(time) .",
				". sql::quote(clientIp) .",
				". sql::quote($user_agent) .",
				". sql::quote($hostname) .",
				". sql::quote($request_path) .",
				". sql::quote($query_string) .",
				". sql::quote(secureRequest) ."
			)
		");

		return $result !== false;
	}

	public static function logLogin($username, $successful_login)
	{
		$result = sql::query("
			INSERT INTO `logs_login`
			(`username`, `username_lower`, `date`, `ip`, `user_agent`, `login_successful`)
			VALUES (
				". sql::quote($username) .",
				". sql::quote(strtolower($username)) .",
				". sql::quote(time) .",
				". sql::quote(clientIp) .",
				". sql::quote(userAgent) .",
				". sql::quote($successful_login) ."
			)
		");

		return $result != false;
	}

	public static function loginAttemptCount($username, $timespan = time_hour)
	{
		$result = sql::query_fetch("
			SELECT count(1) AS cnt
			FROM `logs_login`
			WHERE
				`username_lower` = ". sql::quote($username) ." AND
				`date` > ". sql::quote(time - $timespan) ." AND
				`login_successful` = 0
		");

		return $result['cnt'];
	}

	/**
	* Gets the login attempt count with ip's only from this connecting ip address.
	*/
	public static function loginAttemptCountFromIp($timespan = time_hour)
	{
		$result = sql::query_fetch("
			SELECT count(1) AS cnt
			FROM `logs_login`
			WHERE
				`ip` = ". sql::quote(clientIp) ." AND
				`date` > ". sql::quote(time - $timespan) ."
		");

		return $result['cnt'];
	}

	public static function logRegister($username)
	{
		$result = sql::query("
			INSERT INTO `logs_register`
			(`date`, `ip`, `username`, `username_lower`)
			VALUES (
				". sql::quote(time) .",
				". sql::quote(clientIp) .",
				". sql::quote($username) .",
				". sql::quote(strtolower($username)) ."
			)
		");

		return $result != false;
	}

	public static function getLoginLogs($username = ses_username)
	{
		if($username === false) {
			return false;
		}

		$username_lower = strtolower($username);

		$result = sql::query_fetch_all("
			SELECT `username`, `date`, `ip`, `user_agent`, `login_successful`
			FROM `logs_login`
			WHERE
				`username_lower` = ". sql::quote($username_lower). "
			ORDER BY `date` DESC
		");

		if($result === false) {
			return false;
		}

		return $result;
	}

	public static function getLogExporterRoute()
	{
		return router::instance()->getRoutePath("access_log_export", [
			"security_token" => security_token
		], time);
	}
}
