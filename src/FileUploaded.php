<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

class FileUploaded
{
    private string $name;
    private string $type;
    private string $tmp_name;
    private string $error;
    private float $size;

    public function __construct(string $name, string $type, string $tmp_name, string $error, float $size)
    {
        $this->name = $name;
        $this->type = $type;
        $this->tmp_name = $tmp_name;
        $this->error = $error;
        $this->size = $size;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTmpName(): string
    {
        return $this->tmp_name;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getSize(): float
    {
        return $this->size / 1000;
    }

    public function getMimeContentType(): string
    {
        return mime_content_type($this->tmp_name);
    }
}
