<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 3:36 PM
	 */

	define('PATH_ROOT', './');
	require_once('config.php');

	$db = new db_pdo();

	$results = $db->query(SQL_SELECT_ALL_FROM_OFFICE_IDS);

	// Get all the Office IDs and put into Select Box
	$officeSelect = array();
	$officeSelect[] = '<select name="nws_office" id="nws_office">';
	foreach ($results as $result) {
		$officeSelect[] = '<option value="' . $result['id'] . '">' . $result['city'] . ', ' . $result['state'] . '</option>';
	}
	$officeSelect[] = '</select>';

	$fetch_button = '<input type="button" id="fetch_report" value="Fetch Outlook Report" />';

	// Display the page
	$template_vars = array(
		'I_OFFICE_SELECT' => $officeSelect,
		'I_FETCH_REPORT'  => $fetch_button
	);

	$output = new template('fetch_hwo');
	$output->set_template_vars($template_vars);
	$output->display();


