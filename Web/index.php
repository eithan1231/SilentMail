<?php

// Removing headers that set suto automatically
header_remove("Server");
header_remove("X-Powered-By");

// Defaulting to html content type. (incase someone changed ini)
header("Content-type: text/html");

// Defining working directory. If you want to store assets and stuff in another
// directory outside of www, edit this.
define("WORK_DIR", __DIR__);

// Includes and initialization
require WORK_DIR . "/lib/constants.php";
require WORK_DIR . "/lib/config.php";
require WORK_DIR . "/lib/autoloader.php";
autoload::Initialize(WORK_DIR ."/lib/");
autoload::include('functions');
request_check::check();
autoload::include('globals');

// Starting exception handler
exceptions::initializeHandler();

// Logging request.
logs::logRequest();

// Disabling error reporting in non-development
if(!config['developmentMode']) {
	error_reporting(0);
}

// Caching engine
$cache = null;
switch(config['cache']['mode']) {
	case cache_mode_file: {
		global $cache;
		$cache = new cache_file(
			config['cache']['file']['dir'],
			config['cache']['file']['duration']
		);
		break;
	}

	case cache_mode_redis: {
		global $cache;
		$cache = new cache_file(
			config['cache']['redis']['nodes'],
			config['cache']['redis']['duration']
		);
		break;
	}

	case cache_mode_none:
	default: {
		global $cache;
		$cache = new cache_none();
		break;
	}
}

$route = new router(config['dirFromRoot']);




// =============================================================================
// Special pages
// =============================================================================
$route->registerSpecial("404", function($path) {
	if(ses_awaiting_security_check) {
		router::instance()->redirectRoute("security_check");
	}

	output_page::SetHttpStatus(404, "Not Found");

	define('path_404', $path);
	loadUi("special.404");
});
$route->registerSpecial("500", function($exception) {
	output_page::SetHttpStatus(500, "Internal Error");

	if(config['developmentMode']) {
		header("Content-type: text/plain");
		var_dump($exception);
	}
	else {
		loadUi("special.500");
	}

	die();
});
$route->registerSpecial('prepend', function() {
	// Output buffering
	if(config['outputBuffering'] <= 0) {
		ob_start(null, config['output_buffering']);
	}

	if(!ses_group_enabled) {
		// Account is disabled
		loadUi('special.disabled');
		die();
	}
});
$route->registerSpecial('append', function() {
	// Append is called after route is complete.

	// Ending output buffering
	if(config['outputBuffering'] <= 0) {
		ob_end_flush();
	}

	// Closing mysql connection, not needed anymore.
	sql::close();
});




// =============================================================================
// All post routes
// =============================================================================
$route->registerPostRoute(
	'post',
	'/p/{security_token}/{action}',
	function($route, $p, $post_data) {
		$security_token_verification = security_tokens::verifySecurityToken(
			$p['security_token'],
			ses_user_id
		);

		if($security_token_verification['data']['valid']) {
			post::processPost($p['action'], $post_data);
		}
		else {
			output_page::SetHttpStatus(401, "Invalid Token");
		}
	}
);




// =============================================================================
// Loading assets route
// =============================================================================
$route->registerGetRoute(
	'asset',
	'/a/{version}/{type}/{asset_name}',
	function($route, $p) {
		$type = $p['type'];
		$asset_name = $p['asset_name'];

		$asset_location = WORK_DIR . config['assetDir'];
		$cache_location = WORK_DIR . config['assetCacheDir'];

		$asset_loading = new assetloader($asset_location, $cache_location);
		if(!$asset_loading->LoadAsset($asset_name, $type)) {
			output_page::SetHttpStatus(404, "Not Found");
		}
	}, false, ['append_global_query' => false]
);




// =============================================================================
// Template
// =============================================================================
$route->registerGetRoute('template', '/t/{token}/{name}', function($route, $p) {
	if(!template::outputTemplate($p['name'], $p['token'])) {
		output_page::SetHttpStatus(500, "Invalid Template or Token");
	}
}, false, ['append_global_query' => false]);




