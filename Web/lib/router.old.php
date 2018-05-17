<?php

class router
{
	/* Referenced instance to most recent router. */
	private static $m_instance = false;

	private $m_path;
	private $m_path_exploded;
	private $m_path_exploded_count;

	private $m_routes = [];

	private $m_specialRoutes = [];

	private $m_current_route_name;

	function __construct($path = false)
	{
		if($path === false) {
			$path = $_SERVER['REQUEST_URI'];

			$dir_from_root_length = strlen(config['dirFromRoot']);

			if(substr($path, 0, $dir_from_root_length) == config['dirFromRoot']) {
				$path = substr($path, $dir_from_root_length - 1);
			}

			if(($pos = strpos($path, "?")) !== false) {
				$path = substr($path, 0, $pos);
			}

			$path_length = strlen($path);
			if($path_length > 1 && $path[$path_length - 1] == '/') {
				$path = substr($path, 0, $path_length - 1);
			}
		}

		$this->m_path = str_replace('//', '', $path);
		$this->m_path_exploded = explode('/', $this->m_path);
		$this->m_path_exploded_count = count($this->m_path_exploded);

		router::$m_instance = &$this;
	}

	public static function Instance()
	{
		return router::$m_instance;
	}

	/**
	* Registers a GET method route.
	*/
	public function registerGetRoute($name, $route, $callback, $crawlers_allowed = false)
	{
		return $this->registerRoute("GET", $name, $route, $callback, $crawlers_allowed);
	}

	/**
	* Registers a POST method route.
	*/
	public function registerPostRoute($name, $route, $callback)
	{
		return $this->registerRoute("POST", $name, $route, $callback);
	}

	/**
	* Registers a HEAD method route.
	*/
	public function registerHeadRoute($name, $route, $callback)
	{
		return $this->registerRoute("HEAD", $name, $route, $callback);
	}

	/**
	* Registers a PUT method route.
	*/
	public function registerPutRoute($name, $route, $callback)
	{
		return $this->registerRoute("PUT", $name, $route, $callback);
	}

	/**
	* Registers a DELETE method route.
	*/
	public function registerDeleteRoute($name, $route, $callback)
	{
		return $this->registerRoute("DELETE", $name, $route, $callback);
	}

	/**
	* Registers a CONNECT method route.
	*/
	public function registerConnectRoute($name, $route, $callback)
	{
		return $this->registerRoute("CONNECT", $name, $route, $callback);
	}

	/**
	* Registers a route.
	*/
	public function registerRoute($method, $name, $route, $callback, $crawlers_allowed = false)
	{
		// Subtracting the last slash...
		$route_length = strlen($route);
		if($route_length > 3 && $route[$route_length - 1] == '/') {
			$route = substr($route, 0, $route_length - 1);
		}

		$this->m_routes[] = [
			'name' => strtolower($name),
			'method' => $method,
			'route' => $route,
			'exploded_route' => explode('/', $route),
			'callback' => $callback,
			'crawlers_allowed' => $crawlers_allowed
		];
	}

	/**
	* Registers a special route.
	*/
	public function registerSpecial($name, $callback)
	{
		$this->m_specialRoutes["$name"] = $callback;
	}

	/**
	* Creates a path to a route with custom parameters
	*
	* Example:
	* getRoutePath('routename', ['paramname' => 'value'], 'key=x');
	* // 'paramname' is /path/to/{paramname}/ in a path...
	*
	*/
	public function getRoutePath($route_name, $parameters = false, $query = false, $allow_ref = false)
	{
		$route_name = strtolower($route_name);

		foreach($this->m_routes as $route) {
			if($route['name'] === $route_name) {
				$out_url = config['dirFromRoot'];

				foreach($route['exploded_route'] as $route_path) {
					$route_path_length = strlen($route_path);
					if($route_path_length == 0) {
						continue;
					}

					if($route_path_length > 3 && $route_path[0] == '{' &&
						$route_path[$route_path_length - 1] == '}'
					) {
						$route_path_name = substr($route_path, 1, $route_path_length - 2);

						$out_url .= urlencode($parameters["$route_path_name"]) .'/';
					}
					else {
						$out_url .= urlencode($route_path) ."/";
					}
				}

				// Removing the last slash, it's not needed
				$out_url_length = strlen($out_url);
				if($out_url_length > 1) {
					$out_url = substr($out_url, 0, $out_url_length - 1);
				}

				if($query !== false && strlen($query) > 0) {
					if($query[0] == '#') {
						// Not a query, a hash.
						$out_url .= $query;
					}
					else {
						$out_url .= "?$query";

						if(strpos($query, '=') !== false) {
							if($allow_ref) {
								$out_url .= "&ref=". urlencode($this->m_current_route_name);
							}
						}
					}
				}
				else if($this->m_current_route_name !== null) {
					if($allow_ref) {
						$out_url .= "?ref=". urlencode($this->m_current_route_name);
					}
				}

				return $out_url;
			}
		}

		return false;
	}

