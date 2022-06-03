<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Api;

interface ApiClientInterface
{
    /**
     * Send api request
     *
     * @return ApiResponseInterface
     */
    public function send(): ApiResponseInterface;
}
