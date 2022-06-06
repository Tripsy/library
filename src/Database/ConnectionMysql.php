<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Database;

use Ds\Map;
use PDO;
use PDOException;
use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Standard\ObjectTools;

class ConnectionMysql implements Connection
{
    private Map $config;

    /**
     * @throws ConfigException
     */
    public function __construct(array $settings)
    {
        $this->config = ObjectTools::data($settings, [
            'debug' => 'bool',
            'query_time' => 'float',
            'host' => 'string',
            'port' => 'int',
            'database' => 'string',
            'username' => 'string',
            'password' => 'string',
            'charset' => 'string',
            'prefix' => '?string',
            'table' => 'array'
        ]);
    }

    /**
     * @return PDO
     * @throws DatabaseException
     */
    public function connect(): PDO
    {
        $dbc = [];
        $dbc[] = 'host=' . $this->config->get('host');
        $dbc[] = 'port=' . $this->config->get('port');
        $dbc[] = 'dbname=' . $this->config->get('database');
        $dbc[] = 'charset=' . $this->config->get('charset');

        try {
            return new PDO('mysql:' . implode(';', $dbc), $this->config->get('username'), $this->config->get('password'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true
            ]);
        } catch (PDOException $e) {
            preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);

            throw new DatabaseException('PDO / MySQL error: Could not connect to <strong>' . $this->config->get('host') . ':' . $this->config->get('port') . '@' . $this->config->get('database') . '</strong> using username <strong>' . $this->config->get('username') . '</strong><br /><br /><em>' . $matches[3]);
        }
    }

    /**
     * Return table name based on settings
     *
     * @param string $name
     * @return string
     */
    public function table(string $name): string
    {
        if (array_key_exists($name, $this->config->get('table'))) {
            $name = $this->config->get('table')[$name];
        }

        return $this->config->get('prefix') . $name;
    }

    /**
     * Return true if debug is on
     *
     * @return bool
     */
    public function debug(): bool
    {
        return $this->config->get('debug');
    }

    /**
     * If query execution time exceed debug time return true
     *
     * @param float $executionTime
     * @return float
     */
    public function setQueryFlag(float $executionTime): float
    {
        return $executionTime > $this->config->get('query_time');
    }
}
