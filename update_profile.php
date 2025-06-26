<?php
session_start();
require_once 'config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Обработка данных формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем данные
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    
    try {
        // Обновление данных в таблице patient
        $stmt = $pdo->prepare("UPDATE patient SET 
            name = :name, 
            phone = :phone, 
            date_of_birth = :birthdate, 
            email = :email 
            WHERE id_patient = :id");
            
        $stmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':birthdate' => $birthdate,
            ':email' => $email,
            ':id' => $_SESSION['user_id']
        ]);
        
        // Обновление данных в сессии
        $_SESSION['user_name'] = $name;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_birthdate'] = $birthdate;
        $_SESSION['user_email'] = $email;
        
        // Перенаправление с сообщением об успехе
        $_SESSION['success_message'] = "Данные успешно обновлены";
        header("Location: profile.php");
        exit;
        
    } catch (PDOException $e) {
        // Обработка ошибки базы данных
        $_SESSION['error_message'] = "Ошибка при обновлении данных: " . $e->getMessage();
        header("Location: profile.php");
        exit;
    }
} else {
    // Если запрос не POST
    header("Location: profile.php");
    exit;
}