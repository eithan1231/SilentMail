<?php

class api_v1
{
	public static function processAction($action)
	{
		$action_lower = strtolower($action);

		switch($action_lower) {
			case "ping": {
				return function_response(true, [
					'message' => 'ping'
				]);
			}

			default: {
				return function_response(false, [
					'message' => 'Unknown action'
				]);
			}
		}
	}
}
