<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 6:29 PM
	 */

	define('PATH_ROOT', './');
	require_once('config.php');

	// Get the request vars
	$mode = get_request('mode', '');
	$submode = get_request('submode', '');
	$office = get_request('nws_office', '');

	// Toggle the mode
	switch($mode) {
		case 'getOutlook':
			// Build the URL
			$url = "http://forecast.weather.gov/product.php?site=NWS&issuedby=$office&product=HWO&format=txt&version=1&glossary=0";
			// Grab the page
			$web = new php_web();
			$response = $web->get_url_reponse($url);

			// Strip out the contents inside the <pre> tags and get the $data
			preg_match_all(REGEX_HWO_REPORT, $response, $data, PREG_PATTERN_ORDER);

			// pull out the inner text, create the $outlook
			$orig_outlook = $data[1][0];

			// Hash it to see if it has changed
			$hash = md5($orig_outlook);

			// flatten it into a single line of text
			$outlook = preg_replace('/\s+/', ' ', $orig_outlook);

			preg_match_all(REGEX_TIMESTAMP, $outlook, $ts, PREG_PATTERN_ORDER);
			$hrmin = ((strlen($ts[1][0]) > 3) ? substr($ts[1][0], 0, 2) : substr($ts[1][0], 0, 1)).':'.substr($ts[1][0], -2);
			$time = strtotime($hrmin.' '.$ts[2][0].' '.$ts[3][0].' '.$ts[5][0].' '.$ts[6][0].' '.$ts[7][0]);

			// extract the county list for the outlook and spool into county array
			preg_match_all(REGEX_COUNTY_LIST, $outlook, $county_data, PREG_PATTERN_ORDER);
			$counties = array();
			foreach($county_data[1] as $c_data) {
				$counties = array_merge($counties, explode('-', $c_data));
			}
			sort(array_unique($counties)); // Sort and unique it

			// extract the spotter information statement
			preg_match_all(REGEX_SPOTTER_STATEMENT, $outlook, $spotter_statement, PREG_PATTERN_ORDER);

			// pull out the inner text, create the statement
			$statement = $spotter_statement[1][0];

			// Prepare to write it all to the database
			$db = new db_pdo();
			$result = $db->query(SQL_SELECT_REPORT_BY_HASH, array($hash));

			if(!count($result)) {
				// Update the county list
				$params = array();
				foreach($counties as $county) {
					$params[] = array(
						'office_id'   => $office,
						'county_name' => trim($county)
					);
				}
				$counties_updated = $db->replace_multiple(TABLE_COUNTIES, $params);

				// Add this office to the list of reports to check for with CRON
				$params = array(
					'office_id' => $office
				);
				$db->replace(TABLE_CRON_OFFICE_CHECK, $params);

				// Save the report
				$params = array(
					'office_id'        => $office,
					'report_hash'      => $hash,
					'report_text'      => $orig_outlook,
					'report_timestamp' => $time
				);
				$outlook_id = $db->insert(TABLE_REPORTS, $params);

				// Update the spotter status for this report - should never update
				$params = array(
					'office_id'       => $office,
					'report_id'       => $outlook_id,
					'spotter_message' => $statement
				);
				$db->replace(TABLE_SPOTTER_STATUS, $params);
			}

			// Get the Office City
			$db->query(SQL_SELECT_ALL_FROM_OFFICE_BY_ID, array($office));
			$office_row = $db->get_next();

			ob_clean();
			echo 'For the National Weather Service office serving the ', ucwords($office_row['city']), ', ', strtoupper($office_row['state']), ' area: <br />', ucwords($statement);
			exit;
			break;
	}