	/**
	* Redirects to a new route
	*/
	public function redirectRoute($route_name, $parameters = false, $query = false, $allow_ref = false)
	{
		$route_path = $this->getRoutePath($route_name, $parameters, $query, $allow_ref);

		if($route_path !== false) {
			header("Location: $route_path");
			die();
		}
		else {
			throw new Exception("Invalid Route");
		}
	}

	/**
	* Runs all necessary routes
	*
	* @param $completeCallback Callable
	*		Called when all routes have been run
	*/
	public function runRoutes()
	{
		//
		// EJNOY THE MESS!.... sorry...
		//

		$routeFound = false;

		try {
			// Running prepend
			$this->runSpecialPage('prepend', [&$this]);
		}
		catch(exception $ex) {
			exceptions::log($ex);
			$this->runSpecialPage('500', $ex);
		}

		foreach($this->m_routes as $route) {
			$callable_path_parameters = [];
			$mathed_routes = false;

			// Making sure this method is mathcing
			if($route['method'] !== $_SERVER['REQUEST_METHOD']) {
				continue;
			}

			// Making sure it has the same amount of 'directories'
			if(count($route['exploded_route']) != $this->m_path_exploded_count) {
				continue;
			}

			for($i = 0; $i < $this->m_path_exploded_count; $i++ ) {

				// current path length, aka the path in this index.
				$cur_path_len = strlen($route['exploded_route'][$i]);

				if(
					$cur_path_len > 3 && $route['exploded_route'][$i][0] == '{' &&
					$route['exploded_route'][$i][$cur_path_len - 1] == '}'
				) {
					// Variable in route

					$parameter_name = substr(
						$route['exploded_route'][$i],
						1,
						$cur_path_len - 2
					);

					$callable_path_parameters[$parameter_name] = $this->m_path_exploded[$i];
				}
				else if($this->m_path_exploded[$i] != $route['exploded_route'][$i]) {
					// Unmatched routes
					break;
				}

				if($this->m_path_exploded_count - 1 == $i) {
					$mathed_routes = true;
				}
			}

			if($mathed_routes) {
				$this->m_current_route_name = $route['name'];

				$callable_path_parameters['__name'] = $route['name'];
				$callable_path_parameters['__pattern'] = $route['route'];

				try {
					if($route['method'] === 'POST') {
						// Storing post data in a variable
						$post_data = null;

						// we dont wanna kill server by loading multiple gb files.
						$post_sie = (int)$_SERVER['CONTENT_LENGTH'];
						if($post_sie < 1024 * 1024) {
							// if the post size is under 1 mb, set the post data, else dont.
							$post_data = file_get_contents("php://input");
						}

						call_user_func_array(
							$route['callback'],
							[&$this, $callable_path_parameters, $post_data]
						);
					}
					else {
						// other request method, more then likely get.
						call_user_func_array(
							$route['callback'],
							[&$this, $callable_path_parameters]
						);
					}
				}
				catch(Exception $ex) {
					exceptions::log($ex);
					$this->runSpecialPage('500', $ex);
				}

				$routeFound = true;

				break;
			}
		}

		if(!$routeFound) {
			$this->runSpecialPage('404', $this->m_path);
		}

		try {
			// Running append
			$this->runSpecialPage('append', [&$this]);
		}
		catch(exception $ex) {
			exceptions::log($ex);
			$this->runSpecialPage('500', $ex);
		}
	}

	/**
	* Runs a special page
	*/
	private function runSpecialPage($page, $parameters = false)
	{
		if(isset($this->m_specialRoutes["$page"])) {
			if($parameters !== false) {
				if(!is_array($parameters)) {
					$parameters = [$parameters];
				}
				call_user_func_array($this->m_specialRoutes["$page"], $parameters);
			}
			else {
				call_user_func($this->m_specialRoutes["$page"]);
			}
		}
	}

	/**
	* Returns a list of get routes.
	*/
	public function getRoutes()
	{
		$ret = [];

		foreach($this->m_routes as &$route) {
			if($route['method'] !== 'GET') {
				continue;
			}

			$ret[] = [
				'name' => $route['name'],
				'route' => $route['route'],
				'crawlers_allowed' => $route['crawlers_allowed'],
			];
		}

		return $ret;
	}
}
