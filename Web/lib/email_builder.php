<?php

class email_builder
{
	private $m_attachments;
	private $m_attachments_count;
	private $m_bodies;
	private $m_bodies_count;
	private $m_headers;
	private $m_headers_count;
	private $m_sender;
	private $m_sender_address;
	private $m_recipients;
	private $m_recipients_count;
	private $m_subject;
	private $m_id;
	private $m_date;


	function __construct(string $sender_name, string $sender_address, string $subject, $id = false)
	{
		$this->m_sender = $sender_name;
		$this->m_sender_address = $sender_address;
		$this->m_subject = $subject;
		$this->m_id = $id;
		$this->m_date = false;
		$this->m_bodies = [];
		$this->m_bodies_count = 0;
	}

	/**
	* Adds a header.
	*
	* @return null
	*
	* @param string $name
	*		name of the header.
	* @param string $value
	*		Value of the header.
	*/
	public function addHeader(string $name, string $value)
	{
		$name_lower = strtolower($name);

		switch ($name_lower)
		{
			case "content-type": {
				throw new Exception("Content type is set automatically.");
			}

			case "mime-version": {
				throw new Exception("Unable to set header <{$name}>");
			}

			case "from": {
				$this->m_sender = $value;
				$this->m_sender_address = $value;
				return;
			}

			case "to": {
				$this->addRecipient($value);
				return;
			}

			case "subject": {
				$this->m_subject = $value;
				return;
			}

			case "date": {
				$this->m_date = $value;
				return;
			}

			default: break;
		}

		$value = str_replace("\n", '', $value);
		$this->m_headers[] = [
			'name' => $name,
			'value' => $value
		];

		$this->m_headers_count++;
	}

	/**
	* Gets an array of the headers.
	*
	* @param string $mode
	*		This will manipluate the return value.
	*/
	public function getHeaders(string $mode = 'normal')
	{
		$ret = [];
		switch($mode) {
			case 'name_as_key': {
				foreach ($this->m_headers as $key => $value) {
					$ret[$key] = $value;
				}
				break;
			}

			case 'normal': {
				$ret = $this->m_headers;
				break;
			}

			default:
				break;
		}

		return $ret;
	}

	/**
	* Adds a recipiend
	*
	* @param string $recipiend
	*		Recipiend to be added.
	*/
	public function addRecipient(string $recipient)
	{
		$this->m_recipients[] = $recipient;
		$this->m_recipients_count++;
	}

	/**
	* Adds an attachment
	*
	* @param string $name
	*		Name of attachment
	* @param string $extension
	*		Extension of attachments name
	* @param string $content_type
	*		Mime content type
	* @param binary $data
	*		Content of attachent
	*/
	public function addAttachment(string $name, string $extension, string $content_type, string $data)
	{
		$this->m_attachments[] = [
			'name' => $name,
			'extension' => $extension,
			'content_type' => $content_type,
			'data' => $data
		];
		$this->m_attachments_count++;
	}

	/**
	* Adds body to email.
	*
	* @param string $body
	*		They body.
	* @param string $content_type
	*		The content type of the body
	* @param array $headers
	*		Headers of the body. format: {name: headername, value: headervalue}
	*/
	public function addBody(string $body, string $content_type = 'text/plain', array $headers = [])
	{
		$this->m_bodies[] = [
			'data' => $body,
			'content-type' => $content_type,
			'headers' => $headers
		];
		$this->m_bodies_count++;
	}

