<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use PDO;
use PDOException;
use PDOStatement;
use Tripsy\Library\Database\BuilderSql;
use Tripsy\Library\Database\BuilderSqlData;
use Tripsy\Library\Database\Connection;
use Tripsy\Library\Database\ConnectionMysql;
use Tripsy\Library\Database\DatabaseException;
use Tripsy\Library\Exceptions\ConfigException;

class Sql extends BuilderSql
{
    /**
     * @var array
     */
    private static array $items = [];

    /**
     * Construct param / Config object / Used by debug function
     *
     * @var Config
     */
    private Config $cfg;

    /**
     * Construct param / builder data object
     *
     * @var BuilderSqlData
     */
    private BuilderSqlData $data;

    /**
     * Construct param / MySql connection object
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * PDO connection
     *
     * @var PDO
     */
    private PDO $instance;

    /**
     * PDO prepared statement
     *
     * @var PDOStatement
     */
    private PDOStatement $resource;

    /**
     * @param string $ident
     * @param Config $cfg
     * @param BuilderSqlData $data
     * @throws DatabaseException
     */
    protected function __construct(string $ident, Config $cfg, BuilderSqlData $data)
    {
        $this->cfg = $cfg;
        $this->data = $data;
        $this->connection = new ConnectionMysql($this->cfg->get('mysql.' . $ident));

        $this->instance = $this->connection->connect();

        parent::__construct($this->data);
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }

    /**
     * @param string $ident
     * @param Config $cfg
     * @param BuilderSqlData $data
     * @return static
     * @throws DatabaseException
     */
    public static function init(string $ident, Config $cfg, BuilderSqlData $data): self
    {
        if (!array_key_exists($ident, self::$items)) {
            self::$items[$ident] = new self($ident, $cfg, $data);
        }

        return self::$items[$ident];
    }

    /**
     * Reset builder data properties values
     *
     * @return Sql
     * @throws ConfigException
     */
    public function reset(): self
    {
        $this->data->reset();

        return $this;
    }

    /**
     * Save builder data properties values as array
     *
     * @param array $ignore List with keys to be ignored
     * @return array
     * @throws ConfigException
     */
    public function save(array $ignore = []): array
    {
        return array_diff_key($this->data->list(), array_flip($ignore));
    }

    /**
     * Load builder data properties values from array
     *
     * @param array $data
     * @return Sql
     * @throws ConfigException
     */
    public function load(array $data): self
    {
        $this->data->load($data);

        return $this;
    }

    /**
     * Select query - multiple results
     *
     * @param bool $object
     * @return array
     * @throws DatabaseException
     * @throws ConfigException
     */
    public function select(bool $object = true): array
    {
        $this->execute('select');

        if ($object === true) {
            return $this->resource->fetchAll(PDO::FETCH_CLASS);
        } else {
            return $this->resource->fetchAll();
        }
    }

    /**
     * Count select query results
     *
     * @return int
     * @throws DatabaseException
     * @throws ConfigException
     */
    public function count(): int
    {
        $this->execute('select_count');

        $res = $this->resource->fetch();

        return $res ? reset($res) : 0;
    }

    /**
     * Select query - single result
     *
     * @param string $fetch
     * @return mixed
     * @throws DatabaseException
     * @throws ConfigException
     */
    public function row(string $fetch = 'obj'): mixed
    {
        $this->limit(1);

        $this->execute('select');

        if ($fetch == 'assoc') {
            return $this->resource->fetch(PDO::FETCH_ASSOC);
        } else {
            return $this->resource->fetch(PDO::FETCH_OBJ);
        }
    }

    /**
     * Insert query
     *
     * @param bool $return
     * @return int
     * @throws DatabaseException
     * @throws ConfigException
     */
    public function insert(bool $return = false): int
    {
        $this->execute('insert');

        if ($return) {
            return $this->instance->lastInsertId();
        }

        return 0;
    }

    /**
     * Update query
     *
     * @param bool $return
     * @return int
     * @throws DatabaseException
     * @throws ConfigException
     */
    public function update(bool $return = false): int
    {
        $this->execute('update');

        if ($return) {
            return $this->resource->rowCount();
        }

        return 0;
    }

    /**
     * Delete query
     *
     * @param bool $return
     * @return int
     * @throws DatabaseException
     * @throws ConfigException
     */
    public function delete(bool $return = false): int
    {
        $this->execute('delete');

        if ($return) {
            return $this->resource->rowCount();
        }

        return 0;
    }

