<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 6/7/2015
 * Time: 11:01 AM
 */

/**
 * @file
 * Requests User-selected Locations that haven't been updated in 3600sec
 * Queries the AERIS API for Advisories, filtered to Outlooks, for a "State Zone" (format: XXZ000)
 * Throttles the queries to 10/minute, and prevents double querying for the same State Zone if an advisory is found
 * Stores the API Call Response, the Statement, the Advisory, the State Zone and Issued time for each request
 * Alerts the Users interested in Statements for the requested State Zone
 *
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
$l = new Location();
$a = new Advisory();

// Set up a twitter connection for the app
$connection = new TwitterOAuth($t->consumer_key, $t->consumer_secret, $t->access_token, $t->access_token_secret);

// Gather all the locations to query...
$l->getOutdatedLocationsForUsers();

// For the Free API, we can only call 10 endpoints per minute.
// This means we have to throttle our requests, so we might as well queue them up
// and do them in a way that gets the biggest bang for the buck.
$start_time = $original_start_time = time();
$delay = 60; // seconds
$limit = 10; // requests
$requestCounter = 0; // requests

$zoneCache = array(); // Record the included Zones
$locationCache = array(); // Record all db queries against the locations table

// Execute requests back to back until a limit is hit...
// If 10 request limit is hit first, reset the counter, and wait until 60 seconds limit is hit.
// If 60 seconds limit is hit first, reset the counter, and continue the iteration
// Continue until this has become a 15 minute loop, or we have run out of active locations...
$active_locations = $l->locations;
do {

    $do_increment = true;

    // If there are no more active locations, we're done...
    if (empty($active_locations)) {
        break;
    }

    // How does the request limit look...
    if ($requestCounter <= $limit) {

        // Grab the next active location to request...
        $location_data = array_shift($active_locations);

        // Pull out the zone from the location data
        $location_zone_to_request = $location_data['zone'];

        // If we haven't already cached this one, let's do it...
        // If we did cache it, then we've already alerted the user as well
        if (!isset($zoneCache[$location_zone_to_request])) {
            // Production API calls
            $a->addRequest(API_GET_OUTLOOK_BY_ZONE, $location_zone_to_request);
            $a->prepareSingleUrl();
            $a->executeRequest();

            // Dev pull cache calls
//            $a->getLastCachedResponse();

            // The response is limited to 1, so it's an array of 1 element...
            foreach ($a->data as $response_data) {

                // Extract the Statement and the full report
                $statement = $a->extractSpotterStatement($response_data->details->body);
                $advisory = $response_data->details->bodyFull;

                // Start adding the Included Zones to the Zone Cache, so we don't request them again...
                foreach ($response_data->includes->wxzones as $included_zone) {

                    // Put it in the cache
                    $zoneCache[$included_zone] = 1;

                    // Are any users trying to get this zone's statement?
                    if (!empty($l->locations_users[$included_zone])) {

                        // Add the user to the alert queue...
                        foreach ($l->locations_users[$included_zone] as $user_id) {

                            // Get the key to pull their user_location row from the cache...
                            $user_row_key = $l->getUserLocationRowKey($user_id, $included_zone);

                            // When was their last advisory issued?
                            $last_alert_time = $l->users_locations_rows[$user_row_key][USERS_LOCATIONS_LAST_ALERT_TIME];

                            // Add them to the queue, if this advisory issued time is different from their last issued time
                            if ($last_alert_time != $response_data->timestamps->issued) {

                                // Pull their location data to pass in for use later
                                $user_location_data = $l->users_locations_rows[$user_row_key][USERS_LOCATIONS_LOCATION];

                                // Add to the queue
                                $l->addUserLocationToAlert($user_id, $user_location_data);
                            }
                        }
                    }
                }

                // Write the reports to the database before alerting the users...in case we do a self-hosted advisory display
                $a->updateAdvisoriesCache($zoneCache, $advisory, $statement, $response_data->timestamps->issued);

                // We're done iterating Included Zones for this API Response...
                // Did we have any Users to alert in the queue?
                if (!empty($l->users_locations_to_alert)) {

                    // Iterate over the each user in the queue...pull their User id
                    foreach ($l->users_locations_to_alert as $user_id => $user_data) {

                        // Iterate over each user's locations to alert for...
                        foreach ($user_data as $user_location_data) {

                            // Now we have in $user_location_data:
                            // - key => used for DB query against users_locations
                            // - fips => for specificity in key to get proper city name
                            // - zone => for specificity in key and used in api request

                            // Grab the location row from the db if we don't have it cached...
                            if (!isset($locationCache[$user_location_data['key']])) {
                                $temp = explode('|', $user_location_data['key']);

                                // Assignment as bool
                                if ($location_results = $l->getLocationByFipsStateZone($temp[0], $temp[1], $temp[2])) {
                                    $locationCache[$user_location_data['key']] = $location_results;
                                }
                                unset($temp);
                            }

                            // If we know about this location, we can alert about it...
                            if (!empty($locationCache[$user_location_data['key']])) {

                                // Update the users_locations row for this user-location
                                $l->updateUserLocationRow($user_id, $user_location_data['key'], time(), $response_data->timestamps->issued);

                                // Helper variables for code neatness
                                $area = $locationCache[$user_location_data['key']][LOCATIONS_NAME];
                                $state = $locationCache[$user_location_data['key']][LOCATIONS_STATE];
                                $cwa = $locationCache[$user_location_data['key']][LOCATIONS_CWA];

                                // Compose the twitter Direct Message content, with url
                                $twitter_message = $a->prepareTwitterMessage($statement, $area, $state, $cwa);

//                                /*
                                // Send the DM
                                $dm_result = $connection->post('direct_messages/new', array('text' => $twitter_message, 'user_id' => $user_id));

                                // Error handling...
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
                                            $cleanup_user_ids[$user_id] = $user_id;
                                        }
                                    }
                                    $err_msg = array(
                                        'userid'    => $user_id,
                                        'messages'  => $msgs,
                                        'statement' => $statement,
                                        'location'  => $user_location_data['key']
                                    );

                                    error_log('Twitter DM Error (' . time() . ') : ' . json_encode($err_msg));
                                }
//                                */
                            }
                        }
                    }
                }

                // Clean up time for this iteration...
                $l->emptyUsersLocationsToAlert(); // Dump the user-location alert queue
            }
        } else {
            // We skipped this one, don't increment anything
            // Doing nothing is good for our limit requirements
            $do_increment = false;
        }
    }

    // If we didn't straight up skip the whole thing, check the limits...
    if ($do_increment) {
        // Capture the current time...
        $current_time = time();

        // Howe are we doing on time from when we started?
        if ($current_time - $start_time >= $delay) {

            // We took longer than the delay period, reset the counter and the start time
            $requestCounter = 0;
            $start_time += $delay;
        } else if ($current_time - $start_time < $delay) {
            // We still have time left, so our request limit is still in effect. Bump it one time
            $requestCounter++;
        }
    }

    // As long as we're under 15 minutes from our original start time, keep going
    // We'll also break out if we get done early.
} while (time() - $original_start_time < 900);

// Do we have any active locations left? We shouldn't...
if (!empty($active_locations)) {
    // Oops, our loop took longer than 15 minutes, and we still had more work to do.
    $message = 'I left the loop with ' . count($active_locations) . ' left to process.';
    error_log($message);

    // Let the dev know
    $connection->post('direct_messages/new', array('text' => $message, 'screen_name' => "neotsn"));
}

// Reporting to the Dev...
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