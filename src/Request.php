<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Ds\Map;
use Tripsy\Library\Exceptions\ConfigException;

class Request
{
    private const METHOD_LIST = ['get', 'post', 'cookie', 'files', 'request'];
    private const TYPE_LIST = array('string', 'array', 'bool', 'int', 'float', 'enum', 'date');

    /**
     * Retrieve fields using REQUEST method
     *
     *
     * @return mixed|object
     * @throws ConfigException
     */
    public static function request()
    {
        return self::grab(func_get_args(), 'request');
    }

    /**
     * Retrieve fields using POST method
     *
     * @return mixed|object
     * @throws ConfigException
     */
    public static function post()
    {
        return self::grab(func_get_args(), 'post');
    }

    /**
     * Retrieve fields using GET method
     *
     * @return mixed|object
     * @throws ConfigException
     */
    public static function get()
    {
        return self::grab(func_get_args(), 'get');
    }

    /**
     * Retrieve fields using COOKIE method
     *
     * @return mixed|object
     * @throws ConfigException
     */
    public static function cookie()
    {
        return self::grab(func_get_args(), 'cookie');
    }

    /**
     * Retrieve fields using FILES method
     *
     * @return mixed|object
     * @throws ConfigException
     */
    public static function files()
    {
        return self::grab(func_get_args(), 'cookie');
    }

    /**
     * Retrieve object name -> value for multiple params; For single param return value
     *
     * @param array $params
     * @param string $method
     * @return false|mixed|object
     * @throws ConfigException
     */
    private static function grab(array $params, string $method)
    {
        $result = [];

        foreach ($params as $v) {
            $attr = self::attr($v, $method);

            $result[$attr->get('name')] = self::value($attr);
        }

        if (count($result) == 1) {
            return current($result);
        }

        return (object)$result;
    }

    /**
     * Determine field attributes
     *
     * @param string $var
     * @param string $method
     * @return Map
     * @throws ConfigException
     */
    private static function attr(string $var, string $method): Map
    {
        preg_match('/([a-zA-Z0-9_]*)(?:\[(.*?)\])?/', $var, $match);

        $attr = new Map([
            'name' => $match[1],
            'method' => $method,
            'type' => 'string', //$this->arr_type
            'enum' => null, //enum values, concatenated with ,
            'format' => null, //format (eg: date format OR regex)
            'default' => null, //default value
            'html' => false, //string is html
            'xss_clean' => true
        ]);

        if (empty($match[2]) === false) {
            $match_arr = explode(',', $match[2]);

            foreach ($match_arr as $m) {
                list($spec, $desc) = array_map('trim', explode('=', $m));

                switch ($spec) {
                    case 'method':
                        if (in_array($desc, self::METHOD_LIST) === false) {
                            throw new ConfigException('Method not available (eg: <strong>' . $desc . '</strong> for <strong>' . $var . '</strong>)');
                        }

                        $attr->put('method', $desc);
                        break;

                    case 'type':
                        if (in_array($desc, self::TYPE_LIST) === false) {
                            throw new ConfigException('Type not available (eg: <strong>' . $desc . '</strong> for <strong>' . $var . '</strong>)');
                        }

                        $attr->put('type', $desc);
                        break;

                    case 'enum':
                        $attr->put('type', 'enum');
                        $attr->put('enum', array_map('trim', explode('/', $desc)));
                        break;

                    case 'default':
                        $attr->put('default', $desc);
                        break;

                    case 'html':
                        if ($desc == 'true') {
                            $attr->put('type', 'string');
                            $attr->put('html', true);
                        }
                        break;

                    case 'xss_clean':
                        if ($desc == 'false') {
                            $attr->put('xss_clean', false);
                        }
                        break;
                }
            }
        }

        //clean up for bool, int, float, enum doesn't make sense
        if (in_array($attr['type'], array('string', 'array')) === false) {
            $attr->put('xss_clean', false);
        }

        return $attr;
    }

    /**
     * Return field value
     *
     * @param Map $attr
     * @return array|bool|float|int|mixed|string|string[]|null
     */
    private static function value(Map $attr)
    {
        if ($attr->get('method') == 'files') {
            return $_FILES[$attr->get('name')] ?? null;
        }

        $value = $attr->get('default');

        switch ($attr->get('method')) {
            case 'request':
                $value = $_REQUEST[$attr->get('name')] ?? $value;
                break;

            case 'post':
                $value = $_POST[$attr->get('name')] ?? $value;
                break;

            case 'get':
                $value = $_GET[$attr->get('name')] ?? $value;
                break;

            case 'cookie':
                $value = $_COOKIE[$attr->get('name')] ?? $value;
                break;
        }

        switch ($attr->get('type')) {
            case 'bool':
                $value = (bool)$value;
                break;

            case 'int':
                $value = (int)$value;
                break;

            case 'float':
                $value = (float)$value;
                break;

            case 'enum':
                $value = in_array($value, $attr->get('enum')) ? $value : '';
                break;

            case 'date':
                if ($attr->hasValue('format') === false) {
                    $attr->put('format', 'Y-m-d');
                }

                $d = \DateTime::createFromFormat($attr->get('format'), $value);

                $value = $d && $d->format($attr->get('format')) == $value ? $value : '';
                break;

            default:
                if ($attr->get('type') == 'array') {
                    $value = (array)$value;
                } elseif ($attr->get('type') == 'string') {
                    $value = (string)$value;
                }

                if ($value) {
                    $value = $attr->get('html') === true ? $value : self::striptags($value);
                    $value = self::trim($value);
                    $value = self::stripslashes($value);
                    $value = $attr->get('xss_clean') === true ? (new RequestClean())->xss_clean($value) : $value;
                }
        }

        return $value;
    }

    /**
     * @param $value
     * @return array|string
     */
    private static function stripslashes($value)
    {
        if (is_string($value)) {
            return stripslashes($value);
        } elseif (is_array($value)) {
            return array_map('self::stripslashes', $value);
        }

        return $value;
    }

    /**
     * Remove html tags
     *
     * @param $value
     * @return array|string
     */
    private static function striptags($value)
    {
        if (is_string($value)) {
            return strip_tags($value);
        } elseif (is_array($value)) {
            return array_map('self::striptags', $value);
        }

        return $value;
    }

    /**
     * Trim value
     *
     * @param $value
     * @return array|string
     */
    private static function trim($value)
    {
        if (is_string($value)) {
            return trim($value);
        } elseif (is_array($value)) {
            return array_map('self::trim', $value);
        }

        return $value;
    }
}
