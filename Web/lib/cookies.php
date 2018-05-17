<?php

class cookies
{
	/**
	* Sets the session cookie data
	*/
	public static function setSession(string $data, string $expiry)
	{
		SetCookie(
			config['sessionCookieName'],
			$data,
			$expiry,
			'/'
		);
	}

	/**
	* Gets the session cookie data
	*/
	public static function getSession()
	{
		if(isset($_COOKIE[config['sessionCookieName']])) {
			return $_COOKIE[config['sessionCookieName']];
		}

		return false;
	}
}
