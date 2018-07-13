<?php

class assetloader
{
	function __construct()
	{

	}

	/**
	* Gets a path to an asset.
	*/
	public static function getAssetPath($router, string $asset_name, string $extension)
	{
		if(!assetloader::validAssetExtension($extension)) {
			return false;
		}

		if($router === false || is_null($router)) {
			$router = router::instance();
		}

		return $router->getRoutePath('Asset', [
			'version' => (config['developmentMode']
				? 'devmode----'. cryptography::randomString(10)
				: versionHash
			),
			'type' => $extension,
			'asset_name' => $asset_name
		], false, false);
	}

	/**
	* Loads an asset and outputs it
	*/
	public function loadAsset(string $asset_name, string $extension)
	{
		$extension = strtolower($extension);
		$asset_name = strtolower($asset_name);

		// making sure it's a verified/trusted extension
		if(!assetloader::validAssetExtension($extension)) {
			return false;
		}

		// Getting the direct uncached version of the asset
		$asset_path = WORK_DIR . config['assetDir'] ."/$extension/$asset_name.$extension";

		// making sure the actual asset path exists
		if(!file_exists($asset_path)) {
			return false;
		}

		// Headers
		output_page::SetHttpStatus(200, "OK");
		header("Content-type: ". assetloader::getExtensionMimeType($extension));
		header("Content-length: ". filesize($asset_path));
		header("Cache-Control: max-age=". (config['developmentMode'] ? '0' : '3600'));
		header("X-Robots-Tag: noindex,nofollow");// Prevent crawlers storing assets.

		if(!config['allowCache']) {
			//------------------------------------------------------------------------
			// Cache is disabled
			//------------------------------------------------------------------------

			readfile($asset_path);
		}
		else {
			//------------------------------------------------------------------------
			// Cache is enabled
			//------------------------------------------------------------------------

			$cache_directory = WORK_DIR . config['assetCacheDir'] .'/'. versionHash ."/$extension/";
			$asset_cache_path = ($cache_directory . misc::cleanFileName($asset_name) .".$extension");

			// Making sure cache directory exists, if not, create it.
			if(!file_exists($cache_directory)) {
				mkdir($cache_directory, 0777, true);
			}

			if(!file_exists($asset_cache_path)) {
				switch($extension) {
					case "css": {

						// Getting asset content
						$raw = file_get_contents($asset_path);

						// Minifying it
						$raw = minify::minifyCss($raw);

						// Saving the cache
						if($f = fopen($asset_cache_path, 'w')) {
							fwrite($f, $raw);
							fclose($f);
						}

						// And outputting..
						echo $raw;

						break;
					}

					case "js": {
						// Getting asset content
						$raw = file_get_contents($asset_path);

						// Minifying it
						$raw = minify::minifyJs($raw);

						// Saving the cache
						if($f = fopen($asset_cache_path, 'w')) {
							fwrite($f, $raw);
							fclose($f);
						}

						// And outputting..
						echo $raw;

						break;
					}

					default: {
						// non-cacheable file
						readfile($asset_path);
					}
				}
			}
			else {
				// Cached file exists
				readfile($asset_cache_path);
			}

			//------------------------------------------------------------------------
			// end cache is enabled
			//------------------------------------------------------------------------
		}

		return true;
	}

	/**
	* Gets an inline image
	*/
	public static function getInlineImage(string $image_name, string $extension)
	{
		$extension = strtolower($extension);
		$image_name = strtolower($image_name);

		// making sure it's a verified/trusted extension
		if(!assetloader::validAssetExtension($extension)) {
			return false;
		}

		// Getting the direct uncached version of the asset
		$asset_path = WORK_DIR . config['assetDir'] ."/$extension/$image_name.$extension";

		// making sure the actual asset path exists
		if(!file_exists($asset_path)) {
			return false;
		}

		if($f = fopen($asset_path, 'r')) {
			$content = fread($f, filesize($asset_path));
			fclose($f);

			return 'data:'. assetloader::getExtensionMimeType($extension) .';base64,'. base64_encode($content);
		}
		else {
			trigger_error("Failed to read asset from, {$asset_path}");
			return false;
		}
	}

	/**
	* Deletes all the cached files
	*/
	public static function purgeCache(string $cache_directory)
	{
		$files = scandir($cache_directory, SCANDIR_SORT_NONE);
		foreach($files as $file) {
			if($file[0] == '.') {
				continue;
			}

			$path = "$cache_directory/$file";

			if(is_dir($path)) {
				$path .= '/';
				assetloader::purgeCache($path);
			}
			else {
				echo "deleting {$cache_directory}{$file}<br>\n";
				unlink($cache_directory . $file);
			}
		}
	}

	/**
	* Checks if an extension is a valid asset extension
	*/
	public static function validAssetExtension(string $extension)
	{
		return in_array($extension, config['extensionAssetsTrusted']);
	}

	/**
	* Gets a mime type from extension
	*/
	public static function getExtensionMimeType(string $extension)
	{
		if(isset(config['extensionToMime']["$extension"])) {
			return config['extensionToMime']["$extension"];
		}

		return "application/octet-stream";
	}
}
