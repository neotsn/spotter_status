<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/21/2014
 * Time: 7:44 PM
 */

session_start();
session_destroy();

/* Redirect to page with the connect to Twitter option. */
header('Location: ./index.php');