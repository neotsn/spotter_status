<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 5/12/2017
 * Time: 13:25
 */

namespace Skywarn\Framework\Handlers;

class View
{

    public static function returnPage($template, array $data = array())
    {
        echo self::returnView(T_BASE_HEADER);
        echo self::returnView($template, $data);

        $now = new \DateTime('now', new \DateTimeZone('America\Chicago'));
        echo self::returnView(T_BASE_FOOTER, array(
            'TXT_YEAR' => $now->format('Y')
        ));
    }

    /**
     * @param string $template
     * @param array  $data Used in the INCLUDED file
     *
     * @return string
     */
    public static function returnView($template, array $data = array())
    {
        $output = '';
        $viewPath = PATH_VIEWS . $template . '.view';

        if (file_exists($viewPath)) {

            ob_start();
            include $viewPath;
            $output = ob_get_clean();
        } else {
            error_log('View does not exist: ' . $viewPath);
        }

        return $output;
    }

    /**
     * Helper to pull an array value or default
     *
     * @param array $arr
     * @param mixed $field
     * @param mixed $default
     *
     * @return mixed
     */
    public static function getArrayValue(array $arr, $field, $default = '')
    {
        return (isset($arr[$field])) ? $arr[$field] : $default;
    }

    /**
     * Helper to pull Request array value or default
     *
     * @param mixed  $field
     * @param mixed  $default
     * @param string $type Sanitization type
     *
     * @return mixed
     */
    public static function getRequestValue($field, $default = '', $type = 'string')
    {
        $result = self::getArrayValue($_REQUEST, $field, $default);

        return self::sanitizeValue($type, $result);
    }

    /**
     * Helper to pull Session array value or default
     *
     * @param mixed  $field
     * @param string $default
     * @param string $type
     *
     * @return mixed
     */
    public static function getSessionValue($field, $default = '', $type = 'string')
    {
        $result = self::getArrayValue($_SESSION, $field, $default);

        return self::sanitizeValue($type, $result);
    }

    /**
     * Use REGEX to replace any value not specifically expected in the response
     *  - 'string' remove anything that is not alphanumeric or whitespace
     *  - 'number' removes anything that is not a decimal or digit
     *
     * @param $type
     * @param $value
     *
     * @return mixed
     */
    public static function sanitizeValue($type, $value)
    {
        switch ($type) {
            case 'string':
                $value = preg_replace('/[^\w\s]/', '', $value);
                break;
            case 'number':
                $value = preg_replace('/[^\d\.]/', '', $value);
                break;
        }

        return $value;
    }
}