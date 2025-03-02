<?php
// Настраиваем заголовки для работы с AJAX
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Подключаем базу данных
require_once '../config/database.php';

// Генерация случайного токена
function generateToken($userId) {
    return bin2hex(random_bytes(32));
}

// Установка куки с токеном
function setAuthCookie($name, $value, $days = 1) {
    setcookie(
        $name,
        $value,
        time() + ($days * 24 * 60 * 60), // срок жизни в днях
        '/',                             // доступна для всего сайта
        '',                              // домен
        false,                           // для демо используем false
        false                             // httpOnly - защита от XSS. True - нельзя у клиента редактировать куки. False (для теста) - можно
    );
}

// Регистрация нового пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'register') {
    // Получаем данные из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Сохраняем пользователя в БД
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([
        $data['username'],
        password_hash($data['password'], PASSWORD_DEFAULT)
    ]);
    
    echo json_encode(['message' => 'Регистрация успешна!']);
}

// Вход в систему
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'login') {
    // Получаем данные из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Ищем пользователя в БД
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$data['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Проверяем пароль
    if ($user && password_verify($data['password'], $user['password'])) {
        // Генерируем токен и сохраняем в БД
        $token = generateToken($user['id']);
        $stmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token) VALUES (?, ?)");
        $stmt->execute([$user['id'], $token]);
        
        // Устанавливаем куки
        setAuthCookie('auth_token', $token);
        setAuthCookie('username', $user['username']);
        
        echo json_encode([
            'message' => 'Вход выполнен успешно',
            'username' => $user['username']
        ]);
    } else {
        echo json_encode(['error' => 'Неверный логин или пароль']);
    }
}

// Проверка статуса авторизации
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'status') {
    // Проверяем наличие токена в куки
    if (isset($_COOKIE['auth_token'])) {
        // Проверяем токен в БД
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT u.username 
            FROM auth_tokens t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.token = ?
        ");
        $stmt->execute([$_COOKIE['auth_token']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'logged_in' => true,
                'username' => $result['username']
            ]);
            exit;
        }
    }
    
    echo json_encode(['logged_in' => false]);
}

// Выход из системы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'logout') {
    // Получаем токен из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Удаляем токен из БД если он есть
    if (isset($data['token'])) {
        $conn = getConnection();
        $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE token = ?");
        $stmt->execute([$data['token']]);
    }
    
    echo json_encode(['message' => 'Выход выполнен']);
}
