<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Cache;

abstract class Cache
{
    /**
     * Return cache content if found, otherwise save and return content
     *
     * @param callable $callback
     * @return mixed
     */
    public function output(callable $callback): mixed
    {
        if ($this->found() === true) {
            $output = $this->load();
        } else {
            $output = call_user_func($callback);

            $this->save($output);
        }

        return $output;
    }

    /**
     * Check if cache is found based on `cache_ident`
     *
     * @return bool
     */
    abstract protected function found(): bool;

    /**
     * Save cache content
     *
     * @param mixed $content
     * @return void
     */
    abstract protected function save(mixed $content): void;

    /**
     * Load cache content
     *
     * @return string|array
     */
    abstract protected function load(): mixed;

    /**
     * Remove cache if exist
     *
     * @return void
     */
    abstract public function remove(): void;

    /**
     * Remove all cached records
     *
     * @return void
     */
    abstract public function clear(): void;
}
