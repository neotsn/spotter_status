<?php

/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 6/7/2015
 * Time: 8:50 PM
 */
class Location extends WebRequests
{

    public $users = array();
    public $locations = array();
    public $users_locations = array();
    public $locations_users = array();
    public $users_locations_rows = array();
    public $users_locations_to_alert = array();

    public function getOutdatedLocationsForUsers()
    {
        global $db;

        $params = array(time() - 3500);

        $results = $db->query(SQL_SELECT_ALL_OUTDATED_USERS_LOCATIONS, $params);

        $this->_prepareUsersLocations($results);
    }

    public function addUserLocationToAlert($user, array $location_data)
    {
        $this->users_locations_to_alert[$user][$location_data['key']] = $location_data;
    }

    public function emptyUsersLocationsToAlert()
    {
        $this->users_locations_to_alert = array();
    }

    public function getUserLocationRowKey($user, $location)
    {
        return $user . '_' . $location;
    }

    /**
     * @param $fips
     * @param $state
     * @param $zone
     *
     * @return array
     */
    public function getLocationByFipsStateZone($fips, $state, $zone)
    {
        global $db;

        $params = array($fips, $state, $zone);
        $results = $db->query(SQL_SELECT_LOCATION_BY_FIPS_STATE_ZONE, $params);

        if (!empty($results[0])) {
            return $results[0];
        } else {
            return array();
        }
    }

    public function getLocationById($location_id)
    {
        global $db;

        $params = array($location_id);
        $results = $db->query(SQL_SELECT_LOCATION_BY_FIPS_STATE_ZONE, $params);

        if (!empty($results[0])) {
            return $results[0];
        } else {
            return array();
        }
    }

    public function updateUserLocationRow($user_id, $user_location, $last_checked, $last_alert_time)
    {

        global $db;

        $params = array(
            USERS_LOCATIONS_USER_ID         => $user_id,
            USERS_LOCATIONS_LOCATION_ID => $user_location,
            USERS_LOCATIONS_LAST_CHECKED    => $last_checked,
            USERS_LOCATIONS_LAST_ALERT_TIME => $last_alert_time
        );

        $db->replace(TABLE_USERS_LOCATIONS, $params);
    }

    private function _prepareUsersLocations($results)
    {
        foreach ($results as $result) {

            // Add the user's id to the User list
            $this->users[$result[USERS_LOCATIONS_USER_ID]] = $result[USERS_LOCATIONS_USER_ID];

            // Add the location id to the Location list
            $location_data = $this->_buildLocationData($result);
//            $this->locations[$location_data['zone']] = $location_data['zone'];
            $this->locations[$location_data['zone']] = $location_data;

            // Associate the user with the locations
            $this->users_locations[$result[USERS_LOCATIONS_USER_ID]][$location_data['zone']] = $location_data;

            // Associate the location with the users
            $this->locations_users[$location_data['zone']][$result[USERS_LOCATIONS_USER_ID]] = $result[USERS_LOCATIONS_USER_ID];

            // Create a unique key for the user-location row and store the full row
            $result[USERS_LOCATIONS_LOCATION_ID] = $location_data;
            $this->users_locations_rows[$this->getUserLocationRowKey($result[USERS_LOCATIONS_USER_ID], $location_data['zone'])] = $result;
        }
    }

    private function _buildLocationData($row)
    {
        return array(
            'id'   => $row[LOCATIONS_ID],
            'zone' => $row[LOCATIONS_STATE] . 'Z' . $row[LOCATIONS_ZONE],
        );
    }
}