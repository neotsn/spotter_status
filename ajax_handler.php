<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 6:29 PM
	 */

	session_start();
	define('PATH_ROOT', './');
	require_once('config.php');

	// Get the request vars
	$mode = get_request('mode', '');
	$submode = get_request('submode', '');
	$office = get_request('nws_office', '');
	$user_id = get_request('user_id', '');
	$offices = get_request('offices', array());

	$db = new db_pdo();

	// Toggle the mode
	switch($mode) {
		case 'disconnectService':
			$user = new user($user_id);
			$user->validate_user_session();

			// Destroy the friendship
			$t = new twitter_connection_info();

			// Connect from the user's perspective...
			$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $user->oauth_token, $user->oauth_token_secret);
			$connection->post('friendships/destroy', array('screen_name' => 'noaaalerts'));

			// Delete the user
			$db->delete(TABLE_USERS_OFFICES, array(USERS_OFFICES_USER_ID => $user->id)); // Cleanup Office Associations
			$db->delete(TABLE_USERS, array(USERS_ID => $user->id));

			unset($user);
			header('Location: ./clearsessions.php');
			break;
		case 'followOnTwitter':
			$user = new user($user_id);
			$user->validate_user_session();

			// Destroy the friendship
			$t = new twitter_connection_info();

			// Connect from the user's perspective...
			$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $user->oauth_token, $user->oauth_token_secret);

			// Get the relationship status
			$relation_result = $connection->get('friendships/show', array('source_id' => $t->app_twitter_id, 'target_id' => $user->id));

			// Did the user want to follow and aren't already following?
			$is_following = $relation_result->relationship->source->followed_by;
			$can_dm = $relation_result->relationship->source->can_dm;
			if(!$is_following) {
				// Create the follow on twitter's side
				$friend_result = $connection->post('friendships/create', array('screen_name' => 'noaaalerts'));

				// Run the relationship check again to confirm
				$relation_result = $connection->get('friendships/show', array('source_id' => $t->app_twitter_id, 'target_id' => $user->id));
				$is_following = ($relation_result->relationship->source->followed_by) ? 1 : 0;
				$can_dm = ($relation_result->relationship->source->can_dm) ? 1 : 0;

				// Update the follower status in the database
				$update_pairs = array(USERS_IS_FOLLOWER => $is_following, USERS_CAN_DM => $can_dm);
				$criteria_pairs = array(
					array('field' => USERS_ID, 'op' => '=', 'value' => $user_id)
				);
				$db->update(TABLE_USERS, $update_pairs, $criteria_pairs);
			}

			if(!$is_following) {
				$_SESSION['msg']['error'][] = "Attempted to follow @NOAAalerts, but failed. Please try again.";
			} else {
				$_SESSION['msg']['success'][] = "You are now following @NOAAalerts on Twitter.";
				if(!$can_dm) {
					$_SESSION['msg']['info'][] = "Uh-oh! While you are a @NOAAalerts follower, we are unable to DM you. Are we blocked?";
				}
			}

			header("Location: ./profile.php");
			break;
		case 'saveOfficelist':
			$users_offices_params = array();
			$cron_check_params = array();
			$office_ids = array();

			// If offices were sent, spool them
			if(!empty($offices)) {
				foreach($offices as $office) {
					$users_offices_params[] = array(
						USERS_OFFICES_OFFICE_ID => $office,
						USERS_OFFICES_USER_ID   => $user_id
					);

					$cron_check_params[] = array(
						CRON_OFFICE_ID => $office
					);
				}
			}

			// Clear old settings, and add new settings, if any
			$db->delete(TABLE_USERS_OFFICES, array(USERS_OFFICES_USER_ID => $user_id));
			if(!empty($users_offices_params)) {
				$db->replace_multiple(TABLE_USERS_OFFICES, $users_offices_params);
			}

			// Update CRON check
			$db->replace_multiple(TABLE_CRON_OFFICE_CHECK, $cron_check_params);

			break;
		case 'getOfficelist':
			$user = new user($user_id);
			$user->validate_user_session();

			$selected = $user->get_users_office_ids();

			// Fetch all the offices
			$office_rows = $db->query(SQL_SELECT_ALL_FROM_OFFICES);

			// Group them by state
			$offices_array = array();
			foreach($office_rows as $office_row) {
				$offices_array[$office_row[OFFICES_STATE]][$office_row[OFFICES_ID]] = $office_row[OFFICES_CITY];
			}

			// Generate the templates for each state
			$states_html = '';
			foreach($offices_array as $state => $city_data) {

				$cities_html = '';
				foreach($city_data as $office_id => $city) {
					$city_template = new template('office_cities', false, false);
					$city_template->set_template_vars(array(
						'TXT_OFFICE_ID'         => $office_id,
						'TXT_OFFICE_CITY'       => $city,
						'TXT_OFFICE_CITY_CLASS' => (isset($selected[$office_id])) ? 'nws_office_city_selected' : 'nws_office_city',
						'I_OFFICE_PRESELECTED' => (isset($selected[$office_id])) ? '<input type="hidden" value="'.$office_id.'" name="offices['.$office_id.']" />' : ''
					));
					$cities_html .= $city_template->compile();
				}

				$state_template = new template('office_states', false, false);
				$state_template->set_template_vars(array(
					'TXT_OFFICE_STATE'  => $state,
					'TXT_OFFICE_CITIES' => $cities_html
				));
				$states_html .= $state_template->compile();
			}

			$template = new template('office_list', false, false);
			$template->set_template_vars(array(
				'TXT_STATE_OFFICE_LIST' => $states_html
			));
			$template->display();

			break;
	}