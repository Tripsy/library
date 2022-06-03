<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Database;

use Tripsy\Library\DataAbstract;

class BuilderSqlData extends DataAbstract
{
    protected string $query = '';
    protected string $table = '';
    protected array $column = [];
    protected array $join = [];
    protected array $where = [];
    protected array $group = [];
    protected array $having = [];
    protected array $order = [];
    protected array $set = [];
    protected array $bind = [];
    protected string $limit = '';
}
