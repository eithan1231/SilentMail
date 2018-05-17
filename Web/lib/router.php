<?php

class router
{
	/** The recent-most instance of router created. */
	private static $m_instance = null;

	private $url_working_path = null;
	private $url_working_path_length = null;

	private $m_path = null;
	private $m_path_length = null;
	private $m_path_exploded = null;
	private $m_path_exploded_count = null;

	private $routes = [];
	private $routes_count = null;
	private $regexed_routes = [];// an array of indexes for all the routes with regex.
	private $special_routes = [];

	private $forced_query_string = null;

	private $active_route_name = null;

	function __construct($url_working_path, $path = null)
	{
		$this->url_working_path = $url_working_path;

		if($path === null) {
			$this->m_path = $_SERVER['REQUEST_URI'];
		}
		else {
			$this->m_path = $path;
		}

		// Removes the prexied directories. For example, our project is in the /xx/
		// directory, and the request path is /xx/your/mumma, we want to remvoe the
		// /xx, and just work with the rest. This is what that does.
		$this->url_working_path_length = strlen($this->url_working_path);
		if(
			$this->url_working_path_length >= 1 &&
			$this->url_working_path[$this->url_working_path_length - 1] == '/'
		) {
			$this->url_working_path = substr(
				$this->url_working_path,
				0,
				--$this->url_working_path_length
			);
		}
		if(
			$this->url_working_path_length > 0 &&
			substr($this->m_path, 0, $this->url_working_path_length) == $this->url_working_path
		) {
			$this->m_path = substr($this->m_path, 0, $this->url_working_path_length - 2);
		}

		// Removing hash. clients shouldnt send the # part of the url, but just incase
		// they do, we'll remove it.
		if(($pos = strpos($this->m_path, '#')) !== false) {
			$this->m_path = substr($this->m_path, 0, $pos);
		}

		// Removing query
		if(($pos = strpos($this->m_path, '?')) !== false) {
			$this->m_path = substr($this->m_path, 0, $pos);
		}

		// Setting internal variables
		$this->m_path_length = strlen($this->m_path);
		$this->m_path_exploded = explode('/', $this->m_path);
		$this->m_path_exploded_count = count($this->m_path_exploded);

		router::$m_instance = &$this;
	}

	public static function Instance()
	{
		return router::$m_instance;
	}

	public function setGlobalQueryString($query)
	{
		if(is_string($query) && strlen($query) <= 0) {
			$this->forced_query_string = null;
		}
		else {
			$this->forced_query_string = $query;
		}
	}

	public function registerGetRoute($name, $route, $callback, $allow_crawlers = false, $options = [])
	{
		$options['allow_crawlers'] = $allow_crawlers;

		$this->registerRoute(
			"GET",
			$name,
			$route,
			$callback,
			$options
		);
	}

	public function registerPostRoute($name, $route, $callback, $options = [])
	{
		$this->registerRoute(
			"POST",
			$name,
			$route,
			$callback,
			$options
		);
	}

	public function registerRoute($method, $name, $route, callable $callback, $options = null)
	{
		// default options
		if(!isset($options['append_global_query'])) {
			$options['append_global_query'] = true;
		}

		$route_exploded = explode('/', $route);
		$route_exploded_count = count($route_exploded);

		$route_object = [
			'method' => strtoupper($method),
			'name' => strtolower($name),
			'route' => $route,
			'route_exploded' => $route_exploded,
			'route_exploded_count' => $route_exploded_count,
			'callback' => $callback,
			'options' => $options,
		];

		if($method === '__SPECIAL') {
			$this->special_routes[] = $route_object;
		}
		else {
			if(
				isset($options['regex']) &&
				$options['regex']
			) {
				$this->regexed_routes[] = $this->routes_count;
			}

			$this->routes[] = $route_object;
			$this->routes_count++;
		}
	}

	public function registerSpecial($name, $callback)
	{
		$this->registerRoute('__SPECIAL', $name, null, $callback);
	}

	public function getRoutePath($route_name, $parameters = null, $query = null, $allow_ref = false)
	{
		$route_name_lower = strtolower($route_name);
		foreach ($this->routes as $route) {
			if($route['name'] === $route_name_lower) {
				$url = $this->url_working_path;

				foreach ($route['route_exploded'] as $route_directory_path_index => $route_directory_path) {
					$route_directory_path_len = strlen($route_directory_path);

					if(
						$route_directory_path_len > 3 &&
						$route_directory_path[0] == '{' &&
						$route_directory_path[$route_directory_path_len - 1] == '}'
					) {
						$parameter_key = substr(
							$route_directory_path,
							1,
							$route_directory_path_len - 2
						);

						$url .= urlencode($parameters[$parameter_key]);
					}
					else {
						$url .= urlencode($route_directory_path);
					}

					if($route_directory_path_index < $route['route_exploded_count'] - 1) {
						$url .= '/';
					}
				}

				if(
					isset($route['options']['append_global_query']) &&
					$route['options']['append_global_query']
				) {
					if(is_null($query) && !is_null($this->forced_query_string)) {
						$query = $this->forced_query_string;
					}
					else if(!is_null($query) && !is_null($this->forced_query_string)) {
						if($this->forced_query_string[strlen($this->forced_query_string) - 1] !== '&') {
							$this->forced_query_string .= '&';
						}
						$query = "{$this->forced_query_string}{$query}";
					}
				}

				// Query, ref, and hash.
				if(is_null($query) && !is_bool($query)) {
					// null query string

					if(
						$allow_ref &&
						!is_null($this->active_route_name)
					) {
						$url .= "?__ref=". urlencode($this->active_route_name);
					}
				}
				else {
					// non-null query string

					if(
						$allow_ref &&
						!is_null($this->active_route_name)
					) {
						$url .= "?__ref=". urlencode($this->active_route_name) ."&";
					}

					if($query[0] == '#') {
						$url .= "{$query}";
					}
					else if(strlen($query) > 1) {
						$url .= "?{$query}";
					}
				}


				return $url;
			}
		}
		return false;
	}

