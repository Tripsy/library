<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Database;

abstract class Connection
{
    abstract public function table(string $name): string;
}
