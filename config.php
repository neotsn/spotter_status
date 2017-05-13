<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/21/2014
 * Time: 9:27 PM
 */

require_once PATH_ROOT . '/defines.php';
require_once PATH_ROOT . '/vendor/autoload.php';

spl_autoload_register(
    function ($className) {

        $className = ltrim($className, '\\');
        $fileName = '';

        if ($lastNamespacePosition = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNamespacePosition);
            $className = substr($className, $lastNamespacePosition + 1);
            $fileName = str_replace('\\', '/', $namespace) . '/';
        }

        $fileName .= str_replace('_', '/', $className) . '.php';

        if (file_exists(PATH_APP . $fileName)) {
            include PATH_APP . $fileName;
        }
    }
);

/*
 * Set up the connection info for global use
 */
$connection_info = parse_ini_file(PATH_CONNECTION_INFO, true);
