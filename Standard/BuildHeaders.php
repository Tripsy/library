<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 * http://www.faqs.org/rfcs/rfc2616.html
 *
 */

namespace Tripsy\Library\Standard;

class BuildHeaders
{
    private array $headers = [];

    public function __construct()
    {

    }

    public function add(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function setContentType(string $value): self
    {
        switch ($value) {
            case 'xls':
                $value = 'application/vnd.ms-excel';
                break;

            case 'xlsx':
                $value = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
        }

        $this->headers['Content-Type'] = $value;

        return $this;
    }

    public function output(): void
    {
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }
    }
}
