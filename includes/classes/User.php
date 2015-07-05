<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/25/2014
 * Time: 11:20 AM
 */

/**
 * Class User
 * Handles the validation and manipulation of the User object
 */
class User
{

    public $id = '';
    public $screen_name = '';
    public $oath_token = '';
    public $oauth_token_secret = '';
    public $name = '';
    public $profile_image_url = '';
    public $sid = '';
    public $sid_expire = 0;
    public $is_follower = 0;
    public $can_dm = 0;
    public $ok_to_alert = false;

    private $location = array();

    public function __construct($userid)
    {
        global $db;

        $db->query(SQL_SELECT_USER_BY_ID, array($userid));
        $row = $db->getNext();

        if (!empty($row)) {
            $this->id = $row[USERS_ID];
            $this->screen_name = $row[USERS_SCREEN_NAME];
            $this->oauth_token = $row[USERS_OAUTH_TOKEN];
            $this->oauth_token_secret = $row[USERS_OAUTH_TOKEN_SECRET];
            $this->name = $row[USERS_NAME];
            $this->profile_image_url = $row[USERS_PROFILE_IMAGE_URL_HTTPS];
            $this->sid = $row[USERS_SID];
            $this->sid_expire = $row[USERS_SID_EXPIRE];
            $this->is_follower = $row[USERS_IS_FOLLOWER];
            $this->can_dm = $row[USERS_CAN_DM];
        }
    }

    public function validateUserSession()
    {
        $session_id = get_session('sid', null);
        $is_valid = ($this->id && $this->sid_expire > 0 && $this->sid == $session_id) ? true : false;

        if (!$is_valid) {
            header('Location: ./clearsessions.php');
        }
    }

    public function getUserLocation()
    {
        global $db;

        $db->query(SQL_SELECT_ALL_USERS_LOCATIONS_BY_USER_ID, array($this->id));
        $this->location = $db->getNext();

        return $this->location;
    }

    public function getSpotterStatements()
    {
        global $db;

        return $db->query(SQL_SELECT_STATEMENTS_BY_USER_ID, array($this->id));
    }

    public function updateUserLocationRow($user_location, $last_checked, $last_alert_time, $last_statement_hash)
    {
        global $db;

        // Get the previous cache for this user's row
        $db->query(SQL_SELECT_PREVIOUS_STATEMENT_HASH_FOR_USER_ID, array($this->id));
        $results = $db->getNext();

        $this->ok_to_alert = ($results[USERS_LOCATIONS_LAST_STATEMENT_HASH] != $last_statement_hash);

        /** When we allow for multiple locations per user, we can do uncomment... */
//        $params = array(
//            USERS_LOCATIONS_USER_ID         => $user_id,
//            USERS_LOCATIONS_LOCATION_ID     => $user_location,
//            USERS_LOCATIONS_LAST_CHECKED    => $last_checked,
//            USERS_LOCATIONS_LAST_ALERT_TIME => $last_alert_time
//        );
//        $db->replace(TABLE_USERS_LOCATIONS, $params);

        /** Right now we can only allow one location per user */
        $params = array(
            USERS_LOCATIONS_LOCATION_ID         => $user_location,
            USERS_LOCATIONS_LAST_CHECKED        => $last_checked,
            USERS_LOCATIONS_LAST_ALERT_TIME     => $last_alert_time,
            USERS_LOCATIONS_LAST_STATEMENT_HASH => $last_statement_hash
        );

        $criteria = array(
            array(
                'field' => USERS_LOCATIONS_USER_ID,
                'op'    => '=',
                'value' => $this->id
            )
        );
        $db->update(TABLE_USERS_LOCATIONS, $params, $criteria);
    }
}