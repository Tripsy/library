<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Standard;

class ArrayDot
{
    /**
     * Get an item from an array using "dot" notation
     *
     * @param array $array
     * @param string $key
     * @param $default
     * @return array|mixed|null
     */
    public static function get(array $array, string $key, $default = null)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Set an array item to a given value using 'dot' notation
     *
     * @param array $array
     * @param string $key
     * @param $value
     * @return void
     */
    public static function set(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = array();
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Push to an item, if is array, a value using "dot" notation
     *
     * @param array $array
     * @param string $key
     * @param $value
     * @return void
     */
    public static function push(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 0) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                return;
            }

            $array = &$array[$key];
        }

        $array[] = $value;
    }

    /**
     * Add an element to an array using 'dot' notation if it doesn't exist.
     *
     * @param array $array
     * @param string $key
     * @param $value
     * @return bool
     */
    public static function add(array $array, string $key, $value): bool
    {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);

            return true;
        }

        return false;
    }

    /**
     * Check if an item or items exist in an array using 'dot' notation
     *
     * @param array $array
     * @param string $key
     * @return bool
     */
    public static function has(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        }

        $subArray = $array;

        foreach (explode('.', $key) as $segment) {
            if (array_key_exists($segment, $subArray)) {
                $subArray = $subArray[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove an item by key if exist in an array using 'dot' notation
     *
     * @param array $array
     * @param string $key
     * @return bool
     */
    public static function remove(array &$array, string $key): bool
    {
        if (isset($array[$key])) {
            unset($array[$key]);

            return true;
        }

        $parts = explode('.', $key);
        $reference = &$array;

        while (count($parts) > 1) {
            $part = array_shift($parts);

            if (array_key_exists($part, $reference) && is_array($reference[$part])) {
                $reference = &$reference[$part];
            }
        }

        $last = array_pop($parts);

        if (isset($reference[$last])) {
            unset($reference[$last]);

            return true;
        }

        return false;
    }
}
