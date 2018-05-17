<?php

class output_page
{
	public static function SetHttpStatus($status_code, $status_descriptor)
	{
		if(!is_numeric($status_code)) {
			throw new Exception("Invalid Status code (Not numeric)");
		}

		if(headers_sent()) {
			throw new Exception("Already sent headers");
		}

		$version = $_SERVER['SERVER_PROTOCOL'];
		if(empty($version)) {
			$version = 'HTTP/1.1';
		}

		header("{$version} {$status_code} {$status_descriptor}");
	}
}