// =============================================================================
// Landing page
// =============================================================================
$route->registerGetRoute('landing', ['/', '/index'], function($route) {
	if(ses_awaiting_security_check) {
		$route->redirectRoute("security_check");
	}

	loadUi("landing");
}, true);




// =============================================================================
// Mailbox page
// =============================================================================
$route->registerGetRoute('mail', ['/mail/', '/mail'], function($route) {
	if(ses_awaiting_security_check) {
		$route->redirectRoute("security_check");
	}
	if(!ses_logged_in) {
		$route->redirectRoute("authenticate");
	}

	loadUi("mail");
});




// =============================================================================
// Authentication page. Will have Login, and Registration.
// =============================================================================
$route->registerGetRoute('authenticate', '/auth', function($route, $p) {
	if(ses_awaiting_security_check) {
		$route->redirectRoute("security_check");
	}
	if(ses_logged_in) {
		$route->redirectRoute("mail");
	}
	loadUi("authentication");
}, true);




// =============================================================================
// Users' profile picture.
// =============================================================================
$route->registerGetRoute('profile_picture', '/{username}/picture', function($route, $p) {

});




// =============================================================================
// Users' profile page.
// =============================================================================
$route->registerGetRoute(
	'profile_page',
	['/{username}', '/{username}/'],
	function($route, $p) {
		if(ses_awaiting_security_check) {
			$route->redirectRoute("security_check");
		}

		if(!filters::isValidUsername($p['username'])) {
			// continue search.
			return true;
		}

		if(user::getUserId($p['username']) === false) {
			// User not found; continue search.
			return true;
		}

		define("PROFILE_PAGE_USERNAME", $p['username']);

		loadUi('profile_page');
	},
	true
);




// =============================================================================
// If a account is flagged, we will challenge the user to remove it here.
// =============================================================================
if(ses_awaiting_security_check) {
	$route->registerGetRoute(
		'security_check',
		'/'. hash('md5', clientIp . ses_user_id .'securitycheck'),
		function($route, $p) {
			loadUi("security_check");
		}
	);
}




// =============================================================================
// Logout
// =============================================================================
$route->registerGetRoute('logout', '/logout/{security_token}', function($route, $p) {
	if(!ses_logged_in) {
		$route->redirectRoute("landing");
	}
	if(ses_awaiting_security_check) {
		$route->redirectRoute("security_check");
	}

	$security_token_verification = security_tokens::verifySecurityToken(
		$p['security_token'],
		ses_user_id
	);

	if($security_token_verification['data']['valid']) {
		session::deactivateToken(cookies::getSession(), ses_user_id);
		cookies::setSession('deleted', 0);
	}

	$route->redirectRoute("landing");
});




// =============================================================================
// API
// =============================================================================
$route->registerPostRoute('api', '/api/{version}/{key}/{action}', function($route, $p, $post_data) {
	// Json header
	header("Content-type: text/json");

	// Authenticating
	$api_auth = api::authenticate($p['key']);
	if(!$api_auth['success']) {
		die(function_response(false, [
			'message' => 'Authentication failed'
		], true));
	}

	switch($p['version']) {
		case "v1": {
			die(json_encode(api_v1::processAction($p['action'])));
		}

		default: {
			die(function_response(false, [
				'message' => 'Unsupported API version'
			], true));
			break;
		}
	}
});




// =============================================================================
// Blog Landing Page
// =============================================================================
$route->registerGetRoute('blogLanding', ['/blog', '/blog/'], function($route) {
	if(!config['blogEnabled']) {
		$route->redirectRoute('landing');
	}

	// Loading blog landing page
	loadUi("blog.landing");
}, config['blogEnabled']);




