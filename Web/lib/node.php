<?php

class node
{
	/**
	* Gets the list of nodes that are used for sending
	*/
	public static function getOutNodes()
	{
		return sql::query_fetch_all("
			SELECT `id`, `is_loopback`, `creator_user_id`, `date`, `token`
			FROM `out_node`
		");
	}

	public static function authenticate($token)
	{
		$result = sql::query_fetch("
			SELECT `id`, `is_loopback`
			FROM `access_nodes`
			WHERE
				`token` = ". sql::quote($token) ."
		");

		if($result === false) {
			return function_response(false, [
				'message' => 'Invalid token'
			]);
		}

		if($result['is_loopback']) {
			if(!ip::isLocal()) {
				return function_response(false, [
					'message' => 'Connect ip is not a loopback'
				]);
			}
		}

		return function_response(true, [
			'message' => 'Authenticated successfully.'
		]);
	}

	public static function processAction($action, $post_data)
	{
		switch($action)
		{
			case "mb_exists": {
				$data = json_decode($post_data, true);

				if(!isset($data['address'])) {
					return function_response(false, [
						'message' => 'Address parameter not set'
					]);
				}

				$mailbox_exists = mailbox::doesMailboxExist($data['address']);

				return function_response(true, [
					'exists' => $mailbox_exists
				]);

				break;
			}

			case "mb_new": {
				echo "'$post_data'";
				$data = json_decode($post_data, true);

				if(
					!isset($data['sender']) ||
					!isset($data['receivers']) ||
					!isset($data['mail'])
				) {
					return function_response(false, [
						'message' => 'Parameter(s) not set'
					]);
				}

				$inbox_result = mailbox::insertInbox($data['sender'], $data['receivers'],
					base64_decode($data['mail'])
				);

				return $inbox_result;

				break;
			}

			default: {
				return function_response(false, [
					'message' => 'Action not found'
				]);

				break;
			}
		}
	}
}
