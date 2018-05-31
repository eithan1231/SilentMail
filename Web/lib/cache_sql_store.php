<?php

class cache_sql_store extends cache
{
  const KEY_MAX_LENGTH = 150;
  private $duration = 0;

  function __construct($duration)
  {
    $this->duration = $duration;
  }

  public function store(string $key, $value)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_hashed = $this->hashKey($key);
    $serialized_value = serialize($value);

    // Checking size of serialized object
    if(strlen($serialized_value) > 65535) {
      return false;
    }

    if($this->exists($key)) {
      if($stmt = sql::prepare("
        UPDATE `cache`
        SET
          `value` = ?
          `modified` = ". sql::quote(time) ."
        WHERE
          `name` = ". sql::quote($key_hashed) ."
      ")) {
        $nil = null;
        $stmt->bind_param('b', $nil);
        $stmt->send_long_data(0, $serialized_value);
        if(!$stmt->execute()) {
          throw new Exception("Failed to execute prepared query.");
        }
        $stmt->close();
      }
      else {
        throw new Exception("Failed to prepare query");
      }
    }
    else {
      if($stmt = sql::prepare("
        INSERT INTO `cache`
        (`name`, `value`, `modified`)
        VALUES (
          ". sql::quote($key_hashed) .",
          ?,
          ". sql::quote(time) ."
        )
      ")) {
        $nil = null;
        $stmt->bind_param('b', $nil);
        $stmt->send_long_data(0, $serialized_value);
        if(!$stmt->execute()) {
          throw new Exception("Failed to execute prepared query.");
        }
        $stmt->close();
      }
      else {
        throw new Exception("Failed to prepare query");
      }
    }
  }

  public function get(string $key)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_hashed = $this->hashKey($key);

    $result = sql::query_fetch("
      SELECT `value`, `modified`
      FROM `cache`
      WHERE
        `name` = ". sql::quote($key_hashed) ."
    ");

    if($result['modified'] < time - $this->duration) {
      sql::query("
        DELETE FROM `cache`
        WHERE
          `name` = ". sql::quote($key_hashed) ."
      ");
      return false;
    }

    return unserialize($result['value']);
  }

  public function exists(string $key)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_hashed = $this->hashKey($key);

    $result = sql::query_fetch("
      SELECT count(1) as cnt
      FROM `cache`
      WHERE
        `name` = ". sql::quote($key_hashed) ."
    ");

    if($result === false) {
      return false;
    }

    return $result['cnt'] > 0;
  }

  public function purge(string $key)
  {
    if(!$this->validKey($key)) {
      return false;
    }

    $key_hashed = $this->hashKey($key);

    sql::query("
      DELETE FROM `cache`
      WHERE
        `name` = ". sql::quote($key_hashed) ."
    ");

    return true;
  }

  private function hashKey(string $key)
  {
    return hash('sha512', $key);
  }
}
