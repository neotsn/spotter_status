<?php
	/**
	 * Created by PhpStorm.
	 * User: Chris
	 * Date: 5/24/14
	 * Time: 12:55 PM
	 */

	/**
	 * Class outlook
	 * For handling the content of a Hazardous Weather Outllok
	 */
	class outlook extends php_web {
		public $response = '';
		public $outlooks = array();
		public $timestamps = array();
		public $counties = array();
		public $statements = array();
		public $url = '';

		private $hash = '';
		private $original_outlooks = array();
		private $office_id = '';

		public function __construct($office_id) {
			$this->office_id = $office_id;

			$this->build_url($this->office_id);
			$this->response = $this->get_url_reponse($this->url);
			$this->hash = md5($this->response);
		}

		public function process_outlooks() {
			preg_match_all(REGEX_HWO_REPORT, $this->response, $data, PREG_PATTERN_ORDER);

			foreach($data[1] as $outlook) {
				$flat_outlook = preg_replace('/\s+/', ' ', $outlook);
				$thisOutlookHash = md5($outlook);

				$this->original_outlooks[$thisOutlookHash] = $outlook;
				$this->outlooks[$thisOutlookHash] = $flat_outlook;
				$this->timestamps[$thisOutlookHash] = $this->extract_timestamp($flat_outlook);
				$this->counties[$thisOutlookHash] = $this->extract_counties($flat_outlook);
				$this->statements[$thisOutlookHash] = $this->extract_spotter_statements($flat_outlook);
			}
		}

		/**
		 * @param db_pdo $db Database Object
		 *
		 * @return int
		 */
		public function does_report_hash_exist($db) {
			$result = $db->query(SQL_SELECT_REPORT_BY_HASH, array($this->hash));
			return count($result);
		}

		public function prepare_message($statement, $office_id) {
			$statement = (strlen($statement > 114)) ? substr($statement, 0, 111).'...' : $statement;
			$statement = ucfirst(strtolower($statement));

			$this->build_url($office_id);

			return $office_id.': '.$statement.' '.$this->url;
		}

		/**
		 * @param db_pdo $db Database Object
		 */
		public function save_outlooks($db) {
			// Process the county list...
			$params = array();
			foreach($this->counties as $county) {
				$params[] = array(
					'office_id'   => $this->office_id,
					'county_name' => trim($county)
				);
			}
			$db->replace_multiple(TABLE_COUNTIES, $params);

			// Update the Offices list
			$params = array(
				'office_id'  => $this->office_id,
				'last_check' => time()
			);
			$db->replace(TABLE_CRON_OFFICE_CHECK, $params);

			// Save the outlooks
			foreach($this->outlooks as $key => $outlook) {
				$params = array(
					'office_id'        => $this->office_id,
					'report_hash'      => $this->hash,
					'report_text'      => $this->original_outlooks[$key],
					'report_timestamp' => $this->timestamps[$key]
				);
				$outlook_id = $db->insert(TABLE_REPORTS, $params);

				// Update the spotter status for this report - should never update
				$params = array(
					'office_id'       => $this->office_id,
					'report_id'       => $outlook_id,
					'spotter_message' => $this->statements[$key]
				);
				$db->replace(TABLE_SPOTTER_STATUS, $params);
			}
		}

		private function extract_timestamp($outlook) {
			preg_match_all(REGEX_TIMESTAMP, $outlook, $ts, PREG_PATTERN_ORDER);

			$hrmin = ((strlen($ts[1][0]) > 3) ? substr($ts[1][0], 0, 2) : substr($ts[1][0], 0, 1)).':'.substr($ts[1][0], -2);
			return strtotime($hrmin.' '.$ts[2][0].' '.$ts[3][0].' '.$ts[5][0].' '.$ts[6][0].' '.$ts[7][0]);
		}

		private function extract_counties($outlook) {
			preg_match_all(REGEX_COUNTY_LIST, $outlook, $county_data, PREG_PATTERN_ORDER);

			$counties = array();
			foreach($county_data[1] as $c_data) {
				$counties = array_merge($counties, explode('-', $c_data));
			}
			return sort(array_unique($counties)); // Sort and unique it
		}

		private function extract_spotter_statements($outlook) {
			preg_match_all(REGEX_SPOTTER_STATEMENT, $outlook, $spotter_statement, PREG_PATTERN_ORDER);

			$statements = array();
			foreach($spotter_statement[1] as $statement) {
				$statements[] = $statement;
			}
			return $statements;
		}

		private function build_url($office_id) {
			$this->url = "http://forecast.weather.gov/product.php?site=NWS&issuedby=$office_id&product=HWO&format=txt&version=1&glossary=0";
		}
	}