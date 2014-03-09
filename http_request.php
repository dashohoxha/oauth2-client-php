<?php
/**
 * Function for making http requests.
 */
function http_request($url, $options =array(), $json_decode =true) {
  // debug
  var_log('', "--------------- start http_request ---------------------");
  var_log($url, 'URL');
  var_log($options, 'OPTIONS');

  // get the headers
  $header = '';
  if (isset($options['headers']) and is_array($options['headers'])) {
    foreach ($options['headers'] as $name => $value) {
      $header .= "$name: $value\r\n";
    }
  }
  $header .= "Accept: application/json\r\n";

  // create the context options
  if (isset($options['method']) and ($options['method'] == 'POST')) {
    $data = $options['data'];
    if (is_array($data))  $data = http_build_query($data);
    $header .= "Content-Length: " . strlen($data) . "\r\n";

    $context_options = array (
      'http' => array (
        'method' => 'POST',
        'header'=> $header,
        'content' => $data,
      ));
  }
  else {
    $context_options = array (
      'http' => array (
        'method' => 'GET',
        'header'=> $header,
      ));
  }

  // make the request and get the result
  $context = stream_context_create($context_options);
  $result = @file_get_contents($url, false, $context);
  $error = error_get_last();

  // check for errors
  if ($result === FALSE) {
    $error_msg = 'Error ' . $error['type'] . ': ' . $error['message'] . '; on file ' . $error['file'] . ': ' . $error['line'];
    throw new Exception($error_msg);
  }

  if ($json_decode) {
    $result = json_decode($result, true);
  }

  // debug
  var_log($result, 'RESULT');
  var_log('', "--------------- end http_request -----------------------");

  return $result;
}
