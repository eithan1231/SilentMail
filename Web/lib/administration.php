<?php

class administration
{
  /**
  * Does a query for $username with a wild card on the end.
  *
  * @param string $username
  *   The usernamew are searching for.
  * @param string $user_id
  *   The user who's requesting the user list.
  */
  public static function getUsersWildcard($username, $user_id = ses_user_id)
  {
    $result = sql::query_fetch_all("
      SELECT `id`, `name_first`, `name_last`, `username`
      FROM `user`
      WHERE
        `username_lower` LIKE ". sql::quote(sql::wildcardEscape($username) .'%') ."
      ORDER BY `username` ASC
    ");

    if($result === false) {
      return function_response(false, [
        'message' => 'No users found'
      ]);
    }

    return function_response(true, $result);
  }
}
