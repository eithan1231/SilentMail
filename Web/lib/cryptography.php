<?php

class cryptography
{
	public static function randomString($length, $special_chars = false, $allow_spaces = false)
	{
		if($length > 4096) {
			throw new Exception("Length too long");
		}

		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_'. ($special_chars ? '!@#$%^&*()' : '') . ($allow_spaces ? ' ' : '');
		$chars_length = strlen($chars);
		$ret = '';

		for($i = 0; $i < $length; $i++) {
			$ret .= $chars[mt_rand(0, $chars_length - 1)];
		}

		return $ret;
	}

	public static function hashPassword(string $password, string $salt)
	{
		return hash("sha512", hash("sha512", $salt . $password) . $salt);
	}
}
