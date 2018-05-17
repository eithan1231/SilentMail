<?php

class flagged
{
	public static function isIpFlagged(string $ip = clientIp)
	{
		sql::query("
			SELECT `expiry`
			FROM `flagged_ip`
			WHERE
				`ip` = ". sql::quote($ip) ." AND
				`expiry` > ". sql::quote(time) ."
		")->num_rows > 0;
	}

	/**
	* Flags an IP address. Which will prevent it from accessing some functionality.
	* If a flaging exists, it will add the new duration to it.
	*/
	public static function flagIp(string $ip = clientIp, $reason = false, integer $duration = time_week)
	{
		if(strlen($reason) > 64) {
			$reason = substr($reason, 0, 64);
		}

		$result = sql::query_fetch("
			SELECT `expiry`
			FROM `flagged_ip`
			WHERE
				`ip` = ". sql::quote($ip) ." AND
				`expiry` > ". sql::quote(time) ."
		");

		if($result === false) {
			sql::query("
				INSERT INTO `flagged_ip`
				(`ip`, `date`, `expiry`, `reason`)
				VALUES (
					". sql::quote($ip) .",
					". sql::quote(time) .",
					". sql::quote(time + $duration) .",
					". sql::quote($reason) ."
				)
			");
		}
		else {
			$expiry = $result['expiry'] + $duration;

			sql::query("
				UPDATE `flagged_ip`
				SET `expiry` = ". sql::quote($expiry) .", `reason` = ". sql::quote($reason) ."
				WHERE
					`ip` = ". sql::quote($ip) ." AND
					`expiry` > ". sql::quote(time) ."
			");
		}
	}
}
