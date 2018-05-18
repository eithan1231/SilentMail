<?php

abstract class cache
{
  public abstract function store(string $key, $value);
  public abstract function get(string $key);
  public abstract function exists(string $key);

  public function validKey(string $key)
  {
    return strlen($key) > 5;
  }

  public function buildKey($key_name, $parameters, $user_linked = true)
  {
    $ret = '';
    if($user_linked) {
      $ret = strval(ses_user_id);
    }
    $ret .= ";{$key_name};";
    foreach ($parameters as $value) {
      $ret .= "{$value}+";
    }
    return $ret;
  }
}
