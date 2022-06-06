<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Database;

use PDO;
use PDOException;
use PDOStatement;
use Tripsy\Library\Exceptions\ConfigException;

class Sql extends BuilderSql
{
    /**
     * @var array
     */
    private static array $items = [];

    /**
     * Debug output data
     *
     * @var array
     */
    private array $debug_sql_data = [];

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
     * @param Connection $connection
     * @param BuilderSqlData $data
     * @throws DatabaseException
     */
    protected function __construct(Connection $connection, BuilderSqlData $data)
    {
        $this->data = $data;
        $this->connection = $connection;
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
     * @param Connection $connection
     * @param BuilderSqlData $data
     * @return static
     * @throws DatabaseException
     */
    public static function init(string $ident, Connection $connection, BuilderSqlData $data): self
    {
        if (!array_key_exists($ident, self::$items)) {
            self::$items[$ident] = new self($connection, $data);
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

                $this->debugQueryOutput($debugStartTime, 'run', $query, []);
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

            $this->debugQueryOutput($debugStartTime, $type, $query, $bind);
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
    public function debugQueryOutputFormat(string $query, array $bind): string
    {
        $label = '';

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($trace as $t) {
            if ($t['file'] == __FILE__) {
                continue;
            }

            $label = $t['file'] . '::' . $t['line'];

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
    private function debugQueryOutput(float $startTime, string $type, string $query, array $bind): void
    {
        if ($this->connection->debug() === false) {
            return;
        }

        $sql_time = microtime(true) - $startTime;

        $output = [];

        $output['query'] = $this->debugQueryOutputFormat($query, $bind);
        $output['time'] = $this->connection->setQueryFlag($sql_time) ? ' (<strong>' . $sql_time . ' seconds</strong>)' : ' (' . $sql_time . ' seconds)';

        switch ($type) {
            case 'select':
                $output['result'] =  ' ----> ' . $this->resource->rowCount() . ' result(s)';
                break;

            case 'update':
                $output['result'] =  ' ----> ' . $this->resource->rowCount() . ' affected row(s)';
                break;

            case 'delete':
                $output['result'] =  ' ----> ' . $this->resource->rowCount() . ' deleted row(s)';
                break;
            default:
                $output['result'] = '';
                break;
        }

        $this->debug_sql_data[] = $output;
    }

    /**
     * Return data containing debug information about executed sql queries
     *
     * @return array
     */
    public function debugData(): array
    {
        return $this->debug_sql_data;
    }
}
