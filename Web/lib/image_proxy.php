<?php

class image_proxy
{
  /**
  * Generates a url for the image to be proxied
  *
  * @param string $url
  *   The image url that you want to be proxied.
  */
  public static function getProxiedImageUrl($url)
  {
    global $route;
    return $route->getRoutePath('imageProxy', [
      'image' => $url
    ]);
  }

  public static function outputProxiedImage($img)
  {
    // TODO: Implement image proxy.
    header("location: $img");
    header("Cache-Control: max-age=120");
    /*if(strtolower(substr($img, 0, 8)) == "https://") {

    }
    else if(strtolower(substr($img, 0, 7)) == "http://") {

    }
    else {
      output_page::SetHttpStatus(500, "Unknown Image Scheme");
      return;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $img);*/
  }
}
