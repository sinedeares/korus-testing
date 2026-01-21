<?php

namespace Nafikov\GeoIp\Storage;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity;

class HighloadBlockStorage
{
    private string $hoBlockName = 'GeoIpData';
    private $entityDataClass = null;

    public function __construct()
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new \Exception('Модуль highloadblock не установлен');
        }

        $this->initEntityDataClass();
    }

    private function initEntityDataClass(): void
    {
        $hlBlock = HighloadBlockTable::getList([
            'filter' => ['=NAME' => $this->hoBlockName]
        ])->fetch();

        if (!$hlBlock) {
            throw new \Exception('Highloadblock "' . $this->hoBlockName . '" не найден');
        }

        $entity = HighloadBlockTable::compileEntity($hlBlock);
        $this->entityDataClass = $entity->getDataClass();
    }

    public function getByIp(string $ip): ?array
    {
        $result = $this->entityDataClass::getList([
            'select' => ['*'],
            'filter' => ['UF_IP' => $ip],
            'limit' => 1
        ])->fetch();

        if (!$result) {
            return null;
        }

        return [
            'id' => $result['ID'],
            'ip' => $result['UF_IP'],
            'country' => $result['UF_COUNTRY'],
            'country_code' => $result['UF_COUNTRY_CODE'],
            'region' => $result['UF_REGION'],
            'city' => $result['UF_CITY'],
            'latitude' => $result['UF_LATITUDE'],
            'longitude' => $result['UF_LONGITUDE'],
            'provider' => $result['UF_PROVIDER'],
            'created_at' => $result['UF_CREATED_AT'],
        ];
    }

    public function save(array $data): int
    {
        $existingRecord = $this->entityDataClass::getList([
            'select' => ['*'],
            'filter' => ['=UF_IP' => $data['ip']],
            'limit' => 1
        ])->fetch();

        $fields = [
            'UF_IP' => $data['ip'],
            'UF_COUNTRY' => $data['country'] ?? '',
            'UF_COUNTRY_CODE' => $data['country_code'] ?? '',
            'UF_REGION' => $data['region'] ?? '',
            'UF_CITY' => $data['city'] ?? '',
            'UF_LATITUDE' => $data['latitude'] ?? null,
            'UF_LONGITUDE' => $data['longitude'] ?? null,
            'UF_PROVIDER' => $data['provider'] ?? '',
            'UF_CREATED_AT' => $data['created_at'] ?? new \DateTime(),
        ];

        if ($existingRecord) {
            $result = $this->entityDataClass::update($existingRecord['ID'], $fields);

            if (!$result->isSuccess()) {
                throw new \Exception('Ошибка обновления: ' . implode(', ', $result->getErrorMessages()));
            }

            return $existingRecord['ID'];
        } else {
            $result = $this->entityDataClass::add($fields);

            if (!$result->isSuccess()) {
                throw new \Exception('Ошибка добавления: ' . implode(', ', $result->getErrorMessages()));
            }

            return $result->getId();
        }
    }
}
