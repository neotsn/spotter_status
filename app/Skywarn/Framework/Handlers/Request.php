<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/12/2017
 * Time: 13:24
 */

namespace Skywarn\Framework\Handlers;

use Skywarn\Controllers\AbstractBase;

/**
 * Class Request
 * @package Skywarn\Framework\Handlers
 */
class Request
{
    private
        $controller,
        $method,
        $url;

    /**
     * Request constructor.
     *
     * @param $cprv
     */
    public function __construct($cprv)
    {
        $this->url = $cprv;
        $this->controller = '\Skywarn\Controllers\Root';
        $this->method = '';
    }

    /**
     * Find a controller-method pair based on the URL parts, working from the right-most segment
     */
    public function getControllerMethod()
    {
        $urlParts = explode('/', preg_replace_callback('/\-(.?)/', function ($matches) {
            return strtoupper($matches[1]);
        }, $this->url));

        $urlParts = array_map(function ($part) {
            return ucwords($part);
        }, $urlParts);

        if (count($urlParts) > 1) {

            for ($i = 1; $i < count($urlParts); $i++) {

                $controller = sprintf('\\Skywarn\\Controllers\\%s', implode('\\', array_slice($urlParts, 0, -1)));

                if (class_exists($controller)) {
                    $method = implode('_', array_slice($urlParts, -1));

                    $this->method = 'do' . $method;
                    $this->controller = $controller;
                    break;
                }
            }
        } else {
            $this->method = 'do' . ucwords($urlParts[0]);
        }
    }

    /**
     * Try to execute the requested method on the deduced Controller class
     */
    public function runController()
    {
        if (method_exists($this->controller, $this->method)) {

            /** @var AbstractBase $controller */
            $controller = new $this->controller($this->method, $this->url);
            $controller->execute();
            exit;
        } else {
            // 404
        }
    }
}