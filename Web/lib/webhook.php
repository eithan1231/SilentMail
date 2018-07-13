<?php

class webhooks
{
  public static function runHook($user_id, $hook, $data)
  {
    if(!self::enabled()) {
      return function_response(false, [
        'message' => 'WebHooks have been disabled.'
      ]);
    }

    // NOTE: Do any database querying here, as this function will be called
    // afrer SQL connection is closed.
    $hooks = sql::query_fetch_all("
      SELECT `id`, `url`
      FROM `webhooks`
      WHERE
        `user_id` = ". sql::quote($user_id) ."
    ");

    if($hooks === false) {
      return function_response(false, [
        'message' => 'No hooks found.'
      ]);
    }

    // Registering the hook to run after script execution has completed.
    shutdown_events::register(function() use($user_id, $hook, $data, $hooks) {
      if(config['webhook']['localMode']) {
        foreach($hooks as $hook) {
          $url_parsed = parse_url($hook['url']);
          if($url_parsed === false) {
            continue;
          }

          $post = json_encode($data);
          $post_length = strlen($post);

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $hook['url']);
          curl_setopt($ch, CURLOPT_PORT, $node['endpoint']['port']);
          curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Host: ". $url_parsed['host'],
            "User-agent: ". misc::buildUserAgent(),
            "Content-Length: {$post_length}",
            "Content-Type: text/json"
          ]);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

          curl_exec($ch);

          $status = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));

          curl_close($ch);
        }
      }
      else {// node mode.
        // Selecting a random WebHook node.
        $nodes = config['webhook']['nodes'];// Shortening the config.
        $exclude = [];
        while($index = array_rand_index($nodes, $exclude)) {
          if(count($exclude) === count($nodes)) {
            // All out nodes are disabled.
            return;
          }

          if($nodes[$index]['enabled'] === false) {
            // Node is disabled, lets exclude it and continue search.
            $exclude[] = $index;
          }
          else {
            // Node is enabled. Let's break, and send it the request.
            break;
          }
        }
        $node = $nodes[$index];

        // Building a URL for the request, CURL (AFAIK), doesnt let you manually
        // set things like host, http scheme, path, etc.
        $url = "http://{$node['endpoint']['host']}/";

        // Getting the data for the request.
        $post = json_encode([
          'user_id' => $user_id,
          'hooks' => $hooks,
          'data' => $data
        ]);
        $data_length = strlen($post);

        // Sending a command to the node, to
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, $node['endpoint']['port']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "Host: {$node['endpoint']['host']}",
          "User-agent: ". misc::buildUserAgent(),
          "Key: {$node['endpoint']['key']}",
          "Content-Length: {$data_length}",
          "Content-Type: text/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

        curl_exec($ch);

        $status = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));

        curl_close($ch);

        switch($status) {
          case 200: {
            break;
          }

          case 401: {
            // Unauthorized, try another node.
          }

          default: {
            // Something went wrong
          }
        }
      }
    });

    return function_response(true, [
      'message' => 'Assumed WebHook success - WebHooks are not called in real-time, they are added to a queue.'
    ]);
  }

  public static function create(string $url, $user_id = ses_user_id)
  {
    if(!self::enabled()) {
      return function_response(false, [
        'message' => 'WebHooks have been disabled.'
      ]);
    }

    if(strlen($url) > 1024) {
      return function_response(false, [
        'message' => 'URL exceeds the length of 1024.'
      ]);
    }

    // Parsing URL making sure it has the required 'url parts'. So, host, path.
    $url_parsed = parse_url($url);
    if(!isset($url_parsed['host'])) {
      return function_response(false, [
        'message' => 'Invalid URL: Hostname not found.'
      ]);
    }

    // Checking SSL policy
    if(config['webhook']['sslPolicy'] !== sm_webhook_sslpolicy_none) {

      // Forced SSL
      if(config['webhook']['sslPolicy'] == sm_webhook_sslpolicy_force) {
        if(strtolower($url_parsed['scheme']) !== 'https') {
          return function_response(false, [
            'message' => 'URL\'s prefixed with HTTP are not allowed; Replace HTTP with HTTPS.'
          ]);
        }
      }

      // SSL is not allowed
      if(config['webhook']['sslPolicy'] == sm_webhook_sslpolicy_disallow) {
        if(strtolower($url_parsed['scheme']) !== 'http') {
          return function_response(false, [
            'message' => 'HTTPS is not allowed; Try replace https with http at the beginning of the url.'
          ]);
        }
      }
    }

    // Inserting to database.
    $result = sql::query("
      INSERT INTO `webhooks`
      (`id`, `user_id`, `url`)
      VALUES (
        NULL,
        ". sql::quote($user_id) .",
        ". sql::quote($url) ."
      )
    ");

    $id = sql::getLastInsertId();

    if($result) {
      return function_response(true, [
        'message' => 'Inserted successfully.',
        'id' => $id
      ]);
    }
    else {
      return function_response(false, [
        'message' => 'Database error: Failed to insert.'
      ]);
    }
  }

  public static function get($user_id = ses_user_id)
  {
    if(!self::enabled()) {
      return function_response(false, [
        'message' => 'WebHooks have been disabled.'
      ]);
    }

    $result = sql::query_fetch_all("
      SELECT `id`, `url`
      FROM `webhooks`
      WHERE
        `user_id` = ". sql::quote($user_id) ."
    ");

    if($result) {
      return function_response(true, [
        'message' => 'Fetched WebHooks successfully.',
        'hooks' => $result
      ]);
    }
    else {
      return function_response(false, [
        'message' => 'No WebHooks can be found.'
      ]);
    }
  }

  public static function enabled()
  {
    return config['webhook']['enabled'];
  }
}
