// Доступные способы хранения данных авторизации
const STORAGE_TYPES = {
    SESSION: 'session',        // Сессия на стороне сервера
    LOCAL_STORAGE: 'localStorage',  // Локальное хранилище браузера
    COOKIE: 'cookie'          // Куки браузера
};

// Получаем текущий выбранный способ хранения (session/localStorage/cookie)
function getStorageType() {
    return document.querySelector('input[name="storage"]:checked').value;
}

// Формируем URL для API в зависимости от способа хранения
function getApiUrl() {
    const storage = getStorageType();
    return `/api/${storage}_auth.php`;
}

// Восстанавливаем ранее выбранный способ хранения
function restoreStorageType() {
    const savedType = localStorage.getItem('selectedStorageType') || STORAGE_TYPES.SESSION;
    document.querySelector(`input[name="storage"][value="${savedType}"]`).checked = true;
}

// Сохраняем выбранный способ хранения
function saveStorageType(type) {
    localStorage.setItem('selectedStorageType', type);
}

// Добавляем обработчики для переключения способа хранения
function initStorageHandlers() {
    document.querySelectorAll('input[name="storage"]').forEach(radio => {
        radio.addEventListener('change', () => {
            // Сохраняем новый выбранный метод
            saveStorageType(radio.value);

            // Выходим из текущей сессии при смене способа
            const logoutForm = document.getElementById('logoutForm');
            if (logoutForm) {
                const event = new Event('submit');
                logoutForm.dispatchEvent(event);
            }
            // Проверяем статус авторизации для нового способа
            setTimeout(checkAuth, 100);
        });
    });
}

// Инициализация при загрузке страницы
function initStorage() {
    restoreStorageType();
    initStorageHandlers();
}
