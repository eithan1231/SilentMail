<?php

class cache_none extends cache
{
  const KEY_MAX_LENGTH = 150;

  public function store(string $key, $value)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_hashed = $this->hashKey($key);
    $serialized_value = serialize($value);

    // Checking if the serialized object is above 1mb, if it is, return false.
    // memcached is limited to 1mb values, and 150 byte keys.
    if(strlen($serialized_value) > 1024 * 1024) {
      return false;
    }


    return false;
  }

  public function get(string $key)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_hashed = $this->hashKey($key);
    return fase;
  }

  public function exists(string $key)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_hashed = $this->hashKey($key);
    return fase;
  }

  public function purge(string $key)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_hashed = $this->hashKey($key);
    return false;
  }

  private function hashKey(string $key)
  {
    return hash('sha512', $key);
  }
}
