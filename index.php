<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/20/2014
 * Time: 9:32 PM
 */

/**
 * @file
 * Main splash page welcoming users and prompting them to sign in with Twitter.
 * For twitter connection:
 *        Check if consumer token is set and if so send User to get a request token.
 */

session_start();
define('PATH_ROOT', './');
require_once('config.php');

$session_id = get_session('sid', null);
$user_id = get_session('userid', null);
$msg = get_request('msg', null);

if ($user_id && $session_id) {
    $db = new DbPdo();
    $user = new User($user_id);
    $user->validateUserSession();
    header('Location: ./profile.php');
}

$template = new Template('splash');
$template->display();