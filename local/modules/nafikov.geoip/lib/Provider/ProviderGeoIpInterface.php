<?php

namespace Nafikov\GeoIp\Provider;

interface ProviderGeoIpInterface
{
    public function getGeoData(string $ip): array;

    public function getName(): string;

    public function isAvailable(): bool;
}