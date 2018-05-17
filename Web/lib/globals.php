<?php
// Initializes some global variables

if(cookies::getSession() !== false) {
	$active_session = session::getSession(cookies::getSession());
	if($active_session['success']) {
		define("ses_user_id", $active_session['data']['user_id']);
		define("ses_logged_in", true);
		define("ses_awaiting_security_check", $active_session['data']['awaiting_security_check']);

		// Getting user information
		$user_information = user::getUserInformation(ses_user_id);
		if($user_information['success']) {
			define("ses_username", $user_information['data']['username']);
			define("ses_group_id", $user_information['data']['group_id']);

			$group_information = group::getGroupInformation(ses_group_id);
			if($group_information['success']) {
				define('ses_group_name', $group_information['data']['name']);
				define('ses_group_enabled', $group_information['data']['is_enabled']);
				define('ses_group_is_team', $group_information['data']['is_team']);
				define('ses_group_vaddrlimit', $group_information['data']['virtual_address_limit']);
				define('ses_group_maximum_recipients', $group_information['data']['maximum_recipients']);
				define('ses_group_color', $group_information['data']['color']);
			}
		}
	}
}

if(!defined('ses_user_id')) {
	define("ses_user_id", false);
	define("ses_logged_in", false);
	define("ses_awaiting_security_check", false);
}
if(!defined('ses_username')) {
	define("ses_username", false);
	define("ses_group_id", false);
}
if(!defined('ses_group_name')) {
	define('ses_group_name', 'Unregistered');
	define('ses_group_enabled', true);
	define('ses_group_is_team', false);
	define('ses_group_vaddrlimit', 0);
	define('ses_group_maximum_recipients', 0);
	define('ses_group_color', 'black');
}

// Now we're getting and setting security token
$security_token = security_tokens::createSecurityToken(
	/* can be false... */
	ses_user_id
);
if($security_token['success']) {
	define("security_token", $security_token['data']['token']);
}
else {
	define("security_token", false);
}
javascript::pushVariable('security_token', security_token);


// Getting user agent
$ua = new user_agent(userAgent);
define('ua_browser', $ua->getBrowser());
javascript::pushVariable('ua_browser', ua_browser);
define('ua_browser_version', $ua->getVersion());
javascript::pushVariable('ua_browser_version', ua_browser_version);
define('ua_platform', $ua->getPlatform());
javascript::pushVariable('ua_platform', ua_platform);
unset($ua);
