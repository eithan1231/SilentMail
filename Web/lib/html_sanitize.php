<?php

class html_sanitize
{
	public static function sanitize($html)
	{
		// TODO: Complete this.
		return "<h2>Not yet HTML compatible. The displayed output <b>WILL</b> be wrong!</h2><br/>". htmlspecialchars(strip_tags($html));
	}
}
