<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/24/14
	 * Time: 11:59 AM
	 */
	/**
	 * @file
	 * Checks for new HWO reports on scheduled basis;
	 * Adds new reports when found, updates existing reports if older than 30 minutes
	 * For twitter connection:
	 *        Check if consumer token is set and if so send user to get a request token.
	 */
	define('PATH_ROOT', './');
	require_once(PATH_ROOT . 'config.php');

	// Initialize Variables
	$errors = 0;

	// Initialize Objects
	$db = new db_pdo();
	$t = new twitter_connection_info();

	// Set up a twitter connection for the app
	$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $t->access_token, $t->access_token_secret);

	// Gather all selected office ids that are out of date, or new
	// Returns OUTLOOK_OFFICE_ID
	$offices_to_check = $db->query(SQL_SELECT_ALL_FROM_OUTDATED_ACTIVE_CRON_OFFICES, array(time() - (60 * 30)));

	// Gather existing data for offices (last outlook & statement hashes
	// Returned fields: OUTLOOKS_OFFICE_ID, OUTLOOKS_HASH, STATEMENTS_HASH
	$existing_office_data = $db->query(SQL_SELECT_EXISTING_ACTIVE_CRON_OFFICE_DATA, array(time() - (60 * 30)));

	// If there are offices to check
	if (!empty($offices_to_check)) {

		// Gather up the office ids to find their users real quick...
		$office_ids = array();
		foreach ($offices_to_check as $row) {
			$office_ids[] = '"' . $row[OUTLOOKS_OFFICE_ID] . '"';
		}

		// Query for user-office selections
		$users_offices_to_notify = $db->query(str_replace('?', implode(',', $office_ids), SQL_SELECT_USER_IDS_BY_OFFICE_IDS));

		// Group up the users by office selection
		$users_ids_to_notify = array();
		foreach ($users_offices_to_notify as $user_office) {
			$users_ids_to_notify[$user_office[USERS_OFFICES_OFFICE_ID]][] = $user_office[USERS_OFFICES_USER_ID];
		}

		// Prepare the data cache for existing offices
		$office_cache = array();
		foreach ($existing_office_data as $existing_office) {
			$office_cache[$existing_office[OUTLOOKS_OFFICE_ID]] = $existing_office;
		}

		// For each office returned...
		foreach ($offices_to_check as $office) {

			// Shortcut the office id
			$office_id = $office[OUTLOOKS_OFFICE_ID];

			// Prepare the outlook information
			$outlook = new outlook($office_id);
			$outlook->process_outlook();

			// Do we already have this outlook hash?
			if (!isset($office_cache[$office_id][OUTLOOKS_HASH]) || !$outlook->does_outlook_hash_match($office_cache[$office_id][OUTLOOKS_HASH])) {
				// Nope, save the outlook, and continue
				$outlook->save_outlook();

				// Do we already have this statement hash?
				if (!isset($office_cache[$office_id][STATEMENTS_HASH]) || !$outlook->does_statement_hash_match($office_cache[$office_id][STATEMENTS_HASH])) {
					// Nope, save the statement, and prepare notifications
					$outlook->save_statements();

					// For each statement found in the outlook
					foreach ($outlook->statements as $statement) {
						// If we have users for this office id (we should)
						if (!empty($users_ids_to_notify[$office_id])) {
							// Then for each user, send Direct Message
							foreach ($users_ids_to_notify[$office_id] as $user) {
								$dm_result = $connection->post('direct_messages/new', array('text' => $outlook->prepare_message($statement, $office_id), 'user_id' => $user));
								if (!empty($dm_result->errors)) {
									// Sometimes the message is the same, so Twitter won't allow dupes to be sent
									// Sometimes it's a permissions thing that changed, or bad id info.
									// Just log the count - that's all we can do right now.
									$errors++;
								}
							}
						}
					}
				}
			}
		}
	}

	if ($errors) {
		$message = "There were $errors errors posting direct messages.";
		$connection->post('direct_messages/new', array('text' => $message, 'screen_name' => "neotsn"));
	}