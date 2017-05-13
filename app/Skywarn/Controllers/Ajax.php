<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/12/2017
 * Time: 13:50
 */

namespace Skywarn\Controllers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Skywarn\Framework\Data\Mysql;
use Skywarn\Framework\Handlers\View;
use Skywarn\Framework\Logic\Location;
use Skywarn\Framework\Logic\User;
use Skywarn\Framework\Data\TwitterConnectionInfo;

class Ajax
{

    private $locationId;
    private $userId;
    private $db;

    public function __construct()
    {
        $this->locationId = View::getRequestValue('location_id', '');
        $this->userId = View::getRequestValue('user_id', '');
        $this->db = new Mysql();
    }

    public function doDisconnectService()
    {
        $user = new User($this->userId);
        $user->validateUserSession();

        // Destroy the friendship
        $t = new TwitterConnectionInfo();

        // Connect from the User's perspective...
        $connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $user->oauth_token, $user->oauth_token_secret);
        $connection->post('friendships/destroy', array('screen_name' => 'noaaalerts'));

        // Delete the User
        $this->db->delete(TABLE_USERS_LOCATIONS, array(USERS_LOCATIONS_USER_ID => $user->id)); // Cleanup Location Associations
        $this->db->delete(TABLE_USERS, array(USERS_ID => $user->id));

        unset($user);
        header('Location: /clearsessions');
    }

    public function doSaveLocation()
    {

        if ($this->locationId && $this->userId) {

            // Clear out the old one user row(s)
            $this->db->delete(TABLE_USERS_LOCATIONS, array(USERS_LOCATIONS_USER_ID => $this->userId));

            // Build the insert query
            $params = array(
                USERS_LOCATIONS_USER_ID     => $this->userId,
                USERS_LOCATIONS_LOCATION_ID => $this->locationId
            );

            $this->db->insert(TABLE_USERS_LOCATIONS, $params);
        }
    }

    public function doFollowOnTwitter()
    {
        $user = new User($this->userId);
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
            $connection->post('friendships/create', array('screen_name' => 'noaaalerts'));

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
                    'value' => $this->userId
                )
            );
            $this->db->update(TABLE_USERS, $update_pairs, $criteria_pairs);
        }

        if (!$is_following) {
            $_SESSION['msg']['error'][] = "Attempted to follow @NOAAalerts, but failed. Please try again.";
        } else {
            $_SESSION['msg']['success'][] = "You are now following @NOAAalerts on Twitter.";
            if (!$can_dm) {
                $_SESSION['msg']['info'][] = "Uh-oh! While you are a @NOAAalerts follower, we are unable to DM you. Are we blocked?";
            }
        }

        header("Location: /profile");
    }

    public function doLocationSelector()
    {
        $user = new User($this->userId);
        $user->validateUserSession();

        // Initialize
        $this->locationId = '';
        // Try for the user's selected data first...
        if ($selected_location_row = $user->getUserLocation()) {
            $state = $selected_location_row[LOCATIONS_STATE];
            $this->locationId = $selected_location_row[LOCATIONS_ID];
        } else if ($this->db->query(SQL_SELECT_FIRST_LOCATION_ROW)) {
            // No luck, get the first location row
            $result = $this->db->getNext();
            $state = $result[LOCATIONS_STATE];
            $this->locationId = $result[LOCATIONS_ID];
        } else {
            // Something went wrong...use a hardcode
            $state = 'AK';
            error_log('DB Error: Something went wrong getting locations, using hardcode. [A0SDJF]');
        }

        // Build the State Option Set
        // Put the options into the Selector template
        // Compile the main template, and output
        $templateData = array(
            'TXT_STATE'         => $state,
            'ARR_STATE_ROWS'    => Location::getStatesWithLocations(),
            'TXT_LOCATION_ID'   => $this->locationId,
            'ARR_LOCATION_ROWS' => Location::getLocationsByState($state),
        );
        echo View::returnView(T_PARTIAL_LOCATION_SELECTOR_POPUP, $templateData);
    }

    public function doLocationOptionsForState()
    {
        $state = View::getRequestValue('state', 'AK');

        echo View::returnView(T_PARTIAL_LOCATION_SELECTOR, array(
            'TXT_LOCATION_ID'   => '',
            'ARR_LOCATION_ROWS' => Location::getLocationsByState($state)
        ));
    }

}