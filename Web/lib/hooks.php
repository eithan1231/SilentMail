<?php

class hooks
{
	private static $hooks = [];

	/**
	* Registeres a hook
	*/
	public static function registerHook($event, Callable $callback)
	{
		$event_lower = strtolower($event);

		hooks::$hooks["$event_lower"][] = [
			'name' => $event,
			'callable' => $callback
		];
	}

	/**
	* Runs a hook.
	*/
	public static function runHook($event, $parameters)
	{
		$event_lower = strtolower($event);

		if(!isset(hooks::$hooks["$event_lower"])) {
			return false;
		}

		if(count(hooks::$hooks["$event_lower"]) <= 0) {
			return false;
		}

		foreach(hooks::$hooks["$event_lower"] as &$hook) {
			call_user_func_array($hook['callable'], $parameters);
		}

		return true;
	}
}
