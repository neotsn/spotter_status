<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/25/2014
 * Time: 11:36 AM
 */

/**
 * @file
 * Make User's settings visible in a single location
 */

session_start();
define('PATH_ROOT', './');
require_once(PATH_ROOT . 'config.php');

$user_id = get_session('userid', null);

$db = new DbPdo();
$user = new User($user_id);
$user->validateUserSession();

// Get the User's subscribed offices
$location_row = $user->getUserLocation();
$spotter_statements = $user->getSpotterStatements();

// Make the Spotter Statement Cards
$forecast_cards_html = '';
$messages_html = '';
$profile_location_html = '';

$b_has_forecast_cards = !empty($spotter_statements) ? true : false;
if ($b_has_forecast_cards) {
    $spotter_template = new Template('forecast_card', false, false);
    foreach ($spotter_statements as $statement) {
        $spotter_template->setTemplateVars(array(
            'TXT_STATEMENT_OFFICE'   => strtoupper($statement[LOCATIONS_CWA]),
            'TXT_STATEMENT_FIPS'     => $statement[LOCATIONS_FIPS],
            'TXT_STATEMENT_CITY'      => $statement[LOCATIONS_NAME],
            'TXT_STATEMENT_STATE'     => $statement[LOCATIONS_STATE],
            'TXT_STATEMENT_TIMESTAMP' => date('Y-m-d H:i:s O', $statement[ADVISORIES_ISSUED_TIME]),
            'TXT_STATEMENT_MESSAGE'  => ucfirst(strtolower(str_replace('|', "<br />", $statement[ADVISORIES_STATEMENT]))),
            'TXT_STATEMENT_ADVISORY' => $statement[ADVISORIES_ADVISORY]
        ));
        $forecast_cards_html .= $spotter_template->compile();
        $spotter_template->reset_template();
    }
}

// Gather the Subscribed Offices
$b_has_locations = !empty($location_row) ? true : false;
if ($b_has_locations) {

    $profile_location_template = new Template('profile_location', false, false);
    $profile_location_template->setTemplateVars(array(
        'TXT_USER_ID'         => $user_id,
        'TXT_LOCATION_COUNTY' => $location_row[LOCATIONS_COUNTY],
        'TXT_LOCATION_STATE'  => $location_row[LOCATIONS_STATE]
    ));
    $profile_location_html = $profile_location_template->compile();
}

//	$connection_errors = array();
$dm_class = "profile_conn_ok";
$follow_class = "profile_conn_ok";
if (!$user->can_dm) {
    $dm_class = "profile_conn_bad";
}

if (!$user->is_follower) {
    if (!$user->can_dm) {
        $follow_class = "profile_conn_bad";
    } else {
        $follow_class = "profile_conn_warn";
    }
}

// Get the error-handling messages
if (!empty($_SESSION['msg'])) {
    foreach ($_SESSION['msg'] as $type => $msgs) {
        $msg_template = new Template('msg', false, false);

        foreach ($msgs as $msg) {
            $msg_template->setTemplateVars(array(
                'TXT_TYPE' => $type,
                'TXT_MSG'  => ucwords($type) . ': ' . $msg
            ));
            $messages_html .= $msg_template->compile();
            $msg_template->reset_template();
        }
    }
    unset($_SESSION['msg']);
}

$template = new Template('profile');
$template->setTemplateVars(array(
    'IMG_USER_PROFILE'           => $user->profile_image_url,
    'TXT_USER_SCREEN_NAME'       => $user->screen_name,
    'TXT_LOGOUT_USER'            => 'Logout',
    'TXT_SPOTTER_FORECAST_CARDS' => $forecast_cards_html,
    'TXT_PROFILE_LOCATION' => $profile_location_html,
    'TXT_RELATIONSHIP_STATUS'    => $follow_class,
    'TXT_DM_STATUS'              => $dm_class,
    'TXT_USER_ID'                => $user->id,
    'TXT_MSGS'                   => $messages_html,
    'B_NO_RELATIONSHIP'          => !$user->is_follower,
    'B_HAS_RELATIONSHIP'   => $user->is_follower,
    'B_HAS_OFFICES'        => $b_has_locations,
    'B_HAS_FORECAST_CARDS' => $b_has_forecast_cards
));
$template->display();
