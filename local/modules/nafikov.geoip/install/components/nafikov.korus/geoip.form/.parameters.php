<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

$moduleId = 'nafikov.geoip';

if (!Loader::includeModule($moduleId)) {
    return;
}

$arComponentParameters = [
    'PARAMETERS' => [
        'IPSTACK_API_KEY' => [
            'PARENT' => 'BASE',
            'NAME' => 'IPStack API ключ',
            'TYPE' => 'STRING',
            'DEFAULT' => Option::get($moduleId, 'IPSTACK_API_KEY', ''),
        ],
        'CACHE_TIME' => [
            'PARENT' => 'CACHE_SETTINGS',
            'NAME' => 'Время кеширования (сек.)',
            'TYPE' => 'STRING',
            'DEFAULT' => '3600',
        ],
    ],
];