<?php
namespace OAuth2;

/**
 * Start the PHP session if it is not already started.
 * The session is needed for storing the tokens.
 */
session_start();

/**
 * Include utility functions like http_request(), get_current_full_url(), etc.
 */
include_once(dirname(__FILE__) . '/http_request.php');
include_once(dirname(__FILE__) . '/utils.php');

/**
 * Class OAuth2\Client gets authorization from an oauth2 server.
 *
 * Its goal is to get an access_token from the oauth2 server, so the most
 * important function is getAccessToken().
 *
 * It can use authorization flows: server-side, client-credentials
 * and user-password. The details for each case are passed
 * to the constructor. All the three cases need a client_id,
 * a client_secret, and a token_endpoint. There can be an optional
 * scope as well.
 */
class Client {
  /**
   * Unique identifier of an OAuth2\Client object.
   */
  protected $id = NULL;

  /**
   * Associative array of the parameters that are needed
   * by the different types of authorization flows.
   *  - auth_flow :: server-side | client-credentials | user-password
   *  - client_id :: Client ID, as registered on the oauth2 server
   *  - client_secret :: Client secret, as registered on the oauth2 server
   *  - client_auth :: Client authorization type (data | header). When 'data'
   *       then client_id and client_secret are passed as data fields.
   *       When 'header', they are passed with a header like:
   *         Authorization: Basic baze64_encode('client_id:secret_id')
   *       Optional, default is 'header'.
   *  - token_endpoint :: something like:
   *       https://oauth2_server.example.org/oauth2/token
   *  - authorization_endpoint :: somethig like:
   *       https://oauth2_server.example.org/oauth2/authorize
   *  - redirect_uri :: something like:
   *       https://example.org/oauth2-client-php/authorized.php
   *  - scope :: requested scopes, separated by a space
   *  - username :: username of the resource owner
   *  - password :: password of the resource owner
   */
  protected $params = array(
    'auth_flow' => NULL,
    'client_id' => NULL,
    'client_secret' => NULL,
    'client_auth' => 'header',
    'token_endpoint' => NULL,
    'authorization_endpoint' => NULL,
    'redirect_uri' => NULL,
    'scope' => NULL,
    'username' => NULL,
    'password' => NULL,
    'provider' => NULL,
  );

  /**
   * Associated array that keeps data about the access token.
   */
  protected $token = array(
    'access_token' => NULL,
    'expires_in' => NULL,
    'token_type' => NULL,
    'scope' => NULL,
    'refresh_token' => NULL,
    'expiration_time' => NULL,
  );

  /**
   * Construct an OAuth2\Client object.
   *
   * @param array $params
   *   Associative array of the parameters that are needed
   *   by the different types of authorization flows.
   */
  public function __construct($params = NULL, $id = NULL) {
    if ($params) $this->params = $params + $this->params;

    if (!$id) {
      $id = md5($this->params['token_endpoint']
            . $this->params['client_id']
            . $this->params['auth_flow']);
    }
    $this->id = $id;

    // Get the token data from the session, if it is stored there.
    if (isset($_SESSION['oauth2_client']['token'][$this->id])) {
      $this->token = $_SESSION['oauth2_client']['token'][$this->id] + $this->token;
    }
  }

  /**
   * Get and return an access token.
   *
   * If there is an existing token (stored in session),
   * return that one. But if the existing token is expired,
   * get a new one from the authorization server.
   */
  public function getAccessToken() {
    // Check wheather the existing token has expired.
    // We take the expiration time to be shorter by 10 sec
    // in order to account for any delays during the request.
    // Usually a token is valid for 1 hour, so making
    // the expiration time shorter by 10 sec is insignificant.
    // However it should be kept in mind during the tests,
    // where the expiration time is much shorter.
    $expiration_time = $this->token['expiration_time'];
    if ($expiration_time > (time() + 10)) {
      // The existing token can still be used.
      return $this->token['access_token'];
    }

    // Get another token.
    try {
      $token = $this->getTokenRefreshToken();
    }
    catch (\Exception $e) {
      $token = $this->getNewToken();
    }
    $token['expiration_time'] = $_SERVER['REQUEST_TIME'] + $token['expires_in'];

    // Store the token (on session as well).
    $this->token = $token;
    $_SESSION['oauth2_client']['token'][$this->id] = $token;

    // Redirect to the original path (if this is a redirection
    // from the server-side flow).
    self::redirect();

    // Return the token.
    return $token['access_token'];
  }

