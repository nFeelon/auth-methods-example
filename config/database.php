<?php
// Простое подключение к базе данных
function getConnection() {
    return new PDO(
        "mysql:host=MySQL-8.2;dbname=auth_example;charset=utf8mb4",
        "root",
        ""
    );
}

// Важно! Измените хост, имя базы данных и пользователя в соответствии с вашим проектом