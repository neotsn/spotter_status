<?php

	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/20/2014
	 * Time: 9:36 PM
	 */
	class twitter_connection_info {

		public $consumer_key = '';
		public $consumer_secret = '';
		public $oauth_callback = '';

		public function __construct() {
			$conn_info = parse_ini_file("./connection_info.ini", true);

			$this->consumer_key = $conn_info['twitter']['CONSUMER_KEY'];
			$this->consumer_secret = $conn_info['twitter']['CONSUMER_SECRET'];
			$this->oauth_callback = $conn_info['twitter']['OAUTH_CALLBACK'];
		}

		public function validate_consumer() {
			$result = true;
			if (!$this->consumer_key || !$this->consumer_secret) {
				echo "Twitter API Key Validation Failed on StormSpotter Status End.";
				exit;
			}
			return $result;
		}
	}