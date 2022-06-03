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
use Tripsy\Library\Standard\ObjectTools;

class ConnectionMysql extends Connection
{
    private Map $settings;

    public function __construct(array $settings)
    {
        $this->settings = ObjectTools::data($settings, [
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

//    /**
//     * Return connection setting value based on key
//     *
//     * @param string $key
//     * @return mixed
//     */
//    public function settings(string $key)
//    {
//        return $this->settings->get($key);
//    }

    /**
     * @return PDO
     * @throws DatabaseException
     */
    public function connect(): PDO
    {
        $dbc = [];
        $dbc[] = 'host=' . $this->settings->get('host');
        $dbc[] = 'port=' . $this->settings->get('port');
        $dbc[] = 'dbname=' . $this->settings->get('database');
        $dbc[] = 'charset=' . $this->settings->get('charset');

        try {
            return new PDO('mysql:' . implode(';', $dbc), $this->settings->get('username'), $this->settings->get('password'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true
            ]);
        } catch (PDOException $e) {
            preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);

            throw new DatabaseException('PDO / MySQL error: Could not connect to <strong>' . $this->settings->get('host') . ':' . $this->settings->get('port') . '@' . $this->settings->get('database') . '</strong> using username <strong>' . $this->settings->get('username') . '</strong><br /><br /><em>' . $matches[3]);
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
        if (array_key_exists($name, $this->settings->get('table'))) {
            $name = $this->settings->get('table')[$name];
        }

        return $this->settings->get('prefix') . $name;
    }
}
