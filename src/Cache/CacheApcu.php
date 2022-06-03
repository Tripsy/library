<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Cache;

use Tripsy\Library\Exceptions\ConfigException;

class CacheApcu extends Cache
{
    private int $cache_time;
    private string $cache_ident;

    /**
     * @throws ConfigException
     */
    public function __construct(string $cache_ident, int $cache_time)
    {
        $this->cache_time = $cache_time;
        $this->cache_ident = $cache_ident;

        if (extension_loaded('apcu') === false) {
            throw new ConfigException('Apcu extension is not available.');
        }
    }

    /**
     * Check if cache is found based on `cache_ident`
     *
     * @return bool
     */
    protected function found(): bool
    {
        if (apcu_exists($this->cache_ident)) {
            return true;
        }

        return false;
    }

    /**
     * Save cache content
     *
     * @param mixed $content
     * @return void
     */
    protected function save(mixed $content): void
    {
        apcu_store($this->cache_ident, $content, $this->cache_time);
    }

    /**
     * Load cache content
     *
     * @return mixed
     */
    protected function load(): mixed
    {
        return apcu_fetch($this->cache_ident);
    }

    /**
     * Remove cache if found
     *
     * @return void
     */
    public function remove(): void
    {
        if ($this->found() === true) {
            apcu_delete($this->cache_ident);
        }
    }

    /**
     * Remove all cached records
     *
     * @return void
     */
    public function clear(): void
    {
        apcu_clear_cache('user');
    }
}
