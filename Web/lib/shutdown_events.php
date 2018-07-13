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
      foreach(self::$shutdown_events as $key => $event) {
        if(!$event['register_shutdown']) {
          continue;
        }

        // calling event
        call_user_func($event['event']);

        // already run, unset it.
        unset(self::$shutdown_events[$key]);
      }
    });
  }

  public static function run()
  {
    foreach(self::$shutdown_events as $key => $event) {
      if($event['register_shutdown']) {
        continue;
      }

      // calling event
      call_user_func($event['event']);

      // already run, unset it.
      unset(self::$shutdown_events[$key]);
    }
  }

  /**
  * Registers a shutdown event
  *
  * @param callable $event
  *   The function/event thats called when shutdown happens.
  * @param bool $register_shutdown
  *   Setting this to true, will run the event on register_shutdown_function,
  *   otherwise I will run it before SQL is shutdown.
  */
  public static function register(callable $event, bool $register_shutdown = true)
  {
    self::initialize();
    self::$shutdown_events[] = [
      'register_shutdown' => $register_shutdown,
      'event' => $event
    ];
  }
}
