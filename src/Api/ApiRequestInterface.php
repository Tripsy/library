<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Api;

interface ApiRequestInterface
{
    /**
     * Set key method
     *
     * @param string $method
     * @return self
     */
    public function setMethod(string $method): self;

    /**
     * Get key method
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Set key uri
     *
     * @param string $uri
     * @param mixed ...$args
     * @return self
     */
    public function setUri(string $uri, ...$args): self;

    /**
     * Get key uri
     *
     * @return string
     */
    public function getUri(): string;

    /**
     * Set key body
     *
     * @param array $body
     * @return self
     */
    public function setBody(array $body): self;

    /**
     * Get key body
     *
     * @param bool $is_json
     * @return array|string
     */
    public function getBody(bool $is_json = true);

    /**
     * Update key headers
     *
     * @param array $headers
     * @return self
     */
    public function setHeaders(array $headers): self;

    /**
     * Get key headers
     *
     * @return array
     */
    public function getHeaders(): array;
}
