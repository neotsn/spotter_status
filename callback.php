<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/21/2014
	 * Time: 7:42 PM
	 */

	/**
	 * @file
	 * Take the user when they return from Twitter. Get access tokens.
	 * Verify credentials and redirect to based on response from Twitter.
	 */

	/* Start session and load lib */
	session_start();
	define('PATH_ROOT', './');
	require_once('config.php');

	/* If the oauth_token is old redirect to the connect page. */
	if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
		$_SESSION['oauth_status'] = 'oldtoken';
		header('Location: ./clearsessions.php');
	}

	$t = new twitter_connection_info();

	/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
	$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

	/* Request access tokens from twitter */
	$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

	/* Save the access tokens. Normally these would be saved in a database for future use. */

	/* Remove no longer needed request tokens */
	unset($_SESSION['oauth_token']);
	unset($_SESSION['oauth_token_secret']);

	/* If HTTP response is 200 continue otherwise send to connect page to retry */
	if (200 == $connection->http_code) {
		/* The user has been verified and the access tokens can be saved for future use */
//		$db = new db_pdo();
//		$fv_pairs = array(
//			'oauth_token' => get_array_value($access_token, 'oauth_token'),
//			'oauth_token_secret' => get_array_value($access_token, 'oauth_token_secret')
//		);
//		$db->update(SQL_REPLACE_GENERIC, TABLE_USERS, $fv_pairs);

		$_SESSION['access_token'] = $access_token;
		$_SESSION['status'] = 'verified';
		header('Location: ./setup.php');
	} else {
		/* Save HTTP status for error dialog on connnect page.*/
		header('Location: ./clearsessions.php');
	}
