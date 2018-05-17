<?php

class minify
{
	public static function minifyCss($css)
	{
		$output = "/**\n* Minified by Silents Minifer\n*/\n\n";

		$css_length = strlen($css);

		for($i = 0; $i < $css_length; $i++) {

			// Ignoring strings with singular quotation mark
			if($css[$i] == '\'') {
				$output .= $css[$i++];
				while($i < $css_length)  {
					if($css[$i] == '\'' && $css[$i - 1] != '\\') {
						$output .= $css[$i++];
						break;
					}
					$output .= $css[$i++];
				}
				continue;
			}

			// Ignoring strings with double quotation mark
			if($css[$i] == '"') {
				$output .= $css[$i++];
				while($i < $css_length)  {
					if($css[$i] == '"' && $css[$i - 1] != '\\') {
						$output .= $css[$i++];
						break;
					}
					$output .= $css[$i++];
				}
				continue;
			}

			if($i + 6 < $css_length) {
				if(
					$css[$i + 0] == '@' &&
					$css[$i + 1] == 'm' &&
					$css[$i + 2] == 'e' &&
					$css[$i + 3] == 'd' &&
					$css[$i + 4] == 'i' &&
					$css[$i + 5] == 'a'
				) {
					$output .= "\n";
					while($css[$i] !== "\n") {
						$output .= $css[$i++];
					}
					continue;
				}
			}

			if($i + 1 < $css_length) {

				// Killing comments
				if($css[$i] == '/' && $css[$i + 1] == '*') {
					$i++;
					while(true) {
						if($i + 1 >= $css_length) {
							break;
						}
						if($css[$i] == '*' && $css[$i + 1] == '/') {
							$i++;
							break;
						}
						$i++;
					}
					continue;
				}

				if($css[$i] == ' ' && $css[$i + 1] == ' ') {
					continue;
				}

				if($i > 1) {
					if(
						$css[$i] === ' ' &&
						(
							!ctype_alnum($css[$i - 1]) ||
							!ctype_alnum($css[$i + 1])
						)
					) {
						continue;
					}
				}
			}

			// Removing \r \n \t and spaces
			if($css[$i] == "\r" || $css[$i] == "\n" || $css[$i] == "\t") {
				continue;
			}

			$output .= $css[$i];
		}

		return $output;
	}

	public static function minifyJs($js)
	{
		$output = "/**\n* Minified by Silents Minifer\n*/\n\n";

		$js_length = strlen($js);

		for($i = 0; $i < $js_length; $i++) {

			// Ignoring strings with singular quotation mark
			if($js[$i] == '\'') {
				$output .= $js[$i++];
				while($i < $js_length)  {
					if($js[$i] == '\'' && $js[$i - 1] != '\\') {
						$output .= $js[$i++];
						break;
					}
					$output .= $js[$i++];
				}
				$i--;
				continue;
			}

			// Ignoring strings with double quotation mark
			if($js[$i] == '"') {
				$output .= $js[$i++];
				while($i < $js_length)  {
					if($js[$i] == '"' && $js[$i - 1] != '\\') {
						$output .= $js[$i++];
						break;
					}
					$output .= $js[$i++];
				}
				$i--;
				continue;
			}


			// Multiline comments
			if($i + 1 < $js_length && $js[$i] == '/' && $js[$i + 1] == '*') {
				$i++;
				while(true) {
					if($i + 1 >= $js_length) {
						break;
					}
					if($js[$i] == '*' && $js[$i + 1] == '/') {
						$i++;
						break;
					}
					$i++;
				}
				continue;
			}

			// Single line comments
			if($i + 1 < $js_length && $js[$i] == '/' && $js[$i + 1] == '/') {
				$i++;
				while(true) {
					if($i + 1 >= $js_length) {
						break;
					}
					if($js[$i++] == "\n") {
						break;
					}
				}
				continue;
			}

			if($js[$i] === "\t" || $js[$i] === "\r") {
				continue;
			}

			$output .= $js[$i];
		}

		return $output;
	}
}
