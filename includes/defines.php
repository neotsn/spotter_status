<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 3:43 PM
	 */

	// Server
	define('DB_PASSWORD', 'ysqNCgkX;!;a');
	// PATHS
	define('PATH_INCLUDES', PATH_ROOT . 'includes/');
	define('PATH_TEMPLATES', PATH_ROOT . 'templates/');
	define('PATH_CLASSES', PATH_INCLUDES . 'classes/');
	// Tables
	define('TABLE_COUNTIES', 'counties');
	define('TABLE_CRON_OFFICE_CHECK', 'cron_office_check');
	define('TABLE_OFFICES', 'offices');
	define('TABLE_SPOTTER_STATUS', 'spotter_status');
	define('TABLE_REPORTS', 'reports');
	// Queries
	define('SQL_SELECT_ALL_FROM_OFFICE_IDS', "SELECT * FROM " . TABLE_OFFICES . " ORDER BY state ASC, city ASC");
	define('SQL_SELECT_ALL_FROM_OFFICE_IDS_BY_ID', "SELECT * FROM " . TABLE_OFFICES . " WHERE id=?");
	define('SQL_SELECT_REPORT_BY_HASH', "SELECT id FROM " . TABLE_REPORTS . " WHERE report_hash=?");
	// Modify
	define('SQL_INSERT_GENERIC', "INSERT INTO %t (%c) VALUES (%v)");
	define('SQL_REPLACE_GENERIC', "REPLACE INTO %t (%c) VALUES (%v)");
	// REGEX
	define('REGEX_HWO_REPORT', '/<pre.+?>(.*)<\/pre>/ms');
	define('REGEX_COUNTY_LIST', '/(?:[0-9]{6}\- )(.+?)(?:\- [0-9]{3,4} (AM|PM))/ms');
	define('REGEX_SPOTTER_STATEMENT', '/(?:\.SPOTTER INFORMATION STATEMENT\.\.\. )(.+?)\$\$/');
