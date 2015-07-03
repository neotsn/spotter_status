<?php

/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 6/7/2015
 * Time: 8:30 PM
 */

/**
 * Class Advisory
 * Handles the Weather Underground Advisory API response
 */
class Advisory extends \WebRequests
{

    private $queryStrings = array();
    private $urls;
    private $endpointCount = 0;
    private $responses = array();

    public $data = array();

    /**
     * @param       $string
     * @param array $value
     *
     */
    public function addRequest($string, $value = array())
    {
        global $connection_info;

        if (!empty($value)) {

            switch ($string) {
                case API_GET_OUTLOOK_BY_FIPS:
                case API_GET_OUTLOOK_BY_ZONE:
                    $value = array('%p' => $value);
                    break;
            }
        }

        $auth = array(
            '%i' => $connection_info['aeris']['CLIENT_ID'],
            '%s' => $connection_info['aeris']['CLIENT_SECRET']
        );

        $string = strtr($string, $value) . strtr(API_AUTH_STRING, $auth);

        $this->queryStrings[] = $string;
    }

    /**
     *
     */
    public function prepareBatchUrls()
    {
        $this->queryStrings = array_unique($this->queryStrings);

        $this->endpointCount = count($this->queryStrings);

        for ($x = 0; $x <= ceil(count($this->queryStrings) / 10); $x += 9) {
            $this->_buildBatchUrls(array_slice($this->queryStrings, $x, 10));
        }
    }

    public function prepareSingleUrl()
    {
        $this->queryStrings = array_unique($this->queryStrings);
        $this->endpointCount = 1;

        $this->_buildSingleUrl(array_shift($this->queryStrings));
    }

    /**
     *
     */
    public function executeRequest()
    {
        foreach ($this->urls as $url) {
            $this->responses[] = $this->getUrlReponse($url, $this->endpointCount);
        }

        $this->handleResponses();
    }

    public function getLastCachedResponse()
    {
        global $db;

        $result = $db->query('SELECT call_response FROM ' . TABLE_API_CALLS . ' ORDER BY call_time DESC LIMIT 0, 1');

        $this->responses[] = $result[0]['call_response'];

        $this->handleResponses();
    }

    /**
     * @param $text
     *
     * @return null|string
     */
    public function extractSpotterStatement($text)
    {
        $response = null;

        preg_match_all(REGEX_SPOTTER_STATEMENT, trim($text), $matches);

        if (!empty($matches[1][0])) {
            $response = $this->sentence_case($matches[1][0]);
        } else {
            // TODO Try for Oklahoma Spotter Statement Key
        }

        return $response;
    }

    public function updateAdvisoriesCache($zones, $advisory, $statement, $issued_time)
    {

        global $db;

        $params = array();
        foreach ($zones as $zone => $n) {
            $params[] = array(
                ADVISORIES_STATE_ZONE  => $zone,
                ADVISORIES_ISSUED_TIME => $issued_time,
                ADVISORIES_STATEMENT   => $statement,
                ADVISORIES_ADVISORY    => $advisory
            );
        }

        $db->replaceMultiple(TABLE_ADVISORIES, $params);
    }

    public function prepareTwitterMessage($statement, $area, $state, $cwa)
    {
        $message = trim($area . ', ' . $state . ': ' . $statement);

        $arrayWords = explode(' ', $message);
        $messageLength = strlen($message);

        // Max size of each line
        $maxLineLength = 114;
        $shortMessage = '';

        // Auxiliar counters, foreach will use them
        $currentLength = 0;

        foreach ($arrayWords as $word) {
            // +1 because the word will receive back the space in the end that it loses in explode()
            $wordLength = strlen($word) + 1;

            if (($currentLength + $wordLength) <= $maxLineLength) {
                $shortMessage .= $word . ' ';

                $currentLength += $wordLength;
            } else {
                break;
            }
        }

        $shortMessage = trim($shortMessage); // trim it up
        $shortMessage .= ($currentLength < $messageLength) ? '...' : ''; // add the dots if needed
        $shortMessage .= ' ' . strtr(URL_NWS_ADVISORY, array('%o' => $cwa)); // add the URL

        return $shortMessage;
    }

    private function _buildSingleUrl($queryString)
    {
        $this->urls = array();

        $params = array(
            '%e' => $queryString
        );

        $this->urls[] = strtr(URL_AERIS_DOMAIN, $params);
    }

    /**
     * @param $queryBatch
     */
    private function _buildBatchUrls($queryBatch)
    {
        $batch_params = array(
            '%l' => implode(',', $queryBatch)
        );

        $params = array(
            '%e' => strtr(API_BATCH_REQUEST, $batch_params)
        );

        $this->urls[] = strtr(URL_AERIS_DOMAIN, $params);
    }

    /**
     *
     */
    private function handleResponses()
    {

        foreach ($this->responses as $response) {

            $response = json_decode($response);

            // Was the batch request a success/fail?
            if ($response->success) {

                // Let's go through the responses...
                foreach ($response->response as $alertData) {
                    $this->data[] = $alertData;
                }
            } else {
                error_log($response->response);
            }
        }
    }

    private function sentence_case($string)
    {
        $sentences = preg_split('/([.?!]+)/', $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $new_string = '';
        foreach ($sentences as $key => $sentence) {
            $new_string .= ($key & 1) == 0 ?
                ucfirst(strtolower(trim($sentence))) :
                $sentence . ' ';
        }

        return trim($new_string);
    }

}