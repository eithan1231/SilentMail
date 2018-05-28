<?php

class mailbox
{
	/**
	* Gets the route to an inbox's attachment
	*
	* @param int $inbox_id
	* 		the inbox id.
	* @param string $internal_name
	* 		The internal name of the file.
	*/
	public static function getInboxAttachmentRoute($inbox_id, $internal_name)
	{
		global $route;
		return $route->getRoutePath('inbox_attachment', [
			'security_token' => security_token,
			'inbox_id' => $inbox_id,
			'internal_name' => $internal_name
		]);
	}

	/**
	* Gets the route to download a mail file
	*
	* @param integer $inbox_id
	* 	The ID of the inbox item.
	* @return string
	*/
	public static function getInboxMailRoute($inbox_id)
	{
		global $route;
		return $route->getRoutePath('inbox_mail_download', [
			'security_token' => security_token,
			'inbox_id' => $inbox_id
		]);
	}

	/**
	 * Downloads the raw mail object.
	 *
	 * @param integer $inbox_id
	 * 	The inbox id you want to download
	 * @param integer $user_id
	 * 	Owner of the inbox
	 */
	public static function downloadInboxMailItem($inbox_id, $user_id = ses_user_id)
	{
		$inbox_item = mailbox::getInboxItem($inbox_id, $user_id, false);
		if($inbox_item['success'] === false) {
			return $inbox_item;
		}

		$mail_location = misc::getInboxPath($user_id, $inbox_id) .'mail';
		if(!file_exists($mail_location)) {
			// Mail file doesn't exist
			output_page::setHttpStatus(404, 'Mail File Not Found');
			die();
		}
		$mail_size = filesize($mail_location);

		// Give 10 minutes to download attachment.
		set_time_limit(60 * 5);

		// Setting headers
		header("Content-length: {$mail_size}");
		header("Content-type: application/octet-stream");
		header("Content-disposition: attachment; filename=\"{$inbox_id}.mail\"; file=\"{$inbox_id}\"");
		header("X-Time: {$inbox_item['data']['time']}");
		header("X-Verified: {$inbox_item['data']['is_sender_verified']}");
		header("X-Sender-Name: {$inbox_item['data']['sender_name']}");
		header("X-Sender-Address: {$inbox_item['data']['sender_address']}");
		header("X-Subject: {$inbox_item['data']['subject']}");

		readfile($mail_location);

		return function_response(true, [
			'message' => 'success'
		]);
	}

