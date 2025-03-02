<?php
// Устанавливаем время жизни сессии (30 минут)
ini_set('session.gc_maxlifetime', 1800);

// Настраиваем заголовки для работы с AJAX
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Подключаем базу данных
require_once '../config/database.php';

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
        // Запускаем сессию и сохраняем данные пользователя
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
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
    session_start();
    // Проверяем наличие данных в сессии
    if (isset($_SESSION['username'])) {
        echo json_encode([
            'logged_in' => true,
            'username' => $_SESSION['username']
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
}

// Выход из системы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'logout') {
    session_start();
    // Очищаем все данные сессии
    $_SESSION = array();
    
    // Уничтожаем сессию
    session_destroy();
    
    // Удаляем куку сессии
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    echo json_encode(['message' => 'Выход выполнен']);
}