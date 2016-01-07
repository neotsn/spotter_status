<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/18/2014
 * Time: 6:29 PM
 */

session_start();
define('PATH_ROOT', dirname(__FILE__));
require_once(PATH_ROOT . '/config.php');

// Get the request vars
$mode = get_request('mode', '');
$submode = get_request('submode', '');
$office = get_request('nws_office', '');
$user_id = get_request('user_id', '');
$offices = get_request('offices', array());

$db = new DbPdo();

// Toggle the mode
switch ($mode) {
    case 'disconnectService':
        $user = new User($user_id);
        $user->validateUserSession();

        // Destroy the friendship
        $t = new TwitterConnectionInfo();

        // Connect from the User's perspective...
        $connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $user->oauth_token, $user->oauth_token_secret);
        $connection->post('friendships/destroy', array('screen_name' => 'noaaalerts'));

        // Delete the User
        $db->delete(TABLE_USERS_LOCATIONS, array(USERS_LOCATIONS_USER_ID => $user->id)); // Cleanup Location Associations
        $db->delete(TABLE_USERS, array(USERS_ID => $user->id));

        unset($user);
        header('Location: ./clearsessions.php');
        break;
    case 'followOnTwitter':
        $user = new User($user_id);
        $user->validateUserSession();

        // Start destroying the friendship
        $t = new TwitterConnectionInfo();

        // Connect from the User's perspective...
        $connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $user->oauth_token, $user->oauth_token_secret);

        // Get the relationship status
        $relation_result = $connection->get('friendships/show', array('source_id' => $t->app_twitter_id, 'target_id' => $user->id));

        // Did the User want to follow and aren't already following?
        $is_following = $relation_result->relationship->source->followed_by;
        $can_dm = $relation_result->relationship->source->can_dm;
        if (!$is_following) {
            // Create the follow on twitter's side
            $friend_result = $connection->post('friendships/create', array('screen_name' => 'noaaalerts'));

            // Run the relationship check again to confirm
            $relation_result = $connection->get('friendships/show', array('source_id' => $t->app_twitter_id, 'target_id' => $user->id));

            // Bool helpers
            $is_following = ($relation_result->relationship->source->followed_by) ? 1 : 0;
            $can_dm = ($relation_result->relationship->source->can_dm) ? 1 : 0;

            // Update the follower status in the database
            $update_pairs = array(
                USERS_IS_FOLLOWER => $is_following,
                USERS_CAN_DM      => $can_dm
            );
            $criteria_pairs = array(
                array(
                    'field' => USERS_ID,
                    'op'    => '=',
                    'value' => $user_id
                )
            );
            $db->update(TABLE_USERS, $update_pairs, $criteria_pairs);
        }

        if (!$is_following) {
            $_SESSION['msg']['error'][] = "Attempted to follow @NOAAalerts, but failed. Please try again.";
        } else {
            $_SESSION['msg']['success'][] = "You are now following @NOAAalerts on Twitter.";
            if (!$can_dm) {
                $_SESSION['msg']['info'][] = "Uh-oh! While you are a @NOAAalerts follower, we are unable to DM you. Are we blocked?";
            }
        }

        header("Location: ./profile.php");
        break;
    case 'saveLocation':

        $location_id = get_request('location_id', '');
        $user_id = get_request('user_id', '');

        if ($location_id && $user_id) {

            // Clear out the old one user row(s)
            $db->delete(TABLE_USERS_LOCATIONS, array(USERS_LOCATIONS_USER_ID => $user_id));

            // Build the insert query
            $params = array(
                USERS_LOCATIONS_USER_ID     => $user_id,
                USERS_LOCATIONS_LOCATION_ID => $location_id
            );

            $db->insert(TABLE_USERS_LOCATIONS, $params);
        }
        break;
    case 'getLocationSelector':
        $user = new User($user_id);
        $user->validateUserSession();

        // Initialize
        $location_id = '';
        $selected_location_rows = array();
        // Try for the user's selected data first...
        if ($selected_location_row = $user->getUserLocation()) {
            $state = $selected_location_row[LOCATIONS_STATE];
            $location_id = $selected_location_row[LOCATIONS_ID];
        } else if ($db->query(SQL_SELECT_FIRST_LOCATION_ROW)) {
            // No luck, get the first location row
            $result = $db->getNext();
            $state = $result[LOCATIONS_STATE];
            $location_id = $result[LOCATIONS_ID];
        } else {
            // Something went wrong...use a hardcode
            $state = 'AK';
            error_log('DB Error: Something went wrong getting locations, using hardcode. [A0SDJF]');
        }

        // Build the State Option Set
        $state_rows = $db->query(SQL_SELECT_STATES_FROM_LOCATIONS);

        $state_option_html = '';
        $state_option_template = new Template('state_option', false, false);
        foreach ($state_rows as $state_row) {

            $state_option_template->setTemplateVars(array(
                'TXT_LOCATION_STATE' => $state_row[LOCATIONS_STATE],
                'TXT_IS_SELECTED'    => ($state_row[LOCATIONS_STATE] == $state) ? ' selected ' : ''
            ));
            $state_option_html .= $state_option_template->compile();
            $state_option_template->reset_template();
        }

        // Put the options into the Selector template
        $state_selector_template = new Template('state_selector', false, false);
        $state_selector_template->setTemplateVars(array(
            'I_STATE_OPTIONS' => $state_option_html
        ));

        // Compile the main template, and output
        $template = new Template('location_selector_popup', false, false);
        $template->setTemplateVars(array(
            'I_STATE_SELECTOR'    => $state_selector_template->compile(),
            'I_LOCATION_SELECTOR' => compileLocationSelectTemplate($state, $location_id)
        ));
        $template->display();

        break;

    case 'getLocationOptionsForState':
        $state = get_request('state', 'AK');
        echo compileLocationSelectTemplate($state);
        break;
}

function compileLocationSelectTemplate($state, $location_id = '')
{
    global $db;

    $state = $state ?: 'AK';

    // Build the Location Selector for the selected state
    $state_locations = $db->query(SQL_SELECT_ALL_LOCATIONS_BY_STATE, array($state));

    $location_option_html = '';
    $location_option_template = new Template('location_option', false, false);
    foreach ($state_locations as $location_row) {

        $location_option_template->setTemplateVars(array(
            'TXT_LOCATION_ID'     => $location_row[LOCATIONS_ID],
            'TXT_LOCATION_NAME'   => ($location_row[LOCATIONS_NAME] != $location_row[LOCATIONS_COUNTY] && $location_row[LOCATIONS_NAME]) ? $location_row[LOCATIONS_NAME] . ' - ' : '',
            'TXT_LOCATION_COUNTY' => $location_row[LOCATIONS_COUNTY],
            'TXT_IS_SELECTED'     => ($location_row[LOCATIONS_ID] == $location_id) ? ' selected ' : ''
        ));
        $location_option_html .= $location_option_template->compile();
        $location_option_template->reset_template();
    }

    // Put the options into the Selector template
    $location_selector_template = new Template('location_selector', false, false);
    $location_selector_template->setTemplateVars(array(
        'I_LOCATION_OPTIONS' => $location_option_html
    ));

    return $location_selector_template->compile();
}