<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/12/2017
 * Time: 15:14
 */

namespace Skywarn\Framework\Data;

use Exception;

class WebRequests
{
    /**
     * Create a CURL request to a URL
     *
     * @param string $url URL to request
     * @param int    $endpoints
     *
     * @return mixed CURL response
     */
    protected function getUrlReponse($url, $endpoints = 1)
    {
        $data = null;

        try {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $data = curl_exec($ch);
            curl_close($ch);

            // record the api call
            $this->recordApiCall($url, $endpoints, $data);
        } catch (Exception $e) {
            error_log('CURL Request failed to ' . $url);
            error_log($e->getMessage());
        }

        return $data;
    }

    /**
     * Record the URL requested and the time for diagnostics
     *
     * @param string $url       The url called by the CURL request
     * @param int    $endpoints Number of endpoints called
     * @param string $response  API Call Response
     */
    private function recordApiCall($url, $endpoints, $response)
    {
        global $db;

        $params = array(
            'call_time'           => date('Y-m-d H:i:s', time()),
            'call_url'            => $url,
            'call_endpoints_used' => $endpoints,
            'call_response'       => $response
        );

        $db->insert(TABLE_API_CALLS, $params);
    }
}