<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/12/2017
 * Time: 13:52
 */

namespace Skywarn\Framework\Logic;

use Abraham\TwitterOAuth\TwitterOAuth;
use Skywarn\Framework\Data\Mysql;
use Skywarn\Framework\Data\TwitterConnectionInfo;
use Skywarn\Framework\Handlers\View;

class User
{
    public $id = '';
    public $screen_name = '';
    public $oauth_token = '';
    public $oauth_token_secret = '';
    public $name = '';
    public $profile_image_url = '';
    public $sid = '';
    public $sid_expire = 0;
    public $is_follower = 0;
    public $can_dm = 0;
    public $ok_to_alert = false;

    /** @var Mysql $db */
    private $db;
    private $location = array();

    public function __construct($userid)
    {
        $this->db = new Mysql();

        $this->db->query(SQL_SELECT_USER_BY_ID, array($userid));
        $row = $this->db->getNext();

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
        $session_id = View::getSessionValue('sid', null);
        $is_valid = ($this->id && $this->sid_expire > 0 && $this->sid == $session_id) ? true : false;

        if (!$is_valid) {
            header('Location: /clearsessions');
        }
    }

    public function getUserLocation()
    {

        $this->db->query(SQL_SELECT_ALL_USERS_LOCATIONS_BY_USER_ID, array($this->id));
        $this->location = $this->db->getNext();

        return $this->location;
    }

    public function getSpotterStatements()
    {

        return $this->db->query(SQL_SELECT_STATEMENTS_BY_USER_ID, array($this->id));
    }

    public function updateUserLocationRow($user_location, $last_checked, $last_alert_time, $last_statement_hash)
    {

        // Get the previous cache for this user's row
        $this->db->query(SQL_SELECT_PREVIOUS_STATEMENT_HASH_FOR_USER_ID, array($this->id));
        $results = $this->db->getNext();

        $this->ok_to_alert = ($results[USERS_LOCATIONS_LAST_STATEMENT_HASH] != $last_statement_hash);

        /** When we allow for multiple locations per user, we can do uncomment... */
//        $params = array(
//            USERS_LOCATIONS_USER_ID         => $user_id,
//            USERS_LOCATIONS_LOCATION_ID     => $user_location,
//            USERS_LOCATIONS_LAST_CHECKED    => $last_checked,
//            USERS_LOCATIONS_LAST_ALERT_TIME => $last_alert_time
//        );
//        $this->db->replace(TABLE_USERS_LOCATIONS, $params);

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
        $this->db->update(TABLE_USERS_LOCATIONS, $params, $criteria);
    }

    /**
     * @param array|object $content
     * @param TwitterOAuth $connection
     * @param string       $oauth_token
     * @param string       $oauth_token_secret
     */
    public function updateUserOnAuthentication($content, TwitterOAuth $connection, $oauth_token, $oauth_token_secret)
    {

        $t = new TwitterConnectionInfo();

        // Compile the User's data for database entry
        $sid = sha1($oauth_token . $oauth_token_secret . time() . $content->screen_name);
        $sid_expire = time() + (86400 * 7); // 7 days from now

        // Get the relationship status
        $relation_result = $connection->get('friendships/show', array(
            'source_id' => $t->app_twitter_id,
            'target_id' => $content->id
        ));

        // Did the User want to follow and aren't already following?
        $is_following = $relation_result->relationship->source->followed_by;
        $can_dm = $relation_result->relationship->source->can_dm;

        $params = array(
            USERS_ID                      => $content->id,
            USERS_SCREEN_NAME             => $content->screen_name,
            USERS_NAME                    => $content->name,
            USERS_PROFILE_IMAGE_URL_HTTPS => $content->profile_image_url_https,
            USERS_OAUTH_TOKEN             => $oauth_token,
            USERS_OAUTH_TOKEN_SECRET      => $oauth_token_secret,
            USERS_SID                     => $sid,
            USERS_SID_EXPIRE              => $sid_expire,
            USERS_IS_FOLLOWER             => ($is_following) ? 1 : 0,
            USERS_CAN_DM                  => ($can_dm) ? 1 : 0
        );

        $this->db->replace(TABLE_USERS, $params);

        $_SESSION['sid'] = $sid;
        $_SESSION['sid_expire'] = $sid_expire;
        $_SESSION['userid'] = $content->id;
    }
}