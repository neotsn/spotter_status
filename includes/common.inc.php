<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 3:37 PM
	 */

	/**
	 * Class autoloader
	 *
	 * @param $class_name
	 */
	function __autoload($class_name) {
		if (file_exists(PATH_CLASSES . $class_name . ".php")) {
			include PATH_CLASSES . $class_name . ".php";
		}
	}

	/**
	 * @param        $field
	 * @param        $default
	 * @param string $type
	 *
	 * @return mixed
	 */
	function get_request($field, $default, $type = 'string') {
		$value = (isset($_REQUEST[$field])) ? $_REQUEST[$field] : $default;
		return sanitize_request($type, $value);
	}

	function get_session($field, $default, $type = 'string') {
		$value = (isset($_SESSION[$field])) ? $_SESSION[$field] : $default;
		return sanitize_request($type, $value);
	}

	/**
	 * @param $type
	 * @param $value
	 *
	 * @return mixed
	 */
	function sanitize_request($type, $value) {
		switch ($type) {
			case 'string':
				$value = preg_replace('/[^\w\s]/', '', $value);
				break;
			case 'number':
				$value = preg_replace('/[^\d\.]/', '', $value);
				break;
		}
		return $value;
	}

	/**
	 * @param array  $arr
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	function get_array_value($arr, $key, $default = '') {
		$result = $default;
		if (isset($arr[$key])) {
			$result = $arr[$key];
		}
		return $result;
	}