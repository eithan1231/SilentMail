<?php

class time
{
	public static function formatFromPresent($time)
	{
		$remainder = time - $time;

		$view_time = floor($remainder / time_second);
		if($view_time < time_minute) {
			// seconds
			if($remainder <= 5) {
				return "Just now";
			}
			else {
				return "$view_time seconds ago";
			}
		}
		else if($remainder < time_hour) {
			// minutes

			$view_time = floor($remainder / time_minute);
			if($view_time == 1) {
				return "$view_time minute ago";
			}
			else {
				return "$view_time minutes ago";
			}
		}
		else if($remainder < time_day) {
			// hours

			$view_time = floor($remainder / time_hour);
			if($view_time == 1) {
				return "$view_time hour ago";
			}
			else {
				return "$view_time hours ago";
			}
		}
		else if($remainder < time_week) {
			// days

			$view_time = floor($remainder / time_day);
			if($view_time == 1) {
				return "$view_time day ago";
			}
			else {
				return "$view_time days ago";
			}
		}
		else if($remainder < time_month) {
			// weeks

			$view_time = floor($remainder / time_week);
			if($view_time == 1) {
				return "$view_time week ago";
			}
			else {
				return "$view_time week ago";
			}
		}
		else {
			return date("F j, Y, g:i a", $time);
		}
	}

	public static function formatInFuture($time)
	{
		// TODO: Make this nicer, so like "in x minutes" or "in 17 hours"

		return date("F j, Y, g:i a", $time);
	}

	public static function format($time)
	{
		return date("F j, Y, g:i a", $time);
	}
}
