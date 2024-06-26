<?php

class encoding
{
	public static function fromBase64(string $b64)
	{
		// Replacing some characters with nothing, decoding, and returning.
		return base64_decode(str_replace(["\r", "\n", "\t", ' '], '', $b64));
	}

	/**
	* Will convert $in to $encoding. if $encoding isn't found, it will just return
	* $in without editing it.
	*/
	public static function convert(string $in, string $encoding)
	{
		$encoding = strtolower($encoding);

		switch($encoding) {
			case "b64":
			case "base64":
			case "base-64": {
				return encoding::fromBase64($in);
			}

			case "quoted-printable": {
				return quoted_printable_decode($in);
			}

			default: {
				// Unknown encoding, let's just return it.
				return $in;
			}
		}
	}
}
