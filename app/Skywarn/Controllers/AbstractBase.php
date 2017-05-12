<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/12/2017
 * Time: 15:43
 */

namespace Skywarn\Controllers;

/**
 * Class AbstractBase
 * @package Skywarn\Controllers
 */
abstract class AbstractBase implements ControllerInterface
{
    protected
        $method,
        $url;

    /**
     * AbstractBase constructor.
     *
     * @param string $method
     * @param string $url
     */
    public function __construct($method, $url)
    {
        $this->method = $method;
        $this->url = $url;
    }

    /**
     * Attempt to execute the function named in the local $method property
     */
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