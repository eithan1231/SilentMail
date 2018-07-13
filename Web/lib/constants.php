<?php

if(defined('loaded_constants')) {
	return;
}
define('loaded_constants', 0);

// Client IP address. This is intended for ease of change, if you're using
// a gateway like Cloudflare.
define('clientIp', $_SERVER['REMOTE_ADDR']);

// User agent
define("userAgent", (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''));

// Hostname
define("hostName", (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));

// Boolean to say whether the request was delivered using HTTPS
define("secureRequest", !empty($_SERVER['HTTPS']));

define("default_encoding", ini_get("default_charset"));

// Request path
if(($pos = strpos($_SERVER['REQUEST_URI'], '?')) !== false) {
	// Request contains query string, let's subtract it.
	define("requestPath", substr($_SERVER['REQUEST_URI'], 0, $pos));
}
else {
	// Path with no query string. Leave it as is.
	define("requestPath", $_SERVER['REQUEST_URI']);
}

// Query string
define("queryString", $_SERVER['QUERY_STRING']);

// Time things
define('time_second', 1);
define('time_minute', 60);
define('time_hour', time_minute * 60);
define('time_day', time_hour * 24);
define('time_week', time_day * 7);
define('time_month', time_week * 4);
define('time_year', time_month * 12);
define("time", time());
define("microTime", microtime(true));

// Character related things
define('alphabet', 'abcdefghijklmnopqrstuvwxyz');
define('alphabetUpper', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
define('numbers', '1234567890');

// cache modes.
define('cache_mode_none', 0);
define('cache_mode_file', 1);
define('cache_mode_redis', 2);
define('cache_mode_memcached', 3);
define('cache_mode_sql', 4);

define("sm_webhook_sslpolicy_none", 0);// No SSL policy; allow ssl or non ssl.
define("sm_webhook_sslpolicy_force", 1);// Must have SSL.
define("sm_webhook_sslpolicy_disallow", 2);// SSL is strictly not allowed.
