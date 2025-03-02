<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Методы авторизации</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Выбор способа хранения -->
    <div class="storage-type">
        <h3>Выберите способ хранения:</h3>
        <div class="description">
            <strong>PHP Сессии:</strong> Данные хранятся на сервере, браузер получает только ID сессии<br>
            <strong>LocalStorage:</strong> Данные хранятся в браузере без ограничения по времени<br>
            <strong>Cookie:</strong> Данные хранятся в браузере, можно установить срок жизни
        </div>
        <label>
            <input type="radio" name="storage" value="session"> 
            PHP Сессии
        </label>
        <label>
            <input type="radio" name="storage" value="localStorage"> 
            LocalStorage
        </label>
        <label>
            <input type="radio" name="storage" value="cookie"> 
            Cookie
        </label>
    </div>

    <!-- Формы регистрации и входа -->
    <div id="authForms">
        <div class="form">
            <h3>Регистрация</h3>
            <form id="registerForm" onsubmit="handleRegister(event)">
                <input type="text" name="username" placeholder="Логин" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <button type="submit">Зарегистрироваться</button>
            </form>
        </div>

        <div class="form">
            <h3>Вход</h3>
            <form id="loginForm" onsubmit="handleLogin(event)">
                <input type="text" name="username" placeholder="Логин" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <button type="submit">Войти</button>
            </form>
        </div>
    </div>

    <!-- Информация о пользователе после входа -->
    <div id="userInfo" style="display: none;">
        <h3>Привет, <span id="username"></span>!</h3>
        <p>Метод хранения: <span id="storageType"></span></p>
        <form id="logoutForm" onsubmit="handleLogout(event)">
            <button type="submit">Выйти</button>
        </form>
    </div>

    <!-- Подключаем скрипты -->
    <script src="/js/storage.js"></script>
    <script src="/js/auth.js"></script>
    <script>
        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', () => {
            initStorage();
            checkAuth();
        });
    </script>
</body>
</html>