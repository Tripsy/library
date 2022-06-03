<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Cache;

use Redis;
use RedisException;
use Tripsy\Library\Config;
use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Exceptions\SystemException;

class RedisConnection
{
    private static RedisConnection $instance;
    private Config $cfg;
    private Redis $redis;

    protected function __construct(Config $cfg)
    {
        if (extension_loaded('redis') === false) {
            throw new ConfigException('Redis extension is not available.');
        }

        $this->cfg = $cfg;

        $this->connect();
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }

    /**
     * @param Config $cfg
     * @return RedisConnection
     * @throws ConfigException
     */
    public static function init(Config $cfg): self
    {
        if (isset(self::$instance) === false) {
            self::$instance = new self($cfg);
        }

        return self::$instance;
    }

    /**
     * Establish redis connection
     *
     * @return void
     * @throws SystemException
     */
    private function connect(): void
    {
        try {
            $this->redis = new Redis();

            $this->redis->pconnect($this->cfg->get('redis.host'), $this->cfg->get('redis.port'));

            if ($this->cfg->has('redis.auth')) {
                $this->redis->auth($this->cfg->get('redis.auth'));
            }
        } catch (RedisException $e) {
            throw new SystemException('Redis connection cannot be established.');
        }
    }

    /**
     * Get redis object
     *
     * @return Redis
     */
    public function get(): Redis
    {
        return $this->redis;
    }
}
