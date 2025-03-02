// Очистка всех данных авторизации на клиенте
function clearClientAuth() {
    // Очищаем localStorage
    localStorage.removeItem('user');

    // Очищаем куки
    document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    document.cookie = 'username=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
}

// Обработка отправки формы регистрации
function handleRegister(event) {
    event.preventDefault();

    // Получаем данные из формы
    const form = event.target;
    const formData = new FormData(form);
    const data = {
        username: formData.get('username'),
        password: formData.get('password')
    };

    // Отправляем запрос на регистрацию
    fetch(getApiUrl() + '?action=register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(res => res.json())
        .then(data => {
            alert(data.message || data.error);
            if (!data.error) form.reset();
        });
}

// Обработка отправки формы входа
function handleLogin(event) {
    event.preventDefault();

    // Получаем данные из формы
    const form = event.target;
    const formData = new FormData(form);
    const data = {
        username: formData.get('username'),
        password: formData.get('password')
    };

    // Отправляем запрос на вход
    fetch(getApiUrl() + '?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(res => res.json())
        .then(data => {
            if (!data.error) {
                // Если используем localStorage, сохраняем токен
                const storage = getStorageType();
                if (storage === STORAGE_TYPES.LOCAL_STORAGE && data.token) {
                    localStorage.setItem('user', JSON.stringify({
                        username: data.username,
                        token: data.token
                    }));
                }

                alert('Вход выполнен успешно!');
                form.reset();
                checkAuth();
            } else {
                alert(data.error);
            }
        });
}

// Обработка выхода из системы
function handleLogout(event) {
    event.preventDefault();

    // Получаем токен из localStorage если он есть
    let token = null;
    const userData = JSON.parse(localStorage.getItem('user') || 'null');
    if (userData && userData.token) {
        token = userData.token;
    }

    // Получаем токен из cookie если он есть
    const cookies = document.cookie.split(';');
    const tokenCookie = cookies.find(cookie => cookie.trim().startsWith('auth_token='));
    if (tokenCookie) {
        token = tokenCookie.split('=')[1];
    }

    // Отправляем запрос на выход в оба API с токеном если он есть
    Promise.all([
        fetch('/api/localStorage_auth.php?action=logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token })
        }),
        fetch('/api/cookie_auth.php?action=logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token })
        })
    ])
        .then(() => {
            // Очищаем данные на клиенте
            clearClientAuth();
            hideUserInfo();
        })
        .catch(error => {
            console.error('Ошибка при выходе:', error);
        });
}

// Проверка статуса авторизации
function checkAuth() {
    const storage = getStorageType();

    if (storage === STORAGE_TYPES.LOCAL_STORAGE) {
        // Для localStorage проверяем наличие токена
        const userData = JSON.parse(localStorage.getItem('user') || 'null');
        if (userData) {
            // Проверяем валидность токена на сервере
            fetch(getApiUrl() + '?action=verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: userData.token })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.logged_in) {
                        showUserInfo(userData.username, 'LocalStorage');
                    } else {
                        clearClientAuth();
                        hideUserInfo();
                    }
                });
        } else {
            hideUserInfo();
        }
    } else {
        // Для session и cookie проверяем статус на сервере
        fetch(getApiUrl() + '?action=status')
            .then(res => res.json())
            .then(data => {
                if (data.logged_in) {
                    showUserInfo(
                        data.username,
                        storage === STORAGE_TYPES.COOKIE ? 'Cookie' : 'PHP Session'
                    );
                } else {
                    hideUserInfo();
                }
            });
    }
}

// Отображение информации о пользователе
function showUserInfo(username, type) {
    document.getElementById('username').textContent = username;
    document.getElementById('storageType').textContent = type;
    document.getElementById('userInfo').style.display = 'block';
    document.getElementById('authForms').style.display = 'none';
}

// Скрытие информации о пользователе
function hideUserInfo() {
    document.getElementById('userInfo').style.display = 'none';
    document.getElementById('authForms').style.display = 'block';
}
