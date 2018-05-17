<?php

class ip
{
	public static function isLocal($ip = clientIp)
	{
		if($ip === '::1') {
			return true;
		}

		return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
	}
}