  /**
   * Get a new access_token using the refresh_token.
   *
   * This is used for the server-side and user-password
   * flows (not for client-credentials, there is no
   * refresh_token in it).
   */
  protected function getTokenRefreshToken() {
    if (!$this->token['refresh_token']) {
      throw new \Exception('There is no refresh_token.');
    }
    return $this->getToken(array(
        'grant_type' => 'refresh_token',
        'refresh_token' => $this->token['refresh_token'],
      ));
  }

  /**
   * Refresh token has failed and we are getting a new token.
   */
  protected function getNewToken() {
    $auth_flow = $this->params['auth_flow'];
    switch ($auth_flow) {
      case 'client-credentials':
        $token = $this->getToken(array(
                   'grant_type' => 'client_credentials',
                   'scope' => $this->params['scope'],
                 ));
        break;

      case 'user-password':
        $token = $this->getToken(array(
                   'grant_type' => 'password',
                   'username' => $this->params['username'],
                   'password' => $this->params['password'],
                   'scope' => $this->params['scope'],
                 ));
        break;

      case 'server-side':
        $token = $this->getTokenServerSide();
        break;

      default:
        throw new \Exception("Unknown authorization flow '$auth_flow'. Suported values for auth_flow are: client-credentials, user-password, server-side.");
        break;
    }

    return $token;
  }

  /**
   * Get an access_token using the server-side (authorization code) flow.
   *
   * This is done in two steps:
   *   - First, a redirection is done to the authentication
   *     endpoint, in order to request an authorization code.
   *   - Second, using this code, an access_token is requested.
   *
   * There are lots of redirects in this case and this part is the most
   * tricky and difficult to understand of the oauth2 client.
   *
   * Suppose that in the page 'http://host/xyz.php'
   * we try to get an access_token:
   *     $client = new OAuth2\Client(array(
   *         'token_endpoint' => 'https://oauth2_server/oauth2/token',
   *         'client_id' => 'client1',
   *         'client_secret' => 'secret1',
   *         'auth_flow' => 'server-side',
   *         'authorization_endpoint' => 'https://oauth2_server/oauth2/authorize',
   *         'redirect_uri' => 'https://oauth2_client/authorized.php',
   *       ));
   *     $access_token = $client->getAccessToken();
   *
   * From getAccessToken() we come to this function, getTokenServerSide(),
   * and since there is no $_GET['code'], we redirect to the authentication
   * url, but first we save the current uri and request in the session:
   *   $_SESSION['oauth2_client']['redirect'][$state]['uri'] = 'http://host/xyz.php';
   *
   * Once the authentication and authorization is done on the server, we are
   * redirected by the server to the page 'authorized.php' and then the
   * funcion OAuth2\client::redirect() is called.  It redirects to the saved
   * url 'http://host/xyz.php' (since
   * $_SESSION['oauth2_client']['redirect'][$state] exists), passing along the
   * query parameters sent by the server (which include 'code', 'state', and
   * maybe other parameters as well.)
   *
   * Now the code: $access_token = $client->getAccessToken(); is
   * called again and we come back for a second time to the function
   * getTokenServerSide(). However this time we do have a
   * $_GET['code'], so we get a token from the server and return it.
   *
   * Inside the function getAccessToken() we save the returned token in
   * session and then, since $_SESSION['oauth2_client']['redirect'][$state]
   * exists, we delete it and make another redirect to 'http://host/xyz.php'.
   * This third redirect is in order to have in browser the original url,
   * because from the last redirect we have something like this:
   * 'http://host/xyz.php?code=8557&state=3d7dh3&....'
   *
   * We come again for a third time to the code
   *     $access_token = $client->getAccessToken();
   * But this time we have a valid token already saved in session,
   * so the $client can find and return it without having to redirect etc.
   */
  protected function getTokenServerSide() {
    if (!isset($_GET['code'])) {
      header('Location: ' . $this->getAuthenticationUrl());
      exit;
    }
    else {
      return $this->getToken(array(
          'grant_type' => 'authorization_code',
          'code' => $_GET['code'],
          'redirect_uri' => $this->params['redirect_uri'],
        ));
    }
  }

  /**
   * Return the authentication url (used in case of the server-side flow).
   */
  protected function getAuthenticationUrl() {
    $state = md5(uniqid(rand(), TRUE));
    self::setRedirect($state);

    $query_params = array(
      'response_type' => 'code',
      'client_id'     => $this->params['client_id'],
      'redirect_uri'  => $this->params['redirect_uri'],
      'state' => $state
    );
    if ($this->params['scope']) {
      $query_params['scope'] = $this->params['scope'];
    }
    $endpoint = $this->params['authorization_endpoint'];
    return $endpoint . '?' . http_build_query($query_params);
  }

