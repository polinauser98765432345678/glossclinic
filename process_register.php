<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация
    if (empty($name) || empty($phone) || empty($password)) {
        header("Location: register.php?error=Заполните все обязательные поля");
        exit;
    }
    
    if ($password !== $confirm_password) {
        header("Location: register.php?error=Пароли не совпадают");
        exit;
    }
    
    if (strlen($password) < 6) {
        header("Location: register.php?error=Пароль должен быть не менее 6 символов");
        exit;
    }
    
    // Проверка существующего пользователя
    $stmt = $pdo->prepare("SELECT id_patient FROM patient WHERE phone = ?");
    $stmt->execute([$phone]);
    
    if ($stmt->rowCount() > 0) {
        header("Location: register.php?error=Пользователь с таким телефоном уже существует");
        exit;
    }
    
    // Хеширование пароля
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Создание пользователя
    $stmt = $pdo->prepare("INSERT INTO patient (name, familia, phone, email, ps) VALUES (?, ?, ?, ?, ?)");
    
    // Разделяем ФИО на имя и фамилию
    $nameParts = explode(' ', $name);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    try {
        $stmt->execute([
            $firstName,
            $lastName,
            $phone,
            $email,
            $hashed_password
        ]);
        
        // Автоматический вход после регистрации
        $userId = $pdo->lastInsertId();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $firstName;
        $_SESSION['user_phone'] = $phone;
        
        header("Location: profile.php");
        exit;
    } catch (PDOException $e) {
        header("Location: register.php?error=Ошибка при регистрации");
        exit;
    }
}

header("Location: register.php");
exit;
?>