<?php

namespace Nafikov\GeoIp\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{

    private string $method;
    private UriInterface $uri;
    private array $headers = [];
    private array $headerNames = [];
    private ?StreamInterface $body = null;
    private string $protocolVersion = '1.1';
    private ?string $requestTarget = null;

    public function __construct(
        string       $method,
        UriInterface $uri,
        array        $headers = [],
                     $body = null,
        string       $version = '1.1'
    )
    {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->protocolVersion = $version;
        $this->setHeaders($headers);

        //создание Stream из тела запроса
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
            throw new \InvalidArgumentException('Body must be string, StreamInterface or null');
        }

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }
    }

    //получаем Http метод запроса
    public function getMethod(): string
    {
        return $this->method;
    }

    //создаем новый объект с указанным методом (immutable)
    public function withMethod($method): RequestInterface
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;
    }

    //получаем Uri запроса
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$new->hasHeader('Host')) {
            $new->updateHostFromUri();
        }

        return $new;
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }

        $query = $this->uri->getQuery();
        if ($query !== '') {
            $target .= '?' . $query;
        }

        return $target;
    }

    public function withRequestTarget($requestTarget): RequestInterface
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;
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
        return implode(',', $this->getHeader($name));
    }

    public function withHeader($name, $value): RequestInterface
    {
        $new = clone $this;
        $normalized = strtolower($name);

        //удаляем старый заголовок если есть
        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = is_array($value) ? $value : [$value];

        return $new;
    }

    public function withAddedHeader($name, $value): RequestInterface
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

    public function withoutHeader($name): RequestInterface
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

    public function withBody(StreamInterface $body): RequestInterface
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): RequestInterface
    {
        $new = clone $this;
        $new->protocolVersion = $version;
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

    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();
        if ($host === '') {
            return;
        }

        $port = $this->uri->getPort();
        if ($port !== null) {
            $host .= ':' . $port;
        }

        $this->headerNames['host'] = 'Host';
        $this->headers['Host'] = [$host];
    }

}
