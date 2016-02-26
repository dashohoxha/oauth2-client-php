<?php
if ($_SERVER["SCRIPT_FILENAME"] == __FILE__) {
  highlight_file(__FILE__);
  exit;
}

$server_url = 'https://btranslator.net';

// user-password flow
$oauth2_clients['user-password'] = array(
  'token_endpoint' => $server_url . '/oauth2/token',
  'auth_flow' => 'user-password',
  'client_id' => 'test1',
  'client_secret' => '12345',
  'username' => 'user1',
  'password' => 'pass1',
);

// client-credentials flow
$oauth2_clients['client-credentials'] = array(
  'token_endpoint' => $server_url . '/oauth2/token',
  'auth_flow' => 'client-credentials',
  'client_id' => 'test1',
  'client_secret' => '12345',
);

// server-side flow
$redirect_uri = preg_replace('#/test/.*#', '', get_current_full_url());
$redirect_uri .= '/authorized.php';
$oauth2_clients['server-side'] = array(
  'token_endpoint' => $server_url . '/oauth2/token',
  'auth_flow' => 'server-side',
  'client_id' => 'test1',
  'client_secret' => '12345',
  'authorization_endpoint' => $server_url . '/oauth2/authorize',
  'redirect_uri' => $redirect_uri,
);

/**
 * Google
 *
 * Create a new client at:
 * https://console.developers.google.com/project/827835017427/apiui/credential
 * and set
 * Redirect URIs: https://btranslator.net/oauth2-client-php/authorized.php
 * Javascript Origins: https://btranslator.net
 */
$oauth2_clients['google'] = array(
  'token_endpoint' => 'https://accounts.google.com/o/oauth2/token',
  'auth_flow' => 'server-side',
  'client_id' => '827835017427-h1ad5e20v14nbuq12da8sjglagjb7gkm.apps.googleusercontent.com',
  'client_secret' => 'svEm4UIPCJaJpIu6pO_Pw2zj',
  'client_auth' => 'data',
  'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/auth',
  'redirect_uri' => 'https://btranslator.net/oauth2-client-php/authorized.php',
  'scope' => 'profile',
);

/**
 * Facebook
 *
 * Create a new application at: https://developers.facebook.com/apps
 */
$oauth2_clients['facebook'] = array(
  'token_endpoint' => 'https://graph.facebook.com/oauth/access_token',
  'auth_flow' => 'server-side',
  'client_id' => '751156001654122',
  'client_secret' => '55e026a2707d62fdebcc2df9901311c1',
  'client_auth' => 'data',
  'authorization_endpoint' => 'https://www.facebook.com/dialog/oauth',
  'redirect_uri' => 'https://btranslator.net/oauth2-client-php/authorized.php',
  'scope' => 'user_about_me',
  'provider' => 'facebook',
);

/**
 * LinkedIn
 *
 * See: https://developer.linkedin.com/documents/authentication
 * and: https://developer.linkedin.com/documents/code-samples
 */
$oauth2_clients['linkedin'] = array(
  'token_endpoint' => 'https://www.linkedin.com/uas/oauth2/accessToken',
  'auth_flow' => 'server-side',
  'client_id' => '77msoa2400jp42',
  'client_secret' => 'FcGVGCOeYTaDI1c2',
  'client_auth' => 'data',
  'authorization_endpoint' => 'https://www.linkedin.com/uas/oauth2/authorization',
  'redirect_uri' => 'https://btranslator.net/oauth2-client-php/authorized.php',
  'scope' => 'r_basicprofile',
);

// twitter does not support oauth2

