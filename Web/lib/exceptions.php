<?php

class exceptions
{
	public static function log($exception)
	{
		$ex_as_string = $exception->getMessage();//var_export($exception, true);
		$ex_as_string .= "\n";
		$ex_as_string .= $exception->getFile() ."(". $exception->getLine() .")\n\n";
		$ex_as_string .= $exception->getTraceAsString();

		$ex_as_string .= "\n\n\n\n";
		$queries = sql::get_queries();
		foreach($queries as $value) {
			$ex_as_string .= "\nQuery:\n{$value['query']}\n\n\n";
		}

		if($f = fopen(
			WORK_DIR .'/exceptions/'. time .' - exception - '. uniqueToken,
			'w'
		)) {
			fwrite($f, $ex_as_string);
			fclose($f);
		}
	}

	public static function errorLog($errno, $errstr, $errfile, $errline)
	{
		$ex_as_string = "error number: {$errno}\nfile: {$errfile}({$errline})\n\n{$errstr}";

		if($f = fopen(
			WORK_DIR .'/exceptions/'. time .' - error - '. uniqueToken,
			'w'
		)) {
			fwrite($f, $ex_as_string);
			fclose($f);
		}
	}

	public static function initializeHandler()
	{
		set_exception_handler('exceptions::log');
		set_error_handler('exceptions::errorLog');
	}
}
