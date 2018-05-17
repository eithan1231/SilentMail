<?php

if(!defined("user_agent_initialize")) {
	define("user_agent_initialize", 0);

	define("UAOperatingSystem_Windows", 1);
	define("UAOperatingSystem_OSX", 2);
	define("UAOperatingSystem_xNix", 3);
	define("UAOperatingSystem_IOS", 4);
	define("UAOperatingSystem_Andoird", 5);
	define("UAOperatingSystem_Unknown", 6);

	define("UARenderType_Handhold", 1);
	define("UARenderType_Desktop", 2);
	define("UARenderType_Unknown", -1);
}

class user_agent
{
	// Is a Mozilla formatted user agent?
	private $m_is_mozilla;

	// Product name(mozilla formets will have mozilla) and version of said
	// product.
	private $m_product;
	private $m_product_version;

	// Contains system information
	private $m_system_information;
	private $m_operating_system;
	private $m_render_type;

	function __construct($user_agent = false)
	{
		if(!$user_agent) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
		}

		$product_end = strpos($user_agent, '/');
		if($product_end > -1) {

			$version_end_pos = strpos($user_agent, ' ', $product_end);

			$this->m_product = substr($user_agent, 0, $product_end);
			$this->m_product_version = substr(
				$user_agent, $product_end + 1,
				$version_end_pos - $product_end - 1
			);

			if($this->m_product == 'Mozilla') {
				// Mozilla user agent

				$this->m_is_mozilla = true;

				// Getting sys info
				$sys_info_pos = strpos($user_agent, '(', $version_end_pos);
				$sys_info_pos_end = strpos($user_agent, ')', $sys_info_pos);
				$this->m_system_information = substr(
					$user_agent,
					$sys_info_pos + 1,
					$sys_info_pos_end - $sys_info_pos - 1
				);

				// Handling system info (get os)
				$exploded_sysinfo = explode(';', $this->m_system_information);
				foreach($exploded_sysinfo as $comparable) {
					$break_loop = false;

					// If contains a space, remove excess after space (windows has
					// weird shit after space.. fucking windows.)
					if(($pos = strpos($comparable, ' ')) > 0) {
						$comparable = substr($comparable, 0, $pos);
					}

					switch($comparable) {
						case "Windows": {
							$this->m_operating_system = UAOperatingSystem_Windows;
							$break_loop = true;
							break;
						}

						case "Macintosh": {
							$this->m_operating_system = UAOperatingSystem_OSX;
							$break_loop = true;
							break;
						}

						case "Andoird": {
							$this->m_operating_system = UAOperatingSystem_Andoird;
							$break_loop = true;
							break;
						}

						case "iPad":
						case "iPhone":
						case "iPod": {
							$this->m_operating_system = UAOperatingSystem_IOS;
							$break_loop = true;
							break;
						}

						case "Linux": {
							$this->m_operating_system = UAOperatingSystem_xNix;
							$break_loop = true;
							break;
						}

						default: {
							if($this->m_operating_system === null) {
								$this->m_operating_system = UAOperatingSystem_Unknown;
							}
							break;
						}
					}

					if($break_loop) {
						break;
					}
				}

				// Handle rendering type
				switch($this->m_operating_system) {
					case UAOperatingSystem_IOS:
					case UAOperatingSystem_Andoird: {
						$this->m_render_type = UARenderType_Handhold;
						break;
					}

					case UAOperatingSystem_Windows:
					case UAOperatingSystem_OSX:
					case UAOperatingSystem_xNix: {
						$this->m_render_type = UARenderType_Desktop;
						break;
					}

					default: {
						$this->m_render_type = UARenderType_Unknown;
					}
				}
			}
			else {
				// All other user agents

				$this->m_is_mozilla = false;
			}
		}
	}

	public function getOperatingSystem()
	{
		return $this->m_operating_system;
	}

	public function getRenderType()
	{
		return $this->m_render_type;
	}
}
