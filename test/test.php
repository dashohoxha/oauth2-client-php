<?php
include_once('../oauth2_client.php');
include_once('clients.php');

$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
$client_id = str_replace('.php', '', basename($uri_parts[0]));
$oauth2_client = new OAuth2\Client(
  $params = $oauth2_clients[$client_id],
  $id = 'test-client-' . $client_id
);

try {
  $access_token = $oauth2_client->getAccessToken();
  print 'access_token=' . $access_token;
}
catch (Exception $e) {
  //error_log('Error: ' . $e->getMessage());
  var_log($e->getMessage(), 'Exception message');
  var_log($e, 'Exception');
  print 'Error: ' . $e->getMessage();
  print '<xmp>';  print_r($e);  print '</xmp>';
}

//session_destroy();