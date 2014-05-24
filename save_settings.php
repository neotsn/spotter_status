<?php
	/**
	 * Created by PhpStorm.
	 * User: Chris
	 * Date: 5/23/14
	 * Time: 5:00 PM
	 */

	session_start();
	define('PATH_ROOT', './');
	require_once('config.php');

	// Get request vars
	$user_id = get_request('user_id', null, 'number');
	$offices = get_request('offices', array());
	$agree_to_follow = get_request('agree_to_follow', 'off', 'string');
	$access_token = $_SESSION['access_token'];

	// Call some classes...
	$t = new twitter_connection_info();
	$db = new db_pdo();

	// Let's do some checking...
	// Grab the friendship between the user and app...

	// Connect from the user's perspective...
	$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);

	// Get the relationship status
	$relation_result = $connection->get('friendships/show', array('source_id' => $t->app_twitter_id, 'target_id' => $user_id));

	// Did the user want to follow and aren't already following?
	$is_following = $relation_result->relationship->source->followed_by;
	$can_dm = $relation_result->relationship->source->can_dm;
	if($agree_to_follow == 'on') {
		// Create the follow on twitter's side
		if(!$is_following) {
			$friend_result = $connection->post('friendships/create', array('screen_name' => 'noaaalerts'));
			$is_following = (strtolower($friend_result->screen_name) == 'noaaalerts') ? 1 : 0; // Test the result for db storage
		}

		// Update the follower status in the database
		$update_pairs = array(USERS_IS_FOLLOWER => $is_following);
		$criteria_pairs = array(
			array('field' => 'id', 'op' => '=', 'value' => $user_id)
		);
		$db->update(TABLE_USERS, $update_pairs, $criteria_pairs);
	} else if($agree_to_follow == 'off' && !$can_dm) {
		// They didn't want to follow, but we can't DM them, so error message
		$_SESSION['msg']['error'][] = "@NOAAalerts is unable to DM your account. Please mark the \"Agree to Follow @NOAAalerts\" checkbox, or change your twitter Public DM settings.";
	}

	// Run the relationship check again to confirm
	$relation_result = $connection->get('friendships/show', array('source_id' => $t->app_twitter_id, 'target_id' => $user_id));
	$is_following = $relation_result->relationship->source->followed_by;
	$can_dm = $relation_result->relationship->source->can_dm;

	// Update Offices for the User in the database
	$office_ids = array();
	if(!empty($offices)) {
		$params = array();
		foreach($offices as $office) {
			$params[] = array(
				'office_id' => $office,
				'user_id'   => $user_id
			);

			$office_ids[] = $office; // For the test DM later
		}

		// Clear old settings, and add new settings
		$db->delete(TABLE_USERS_OFFICES, array('user_id' => $user_id));
		$db->replace_multiple(TABLE_USERS_OFFICES, $params);
	}

	// Send a test "Welcome" DM with the offices the user is configured to get alerts for
	$received_dm = false;
	if($can_dm) {
		// Change context to the twitter app id
		$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $t->access_token, $t->access_token_secret);
		// Attempt to DM the user with a status update
		$dm_result = $connection->post('direct_messages/new', array('text' => 'You will now receive StormSpotter Activation forecasts from the NWS office'.(count($office_ids) > 1 ? 's' : '').' in: '.implode(', ', $office_ids), 'user_id' => $user_id));

		// Show message if failed to send DM
		$received_dm = ($dm_result->id) ? true : false;
		if(!$received_dm) {
			$_SESSION['msg']['info'][] = "We were unable to send a \"Welcome\" test Direct Message at this time. Your information is stored, and you should still receive StormSpotter Activation forecasts.";
		} else {
			$_SESSION['msg']['success'][] = "You should receive a Direct Message shortly confirming your settings. Thanks!";
		}
	}

	header('Location: index.php?msg=success');