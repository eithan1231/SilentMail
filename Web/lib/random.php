<?php

class random
{
  private static $seed = null;

  private static $previous = 0;
  private static $x;

  public static function getSeed()
  {
    if(is_null($this->seed)) {
      $seed_tmp = hash('sha512', time . uniqueToken . versionHash . config['projectName'], true);
    }
  }

  public static function rand(int $min, int $max)
  {

  }
}
