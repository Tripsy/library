<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Api;

interface ApiResponseInterface
{
    /**
     * Sets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt to understand and satisfy the request.
     *
     * @param int $code
     * @return void
     */
    public function setStatusCode(int $code): void;

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt to understand and satisfy the request.
     *
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * Sets the response body.
     *
     * @param string $content
     * @return void
     */
    public function setBody(string $content): void;

    /**
     * Gets the body of the response.
     *
     * @return string
     */
    public function getBody(): string;

    /**
     * Sets the response error code & message
     *
     * @param string $code
     * @param string $message
     * @return void
     */
    public function setError(string $code, string $message): void;

    /**
     * Get the response error code
     *
     * @return string
     */
    public function getErrorCode(): string;

    /**
     * Get the response error message
     *
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * Return the request object
     *
     * @return ApiRequestInterface
     */
    public function getRequest(): ApiRequestInterface;

    /**
     * Return true is status code is "ok" (eg: 2xx)
     *
     * @return bool
     */
    public function isSuccessStatusCode(): bool;
}
