<?php
/**
 * Created by thepizzy.net.
 * User: @neotsn
 * Date: 5/24/14
 * Time: 12:55 PM
 */

/**
 * Class Outlook
 * For handling the content of a Hazardous Weather Outllok
 */
class Outlook extends WebRequests
{
    public $statements = array();

    private $counties = array();
    private $flat_outlook = '';
    private $hash = '';
    private $office_id = '';
    private $original_outlook = '';
    private $response = '';
    private $timestamp = '';
    private $url = '';
    private $statement_hash = '';

    /**
     * Fetches the Outlook for the office_id provided
     * Then extracts the Outlook's text from the <pre> tags
     * Then trims the whitespace if text is found, or passes blank if none - stored as original Outlook
     * Then does an internal whitespace trim with regex - stored as flat Outlook
     * Then generates a hash from the flat Outlook for database comparison
     *
     * @param string $office_id 3-Letter Office ID
     */
    public function __construct($office_id)
    {
        $this->office_id = $office_id;

        // Construct the URL
        $this->_buildUrl($this->office_id);
        // CURL for the webpage
        $this->response = $this->getUrlReponse($this->url);

        // Extract the Outlook from between the <pre> tags
        preg_match_all(REGEX_HWO_REPORT, $this->response, $data, PREG_PATTERN_ORDER);

        // Trim the whitespace on the Outlook, or pass blank for no Outlook
        $this->original_outlook = trim(!empty($data[1][0]) ? $data[1][0] : '');

        // Flatten the Outlook so no whitespace can change the hash
        $this->flat_outlook = preg_replace('/\s+/ms', ' ', $this->original_outlook);

        // Hash this modified result - a change in information or date will trigger an update, not whitespace
        $this->hash = md5($this->flat_outlook);
    }

    /**
     * Extracts the counties (for future features), [first] timestamp of report, and spotter statements
     */
    public function processOutlook()
    {

        // Gather the information from this Outlook
        // Counties
        $this->counties = $this->_extractCounties($this->flat_outlook);

        // Timestamps
        $this->timestamp = $this->_extractTimestamp($this->flat_outlook);

        // Statements
        $this->statements = $this->_extractSpotterStatements($this->flat_outlook);

        // Statement Hash
        $this->statement_hash = md5(trim(preg_replace('/\s+/ms', ' ', implode('|', $this->statements))));
    }

    /**
     * Checks to see if the current Outlook's hash has changed from what's in the database
     *
     * @param string $last_outlook_hash The last Outlook hash for the office
     *
     * @return bool
     */
    public function doesOutlookHashMatch($last_outlook_hash)
    {
        return ($last_outlook_hash == $this->hash);
    }

    /**
     * Checks to see if the current statement hash has changed from what's in the database
     *
     * @param string $last_statement_hash The last statement hash for the office
     *
     * @return bool
     */
    public function doesStatementHashMatch($last_statement_hash)
    {
        return ($last_statement_hash == $this->statement_hash);
    }

    /**
     * Formats the Spotter Statement for use in Direct Messages
     *
     * @param string $statement Spotter Activation Statement text
     * @param string $office_id 3-Letter Office ID
     *
     * @return string Formatted text for Direct Message consumption
     */
    public function prepareMessage($statement, $office_id)
    {
        $statement = (strlen($statement) > 112) ? substr($statement, 0, 109) . '...' : $statement;
        $statement = ucfirst(strtolower($statement));

        $this->_buildUrl($office_id);

        return $office_id . ': ' . $statement . ' ' . $this->url;
    }

    /**
     * Save the Outlook data:
     * Counties, Outlooks, Statements, Update office last-checked time
     */
    public function saveOutlook()
    {
        global $db;
        // Process the county list...
        $params = array();
        foreach ($this->counties as $county) {
            $params[] = array(
                COUNTIES_OFFICE_ID => $this->office_id,
                COUNTIES_NAME      => trim($county)
            );
        }
        $db->replaceMultiple(TABLE_COUNTIES, $params);

        // Update the Offices list
        $params = array(
            CRON_OFFICE_ID         => $this->office_id,
            CRON_OFFICE_LAST_CHECK => time()
        );
        $db->replace(TABLE_CRON_OFFICE_CHECK, $params);

        // Save the outlooks
        $params = array(
            OUTLOOKS_OFFICE_ID => $this->office_id,
            OUTLOOKS_HASH      => $this->hash,
            OUTLOOKS_TEXT      => $this->original_outlook,
            OUTLOOKS_TIMESTAMP => $this->timestamp
        );
        $db->insert(TABLE_OUTLOOKS, $params);
    }

    public function saveStatements()
    {
        global $db;

        // Update the spotter status for this report - should never update
        $params = array(
            STATEMENTS_OFFICE_ID    => $this->office_id,
            STATEMENTS_MESSAGE      => implode(' | ', $this->statements),
            STATEMENTS_LAST_OUTLOOK => $this->timestamp,
            STATEMENTS_HASH         => $this->statement_hash
        );
        $db->replace(TABLE_STATEMENTS, $params);
    }

    /**
     * Extract the first timestamp found for use as the Outlook's Timestamp
     *
     * @param string $outlook The Outlook body text
     *
     * @return int Unixtime
     */
    private function _extractTimestamp($outlook)
    {
        preg_match_all(REGEX_TIMESTAMP, $outlook, $ts, PREG_PATTERN_ORDER);

        $hrmin = ((strlen($ts[1][0]) > 3) ? substr($ts[1][0], 0, 2) : substr($ts[1][0], 0, 1)) . ':' . substr($ts[1][0],
                -2);
        return strtotime($hrmin . ' ' . $ts[2][0] . ' ' . $ts[3][0] . ' ' . $ts[5][0] . ' ' . $ts[6][0] . ' ' . $ts[7][0]);
    }

    /**
     * Extract the counties and merge into a single array
     * This is for use in a future feature
     *
     * @param string $outlook The Outlook body text
     *
     * @return array Counties present in the Outlook(s)
     */
    private function _extractCounties($outlook)
    {
        preg_match_all(REGEX_COUNTY_LIST, $outlook, $county_data, PREG_PATTERN_ORDER);

        $counties = array();
        foreach ($county_data[1] as $c_data) {
            $counties = array_merge($counties, explode('-', $c_data));
        }
        sort(array_unique($counties));
        return $counties; // Sort and unique it
    }

    /**
     * Extracts the Spotter Activation Statement(s) from the Outlook
     *
     * @param string $outlook The Outlook body text
     *
     * @return array Array of Statements found in the Outlook(s)
     */
    private function _extractSpotterStatements($outlook)
    {
        preg_match_all(REGEX_SPOTTER_STATEMENT, $outlook, $spotter_statement, PREG_PATTERN_ORDER);

        $statements = array();
        foreach ($spotter_statement[1] as $statement) {
            $statements[] = $statement;
        }
        return $statements;
    }

    /**
     * Wrapper for url concatenation
     *
     * @param string $office_id 3-Letter Office ID
     */
    private function _buildUrl($office_id)
    {
        $this->url = "http://forecast.weather.gov/product.php?site=NWS&issuedby=$office_id&product=HWO&format=txt&version=1&glossary=0";
    }
}
