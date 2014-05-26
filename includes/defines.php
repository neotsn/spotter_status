<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 3:43 PM
	 */

	// PATHS
	define('PATH_INCLUDES', PATH_ROOT.'includes/');
	define('PATH_TEMPLATES', PATH_ROOT.'templates/');
	define('PATH_CSS', PATH_ROOT.'css/');
	define('PATH_CLASSES', PATH_ROOT.'includes/classes/');

	// Tables
	define('TABLE_COUNTIES', 'counties');
	define('TABLE_CRON_OFFICE_CHECK', 'cron_office_check');
	define('TABLE_OFFICES', 'offices');
	define('TABLE_OUTLOOKS', 'outlooks');
	define('TABLE_STATEMENTS', 'statements');
	define('TABLE_USERS', 'users');
	define('TABLE_USERS_OFFICES', 'users_offices');

	// Counties Table
	define('COUNTIES_OFFICE_ID', 'office_id');
	define('COUNTIES_NAME', 'county_name');

	// Cron_Offices Table
	define('CRON_OFFICES_ID', 'office_id');
	define('CRON_OFFICES_LAST_CHECK', 'last_check');

	// Offices Table
	define('OFFICES_ID', 'id');
	define('OFFICES_CITY', 'city');
	define('OFFICES_STATE', 'state');

	// Outlooks Table
	define('OUTLOOKS_ID', 'id');
	define('OUTLOOKS_OFFICE_ID', 'office_id');
	define('OUTLOOKS_HASH', 'hash');
	define('OUTLOOKS_TIMESTAMP', 'timestamp');
	define('OUTLOOKS_TEXT', 'text');

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

	// Queries
	define('SQL_SELECT_ALL_FROM_OUTDATED_ACTIVE_CRON_OFFICES', "SELECT DISTINCT c.".CRON_OFFICES_ID." FROM ".TABLE_CRON_OFFICE_CHECK." c, ".TABLE_USERS_OFFICES." uo WHERE ".CRON_OFFICES_LAST_CHECK."<=? AND c.".CRON_OFFICES_ID."=uo.".USERS_OFFICES_OFFICE_ID);
	define('SQL_SELECT_ALL_FROM_OFFICES', "SELECT * FROM ".TABLE_OFFICES." ORDER BY ".OFFICES_STATE." ASC, ".OFFICES_CITY." ASC");
	define('SQL_SELECT_ALL_FROM_OFFICES_BY_ID', "SELECT * FROM ".TABLE_OFFICES." WHERE ".OFFICES_ID."=?");
	define('SQL_SELECT_ALL_FROM_OFFICES_BY_USER_ID', "SELECT * FROM ".TABLE_OFFICES." o, ".TABLE_USERS_OFFICES." uo WHERE o.".OFFICES_ID."=uo.".USERS_OFFICES_OFFICE_ID." AND uo.".USERS_OFFICES_USER_ID."=?");
	define('SQL_SELECT_OUTLOOK_BY_HASH', "SELECT * FROM ".TABLE_OUTLOOKS." WHERE ".OUTLOOKS_HASH."=?");
	define('SQL_SELECT_ALL_STATEMENTS_FOR_USER_ID', "SELECT s.*, o.* FROM ".TABLE_STATEMENTS." s, ".TABLE_USERS_OFFICES." uo, ".TABLE_OFFICES." o WHERE s.".STATEMENTS_OFFICE_ID."=uo.".USERS_OFFICES_OFFICE_ID." AND uo.".USERS_OFFICES_OFFICE_ID."=o.".OFFICES_ID." AND uo.".USERS_OFFICES_USER_ID."=?");
	define('SQL_SELECT_USER_BY_OAUTH_TOKEN', "SELECT * FROM ".TABLE_USERS." WHERE ".USERS_OAUTH_TOKEN."=?");
	define('SQL_SELECT_USER_BY_ID', "SELECT * FROM ".TABLE_USERS." WHERE ".USERS_ID."=?");
	define('SQL_SELECT_USER_ROWS_BY_OFFICE_ID', "SELECT DISTINCT *.u FROM ".TABLE_USERS_OFFICES." uo, ".TABLE_USERS." u WHERE uo.".USERS_OFFICES_OFFICE_ID."=? AND u.".USERS_ID."=uo.".USERS_OFFICES_USER_ID);
	define('SQL_SELECT_OFFICE_IDS_BY_USER_ID', "SELECT * FROM ".TABLE_USERS_OFFICES." WHERE ".USERS_OFFICES_USER_ID."=?");
	define('SQL_SELECT_USER_IDS_BY_OFFICE_ID', "SELECT DISTINCT ".USERS_OFFICES_USER_ID." FROM ".TABLE_USERS_OFFICES." WHERE ".USERS_OFFICES_OFFICE_ID."=?");

	// Modify
	define('SQL_INSERT_GENERIC', "INSERT INTO %t (%c) VALUES (%v)");
	define('SQL_REPLACE_GENERIC', "REPLACE INTO %t (%c) VALUES (%v)");
	define('SQL_DELETE_GENERIC', "DELETE IGNORE FROM %t WHERE %c");
	define('SQL_UPDATE_GENERIC', "UPDATE %t SET %ufv WHERE %cfv");

	// REGEX
	define('REGEX_HWO_REPORT', '/<pre.+?>(.*)<\/pre>/ms');
	define('REGEX_TIMESTAMP', '/([\d]{3,4}) (AM|PM) ([\w]{3}) ([\w]{3}) ([\w]{3}) ([\d]{1,2}) ([\d]{4})/ms');
	define('REGEX_COUNTY_LIST', '/(?:[0-9]{6}\- )(.+?)(?:\- [0-9]{3,4} (AM|PM))/ms');
	define('REGEX_SPOTTER_STATEMENT', '/(?:\.SPOTTER INFORMATION STATEMENT\.\.\. )(.+?)\$\$/');
	define('REGEX_TEMPLATE_INCLUDE', '/<!-- INCLUDE (.+?) -->/ms');
	define('REGEX_TEMPLATE_CONDITIONS', '/<!-- IF ([{\w}]+)? -->(.+?)(?:<!-- ELSE -->(.+?))?<!-- ENDIF -->/ms');
