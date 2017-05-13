<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/12/2017
 * Time: 13:11
 */

namespace Skywarn\Controllers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Skywarn\Framework\Data\TwitterConnectionInfo;
use Skywarn\Framework\Handlers\View;
use Skywarn\Framework\Logic\User;

class Root
{
    public function doIndex()
    {
        $session_id = View::getSessionValue('sid', null);
        $user_id = View::getSessionValue('userid', null);
        // $msg = View::getRequestValue('msg', null);

        if ($user_id && $session_id) {
            $user = new User($user_id);
            $user->validateUserSession();
            header('Location: /profile');
        }

        echo View::returnView(T_SPLASH);
    }

    public function doClearSessions()
    {
        session_start();
        session_destroy();

        /* Redirect to page with the connect to Twitter option. */
        header('Location: ./index.php');
    }

    public function doCallback()
    {

        $requestOauthToken = View::getRequestValue('oauth_token', null);
        $sessionOauthToken = View::getSessionValue('oauth_token');

        if (!is_null($requestOauthToken) && $requestOauthToken !== $sessionOauthToken) {
            $_SESSION['oauth_status'] = 'oldtoken';
            header('Location: /clearsessions');
        }

        $t = new TwitterConnectionInfo();

        // Create TwitteroAuth object with app key/secret and token key/secret from default phase
        $connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

        // Request access tokens from twitter
        $access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));

        /* Save the access tokens. Normally these would be saved in a database for future use. */
        /* Remove no longer needed request tokens */
        unset($_SESSION['oauth_token']);
        unset($_SESSION['oauth_token_secret']);

        // If HTTP response is 200 continue otherwise send to connect page to retry
        if (200 == $connection->getLastHttpCode()) {
            // The User has been verified and the access tokens can be saved for future use

            $_SESSION['access_token'] = $access_token;
            $_SESSION['status'] = 'verified';
            header('Location: /setup');
        } else {
            // Save HTTP status for error dialog on connnect page.*/
            header('Location: /clearsessions');
        }
    }

    public function doProfile()
    {

        $user_id = View::getSessionValue('userid', null);

        $user = new User($user_id);
        $user->validateUserSession();

        // Get the User's subscribed offices
        $location_row = $user->getUserLocation();
        $spotter_statements = $user->getSpotterStatements();

        // Set some boolean checks
        $hasLocations = !empty($location_row);
        $hasForecastCards = !empty($spotter_statements);

        // Make the Spotter Statement Cards
        $forecast_cards_html = $profile_location_html = $messages_html = '';

        if ($hasForecastCards) {
            foreach ($spotter_statements as $statement) {
                $issueDateTime = new \DateTime('@' . $statement[ADVISORIES_ISSUED_TIME], new \DateTimeZone('America\Chicago'));

                $forecast_cards_html .= View::returnView(T_PARTIAL_FORECAST_CARD, array(
                    'TXT_STATEMENT_OFFICE'    => strtoupper($statement[LOCATIONS_CWA]),
                    'TXT_STATEMENT_FIPS'      => $statement[LOCATIONS_FIPS],
                    'TXT_STATEMENT_CITY'      => $statement[LOCATIONS_NAME],
                    'TXT_STATEMENT_STATE'     => $statement[LOCATIONS_STATE],
                    'TXT_STATEMENT_TIMESTAMP' => $issueDateTime->format('Y-m-d H:i:s O'),
                    'TXT_STATEMENT_MESSAGE'   => ucfirst(strtolower(str_replace('|', "<br />", $statement[ADVISORIES_STATEMENT]))),
                    'TXT_STATEMENT_ADVISORY'  => $statement[ADVISORIES_ADVISORY]
                ));
            }
        }

        if ($hasLocations) {
            // TODO - Turn this into a FOREACH if multiple locaitons are allowed
            $profile_location_html .= View::returnView(T_PARTIAL_PROFILE_LOCATION, array(
                'TXT_USER_ID'         => $user_id,
                'TXT_LOCATION_COUNTY' => $location_row[LOCATIONS_COUNTY],
                'TXT_LOCATION_STATE'  => $location_row[LOCATIONS_STATE]
            ));
        }

        $dm_class = (!$user->can_dm) ? "profile_conn_bad" : "profile_conn_ok";

        if ($user->is_follower) {
            $follow_class = "profile_conn_ok";
        } else if (!$user->can_dm) {
            $follow_class = "profile_conn_bad";
        } else {
            $follow_class = "profile_conn_warn";
        }

        // Get the error-handling messages
        $sessionMessages = View::getSessionValue('msg', array());
        foreach ($sessionMessages as $type => $sessionMessage) {

            $messages_html .= View::returnView(T_PARTIAL_MESSAGE, array(
                'TXT_TYPE' => $type,
                'TXT_MSG'  => ucwords($type) . ': ' . $sessionMessage
            ));
        }
        unset($_SESSION['msg']);

        View::returnPage(T_PROFILE, array(
            'IMG_USER_PROFILE'           => $user->profile_image_url,
            'TXT_USER_SCREEN_NAME'       => $user->screen_name,
            'TXT_LOGOUT_USER'            => 'Logout',
            'TXT_SPOTTER_FORECAST_CARDS' => $forecast_cards_html,
            'TXT_PROFILE_LOCATION'       => $profile_location_html,
            'TXT_RELATIONSHIP_STATUS'    => $follow_class,
            'TXT_DM_STATUS'              => $dm_class,
            'TXT_USER_ID'                => $user->id,
            'TXT_MSGS'                   => $messages_html,
            'B_HAS_RELATIONSHIP'         => $user->is_follower,
            'B_HAS_OFFICES'              => $hasLocations,
            'B_HAS_FORECAST_CARDS'       => $hasForecastCards
        ));
    }

    public function doRedirect()
    {
        $t = new TwitterConnectionInfo();

        // Get temporary credentials
        $connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret);

        /** @var array $request_token */
        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $t->oauth_callback));

        // Store temporary credentials to session
        $_SESSION['oauth_token'] = $token = View::getArrayValue($request_token, 'oauth_token', '');
        $_SESSION['oauth_token_secret'] = View::getArrayValue($request_token, 'oauth_token_secret', '');

        /* If last connection failed don't display authorization link. */
        switch ($connection->getLastHttpCode()) {
            case 200:
                /* Build authorize URL and redirect User to Twitter. */
                $url = $connection->url('oauth/authorize', array('oauth_token' => $token));
                header('Location: ' . $url);
                break;
            default:
                /* Show notification if something went wrong. */
                echo 'Could not connect to Twitter. Refresh the page or try again later.';
                exit;
        }
    }

    public function doSetup()
    {
        $access_token = View::getSessionValue('access_token', array());
        $oauth_token = View::getArrayValue($access_token, 'oauth_token');
        $oauth_token_secret = View::getArrayValue($access_token, 'oauth_token_secret');

        // If access tokens are not available redirect to connect page.
        if (!$access_token || !$oauth_token || !$oauth_token_secret) {
            // Lost the access token in session somehow, destroy and start again
            header('Location: /clearsessions');
        }

        // We don't have a valid session, so we need to set it up again.
        $t = new TwitterConnectionInfo();

        /* Create a TwitterOauth object with consumer/User tokens. */
        $connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $oauth_token, $oauth_token_secret);

        /* If method is set change API call made. Test is called by default. */
        $content = $connection->get('account/verify_credentials');

        // Update the User's database entry with the new info
        $user = new User($content->id);
        $user->updateUserOnAuthentication($content, $connection, $oauth_token, $oauth_token_secret);

        // Send back to index for configuration options with session set
        header('Location: /profile');
    }
}