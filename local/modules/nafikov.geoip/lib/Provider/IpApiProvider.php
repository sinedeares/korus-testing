<?php

namespace Nafikov\GeoIp\Provider;

use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Nafikov\GeoIp\Http\Request;
use Nafikov\GeoIp\Http\Uri;

class IpApiProvider implements ProviderGeoIpInterface
{
    private ClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiUrl = 'http://ip-api.com/json/';

    public function __construct(ClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return 'IP-API';
    }

    public function isAvailable(): bool
    {
        try {
            $request = new Request('GET', new Uri($this->apiUrl));
            $response = $this->httpClient->sendRequest($request);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->warning('IP-API недоступен', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getGeoData(string $ip): array
    {
        try {
            $this->logger->info('Вызов IP-API', ['ip' => $ip]);


            //запрашиваем конкретные поля, при желании можно получать все
            $url = $this->apiUrl . $ip . '?fields=status,country,countryCode,region,regionName,city,lat,lon';
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

            if (isset($data['status']) && $data['status'] === 'fail') {
                throw new \Exception('API вернул ошибку: ' . ($data['message'] ?? 'неизвестная ошибка'));
            }

            // Нормализуем данные
            $normalized = [
                'ip' => $ip,
                'country' => $data['country'] ?? '',
                'country_code' => $data['countryCode'] ?? '',
                'region' => $data['regionName'] ?? '',
                'city' => $data['city'] ?? '',
                'latitude' => $data['lat'] ?? null,
                'longitude' => $data['lon'] ?? null,
                'provider' => $this->getName(),
            ];

            $this->logger->info('Данные из IP-API успешно получены', [
                'ip' => $ip,
                'country' => $normalized['country']
            ]);

            return $normalized;

        } catch (\Exception $e) {
            $this->logger->error('Ошибка получения данных из IP-API', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}