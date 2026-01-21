<?php

namespace Nafikov\GeoIp\Provider;

use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Nafikov\GeoIp\Http\Request;
use Nafikov\GeoIp\Http\Uri;

class SypexGeoProvider implements ProviderGeoIpInterface
{
    private ClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiUrl = 'https://api.sypexgeo.net/json';

    public function __construct(ClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return 'SypexGeo';
    }

    public function isAvailable(): bool
    {
        try {
            $request = new Request('GET', new Uri($this->apiUrl));
            $response = $this->httpClient->sendRequest($request);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->warning('SypexGeo недоступен', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getGeoData(string $ip): array
    {
        try {
            $this->logger->info('Вызов SypexGeo', ['ip' => $ip]);

            $url = $this->apiUrl . '/' . $ip;
            $request = new Request('GET', new Uri($url));

            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Ivalid response status: ' . $response->getStatusCode());
            }

            //получаем тело ответа
            $body = (string)$response->getBody();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }

            $normalized = [
                'ip' => $ip,
                'country' => $data['country']['name_ru'] ?? '',
                'country_code' => $data['country']['iso'] ?? '',
                'region' => $data['region']['name_ru'] ?? '',
                'city' => $data['city']['name_ru'] ?? '',
                'latitude' => $data['city']['lat'] ?? null,
                'longitude' => $data['city']['lon'] ?? null,
                'provider' => $this->getName(),
            ];

            $this->logger->info('Данные из SypexGeo успешно получены', [
                'ip' => $ip,
                'country' => $normalized['country'],
            ]);

            return $normalized;
        } catch (\Exception $e) {
            $this->logger->error('Ошибка получения данных из SypexGeo', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

    }
}