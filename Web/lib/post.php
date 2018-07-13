<?php

class post
{
	public static function getPostRoute($action)
	{
		return router::instance()->getRoutePath("post", [
			'security_token' => security_token,
			'action' => $action,
		]);
	}

	/**
	* Processes post request.
	*/
	public static function processPost($action, $post_data)
	{
		// Lets just set the header to text/plain. Some of the actions dont set the
		// require content type, and this could be a potential security rist. (XSS)
		header("Content-type: text/plain");

		switch ($action) {
			// =======================================================================
			// Login
			// =======================================================================
			case 'login': {
				// Saying content type is json.
				header("Content-type: text/json");

				// Parsing post data
				$parsed_post_data = json_decode($post_data, true);
				if(!isset($parsed_post_data['username']) || !isset($parsed_post_data['password'])) {
					die(json_encode(function_response(false, [
						'message' => 'Username or password not found'
					])));
				}

				$authenticated = credentials::authenticate(
					$parsed_post_data['username'],
					$parsed_post_data['password']
				);

				if($authenticated['success']) {
					// Creating session
					$session = session::createSession($authenticated['data']['user_id']);

					// Setting sessions cookie
					cookies::setSession(
						$session['data']['token'],
						$session['data']['expiry']
					);

					die(json_encode(function_response(true, [
						'message' => $authenticated['data']['message'],
						'redirect' => router::instance()->getRoutePath('mail'),
					])));
				}
				else {
					die(json_encode(function_response(false, [
						'message' => $authenticated['data']['message']
					])));
				}

				break;
			}

			// =======================================================================
			// Registration
			// =======================================================================
			case 'register': {

				// Saying content type is json.
				header("Content-type: text/json");

				$parsed_post_data = json_decode($post_data, true);
				if(
					!isset($parsed_post_data['username']) ||
					!isset($parsed_post_data['password']) ||
					!isset($parsed_post_data['first_name']) ||
					!isset($parsed_post_data['last_name']) ||
					!isset($parsed_post_data['security_question']) ||
					!isset($parsed_post_data['security_answer']) ||
					!isset($parsed_post_data['security_hint'])
				) {
					// username or password not found.
					die(function_response(false, [
						'message' => 'Parameters not found'
					], true));
				}

				// Code that actually registers user
				$registration = credentials::register(
					$parsed_post_data['username'],
					$parsed_post_data['password'],
					$parsed_post_data['security_question'],
					$parsed_post_data['security_answer'],
					$parsed_post_data['security_hint'],
					$parsed_post_data['first_name'],
					$parsed_post_data['last_name']
				);

				// Handling the response of the registration
				if($registration['success']) {

					// Creating a new session
					$session = session::createSession($registration['data']['user_id']);

					// And setting that session as a cookie
					cookies::setSession(
						$session['data']['token'],
						$session['data']['expiry']
					);

					// Successful registration
					die(function_response(true, [
						'message' => 'Registration Successful.',
						'redirect' => router::instance()->getRoutePath('mail'),
					], true));
				}
				else {
					// Failed registration
					die(function_response(false, [
						'message' => $registration['data']['message']
					], true));
				}

				break;
			}

			// =======================================================================
			// Sends an email
			// =======================================================================
			case 'send-mail': {
				// Saying content type is json.
				header("Content-type: text/json");

				if(ses_awaiting_security_check) {
					die(function_response(false, [
						'message' => 'Awaiting security check'
					], true));
				}

				if(!ses_group_enabled) {
					die(function_response(false, [
						'message' => 'Account Disabled'
					], true));
				}

				$data = json_decode($post_data, true);

				// Checking Parameters
				if(
					!isset($data['recipients']) ||
					!isset($data['subject']) ||
					!isset($data['body']) ||
					!isset($data['attachments'])
				) {
					die(function_response(false, [
						'message' => 'Unset Parameters'
					], true));
				}

				$response = mailbox::sendMail(
					ses_user_id,
					$data['recipients'],
					$data['subject'],
					$data['body'],
					$data['attachments']
				);
				die(function_response($response['success'], $response['data'], true));

				break;
			}

			// =======================================================================
			// Security Check
			// =======================================================================
			case 'security-check': {
				if(!ses_awaiting_security_check) {
					router::instance()->redirectRoute("landing");
				}

				// Saying content type is json.
				header("Content-type: text/json");

				if(!isset($_POST['id']) || !isset($_POST['answer'])) {
					router::instance()->redirectRoute(
						'security_check',
						false,
						"s=". urlencode("Parameters not set")
					);
				}

				$question_id = $_POST['id'];
				$answer = $_POST['answer'];

				if(strlen($question_id) <= 1) {
					router::instance()->redirectRoute(
						'security_check',
						false,
						"s=". urlencode('Invalid question id (server error)')
					);
				}

				$result = security_questions::validatePair($question_id, ses_user_id, $answer);

				if($result['success']) {
					if(session::securityCheckComplete(cookies::getSession(), ses_user_id)) {
						router::instance()->redirectRoute(
							'mail',
							false
						);
					}
					else {
						router::instance()->redirectRoute(
							'security_check',
							false,
							"s=". urlencode('Unable to verify question')
						);
					}
				}
				else {
					router::instance()->redirectRoute(
						'security_check',
						false,
						"s=". urlencode($result['data']['message'])
					);
				}

				break;
			}

			// =======================================================================
			// Create new virtual email
			// =======================================================================
			case 'vmail-create': {
				// Saying content type is json.
				header("Content-type: text/json");

				if(ses_awaiting_security_check) {
					die(function_response(false, [
						'message' => 'Awaiting security check'
					], true));
				}

				if(!ses_group_enabled) {
					die(function_response(false, [
						'message' => 'Account Disabled'
					], true));
				}

				$parsed_post_data = json_decode($post_data, true);
				if(!isset($parsed_post_data['username'])) {
					die(function_response(false, [
						'message' => 'Username not found'
					], true));
				}

				$vmailbox = vmailbox::create($parsed_post_data['username']);

				$vmailbox['data']['message'] = htmlentities($vmailbox['data']['message']);

				die(json_encode($vmailbox));

				break;
			}

			// =======================================================================
			// Disables virtual mail
			// =======================================================================
			case 'vmail-disable': {
				// Saying content type is json.
				header("Content-type: text/json");

				if(ses_awaiting_security_check) {
					die(function_response(false, [
						'message' => 'Awaiting security check'
					], true));
				}

				if(!ses_group_enabled) {
					die(function_response(false, [
						'message' => 'Account Disabled'
					], true));
				}

				$parsed_post_data = json_decode($post_data, true);
				if(!isset($parsed_post_data['id'])) {
					die(function_response(false, [
						'message' => 'Parameter not found'
					], true));
				}

				$vmailbox = vmailbox::vBoxDisable($parsed_post_data['id']);

				die(function_response($vmailbox, ['message' => ''], true));

				break;
			}

			// =======================================================================
			// Enables virtual mail
			// =======================================================================
			case 'vmail-enable': {
				// Saying content type is json.
				header("Content-type: text/json");

				if(ses_awaiting_security_check) {
					die(function_response(false, [
						'message' => 'Awaiting security check'
					], true));
				}

				if(!ses_group_enabled) {
					die(function_response(false, [
						'message' => 'Account Disabled'
					], true));
				}

				$parsed_post_data = json_decode($post_data, true);
				if(!isset($parsed_post_data['id'])) {
					die(function_response(false, [
						'message' => 'Parameter not found'
					], true));
				}

				$vmailbox = vmailbox::vBoxEnable($parsed_post_data['id']);

				die(function_response($vmailbox, ['message' => ''], true));

				break;
			}

			// =======================================================================
			// Submits new preference settings
			// NOTE: This isn't used from javascript, this is a form post.
			// =======================================================================
			case 'settings-preferences': {
				// Saying content type is json.
				header("Content-type: text/json");

				if(
					ses_awaiting_security_check ||
					!ses_group_enabled
				) {
					output_page::setHttpStatus(403, "inaccessible");
					return;
				}

				$preference_options = preferences::getPreferenceOptions();
				$set_prefernce_data = [];

				foreach ($preference_options as $key => $value) {
					$set_prefernce_data[$key] = isset($_POST[$key]);
				}

				if(!preferences::setPreferences(ses_user_id, $set_prefernce_data)) {
					output_page::setHttpStatus(500, "Settting Preference Error");
					return;
				}

				router::instance()->redirectRoute('mail');

				break;
			}

			// =======================================================================
			// Changes users password
			// =======================================================================
			case 'settings-password-change': {
				// Saying content type is json.
				header("Content-type: text/json");

				if(
					ses_awaiting_security_check ||
					!ses_group_enabled
				) {
					output_page::setHttpStatus(403, "inaccessible");
					return;
				}

				$parsed_post_data = json_decode($post_data, true);
				if(
					!isset($parsed_post_data['current_password']) ||
					!isset($parsed_post_data['new_password']) ||
					!isset($parsed_post_data['new_password_verify'])
				) {
					die(function_response(false, [
						'message' => 'Parameters not set'
					], true));
				}

				$current_password = $parsed_post_data['current_password'];
				$new_password = $parsed_post_data['new_password'];
				$new_password_verify = $parsed_post_data['new_password_verify'];

				$change_result = credentials::changePassword(
					ses_user_id, $current_password, $new_password, $new_password_verify
				);

				if($change_result['success']) {
					die(function_response(true, [
						'message' => $change_result['data']['message']
					], true));
				}
				else {
					die(function_response(false, [
						'message' => $change_result['data']['message']
					], true));
				}

				break;
			}

			// =======================================================================
			// Uploads a new user file
			// =======================================================================
			case 'user-files': {
				// Saying content type is json.
				header("Content-type: text/json");

				break;
			}

			// =======================================================================
			// Searches for a user. This is restricted to administration
			// =======================================================================
			case 'admin-query-user': {
				if(
					ses_awaiting_security_check ||
					!ses_group_enabled ||
					!ses_group_can_admin_user
				) {
					output_page::setHttpStatus(403, "inaccessible");
					return;
				}

				// Saying content type is json.
				header("Content-type: text/json");

				$input = json_decode($post_data, true);
				if(!isset($input['user'])) {
					die(function_response(false, [
						'message' => 'Username not found'
					], true));
				}

				if(strlen($input['user']) < 1) {
					die(function_response(false, false, true));
				}

				$where = "1 = 1";

				if($exact = extract_instruction('exact', $input['user'])) {
					// Searching for the exact username
					$where = "`user`.`username_lower` = ". sql::quote(strtolower($exact[0]));
				}
				else if($uid = extract_instruction('uid', $input['user'])) {
					// Searching for user id.
					$where = "`user`.`id` = ". sql::quote($uid[0]);
				}
				else if(isset($input['wildcard']) && $input['wildcard']) {
					// wild card search

					$where = "`user`.`username_lower` = ". sql::quote(
						sql::wildcardEscape(strtolower($input['user']))
					);
					$where .= " OR `user`.`username_lower` LIKE ". sql::quote(
						"%". sql::wildcardEscape(strtolower($input['user'])) ."%"
					);
					$where .= " OR `user`.`name_first` = ". sql::quote($input['user']);
					$where .= " OR `user`.`name_last` = ". sql::quote($input['user']);
				}
				else {
					// default: exact.
					$where = "`user`.`username_lower` = ". sql::quote(
						sql::wildcardEscape(strtolower($input['user']))
					);
				}


				$result = sql::query_fetch_all($q = "
					SELECT
						`user`.`id` AS id,
						`user`.`name_first` AS name_first,
						`user`.`name_last` AS name_last,
						`user`.`username` AS username,
						`user`.`group_id` AS group_id,
						`user`.`force_security` AS force_security,
						`user`.`country` AS country,
						`group`.`name` AS group_name,
						`group`.`is_enabled` AS group_is_enabled,
						`group`.`is_team` AS group_is_team,
						`group`.`can_blog` AS group_can_blog,
						`group`.`virtual_address_limit` AS group_virtual_address_limit,
						`group`.`maximum_recipients` AS group_maximum_recipients,
						`group`.`color` AS group_color
					FROM `user`
					RIGHT JOIN `group`
					ON
						`user`.`group_id` = `group`.`id`
					WHERE
						{$where}
					LIMIT 32
				");

				if($result === false) {
					die(function_response(false, false, true));
				}

				die(function_response(true, $result, true));

				break;
			}


			default: {
				break;
			}
		}
	}
}
