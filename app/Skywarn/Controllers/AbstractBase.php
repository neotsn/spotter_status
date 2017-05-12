<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/12/2017
 * Time: 15:43
 */

namespace Skywarn\Controllers;

abstract class AbstractBase implements ControllerInterface
{
    protected
        $method,
        $url;

    public function __construct($method, $url)
    {
        $this->method = $method;
        $this->url = $url;
    }

    public function execute()
    {
        try {
            $this->{$this->method}();
        } catch (\Exception $e) {
            error_log($this->method);
            error_log($e->getCode() . ' :: ' . $e->getMessage() . '[49S8D4F]');
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        }
    }

}