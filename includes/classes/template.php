<?php

/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/18/2014
 * Time: 5:09 PM
 */
class Template
{

    public $filename = '';
    public $template_vars = array();

    private $htmlout = '';
    private $original_file = '';
    private $header = '';
    private $footer = '';

    /**
     * Build the Template for output
     *
     * @param string $filename  The filename, less .html
     * @param bool   $addHeader Prepend the header Template?
     * @param bool   $addFooter Append the footer Template?
     */
    public function __construct($filename, $addHeader = true, $addFooter = true)
    {
        $this->filename = $filename;

        if ($addHeader) {
            $this->build_header();
        }
        if (!$this->original_file) {
            $this->original_file = file_get_contents(PATH_TEMPLATES . $this->filename . '.html');
        }

        if ($addFooter) {
            $this->build_footer();
        }
        $this->reset_template();
    }

    /**
     * Stores the field-value pairs for translation into the html Template
     *
     * @param string|array $field The variable text to replace in the html Template
     * @param string       $value The HTML value to substitute in place of the $field
     */
    public function setTemplateVars($field, $value = '')
    {
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
    public function display()
    {
        $this->buildTemplate();
        echo $this->htmlout;
    }

    /**
     * Prepares the html result, and returns (no echo)
     *
     * @return string HTML result
     */
    public function compile()
    {
        $this->buildTemplate();
        return $this->htmlout;
    }

    /**
     * Restores the original template with header/footer information for reuse in loop
     */
    public function reset_template()
    {
        $this->htmlout = $this->header . $this->original_file . $this->footer;
    }

    /**
     * Replaces variables with html content and generates the html string
     */
    private function buildTemplate()
    {
        $this->htmlout = $this->parseTemplate($this->htmlout);
        $this->htmlout = strtr($this->htmlout, $this->template_vars);
    }

    /**
     * Recursive:
     * Handles INCLUDE and IF-ELSE statements inside Template files
     *
     * @param string $template_html the Template's HTML text to parse
     *
     * @return string Parsed HTML Result
     */
    private function parseTemplate($template_html)
    {

        // Includes
        preg_match_all(REGEX_TEMPLATE_INCLUDE, $template_html, $includes, PREG_PATTERN_ORDER);

        if (!empty($includes[0])) {
            foreach ($includes[1] as $x => $template_filename) {
                if (file_exists(PATH_TEMPLATES . $template_filename)) {
                    $template_data = file_get_contents(PATH_TEMPLATES . $template_filename);
                    $template_html = str_replace($includes[0][$x], $this->parseTemplate($template_data),
                        $template_html);
                }
            }
        }

        // IF-ELSE statements
        preg_match_all(REGEX_TEMPLATE_CONDITIONS, $template_html, $ifs, PREG_PATTERN_ORDER);
        if (!empty($ifs[0])) {
            foreach ($ifs[1] as $x => $b_key) {
                $replacement = (isset($this->template_vars[$b_key]) && ($this->template_vars[$b_key])) ? $ifs[2][$x] : $ifs[3][$x];
                $template_html = preg_replace('/<!-- IF ' . $b_key . ' -->(.+?)<!-- ENDIF -->/ms', $replacement,
                    $template_html);
            }
        }

        return $template_html;
    }

    private function build_header()
    {
        // Special handling for per-page css
        $header = new Template('header', false, false);
        $header->setTemplateVars(array(
            'PATH_CSS'     => PATH_CSS,
            'PATH_JS'      => PATH_JS,
            'CSS_SPECIFIC' => (file_exists(PATH_CSS . $this->filename . '.css')) ? '<link rel="stylesheet" type="text/css" href="' . PATH_CSS . $this->filename . '.css">' : ''
        ));
        $this->header .= $header->compile();
    }

    private function build_footer()
    {
        $footer = new Template('footer', false, false);
        $footer->setTemplateVars(array(
            'VERSION_NUMBER' => VERSION,
            'YEAR'           => date('Y')
        ));
        $this->footer .= $footer->compile();
    }
}