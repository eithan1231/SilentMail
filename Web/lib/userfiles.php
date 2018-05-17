<?php

class userfiles
{
  public static function new($file, $user_id = ses_user_id)
  {
    if(isset($file[0])) {
      $file = $file[0];
    }

    if($file['error'] !== UPLOAD_ERR_OK) {
      return function_response(false, [
        'message' => 'Failed to upload file. Code: '. $file['error']
      ]);
    }

    if(!is_numeric($file['size'])) {
      return function_response(false, [
        'message' => 'Invalid Size'
      ]);
    }

    if($file['size'] > config['userfileSizeLimit']) {
      return function_response(false, [
        'message' => 'File too large'
      ]);
    }

    // Initlizing variables
    $dir1 = cryptography::randomString(2);
    $dir2 = cryptography::randomString(2);
    $dir3 = cryptography::randomString(2);
    $filename = cryptography::randomString(16);
    $original_filename = $file['name'];

    // Generates the internal path. If it already exists, try try try again.
    $internal_path = misc::getUserfilePath($dir1, $dir2, $dir3, $filename);
    $dir = misc::getUserfilePath($dir1, $dir2, $dir3, '');
    while(file_exists($internal_path)) {
      $dir1 = cryptography::randomString(2);
      $dir2 = cryptography::randomString(2);
      $dir3 = cryptography::randomString(2);
      $filename = cryptography::randomString(16);
      $internal_path = misc::getUserfilePath($dir1, $dir2, $dir3, $filename);
      $dir = misc::getUserfilePath($dir1, $dir2, $dir3, '');
    }

    // Checking the directory that we're uploading to, exists.
    if(!file_exists($dir)) {
      mkdir($dir, 0666);
    }

    // Move to newly generated internal path
    if(!move_uploaded_file($file['tmp_name'], $internal_path)) {
      return function_response(false, [
        'message' => 'Failed to move internal file'
      ]);
    }

    // Inserting to database
    $result = sql::query("
      INSERT INTO `userfiles`
      (`id`, `creator`, `original_name`, `parent_dir_1`, `parent_dir_2`, `parent_dir_3`, `filename`, `size`, `date`)
      VALUES (
        NULL,
        ". sql::quote($user_id) .",
        ". sql::quote($original_filename) .",
        ". sql::quote($dir1) .",
        ". sql::quote($dir2) .",
        ". sql::quote($dir3) .",
        ". sql::quote($filename) .",
        ". sql::quote($file['size']) .",
        ". sql::quote(time) ."
      )
    ");

    if($result) {
      return function_response(true, [
        'message' => 'Success'
      ]);
    }
    else {
      return function_response(false, [
        'message' => 'Internal Error: SQL Query'
      ]);
    }
  }

  public static function delete($file_id, $user_id = ses_user_id)
  {
    throw new Exception("Not implemented");
  }

  public static function output($file_id, $user_id = ses_user_id)
  {
    $file = sql::query_fetch("
      SELECT `original_name`, `parent_dir_1`, `parent_dir_2`, `parent_dir_3`, `filename`, `size`, `date`
      FROM `userfiles`
      WHERE
        `id` = ". sql::quote($file_id) ." AND
        `creator` = ". sql::quote($user_id) ."
    ");

    if(!$file) {
      return function_response(false, [
        'message' => 'Not found'
      ]);
    }

    //
  }

  public static function output_as_untrusted($file_id, $user_id = ses_user_id)
  {
    if(headers_sent()) {
      return function_response(false, [
        'message' => 'Headers already sent'
      ]);
    }

    $file = sql::query_fetch("
      SELECT `original_name`, `parent_dir_1`, `parent_dir_2`, `parent_dir_3`, `filename`, `size`, `date`
      FROM `userfiles`
      WHERE
        `id` = ". sql::quote($file_id) ." AND
        `creator` = ". sql::quote($user_id) ."
    ");

    if(!$file) {
      return function_response(false, [
        'message' => 'Not found'
      ]);
    }

    header("Content-disposition: attachment; filename=\"". remove_clrf($file['original_name']) ."\"; name=\"". remove_clrf($file['original_name']) ."\"");

    $internal_path = misc::getUserfilePath(
      $file['parent_dir_1'], $file['parent_dir_2'], $file['parent_dir_3'],
      $file['filename']
    );

    if(!file_exists($internal_path)) {
      return function_response(false, [
        'message' => 'Cannot read file'
      ]);
    }

    header("Content-length: ". filesize($internal_path));
    readfile($internal_path);

    return function_response(true, [
      'message' => 'Successful'
    ]);
  }
}
