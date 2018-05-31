<?php

class email
{
	private $m_body = '';
	private $m_bodyParsed = [];

	/** Request cache for attachments */
	private $m_attachments_content = false;
	private $m_attachments = false;

	function __construct($mail)
	{
		$parsed_mail = $this->parseMail($mail);

		if($parsed_mail['success']) {
			$parsed_body = $this->handleBody($parsed_mail['data']);

			if($parsed_body) {
				if($parsed_body['success']) {
					if($parsed_mail['data']['headers']) {
						$this->m_headers = $parsed_mail['data']['headers'];
					}

					$this->m_bodyParsed = &$parsed_body['data'];
				}
			}
		}
		else {
			return $parsed_mail;
		}
	}

	private function handleBody($mail)
	{
		$headers = &$mail['headers'];
		$body = &$mail['body'];
		$body_length = strlen($body);
		$content_type = &$mail['content-type'];

		$ret = [];
		$ret['message'] = '';
		$ret['content-type'] = &$content_type;
		$ret['body-parts'] = [];
		$ret['body-content'] = &$mail['body-content'];
		$ret['body'] = &$body;
		$ret['headers'] = &$headers;

		switch ($content_type['type']) {
			// =======================================================================
			// Multipart/*
			// Specification: https://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
			// =======================================================================
			case 'multipart': {
				if(!isset($content_type['boundary'])) {
					$ret['message'] = 'Multipart boundary not found';
					return function_response(false, $ret);
				}

				switch ($content_type['subtype']) {
					case 'related':
					case 'alternative':
					case 'mixed': {

						$boundary = "--". $content_type['boundary'];
						$boundary_length = strlen($boundary);
						$boundary_close = $boundary ."--";
						$boundary_close_length = $boundary_length + 2;

						$pos_prev = strpos($body, $boundary);
						if($pos_prev === false) {
							$pos_prev += $boundary_length;
						}

						$pos = strpos($body, $boundary, $pos_prev + 1);
						if($pos === false) {
							$pos = $body_length;
						}

						$pos_close = strpos($body, $boundary_close);
						if($pos_close === false) {
							$pos_close = $body_length;
						}

						while(true) {
							if($pos === false || $pos > $pos_close) {
								break;
							}

							$body_part = substr($body, $pos_prev, $pos - $pos_prev);

							$body_part_parsed = $this->parseMail($body_part);
							if($body_part_parsed['success']) {
								$body_part_handled = $this->handleBody($body_part_parsed['data']);

								$ret['body-parts'][] = &$body_part_handled['data'];
							}

							if($pos + 1 > $body_length) {
								break;
							}

							$pos_prev = $pos;
							$pos = strpos($body, $boundary, $pos_prev + 1);
						}

						return function_response(true, $ret);
					}

					default: {
						$ret['message'] = 'Unsupported multipart subtype';
						return function_response(false, $ret);
					}
				}

				break;
			}// multipart


			// =======================================================================
			// Text/*
			// =======================================================================
			case "text": {

				switch ($content_type['subtype']) {
					case "plain": {
						return function_response(true, $ret);
					}

					case 'html': {
						return function_response(true, $ret);
					}

					default: {
						$ret['message'] = 'Unsupported text subtype';
						return function_response(false, $ret);
					}
				}

				break;
			}// text

			default: {
				$ret['message'] = 'Unsupported content type';
				return function_response(false, $ret);
			}
		}

		$ret['message'] = 'Unknown msg';
		return function_response(false, $ret);
	}

