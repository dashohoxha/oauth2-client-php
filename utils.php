<?php
/**
 * Utility functions.
 */

/**
 * When debugging is true, some debug messages will be logged
 * to '/tmp/php.log'.
 */
define('DEBUG', true);

/**
 * Dump the content of a variable into a log file (used for debugging).
 */
function var_log($var, $label ='') {
  if (!defined('DEBUG'))  return;

  $file = '/tmp/php.log';
  $content = "\n==> $label: " . print_r($var, true);
  file_put_contents($file, $content, FILE_APPEND);
}


/**
 * Return the full URL of the current request (without query parameters).
 */
function get_current_full_url() {
  $schema = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ? 'https://' : 'http://';
  $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
  $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
  return $schema . $host . $uri_parts[0];
}


/* ----------------------------------------------- */

/**
 * Log the content of the request and session for debugging.
 */
if (defined('DEBUG')) {
  var_log('', get_current_full_url());
  var_log($_REQUEST, '$_REQUEST');
  if (isset($_SESSION['oauth2_client'])) {
    var_log($_SESSION['oauth2_client'], '$_SESSION[oauth2_client]');
  }
}
