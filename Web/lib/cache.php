<?php

abstract class cache
{
  public abstract function store(string $key, $value);
  public abstract function get(string $key);
  public abstract function exists(string $key);
  public abstract function purge(string $key);

  public function validKey(string $key)
  {
    return strlen($key) > 5;
  }

  public function buildKey($key_name, $parameters = [], $user_linked = true)
  {
    // returns ses_user_id;key_name;<param1>^<param2>^<param3>
    $ret = '';
    if($user_linked) {
      $ret = strval(ses_user_id);
    }
    $ret .= ";{$key_name};";
    $parameter_count = count($parameters);
    foreach ($parameters as $key => $value) {
      $ret .= "<{$value}>";
      if($key < $parameter_count) {
        $ret .= "^";
      }
    }
    return $ret;
  }
}
