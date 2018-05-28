<?php

class cache_redis extends cache
{
  /**
  * Redis object.
  */
  $redis = null;

  /**
  * The duration cache stays alive.
  */
  $default_duration = time_day;

  public function __construct($nodes, $cache_duration_default_default)
  {
    if(!class_exists('Redis')) {
      throw new Exception("Redis not installed");
    }

    if(!is_array($nodes)) {
      $nodes = [$nodes];
    }

    $this->default_duration = $cache_duration_default;
    $this->redis = new Redis();

    foreach($nodes as $node) {
      if(!$this->redis->connect(
        $node['host'],
        $node['port']
      )) {
        throw new Exception("Redis: Failed to connect to: {$node['host']}:{$node['port']}");
      }

      if(isset($node['auth'])) {
        if(!$this->redis->auth($nnode['auth'])) {
          throw new Exception("Redis: Failed to authenticate: {$node['host']}:{$node['port']}");
        }
      }
    }
  }

  public function store(string $key, $value)
  {
    $hashed_key = $this->hashKey($key);
    return $this->redis->setEx($hashed_key, $this->default_duration, serialize($value));
  }

  public function get(string $key)
  {
    if(!$this->exists($key)) {
      return false;
    }

    $hashed_key = $this->hashKey($key);
    $value = unserialize($this->redis->get($hashed_key));

    return $value;
  }

  public function exists(string $key)
  {
    if(!$this->validKey($key)) {
      throw new Exception('Invalid key');
    }

    $hashed_key = $this->hashKey($key);

    return $this->redis->exists($hashed_key);
  }

  public function purge(string $key)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $hashed_key = $this->hashKey($key);

    return $this->redis->delete($hashed_key);
  }

  private function hashKey(string $key)
  {
    return hash('sha512', $key);
  }
}
