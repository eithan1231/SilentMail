<?php

class shutdown_events
{
  private static $shutdown_events = [];
  private static $initlized = false;

  private static function initialize()
  {
    if(self::$initlized) {
      return;
    }
    self::$initlized = true;

    register_shutdown_function(function() {
      foreach(self::$shutdown_events as $event) {
        call_user_func($event);
      }
    });
  }

  public static function register(callable $event)
  {
    self::initialize();
    self::$shutdown_events[] = $event;
  }
}
