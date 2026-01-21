<?php

namespace Nafikov\GeoIp\Exceptions;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Exception;

class RequestException extends Exception implements RequestExceptionInterface
{
    private ?RequestInterface $request = null;

    public function __construct(
        string $message = '',
        RequestInterface $request = null,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->request = $request;
    }

    /**
     * Возвращает запрос, который вызвал исключение
     * 
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        if ($this->request === null) {
            throw new \RuntimeException('Request is not set');
        }
        return $this->request;
    }
}
