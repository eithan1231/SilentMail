<?php

class filters
{
	public static function isValidJavascriptVariable(string $variable_name)
	{
		$len = strlen($variable_name);
		for($i = 0; $i < $len; $i++) {
			if($variable_name[$i] == '_') {
				continue;
			}
			if(!is_alphanumeric($variable_name[$i])) {
				return false;
			}
		}
		return true;
	}

	public static function isValidIp(string $ip)
	{
		return filter_var($ip, FILTER_VALIDATE_IP);
	}

	public static function cleanKeyword(string $keyword)
	{
		$keyword_length = strlen($keyword);

		if($keyword_length > 16) {
			$keyword = substr($keyword, 0, 16);
		}

		return strtolower($keyword);
	}

	public static function isValidKeyword(string $keyword)
	{
		if($keyword === false) {
			return false;
		}

		$keyword_length = strlen($keyword);

		if($keyword_length < 4) {
			return false;
		}

		if($keyword_length > 16) {
			return false;
		}

		for ($i = 0; $i < $keyword_length; $i++) {
			if(in_array2($keyword[$i], alphabetUpper)) {
				// All keywords are lowercase.
				return false;
			}
		}

		return true;
	}

	public static function isValidUsername(string $username)
	{
		return filters::isValidUsername2($username)['success'];
	}

	public static function isValidUsername2(string $username)
	{
		$username_length = strlen($username);
		if($username_length < 3) {
			return function_response(false, [
				'message' => 'Exceeded username length'
			]);
		}

		if($username_length > 64) {
			return function_response(false, [
				'message' => 'Exceeded username length'
			]);
		}

		for($i = 0; $i < $username_length; $i++) {
			if(in_array2($username[$i], alphabet)) {
				continue;
			}

			if(in_array2($username[$i], alphabetUpper)) {
				continue;
			}

			if(is_numeric($username[$i])) {
				continue;
			}

			if(
				$username[$i] == '.' ||
				$username[$i] == '_' ||
				$username[$i] == '+' ||
				$username[$i] == '-'
			) {
				continue;
			}

			return function_response(false, [
				'message' => htmlentities(character_name($username[$i])) .' character is not allowed in username.',
			]);
		}

		return function_response(true, [
			'message' => ''
		]);
	}

	public static function isValidPassword(string $password)
	{
		// Add some sort of popular password blacklsit
		if(strtolower($password) === "password") {
			return false;
		}
		if(strlen($password) < 6) {
			return false;
		}
		return true;
	}

	public static function isValidEmail(string $email)
	{
		// I was going to make my own, following the email specification on the
		// smtp RFC page, but i couldnt be bothored.
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	public static function isValidSecurityQuestion(string $question)
	{
		if(strlen($question) > 64) {
			return false;
		}

		if(strlen($question) < 5) {
			return false;
		}

		return true;
	}

	public static function isValidSecurityAnswer(string $answer)
	{
		if(strlen($answer) > 128) {
			return false;
		}
		if(strlen($answer) < 2) {
			return false;
		}

		return true;
	}

	public static function isValidSecurityHint(string $hint)
	{
		if(strlen($hint) > 64) {
			return false;
		}

		return true;
	}

	public static function isValidFirstName(string $name)
	{
		return strlen($name) < 32;
	}

	public static function isValidLastName(string $name)
	{
		return strlen($name) < 32;
	}
}
