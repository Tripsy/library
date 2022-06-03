<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 * https://github.com/phpoffice/phpspreadsheet
 *
 */

namespace Tripsy\Library\Excel;

use Tripsy\Library\DataAbstract;

class DataSheet extends DataAbstract
{
    protected string $title = 'Sample';
    protected array $columns;
    protected array $rows;
    protected string $filter; //A1:C1
}
