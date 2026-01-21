<?php

namespace Nafikov\GeoIp\Http;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    private $resource;
    private bool $seekable;
    private bool $readable;
    private bool $writable;

    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }

        $this->resource = $resource;

        $meta = stream_get_meta_data($resource); //информация о ресурсе потока

        $this->seekable = $meta['seekable'];
        $this->readable = $this->detectReadable($meta['mode']);
        $this->writable = $this->detectWritable($meta['mode']);
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }
            return $this->getContents();
        } catch (Exception $e) {
            return '';
        }
    }

    public function close(): void
    {
        if (isset($this->resource)) {
            fclose($this->resource);
        }
        $this->detach();
    }

    //отвязка ресурса
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;
        return $resource;
    }

    public function getSize(): ?int
    {
        if (!isset($this->resource)) {
            return null;
        }

        $stats = fstat($this->resource);
        return $stats['size'] ?? null;
    }

    //показатель курсора
    public function tell(): int
    {
        if (!isset($this->resource)) {
            throw new \RuntimeException('Resource is detached');
        }

        $result = ftell($this->resource);
        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function eof(): bool
    {
        return !isset($this->resource) || feof($this->resource);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        }

        if (fseek($this->resource, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position ');
        }
    }

    //перевод на начало потока
    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write($string): int
    {
        if (!$this->writable) {
            throw new \RuntimeException('Stream is not writable');
        }

        $result = fwrite($this->resource, $string);
        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read($length): string
    {
        if (!$this->readable) {
            throw new \RuntimeException('Stream is not readable');
        }

        $result = fread($this->resource, $length);
        if ($result === false) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    public function getContents(): string
    {
        if (!$this->readable) {
            throw new \RuntimeException('Stream is not readable');
        }

        $result = stream_get_contents($this->resource);
        if ($result === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $result;
    }

    public function getMetaData($key = null)
    {
        if (!isset($this->resource)) {
            return $key ? null : [];
        }

        $meta = stream_get_meta_data($this->resource);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    private function detectReadable(string $mode): bool
    {
        return strpos($mode, 'r') !== false || strpos($mode, '+') !== false;
    }

    private function detectWritable(string $mode): bool
    {
        return strpos($mode, 'w') !== false
            || strpos($mode, 'a') !== false
            || strpos($mode, 'x') !== false
            || strpos($mode, 'c') !== false
            || strpos($mode, '+') !== false;
    }

}