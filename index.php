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
	$session_id = get_session('sid', null, 'string');
	$session_expire = get_session('sid_expire', null, 'string');
	$user_id = get_session('userid', null, 'string');

	$db = new db_pdo();
	$db->query(SQL_SELECT_USER_BY_ID, array($user_id));
	$user_row = $db->get_next();

	if ($user_id && !empty($user_row) && get_array_value($user_row, USERS_SID_EXPIRE, 0) > time() && get_array_value($user_row, USERS_SID, '') == $session_id) {
		// Valid User In Session, create Setup inputs

		$template_vars = array(
			'IMG_USER_PROFILE'     => get_array_value($user_row, USERS_PROFILE_IMAGE_URL_HTTPS, ''),
			'TXT_USER_SCREEN_NAME' => '@' . get_array_value($user_row, USERS_SCREEN_NAME, ''),
			'TXT_LOGOUT_USER'      => 'Logout'
		);

		$template = new template('setup');
		$template->set_template_vars($template_vars);
		$template->display();
	} else {
		$template = new template('splash');
		$template->display();
	}

