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
 *        Check if consumer token is set and if so send User to get a request token.
 */
define('PATH_ROOT', './');
require_once(PATH_ROOT . 'config.php');

// Initialize Variables
$errors = 0;
$cleanup_user_ids = array();

// Initialize Objects
$db = new DbPdo();
$t = new TwitterConnectionInfo();

// Set up a twitter connection for the app
$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $t->access_token, $t->access_token_secret);

// Gather all selected office ids that are out of date, or new
// Returns OUTLOOK_OFFICE_ID
$offices_to_check = $db->query(SQL_SELECT_ALL_FROM_OUTDATED_ACTIVE_CRON_OFFICES, array(time() - (60 * 30)));

// If there are offices to check
if (!empty($offices_to_check)) {

    // Initialize
    $office_ids = array();
    $existing_office_data_cache = array();
    $offices_with_users_to_notify = array();

    // Prepare the list of out-dated offices that users may have chosen...
    foreach ($offices_to_check as $row) {
        $office_ids[] = '"' . $row[OUTLOOKS_OFFICE_ID] . '"';
    }

    // Look for users who have selected an out-dated office, and get those pairings...
    $user_office_selections = $db->query(str_replace('?', implode(',', $office_ids),
        SQL_SELECT_USER_IDS_BY_OFFICE_IDS));

    // Group the users by office id...
    foreach ($user_office_selections as $row) {
        $offices_with_users_to_notify[$row[USERS_OFFICES_OFFICE_ID]][] = $row[USERS_OFFICES_USER_ID];
    }

    // Prepare the data cache for existing offices
    // Gather existing data for offices (last Outlook & statement hashes)
    // Returned fields: OUTLOOKS_OFFICE_ID, OUTLOOKS_HASH, STATEMENTS_HASH
    $existing_office_data = $db->query(SQL_SELECT_EXISTING_ACTIVE_CRON_OFFICE_DATA, array(time() - (60 * 30)));
    foreach ($existing_office_data as $existing_office) {
        $existing_office_data_cache[$existing_office[OUTLOOKS_OFFICE_ID]] = $existing_office;
    }

    // For each office returned...
    foreach ($offices_to_check as $office) {

        // Shortcut the office id
        $office_id = $office[OUTLOOKS_OFFICE_ID];

        // Prepare the Outlook information
        $outlook = new Outlook($office_id);
        $outlook->processOutlook();

        // Do we already have this Outlook hash? If we do, does the hash match this one?
        if (!isset($existing_office_data_cache[$office_id][OUTLOOKS_HASH]) || !$outlook->doesOutlookHashMatch($existing_office_data_cache[$office_id][OUTLOOKS_HASH])) {
            // Nope, save the Outlook, and continue
            $outlook->saveOutlook();

            // Do we already have this statement hash? If we do, does the hash match this one?
            if (!isset($existing_office_data_cache[$office_id][STATEMENTS_HASH]) || !$outlook->doesStatementHashMatch($existing_office_data_cache[$office_id][STATEMENTS_HASH])) {
                // Nope, save the statement, and prepare notifications
                $outlook->saveStatements();

                // For each statement found in the Outlook
                foreach ($outlook->statements as $statement) {
                    // If we have users for this office id (we should)
                    if (!empty($offices_with_users_to_notify[$office_id])) {
                        // Then for each User, send Direct Message
                        foreach ($offices_with_users_to_notify[$office_id] as $user) {
                            $dm_result = $connection->post('direct_messages/new',
                                array('text' => $outlook->prepareMessage($statement, $office_id), 'user_id' => $user));
                            if (!empty($dm_result->errors)) {
                                // Sometimes the message is the same, so Twitter won't allow dupes to be sent
                                // Sometimes it's a permissions thing that changed, or bad id info.
                                // Gather the error codes and messages and associate to userid; drop that into error_log
                                // Then send dev a message with error count notification.
                                $errors++;

                                $msgs = array();
                                foreach ($dm_result->errors as $err) {
                                    $msgs[] = $err->code . ': ' . $err->message;

                                    if (in_array($err->code, array(150))) {
                                        $cleanup_user_ids[$user] = $user;
                                    }
                                }
                                $err_msg = array(
                                    'userid' => $user,
                                    'messages'  => $msgs,
                                    'statement' => $statement,
                                    'office'    => $office_id
                                );

                                error_log('Twitter DM Error (' . time() . ') : ' . json_encode($err_msg));
                            }
                        }
                    }
                }
            }
        }
    }
}

if ($errors) {
//    $message = "There were $errors errors posting direct messages.";
//    $connection->post('direct_messages/new', array('text' => $message, 'screen_name' => "neotsn"));

    if (!empty($cleanup_user_ids)) {
        foreach ($cleanup_user_ids as $remove_user_id) {
            $db->delete(TABLE_USERS, array(USERS_ID => $remove_user_id));
            $db->delete(TABLE_USERS_OFFICES, array(USERS_OFFICES_USER_ID => $remove_user_id));
        }

        $message = "Removed " . count($cleanup_user_ids) . " users who unfollowed.";
        $connection->post('direct_messages/new', array('text' => $message, 'screen_name' => "neotsn"));
    }
}