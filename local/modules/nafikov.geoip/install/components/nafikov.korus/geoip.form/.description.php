<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => 'Форма GeoIP поиска',
    'DESCRIPTION' => 'Поиск информации по IP адресу',
    'PATH' => [
        'ID' => 'custom',
        'NAME' => 'Пользовательские компоненты',
        'CHILD' => [
            'ID' => 'geoip',
            'NAME' => 'GeoIP',
        ],
    ],
];