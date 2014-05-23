<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/21/2014
	 * Time: 7:29 PM
	 */

	/**
	 * @file
	 * Redirects user to connect to twitter
	 */

	session_start();

	define('PATH_ROOT', './');
	require_once('config.php');

	$t = new twitter_connection_info();

	// Build TwitterOAuth object with the App credentials
	$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret);

	// Get temporary credentials
	/** @var array $request_token */
	$request_token = $connection->getRequestToken($t->oauth_callback);

	// Store temporary credentials to session
	$_SESSION['oauth_token'] = $token = get_array_value($request_token, 'oauth_token', '');
	$_SESSION['oauth_token_secret'] = get_array_value($request_token, 'oauth_token_secret', '');

	/* If last connection failed don't display authorization link. */
	switch ($connection->http_code) {
		case 200:
			/* Build authorize URL and redirect user to Twitter. */
			$url = $connection->getAuthorizeURL($token);
			header('Location: ' . $url);
			break;
		default:
			/* Show notification if something went wrong. */
			echo 'Could not connect to Twitter. Refresh the page or try again later.';
			exit;
	}