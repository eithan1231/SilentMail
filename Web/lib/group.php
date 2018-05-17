<?php

class group
{
	/** Caching function return values so we dont execute the same query twice. */
	private static $group_info_cache;

	public static function getGroupInformation($group_id, $allow_cache = true)
	{
		if($allow_cache && isset(group::$group_info_cache[$group_id])) {
			return group::$group_info_cache[$group_id];
		}

		$result = sql::query_fetch("
			SELECT `name`, `is_enabled`, `is_team`, `can_blog`, `virtual_address_limit`, `maximum_recipients`, `color`
			FROM `group`
			WHERE
				`id` = ". sql::quote($group_id) ."
		");

		if($result === false) {
			return (group::$group_info_cache[$group_id] = function_response(false, [
				'message' => 'Group not found'
			]));
		}
		else {
			return (group::$group_info_cache[$group_id] = function_response(true,
				$result
			));
		}
	}

	public static function getGroupInformationByUserId($user_id)
	{
		if($user_id == ses_user_id) {
			return group::getGroupInformation(ses_group_id);
		}
		else {
			$user_info = user::getUserInformation($user_id);

			if($user_info['success']) {
				return group::getGroupInformation($user_info['data']['group_id']);
			}
			else {
				return function_response(false, [
					'message' => 'Cannot lookup user information'
				]);
			}
		}
	}
}