// =============================================================================
// Blog Category Viewer
// =============================================================================
$route->registerGetRoute('blogCategory', ['/blog/{category}/', '/blog/{category}'], function($route, $p) {
	if(!config['blogEnabled']) {
		$route->redirectRoute('landing');
	}

	// Defining the category parameter globally so the category page has access to it.
	define("BLOG_CATEGORY", $p['category']);

	// Loading category page
	loadUi("blog.category");
}, config['blogEnabled']);




// =============================================================================
// New blog post
// =============================================================================
$route->registerGetRoute('blogNew', '/blog/{category}/new', function($route, $p) {
	if(!config['blogEnabled']) {
		$route->redirectRoute('landing');
	}

	// Defining variables so the post page has access to them.
	define("BLOG_CATEGORY", $p['category']);

	// Loading post (thread) page.
	loadUi("blog.new");
}, config['blogEnabled']);




// =============================================================================
// Blog Post Viewer
// =============================================================================
$route->registerGetRoute(
	'blogPost',
	['/blog/{category}/{thread}', '/blog/{category}/{thread}/'],
	function($route, $p) {
		if(!config['blogEnabled']) {
			$route->redirectRoute('landing');
		}

		// Defining variables so the post page has access to them.
		define("BLOG_CATEGORY", $p['category']);
		define("BLOG_THREAD", $p['thread']);

		// Loading post (thread) page.
		loadUi("blog.post");
	},
	config['blogEnabled']
);




// =============================================================================
// robots.txt
// =============================================================================
$route->registerGetRoute('robotsTxt', '/robots.txt', function($route, $p) {
	header("Content-type: text/plain");
	loadUi("robots_txt");
});




// =============================================================================
// terms of service
// =============================================================================
$route->registerGetRoute(
	'termsOfService',
	['/terms-of-service', '/terms-of-service/', '/tos', '/tos/'],
	function($route, $p) {
		loadUi("terms_of_service");
	}
);




// =============================================================================
// terms of service
// =============================================================================
$route->registerGetRoute(
	'contactUs',
	['/contact-us', '/contact-us/'],
	function($route, $p) {
		loadUi("contact_us");
	}
);




// =============================================================================
// The interface between the nodes (things that listen for emails) and the server
// =============================================================================
$route->registerPostRoute('nodeInterface', '/node/{key}/{action}/', function($route, $p, $post_data) {
	$node_auth_result = node::authenticate($p['key']);

	header("Content-type: text/json");

	if($node_auth_result['success']) {
		// Node calls might last a long time, let's never timeout.
		set_time_limit(0);

		$action_result = node::processAction($p['action'], $post_data);

		die(json_encode($action_result));
	}
	else {
		die(json_encode($node_auth_result));
	}
});




// =============================================================================
// Access log exporter
// =============================================================================
$route->registerGetRoute(
	'access_log_export',
	'/accesslogexporter/{security_token}/',
	function($route, $p) {
		if(!ses_logged_in) {
			$route->redirectRoute("landing");
		}
		if(ses_awaiting_security_check) {
			$route->redirectRoute("security_check");
		}

		$security_token_verification = security_tokens::verifySecurityToken(
			$p['security_token'],
			ses_user_id
		);

		if($security_token_verification['data']['valid']) {
			header("Content-type: text/json");
			header("Content-disposition: attachment; name=\"Access Logs\"; filename=\"Access Logs.json\"");

			$logs = logs::getLoginLogs(ses_username);

			die(function_response($logs !== false, $logs, true));
		}
		else {
			output_page::SetHttpStatus(401, "Invalid Token");
		}
	}
);




// =============================================================================
// Favicon
// =============================================================================
$route->registerGetRoute(
	'favicon',
	'/favicon.ico',
	function($route, $p) {
		$asset_location = WORK_DIR . config['assetDir'];
		$cache_location = WORK_DIR . config['assetCacheDir'];

		$asset_loading = new assetloader($asset_location, $cache_location);
		if(!$asset_loading->LoadAsset("favicon", "ico")) {
			output_page::SetHttpStatus(404, "Not Found");
		}
	}
);




