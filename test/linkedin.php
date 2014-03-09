<?php
include_once('test.php');

// get the profile data
$url = 'https://api.linkedin.com/v1/people/~:(firstName,lastName)'
   . '?' . http_build_query(array('oauth2_access_token' => $access_token));
//$profile = http_request("https://api.linkedin.com/v1/people/~:(firstName,lastName)?oauth2_access_token=$access_token");
$profile = http_request($url);
print '<xmp>';
print_r($profile);
print '</xmp>';

