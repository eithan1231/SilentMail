<?php


class autoload
{
	private static $paths;

	public static function registerPath(string $path)
	{
		autoload::$paths[] = $path;
	}

	public static function initialize(string $path)
	{
		autoload::RegisterPath($path);

		spl_autoload_register(function($class_name) {

			$class_name = strtolower($class_name);

			$potential_class_names = [];
			$potential_class_names[] = $class_name;
			$potential_class_names[] = str_replace('_', '/', $class_name);

			foreach (autoload::$paths as $path) {
				foreach($potential_class_names as $potential_class_name) {
					// Generating path location
					$path .= '/' . $potential_class_name . '.php';

					if(class_exists($class_name)) {
						// Class has already been loaded
						return true;
					}

					// Making sure the class file exists
					if(file_exists($path)) {

						// Loading class
						require $path;

						// Returning state of success
						return class_exists($class_name);
					}
				}
			}

			return false;
		});
	}

	public static function include(string $file)
	{
		$file = strtolower($file);

		foreach (autoload::$paths as $path) {
			// Generating path location
			$path .= '/' . $file . '.php';

			// Making sure the class file exists
			if(file_exists($path)) {

				// Loading class
				require $path;
			}
		}

		return false;
	}
}

?>
