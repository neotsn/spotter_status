<?php

/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/20/2014
 * Time: 9:36 PM
 */
class TwitterConnectionInfo
{

    public $consumer_key = '';
    public $consumer_secret = '';
    public $oauth_callback = '';
    public $access_token = '';
    public $access_token_secret = '';
    public $app_twitter_id = '';

    public function __construct()
    {
//        $connection_info = parse_ini_file(PATH_CONNECTION_INFO, true);
        global $connection_info;

        $this->consumer_key = $connection_info['twitter']['CONSUMER_KEY'];
        $this->consumer_secret = $connection_info['twitter']['CONSUMER_SECRET'];
        $this->oauth_callback = $connection_info['twitter']['OAUTH_CALLBACK'];
        $this->access_token = $connection_info['twitter']['ACCESS_TOKEN'];
        $this->access_token_secret = $connection_info['twitter']['ACCESS_TOKEN_SECRET'];
        $this->app_twitter_id = $connection_info['twitter']['TWITTER_APP_USER_ID'];
    }

    public function validateConsumer()
    {
        $result = true;
        if (!$this->consumer_key || !$this->consumer_secret) {
            echo "Twitter API Key Validation Failed on Storm Spotter Status End.";
            exit;
        }

        return $result;
    }
}