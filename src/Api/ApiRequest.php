<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Api;

class ApiRequest implements ApiRequestInterface
{
    public const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    private string $method;
    private string $uri;
    private array $body = [];
    private array $headers = [];

    /**
     * @param string $method
     * @param string $uri
     */
    public function __construct(string $method = '', string $uri = '')
    {
        if ($method) {
            $this->setMethod($method);
        }

        if ($uri) {
            $this->setUri($uri);
        }
    }

    /**
     * Set method
     *
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): self
    {
        if (in_array($method, self::ALLOWED_METHODS)) {
            $this->method = $method;
        }

        return $this;
    }

    /**
     * Get method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set uri
     *
     * @param string $uri
     * @param ...$args
     * @return $this
     */
    public function setUri(string $uri, ...$args): self
    {
        $this->uri = implode('/', array_merge([$uri], $args));

        return $this;
    }

    /**
     * Get uri
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set body
     *
     * @param array $body
     * @return $this
     */
    public function setBody(array $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @param bool $is_json
     * @return string|array
     */
    public function getBody(bool $is_json = true)
    {
        return $is_json ? json_encode($this->body) : $this->body;
    }

    /**
     * Set headers
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $k => $v) {
            $this->headers[$k] = $v;
        }

        return $this;
    }

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