	/**
	* Parses a raw email. Will extract headers, put headers into a nice array,
	* get content-type (will default to text/plain), then subtracts body and
	* returns it. This function should generally be fault tolerant.
	*/
	private function parseMail($part)
	{
		$body_position = strpos($part, "\r\n\r\n");
		if($body_position === false) {
			$body_position = strpos($part, "\n\n");
		}
		if($body_position === false) {
			return function_response(false, [
				'message' => 'Unable to find body position'
			]);
		}

		// Header object
		$headers = [];

		// Subtracting body and headers from the raw part
		$headers_sub = substr($part, 0, $body_position);
		$body_sub = substr($part, $body_position);
		$body_sub_length = strlen($body_sub);

		// Getting headers
		$headers_exploded = explode("\n", $headers_sub);
		$last_header = false;
		foreach($headers_exploded as &$header_data) {

			// Removing the ending \r
			$header_data_length = strlen($header_data);
			if($header_data[$header_data_length - 1] == "\r") {
				--$header_data_length;
				$header_data = substr($header_data, 0, $header_data_length);
			}

			if($last_header != false && ($header_data[0] === ' ' || $header_data[0] === "\t")) {

				$header_data = substr($header_data, 1);
				while($header_data[0] === ' ' || $header_data[0] === "\t") {
					$header_data = substr($header_data, 1);
				}

				$headers[$last_header] .= $header_data;
			}
			else {
				$value_start = strpos($header_data, ':');
				if($value_start > -1) {
					$name = strtolower(substr($header_data, 0, $value_start));
					$name_length = strlen($name);
					$value = substr($header_data, $value_start + 1);
					$value_length = strlen($value);

					if($name_length <= 0 || $value_length <= 0) {
						continue;
					}

					while($value[0] == ' ') {
						$value = substr($value, 1);
					}

					$name_lower = strtolower($name);
					$last_header = $name_lower;
					$headers["$name_lower"] = $value;
				}
			}
		}

		// Removving /r and /n from the beginning of the subtracted body.
		while($body_sub != null && ($body_sub[0] == "\r" || $body_sub[0] == "\n")) {
			$body_sub = substr($body_sub, 1);
		}

		// If the subtracted body is null, let's make it a empty string to prevent
		// errors later on.
		if($body_sub === null) {
			$body_sub = '';
		}

		// Setting content type header if it doesn't exist
		if(!isset($headers['content-type'])) {
			$headers['content-type'] = 'text/plain';
		}
		$content_type = $this->parseMimeContentType($headers['content-type']);

		// Somtimes with multipart/alternate it will have the body up the top, this
		// will be getting this body.
		$body_content = '';
		$body_content_position = strpos($body_sub, "\r\n\r\n");
		if($body_content_position <= 0) {
			$body_content_position = strpos($body_sub, "\n\n");
		}
		if($body_content_position > 0) {
			if($content_type['type'] == 'multipart' && isset($content_type['boundary'])) {
				$multipart_position = strpos($body_sub, "--{$content_type['boundary']}");

				if($body_content_position < $multipart_position) {
					$body_content = substr($body_sub, 0, $body_content_position);
				}
			}
			else {
				$body_content = substr($body_sub, 0, $body_content_position);
			}
		}

		return function_response(true, [
			'message' => '',
			'content-type' => $content_type,
			'headers' => $headers,
			'body' => $body_sub,
			'body-content' => $body_content
		]);
	}

	/**
	* Cleans mime header's value (basically just removes comments.)
	*/
	private function cleanMimeHeader(&$header)
	{
		// Removing comments
		$opening_pos = strpos($header, '(');
		if($opening_pos > -1) {
			$closing_pos = strpos($header, ')');

			$header_1 = substr($header, 0, $opening_pos);
			$header_1_length = strlen($header_1);
			$header_2 = substr($header, $closing_pos + 1);
			$header_2_length = strlen($header_2);

			if($header_1_length > 0) {
				if($header_1[0] == ' ') {
					$header_1 = substr($header_1, 1);
				}

				if($header_1[$header_1_length - 1] == ' ') {
					$header_1 = substr($header_1, 0, $header_1_length - 1);
				}
			}

			if($header_2_length > 0 && $header_2[0] == ' ') {
				$header_2 = substr($header_2, 1);
			}

			$header = $header_1 . $header_2;

			unset($header_1);
			unset($header_2);
		}
	}

