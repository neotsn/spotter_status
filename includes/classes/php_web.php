<?php

	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/20/2014
	 * Time: 9:06 PM
	 */
	class php_web {

		public function get_url_reponse($url) {
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$data = curl_exec($ch);
			curl_close($ch);
			return $data;
		}

		public function ensure_string($value) {
			return (string)$value;
		}

		public function ensure_int($value) {
			return (int)$value;
		}
	}