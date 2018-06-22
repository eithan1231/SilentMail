<?php

class geo
{
	/**
	* Gets country of an IP address
	*/
	public static function getCountry(string $ip = clientIp)
	{
		$geo_info = geo::getGeoInfo($ip);
		if($geo_info === false) {
			return "Unknown";
		}
		else {
			return $geo_info['country'];
		}
	}

	public static function getGeoInfo(string $ip = clientIp)
	{
		if(strpos($ip, ':') !== false) {
			// TODO: ipv6 support
			return false;
		}

		if(!filters::isValidIp($ip)) {
			return false;
		}

		$result = sql::query_fetch("
			SELECT
				countries.country as country,
				countries.iso_code_2 as iso_code_2,
				countries.iso_code_3 as iso_code_3,
				countries.lat as lat,
				countries.lon as lon
			FROM `ip_lookup`
			RIGHT JOIN `countries`
			ON
				countries.code = ip_lookup.country
			WHERE
				ip_lookup.ip < INET_ATON(". sql::quote($ip) .")
			ORDER BY ip_lookup.ip DESC
		");

		if($result === false) {
			return false;
		}

		return $result;
	}
}
