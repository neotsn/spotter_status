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

    private $locations = array();

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

    public function getUsersLocations()
    {
        global $db;

        $results = $db->query(SQL_SELECT_ALL_USERS_LOCATIONS_BY_USER_ID, array($this->id));

        $this->locations = array();
        foreach ($results as $result) {
            $this->locations[$result[LOCATIONS_ID]] = $result;
        }

        return $this->locations;
    }

    public function getSpotterStatements()
    {
        global $db;

        return $db->query(SQL_SELECT_STATEMENTS_BY_USER_ID, array($this->id));
    }
}