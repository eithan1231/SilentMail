<?php

class preferences
{
	/**
	* To prevent duplicate queries within the same http request, we will store pref-
	* rences in this variable.
	*/
	private static $m_prefernce_cache = [];

	/**
	* Initializes user preferences. Will set default values
	*
	* @param integer $user_id
	* 	The user id whose preferences are to be initialized
	*/
	public static function initializePreferences($user_id)
	{
		// Making sure preferences aren't already in existance.
		if(preferences::getPreferences($user_id) !== false) {
			return true;
		}

		return sql::query("
			INSERT `preferences`
			(`user_id`, `hide_full_name`, `technical_mode`, `allow_profile_page`)
			VALUES (
				". sql::quote($user_id) .",
				0,
				0,
				1
			)
		") !== false;
	}

	/**
	* Gets all users preferences
	*
	* @param integer $user_id
	* 	The user id whose preferences you're getting.
	*/
	public static function getPreferences($user_id = ses_user_id)
	{
		if(isset(preferences::$m_prefernce_cache[$user_id])) {
			return preferences::$m_prefernce_cache[$user_id];
		}

		$result = sql::query_fetch("
			SELECT `hide_full_name`, `technical_mode`, `allow_profile_page`
			FROM `preferences`
			WHERE
				`user_id` = ". sql::quote($user_id) ."
		");

		if($result === false) {
			return false;
		}

		return preferences::$m_prefernce_cache[$user_id] = $result;
	}

	/**
	* Gets a single preference variable
	*
	* @param string $preference_name
	* 	Name of the preference
	* @param integer $user_id
	* 	The user id of the owning preference
	*/
	public static function getPreference($preference_name, $user_id = ses_user_id)
	{
		$preference_options = preferences::getPreferenceOptions();

		if(!isset($preference_options[$preference_name])) {
			throw new Exception("Unknown preference, {$preference_name}");
		}

		// Storing preferences to cache, so we can just get the preference variable
		// from cache
		if(!isset(preferences::$m_prefernce_cache[$user_id])) {
			preferences::getPreferences($user_id);
		}

		// Returning the cached preference.
		return preferences::$m_prefernce_cache[$user_id][$preference_name];
	}

	/**
	* Sets users preference(s).
	*
	* @param integer $user_id
	* 	the user whose preference this is
	* @param array $prefernces
	* 	array of preferences to set. Key will be the preference name, and value of
	* 	key will be .... value.
	*/
	public static function setPreferences($user_id, $prefernces)
	{
		if(!is_array($prefernces)) {
			return false;
		}

		if(preferences::getPreferences($user_id) === false) {
			return false;
		}

		$preference_options = preferences::getPreferenceOptions();

		$set = function($prefernce, $value) use(&$user_id, &$preference_options) {
			// Invalid preference name
			if(!isset($preference_options[$prefernce])) {
				return false;
			}

			// Updating cache.
			if(isset(preferences::$m_prefernce_cache[$user_id][$prefernce])) {
				if(preferences::$m_prefernce_cache[$user_id][$prefernce] == $value) {
					// Nothing needs updating.
					return;
				}
				preferences::$m_prefernce_cache[$user_id][$prefernce] = $value;
			}

			return sql::query("
				UPDATE `preferences`
				SET `{$prefernce}` = ". sql::quote($value) ."
				WHERE
					`user_id` = ". sql::quote($user_id) ."
			") !== false;
		};

		foreach($prefernces as $key => $value) {
			$key_lower = strtolower($key);
			if($set($key_lower, $value) === false) {
				return false;
			}
		}

		return true;
	}

	public static function getPreferenceOptions()
	{
		return [
			'hide_full_name' => [
				'clean_name' => 'Hide Full Name',
				'descrption' => 'This will hide your name whenever possible. An example would be when sending mail, your first and last name will be hidden, they will only have your username.'
			],
			'technical_mode' => [
				'clean_name' => 'Technical Mode',
				'descrption' => 'Give more technical options. (exporting raw mail, api settings, etc.)',
			],
			'allow_profile_page' => [
				'clean_name' => 'Allow Profile Page',
				'descrption' => 'Allows your profile to be publicly accisable by a link. Will supply users with information to contact you. All information displayed can be controled by you.'
			]
		];
	}
}
