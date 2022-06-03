<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Database;

use Tripsy\Library\Exceptions\ConfigException;

abstract class BuilderSql
{
    /**
     * @var BuilderSqlData
     */
    private BuilderSqlData $data;

    /**
     * @param BuilderSqlData $data
     */
    public function __construct(BuilderSqlData $data)
    {
        $this->data = $data;
    }

    /**
     * Convert column name from `u.email` to `email` to be used as placeholder
     *
     * @param string $column
     * @return string
     */
    private function stripColumnAlias(string $column): string
    {
        $pos = strpos($column, '.');

        if ($pos !== false) {
            return substr($column, $pos + 1);
        } else {
            return $column;
        }
    }

    /**
     * Set custom query expression
     *
     * @param string $expression
     * @return $this
     * @throws ConfigException
     */
    public function query(string $expression): self
    {
        $this->data->set('query', $expression);

        return $this;
    }

    /**
     * Get table name based on settings and add alias
     *
     * @param string $name
     * @param string $alias
     * @return string
     */
    public function queryTable(string $name, string $alias = ''): string
    {
        return $this->connection->table($name) . ($alias ? ' AS ' . $alias : '');
    }

    /**
     * Set table name for queries like select, update, insert, delete
     *
     * @param string $name
     * @param string $alias
     * @return $this
     * @throws ConfigException
     */
    public function table(string $name, string $alias = ''): self
    {
        $this->data->set('table', $this->queryTable($name, $alias));

        return $this;
    }

    /**
     * Create join entry for query
     *
     * @param string $table
     * @param string $alias
     * @param string $condition
     * @param string $mode
     * @return $this
     * @throws ConfigException
     */
    public function join(string $table, string $alias, string $condition, string $mode = 'INNER JOIN'): self
    {
        $this->data->push('join', $mode . ' ' . $this->queryTable($table, $alias) . ' ' . $condition);

        return $this;
    }

    /**
     * Bind column for select query
     *
     * @param $data
     * @param array $bind
     * @return $this
     * @throws ConfigException
     */
    public function column($data, array $bind = []): self
    {
        if (is_string($data)) {
            $data = array_map('trim', explode(',', $data));
        }

        $this->data->merge('column', $data);

        $this->bind($bind);

        return $this;
    }

    /**
     * Bind where clause
     *
     * @param $data
     * @param null $value
     * @param string $condition
     * @return $this
     * @throws ConfigException
     */
    public function where($data, $value = null, string $condition = '='): self
    {
        if (is_array($data)) {
            foreach ($data as $column => $value) {
                $this->whereColumn($column, $value, $condition);
            }
        } else {
            $this->whereColumn($data, $value, $condition);
        }

        return $this;
    }

    /**
     * Push where column condition and update bind
     *
     * @param string $column
     * @param $value
     * @param string $condition
     * @return void
     * @throws ConfigException
     */
    private function whereColumn(string $column, $value, string $condition)
    {
        $placeholder = $this->stripColumnAlias($column);

        $this->data->push('where', $column . ' ' . $condition . ' :' . $placeholder);

        $this->bindValue($placeholder, $value);
    }

    /**
     * Push where column with IN / NOT in condition and update bind
     *
     * @param string $column
     * @param array $data
     * @param string $condition
     * @return $this
     * @throws ConfigException
     */
    public function whereIn(string $column, array $data, string $condition = 'IN'): self
    {
        $i = 0;
        $bind = array();
        $arr_placeholder = array();

        $placeholder = $this->stripColumnAlias($column);

        foreach ($data as $value) {
            $i++;

            $key = $placeholder . '_' . $i;

            $arr_placeholder[] = ':' . $key;

            $bind[$key] = $value;
        }

        $this->data->push('where', $column . ' ' . $condition . ' (' . implode(', ', $arr_placeholder) . ')');

        $this->bind($bind);

        return $this;
    }

    /**
     * Push where expression and update bind
     *
     * @param string $expression
     * @param array $bind
     * @return $this
     * @throws ConfigException
     */
    public function whereRaw(string $expression, array $bind = []): self
    {
        $this->data->push('where', $expression);

        $this->bind($bind);

        return $this;
    }

    /**
     * Add group clause for query
     *
     * @param $data
     * @return $this
     * @throws ConfigException
     */
    public function group($data): self
    {
        if (is_string($data)) {
            $data = array_map('trim', explode(',', $data));
        }

        $this->data->merge('group', $data);

        return $this;
    }

    /**
     * Push order criteria for query
     *
     * @param string $order
     * @param string $direction
     * @return $this
     * @throws ConfigException
     */
    public function order(string $order, string $direction = 'ASC'): self
    {
        $this->data->push('order', $order . ' ' . $direction);

        return $this;
    }

    /**
     * Set limit for query
     *
     * @param int $count
     * @param int $start
     * @return $this
     * @throws ConfigException
     */
    public function limit(int $count, int $start = 0): self
    {
        $this->data->set('limit', $start . ', ' . $count);

        return $this;
    }

    /**
     * Helper used to push key / value pair for `set` data
     *
     * @param $key
     * @param $value
     * @return void
     * @throws ConfigException
     */
    private function setValue($key, $value): void
    {
        if (is_null($value)) {
            $this->data->push('set', $key);
        } else {
            $this->data->push('set', $value, $key);
        }
    }

