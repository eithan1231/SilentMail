<?php

class cache_none extends cache
{
  public function store(string $key, $value)
  {
    return false;
  }

  public function get(string $key)
  {
    return false;
  }

  public function exists(string $key)
  {
    return false;
  }

  public function purge(string $key)
  {
    return false;
  }
}
