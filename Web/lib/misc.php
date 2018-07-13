<?php

class misc
{
	public static function buildUserAgent()
	{
		return str_replace(" ", '', config['projectName']) .'-UA/v'. config['version'];
	}

	public static function getUserfilePath($parent_dir_1, $parent_dir_2, $parent_dir_3, $filename)
	{
		$filename = misc::cleanFileName($filename);
		return config['userfileDir']. "{$parent_dir_1}/{$parent_dir_2}/{$parent_dir_3}/{$filename}";
	}

	public static function constructAddress($username)
	{
		return strtolower($username) ."@". config['mailDomain'];
	}

	public static function buildTitle($page_name)
	{
		return htmlentities("{$page_name} - ". config['projectName']);
	}

	public static function cleanFileName($name)
	{
		$out = '';
		for($i = 0; $i < strlen($name); $i++) {
			if(is_numeric($name[$i]) || ctype_alpha($name[$i]) || $name[$i] == '.') {
				$out .= $name[$i];
			}
		}
		return $out;
	}

	public static function getInboxPath($user_id, $inbox_id)
	{
		return str_replace(["\\", '//'], '/', config['mailboxDir'] ."/{$user_id}/inbox/{$inbox_id}/");
	}

	public static function getOutboxPath($user_id, $outbox_id)
	{
		return str_replace(["\\", '//'], '/', config['mailboxDir'] ."/{$user_id}/outbox/{$outbox_id}/");
	}

	public static function getVInboxPath($vuser_id, $user_id, $vinbox_id)
	{
		return str_replace(["\\", '//'], '/', config['mailboxDir'] ."/{$user_id}/vinbox/{$vuser_id}/{$vinbox_id}/");
	}

	/**
	* Reads the "from" header.
	*/
	public static function readFromHeader($header)
	{
		$sender_opening = strpos($header, '<');
		if($sender_opening === false) {
			return [
				'name' => $header,
				'return' => $header,
			];
		}
		else {
			$sender_closing = strrpos($header, '>');
			if($sender_closing === false) {
				return [
					'name' => $header,
					'return' => $header,
				];
			}

			if($sender_closing < $sender_opening) {
				return [
					'name' => $header,
					'return' => $header,
				];
			}
			else {
				$return = substr($header, $sender_opening + 1, $sender_closing - $sender_opening - 1);

				$name_1 = substr($header, 0, $sender_opening);
				$name_2 = substr($header, $sender_closing + 1);
				$name = "{$name_1}{$name_2}";
				$name_length = strlen($name);

				if($name_length == 0) {
					return $name;
				}

				if($name[0] == ' ') {
					$name = substr($name, 1);
				}

				if($name_length > 1 && $name[$name_length - 1] == ' ') {
					$name = substr($name, 0, $name_length - 1);
				}

				return [
					'name' => $name,
					'return' => $return,
				];
			}
		}
	}
}
