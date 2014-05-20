<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 3:37 PM
	 */

	require_once "defines.php";

	/**
	 * Class autoloader
	 *
	 * @param $class_name
	 */
	function __autoload($class_name) {
		include PATH_CLASSES . $class_name . ".php";
	}

	function get_request($field, $default, $type = 'string') {
		$value = (isset($_REQUEST[$field])) ? $_REQUEST[$field] : $default;
		return sanitize_request($type, $value);
	}

	function sanitize_request($type, $value) {
		switch ($type) {
			case 'string':
				$value = preg_replace('/[^\w\d\s]+?/', '', $value);
				break;
			case 'number':
				$value = preg_replace('/[^\d\.]+?/', '', $value);
				break;
		}
		return $value;
	}