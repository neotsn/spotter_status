<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/25/2014
	 * Time: 11:36 AM
	 */

	/**
	 * @file
	 * Make user's settings visible in a single location
	 */

	session_start();
	define('PATH_ROOT', './');
	require_once('config.php');

	$user_id = get_session('userid', null);

	$db = new db_pdo();
	$user = new user($user_id);
	$user->validate_user_session();

	// Get the user's subscribed offices
	$office_rows = $user->get_users_office_rows();
	$spotter_statements = $user->get_statements();

	// Make the Spotter Statement Cards
	$forecast_cards_html = '';
	$b_has_forecast_cards = (!empty($spotter_statements)) ? 1 : 0;
	if($b_has_forecast_cards) {
		foreach($spotter_statements as $statement) {
			$spotter_template = new template('forecast_card', false, false);
			$spotter_template->set_template_vars(array(
				'TXT_STATEMENT_OFFICE'    => strtoupper($statement[STATEMENTS_OFFICE_ID]),
				'TXT_STATEMENT_CITY'      => $statement[OFFICES_CITY],
				'TXT_STATEMENT_STATE'     => $statement[OFFICES_STATE],
				'TXT_STATEMENT_TIMESTAMP' => date('Y-m-d H:i:s O', $statement[STATEMENTS_LAST_OUTLOOK]),
				'TXT_STATEMENT_MESSAGE'   => ucfirst(strtolower(str_replace('|', "<br />", $statement[STATEMENTS_MESSAGE])))
			));
			$forecast_cards_html .= $spotter_template->compile();
		}
	}

	// Gather the Subscribed Offices
	$b_has_offices = (!empty($office_rows)) ? 1 : 0;
	if($b_has_offices) {

		// Group them by state
		$office_locations = array();
		foreach($office_rows as $office_row) {
			$office_locations[$office_row[OFFICES_STATE]][$office_row[OFFICES_ID]] = $office_row[OFFICES_CITY];
		}

		$subscribed_offices_html = '';
		foreach($office_locations as $state => $city_data) {

			$cities_html = '';
			foreach($city_data as $office_id => $city) {
				$city_template = new template('office_cities_profile', false, false);
				$city_template->set_template_vars(array(
					'TXT_OFFICE_ID'         => $office_id,
					'TXT_OFFICE_CITY'       => $city,
					'TXT_OFFICE_CITY_CLASS' => 'nws_office_city',
					'I_OFFICE_PRESELECTED'  => ''
				));
				$cities_html .= $city_template->compile();
			}

			$state_template = new template('office_states_profile', false, false);
			$state_template->set_template_vars(array(
				'TXT_OFFICE_STATE'  => $state,
				'TXT_OFFICE_CITIES' => $cities_html
			));
			$subscribed_offices_html .= $state_template->compile();
		}
	}

	//	$connection_errors = array();
	$dm_class = "profile_conn_ok";
	$follow_class = "profile_conn_ok";
	if(!$user->can_dm) {
		$dm_class = "profile_conn_bad";
	}

	if(!$user->is_follower) {
		if(!$user->can_dm) {
			$follow_class = "profile_conn_bad";
		} else {
			$follow_class = "profile_conn_warn";
		}
	}

	// Get the error-handling messages
	$messages_html = '';
	if(!empty($_SESSION['msg'])) {
		foreach($_SESSION['msg'] as $type => $msgs) {
			$msg_template = new template('msg', false, false);

			foreach($msgs as $msg) {
				$msg_template->set_template_vars(array(
					'TXT_TYPE' => $type,
					'TXT_MSG'  => ucwords($type).': '.$msg
				));
				$messages_html .= $msg_template->compile();
			}
		}
		unset($_SESSION['msg']);
	}

	$template = new template('profile');
	$template->set_template_vars(array(
		'IMG_USER_PROFILE'           => $user->profile_image_url,
		'TXT_USER_SCREEN_NAME'       => $user->screen_name,
		'TXT_LOGOUT_USER'            => 'Logout',
		'TXT_SPOTTER_FORECAST_CARDS' => $forecast_cards_html,
		'TXT_SUBSCRIBED_OFFICES'     => $subscribed_offices_html,
		'TXT_RELATIONSHIP_STATUS'    => $follow_class,
		'TXT_DM_STATUS'              => $dm_class,
		'TXT_USER_ID'                => $user->id,
		'TXT_MSGS'                   => $messages_html,
		'B_NO_RELATIONSHIP'          => !$user->is_follower,
		'B_HAS_OFFICES'              => $b_has_offices,
		'B_HAS_FORECAST_CARDS'       => $b_has_forecast_cards
	));
	$template->display();
