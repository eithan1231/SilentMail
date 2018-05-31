<?php

class sql
{
	public static $instance = false;

	/** History of executed queries */
	private static $query_history = [];

	/**
	* Opens and connects to mysql
	*/
	public static function connect()
	{
		try {
			if(sql::ping()) {
				return true;
			}

			mysqli_report(MYSQLI_REPORT_STRICT);

			self::$instance = mysqli_connect(
				config['sql']['hostname'],
				config['sql']['username'],
				config['sql']['password'],
				config['sql']['database']
			);

			if(!self::$instance) {
				return false;
			}

			return true;
		}
		catch(exception $ex) {
			exceptions::log($ex);
			return false;
		}
	}

	/**
	* Prepares a SQL query
	*
	* @param string $query
	*		Query to be prepared
	*/
	public static function prepare($query)
	{
		return self::$instance->prepare($query);
	}

	/**
	* Executes a query, and returns result of mysqli_query.
	*
	* @param string $query
	*		Query to be executed
	*/
	public static function query($query)
	{
		// Getting start time (in second, and micro second.)
		$ms_start_time = microtime(true);
		$start_time = time();

		// Executing the query
		$result = mysqli_query(self::$instance, $query);

		// Calculating the time it too to query the data.
		$ms_complete_time = microtime(true) - $ms_start_time;
		$complete_time = time() - $start_time;

		if(!$result) {
			throw new Exception(self::$instance->error);
		}

		if($start_time > 2 && config['reportSlowQueries']) {
			shutdown_events::register(function() {
				// TODO: Write slow query reporter.
			});
		}

		sql::$query_history[] = [
			'microtime' => $ms_start_time,
			'time' => $complete_time,
			'query' => $query
		];

		return $result;
	}

	/**
	* Executes a query, and fetches result.
	*
	* @param string $query
	*		Query to be executed
	*/
	public static function query_fetch($query)
	{
		$res = sql::query($query);

		if($res === false) {
			return false;
		}

		if($res->num_rows == 0) {
			return false;
		}

		return mysqli_fetch_assoc($res);
	}

	/**
	* Executes a query, and fetches all results.
	*
	* @param string $query
	*		Query to be executed
	*/
	public static function query_fetch_all($query)
	{
		$res = sql::query($query);

		if($res === false) {
			return false;
		}

		if($res->num_rows == 0) {
			return false;
		}

		$dat = [];
		$index = 0;
		while($row = mysqli_fetch_assoc($res)) {
			$dat[$index] = $row;
			$dat[$index]['__index__'] = $index;
			$index++;
		}

		return $dat;
	}

	/**
	* Returns the value of the mysql function, LAST_INSERT_ID.
	*/
	public static function getLastInsertId()
	{
		$res = self::query_fetch("SELECT LAST_INSERT_ID() as id");

		if($res === false) {
			trigger_error("No last insert id.");
		}

		return $res['id'];
	}

	/**
	* Escapes a parameter
	*
	* @param numeric|boolean|string $var
	*		Variable to be escaped
	*/
	public static function quote($var)
	{
		if(
			is_null($var) ||
			is_resource($var) ||
			is_object($var) ||
			is_array($var)
		) {
			throw new Exception(gettype($vat) ." is a disallowed type for \$var");
		}

		if(is_float($var) || is_double($var)) {
			return mysqli_real_escape_string(self::$instance, (string)$var);
		}
		else if(is_numeric($var)) {
			return intval($var);
		}
		else if(is_bool($var)) {
			return ($var === true ? '1' : '0');
		}
		else if(is_string($var)) {
			$escaped = mysqli_real_escape_string(self::$instance, $var);

			if($escaped === false) {
				throw new Exception("Failed to escape string");
			}

			return "\"$escaped\"";
		}
		else {
			throw new Exception("Unknown variable type");
		}
	}

	/**
	* sends a ping to verify connection is alive
	*/
	public static function ping()
	{
		if(self::$instance === false) {
			return false;
		}

		return mysqli_ping(self::$instance);
	}

	/**
	* Gets a list of executed queries
	*/
	public static function get_queries()
	{
		return sql::$query_history;
	}

	/**
	* Closes mysqli instance
	*/
	public static function close()
	{
		return mysqli_close(self::$instance);
	}
}
