<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Tripsy\Library\Standard\ArrayDot;

class Config
{
    private array $config;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->config = $data;
    }

    /**
     * @param string $var
     * @param $default
     * @return mixed
     */
    public function get(string $var, $default = null): mixed
    {
        return ArrayDot::get($this->config, $var, $default);
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function set($key, $value = null): void
    {
        $keys = is_array($key) ? $key : array($key => $value);

        foreach ($keys as $key => $value) {
            ArrayDot::set($this->config, $key, $value);
        }
    }

    /**
     * @param mixed $key
     * @param $value
     * @return void
     */
    public function push(mixed $key, $value = null): void
    {
        $keys = is_array($key) ? $key : array($key => $value);

        foreach ($keys as $key => $value) {
            ArrayDot::push($this->config, $key, $value);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return ArrayDot::has($this->config, $key);
    }
}
