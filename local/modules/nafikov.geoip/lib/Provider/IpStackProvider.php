<?php

namespace Nafikov\GeoIp\Provider;

use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Nafikov\GeoIp\Http\Request;
use Nafikov\GeoIp\Http\Uri;

class IpStackProvider implements ProviderGeoIpInterface
{
    private ClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $apiUrl = 'http://api.ipstack.com/';

    public function __construct(
        ClientInterface $httpClient,
        LoggerInterface $logger,
        string          $apiKey = ''
    )
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
    }

    public function getName(): string
    {
        return 'IPStack';
    }

    public function isAvailable(): bool
    {
        if (empty($this->apiKey)) {
            $this->logger->debug('Не задан API-ключ для провайдера IPStack');
            return false;
        }

        try {
            $url = $this->apiUrl . 'check?access_key=' . $this->apiKey;
            $request = new Request('GET', new Uri($url));
            $response = $this->httpClient->sendRequest($request);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->warning('IPStack недоступен', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getGeoData(string $ip): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('IPStack API key is not configured');
        }

        try {
            $this->logger->info('Вызов IPStack', ['ip' => $ip]);

            $url = $this->apiUrl . $ip . '?access_key=' . $this->apiKey;
            $request = new Request('GET', new Uri($url));

            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Invalid response status: ' . $response->getStatusCode());
            }

            $body = (string)$response->getBody();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            if (isset($data['error'])) {
                throw new \Exception('API error: ' . $data['error']['info']);
            }

            // Нормализуем данные
            $normalized = [
                'ip' => $ip,
                'country' => $data['country_name'] ?? '',
                'country_code' => $data['country_code'] ?? '',
                'region' => $data['region_name'] ?? '',
                'city' => $data['city'] ?? '',
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'provider' => $this->getName(),
            ];

            $this->logger->info('Данные из IPStack успешно получены', [
                'ip' => $ip,
                'country' => $normalized['country']
            ]);

            return $normalized;

        } catch (\Exception $e) {
            $this->logger->error('Ошибка получения данных из IPStack', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}