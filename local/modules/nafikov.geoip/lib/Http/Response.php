<?php

namespace Nafikov\GeoIp\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase = '';
    private array $headers = [];
    private array $headerNames = [];
    private StreamInterface $body;
    private string $protocolVersion = '1.1';

    private const PHRASES = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function __construct(
        int    $statusCode = 200,
        array  $headers = [],
               $body = null,
        string $version = '1.1',
        string $reason = ''
    )
    {
        $this->statusCode = $statusCode;
        $this->protocolVersion = $version;
        $this->setHeaders($headers);

        // Создание Stream из тела ответа
        if ($body === null) {
            $this->body = new Stream(fopen('php://temp', 'r+'));
        } elseif ($body instanceof StreamInterface) {
            $this->body = $body;
        } elseif (is_string($body)) {
            $stream = new Stream(fopen('php://temp', 'r+'));
            $stream->write($body);
            $stream->rewind();
            $this->body = $stream;
        } else {
            throw new \InvalidArgumentException('Body must be a string, StreamInterface or null');
        }

        // установка фразы состояния
        if ($reason === '' && isset(self::PHRASES[$statusCode])) {
            $this->reasonPhrase = self::PHRASES[$statusCode];
        } else {
            $this->reasonPhrase = $reason;
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        $new = clone $this;
        $new->statusCode = (int)$code;

        if ($reasonPhrase === '' && isset(self::PHRASES[$code])) {
            $reasonPhrase = self::PHRASES[$code];
        }

        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): ResponseInterface
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader($name): array
    {
        $name = strtolower($name);
        if (!isset($this->headerNames[$name])) {
            return [];
        }
        return $this->headers[$this->headerNames[$name]];
    }

    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): ResponseInterface
    {
        $new = clone $this;
        $normalized = strtolower($name);

        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = is_array($value) ? $value : [$value];

        return $new;
    }

    public function withAddedHeader($name, $value): ResponseInterface
    {
        $new = clone $this;
        $normalized = strtolower($name);

        if (isset($new->headerNames[$normalized])) {
            $name = $new->headerNames[$normalized];
            $new->headers[$name] = array_merge(
                $new->headers[$name],
                is_array($value) ? $value : [$value]
            );
        } else {
            $new->headerNames[$normalized] = $name;
            $new->headers[$name] = is_array($value) ? $value : [$value];
        }

        return $new;
    }

    public function withoutHeader($name): ResponseInterface
    {
        $new = clone $this;
        $normalized = strtolower($name);

        if (!isset($new->headerNames[$normalized])) {
            return $new;
        }

        $original = $new->headerNames[$normalized];
        unset($new->headers[$original], $new->headerNames[$normalized]);

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    private function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $normalized = strtolower($name);
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = is_array($value) ? $value : [$value];
        }
    }
}