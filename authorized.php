<?php
/**
 * This file is where oauth2 server redirects after authorization
 * (in the server-side flow).
 * Make sure that it can be accessed on the redirect_uri URL.
 */

include_once('oauth2_client.php');
OAuth2\Client::authorized();