	/**
	* Parses mime content type. This isn't exactly to spec, but should do fine.
	*
	* Specifications can be found on this page:
	* https://www.ietf.org/rfc/rfc2045.txt
	*/
	private function parseMimeContentType($content_type)
	{
		$content_type_exploded = explode(';', $content_type);
		$content_type_exploded_count = count($content_type_exploded);
		$ret = [];

		// Handles the type. Don't want to have duplicate chunks of code everywhere.
		$handleType = function($type_string) use(&$ret) {
			$split_pos = strpos($type_string, '/');
			if($split_pos > -1) {
				$ret["type"] = strtolower(substr($type_string, 0, $split_pos));
				$ret["subtype"] = strtolower(substr($type_string, $split_pos + 1));
			}
		};

		if($content_type_exploded_count > 1) {

			for($i = 0; $i < $content_type_exploded_count; $i++) {
				$name_end_pos = strpos($content_type_exploded[$i], '=');

				if($name_end_pos > -1) {
					$name = substr($content_type_exploded[$i], 0, $name_end_pos);
					$name_length = strlen($name);
					$value = substr($content_type_exploded[$i], $name_end_pos + 1);
					$value_length = strlen($value);

					if($name[$name_length - 1] == '"') {
						// removeing quotation marks
						$name = substr($name, 1, $name_length - 2);
					}

					if($value[$value_length - 1] == '"') {
						// Removing quotation marks
						$value = substr($value, 1, $value_length - 2);
					}

					if($name[0] == ' ') {
						// Removing first character, because it's a space.
						$name = substr($name, 1);
					}

					// Getting lowered name
					$name_lower = strtolower($name);

					$ret["$name_lower"] = $value;
				}
				else if($i == 0) {
					$handleType($content_type_exploded[$i]);
				}
			}
		}
		else {
			$handleType($content_type_exploded[0]);
		}

		return $ret;
	}

	private function parseContentDisposition($content_disposition)
	{
		$content_type_exploded = explode(';', $content_disposition);
		$content_type_exploded_count = count($content_type_exploded);
		$ret = [];

		if($content_type_exploded_count > 1) {

			for($i = 0; $i < $content_type_exploded_count; $i++) {
				$name_end_pos = strpos($content_type_exploded[$i], '=');

				if($name_end_pos > -1) {
					$name = substr($content_type_exploded[$i], 0, $name_end_pos);
					$name_length = strlen($name);
					$value = substr($content_type_exploded[$i], $name_end_pos + 1);
					$value_length = strlen($value);

					if($name[$name_length - 1] == '"') {
						// removeing quotation marks
						$name = substr($name, 1, $name_length - 2);
					}

					if($value[$value_length - 1] == '"') {
						// Removing quotation marks
						$value = substr($value, 1, $value_length - 2);
					}

					if($name[0] == ' ') {
						// Removing first character, because it's a space.
						$name = substr($name, 1);
					}

					// Getting lowered name
					$name_lower = strtolower($name);

					$ret["$name_lower"] = $value;
				}
				else if($i == 0) {
					$ret["type"] = $content_type_exploded[$i];
				}
			}
		}
		else {
			$ret["type"] = $content_type_exploded[0];
		}

		return $ret;
	}

	public function getHeader($name)
	{
		$name_lower = strtolower($name);

		if(isset($this->m_bodyParsed['headers']["$name_lower"])) {
			return $this->m_bodyParsed['headers']["$name_lower"];
		}

		return false;
	}

	public function getHeaders()
	{
		return $this->m_bodyParsed['headers'];
	}

	public function getContentType()
	{
		if($this->m_bodyParsed['content-type']) {
			return $this->m_bodyParsed['content-type'];
		}
		else {
			return '';
		}
	}

	/**
	* Gets parsed body. Has messages, different formats, attachments and such.
	*/
	public function getParsedBody()
	{
		return $this->m_bodyParsed;
	}

	public function getBody()
	{
		return $this->m_bodyParsed['body'];
	}

	public function getBodyParts()
	{
		return $this->m_bodyParsed['body-parts'];
	}

