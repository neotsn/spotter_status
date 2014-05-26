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

	// Get the relationship status
	$relation_result = $connection->get('friendships/show', array('source_id' => $t->app_twitter_id, 'target_id' => $content->id));

	// Did the user want to follow and aren't already following?
	$is_following = $relation_result->relationship->source->followed_by;
	$can_dm = $relation_result->relationship->source->can_dm;

	// Compile the user's data for database entry
	$sid = sha1($access_token['oauth_token'] . $access_token['oauth_token_secret'] . time() . $content->screen_name);
	$sid_expire = time() + (86400 * 7); // 7 days from now

	$user_params = array(
		USERS_ID                      => $content->id,
		USERS_SCREEN_NAME             => $content->screen_name,
		USERS_NAME                    => $content->name,
		USERS_PROFILE_IMAGE_URL_HTTPS => $content->profile_image_url_https,
		USERS_OAUTH_TOKEN             => $access_token['oauth_token'],
		USERS_OAUTH_TOKEN_SECRET      => $access_token['oauth_token_secret'],
		USERS_SID                     => $sid,
		USERS_SID_EXPIRE              => $sid_expire,
		USERS_IS_FOLLOWER             => ($is_following) ? 1 : 0,
		USERS_CAN_DM                  => ($can_dm) ? 1 : 0
	);

	// Update the user's database entry with the new info
	$db = new db_pdo();
	$db->replace(TABLE_USERS, $user_params);

	$_SESSION['sid'] = $sid;
	$_SESSION['sid_expire'] = $sid_expire;
	$_SESSION['userid'] = $content->id;

	// Send back to index for configuration options with session set
	header('Location: ./profile.php');