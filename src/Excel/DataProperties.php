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

class DataProperties extends DataAbstract
{
    protected string $creator = 'App';
    protected string $title = 'Report';
    protected string $description = 'Report data';
}
