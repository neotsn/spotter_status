<?php
	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 3:35 PM
	 */

	/**
	 * Class db_pdo
	 * a PDO wrapper for Mysql connections
	 */
	class db_pdo {

		private $database = '';

		public $db = null;
		public $query = null;
		public $results = array();

		/**
		 * @param string|null $db_name Database Name to connect to
		 */
		public function __construct($db_name = null) {

			$conn_info = parse_ini_file("./connection_info.ini", true);

			$this->database = (is_null($db_name)) ? $conn_info['db']['database'] : $db_name;

			$this->db = new PDO('mysql:host=' . $conn_info['db']['hostname'] . ';dbname=' . $this->database . ';charset=utf8', $conn_info['db']['username'], $conn_info['db']['password']);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}

		/**
		 * @param       $statement
		 * @param array $params
		 *
		 * @return array
		 */
		public function query($statement, $params = array()) {
			$this->query = $this->db->prepare($statement);
			$this->query->execute($params);
			$this->results = $this->query->fetchAll(PDO::FETCH_ASSOC);
			return $this->results;
		}

		/**
		 * @param $sql
		 * @param $table
		 * @param $fv_pairs
		 *
		 * @return bool|int
		 */
		public function update($sql, $table, $fv_pairs) {

			// Build the field assignment strings
			$field_str = $value_str = array();
			foreach ($fv_pairs as $f => $v) {
				$field_str[$f] = $f;
				$value_str[$f] = ':' . $f;
			}

			$results = false;
			try {
				// Start the transaction
				$this->db->beginTransaction();

				// Prepare the SQL statement
				$sql = strtr($sql, array(
						'%t' => $table,
						'%c' => implode(', ', array_unique($field_str)),
						'%v' => implode(', ', array_unique($value_str)))
				);
				$this->query = $this->db->prepare($sql);

				// loop through to bind the variable variable name for each field
				foreach ($value_str as $f => $v) {
					$this->query->bindParam($v, $$f); // Intentional variable variable
				}
				// loop through to assign the variable variable's value, and execute
				foreach ($fv_pairs as $f => $v) {
					$$f = $v; // set the variable variable
				}
				$this->query->execute(); // execute the replace query

				// attempt to commit the transaction
				$this->db->commit();
				// return # rows affected
				$results = $this->db->lastInsertId();
			} catch (PDOException $e) {
				$this->db->rollBack();
				echo $e->getMessage();
			}

			return $results;
		}

		/**
		 * @param $sql
		 * @param $table
		 * @param $fv_pairs_array
		 *
		 * @return bool|int
		 */
		public function update_multiple($sql, $table, $fv_pairs_array) {

			// Build the field assignment strings
			$field_str = $value_str = array();
			foreach ($fv_pairs_array as $fv_pairs) {
				foreach ($fv_pairs as $f => $v) {
					$field_str[$f] = $f;
					$value_str[$f] = ':' . $f;
				}
			}
			$results = 0;
			try {
				// Start the transaction
				$this->db->beginTransaction();

				// Prepare the SQL statement
				$sql = strtr($sql, array(
						'%t' => $table,
						'%c' => implode(', ', array_unique($field_str)),
						'%v' => implode(', ', array_unique($value_str)))
				);
				$this->query = $this->db->prepare($sql);

				// loop through to bind the variable variable name for each field
				foreach ($value_str as $f => $v) {
					$this->query->bindParam($v, $$f); // Intentional variable variable
				}
				// loop through to assign the variable variable's value, and execute
				foreach ($fv_pairs_array as $fv_pairs) {
					foreach ($fv_pairs as $f => $v) {
						$$f = $v; // set the variable variable
					}
					$this->query->execute(); // execute the replace query
					$results += $this->query->rowCount(); // return # rows affected
				}
				// attempt to commit the transaction
				$this->db->commit();
			} catch (PDOException $e) {
				$this->db->rollBack();
				echo $e->getMessage();
			}

			return $results;
		}

		public function get_next() {
			return (!empty($this->results)) ? array_shift($this->results) : array();
		}
	}