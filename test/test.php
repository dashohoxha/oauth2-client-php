<?php
if ($_SERVER["SCRIPT_FILENAME"] == __FILE__) {
  highlight_file(__FILE__);
  exit;
}

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
  print "access_token = <strong>$access_token</strong>";
}
catch (Exception $e) {
  //error_log('Error: ' . $e->getMessage());
  var_log($e->getMessage(), 'Exception message');
  var_log($e, 'Exception');
}

//session_destroy();


