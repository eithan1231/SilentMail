<?php

class request_check
{
	public static function check()
	{
		// Checking PHP compatibility
		if(
			version_compare(phpversion(), '7.0.0', '<') ||
			version_compare(phpversion(), '8.0.0', '>')
		) {
			output_page::SetHttpStatus(500, 'Internal Error');
			die("Unsupported PHP version");
		}

		// Making sure we have GD installed
		if(!function_exists('gd_info')) {
			output_page::SetHttpStatus(500, 'Internal Error');
			die("Contact administrator, GB library not installed<br /><br />Read <a href=\"http://php.net/manual/en/book.image.php\">this</a> for more information.");
		}

		if(!class_exists('mysqli')) {
			output_page::SetHttpStatus(500, 'Internal Error');
			die("Contact administrator, Mysqli library not installed.");
		}

		// Checking if there are any verified hosts
		if(count(config['trustedHosts']) == 0) {
			output_page::SetHttpStatus(500, 'Internal Error');
			die("No verified hosts found. Contact systems administrator.");
		}

		// Checking this is a verified host
		if(
			!isset($_SERVER['HTTP_HOST']) ||
			(
				!in_array($_SERVER['HTTP_HOST'], config['trustedHosts']) &&
				$_SERVER['HTTP_HOST'] !== 'smtpNode'
			)
		) {
			output_page::SetHttpStatus(401, 'Unauthorized');
			die();
		}

		if(!isset($_SERVER['HTTP_USER_AGENT'])) {
			output_page::SetHttpStatus(500, 'Internal Error');
			die("User agent not found.");
		}

		// Now lets connect to database...
		sql::connect();
		if(!sql::ping()) {
			output_page::SetHttpStatus(500, 'Internal Error');
			loadUi('special.db');
			die();
		}

		if(blocks::isIpBlocked()) {
			// User is IP banned
			header_remove("Content-type");
			header_remove("Content-length");
			header_remove("Date");
			header_remove("Vary");
			output_page::SetHttpStatus(500, 'Internal Error');
			die();
		}
	}
}
