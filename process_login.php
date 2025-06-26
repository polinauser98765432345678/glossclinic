<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    if (empty($phone) || empty($password)) {
        header("Location: login.php?error=Заполните все поля");
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id_patient, name, phone, ps FROM patient WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['ps'])) {
        $_SESSION['user_id'] = $user['id_patient'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_phone'] = $user['phone'];
        
        header("Location: profile.php");
        exit;
    } else {
        header("Location: login.php?error=Неверный телефон или пароль");
        exit;
    }
}

header("Location: login.php");
exit;
?>