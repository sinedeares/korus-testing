<?php

namespace Nafikov\GeoIp\Http;

use Nafikov\GeoIp\Exceptions\NetworkException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Client\ClientExceptionInterface;

class Client implements ClientInterface
{
    private int $timeout;

    private array $curlOptions;

    public function __construct(int $timeout = 10, array $curlOptions = [])
    {
        $this->timeout = $timeout;
        $this->curlOptions = $curlOptions;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => (string)$request->getUri(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        //установка Http метода
        $method = strtoupper($request->getMethod());
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        //установка заголовков
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = $name . ': ' . $value;
            }
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        //установка тела запроса
        $body = (string)$request->getBody();
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if (!empty($this->curlOptions)) {
            curl_setopt_array($ch, $this->curlOptions);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            throw new NetworkException(
                'cURL error ' . $errno . ': ' . $error,
                $request
            );
        }

        //получение информации о запросе
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        curl_close($ch);

        $headerString = substr($response, 0, $headerSize);
        $bodyString = substr($response, $headerSize);

        $headers = $this->parseHeaders($headerString);

        return new Response($statusCode, $headers, $bodyString);

    }

    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", trim($headerString));

        //пропускаем статус
        array_shift($lines);

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);

                if (!isset($headers[$name])) {
                    $headers[$name] = [];
                }
                $headers[$name][] = $value;
            }
        }
        return $headers;
    }
}