	/**
	* Downloads an inbox's attachment file.
	*
	* @param integer $inbox_id
	*		ID of the inbox item
	* @param string $internal_name
	*		The internal name of the file (Typically a hash of the name)
	* @param integer $user_id
	*		Owner of the inbox item.
	*/
	public static function downloadInboxAttachment($inbox_id, $internal_name, $user_id = ses_user_id)
	{
		$inbox_item = mailbox::getInboxItem($inbox_id, $user_id, false);
		if($inbox_item['success'] == false) {
			return function_response(false, $inbox_item['data']);
		}

		$working_directory = misc::getInboxPath($user_id, $inbox_id);
		$attachment_directory = $working_directory .'attachments';

		if($inbox_item['data']['mail_attachments_count'] === 0) {
			if($inbox_item['success'] == false) {
				return function_response(false, [
					'message' => 'No attachments found'
				]);
			}
		}

		// Give 10 minutes to download attachment.
		set_time_limit(60 * 5);

		foreach($inbox_item['data']['mail_attachments'] as &$attachment) {
			if($attachment['internal-name'] !== $internal_name) {
				continue;
			}

			// Getting the file path of the attachment
			$attachment_file = "{$attachment_directory}/{$attachment['internal-name']}";

			// Checking if the attachment exists
			if(!file_exists($attachment_file)) {
				return function_response(false, [
					'message' => 'Attachment missing'
				]);
			}

			// Reconstructing content type
			$content_type_string = $attachment['content-type']['type'] .'/'. $attachment['content-type']['subtype'];

			// Sending content type header.
			if(in_array($content_type_string, config['trustedAttachmentMime'])) {
				header("Content-type: {$content_type_string}");
			}
			else {
				// Binary content type, will just download as a file.
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

	/**
	* Gets an inbox item (handled mail file) with all essential information
	*/
	public static function getInboxItem($inbox_id, $user_id = ses_user_id, $get_email = true)
	{
		if($user_id === false) {
			return function_response(false, [
				'message' => 'Invalid user id'
			]);
		}

		$result = sql::query_fetch("
			SELECT `time`, `is_sender_verified`, `sender_name`, `sender_address`, `subject`, `mail_headers`, `mail_attachments`
			FROM `inbox`
			WHERE
				`id` = ". sql::quote($inbox_id) ." AND
				`receiver` = ". sql::quote($user_id) ."
			LIMIT 1
		");

		if($result === false) {
			return function_response(false, [
				'message' => 'Inbox Item not found'
			]);
		}

		$working_directory = misc::getInboxPath($user_id, $inbox_id);

		if($get_email) {
			$email_path = $working_directory .'mail';

			if($f = fopen($email_path, 'r')) {
				$email_contents = fread($f, filesize($email_path));
				fclose($f);

				$mail = new email($email_contents);

				$result['mail'] = $mail;
			}
			else {
				$result['mail'] = false;
			}
		}

		$result['mail_attachments'] = json_decode($result['mail_attachments'], true);
		$result['mail_headers'] = json_decode($result['mail_headers'], true);
		$result['mail_attachments_count'] = count($result['mail_attachments']);

		return function_response(true, $result);
	}

	/**
	* Marks an inbox item (email) as read
	*/
	public static function markInboxItemRead($inbox_id, $user_id = ses_user_id)
	{
		return sql::query("
			UPDATE `inbox`
			SET `has_seen` = 1
			WHERE
				`id` = ". sql::quote($inbox_id) ." AND
				`receiver` = ". sql::quote($user_id) ."
		") !== false;
	}

	/**
	* Gets an inbox page
	*/
	public static function getInbox($user_id, $index, $per_page = 32)
	{
		$per_page = ($per_page > 100 ? 100 : $per_page);

		$result = sql::query("
			SELECT `id`, `time`, `has_seen`, `is_sender_verified`, `sender_name`, `sender_address`, `subject`
			FROM `inbox`
			WHERE `receiver` = ". sql::quote($user_id) ."
			ORDER BY `time` DESC
			LIMIT ". sql::quote($per_page) ." OFFSET ". sql::quote($index) ."
		");

		if($result->num_rows <= 0) {
			return function_response(false, [
				'message' => 'No emails found'
			]);
		}

		$data = [];
		for($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
			$data[] = $row;
		}

		return function_response(true, [
			'mail' => $data,
			'mail_count' => $i
		]);
	}

	/**
	* Gets the amount of emilas in a users inbox
	*/
	public static function getInboxCount($user_id)
	{
		$result = sql::query_fetch("
			SELECT COUNT(1) AS size
			FROM `inbox`
			WHERE
				`receiver` = ". sql::quote($user_id) ."
		");

		if($result === false) {
			// Failed query, or inbox has empty
			return false;
		}

		return $result['size'];
	}

	/**
	* Inserts a new mail to users inbox.
	*/
	public static function insertInbox($sender, $receivers, $raw_mail)
	{
		// Handle raw mail
		$email = new email($raw_mail);

		$attachments = $email->getAttachments(true);
		$attachments_no_content = $email->getAttachments(false);
		$headers = $email->getHeaders();
		$keywords = $email->getKeywords();

		// Getting json versions of the email properties.
		$headers_json = json_encode($headers);
		$attachments_no_content_json = json_encode($attachments_no_content);

		// Getting essential headers
		$subject = $email->getHeader('subject');
		$sender = $email->getHeader('from');

		// Extracting name & email from the "from" header.
		$sender = misc::readFromHeader($sender);

		// Send to users
		foreach($receivers as &$receiver) {
			try {
				if(!filters::isValidEmail($receiver)) {
					continue;
				}

				$username_end = strpos($receiver, '@');
				if($username_end === false) {
					// Invalid email
					continue;
				}

				$username = substr($receiver, 0, $username_end);
				$vbox_mode = vmailbox::exists($username);
				if($vbox_mode) {
					// ===================================================================
					// vmail
					// ===================================================================

					$vuser_info = sql::query_fetch("
						SELECT `id`, `parent_id`, `is_enabled`
						FROM `virtual_user`
						WHERE
							`username_lower` = ". sql::quote($username) ."
					");

					if($vuser_info === false || !$vuser_info['is_enabled']) {
						continue;
					}

					$user_id = $vuser_info['parent_id'];
					$vbox_id = $vuser_info['id'];

					$inbox_insert = sql::query("
						INSERT INTO `vinbox`
						(`id`, `receiver`, `receiver_parent`, `time`, `has_seen`, `is_sender_verified`, `sender_name`, `sender_address`, `subject`, `mail_headers`, `mail_attachments`)
						VALUES (
							NULL,
							". sql::quote($vbox_id) .",
							". sql::quote($user_id) .",
							". sql::quote(time) .",
							0,
							0,
							". sql::quote($sender['name']) .",
							". sql::quote($sender['return']) .",
							". sql::quote($subject) .",
							". sql::quote($headers_json) .",
							". sql::quote($attachments_no_content_json) ."
						)
					");

					// Checking query was inserted successfully
					if($inbox_insert === false) {
						// Failed to insert
						continue;
					}

					// Getting the ID of the newly inserted mail item.
					$vinbox_id = sql::query_fetch("SELECT LAST_INSERT_ID() AS vinboxid");
					$vinbox_id = ($vinbox_id === false ? false : $vinbox_id['vinboxid']);
					if($vinbox_id === false) {
						continue;
					}

					// Inserting keywords
					foreach($keywords as &$keyword) {
						$keyword_cleaned = filters::cleanKeyword($keyword);
						if(filters::isValidKeyword($keyword_cleaned)) {
							sql::query("
								INSERT INTO `vinbox_keywords`
								(`vinbox_id`, `receiver`, `receiver_parent`, `word`)
								VALUES (
									". sql::quote($vinbox_id) .",
									". sql::quote($vbox_id) .",
									". sql::quote($user_id) .",
									". sql::quote($keyword_cleaned) ."
								)
							");
						}
					}

					// Getting working directory for things like attachments.
					$working_directory = misc::getVInboxPath($vbox_id, $user_id, $vinbox_id);
					$attachment_directory = "{$working_directory}attachments/";
				}//vbox mode end
				else {
					// ===================================================================
					// normal mail
					// ===================================================================


					$user_id = user::getUserId($username);
					if($user_id === false) {
						// Username doesnt exist
						continue;
					}

					$inbox_insert = sql::query("
						INSERT INTO `inbox`
						(`id`, `receiver`, `time`, `has_seen`, `is_sender_verified`, `sender_name`, `sender_address`, `subject`, `mail_headers`, `mail_attachments`)
						VALUES (
							NULL,
							". sql::quote($user_id) .",
							". sql::quote(time) .",
							0,
							0,
							". sql::quote($sender['name']) .",
							". sql::quote($sender['return']) .",
							". sql::quote($subject) .",
							". sql::quote($headers_json) .",
							". sql::quote($attachments_no_content_json) ."
						)
					");

					// Checking query was inserted successfully
					if($inbox_insert === false) {
						// Failed to insert
						continue;
					}

					// Getting the ID of the newly inserted mail item.
					$inbox_id = sql::query_fetch("SELECT LAST_INSERT_ID() AS inboxid");
					$inbox_id = ($inbox_id === false ? false : $inbox_id['inboxid']);
					if($inbox_id === false) {
						continue;
					}

					// Inserting keywords
					foreach($keywords as &$keyword) {
						$keyword_cleaned = filters::cleanKeyword($keyword);
						if(filters::isValidKeyword($keyword_cleaned)) {
							sql::query("
								INSERT INTO `inbox_keywords`
								(`inbox_id`, `receiver`, `word`)
								VALUES (
									". sql::quote($inbox_id) .",
									". sql::quote($user_id) .",
									". sql::quote($keyword_cleaned) ."
								)
							");
						}
					}

					// Getting working directory for things like attachments.
					$working_directory = misc::getInboxPath($user_id, $inbox_id);
					$attachment_directory = "{$working_directory}attachments/";
				}// normal mail end

				// Checking if working directory exists, if it doesnt, create it.
				if(!file_exists($working_directory)) {
					mkdir($working_directory, 0770, true);
				}

				// Checking if attachment directory exists, if it doesnt, create it.
				if(!file_exists($attachment_directory)) {
					mkdir($attachment_directory, 0770, true);
				}

				// Saving attachments
				foreach($attachments as &$attachment) {
					$filename = $attachment_directory . $attachment['internal-name'];

					if(file_exists($filename)) {
						// File already exists, wtf..
						continue;
					}

					// Saving file
					if($f = fopen($filename, 'w')) {
						fwrite($f, $attachment['content']);
						fclose($f);
					}
				}

				// Wrinting the raw mail file
				if($f = fopen($working_directory .'mail', 'w')) {
					fwrite($f, $raw_mail);
					fclose($f);
				}
			}
			catch(Exception $ex) {
				exceptions::log($ex);
			}
		}

		return function_response(true, [
			'message' => ''
		]);
	}

	/**
	* Gets users outbox
	*/
	public static function getOutbox($user_id, $index, $per_page = 32)
	{
		$per_page = ($per_page > 100 ? 100 : $per_page);

		$result = sql::query("
			SELECT `id`, `time`, `has_sent`, `recipients`, `subject`
			FROM `outbox`
			WHERE
				`sender` = ". sql::quote($user_id) ."
			ORDER BY `time` DESC
			LIMIT ". sql::quote($per_page) ." OFFSET ". sql::quote($index) ."
		");

		// Making sure we've got more then 0 rows.
		if($result->num_rows <= 0) {
			return function_response(false, [
				'message' => 'No emails found'
			]);
		}

		$data = [];
		for($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
			$recipients_tmp = json_decode($row['recipients'], true);

			$data[] = [
				'id' => $row['id'],
				'time' => $row['time'],
				'has_sent' => $row['has_sent'],
				'recipients' => $recipients_tmp,
				'recipients_count' => count($recipients_tmp),
				'subject' => $row['subject']
			];
		}

		return function_response(true, [
			'mail' => $data
		]);
	}

	/**
	* Gets the total size of a users outbox. Can be used for calculating pages.
	*/
	public static function getOutboxCount($user_id)
	{
		$result = sql::query_fetch("
			SELECT COUNT(1) AS size
			FROM `outbox`
			WHERE
				`sender` = ". sql::quote($user_id) ."
		");

		if($result === false) {
			// Failed query, or outbox has empty
			return false;
		}

		return $result['size'];
	}

	public static function sendMail($user_id, $recipients, $subject, $body, $attachments)
	{
		if(strlen($recipients) <= 0) {
			return function_response(false, [
				'message' => 'Invalid Recipients.'
			]);
		}

		$subject_length = strlen($subject);
		$body_length = strlen($body);
		if(strpos($recipients, ',') !== false) {
			$recipients = explode(',', $recipients);// TODO: get recipients correctly.
			$recipients_count = count($recipients);
		}
		else {
			$recipients = [$recipients];
			$recipients_count = 1;
		}
		$user_id_group_information = group::getGroupInformationByUserId($user_id);
		$user_information = user::getUserInformation($user_id);

		if(!$user_information['success']) {
			return function_response(false, [
				'message' => 'Unable to get information linked with user'
			]);
		}

		if($subject_length > 256) {
			return function_response(false, [
				'message' => 'Invalid subject length. 256 is the limit.'
			]);
		}

		if($subject_length <= 0) {
			return function_response(false, [
				'message' => 'Invalid subject length.'
			]);
		}

		if($body_length >= 16777215) {
			return function_response(false, [
				'message' => 'Body is too large'
			]);
		}

		// checking that we got group information successfully.
		if(!$user_id_group_information['success']) {
			return function_response(false, [
				'message' => 'Unable to get group infromation on sender'
			]);
		}

		// checking maximum recipients.
		if($recipients_count > $user_id_group_information['data']['maximum_recipients']) {
			return function_response(false, [
				'message' => 'Exceeded maximum recipient count'
			]);
		}

		// validating each recipient
		foreach($recipients as &$value) {
			$value_length = strlen($value);

			// Removing first space.
			while($value_length > 0 && $value[0] == ' ') {
				$value = substr($value, 1);
				$value_length = $value_length - 1;
			}

			// Validating email
			if(!filters::isValidEmail($value)) {
				return function_response(false, [
					'message' => "<{$value}> is an invalid email address"
				]);
			}
		}

		// Getting infromation for the sql row
		$recipients_json = json_encode($recipients);

		$mail_item = new email_builder(
			(preferences::getPreferences('hide_full_name', $user_id)
				? $user_information['data']['username']
				: $user_information['data']['name_full']
			),
			misc::constructAddress($user_information['data']['username']),
			$subject
		);
		foreach ($recipients as $value) {
			$mail_item->addRecipient($value);
		}

		// Adding bodies
		$mail_item->addBody($body, 'text/plain');

		$html_body = "<div>\n";
		$html_body .= "<!-- ". esc(config['projectName'] ." - ". config['version']) ." -->\n";// Watermark
		$html_body .= esc($body);
		$html_body .= "\n</div>";
		$mail_item->addBody($html_body, 'text/html');

		// Getting mail data.
		$mail_data = $mail_item->constructMail();

		// Getting a processed mail object.
		$mail_processed = new email($mail_data);

		// Attachments
		$attachments = $mail_processed->getAttachments();

		// Getting JSON data
		$headers_json = json_encode($mail_processed->getHeaders());
		$attachments_json = json_encode($mail_processed->getAttachments(false));

		// Insert to outbox.
		$insert_result = sql::query("
			INSERT INTO `outbox`
			(`id`, `sender`, `time`, `has_sent`, `recipients`, `subject`, `mail_headers`, `mail_attachments`)
			VALUES (
				NULL,
				". sql::quote($user_id) .",
				". sql::quote(time) .",
				0,
				". sql::quote($recipients_json) .",
				". sql::quote($subject) .",
				". sql::quote($headers_json) .",
				". sql::quote($attachments_json) ."
			)
		");

		// Making sure we inserted successfully
		if($insert_result === false) {
			return function_response(false, [
				'message' => "Failed to insert query to outbox."
			]);
		}

		// Getting the outbox id.
		$outbox_id = sql::query_fetch("SELECT LAST_INSERT_ID() as id")['id'];

		// Adding header to body
		$mail_item->addHeader('Message-ID', $outbox_id);

		// Saving to HDD now.
		$working_directory = misc::getOutboxPath($user_id, $outbox_id);
		$attachments_dir = $working_directory .'attachments/';
		mkdir($working_directory, 0770, true);
		mkdir($attachments_dir, 0770, true);

		// Saving mail object.
		if($f = fopen($working_directory ."mail", 'w')) {
			fwrite($f, $mail_data);
			fclose($f);
		}
		else {
			return function_response(false, [
				'message' => "Unable to save mail file."
			]);
		}

		// Saving mail attachments
		foreach($attachments as &$attachment) {
			$filename = "{$attachments_dir}{$attachment['internal-name']}";

			if(file_exists($filename)) {
				// File already exists, wtf..
				continue;
			}

			// Saving file
			if($f = fopen($filename, 'w')) {
				fwrite($f, $attachment['content']);
				fclose($f);
			}
		}

		// Inserting keywords
		$keywords = $mail_processed->getKeywords();
		foreach($keywords as &$keyword) {
			$keyword_cleaned = filters::cleanKeyword($keyword);
			if(filters::isValidKeyword($keyword_cleaned)) {
				sql::query("
					INSERT INTO `outbox_keywords`
					(`outbox_id`, `sender`, `word`)
					VALUES (
						". sql::quote($outbox_id) .",
						". sql::quote($user_id) .",
						". sql::quote($keyword_cleaned) ."
					)
				");
			}
		}

		return function_response(false, [
			'message' => "Hey Kid! TODO: Mailbox::sendMail."
		]);

		// Getting all out nodes
		$nodes = node::getOutNodes();
		$selected_node_index = array_rand_index($nodes);
		$node = $nodes[$selected_node_index];

		// Get random node
		$node_id = 0;
		$node_address = '127.0.0.1';
		$node_port = 3456;
		$node_auth = 'thisisatest';

		// Size of buffering
		$buffer_size = 1024;

		// Creating socket
		if(!($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
			throw new Exception("Unable to create socket");
		}

		// Connecting
		if(!socket_connect($socket, $node_address, $node_port)) {
			throw new Exception("Unable to connect to mail node, node {$node_id}");
		}

		// Function for sending data.
		$send = function($buf) use(&$socket) {
			return socket_write($socket, $buf, strlen($buf));
		};

		// function for receiving data
		$recv = function() use(&$socket, $buffer_size) {
			return socket_read($socker, $buffer_size);
		};

		// Closes connection and socket
		$close = function() use(&$socket) {
			socket_shutdown($socket);
			socket_close($socket);
		};

		// Handles a response
		$handleResponse = function(&$response) {
			$response_length = strlen($response);
			if($response_length < 2) {
				return [
					'success' => false,
					'parameter' => false
				];
			}

			$parameter = false;
			if($response_length > 2) {
				$parameter = substr($response, 3);

				if($parameter[0] === ' ') {
					$parameter = substr($parameter, 1);
				}
			}

			$response_as_2_char = strtolower(substr($response, 0, 2));
			switch($response_as_2_char) {
				case "ok": {
					return [
						'success' => true,
						'parameter' => $parameter
					];
				}

				case "no":
				default: {
					return [
						'success' => false,
						'parameter' => $parameter
					];
				}
			}
		};

		try {
			// Sending initial command
			if(!$send('HELO')) {
				throw new Exception("Unable to write command");
			}

			// Receiving response on initial command
			$buffer = $recv();
			$response = $handleResponse($buffer);
			if(!$response['success']) {
				trigger_error("HELO handshake returned no. Parameter: ". htmlentities("<{$response['parameter']}>"), E_ERROR);

				return function_response(false, [
					'message' => 'Internal Error ('. __LINE__ .')'
				]);
			}

			// Sending authenticate command
			if(!$send("AUTH {$node_auth}")) {
				throw new Exception("Unable to write command");
			}

			// Receiving response for auth(entication) command
			$buffer = $recv();
			$response = $handleResponse($buffer);
			if(!$response['success']) {
				trigger_error("AUTH command returned no. Parameter: ". htmlentities("<{$response['parameter']}>"), E_ERROR);

				return function_response(false, [
					'message' => 'Internal Error ('. __LINE__ .','. $selected_node_index .')'
				]);
			}

			// Sending all recipients
			foreach($recipients as $recipient) {
				// Sending authenticate command
				if(!$send("RCPT {$recipient}")) {
					throw new Exception("Unable to write command");
				}

				// Receiving response for auth(entication) command
				$buffer = $recv();
				$response = $handleResponse($buffer);
				if(!$response['success']) {
					trigger_error("RCPT command returned no. Parameter: ". htmlentities("<{$response['parameter']}>"), E_ERROR);

					return function_response(false, [
						'message' => 'Internal Error ('. __LINE__ .')'
					]);
				}
			}

			// Constructing mailing body, and sending data command
			$constructed_email = $mail_item->constructMail();
			$constructed_email_length = strlen($constructed_email);
			$send("DATA {$constructed_email_length}");

			// Making sure the data command was sent AOK
			$buffer = $recv();
			$response = $handleResponse($buffer);
			if(!$response['success']) {
				trigger_error("DATA command returned no. Parameter: ". htmlentities("<{$response['parameter']}>"), E_ERROR);

				return function_response(false, [
					'message' => 'Internal Error ('. __LINE__ .')'
				]);
			}

			// We haven't returned, so we can send the data.
			for($i = 0; $i < round($constructed_email_length / $buffer_size); $i++) {
				$packet = substr($constructed_email, $packet_size * $i, $packet_size);
				$send($packet);
			}

			// Quitting
			$send("QUIT");
			$close();
		}
		catch(Exception $ex) {
			try {
				// Something went wrong, let's close.
				$close();

				throw $ex;
			}
			catch(Exception $e) {
				throw $e;
			}
		}
	}

	/**
	* Checks if a mailbox, or email exists.
	*/
	public static function doesMailboxExist($mailbox)
	{
		if(!filters::isValidEmail($mailbox)) {
			return false;
		}

		$username = substr($mailbox, 0, strrpos($mailbox, '@'));

		if(!filters::isValidUsername($username)) {
			return false;
		}

		$username_lower = strtolower($username);

		$result = sql::query("
			SELECT `id`
			FROM `user`
			WHERE
				`username_lower` = ". sql::quote($username_lower) ."
			UNION ALL
			SELECT `id`
			FROM `virtual_user`
			WHERE
				`username_lower` = ". sql::quote($username_lower) ."
			LIMIT 1
		");

		return $result->num_rows > 0;
	}
}
