<?php

/**
* Cleans a name. Will auto capitalize, etc.
*/
function clean_name(string $name)
{
	$name_lower = strtolower($name);
	$name_exploded = explode(' ', $name_lower);
	$name_exploded_count = count($name_exploded);
	$ret = '';

	for ($i = 0; $i < $name_exploded_count; $i++) {
		$name_index_length = strlen($name_exploded[$i]);

		if($name_index_length == 0) {
			// Empty name
			continue;
		}

		$ret .= strtoupper($name_exploded[$i][0]);

		if($name_index_length >= 2) {
			$ret .= substr($name_exploded[$i], 1);
		}

		if($i !== $name_exploded_count - 1) {
			$ret .= ' ';
		}
	}

	return $ret;
}

/**
* All my functions were returning ['succ ... ta' => []], and it was looknig quite
* ugly, so I decided to have this return that in a cleaner looking way.
*/
function function_response($success, $data, $stringify = false)
{
	$ret = [
		'success' => $success,
		'data' => $data
	];

	if($stringify) {
		$ret = json_encode($ret);
	}

	return $ret;
}

/**
* in_array2
*/
function in_array2($target, $haystack)
{
	$haystack_length = strlen($haystack);
	for($i = 0; $i < $haystack_length; $i++) {
		if($haystack[$i] == $target) {
			return true;
		}
	}
	return false;
}

/**
* Will make a string smaller.
*
* For example:
* it will turn "abcdefghijklmnop"
* into something like "abcd..."
*/
function str_smallify(string $str, $len)
{
	$str_len = strlen($str);

	if($str_len < $len || $str_len < 6 || $len < 6) {
		return $str;
	}

	return substr($str, 0, $len - 3) . '...';
}

/**
* Subtracts excess from a string if the length is aboce x.
*/
function subiflen(string &$str, $len)
{
	if(strlen($str) > $len) {
		$str = substr($str, 0, $len);
	}
}

/**
* Gets the literal name of a character.
*/
function character_name($character)
{
	switch($character) {
		case ' ': {
			return 'Space';
		}

		case '!': {
			return 'Exclamation mark';
		}

		case '"': {
			return 'American quotation mark';
		}

		case '\'': {
			return 'British quotation mark';
		}

		case '#': {
			return 'Number';
		}

		case '$': {
			return 'Dollar';
		}

		case '%': {
			return 'Percent';
		}

		case '&': {
			return 'Ampersand';
		}

		case '(': {
			return 'Opening bracket';
		}

		case ')': {
			return 'Closing bracket';
		}

		case '*': {
			return 'Asterisk';
		}

		case '+': {
			return 'Plus';
		}

		case ',': {
			return 'Comma';
		}

		case '-': {
			return 'Hyphen';
		}

		case '.': {
			return 'Fullstop';
		}

		case '/': {
			return 'Forward slash';
		}

		case ':': {
			return 'Colon';
		}

		case ';': {
			return 'Semicolon';
		}

		case '<': {
			return 'Less-than';
		}

		case '=': {
			return 'Equals';
		}

		case '>': {
			return 'Greater-than';
		}

		case '?': {
			return 'Question mark';
		}

		case '@': {
			return 'At sign';
		}

		case '[': {
			return 'Opening square bracket';
		}

		case ']': {
			return 'Closing square bracket';
		}

		case '{': {
			return 'Opening curly bracket';
		}

		case '}': {
			return 'Closing curly bracket';
		}

		case '\\': {
			return 'Backslash';
		}

		case '^': {
			return 'Caret';
		}

		case '_': {
			return 'Underscore';
		}

		case '`': {
			return 'Grave accent';
		}

		case '|': {
			return 'Vbar';
		}

		case '~': {
			return 'Tilde';
		}

		default: {
			if(is_alphanumeric($character)) {
				return $character;
			}
			else {
				return "Unknown";
			}
		}
	}
}


function multiexplode($delimiters, string $string)
{
	if(is_string($delimiters)) {
		$delimiters = [$delimiters];
	}

	return explode(
		$delimiters[0],
		str_replace($delimiters, $delimiters[0], $string)
	);
}

function loadUi(string $ui) {
	$ui = str_replace('.', '/', $ui);
	$ui = WORK_DIR ."/ui/{$ui}.php";
	if(!file_exists($ui)) {
		return false;
	}
	return require $ui;
}

/**
* Gets the random index in an array. It can also blacklist indexes, which will
* be useful for getting an out-node.
*
* @return integer|boolean
*		On success, integer. On failure, false.
*
* @param array $array
*		The array you're getting a random index of.
* @param array|false $excluded_indexes
*		An array of values this function will never return.
*/
function array_rand_index($array, $excluded_indexes = false)
{
	if(!is_array($array)) {
		return false;
	}

	$count = count($array);
	if($count == 1) {
		return 0;
	}

	if($excluded_indexes !== false) {
		$rnd_index = mt_rand(0, $count - 1);
		$try_count = 0;

		while(in_array($rnd_index = mt_rand(0, $count - 1), $excluded_indexes)) {
			if(++$try_count > 32) {
				return false;
			}
		}

		return $rnd_index;
	}
	else {
		return mt_rand(0, $count - 1);
	}
}

/**
* Cleans a string from CLRF's.
*
* @param string $string
*		String to be cleaned of clrf.
*/
function remove_clrf($string)
{
	return str_replace(["\r", "\n"], '', $string);
}

function is_alphanumeric($char)
{
	// i could never remember ctype_alnum...
	return ctype_alnum($char);
}

/**
* htmlspecialchars is too long, so i made a copy of the function but a lot smaller.
*/
function esc($in, $flags = ENT_QUOTES | ENT_HTML5, $encoding = default_encoding, $double_encode = true)
{
	return htmlspecialchars($in, $flags, $encoding, $double_encode);
}

/**
* Hashes an array
*/
function hashArray($algo, $array)
{
	$handleArray = function($_array) use($handleArray)
	{
		$ret = '{';
		foreach ($_array as $key => $value) {
			if(is_array($value)) {
				$ret .= "{$key}: ". $handleArray($value) .",";
			}
			if(is_null($value)) {
				$ret .= "{$key}: NULL,";
			}
			if(is_numeric($value)) {
				$ret .= "{$key}: {$value},";
			}
			if(is_string($value)) {
				$ret .= "{$key}: \"". addslashes($value) ."\",";
			}
			if(is_bool($vale)) {
				$ret .= "{$key}: ". ($value ? 'true' : 'false') .",";
			}
		}
		$ret .= '}';
	};

	return hash($algo, $handleArray($array));
}
