<?php

namespace Nafikov\GeoIp\Service;

use Nafikov\GeoIp\Provider\ProviderGeoIpInterface;
use Psr\Log\LoggerInterface;

class ProviderManager
{
    private array $providers = [];

    private LoggerInterface $logger;

    //текущий активный провайдер
    public ?ProviderGeoIpInterface $activeProvider = null;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    //добавление провайдера в список (priority меньше = выше приоритет)
    public function addProvider(ProviderGeoIpInterface $provider, int $priority = 100): void
    {
        $this->providers[] = [
            'provider' => $provider,
            'priority' => $priority
        ];

        usort($this->providers, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        $this->logger->debug('Добавлен провайдер', [
            'provider' => $provider->getName(),
            'priority' => $priority,
        ]);
    }

    public function getGeoData(string $ip): array
    {
        if (empty($this->providers)) {
            throw new \Exception('Не добавлено ни одного провайдера');
        }

        $lastException = null;

        foreach ($this->providers as $item) {
            $provider = $item['provider'];

            try {
                $this->logger->info('Пробуем получить данные', [
                    'provider' => $provider->getName(),
                    'ip' => $ip,
                ]);

                // Проверяем доступность провайдера
                $this->logger->debug('Проверяем доступность провайдера', [
                    'provider' => $provider->getName(),
                ]);
                
                if (!$provider->isAvailable()) {
                    $this->logger->warning('Провайдер недоступен, переключаемся на другой', [
                        'provider' => $provider->getName(),
                    ]);
                    continue;
                }

                $this->logger->debug('Провайдер доступен, запрашиваем данные', [
                    'provider' => $provider->getName(),
                ]);

                // Получаем данные
                $data = $provider->getGeoData($ip);

                // Если успешно - сохраняем как активный провайдер
                $this->activeProvider = $provider;

                $this->logger->info('Данные успешно получены', [
                    'provider' => $provider->getName(),
                    'ip' => $ip,
                ]);

                return $data;
            } catch (\Throwable $e) {
                $this->logger->error('Провайдер не ответил, пробуем другой', [
                    'provider' => $provider->getName(),
                    'ip' => $ip,
                    'error' => $e->getMessage(),
                ]);

                $lastException = $e;
                continue;
            }
        }

        $this->logger->critical('Все провайдеры не отвечают', [
            'ip' => $ip,
        ]);

        throw new \Exception(
            'All GeoIP providers are unavailable. Last error: ' .
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    public function getActiveProvider(): ?ProviderGeoIpInterface
    {
        return $this->activeProvider;
    }

    //список всех провайдеров
    public function getProviders(): array
    {
        return array_map(function ($item) {
            return $item['provider'];
        }, $this->providers);
    }

    public function checkProviders(): array
    {
        $results = [];
        foreach ($this->providers as $item) {
            $provider = $item['provider'];

            $isAvailable = false;
            $error = null;

            try {
                $isAvailable = $provider->isAvailable();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }

            $results[] = [
                'name' => $provider->getName(),
                'priority' => $item['priority'],
                'available' => $isAvailable,
                'error' => $error
            ];
        }

        return $results;
    }
}