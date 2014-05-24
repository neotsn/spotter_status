<?php
	/**
	 * Created by PhpStorm.
	 * User: Chris
	 * Date: 5/24/14
	 * Time: 11:59 AM
	 */

	/**
	 * @file
	 * Checks for new HWO reports every
	 * For twitter connection:
	 *        Check if consumer token is set and if so send user to get a request token.
	 */

	session_start();
	define('PATH_ROOT', './');
	require_once('config.php');

	$db = new db_pdo();
	$t = new twitter_connection_info();
	// Set up a twitter connection for the app
	$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $t->access_token, $t->access_token_secret);
	//	$connection->post('statuses/update', array('status' => "@neotsn Checking for updated HWOs of selected NWS Offices #HWOCheck ".time())); // temporary cron check

	$offices_to_check = $db->query(SQL_SELECT_ALL_FROM_OUTDATED_ACTIVE_CRON_OFFICES, array(time() - (60 * 30)));

	$x = 0;
	if(!empty($offices_to_check)) {
		foreach($offices_to_check as $office) {

			$outlook = new outlook($office['office_id']);
			$outlook->process_outlooks();
			if(!$outlook->does_report_hash_exist($db)) {
				$outlook->save_outlooks($db);

				$users_ids_to_notify = $db->query(SQL_SELECT_USER_IDS_BY_OFFICE_ID, array($office['office_id']));
				if(!empty($users_ids_to_notify)) {
					foreach($users_ids_to_notify as $user) {
						foreach($outlook->statements as $key => $statements) {
							foreach($statements as $statement) {
								$connection->post('direct_messages/new', array('text' => $outlook->prepare_message($statement, $office['office_id']), 'user_id' => $user['user_id']));
								$x++;
							}
						}
					}
				}
			}
		}
	}

	if($x) {
		$connection->post('statuses/update', array('status' => "@neotsn Sent out $x notifications #HWOCheck ".time())); // temporary cron check
	}