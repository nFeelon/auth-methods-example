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
        
        echo json_encode([
            'message' => 'Вход выполнен успешно',
            'username' => $user['username'],
            'token' => $token // Отправляем токен клиенту для сохранения в localStorage
        ]);
    } else {
        echo json_encode(['error' => 'Неверный логин или пароль']);
    }
}

// Проверка валидности токена
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'verify') {
    // Получаем токен из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['token'])) {
        // Проверяем токен в БД
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT u.username 
            FROM auth_tokens t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.token = ?
        ");
        $stmt->execute([$data['token']]);
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
    
    if (isset($data['token'])) {
        // Удаляем токен из БД
        $conn = getConnection();
        $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE token = ?");
        $stmt->execute([$data['token']]);
    }
    
    echo json_encode(['message' => 'Выход выполнен']);
}
