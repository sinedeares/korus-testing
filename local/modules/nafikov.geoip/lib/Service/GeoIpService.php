<?php

namespace Nafikov\GeoIp\Service;

use Nafikov\GeoIp\Storage\HighloadBlockStorage;
use Psr\Log\LoggerInterface;
use Bitrix\Main\Type\DateTime;

class GeoIpService
{
    private ProviderManager $providerManager;
    private HighloadBlockStorage $storage;
    private LoggerInterface $logger;

    public function __construct(
        ProviderManager      $providerManager,
        HighloadBlockStorage $storage,
        LoggerInterface      $logger
    )
    {
        $this->providerManager = $providerManager;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public function getGeoData(string $ip): array
    {
        // Валидация IP адреса
        if (!$this->validateIp($ip)) {
            $this->logger->error('Введён невалидный ip адрес', ['ip' => $ip]);
            throw new \InvalidArgumentException('Invalid IP address: ' . $ip);
        }

        $this->logger->info('Processing GeoIP request', ['ip' => $ip]);

        try {
            $storedData = $this->storage->getByIp($ip);

            if ($storedData !== null) {
                $this->logger->info('Данные найдены в HL-блоке', [
                    'ip' => $ip,
                    'country' => $storedData['country'] ?? '',
                ]);

                $storedData['from_storage'] = true;
                return $storedData;
            }

            $this->logger->info('Данные не найдены в HL-блоке, производим поиск', [
                'ip' => $ip
            ]);

        } catch (\Throwable $e) {
            $this->logger->warning('Ошибка чтения из хранилища', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $data = $this->providerManager->getGeoData($ip);

            $data['created_at'] = new DateTime();
            $data['from_storage'] = false;

            try {
                $this->storage->save($data);

                $this->logger->info('Данные сохранены в HL-блоке', [
                    'ip' => $ip,
                    'provider' => $data['provider'] ?? 'unknown',
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('Ошибка сохранения данных в HL-блоке', [
                    'ip' => $ip,
                    'error' => $e->getMessage(),
                ]);
            }

            return $data;

        } catch (\Throwable $e) {
            $this->logger->critical('Ошибка получения данных', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function validateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public function checkProviders(): array
    {
        return $this->providerManager->checkProviders();
    }

    public function getActiveProvider(): ?string
    {
        $provider = $this->providerManager->getActiveProvider();
        return $provider ? $provider->getName() : null;
    }

}