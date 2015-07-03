<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/18/2014
 * Time: 3:43 PM
 */

// System
define('VERSION', '1.3.3');

// PATHS
define('PATH_INCLUDES', PATH_ROOT . 'includes/');
define('PATH_TEMPLATES', PATH_ROOT . 'templates/');
define('PATH_CSS', PATH_ROOT . 'css/');
define('PATH_JS', PATH_ROOT . 'js/');
define('PATH_CLASSES', PATH_ROOT . 'includes/classes/');
define('PATH_CONNECTION_INFO', '../../_private/connection_info.ini');

// Tables
define('TABLE_ADVISORIES', 'advisories');
define('TABLE_API_CALLS', 'api_calls');
define('TABLE_CRON_OFFICE_CHECK', 'cron_office_check');
define('TABLE_LOCATIONS', 'locations');
define('TABLE_OFFICES', 'offices');
define('TABLE_STATEMENTS', 'statements');
define('TABLE_USERS', 'users');
define('TABLE_USERS_OFFICES', 'users_offices');
define('TABLE_USERS_LOCATIONS', 'users_locations');

// Advisories Table
define('ADVISORIES_STATE_ZONE', 'state_zone');
define('ADVISORIES_ISSUED_TIME', 'issued_time');
define('ADVISORIES_STATEMENT', 'statement');
define('ADVISORIES_ADVISORY', 'advisory');

// Cron_Offices Table
define('CRON_OFFICE_ID', 'office_id');

// Locations Table
define('LOCATIONS_ID', 'name');
define('LOCATIONS_STATE', 'state');
define('LOCATIONS_ZONE', 'zone');
define('LOCATIONS_CWA', 'cwa');
define('LOCATIONS_NAME', 'name');
define('LOCATIONS_STATE_ZONE', 'state_zone');
define('LOCATIONS_COUNTY', 'county');
define('LOCATIONS_FIPS', 'fips');
define('LOCATIONS_TIMEZONE', 'timezone');
define('LOCATIONS_FE_AREA', 'fe_area');
define('LOCATIONS_LAT', 'lat');
define('LOCATIONS_LON', 'lon');

// Offices Table
define('OFFICES_ID', 'id');
define('OFFICES_CITY', 'city');
define('OFFICES_STATE', 'state');

// Statements Table
define('STATEMENTS_OFFICE_ID', 'office_id');
define('STATEMENTS_MESSAGE', 'spotter_message');
define('STATEMENTS_LAST_OUTLOOK', 'last_outlook');

// Users Table
define('USERS_ID', 'id');
define('USERS_NAME', 'name');
define('USERS_PROFILE_IMAGE_URL_HTTPS', 'profile_image_url_https');
define('USERS_OAUTH_TOKEN', 'oauth_token');
define('USERS_OAUTH_TOKEN_SECRET', 'oauth_token_secret');
define('USERS_SID', 'sid');
define('USERS_SID_EXPIRE', 'sid_expire');
define('USERS_SCREEN_NAME', 'screen_name');
define('USERS_IS_FOLLOWER', 'is_follower');
define('USERS_CAN_DM', 'can_dm');

// Users_Offices Table
define('USERS_OFFICES_USER_ID', 'user_id');
define('USERS_OFFICES_OFFICE_ID', 'office_id');

// Users_Locations Table
define('USERS_LOCATIONS_USER_ID', 'user_id');
define('USERS_LOCATIONS_LOCATION_ID', 'location_id');
define('USERS_LOCATIONS_LAST_CHECKED', 'last_checked');
define('USERS_LOCATIONS_LAST_ALERT_TIME', 'last_alert_time');

// New Queries
define('SQL_SELECT_ALL_OUTDATED_USERS_LOCATIONS', "SELECT * FROM " . TABLE_USERS_LOCATIONS . " ul LEFT JOIN " . TABLE_LOCATIONS . " l on ul." . USERS_LOCATIONS_LOCATION_ID . " = l." . LOCATIONS_ID . " WHERE ul." . USERS_LOCATIONS_LAST_CHECKED . "<=?");
define('SQL_SELECT_LOCATION_BY_FIPS_STATE_ZONE', "SELECT * FROM " . TABLE_LOCATIONS . " WHERE " . LOCATIONS_FIPS . "=? AND " . LOCATIONS_STATE . "=? AND " . LOCATIONS_ZONE . "=?");
define('SQL_SELECT_LOCATION_BY_ID', "SELECT * FROM " . TABLE_LOCATIONS . " WHERE " . LOCATIONS_ID . "=?");
define('SQL_SELECT_ALL_USERS_LOCATIONS_BY_USER_ID', "SELECT * FROM " . TABLE_USERS_LOCATIONS . " WHERE " . USERS_LOCATIONS_USER_ID . "=?");

