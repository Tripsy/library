<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Standard;

use Tripsy\Library\Exceptions\ConfigException;

class StringTools
{
    public static function has_value(string $string, array $lang): string
    {
        return empty($string) ? $lang['no'] : $lang['yes'];
    }

    public static function number($number, $lang)
    {
        //case
        switch ($number) {
            case 0:
                $type = 'zero';
                break;

            case 1:
                $type = 'one';
                break;

            default:
                $type = 'any';
        }

        //return
        return str_replace('{number}', $number, $lang[$type]);
    }

    public static function translate($string, $translate = [])
    {
        /*
            $translate = array(
				'months'   => $this->language->get('general.months'),
				'days'     => $this->language->get('general.days'),
                'anything' => array('this' => 'that', 'white' => 'black')
			)
        */
        //loop
        foreach ($translate as $k => $v) {
            //case
            switch ($k) {
                case 'months':
                    $v = array_combine(
                        array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'),
                        $v
                    );
                    break;

                case 'days':
                    $v = array_combine(
                        array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                        $v
                    );
                    break;
            }

            //replace
            $string = strtr($string, $v);
        }

        //return
        return $string;
    }

    public static function show($string, $default = 'n/a')
    {
        return empty($string) ? $default : $string;
    }

    public static function currency($currency = 'euro')
    {
        //case
        switch ($currency) {
            case 'euro':
                return '&euro;';
                break;

            case 'usd':
                return '$';
                break;

            case 'lei':
                return 'lei';
                break;

            default:
                return null;
        }
    }

    public static function money($amount, $currency = 'euro')
    {
        //return
        return number_format($amount, 2, '.', ',') . ' ' . self::currency($currency);
    }

    public static function replace_brackets($string)
    {
        return str_replace(array('{', '}'), array('&#123;', '&#125;'), $string);
    }

    /**
     * Replace {key} from $string with key / value from $array
     *
     * @param string $string
     * @param array $array
     * @param string $delimiter
     * @return string
     */
    public static function interpolate(string $string, array $array, string $delimiter = '{}'): string
    {
        if ($array) {
            $array_keys = array_keys($array);

            foreach ($array_keys as &$v) {
                $v = $delimiter[0] . $v . $delimiter[1];
            }

            $string = str_replace($array_keys, $array, $string);
        }

        return $string;
    }

    public static function encrypt($string, $vars)
    {
        // vars
        $vars = (object)array_merge(array(
            'length' => 40,
            'prefix' => 'opZ',
            'suffix' => 'KfW',
        ), $vars);

        //vars
        $string = md5($string);
        $string = $vars->prefix . $string . $vars->suffix;
        $string = md5($string);
        $string = strrev($string);
        $string = substr($string, 0, $vars->length);

        //return
        return $string;
    }

    public static function random_code($length, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')
    {
        return substr(str_shuffle($chars), 0, $length);
    }

    public static function javascript_clean($string)
    {
        $string = str_replace('"', '', $string);
        $string = preg_replace('/\s\s+/', '', $string);
        $string = addslashes($string);

        return $string;
    }

    public static function safe_chars($string)
    {
        $array = array(
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A', 'Ä' => 'A', 'Ă' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'Eth',
            'Ñ' => 'N', 'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ș' => 'S', 'Ş' => 'S',
            'Ț' => 'T', 'Ţ' => 'T', 'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ä' => 'a', 'ă' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'eth',
            'ñ' => 'n', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y',
            'ß' => 'sz', 'þ' => 'thorn', 'ÿ' => 'y'
        );

        return strtr($string, $array);
    }

    public static function seo_url($string)
    {
        //vars
        $string = self::safe_chars($string);
        $string = str_replace('-', ' ', $string);
        $string = preg_replace('/[^a-z\d\s]+/s', '', strtolower($string));
        $string = preg_replace("/[\s]+/", '-', trim($string));

        //return
        return $string;
    }

//	public static function index_letter($string, $uppercase = false)
//	{
//		$string = self::safe_chars($string);
//		$string = preg_replace('/[^A-Za-z0-9\s\s+]/', '', $string);
//
//		return $uppercase ? strtoupper($string[0]) : strtolower($string[0]);
//	}

    /**
     * @param string $icon
     * @param string $title
     * @return string
     * @throws ConfigException
     */
    public static function icon(string $icon, string $title = ''): string
    {
        $arr_class = explode(' ', $icon);
        $icon = array_shift($arr_class);

        switch ($icon) {
            case 'link':
                $i = 'fa-link grey';
                break;

            case 'delete':
                $i = 'fa-trash-alt red';
                break;

            case 'edit':
                $i = 'fa-pencil-alt green';
                break;

            case 'active':
            case 'done':
            case 'ok':
            case 'enable':
                $i = 'fa-check-circle green';
                break;

            case 'pending':
                $i = 'fa-key orange';
                break;

            case 'inactive':
            case 'disable':
            case 'cancelled':
                $i = 'fa-stop-circle red';
                break;

            case 'error':
                $i = 'fa-exclamation-triangle red';
                break;

            case 'send':
                $i = 'fa-paper-plane blue';
                break;

            case 'remind':
                $i = 'fa-bullhorn orange';
                break;

            case 'email':
                $i = 'fa-envelope orange';
                break;

            case 'rejected':
                $i = 'fa-thumbs-down orange';
                break;

            case 'expired':
                $i = 'fa-history red';
                break;

            case 'featured':
                $i = 'fa-star';
                break;

            case 'archived':
                $i = 'fa-folder red';
                break;

            case 'calendar':
                $i = 'fa-calendar green';
                break;

            case 'booking':
                $i = 'fa-clipboard green';
                break;

            case 'pause':
                $i = 'fa-pause-circle orange';
                break;

            case 'block':
                $i = 'fa-lock';
                break;

            case 'unblock':
            case 'available':
                $i = 'fa-unlock';
                break;

            case 'user-add':
                $i = 'fa-user-plus';
                break;

            case 'user-cancel':
                $i = 'fa-user-minus';
                break;

            case 'user-done':
                $i = 'fa-user-check';
                break;

            case 'scheduled':
                $i = 'fa-clock orange';
                break;

            default:
                throw new ConfigException('Icon type (eg: ' . $icon . ') not defined');
        }

        if ($arr_class) {
            $i .= ' ' . implode(' ', $arr_class);
        }

        return '<i class="fas ' . $i . '" ' . ($title ? ' title="' . $title . '"' : null) . '></i>';
    }

    public static function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    /**
     * Return true for true, 'true', 1, '1'
     *
     * @param $value
     * @return bool
     * @throws ConfigException
     */
    public static function isTrue($value): bool
    {
        $type = gettype($value);

        switch ($type) {
            case 'NULL':
                return false;
                break;
            case 'boolean':
                return $value;
                break;
            case 'integer':
                if ($value == 1) {
                    return true;
                }
                break;
            case 'string':
                if ($value == '1' || $value === 'true') {
                    return true;
                }
                break;
            default:
                throw new ConfigException('Invalid value type provided (eg: ' . $type . ')');
                break;
        }

        return false;
    }
}
