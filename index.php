<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/20/2014
 * Time: 9:32 PM
 */

use Skywarn\Framework\Handlers\Request;
use Skywarn\Framework\Handlers\View;

session_start();
define('PATH_ROOT', dirname(__FILE__));
require_once(PATH_ROOT . '/config.php');

$cprv = View::getRequestValue('cprv', '/index');

$requestHandler = new Request($cprv);
$requestHandler->getControllerMethod();
$requestHandler->runController();
