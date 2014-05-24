<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/20/2014
	 * Time: 9:32 PM
	 */

	/**
	 * @file
	 * Main splash page welcoming users and prompting them to sign in with Twitter.
	 * For twitter connection:
	 *        Check if consumer token is set and if so send user to get a request token.
	 */

	session_start();
	define('PATH_ROOT', './');
	require_once('config.php');

	// TODO Add tweet from NOAAAlerts that it is checking for new reports on cron.

	$session_id = get_session('sid', null);
	$session_expire = get_session('sid_expire', null);
	$user_id = get_session('userid', null);
	$msg = get_request('msg', null);

	$db = new db_pdo();
	$db->query(SQL_SELECT_USER_BY_ID, array($user_id));
	$user_row = $db->get_next();

	// TODO Turn this validation into a method of a class
	if($user_id && !empty($user_row) && get_array_value($user_row, USERS_SID_EXPIRE, 0) > time() && get_array_value($user_row, USERS_SID, '') == $session_id) {
		// Valid User In Session, create Setup inputs

		// Fetch all the offices
		$office_rows = $db->query(SQL_SELECT_ALL_FROM_OFFICE);

		// Group them by state
		$offices_array = array();
		foreach($office_rows as $office_row) {
			$offices_array[$office_row['state']][$office_row['id']] = $office_row['city'];
		}

		// Get the offices already selected by the user
		$selected_office_rows = $db->query(SQL_SELECT_OFFICE_IDS_BY_USER_ID, array($user_id));

		$selected = array();
		if(!empty($selected_office_rows)) {
			foreach($selected_office_rows as $selected_office_row) {
				$selected[$selected_office_row['office_id']] = $selected_office_row['user_id'];
			}
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
					'I_OFFICE_PRESELECTED'  => (isset($selected[$office_id])) ? '<input type="hidden" value="'.$office_id.'" name="offices['.$office_id.']" />' : ''
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

		// Get the error-handling messages
		$messages_html = '';
		if(!empty($_SESSION['msg'])) {
			foreach($_SESSION['msg'] as $type => $msgs) {
				$msg_template = new template('msg', false, false);

				foreach($msgs as $msg) {
					$msg_template->set_template_vars(array('TXT_TYPE' => $type, 'TXT_MSG' => ucwords($type).': '.$msg));
					$messages_html .= $msg_template->compile();
				}
			}
			unset($_SESSION['msg']);
		}

		$template_vars = array(
			'IMG_USER_PROFILE'            => get_array_value($user_row, USERS_PROFILE_IMAGE_URL_HTTPS, ''),
			'TXT_USER_SCREEN_NAME'        => '@'.get_array_value($user_row, USERS_SCREEN_NAME, ''),
			'TXT_LOGOUT_USER'             => 'Logout',
			'TXT_AGREE_TO_FOLLOW_CHECKED' => (get_array_value($user_row, USERS_IS_FOLLOWER, 0)) ? 'checked' : '',
			'TXT_STATE_OFFICE_LIST'       => $states_html,
			'TXT_USER_ID'                 => $user_id,
			'TXT_MSGS'                    => $messages_html
		);

		// TODO Add if-else logic to template drawing

		$template = new template('setup');
		$template->set_template_vars($template_vars);
		$template->display();
	} else {
		$template = new template('splash');
		$template->display();
	}

