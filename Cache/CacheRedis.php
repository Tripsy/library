<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 * https://github.com/phpredis/phpredis
 *
 */

namespace Tripsy\Library\Cache;

use Redis;

class CacheRedis extends Cache
{
    private int $cache_time;
    private string $cache_ident;
    private Redis $redis;


    public function __construct(string $cache_ident, int $cache_time, Redis $redis)
    {
        $this->cache_time = $cache_time;
        $this->cache_ident = $cache_ident;

        $this->redis = $redis;
    }

    /**
     * Check if cache exist based on `cache_ident`
     *
     * @return bool
     */
    protected function found(): bool
    {
        if ($this->redis->exists($this->cache_ident)) {
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
        $this->redis->set($this->cache_ident, json_encode($content), $this->cache_time);
    }

    /**
     * Load cache content
     *
     * @return mixed
     */
    protected function load(): mixed
    {
        $content = $this->redis->get($this->cache_ident);

        return json_decode($content, true);
    }

    /**
     * Remove cache if exist
     *
     * @return void
     */
    public function remove(): void
    {
        $this->redis->unlink($this->cache_ident);
    }

    /**
     * Remove all cached records
     *
     * @return void
     */
    public function clear(): void
    {
        $this->redis->flushAll();
    }
}
