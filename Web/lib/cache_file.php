<?php

class cache_file extends cache
{
  private $cache_dir = null;
  private $valid_duration = null;

  public function __construct(string $cache_dir, int $valid_duration)
  {
    $this->cache_dir = $cache_dir;
    if($this->cache_dir[strlen($this->cache_dir) - 1] != '/') {
      $this->cache_dir .= '/';
    }

    if(!file_exists($this->cache_dir)) {
      mkdir($this->cache_dir, 0777, true);
    }

    $this->valid_duration = $valid_duration;
  }

  /**
  * Stores a key/value pair
  *
  * @param string $key
  *   They key we're saving.
  * @param mixed $value
  *   The value we're saving
  */
  public function store(string $key, $value)
  {
    if(!$this->validKey($key)) {
      throw new Exception("Invalid key");
    }

    $key_path = $this->getKeyPath($key);
    $serialized_value = serialize($value);
    $free_space = intval(disk_free_space($this->cache_dir));

    if($free_space < strlen($serialized_value) + 1024) {
      // insufficient disk space.
      // NOTE: the added 1024, is for the filesystem and stuff.
      return false;
    }

    if($f = fopen($key_path, 'w')) {
      fwrite($f, $serialized_value);
      fclose($f);
    }
    else {
      throw new Exception("Unable to open file");
    }

    return true;
  }

  /**
  * Gets the object (or any type) linked with the key
  *
  * @param string $key
  *   They key we're getting.
  */
  public function get(string $key)
  {
    if(!$this->exists($key)) {
      return false;
    }

    $key_path = $this->getKeyPath($key);
    $value = '';

    $size = filesize($key_path);
    if($f = fopen($key_path, 'r')) {
      $value = fread($f, $size);
      fclose($f);
    }
    else {
      throw new Exception("Unable to open file");
    }

    return unserialize($value);
  }

  /**
  * Checks if a key exists
  *
  * @param string $key
  *   The key we want to check exists
  */
  public function exists(string $key)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_path = $this->getKeyPath($key);

    if(file_exists($key_path)) {
      if($this->hasExpired($key)) {
        unlink($key_path);// delete old file, its expired.
        return false;
      }
      return true;
    }
    else {
      return false;
    }
  }

  /**
  * Purges a cache key
  *
  * @param string $key
  *   The key we want to purge
  */
  public function purge(string $key)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_path = $this->getKeyPath($key);

    if(file_exists($key_path)) {
      unlink($key_path);
      return true;
    }
    else {
      return false;
    }
  }

  /**
  * Each key is linked with a file, this gets that file, and the path to it.
  *
  * @param string $key
  *   The key we need the filename of
  */
  private function getKeyPath(string $key)
  {
    return $this->cache_dir . versionHash . '_' . hash('sha256', $key);
  }

  /**
  * Checks if a key has expired
  *
  * @param string $key
  *   The key we want to check has expired.
  */
  private function hasExpired(string $key)
  {
    $key_path = $this->getKeyPath($key);

    return time - filemtime($key_path) >= $this->valid_duration;
  }
}
