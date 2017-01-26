<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/21/2014
 * Time: 7:42 PM
 */
use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * @file
 * Take the User when they return from Twitter. Get access tokens.
 * Verify credentials and redirect to based on response from Twitter.
 */

/* Start session and load lib */
session_start();
define('PATH_ROOT', dirname(__FILE__));
require_once(PATH_ROOT . '/config.php');

/* If the oauth_token is old redirect to the connect page. */
if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
    $_SESSION['oauth_status'] = 'oldtoken';
    header('Location: ./clearsessions.php');
}

$t = new TwitterConnectionInfo();

/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

/* Request access tokens from twitter */
$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));
//$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

/* Save the access tokens. Normally these would be saved in a database for future use. */

/* Remove no longer needed request tokens */
unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);

/* If HTTP response is 200 continue otherwise send to connect page to retry */
if (200 == $connection->getLastHttpCode()) {
    /* The User has been verified and the access tokens can be saved for future use */

    $_SESSION['access_token'] = $access_token;
    $_SESSION['status'] = 'verified';
    header('Location: ./setup.php');
} else {
    /* Save HTTP status for error dialog on connnect page.*/
    header('Location: ./clearsessions.php');
}