	public function getAttachments($get_content = true)
	{
		if($get_content) {
			if($this->m_attachments_content !== false) {
				return $this->m_attachments_content;
			}
		}
		else {
			if($this->m_attachments !== false) {
				return $this->m_attachments;
			}
		}

		$attachments = [];

		$getAttachments = function(&$body_parts) use (&$attachments, &$getAttachments, &$get_content) {
			foreach($body_parts as &$body_part) {
				if($body_part['content-type']['type'] === 'multipart') {
					$getAttachments($body_part['body-parts']);
					continue;
				}

				if(!isset($body_part['headers']['content-disposition'])) {
					continue;
				}

				$body_part_content_disposition = $this->parseContentDisposition(
					$body_part['headers']['content-disposition']
				);

				if(
					$body_part_content_disposition['type'] == 'attachment' ||
					$body_part_content_disposition['type'] == 'inline'
				) {

					$filename = "unknown";
					if(isset($body_part_content_disposition['filename'])) {
						$filename = $body_part_content_disposition['filename'];
					}
					else if(isset($body_part['content-type']['name'])) {
						$filename = $body_part['content-type']['name'];
					}

					$encoding = false;
					if(isset($body_part['headers']['content-transfer-encoding'])) {
						$encoding = $body_part['headers']['content-transfer-encoding'];
					}

					$content = false;
					if($get_content === true) {
						$content = ($encoding === false
							? $body_part['body']
							: encoding::convert($body_part['body'], $encoding)
						);
					}


					// Getting content id. Can be used for internally linking assets. For
					// example, user sends an image with the content id "cidexample," and
					// in the html it has an image tag with the src url scheme being "cid:"
					// it will generate a link to this assets attachment and replace the
					// src tag.
					$content_id = cryptography::randomString(32);
					if(isset($body_part['headers']['content-id'])) {
						$content_id = $body_part['headers']['content-id'];
					}


					$attachments[] = [
						'content-disposition' => $body_part_content_disposition,
						'inline' => $body_part_content_disposition['type'] == 'inline',
						'name' => $filename,
						'content' => $content,
						'content-type' => $body_part['content-type'],
						'content-id' => $content_id,
						'internal-name' => hash('sha1', $filename . $body_part['body']),
					];
				}
			}
		};

		if($this->m_bodyParsed['content-type']['type'] === 'multipart') {
			$getAttachments($this->m_bodyParsed['body-parts']);
		}

		if($get_content) {
			return $this->m_attachments_content = $attachments;
		}
		else {
			return $this->m_attachments = $attachments;
		}
	}

	public function getKeywords()
	{
		$ret = [];

		$extractKeywordsFromBody = function($to_extract, $content_type = false) use (&$ret) {
			if($content_type !== false) {
				if($content_type['type'] === 'text') {
					switch($content_type['subtype']) {
						case "html":
						case "xml": {
							$to_extract = strip_tags($to_extract);
							break;
						}

						case "plain": {
							break;
						}

						default: {
							return;
						}
					}
				}
				else {
					return;
				}
			}

			$to_extract_exploded = explode(' ', trim($to_extract));
			foreach($to_extract_exploded as &$keyword) {
				$ret[] = $keyword;
			}
		};

		$enumerateMultipart = function(&$body_parts) use (&$ret, &$extractKeywordsFromBody, &$enumerateMultipart) {
			foreach($body_parts as &$body_part) {
				if($body_part['content-type']['type'] === 'multipart') {
					$enumerateMultipart($body_part['body-parts']);
					continue;
				}

				if(isset($body_part['headers']['content-disposition'])) {
					continue;
				}

				$extractKeywordsFromBody($body_part['body'], $body_part['content-type']);
			}
		};

		if($this->m_bodyParsed['content-type']['type'] === 'multipart') {
			$enumerateMultipart($this->m_bodyParsed['body-parts']);
		}
		else if($this->m_bodyParsed['content-type']['type'] === 'text') {
			$extractKeywordsFromBody(
				$this->m_bodyParsed['body'],
				$this->m_bodyParsed['content-type']
			);
		}

		if($subject_header = $this->getHeader('subject')) {
			$extractKeywordsFromBody($subject_header);
		}

		if($from_header = $this->getHeader('from')) {
			$from_header_parsed = misc::readFromHeader($from_header);

			if(isset($from_header_parsed['name'])) {
				$extractKeywordsFromBody($from_header_parsed['name']);
			}
			if(isset($from_header_parsed['return'])) {
				$extractKeywordsFromBody($from_header_parsed['return']);
			}
		}

		return array_unique($ret);
	}
}
