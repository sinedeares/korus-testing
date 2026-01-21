<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Nafikov\GeoIp\Service\GeoIpService;
use Nafikov\GeoIp\Service\ProviderManager;
use Nafikov\GeoIp\Storage\HighloadBlockStorage;
use Nafikov\GeoIp\Logger\FileLogger;
use Nafikov\GeoIp\Http\Client;
use Nafikov\GeoIp\Provider\SypexGeoProvider;
use Nafikov\GeoIp\Provider\IpApiProvider;
use Nafikov\GeoIp\Provider\IpStackProvider;
use Psr\Log\LogLevel;

class GeoIpForm extends CBitrixComponent
{
    private ?GeoIpService $geoIpService = null;

    protected function checkModules(): bool
    {
        if (!Loader::includeModule('nafikov.geoip')) {
            $this->showError('Модуль nafikov.geoip не установлен');
            return false;
        }

        if (!Loader::includeModule('highloadblock')) {
            $this->showError('Модуль highloadblock не установлен');
            return false;
        }

        return true;
    }

    protected function initService(): void
    {
        if ($this->geoIpService !== null) {
            return;
        }

        // Создаем логгер
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/geoip.log';
        $logger = new FileLogger($logFile, LogLevel::INFO);

        $httpClient = new Client(10); // 10 секунд таймаут

        $providerManager = new ProviderManager($logger);

        $providerManager->addProvider(
            new SypexGeoProvider($httpClient, $logger),
            1 // Высший приоритет
        );

        $providerManager->addProvider(
            new IpApiProvider($httpClient, $logger),
            2
        );

        $ipStackApiKey = $this->arParams['IPSTACK_API_KEY'] ?? '';
        if (!empty($ipStackApiKey)) {
            $providerManager->addProvider(
                new IpStackProvider($httpClient, $logger, $ipStackApiKey),
                3
            );
        }

        $storage = new HighloadBlockStorage();

        $this->geoIpService = new GeoIpService(
            $providerManager,
            $storage,
            $logger
        );
    }

    protected function processAjax(): void
    {
        $request = Application::getInstance()->getContext()->getRequest();

        if ($request->isAjaxRequest() && $request->getPost('action') === 'search') {
            // Получаем IP из запроса
            $ip = trim($request->getPost('ip'));

            try {
                $data = $this->geoIpService->getGeoData($ip);

                $this->sendJsonResponse([
                    'success' => true,
                    'data' => $data,
                ]);

            } catch (\InvalidArgumentException $e) {
                $this->sendJsonResponse([
                    'success' => false,
                    'error' => 'Неверный формат IP адреса',
                    'message' => $e->getMessage(),
                ], 400);

            } catch (\Throwable $e) {
                // Логируем ошибку для отладки
                if (isset($this->geoIpService)) {
                    $logger = new FileLogger(
                        $_SERVER['DOCUMENT_ROOT'] . '/local/logs/geoip.log',
                        \Psr\Log\LogLevel::ERROR
                    );
                    $logger->error('Ошибка в компоненте при обработке запроса', [
                        'ip' => $ip,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }

                $this->sendJsonResponse([
                    'success' => false,
                    'error' => 'Не удалось получить данные о геолокации',
                    'message' => $e->getMessage(),
                ], 500);
            }
        }
    }

    protected function sendJsonResponse(array $data, int $statusCode = 200): void
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE);

        die();
    }

    protected function showError(string $message): void
    {
        ShowError($message);
    }

    public function onPrepareComponentParams($params): array
    {
        $params['IPSTACK_API_KEY'] = $params['IPSTACK_API_KEY'] ?? '';

        return $params;
    }

    public function executeComponent()
    {
        if (!$this->checkModules()) {
            return;
        }

        $this->initService();

        $this->processAjax();

        $this->arResult['COMPONENT_ID'] = $this->randString();

        $this->includeComponentTemplate();
    }
}