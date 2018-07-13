<?php

class html_sanitize
{
	public static function sanitize($html, $c = 1, $settings = array())
	{
		autoload::include('dependencies/htmLawed');
		return htmLawed($html, $c, $settings);
	}
}
