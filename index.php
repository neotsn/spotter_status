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

	$session_id = get_session('sid', null);
	$user_id = get_session('userid', null);
	$msg = get_request('msg', null);

	if($user_id && $session_id) {
		$db = new db_pdo();
		$user = new user($user_id);
		$user->validate_user_session();
		header('Location: ./profile.php');
	}

	$template = new template('splash');
	$template->display();