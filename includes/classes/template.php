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
				// Special handling for per-page css
				$header = new template('header', false, false);
				$header->set_template_vars(array(
					'CSS_SPECIFIC' => (file_exists(PATH_CSS . $filename . '.css')) ? '<link rel="stylesheet" type="text/css" href="' . PATH_CSS . $filename . '.css">' : ''
				));
				$this->htmlout .= $header->compile();
			}
			$this->htmlout .= file_get_contents(PATH_TEMPLATES . $this->filename . '.html');
			if ($addFooter) {
				$this->htmlout .= file_get_contents(PATH_TEMPLATES . 'footer.html');
			}
		}

		/**
		 * Stores the field-value pairs for translation into the html template
		 *
		 * @param string|array $field The variable text to replace in the html template
		 * @param string       $value The HTML value to substitute in place of the $field
		 */
		public function set_template_vars($field, $value = '') {
			if (is_array($field)) {
				foreach ($field as $f => $v) {
					$v = (is_array($v)) ? implode('', $v) : $v;
					$this->template_vars['{' . $f . '}'] = $v;
				}
			} else {
				$value = (is_array($value)) ? implode('', $value) : $value;
				$this->template_vars['{' . $field . '}'] = $value;
			}
		}

		/**
		 * Echos out the html result
		 */
		public function display() {
			$this->build_template();
			echo $this->htmlout;
		}

		public function compile() {
			$this->build_template();
			return $this->htmlout;
		}

		/**
		 * Replaces variables with html content and generates the html string
		 */
		private function build_template() {

			$this->htmlout = $this->parse_template($this->htmlout);

			$this->htmlout = strtr($this->htmlout, $this->template_vars);
		}

		private function parse_template($template_html) {

			// Includes
			preg_match_all(REGEX_TEMPLATE_INCLUDE, $template_html, $includes, PREG_PATTERN_ORDER);

			if(!empty($includes[0])) {
				foreach ($includes[1] as $x => $template_filename) {
					if(file_exists(PATH_TEMPLATES.$template_filename)) {
						$template_data = file_get_contents(PATH_TEMPLATES.$template_filename);
						$template_html = str_replace($includes[0][$x], $this->parse_template($template_data), $template_html);
					}
				}
			}

			// IF statements
			preg_match_all('/<!-- IF ([{\w}]+)? -->(.*)<!-- ENDIF -->/ms', $template_html, $ifs, PREG_PATTERN_ORDER);

			if (!empty($ifs[0])) {
				foreach ($ifs[1] as $x => $b_key) {
					$replacement = (isset($this->template_vars[$b_key]) && ($this->template_vars[$b_key])) ? $ifs[2][$x] : '';
					$template_html = preg_replace('/<!-- IF ' . $b_key . ' -->(.*)<!-- ENDIF -->/ms', $replacement, $template_html);
				}
			}

			return $template_html;
		}
	}