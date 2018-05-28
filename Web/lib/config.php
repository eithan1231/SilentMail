<?php

if(defined('config')) {
	return;
}

define("config", [
	/* The current version */
	'version' => '0.1',

	/* Name of your project. Will in included in titles, and other places. */
	'projectName' => 'SilentMail',

	/*
	* Are you in development mode? if you are, this will output errors and such.
	* If you do not know what this means, leave it set to false. Being in
	* development mode WILL result in you being vulnerable!
	*/
	'developmentMode' => true,

	/* Name of the session cookie. */
	'sessionCookieName' => '__sesn',

	/* Duration of security tokens in seconds. */
	'securityTokenExpiry' => 60 * 60 * 24,

	/* The usergroup people are assigned to after registration */
	'defaultGroup' => 1,

	/* The amount of times you can change your password within a 24h period*/
	'passwordChangeLimitPer24h' => 3,

	/*
	* If a SQL query takes longer than it should, this will automatically report
	* the query to a server with information to debug the query for later analisis.
	* I would recommend leaving this to FALSE! It could result in sensitive
	* information being sent to a server!
	*/
	'reportSlowQueries' => true,

	/**
	* This is to configure output buffering. Whatever this is set to, it will
	* send that amount of bytes in each packet. This can benifit performance.
	* To leave this at PHP's default, set it to 0 or under.
	*/
	'outputBuffering' => 2048,

	/**
	* I would recommend this be false. It being true could be a security concern.
	*/
	'areQuerySessionsAllowed' => true,

	/**
	* Settings for cache.
	*/
	'cache' => [
		/**
		* The mode we want to cache with.
		*
		* Possible modes:
		*		cache_mode_none - Default, no cache.
		*		cache_mode_file - Stores cache in files.
		*		cache_mode_redis - Stores cache with redis.
		*		cache_mode_memcached - Stores cache with memcached.
		*/
		'mode' => cache_mode_file,

		/**
		* options fot the mode, cache_mode_file.
		*/
		'file' => [
			/**
			* The directory where we want to store cache.
			*/
			'dir' => WORK_DIR .'/cache',

			/**
			* The duration the cache stays valid.
			*/
			'duration' => time_day,
		],

		/**
		* Options for the mode, cache_mode_redis.
		*/
		'redis' => [
			/**
			* Amount of time we can store it in cache.
			*/
			'duration' => time_hour,

			/**
			* The redis nodes (can be an array, or one item)
			*/
			'nodes' => [
				/**
				* Host.
				*/
				'host' => '127.0.0.1',

				/**
				* Port.
				*/
				'port' => 0,

				/**
				* The authentication password. This is optional.
				*/
				'auth' => ''
			],
		],

		/**
		* Options for the mode, cache_mode_memcached.
		*/
		'memcached' => [

		],
	],

	/**
	* This will allow us to cache UI assets. Turning this off will disable
	* minifying.
	*/
	'allowCache' => false,

	/* Enables and disables the blog */
	'blogEnabled' => true,

	/* The URL path to this projects folder, example is as follows. */
	'dirFromRoot' => '/',

	/* sql credentials */
	'sql' => [
		'username' => 'root',
		'password' => '',
		'hostname' => '127.0.0.1',
		'database' => 'mail',
	],

	/* The domain that will be at the end of email addresses. */
	'mailDomain' => 'localhost',

	/* List of resuted hosts, not connecting from one will result in page death */
	'trustedHosts' => ['localhost', "127.0.0.1"],

	/* asset directories */
	'assetDir' => '/ui/assets/',
	'assetCacheDir' => '/ui/assets.cache/',

	/*
	* directory for mailboxes. Can be useful for if you have a network drive you
	* want to store mailbox items on.
	*/
	'mailboxDir' => WORK_DIR .'/mailbox/',

	/**
	* The directory where user files will be uploaded to
	*/
	'userfileDir' => WORK_DIR .'/userfiles/',

	/* The maximum user file size. Currently at 24mb. */
	'userfileSizeLimit' => (1024 * 1024 * 24),

	/*
	* Maximum amount of keywords a user can search through when searching.
	* recommend leaving it as is.
	*/
	'searchKeywordLimit' => 12,

	/* List of trusted asset extensions */
	'extensionAssetsTrusted' => [
		'js', 'json', 'css', 'xml', 'png', 'aac', 'ico', 'gif',
		'jpeg', 'jpg', 'mpeg', 'oga',
	],

	/* The mime type (content type header) linked with trusted extensions */
	'extensionToMime' => [
		'js' => 'text/javascript', 'json' => 'text/json', 'ico' => 'image/x-icon',
		'gif' => 'image/gif', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg',
		'png' => 'image/png', 'mpeg' => 'video/mpeg', 'oga' => 'audio/ogg',
		'xml' => 'application/xml', 'css' => 'text/css', 'csv' => 'text/csv',
	],

	/*
	* Trusted attachment mime types.
	* All non trusted will be sent with application/octet-stream
	*/
	'trustedAttachmentMime' => [
		'text/plain', 'image/png', 'image/jpg', 'image/jpeg',
	],

	'seed' => '<should_be_unique_to_your_site>'
]);

// This ideally should never be true, but for some tests it's required.
define("SKIP_REGISTRATION_SECURITY_CHECKS", (
	// if development mode, hostname is localhost, and connection is a local,
	// set true.
	config['developmentMode'] && hostName == 'localhost' && (clientIp == '::1' || clientIp == "127.0.0.1")
));

// tokens/hashes
define("versionHash", hash('adler32', config['seed'] . config['version']));
define("uniqueToken", hash('md5', config['seed'] . clientIp . versionHash . time . microTime));
define("templateToken", hash('md5', config['seed'] . versionHash . clientIp));
