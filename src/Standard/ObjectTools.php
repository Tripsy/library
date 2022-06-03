<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Standard;

use Ds\Map;
use Tripsy\Library\Exceptions\ConfigException;

class ObjectTools
{
    /**
     * @param array $array
     * @param array $settings
     * @return Map
     * @throws ConfigException
     */
    public static function data(array $array, array $settings = []): Map
    {
        $data = new Map($array);
        $settings = new Map($settings);

        $extra_keys = $data->diff($settings)->keys()->join(', ');

        if ($extra_keys) {
            throw new ConfigException('Extra map keys found (eg: ' . $extra_keys . ')');
        }

        foreach ($settings as $key => $type) {
            $is_mandatory = true;

            if (str_starts_with($type, '?')) {

                $is_mandatory = false;
                $type = ltrim($type, '?');
            }

            $is_found = $data->hasKey($key);

            if ($is_mandatory === true && $is_found === false) {
                throw new ConfigException('Map key (eg: ' . $key . ') does not exist');
            }

            if ($is_found === true) {
                $value = $data->get($key);

                if ($is_mandatory === true && empty($value)) {
                    throw new ConfigException('Key value (eg: ' . $key . ' [' . $type . ']) is not set');
                }

                if (get_debug_type($data->get($key)) != $type) {
                    throw new ConfigException('Map key (eg: ' . $key . ') is not ' . $type);
                }
            }
        }

        return $data;
    }
}
