<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Tripsy\Library\Standard\ArrayDot;

class Session
{
    private static Session $instance;

    protected function __construct()
    {
        session_start();

        if (rand(1, 5) == 1) {
            session_regenerate_id();
        }
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }

    public static function init(): Session
    {
        if (isset(self::$instance) === false) {
            self::$instance = new static();
        }

        return static::$instance;
    }

    public function get($var, $default = '')
    {
        if (ArrayDot::has($_SESSION, $var)) {
            return ArrayDot::get($_SESSION, $var);
        } else {
            return $default;
        }
    }

    public function set(string $key, $value): void
    {
        ArrayDot::set($_SESSION, $key, $value);
    }

    public function has($key): bool
    {
        return ArrayDot::has($_SESSION, $key);
    }

    public function remove($key): bool
    {
        return ArrayDot::remove($_SESSION, $key);
    }
}
