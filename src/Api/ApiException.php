<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Api;

use Tripsy\Library\Exceptions\SystemException;
use function Tripsy\Library\Exceptions\dd;

class ApiException extends SystemException
{
    public function __construct(string $message, int $code, ApiResponseInterface $apiResponse)
    {
        dd($apiResponse->returnDebugData()); //TODO

        parent::__construct($message, $code);
    }
}
