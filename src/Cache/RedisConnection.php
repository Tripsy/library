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
use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Exceptions\SystemException;
use Tripsy\Library\Standard\ObjectTools;

class RedisConnection
{
    private static RedisConnection $instance;
    private Redis $redis;

    /**
     * @throws ConfigException
     * @throws SystemException
     */
    protected function __construct(array $settings)
    {
        if (extension_loaded('redis') === false) {
            throw new ConfigException('Redis extension is not available.');
        }

        $config = ObjectTools::data($settings, [
            'host' => 'string',
            'port' => 'int',
            'auth' => '?array'
        ]);

        $this->connect(
            $config->get('host'),
            $config->get('port'),
            $config->get('auth')
        );
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }

    /**
     * @param array $settings
     * @return RedisConnection
     * @throws ConfigException
     * @throws SystemException
     */
    public static function init(array $settings): self
    {
        if (isset(self::$instance) === false) {
            self::$instance = new self($settings);
        }

        return self::$instance;
    }

    /**
     * Establish redis connection
     *
     * @param string $host
     * @param int $port
     * @param array $auth
     * @return void
     * @throws SystemException
     */
    private function connect(string $host, int $port, array $auth): void
    {
        try {
            $this->redis = new Redis();

            $this->redis->pconnect($host, $port);

            if ($auth) {
                $this->redis->auth($auth);
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
