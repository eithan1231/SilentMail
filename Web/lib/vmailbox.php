<?php

/**
* vmailbox
*/
class vmailbox
{
	/**
	* Checks if a user can create a virtual mailbox
	*/
	public static function canCreate($user_id = ses_user_id)
	{
		$vboxes_count = vmailbox::getVBoxesCount($user_id);

		// woah, my function naming is shit.
		$group_info = group::getGroupInformationByUserId($user_id);

		if($group_info['success'] === false) {
			// Failed to get info on userid, let's just throw an exception
			throw new Exception("Unable to get group information");
		}

		if(!$group_info['data']['is_enabled']) {
			return false;
		}

		return !($vboxes_count >= $group_info['data']['virtual_address_limit']);
	}

	/**
	* Creates a new vmailbox
	*/
	public static function create($vusername, $user_id = ses_user_id)
	{
		if(!vmailbox::canCreate($user_id)) {
			return function_response(false, [
				'message' => 'Unable to create vMailbox'
			]);
		}

		if(vmailbox::exists($vusername)) {
			return function_response(false, [
				'message' => 'vUsername taken'
			]);
		}

		if(credentials::usernameExists($vusername)) {
			return function_response(false, [
				'message' => 'Username is taken'
			]);
		}

		if(!($vusername_result = filters::isValidUsername2($vusername))['success']) {
			return function_response(false, [
				'message' => $vusername_result['data']['message']
			]);
		}

		$vusername_lower = strtolower($vusername);

		$result = sql::query("
			INSERT INTO `virtual_user`
			(`id`, `parent_id`, `username`, `username_lower`, `is_enabled`)
			VALUES (
				NULL,
				". sql::quote($user_id) .",
				". sql::quote($vusername) .",
				". sql::quote($vusername_lower) .",
				1
			)
		");

		if($result === false) {
			throw new Exception("Failed to insert query");
		}

		$vuser_id = sql::query_fetch("SELECT LAST_INSERT_ID() as id")['id'];

		return function_response(true, [
			'message' => 'Successful',
			'id' => $vuser_id
		]);
	}

	/**
	* Checks if a virtual mailbox exists
	*/
	public static function exists($vusername)
	{
		$vusername_lower = strtolower($vusername);

		return sql::query("
			SELECT `id`
			FROM `virtual_user`
			WHERE
				`username_lower` = ". sql::quote($vusername_lower) ."
			LIMIT 1
		")->num_rows > 0;
	}

	/**
	* Gets a users virtual mailboxes
	*/
	public static function getVBoxes($user_id = ses_user_id)
	{
		return sql::query_fetch_all("
			SELECT `id`, `username`, `username_lower`, `is_enabled`
			FROM `virtual_user`
			WHERE
				`parent_id` = ". sql::quote($user_id) ."
		");
	}

	/**
	* Gets the amount of virtual mailboxes a user has.
	*/
	public static function getVBoxesCount($user_id = ses_user_id)
	{
		return (($res = sql::query_fetch("
			SELECT count(1) as size
			FROM `virtual_user`
			WHERE `parent_id` = ". sql::quote($user_id) ."
		")) === false ? 0 : $res['size']);
	}

	/**
	* Gets info about a vbox
	*/
	public static function getVBoxInfo($id, $user_id = ses_user_id)
	{
		return sql::query_fetch("
			SELECT `id`, `parent_id`, `username`, `is_enabled`
			FROM `virtual_user`
			WHERE
				`id` = ". sql::quote($id) ." AND
				`parent_id` = ". sql::quote($user_id) ."
		");
	}

	public static function vBoxDisable($id, $user_id = ses_user_id)
	{
		return sql::query("
			UPDATE `virtual_user`
			SET `is_enabled` = 0
			WHERE
				`id` = ". sql::quote($id) ." AND
				`parent_id` = ". sql::quote($user_id) ."
		") !== false;
	}

	public static function vBoxEnable($id, $user_id = ses_user_id)
	{
		return sql::query("
			UPDATE `virtual_user`
			SET `is_enabled` = 1
			WHERE
				`id` = ". sql::quote($id) ." AND
				`parent_id` = ". sql::quote($user_id) ."
		") !== false;
	}

	/**
	* Gets a virtual inboxes inbox.
	*/
	public static function getVBoxInbox($vbox_id, $index = 0, $per_page = 32, $parent = ses_user_id)
	{
		if($per_page > 100) {
			$per_page = 100;
		}

		$result = sql::query_fetch_all("
			SELECT `id`, `receiver`, `receiver_parent`, `time`, `has_seen`, `is_sender_verified`, `sender_name`, `sender_address`, `subject`
			FROM `vinbox`
			WHERE
				`receiver` = ". sql::quote($vbox_id) ." AND
				`receiver_parent` = ". sql::quote($parent) ."
			ORDER BY `time` DESC
			LIMIT ". sql::quote($per_page) ." OFFSET ". sql::quote($index) ."
		");

		if($result === false) {
			return function_response(false, [
				'message' => 'No vInbox items found'
			]);
		}

		return function_response(true, ['mail' => $result]);
	}

	/**
	* Gets the amount of 'items' in a virtual inbox
	*/
	public static function getVBoxInboxCount($vbox_id, $parent = ses_user_id)
	{
		return (($res = sql::query_fetch("
			SELECT count(1) AS size
			FROM `vinbox`
			WHERE
				`receiver` = ". sql::quote($vbox_id) ." AND
				`receiver_parent` = ". sql::quote($parent) ."
		")) === false ? 0 : $res['size']);
	}

	public static function getVBoxInboxUnreadCount($vbox_id, $parent = ses_user_id)
	{
		return sql::query_fetch("
			SELECT count(1) AS size
			FROM `vinbox`
			WHERE
				`receiver` = ". sql::quote($vbox_id) ." AND
				`receiver_parent` = ". sql::quote($parent) ." AND
				`has_seen` = 0
		")['size'];
	}

	/**
	* Gets an inbox item (handled mail file) with all essential information
	*/
	public static function getVBoxInboxItem($vbox_id, $vinbox_id, $user_id = ses_user_id, $get_email = true)
	{
		if($user_id === false) {
			return function_response(false, [
				'message' => 'Invalid user id'
			]);
		}

		$result = sql::query_fetch("
			SELECT `time`, `is_sender_verified`, `sender_name`, `sender_address`, `subject`, `mail_headers`, `mail_attachments`
			FROM `vinbox`
			WHERE
				`id` = ". sql::quote($vinbox_id) ." AND
				`receiver` = ". sql::quote($vbox_id) ." AND
				`receiver_parent` = ". sql::quote($user_id) ."
			LIMIT 1
		");

		if($result === false) {
			return function_response(false, [
				'message' => 'vInbox Item not found'
			]);
		}

		$working_directory = misc::getVInboxPath($vbox_id, $user_id, $vinbox_id);

		if($get_email) {
			$email_path = $working_directory .'mail';

			$email_contents = '';

			if($f = fopen($email_path, 'r')) {
				$email_contents = fread($f, filesize($email_path));
				fclose($f);
			}

			$mail = new email($email_contents);

			$result['mail'] = $mail;
		}

		$result['mail_attachments'] = json_decode($result['mail_attachments'], true);
		$result['mail_headers'] = json_decode($result['mail_headers'], true);
		$result['mail_attachments_count'] = count($result['mail_attachments']);

		return function_response(true, $result);
	}

	/**
	* marks a vinbox item as read.
	*/
	public static function markVBoxItemRead($vbox_id, $vinbox_id, $user_id = ses_user_id)
	{
		return sql::query("
			UPDATE `vinbox`
			SET `has_seen` = 1
			WHERE
				`id` = ". sql::quote($vinbox_id) ." AND
				`receiver` = ". sql::quote($vbox_id) ." AND
				`receiver_parent` = ". sql::quote($user_id) ."
		") !== false;
	}

	/**
	* Gets the route to an inbox's attachment
	*/
	public static function getVBoxInboxAttachmentRoute($vbox_id, $vinbox_id, $internal_name)
	{
		return router::instance()->getRoutePath('vinbox_attachment', [
			'security_token' => security_token,
			'vinbox_id' => $vinbox_id,
			'vbox_id' => $vbox_id,
			'internal_name' => $internal_name
		]);
	}

	/**
	* Downloads an inbox's attachment file.
	*/
	public static function downloadVBoxInboxAttachment($vbox_id, $vinbox_id, $internal_name, $user_id = ses_user_id)
	{
		$vinbox_item = vmailbox::getVBoxInboxItem($vbox_id, $vinbox_id, $user_id, false);
		if($vinbox_item['success'] == false) {
			return function_response(false, $vinbox_item['data']);
		}

		$working_directory = misc::getVInboxPath($vbox_id, $user_id, $vinbox_id);
		$attachment_directory = $working_directory .'attachments';

		if($inbox_item['data']['mail_attachments_count'] === 0) {
			if($inbox_item['success'] == false) {
				return function_response(false, [
					'message' => 'No attachments found'
				]);
			}
		}

		foreach($vinbox_item['data']['mail_attachments'] as &$attachment) {
			if($attachment['internal-name'] !== $internal_name) {
				continue;
			}

			// Getting the file path of the attachment
			$attachment_file = "{$attachment_directory}/{$attachment['internal-name']}";

			// Checking if the attachment exists
			if(!file_exists($attachment_file)) {
				return function_response(false, [
					'message' => 'Internal Errror (Missing attachment)'
				]);
			}

			// Reconstructing content type
			$content_type_string = $attachment['content-type']['type'] .'/'. $attachment['content-type']['subtype'];

			// Sending content type header.
			if(in_array($content_type_string, config['trustedAttachmentMime'])) {
				header("Content-type: {$content_type_string}");
			}
			else {
				header("Content-type: application/octet-stream");
			}

			// Setting content-dispostion header_remove
			header("Content-Disposition: attachment; filename=\"{$attachment['name']}\"; name=\"{$attachment['name']}\"");

			// Outputting the file
			readfile($attachment_file);

			return function_response(true, [
				'message' => ''
			]);
		}

		return function_response(false, [
			'message' => 'Unknown attachment'
		]);
	}
}
