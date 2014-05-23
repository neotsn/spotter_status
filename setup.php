<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/21/2014
	 * Time: 8:04 PM
	 */

	/**
	 * @file
	 * User has successfully authenticated with Twitter. Access tokens saved to session and DB.
	 */
	session_start();
	define('PATH_ROOT', './');
	require_once('config.php');

	// If access tokens are not available redirect to connect page.
	if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
		// Lost the access token in session somehow, destroy and start again
		header('Location: ./clearsessions.php');
	}

	// We don't have a valid session, so we need to set it up again.
	$t = new twitter_connection_info();

	/* Get user access tokens out of the session. */
	$access_token = $_SESSION['access_token'];

	/* Create a TwitterOauth object with consumer/user tokens. */
	$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);

	/* If method is set change API call made. Test is called by default. */
	$content = $connection->get('account/verify_credentials');

	// Compile the user's data for database entry
	$sid = sha1($access_token['oauth_token'] . $access_token['oauth_token_secret'] . time() . $content->screen_name);
	$sid_expire = time() + (86400 * 7); // 7 days from now

	$user_params = array(
		'id'                      => $content->id,
		'screen_name'             => $content->screen_name,
		'name'                    => $content->name,
		'profile_image_url_https' => $content->profile_image_url_https,
		'oauth_token'             => $access_token['oauth_token'],
		'oauth_token_secret'      => $access_token['oauth_token_secret'],
		'sid'                     => $sid,
		'sid_expire'              => $sid_expire
	);

	// Update the user's database entry with the new info
	$db = new db_pdo();
	$db->update(SQL_REPLACE_GENERIC, TABLE_USERS, $user_params);

	$_SESSION['sid'] = $sid;
	$_SESSION['sid_expire'] = $sid_expire;
	$_SESSION['userid'] = $content->id;

	// Send back to index for configuration options with session set
	header('Location: ./index.php');

	/* Some example calls */
	//$connection->get('users/show', array('screen_name' => 'abraham'));
	//$connection->post('statuses/update', array('status' => date(DATE_RFC822)));
	//$connection->post('statuses/destroy', array('id' => 5437877770));
	//$connection->post('friendships/create', array('id' => 9436992));
	//$connection->post('friendships/destroy', array('id' => 9436992));
