<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 3:43 PM
	 */

	// PATHS
	define('PATH_INCLUDES', PATH_ROOT . 'includes/');
	define('PATH_TEMPLATES', PATH_ROOT . 'templates/');
	define('PATH_CLASSES', PATH_ROOT . 'includes/classes/');
	// Tables
	define('TABLE_COUNTIES', 'counties');
	define('TABLE_CRON_OFFICE_CHECK', 'cron_office_check');
	define('TABLE_OFFICES', 'offices');
	define('TABLE_SPOTTER_STATUS', 'spotter_status');
	define('TABLE_REPORTS', 'reports');

	// Users_Offices Table
	define('TABLE_USERS_OFFICES', 'users_offices');
	define('USERS_OFFICES_USER_ID', 'user_id');
	define('USERS_OFFICES_OFFICE_ID', 'office_id');
	// TODO Flesh out defines for other table columns
	// Users Table
	define('TABLE_USERS', 'users');
	define('USERS_ID', 'id');
	define('USERS_NAME', 'name');
	define('USERS_PROFILE_IMAGE_URL_HTTPS', 'profile_image_url_https');
	define('USERS_OAUTH_TOKEN', 'oauth_token');
	define('USERS_OAUTH_TOKEN_SECRET', 'oauth_token_secret');
	define('USERS_SID', 'sid');
	define('USERS_SID_EXPIRE', 'sid_expire');
	define('USERS_SCREEN_NAME', 'screen_name');
	define('USERS_IS_FOLLOWER', 'is_follower');

	// Queries
	define('SQL_SELECT_ALL_FROM_OFFICE', "SELECT * FROM ".TABLE_OFFICES." ORDER BY state ASC, city ASC");
	define('SQL_SELECT_ALL_FROM_OFFICE_BY_ID', "SELECT * FROM ".TABLE_OFFICES." WHERE id=?");
	define('SQL_SELECT_REPORT_BY_HASH', "SELECT id FROM " . TABLE_REPORTS . " WHERE report_hash=?");
	define('SQL_SELECT_USER_BY_OAUTH_TOKEN', "SELECT * FROM ".TABLE_USERS." WHERE ".USERS_OAUTH_TOKEN."=?");
	define('SQL_SELECT_USER_BY_ID', "SELECT * FROM ".TABLE_USERS." WHERE ".USERS_ID."=?");
	define('SQL_SELECT_OFFICE_IDS_BY_USER_ID', "SELECT * FROM ".TABLE_USERS_OFFICES." WHERE ".USERS_OFFICES_USER_ID."=?");
	// Modify
	define('SQL_INSERT_GENERIC', "INSERT INTO %t (%c) VALUES (%v)");
	define('SQL_REPLACE_GENERIC', "REPLACE INTO %t (%c) VALUES (%v)");
	define('SQL_DELETE_GENERIC', "DELETE IGNORE FROM %t WHERE %c");
	define('SQL_UPDATE_GENERIC', "UPDATE %t SET %ufv WHERE %cfv");

	// REGEX
	define('REGEX_HWO_REPORT', '/<pre.+?>(.*)<\/pre>/ms');
	define('REGEX_COUNTY_LIST', '/(?:[0-9]{6}\- )(.+?)(?:\- [0-9]{3,4} (AM|PM))/ms');
	define('REGEX_SPOTTER_STATEMENT', '/(?:\.SPOTTER INFORMATION STATEMENT\.\.\. )(.+?)\$\$/');
	define('REGEX_TEMPLATE_INCLUDE', '/<!-- INCLUDE (.+?) -->/ms');
