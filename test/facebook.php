<?php
include_once('test.php');

// get the profile data
$profile = http_request(
  $url = 'https://graph.facebook.com/me',
  $options = array(
    'headers' => array('Authorization' => 'Bearer ' . $access_token),
  )
);
print '<xmp>';
print_r($profile);
print '</xmp>';