// Queries
define('SQL_SELECT_ALL_FROM_OFFICES', "SELECT * FROM " . TABLE_OFFICES . " ORDER BY " . OFFICES_STATE . " ASC, " . OFFICES_CITY . " ASC");
define('SQL_SELECT_ALL_FROM_OFFICES_BY_USER_ID', "SELECT * FROM " . TABLE_OFFICES . " o, " . TABLE_USERS_OFFICES . " uo WHERE o." . OFFICES_ID . "=uo." . USERS_OFFICES_OFFICE_ID . " AND uo." . USERS_OFFICES_USER_ID . "= ?");
define('SQL_SELECT_ALL_STATEMENTS_FOR_USER_ID', "SELECT s.*, o.* FROM " . TABLE_STATEMENTS . " s, " . TABLE_USERS_OFFICES . " uo, " . TABLE_OFFICES . " o WHERE s." . STATEMENTS_OFFICE_ID . "=uo." . USERS_OFFICES_OFFICE_ID . " AND uo." . USERS_OFFICES_OFFICE_ID . "=o." . OFFICES_ID . " AND uo." . USERS_OFFICES_USER_ID . "= ?");
define('SQL_SELECT_USER_BY_ID', "SELECT * FROM " . TABLE_USERS . " WHERE " . USERS_ID . "=?");
define('SQL_SELECT_OFFICE_IDS_BY_USER_ID', "SELECT * FROM " . TABLE_USERS_OFFICES . " WHERE " . USERS_OFFICES_USER_ID . "= ?");

// Modify
define('SQL_CREATE_TABLE_GENERIC', "CREATE TABLE IF NOT EXISTS %tn (%ct)");
define('SQL_INSERT_GENERIC', "INSERT INTO %t (%c) VALUES (%v)");
define('SQL_REPLACE_GENERIC', "REPLACE INTO %t (%c) VALUES (%v)");
define('SQL_DELETE_GENERIC', "DELETE IGNORE FROM %t WHERE %c");
define('SQL_UPDATE_GENERIC', "UPDATE %t SET %ufv WHERE %cfv");
define('SQL_DROP_TABLE_GENERIC', "DROP TABLE IF EXISTS %tn ");

// REGEX
define('REGEX_TEMPLATE_INCLUDE', '/<!-- INCLUDE (.+?) -->/ms');
define('REGEX_TEMPLATE_CONDITIONS', '/<!-- IF ([{\w}]+)? -->(.+?)(?:<!-- ELSE -->(.+?))?<!-- ENDIF -->/ms');
define('REGEX_SPOTTER_STATEMENT', '/\.SPOTTER INFORMATION STATEMENT\.\.\.[\s]+?([\S\s]*)$/');

// URLs
define('URL_AERIS_DOMAIN', 'https://api.aerisapi.com%e');
define('URL_PIZZY_ADVISORY_BODY', 'https://thepizzy.net/labs/spotter/showAdvisory.php?zone=%z');
define('URL_NWS_ADVISORY', 'http://forecast.weather.gov/product.php?site=%o&issuedby=%o&product=HWO&version=1&glossary=0');

// API End Points
define('API_BATCH_REQUEST', '/batch?requests=%l');
define('API_GET_OUTLOOK_BY_FIPS', '/advisories/closest?filter=outlook&p=fips:%p');
define('API_GET_OUTLOOK_BY_ZONE', '/advisories/closest?filter=outlook&p=%p&limit=1');
define('API_AUTH_STRING', '&client_id=%i&client_secret=%s');

// API Types
define('API_TYPE_NONE', 0);
define('API_TYPE_AERIS', 1);
//define('API_TYPE_WUNDERGROUND', 2);