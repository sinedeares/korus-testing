<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$componentId = $arResult['COMPONENT_ID'];
?>

<div class="geoip-form" id="geoip-form-<?= $componentId ?>">
    <div class="geoip-form__container">
        <h2 class="geoip-form__title">Поиск геолокации по IP адресу</h2>

        <form class="geoip-form__search" id="geoip-search-form-<?= $componentId ?>">
            <div class="geoip-form__input-group">
                <input
                    type="text"
                    name="ip"
                    class="geoip-form__input"
                    placeholder="Введите IP адрес (например: 8.8.8.8)"
                    required
                    pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$"
                >
                <button type="submit" class="geoip-form__button">
                    <span class="geoip-form__button-text">Найти</span>
                    <span class="geoip-form__loader" style="display: none;"></span>
                </button>
            </div>
            <small class="geoip-form__hint">Поддерживаются IPv4 адреса</small>
        </form>

        <!-- Блок с результатами -->
        <div class="geoip-form__result" id="geoip-result-<?= $componentId ?>" style="display: none;">
            <div class="geoip-form__result-header">
                <h3 class="geoip-form__result-title">Результаты поиска</h3>
                <span class="geoip-form__badge geoip-form__badge--storage" style="display: none;">
                    Из хранилища
                </span>
                <span class="geoip-form__badge geoip-form__badge--provider" style="display: none;">
                    <span class="geoip-form__provider-name"></span>
                </span>
            </div>

            <div class="geoip-form__data">
                <div class="geoip-form__data-row">
                    <span class="geoip-form__label">IP адрес:</span>
                    <span class="geoip-form__value" data-field="ip"></span>
                </div>
                <div class="geoip-form__data-row">
                    <span class="geoip-form__label">Страна:</span>
                    <span class="geoip-form__value" data-field="country"></span>
                </div>
                <div class="geoip-form__data-row">
                    <span class="geoip-form__label">Код страны:</span>
                    <span class="geoip-form__value" data-field="country_code"></span>
                </div>
                <div class="geoip-form__data-row">
                    <span class="geoip-form__label">Регион:</span>
                    <span class="geoip-form__value" data-field="region"></span>
                </div>
                <div class="geoip-form__data-row">
                    <span class="geoip-form__label">Город:</span>
                    <span class="geoip-form__value" data-field="city"></span>
                </div>
                <div class="geoip-form__data-row">
                    <span class="geoip-form__label">Координаты:</span>
                    <span class="geoip-form__value">
                        <span data-field="latitude"></span>, <span data-field="longitude"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Блок с ошибкой -->
        <div class="geoip-form__error" id="geoip-error-<?= $componentId ?>" style="display: none;">
            <div class="geoip-form__error-icon">⚠️</div>
            <div class="geoip-form__error-message"></div>
        </div>
    </div>
</div>

<script>
    window.GeoIpComponentId = '<?= $arResult['COMPONENT_ID'] ?>';
</script>
<script src="<?= $this->GetFolder() ?>/script.js"></script>