<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Api;

class ApiResponse implements ApiResponseInterface
{
    private ApiRequestInterface $request;

    private string $error_code = '0';
    private string $error_message = '';
    private int $status_code;
    private string $body;

    public function __construct(ApiRequestInterface $request)
    {
        $this->request = $request;
    }

    public function setStatusCode(int $code): void
    {
        $this->status_code = $code;
    }

    public function getStatusCode(): int
    {
        return $this->status_code ?? 0;
    }

    public function setBody(string $content): void
    {
        $this->body = $content;
    }

    public function getBody(): string
    {
        return $this->body ?? '';
    }

    public function setError(string $code, string $message): void
    {
        $this->error_code = $code;
        $this->error_message = $message;
    }

    public function getErrorCode(): string
    {
        return $this->error_code;
    }

    public function getErrorMessage(): string
    {
        return $this->error_message;
    }

    public function getRequest(): ApiRequestInterface
    {
        return $this->request;
    }

    public function isSuccessStatusCode(): bool
    {
        $code = $this->getStatusCode();

        if ($code >= 200 && $code <= 300) {
            return true;
        }

        return false;
    }

    /**
     * Helper function which return response & request data
     *
     * @return array
     */
    public function returnDebugData(): array
    {
        return [
            'response' => [
                'status_code' => $this->getStatusCode(),
                'error_code' => $this->getErrorCode(),
                'error_message' => $this->getErrorMessage(),
                'body' => $this->getBody()
            ],
            'request' => [
                'uri' => $this->getRequest()->getUri(),
                'method' => $this->getRequest()->getMethod(),
                'headers' => $this->getRequest()->getHeaders(),
                'body' => $this->getRequest()->getBody(),
            ],
        ];
    }
}
