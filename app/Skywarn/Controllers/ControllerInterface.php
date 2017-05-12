<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/12/2017
 * Time: 15:44
 */

namespace Skywarn\Controllers;

/**
 * Interface ControllerInterface
 * @package Skywarn\Controllers
 */
interface ControllerInterface
{
    /**
     * ControllerInterface constructor.
     *
     * @param string $method The method to run from the Controller class
     * @param string $url    The initially-requested URL
     */
    public function __construct($method, $url);

    /**
     * @return mixed
     */
    public function execute();

}