    /**
     * Run query
     *
     * @return void
     * @throws DatabaseException
     * @throws ConfigException
     */
    public function run(): void
    {
        $this->execute('run');
    }

    /**
     * Execute same query for multiple sets of data / Reset builder / Debug
     *
     * @param array $data
     * @return void
     * @throws DatabaseException
     * @throws ConfigException
     */
    public function prepare(array $data)
    {
        $debugStartTime = microtime(true);

        $query = $this->queryBuild('run');

        try {
            $this->resource = $this->instance->prepare($query);

            foreach ($data as $bind) {
                foreach ($bind as $key => $value) {
                    $this->resource->bindValue(':' . $key, ($value ?: (is_int($value) ? 0 : null)), (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
                }

                $this->resource->execute();

                $this->debugOutput($debugStartTime, 'run', $query, []);
                $this->reset();
            }
        } catch (PDOException $e) {
            $message = array(
                'error' => '<em>' . $e->getMessage() . '</em>',
                'query' => $query,
                'data' => $data
            );

            throw new DatabaseException('<pre>' . print_r($message, true) . '</pre>');
        }
    }

    /**
     * Execute query / Reset builder / Debug
     *
     * @param string $type
     * @return void
     * @throws DatabaseException
     * @throws ConfigException
     */
    private function execute(string $type): void
    {
        $debugStartTime = microtime(true);

        $query = $this->queryBuild($type);
        $bind = $this->data->get('bind');

        try {
            $this->resource = $this->instance->prepare($query);

            foreach ($bind as $key => $value) {
                $this->resource->bindValue(':' . $key, ($value ?: (is_int($value) ? 0 : null)), (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
            }

            $this->resource->execute();

            $this->debugOutput($debugStartTime, $type, $query, $bind);
            $this->reset();
        } catch (PDOException $e) {
            $message = array(
                'error' => '<em>' . $e->getMessage() . '</em>',
                'query' => $query,
                'bind' => $bind
            );

            throw new DatabaseException('<pre>' . print_r($message, true) . '</pre>');
        }
    }

    /**
     * Debug query
     *
     * @param string $query
     * @param array $bind
     * @return string
     */
    public function debugQuery(string $query, array $bind): string
    {
        $label = '';

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($trace as $t) {
            if ($t['file'] == __FILE__) {
                continue;
            }

            $label = str_replace($this->cfg->get('folder.root'), '', $t['file']) . '::' . $t['line'];

            break;
        }

        $output = '<strong>' . $label . '</strong> ~ ' . $query;

        if (empty($bind)) {
            return $output;
        }

        $search = [];
        $replace = [];

        foreach ($bind as $t_key => $t_value) {
            $search[] = ':' . $t_key;
            $replace[] = "'$t_value'";
        }

        return str_replace($search, $replace, $output);
    }

    /**
     * Write debug output
     *
     * @param float $startTime
     * @param string $type
     * @param string $query
     * @param array $bind
     * @return void
     */
    private function debugOutput(float $startTime, string $type, string $query, array $bind): void
    {
        if ($this->cfg->get('debug.sql') === false) {
            return;
        }

        $sql_time = microtime(true) - $startTime;
        $sql_count = $this->cfg->get('debug.sql_count') + 1;
        $sql_output = $this->cfg->get('debug.sql_output');
        $sql_output .= $sql_count . ') ' . $this->debugQuery($query, $bind);
        $sql_output .= ($sql_time > $this->cfg->get('debug.sql_time')) ? ' (<strong>' . $sql_time . ' seconds</strong>)' : ' (' . $sql_time . ' seconds)';

        switch ($type) {
            case 'select':
                $sql_output .= ' ----> ' . $this->resource->rowCount() . ' result(s)';
                break;

            case 'update':
                $sql_output .= ' ----> ' . $this->resource->rowCount() . ' affected row(s)';
                break;

            case 'delete':
                $sql_output .= ' ----> ' . $this->resource->rowCount() . ' deleted row(s)';
                break;
        }

        $sql_output .= '<br />';

        //cfg -> update
        $this->cfg->set('debug.sql_count', $sql_count);
        $this->cfg->set('debug.sql_output', $sql_output);
    }

    public function debug()
    {
        dump($this->cfg->get('debug.sql_output'));
    }
}
