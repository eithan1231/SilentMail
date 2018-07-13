<?php

class credentials
{
	/**
	* Authenticates a username and password.
  * @param [type] $username
  * 		Username to the user who's logging in
  * @param [type] $password
  * 		Password to the user who's loggin in.
  */
	public static function authenticate(string $username, string $password)
	{
		// Getting information that could help is tell whether this account needs
		// blocking.
		$login_attempt_count = 0;
		$login_attempt_count_ip = 0;
		$updateAttemptCount = function(&$user_id) use ($username, $login_attempt_count, $login_attempt_count_ip) {
			$login_attempt_count = logs::loginAttemptCount($username);
			$login_attempt_count_ip = logs::loginAttemptCountFromIp();

			if($login_attempt_count >= 5) {
				if($user_id != false) {
					user::forceSecurityCheck($user_id);
				}

				if($login_attempt_count === $login_attempt_count_ip) {
					blocks::blockLogin(clientIp, time_minute * 15);
					blocks::blockRegister(clientIp, time_hour);
				}
				else {
					blocks::blockAccount($username);
				}
			}
			else if($login_attempt_count_ip > 5) {
				blocks::blockLogin(clientIp, time_minute * 15);
				blocks::blockRegister(clientIp, time_hour);

				if($user_id != false) {
					user::forceSecurityCheck($user_id);
				}
			}
		};

		// Checking if the IP has been temporarily blocked from logging in
		if(blocks::isLoginBlocked()) {
			return function_response(false, [
				'message' => 'You have been temporarily blocked from logging in. Check back in 15 minutes.',
			]);
		}

		// Making sure the username is valid
		if(!filters::isValidUsername($username)) {
			return function_response(false, [
				'message' => 'Invalid username'
			]);
		}

		if(!credentials::usernameExists($username)) {

			// Logging failed login attempt
			logs::logLogin($username, false);

			return function_response(false, [
				'message' => 'Unknown username'
			]);
		}

		if(vmailbox::exists($username)) {

			// Logging failed login attempt
			logs::logLogin($username, false);

			return function_response(false, [
				'message' => 'cannot login to virtual mailboxes.'
			]);
		}

		// Checking if the account has been blocked
		if(blocks::isAccountBlocked($username)) {
			return function_response(false, [
				'message' => 'Account has been temporarily blocked from logging in. Check back in 15 minutes.',
			]);
		}

		$username_lower = strtolower($username);

		// Getting data linked with username
		$result = sql::query_fetch("
			SELECT `id`, `username`, `password`, `salt`, `group_id`, `force_security`, `country`
			FROM `user`
			WHERE
				`username_lower` = ". sql::quote($username_lower) ."
		");

		// Setting user id
		$user_id = intval($result['id']);

		// Hashing the password that we're comparing
		$password_hashed = cryptography::hashPassword($password, $result['salt']);

		// Comparing passwords
		if($password_hashed != $result['password']) {
			// logging login
			logs::logLogin($username, false);

			// Wrong password, lets check if user just entered an old password

			// Getting old passwords
			$password_history = sql::query_fetch_all("
				SELECT `password`, `salt`, `date`, `ip`, `user_agent`
				FROM `password_history`
				WHERE
					`user_id` = ". sql::quote($user_id) ."
			");

			// Checking if query was successful, if it is, enumerate through all old
			// passwords, and compare them.
			if($password_history !== false) {
				foreach($password_history as $history_index) {
					$attempt_password_hashed = cryptography::hashPassword(
						$password,
						$history_index['salt']
					);

					if($attempt_password_hashed == $history_index['password']) {
						return function_response(false, [
							'message' => 'You entered an old password. It was changed '. time::formatFromPresent($history_index['date'])
						]);
					}
				}
			}

			$updateAttemptCount($user_id);

			return function_response(false, [
				'message' => 'Invalid Password'
			]);
		}
		else {
			// Logging successful login
			logs::logLogin($username, true);
			$updateAttemptCount($user_id);
		}

		if(flagged::isIpFlagged()) {
			// IP has been flagged, this does not mean it's a bad ip with bad intentions
			// either way, let's force additional security.
			$result['force_security'] = true;
		}

		// Geo location checks
		if(!$result['force_security']) {
			if($result['country'] !== '') {

				// Checking if we're logging it from another country
				$geo_info = geo::getGeoInfo();
				if($geo_info) {
					if($result['country'] !== $geo_info['iso_code_2']) {
						// Logging in from another country, lets prompt with security check
						$result['force_security'] = true;
					}
				}
				else {
					// Failed to get geo information, lets force security check
					$result['force_security'] = true;
				}
			}
			else {
				// unknown registration country.
			}
		}

		return function_response(true, [
			'message' => 'Authenticated successfully',
			'user_id' => $result['id'],
			'username' => $result['username'],
			'group_id' => $result['group_id'],
			'force_security_check' => $result['force_security'],
		]);
	}

	/**
	* Create a new user account.
	*
	* @param string $username
	* 		The username of the account (prefixed to @xx.com)
	* @param string $password
	* 		Password of the account
	* @param string $security_question
	* 		The security questions, question.
	* @param string $security_answer
	* 		Answer to security question
	* @param string $security_hint
	* 		Optional security question hint
	* @param string $first_name
	* 		First name of the user
	* @param string $last_name
	* 		Last name of the user
	*/
	public static function register(
		string $username, string $password,
		string $security_question, string $security_answer, string $security_hint,
		$first_name = '', $last_name = ''
	) {

		// is registration blocked
		if(!SKIP_REGISTRATION_SECURITY_CHECKS && blocks::isRegisterBlocked()) {
			return function_response(false, [
				'message' => 'You have been blocked from registering. Check back later.',
			]);
		}

		if(!filters::isValidFirstName($first_name)) {
			return function_response(false, [
				'message' => 'Invalid first name'
			]);
		}

		if(!filters::isValidLastName($last_name)) {
			return function_response(false, [
				'message' => 'Invalid last name'
			]);
		}

		if(!($username_result = filters::isValidUsername2($username))['success']) {
			return function_response(false, [
				'message' => $username_result['data']['message']
			]);
		}

		// is valid password
		if(!SKIP_REGISTRATION_SECURITY_CHECKS && !filters::isValidPassword($password)) {
			return function_response(false, [
				'message' => 'Password not valid.'
			]);
		}

		if(credentials::usernameExists($username)) {
			return function_response(false, [
				'message' => 'Username is taken'
			]);
		}

		if(vmailbox::exists($username)) {
			return function_response(false, [
				'message' => 'Username is taken'
			]);
		}

		// is valid security question
		if(!SKIP_REGISTRATION_SECURITY_CHECKS && !filters::isValidSecurityQuestion($security_question)) {
			return function_response(false, [
				'message' => 'Invalid security question'
			]);
		}

		// is valid security answer
		if(!SKIP_REGISTRATION_SECURITY_CHECKS && !filters::isValidSecurityAnswer($security_answer)) {
			return function_response(false, [
				'message' => 'Invalid security answer'
			]);
		}

		// is valid security hint
		if(!SKIP_REGISTRATION_SECURITY_CHECKS && !filters::isValidSecurityHint($security_hint)) {
			return function_response(false, [
				'message' => 'Invalid security hint'
			]);
		}

		$salt = cryptography::randomString(64, true);
		$password_hashed = cryptography::hashPassword($password, $salt);
		$username_lower = strtolower($username);
		$geo_information = geo::getGeoInfo();

		if($geo_information !== false) {
			$iso_code_2 = $geo_information['iso_code_2'];
		}
		else {
			$iso_code_2 = '';
		}

		$result = sql::query("
			INSERT INTO `user`
			(`id`, `username`, `username_lower`, `password`, `salt`, `group_id`, `force_security`, `name_first`, `name_last`, `country`, `picture_userfiles_id`, `manageable`)
			VALUES (
				NULL,
				". sql::quote($username) .",
				". sql::quote($username_lower) .",
				". sql::quote($password_hashed) .",
				". sql::quote($salt) .",
				". sql::quote(config['defaultGroup']) .",
				0,
				". sql::quote($first_name) .",
				". sql::quote($last_name) .",
				". sql::quote($iso_code_2) .",
				0,
				1
			)
		");

		// Getting user id, probably better ways to do this, but o well.
		$user_id = sql::getLastInsertId();

		// Initialize user preferences (will insert default values.)
		preferences::initializePreferences($user_id);

		logs::logRegister($username);

		// Inserting security questions
		$security_question_result = security_questions::newPair(
			$user_id,
			$security_hint,
			$security_question,
			$security_answer
		);

		// Checking the seucity questions were inserted successfully
		if($security_question_result['success'] === false) {
			return function_response(false, [
				'message' => $security_question_result['data']['message']
			]);
		}

		// push registration notification
		notifications::push('Registration', '#', $user_id);

		return function_response(true, [
			'user_id' => $user_id
		]);

		//old
		return [
			'success' => true,
			'data' => [
				'user_id' => $user_id
			]
		];
	}

	/**
	* Checks if a username exists
	* @param string $username
	* 		username you want to check exists.
	*/
	public static function usernameExists(string $username)
	{
		$username_lower = strtolower($username);
		return sql::query("
			SELECT `id`
			FROM `user`
			WHERE `username_lower` = ". sql::quote($username_lower) ."
		")->num_rows > 0;
	}

	/**
	* Changes a users password
	* @param integer $user_id
	*		The user id whose password is to be changes
	* @param string $current_password
	*		The current password of the user, used for verification
	* @param string $new_password
	*		The new password of the user
	* @param string $new_password_verification
	*		Verification of the new password
	*/
	public static function changePassword(
		$user_id,
		string $current_password, string $new_password, string $new_password_verification
	) {
		global $cache;

		// Comparing new password, and the new passworf verification
		if($new_password !== $new_password_verification) {
			return function_response(false, [
				'message' => 'New password does not match comparable password'
			]);
		}

		// Checking new password
		if(!filters::isValidPassword($new_password)) {
			return function_response(false, [
				'message' => 'New password is invalid.'
			]);
		}

		// Checking current password
		if(!filters::isValidPassword($current_password)) {
			return function_response(false, [
				'message' => 'Current password is invalid.'
			]);
		}

		// Checking how many times user has changed password in past 24h.
		$limit_check = sql::query_fetch("
			SELECT count(1) as `change_count`
			FROM `password_history`
			WHERE
				`user_id` = ". sql::quote($user_id) ." AND
				`date` > ". sql::quote(time - time_day) ."
		");
		if(!$limit_check || $limit_check['change_count'] >= config['passwordChangeLimitPer24h']) {
			return function_response(false, [
				'message' => 'You are limited to '. config['passwordChangeLimitPer24h'] .' password changes per day.'
			]);
		}

		// Getting current password, and salt.
		$user_lookup = sql::query_fetch("
			SELECT `password`, `salt`
			FROM `user`
			WHERE `id` = ". sql::quote($user_id) ."
		");

		// Checking the password and salt lookup doesnt return false (false
		// means no rows.)
		if($user_lookup === false) {
			return function_response(false, [
				'message' => 'User cannot be found.'
			]);
		}

		// Comparing current password
		if(cryptography::hashPassword($current_password, $user_lookup['salt']) !== $user_lookup['password']) {
			return function_response(false, [
				'message' => 'Current password does not match.'
			]);
		}

		// hashing new password
		$new_password_hashed = cryptography::hashPassword($new_password, $user_lookup['salt']);
		$user_agent = userAgent;
		subiflen($user_agent, 512);

		// Inserting password history
		if(!sql::query("
			INSERT INTO `password_history`
			(`user_id`, `password`, `salt`, `date`, `ip`, `user_agent`)
			VALUES (
				". sql::quote($user_id) .",
				". sql::quote($user_lookup['password']) .",
				". sql::quote($user_lookup['salt']) .",
				". sql::quote(time) .",
				". sql::quote(clientIp) .",
				". sql::quote($user_agent) ."
			)
		")) {
			return function_response(false, [
				'message' => 'Internal Error (Password History)'
			]);
		}

		// Password has been updated, so lets purge password history page cache
		$cache->purge($cache->buildKey("ui-pw-history"));

		// Updating password
		if(sql::query("
			UPDATE `user`
			SET
				`password` = ". sql::quote($new_password_hashed) ."
			WHERE
				`id` = ". sql::quote($user_id) ."
		")) {
			return function_response(true, [
				'message' => 'Password update successful'
			]);
		}
		else {
			return function_response(true, [
				'message' => 'Internal Error (Update Existing)'
			]);
		}
	}
}