    /**
     * Push value for `set` data
     *
     * @param $data
     * @param $value
     * @return $this
     * @throws ConfigException
     */
    public function set($data, $value = null): self
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $this->setValue($k, $v);
            }
        } else {
            $this->setValue($data, $value);
        }

        return $this;
    }

    /**
     * Helper used to push key / value pair for `bind` data
     *
     * @param string $key
     * @param $value
     * @return void
     * @throws ConfigException
     */
    private function bindValue(string $key, $value): void
    {
        $this->data->push('bind', $value, $key);
    }

    /**
     * Push value for `bind` data
     *
     * @param $data
     * @param $value
     * @return $this
     * @throws ConfigException
     */
    public function bind($data, $value = null): self
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $this->bindValue($k, $v);
            }
        } else {
            $this->bindValue($data, $value);
        }

        return $this;
    }

    /**
     * Build query string based on data parameters
     *
     * @param string $type
     * @return string
     * @throws DatabaseException
     */
    protected function queryBuild(string $type): string
    {
        if ($query = $this->data->get('query')) {
            return $query;
        }

        switch ($type) {
            case 'select':
                return $this->queryBuildSelect();
            case 'select_count':
                return $this->queryBuildSelect(true);
            case 'insert':
                return $this->queryBuildInsert();
            case 'update':
                return $this->queryBuildUpdate();
            case 'delete':
                return $this->queryBuildDelete();
            default:
                throw new DatabaseException('Query type (eg: ' . $type . ') is not defined');
        }
    }

    /**
     * Build select query string based on data parameters
     *
     * @param bool $is_count
     * @return string
     * @throws DatabaseException
     * @throws ConfigException
     */
    private function queryBuildSelect(bool $is_count = false): string
    {
        if (empty($this->data->get('table')) === true) {
            throw new DatabaseException('SQL param not set (eg: table)');
        }

        if ($is_count === true) {
            $this->data->push('column', 'id');

            $array = $this->data->get('column');

            $this->data->set('column', [
                'COUNT(' . reset($array) . ')'
            ]);
        } elseif (empty($this->data->get('column')) === true) {
            throw new DatabaseException('SQL param not set (eg: column)');
        }

        $query = 'SELECT ' . implode(', ', $this->data->get('column')) . ' FROM ' . $this->data->get('table');

        if ($join = $this->data->get('join')) {
            $query .= ' ' . implode(' ', $join);
        }

        if ($where = $this->data->get('where')) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($group = $this->data->get('group')) {
            $query .= ' GROUP BY ' . implode(', ', $group);
        }

        if ($having = $this->data->get('having')) {
            $query .= ' HAVING ' . implode(' AND ', $having);
        }

        if ($order = $this->data->get('order')) {
            $query .= ' ORDER BY ' . implode(', ', $order);
        }

        if ($limit = $this->data->get('limit')) {
            $query .= ' LIMIT ' . $limit;
        }

        return $query;
    }

    /**
     * Build insert query string based on data parameters
     *
     * @return string
     * @throws DatabaseException
     * @throws ConfigException
     */
    private function queryBuildInsert(): string
    {
        if (empty($this->data->get('table')) === true) {
            throw new DatabaseException('SQL param not set (eg: table)');
        }

        if (empty($this->data->get('set')) === true) {
            throw new DatabaseException('SQL param not set (eg: set)');
        }

        $this->bind($this->data->get('set'));

        $arr_columns = array_keys($this->data->get('set'));
        $arr_values = [];

        foreach ($arr_columns as $key) {
            $arr_values[] = ':' . $key;
        }

        return 'INSERT INTO ' . $this->data->get('table') . ' (' . implode(', ', $arr_columns) . ') VALUES (' . implode(', ', $arr_values) . ')';
    }

    /**
     * Build update query string based on data parameters
     *
     * @return string
     * @throws DatabaseException
     * @throws ConfigException
     *
     */
    private function queryBuildUpdate(): string
    {
        if (empty($this->data->get('table')) === true) {
            throw new DatabaseException('SQL param not set (eg: table)');
        }

        if (empty($this->data->get('set')) === true) {
            throw new DatabaseException('SQL param not set (eg: set)');
        }

        if (empty($this->data->get('where')) === true) {
            throw new DatabaseException('SQL param not set (eg: where)');
        }

        $set = [];

        foreach ($this->data->get('set') as $key => $value) {
            if (is_int($key)) {
                $set[] = $value;
            } else {
                $set[] = $key . ' = :' . $key;

                $this->bindValue($key, $value);
            }
        }

        return 'UPDATE ' . $this->data->get('table') . ' SET ' . implode(', ', $set) . ' WHERE ' . implode(' AND ', $this->data->get('where'));
    }

    /**
     * Build delete query string based on data parameters
     *
     * @return string
     * @throws DatabaseException
     * @throws ConfigException
     */
    private function queryBuildDelete(): string
    {
        if (empty($this->data->get('table')) === true) {
            throw new DatabaseException('SQL param not set (eg: table)');
        }

        if (empty($this->data->get('where')) === true) {
            throw new DatabaseException('SQL param not set (eg: where)');
        }

        return 'DELETE FROM  ' . $this->data->get('table') . ' WHERE ' . implode(' AND ', $this->data->get('where'));
    }
}