	public function redirectRoute($route_name, $parameters = null, $query = null, $allow_ref = false)
	{
		$route = $this->getRoutePath(
			$route_name,
			$parameters,
			$query,
			$allow_ref
		);

		if(!$route) {
			throw new Exception("Failed to get route");
		}

		header("Location: {$route}");
		die();
	}

	private function runSpecialRoute($route, $params = null)
	{
		if(!is_null($params)) {
			if(!is_array($params)) {
				$params = [$params];
			}
		}

		foreach ($this->special_routes as $special_route) {
			if($special_route['name'] === $route) {
				if(is_array($params)) {
					call_user_func_array($special_route['callback'], $params);
				}
				else {
					call_user_func($special_route['callback']);
				}
			}
		}
	}

	public function runRoutes()
	{
		try {
			// Running prepend script
			$this->runSpecialRoute('prepend', [&$this]);

			// Running through al the regular expression routes.
			foreach($this->regexed_routes as $regex_index) {
				$route = $this->routes[$regex_index];

				$preg_match_res = preg_match_all(
					$route['route'],
					$this->m_path,
					$matches
				);

				if($preg_match_res > 0) {
					$callback_dat = call_user_func_array(
						$route['callback'],
						[&$this, $matches]
					);

					if($callback_dat !== true) {
						break;
					}
				}
			}

			// Running through all the normal regisrered routes
			foreach($this->routes as $route_index => $route) {

				if(
					isset($route['options']['regex']) &&
					$route['options']['regex']
				) {
					// Regex is handled in another loop, skip
					continue;
				}

				// Making sure the current registered route, and the requesting path,
				// have the same ammount of 'directory paths'.
				if($route['route_exploded_count'] == $this->m_path_exploded_count) {
					$callable_parameters = [];
					$path_expld_key = 0;
					$match = true;

					// going through al of the exploded directory path's
					foreach($route['route_exploded'] as $path_expld_key => $route_directory_path) {
						$path_directory_path = urldecode($this->m_path_exploded[$path_expld_key]);
						$path_directory_path_length = strlen($path_directory_path);
						$route_directory_path_length = strlen($route_directory_path);

						// Both empty route paths, therefore a match
						if(
							$route_directory_path_length == 0 &&
							$path_directory_path_length == 0
						) {
							continue;
						}

						if(
							$route_directory_path_length <= 0 ||
							$path_directory_path_length <= 0
						) {
							$match = false;
							break;
						}

						// Paremater with no explicit word, and isnt a parameter. So, a
						// dynamic directory name.
						if($route_directory_path[0] == '?') {
							continue;
						}

						// Checking if its a parameter path
						if(
							$route_directory_path[0] == '{' &&
							$route_directory_path[$route_directory_path_length - 1] == '}'
						) {
							$parameter_key = substr(
								$route_directory_path,
								1,
								$route_directory_path_length - 2
							);

							$callable_parameters["$parameter_key"] = $path_directory_path;

							continue;
						}

						if($route_directory_path == $path_directory_path) {
							continue;
						}

						$match = false;
						break;
					}

					// Match
					if($match) {
						if($path_expld_key == ($route['route_exploded_count'] - 1)) {
							$callable_parameters['__name'] = $route['name'];
							$callable_parameters['__pattern'] = $route['route'];
							$callable_parameters['__method'] = $route['method'];
							$callable_parameters['__options'] = $route['options'];

							$callback_dat = null;

							switch($route['method']) {
								case "GET": {
									$callback_dat = call_user_func_array(
										$route['callback'],
										[&$this, $callable_parameters]
									);
									break;
								}

								case "POST": {
									$post_data = null;
									$post_size = intval($_SERVER['CONTENT_LENGTH']);
									if($post_size < 1024 * 1024) {
										$post_data = file_get_contents("php://input");
									}

									$callback_dat = call_user_func_array(
										$route['callback'],
										[&$this, $callable_parameters, $post_data]
									);
									break;
								}

								default: {
									throw new Exception("Unsupported HTTP method");
									break;
								}
							}

							// Checking callback results. Anything other true will continue route
							// search. Useful of theres multiple routes with similar parameters.
							if($callback_dat !== true) {
								break;
							}
						}
					}
				}

				if($route_index == ($this->routes_count - 1)) {
					$this->runSpecialRoute('404', $this->m_path);
				}
			}

			// Running append
			$this->runSpecialRoute('append', [&$this]);
		}
		catch (Exception $ex) {
			exceptions::log($ex);
			$this->runSpecialRoute('500', $ex);
		}
	}

	public function getRoutes()
	{
		$ret = [];

		foreach($this->routes as $route) {
			$ret[] = array_merge([
				'route' => $route['route'],
				'crawlers_allowed' => (isset($route['options']['allow_crawlers'])
					? $route['options']['allow_crawlers']
					: false
				)
			], $route);
		}

		return $ret;
	}
}
