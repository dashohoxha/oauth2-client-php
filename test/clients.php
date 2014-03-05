<?php
$server_url = 'https://dev.l10n.org.xx';

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
