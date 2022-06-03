<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Standard;

class ArrayTools
{
    public static function has_string_keys(array $array): bool
    {
        foreach ($array as $k => $v) {
            if (is_string($k) === false) {
                return false;
            }
        }

        return true;
    }

    //find key by position
    public static function key_by_index(array $array, $pos = 0)
    {
        return key(array_slice($array, $pos, 1, true));
    }

    //clean array
    public static function clean($array, $settings = [])
    {
        //default vars
        $settings = array_merge(array(
            'empty' => true,
            'duplicate' => true,
            'strtolower' => true,
            'trim' => true
        ), $settings);

        $array = array_map('trim', $array);

        if ($settings['strtolower']) $array = array_map('strtolower', $array);
        if ($settings['duplicate']) $array = array_unique($array);
        if ($settings['empty']) $array = array_filter($array);

        //return
        return $array;
    }

    //merge multi-dimensional array by key (note: $combine overwrite $array)
    public static function merge(array $array, array $combine)
    {
        foreach ($combine as $key => $value) {
            if (is_array($value) && isset($array[$key]) && is_array($array[$key])) {
                $array[$key] = self::merge($array[$key], $value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * @param array $array
     * @param string $value
     *
     * @return array
     */
    public static function sortByValue(array $array, string $value): array
    {
        usort($array, function ($a, $b) use ($value) {
            return $a[$value] <=> $b[$value];
        });

        return $array;
    }
}