	/**
	* Constructs the emails body
	*/
	public function constructMail()
	{
		$ret = '';

		if($this->m_sender == $this->m_sender_address) {
			$ret .= "From: <". remove_clrf($this->m_sender_address) . ">\r\n";
		}
		else {
			$ret .= "From: ". remove_clrf($this->m_sender) ." <". remove_clrf($this->m_sender_address) . ">\r\n";
		}

		if($this->m_recipients_count > 1) {
			$ret .= "Cc: ";
			for($i = 0; $i < $this->m_recipients_count; $i++ ) {
				$ret .= "<". remove_clrf($this->m_recipients[$i]) .">";

				if($this->m_recipients_count - 1 != $i) {
					$ret .= ",";
				}
			}

			$ret .= "\r\n";
		}

		if($this->m_date === false) {
			$ret .= "Date: ". date('r', time) . "\r\n";
		}
		else {
			$ret .= "Date: {$this->m_date}\r\n";
		}

		if($this->m_id !== false) {
			$ret .= "Message-ID: ". misc::constructAddress($this->m_id) ."\r\n";
		}

		if($this->m_subject) {
			$ret .= "Subject: ". remove_clrf($this->m_subject) ."\r\n";
		}

		// Adding other headers
		if($this->m_headers) {
			foreach($this->m_headers as $header) {
				$name = remove_clrf($header['name']);
				$value = remove_clrf($header['value']);
				$ret .= "{$name}: {$value}\r\n";
			}
		}

		if($this->m_bodies_count == 0) {
			// No Body.

			$ret .= "MIME-Version: 1.0\r\n";
			$ret .= "Content-type: text/plain\r\n";
			$ret .= "\r\nNo Body.\r\n";
		}
		else if($this->m_bodies_count == 1 && $this->m_attachments_count == 0) {
			// One body, not attachments.

			$ret .= "MIME-Version: 1.0\r\n";
			$ret .= "Content-type: {$this->m_bodies[0]['content-type']}\r\n\r\n";
			$ret .= "{$this->m_bodies[0]['data']}\r\n";
		}
		else {
			// Multiple bodies and multiple attachments

			//_adler32(ret + time)-md5(uniqueToken + time)-randomstring(17)_
			$boundary = "_". hash('adler32', $ret . time) .'-'. hash('md5', uniqueToken . time) .'-'. cryptography::randomString(17) ."_";

			$ret .= "MIME-Version: 1.0\r\n";
			$ret .= "Content-type: multipart/mixed;\r\n\tboundary=\"{$boundary}\"";
			$ret .= "\r\n\r\n";

			if($this->m_attachments_count > 0) {
				foreach ($this->m_attachments as &$value) {
					// Setting boundary
					$ret .= "--{$boundary}\r\n";

					// Variables use in content disposition header
					$name = urlencode(remove_clrf($value['name']));
					$filename = "{$name}.". urlencode(remove_clrf(substr($value['extension'], 0, 3)));

					// Content type header
					$ret .= "Content-type: ". remove_clrf($value['content_type']) ."\r\n";

					// Content disposition header
					$ret .= "Content-disposition: attachment;\r\n\t";
					$ret .= "name=\"{$name}\";\r\n\t";
					$ret .= "filename=\"{$filename}\"\r\n";

					// Setting transfer encoding
					$ret .= "Transfer-encoding: base64\n\n";

					// Adding attachment to $ret
					$ret .= base64_encode($value['data']);
					$ret .= "\r\n\r\n";
				}
			}

			// Enumerating through bodies
			for($i = 0; $i < $this->m_bodies_count; $i++) {
				$body = &$this->m_bodies[$i];
				$body_headers = $body['headers'];
				$body_headers_count = count($body_headers);

				// Adding boundary
				$ret .= "--{$boundary}\r\n";

				// Adding headers
				if($body_headers_count > 0) {
					foreach($body_headers as &$value) {
						$name = remove_clrf($value['name']);
						$value = remove_clrf($value['value']);

						if(strtolower($name) == 'content-type') {
							// Cannot set content type header from the header array.
							continue;
						}

						$ret .= "{$name}: {$value}\r\n";
					}
				}

				$ret .= "Content-type: {$body['content-type']}\r\n\r\n";
				$ret .= str_replace(".\r\n", ".\n", $body['data']);
				$ret .= "\r\n\r\n";

				if($this->m_bodies_count - 1 == $i) {
					// Last body
					$ret .= "--{$boundary}--\r\n";
				}
			}
		}

		$ret .= ".\r\n";

		return $ret;
	}
}
