<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Log;

use Tripsy\Library\Database\DatabaseException;
use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Sql;

class LogDatabase extends Logger
{
    private Sql $sql;

    public function __construct(Sql $sql)
    {
        $this->sql = $sql;
    }

    /**
     * @param string $label
     * @param string $message
     * @param array $context
     * @return void
     * @throws DatabaseException
     * @throws ConfigException
     */
    public function record(string $label, string $message, array $context): void
    {
        $this->sql
            ->table('log_error')
            ->set([
                'label' => $label,
                'message' => $message,
                'context' => serialize($context),
                'request_uri' => var_server('REQUEST_URI'),
                'record_ip' => var_server('REMOTE_ADDR'),
                'record_at' => date('Y-m-d H:i:s'),
            ])
            ->insert();
    }
}
