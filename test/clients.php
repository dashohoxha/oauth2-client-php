<?php
if ($_SERVER["SCRIPT_FILENAME"] == __FILE__) {
  highlight_file(__FILE__);
  exit;
}

$server_url = 'https://test1.l10n.org.al';

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

// Google
$oauth2_clients['google'] = array(
  'token_endpoint' => 'https://accounts.google.com/o/oauth2/auth',
  'auth_flow' => 'server-side',
  'client_id' => '827835017427-h1ad5e20v14nbuq12da8sjglagjb7gkm.apps.googleusercontent.com',
  'client_secret' => 'svEm4UIPCJaJpIu6pO_Pw2zj',
  'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/auth',
  'redirect_uri' => 'https://test1.l10n.org.al/oauth2-client-php/authorized.php',
  'scope' => 'profile',
);

