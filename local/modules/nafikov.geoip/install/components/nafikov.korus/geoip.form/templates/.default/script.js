(function() {
    const componentId = window.GeoIpComponentId;
    const form = document.getElementById('geoip-search-form-' + componentId);
    const resultBlock = document.getElementById('geoip-result-' + componentId);
    const errorBlock = document.getElementById('geoip-error-' + componentId);

    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const ipInput = form.querySelector('input[name="ip"]');
        const ip = ipInput.value.trim();
        const button = form.querySelector('button[type="submit"]');
        const buttonText = button.querySelector('.geoip-form__button-text');
        const loader = button.querySelector('.geoip-form__loader');

        // Скрываем предыдущие результаты
        resultBlock.style.display = 'none';
        errorBlock.style.display = 'none';

        // Показываем загрузку
        button.disabled = true;
        buttonText.textContent = 'Поиск...';
        loader.style.display = 'inline-block';

        // Отправляем AJAX запрос
        const formData = new FormData();
        formData.append('action', 'search');
        formData.append('ip', ip);

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || `HTTP ${response.status}`);
                    }).catch(() => {
                        throw new Error(`HTTP ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showResult(data.data);
                } else {
                    showError(data.error || 'Произошла ошибка при поиске');
                }
            })
            .catch(error => {
                showError(error.message || 'Ошибка соединения с сервером');
            })
            .finally(() => {
                button.disabled = false;
                buttonText.textContent = 'Найти';
                loader.style.display = 'none';
            });

        function showResult(data) {
            resultBlock.querySelectorAll('[data-field]').forEach(field => {
                const fieldName = field.getAttribute('data-field');
                field.textContent = data[fieldName] || '-';
            });

            const storageBadge = resultBlock.querySelector('.geoip-form__badge--storage');
            const providerBadge = resultBlock.querySelector('.geoip-form__badge--provider');

            if (data.from_storage) {
                storageBadge.style.display = 'inline-block';
                providerBadge.style.display = 'none';
            } else {
                storageBadge.style.display = 'none';
                providerBadge.style.display = 'inline-block';
                providerBadge.querySelector('.geoip-form__provider-name').textContent = data.provider || '';
            }

            resultBlock.style.display = 'block';
        }

        function showError(message) {
            errorBlock.querySelector('.geoip-form__error-message').textContent = message;
            errorBlock.style.display = 'block';
        }
    });
})();