// =============================================================================
// Notification redirect
// =============================================================================
$route->registerGetRoute('notification_redirect', '/notification_redirect/{id}', function($route, $p) {
	if(!ses_logged_in) {
		$route->redirectRoute("landing");
	}
	if(ses_awaiting_security_check) {
		$route->redirectRoute("security_check");
	}

	$notification = notifications::getNotification($p['id'], ses_user_id);
	if($notification != false) {

		notifications::markRead($p['id'], ses_user_id);

		if($notification['link'][0] == '#' || $notification['link'][0] == '?') {
			die("<script>window.close();</script>");
		}

		header("Location: {$notification['link']}");
		die();
	}
	else {
		header("Content-type: text/html");
		die("<script>window.close();</script>");
	}
});




// =============================================================================
// Inbox attachment downloader
// =============================================================================
$route->registerGetRoute(
	'inbox_attachment',
	'/inboxattachmentdownloader/{security_token}/{inbox_id}/{internal_name}/',
	function($route, $p) {
		if(!ses_logged_in) {
			$route->redirectRoute("landing");
		}
		if(ses_awaiting_security_check) {
			$route->redirectRoute("security_check");
		}

		$security_token_verification = security_tokens::verifySecurityToken(
			$p['security_token'],
			ses_user_id
		);

		if($security_token_verification['data']['valid']) {
			$result = mailbox::downloadInboxAttachment($p['inbox_id'], $p['internal_name'], ses_user_id);

			if(!$result['success']) {
				header("Content-type: text/plain");
				die($result['data']['message']);
			}
		}
		else {
			output_page::SetHttpStatus(401, "Invalid Token");
		}
	}
);




// =============================================================================
// inbox mail downloader
// =============================================================================
$route->registerGetRoute(
	'inbox_mail_download',
	'/inboxmaildownloader/{security_token}/{inbox_id}/',
	function($route, $p) {
		if(!ses_logged_in) {
			$route->redirectRoute("landing");
		}
		if(ses_awaiting_security_check) {
			$route->redirectRoute("security_check");
		}

		$security_token_verification = security_tokens::verifySecurityToken(
			$p['security_token'],
			ses_user_id
		);

		if($security_token_verification['data']['valid']) {
			$result = mailbox::downloadInboxMailItem($p['inbox_id']);

			if(!$result['success']) {
				header("Content-type: text/plain");
				die($result['data']['message']);
			}
		}
		else {
			output_page::SetHttpStatus(401, "Invalid Token");
		}
	}
);




// =============================================================================
// vInbox attachment downloader
// =============================================================================
$route->registerGetRoute(
	'vinbox_attachment',
	'/vinboxattachmentdownloader/{security_token}/{vbox_id}/{vinbox_id}/{internal_name}/',
	function($route, $p) {
		if(!ses_logged_in) {
			$route->redirectRoute("landing");
		}
		if(ses_awaiting_security_check) {
			$route->redirectRoute("security_check");
		}

		$security_token_verification = security_tokens::verifySecurityToken(
			$p['security_token'],
			ses_user_id
		);

		if($security_token_verification['data']['valid']) {
			$result = vmailbox::downloadVBoxInboxAttachment($p['vbox_id'], $p['vinbox_id'], $p['internal_name']);

			if(!$result['success']) {
				header("Content-type: text/plain");
				die($result['data']['message']);
			}
		}
		else {
			output_page::SetHttpStatus(401, "Invalid Token");
		}
	}
);




// =============================================================================
// Testing routes.
// =============================================================================
if(config['developmentMode']) {
	define('TEST_DEBUG', 1);

	$route->registerGetRoute('test_nav', '/test/', function($route) {
		tests::renderNavPage();
	});

	$route->registerGetRoute('test', '/test/{action}', function($route, $p) {
		tests::runTest($route, $p);
	});
}

$route->runRoutes();
