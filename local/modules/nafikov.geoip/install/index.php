<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Highloadblock\HighloadBlockTable;

Loc::loadMessages(__FILE__);

class nafikov_geoip extends CModule
{
    public $MODULE_ID = 'nafikov.geoip';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;

    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('PARTNER_NAME');
        $this->PARTNER_URI = '';
    }

    public function DoInstall()
    {
        global $APPLICATION;

        try {
            ModuleManager::registerModule($this->MODULE_ID);

            $this->installHighloadBlock();

            $this->installFiles();
        } catch (Exception $e) {
            $APPLICATION->ThrowException($e->getMessage());
            return false;
        }
        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        try {

            ModuleManager::unRegisterModule($this->MODULE_ID);

            $this->uninstallHighloadBlock();

            $this->uninstallFiles();
        } catch (Exception $e) {
            $APPLICATION->ThrowException($e->getMessage());
            return false;
        }
        return true;
    }

    private function installHighloadBlock()
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new Exception('Модуль highloadblock не установлен');
        }

        $hlblock = HighloadBlockTable::getList([
            'filter' => ['=NAME' => 'GeoIpData']
        ])->fetch();

        if ($hlblock) {
            return;
        }

        $result = HighloadBlockTable::add([
            'NAME' => 'GeoIpData',
            'TABLE_NAME' => 'nafikov_geoip_data'
        ]);

        if (!$result->isSuccess()) {
            throw new Exception(implode(', ', $result->getErrorMessages()));
        }

        $hlblockId = $result->getId();

        $this->createUserFields($hlblockId);
    }

    private function createUserFields($hlBlockId)
    {
        $entityId = 'HLBLOCK_' . $hlBlockId;

        $fields = [
            [
                'FIELD_NAME' => 'UF_IP',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'UF_IP',
                'SORT' => 100,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_FILTER' => 'Y',
                'EDIT_FORM_LABEL' => ['ru' => 'IP адрес', 'en' => 'IP Address'],
                'LIST_COLUMN_LABEL' => ['ru' => 'IP адрес', 'en' => 'IP Address'],
                'SETTINGS' => ['SIZE' => 20, 'ROWS' => 1, 'DEFAULT_VALUE' => '']
            ],
            [
                'FIELD_NAME' => 'UF_COUNTRY',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'UF_COUNTRY',
                'SORT' => 200,
                'MULTIPLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Страна', 'en' => 'Country'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Страна', 'en' => 'Country'],
                'SETTINGS' => ['SIZE' => 50, 'ROWS' => 1]
            ],
            [
                'FIELD_NAME' => 'UF_COUNTRY_CODE',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'UF_COUNTRY_CODE',
                'SORT' => 300,
                'MULTIPLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Код страны', 'en' => 'Country Code'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Код страны', 'en' => 'Country Code'],
                'SETTINGS' => ['SIZE' => 2, 'ROWS' => 1]
            ],
            [
                'FIELD_NAME' => 'UF_REGION',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'UF_REGION',
                'SORT' => 400,
                'MULTIPLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Регион', 'en' => 'Region'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Регион', 'en' => 'Region'],
                'SETTINGS' => ['SIZE' => 100, 'ROWS' => 1]
            ],
            [
                'FIELD_NAME' => 'UF_CITY',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'UF_CITY',
                'SORT' => 500,
                'MULTIPLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Город', 'en' => 'City'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Город', 'en' => 'City'],
                'SETTINGS' => ['SIZE' => 100, 'ROWS' => 1]
            ],
            [
                'FIELD_NAME' => 'UF_LATITUDE',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => 'UF_LATITUDE',
                'SORT' => 600,
                'MULTIPLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Широта', 'en' => 'Latitude'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Широта', 'en' => 'Latitude'],
            ],
            [
                'FIELD_NAME' => 'UF_LONGITUDE',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => 'UF_LONGITUDE',
                'SORT' => 700,
                'MULTIPLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Долгота', 'en' => 'Longitude'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Долгота', 'en' => 'Longitude'],
            ],
            [
                'FIELD_NAME' => 'UF_PROVIDER',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'UF_PROVIDER',
                'SORT' => 800,
                'MULTIPLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Провайдер', 'en' => 'Provider'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Провайдер', 'en' => 'Provider'],
                'SETTINGS' => ['SIZE' => 50, 'ROWS' => 1]
            ],
            [
                'FIELD_NAME' => 'UF_CREATED_AT',
                'USER_TYPE_ID' => 'datetime',
                'XML_ID' => 'UF_CREATED_AT',
                'SORT' => 900,
                'MULTIPLE' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Дата создания', 'en' => 'Created At'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Дата создания', 'en' => 'Created At'],
            ],
        ];

        $oUserTypeEntity = new CUserTypeEntity();

        foreach ($fields as $field) {
            $field['ENTITY_ID'] = $entityId;
            $oUserTypeEntity->Add($field);
        }
    }

    private function uninstallHighloadBlock()
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new Exception('Модуль highloadblock не установлен');
        }

        $hlBlock = HighloadBlockTable::getList([
            'filter' => ['=NAME' => 'GeoIpData']
        ])->fetch();

        if ($hlBlock) {
            HighloadBlockTable::delete($hlBlock['ID']);
        }
    }

    public function installFiles()
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );
    }

    public function uninstallFiles()
    {
        DeleteDirFilesEx('/local/components/nafikov.korus/geoip.form');
    }
}