  /**
   * Save the information needed for redirection after getting the token.
   */
  public static function setRedirect($state, $redirect =NULL) {
    if ($redirect === NULL) {
      $redirect = array(
        'uri' => get_current_full_url(),
        'params' => $_REQUEST,
        'client' => 'oauth2_client',
      );
    }
    if (!isset($redirect['client'])) {
      $redirect['client'] = 'external';
    }
    $_SESSION['oauth2_client']['redirect'][$state] = $redirect;
  }

  /**
   * This function should be called from the redirect_uri
   * (on the server-side flow).
   */
  public static function authorized() {
    // Check for any errors in the server response.
    if (isset($_GET['error'])) {
      $error = $_GET['error'];
      $error_description = $_GET['error_description'];
      throw new \Exception("Authorization Error: $error: $error_description");
    }

    // Redirect to the client that started the authentication.
    self::redirect($clean = FALSE);
  }

  /**
   * Redirect to the original path.
   *
   * Redirects are registered with OAuth2\Client::setRedirect()
   * The redirect contains the url to go to and the parameters
   * to be sent to it.
   */
  protected static function redirect($clean =TRUE) {
    if (!isset($_REQUEST['state']))  return;
    $state = $_REQUEST['state'];

    if (!isset($_SESSION['oauth2_client']['redirect'][$state]))  return;
    $redirect = $_SESSION['oauth2_client']['redirect'][$state];

    if ($redirect['client'] != 'oauth2_client') {
      unset($_SESSION['oauth2_client']['redirect'][$state]);
    }
    else {
      if ($clean) {
        unset($_SESSION['oauth2_client']['redirect'][$state]);
        unset($_REQUEST['code']);
        unset($_REQUEST['state']);
      }
    }

    // Redirect.
    $query = http_build_query($redirect['params'] + $_REQUEST);
    $url = (empty($query) ? $redirect['uri'] : $redirect['uri'].'?'.$query);
    header("Location: $url");
    exit;
  }

  /**
   * Get and return an access token for the grant_type given in $params.
   */
  protected function getToken($data) {
    if (isset($data['scope']) and $data['scope'] == NULL) {
      unset($data['scope']);
    }

    $client_id = $this->params['client_id'];
    $client_secret = $this->params['client_secret'];
    $token_endpoint = $this->params['token_endpoint'];

    $options = array(
      'method' => 'POST',
      'data' => $data,
      'headers' => array(
        'Content-Type' => 'application/x-www-form-urlencoded',
        //'Authorization' => 'Basic ' . base64_encode("$client_id:$client_secret"),
      ),
    );
    if ($this->params['client_auth'] == 'data') {
      $options['data']['client_id'] = $client_id;
      $options['data']['client_secret'] = $client_secret;
    }
    else {
      $options['headers']['Authorization'] = 'Basic ' . base64_encode("$client_id:$client_secret");
    }
    if ($this->params['provider'] == 'facebook'
       and $data['grant_type'] == 'authorization_code') {
      // It has to be done differently for Facebook,
      // because it does not return a json response.
      $result = http_request($token_endpoint, $options, $json_decode = false);
      $response_params = array();
      parse_str($result, $response_params);
      if (!isset($response_params['access_token'])) {
        throw new \Exception('Failed to get token.');
      }
      return array(
        'access_token' => $response_params['access_token'],
        'expires_in' => $response_params['expires'],
      );
    }
    else {
      $result = http_request($token_endpoint, $options);
      if ($result === FALSE) {
        throw new \Exception('Failed to get token.');
      }
    }

    return $result;
  }

  /**
   * Share an access token with oauth2_client.
   *
   * Another oauth2 client that has been successfully authenticated
   * and has received an access_token, can share it with oauth2_client,
   * so that oauth2_client does not have to repeat the authentication
   * process again.
   *
   * Example:
   *   $client_id = $hybridauth->client_id;
   *   $token = array(
   *     'access_token' => $hybridauth->access_token,
   *     'refresh_token' => $hybridauth->refresh_token,
   *     'expires_in' => $hybridauth->access_token_expires_in,
   *     'expiration_time' => $hybridauth->access_token_expires_at,
   *     'scope' => $hybridauth->scope,
   *   );
   *   $token_endpoint = $oauth2->token_endpoint;
   *   $client_id = $oauth2->client_id;
   *   $auth_flow = 'server-side';
   *   $id = md5($token_endpoint . $client_id . $auth_flow);
   *   OAuth2\Client::setToken($id, $token);
   */
  public static function setToken($id, $token) {
    $_SESSION['oauth2_client']['token'][$id] = $token;
  }
}
