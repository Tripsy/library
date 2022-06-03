<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Standard;

use Tripsy\Library\DataAbstract;
use Tripsy\Library\Exceptions\ConfigException;

class Output
{
    private bool $error;
    private array $message = [];

    public DataAbstract $data;

    public function __construct(DataAbstract $data, $error = true)
    {
        $this->error = $error;
        $this->data = $data;
    }

    /**
     * @param string $text
     * @param array $text_data
     * @return void
     */
    private function assembleMessage(string $text, array $text_data): void
    {
        $this->message[] = StringTools::interpolate($text, $text_data);
    }

    /**
     * Return message as string if only one message exist or as array if multiple messages are set
     *
     * @return string|array
     */
    public function message()
    {
        $count_message = count($this->message);

        if ($count_message == 1) {
            return current($this->message);
        } elseif ($count_message == 0) {
            return '';
        }

        return $this->message;
    }

    /**
     * Adds message, mark output as fail
     *
     * @param string $text
     * @param array $text_data
     * @return $this
     */
    public function fail(string $text = '', array $text_data = []): self
    {
        $this->error = true;
        $this->assembleMessage($text, $text_data);

        return $this;
    }

    /**
     * Adds message, mark output as success
     *
     * @param string $text
     * @param array $text_data
     * @return $this
     */
    public function success(string $text = '', array $text_data = []): self
    {
        $this->error = false;
        $this->assembleMessage($text, $text_data);

        return $this;
    }

    /**
     * Return true on error
     *
     * @return bool
     */
    public function error(): bool
    {
        return $this->error;
    }

    /**
     * @return array
     * @throws ConfigException
     */
    public function toArray(): array
    {
        return [
            'error' => $this->error(),
            'message' => $this->message(),
            'data' => $this->data->list()
        ];
    }

    /**
     * @return string
     * @throws ConfigException
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
