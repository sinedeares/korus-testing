<?php
/**
 * Страница настроек модуля в админке
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

if (!$USER->IsAdmin()) {
    return;
}

$moduleId = 'custom.geoip';

Loader::includeModule($moduleId);

$request = HttpApplication::getInstance()->getContext()->getRequest();

// Обработка сохранения настроек
if ($request->isPost() && check_bitrix_sessid()) {
    $ipStackApiKey = $request->getPost('IPSTACK_API_KEY');
    $logLevel = $request->getPost('LOG_LEVEL');

    Option::set($moduleId, 'IPSTACK_API_KEY', $ipStackApiKey);
    Option::set($moduleId, 'LOG_LEVEL', $logLevel);
}

// Получение текущих значений
$ipStackApiKey = Option::get($moduleId, 'IPSTACK_API_KEY', '');
$logLevel = Option::get($moduleId, 'LOG_LEVEL', 'info');

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => 'Настройки',
        'TITLE' => 'Настройки модуля GeoIP',
    ],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <?php $tabControl->Begin(); ?>

    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%">
            <label for="IPSTACK_API_KEY">IPStack API ключ:</label>
        </td>
        <td width="60%">
            <input
                type="text"
                name="IPSTACK_API_KEY"
                id="IPSTACK_API_KEY"
                value="<?= htmlspecialchars($ipStackApiKey) ?>"
                size="50"
            >
            <br>
            <small>Получить ключ можно на <a href="https://ipstack.com/" target="_blank">ipstack.com</a></small>
        </td>
    </tr>

    <tr>
        <td>
            <label for="LOG_LEVEL">Уровень логирования:</label>
        </td>
        <td>
            <select name="LOG_LEVEL" id="LOG_LEVEL">
                <option value="debug" <?= $logLevel === 'debug' ? 'selected' : '' ?>>Debug</option>
                <option value="info" <?= $logLevel === 'info' ? 'selected' : '' ?>>Info</option>
                <option value="warning" <?= $logLevel === 'warning' ? 'selected' : '' ?>>Warning</option>
                <option value="error" <?= $logLevel === 'error' ? 'selected' : '' ?>>Error</option>
            </select>
            <br>
            <small>Логи сохраняются в /local/logs/geoip.log</small>
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>

    <input type="submit" name="save" value="Сохранить" class="adm-btn-save">

    <?php $tabControl->End(); ?>
</form>