<?php

	/**
	 * Created by thepizzy.net
	 * User: @neotsn
	 * Date: 5/18/2014
	 * Time: 5:09 PM
	 */
	class template {

		private $htmlout = '';

		public $filename = '';
		public $template_vars = array();

		/**
		 * Build the template for output
		 *
		 * @param string $filename  The filename, less .html
		 * @param bool   $addHeader Prepend the header template?
		 * @param bool   $addFooter Append the footer template?
		 */
		public function __construct($filename, $addHeader = true, $addFooter = true) {
			$this->filename = $filename;

			if ($addHeader) {
				$this->htmlout .= file_get_contents(PATH_TEMPLATES . 'header.html');
			}
			$this->htmlout .= file_get_contents(PATH_TEMPLATES . $this->filename . '.html');
			if ($addFooter) {
				$this->htmlout .= file_get_contents(PATH_TEMPLATES . 'footer.html');
			}

			$this->prepare_template_vars();
		}

		public function set_template_vars($field, $value = '') {
			if (is_array($field)) {
				foreach ($field as $f => $v) {
					$v = (is_array($v)) ? implode('', $v) : $v;
					$this->template_vars[$f] = $v;
				}
			} else {
				$value = (is_array($value)) ? implode('', $value) : $value;
				$this->template_vars[$field] = $value;
			}
		}

		public function build_template() {
			$this->htmlout = strtr($this->htmlout, $this->template_vars);
		}

		public function display() {
			echo $this->htmlout;
		}

		private function prepare_template_vars() {
			switch ($this->filename) {
				case 'main':
					$this->template_vars['{I_OFFICE_SELECT}'] = '[Insert Office Select Box]';
					break;
			}
		}
	}