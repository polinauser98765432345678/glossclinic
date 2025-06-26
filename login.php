<?php
session_start();
require_once 'config.php';

$pageTitle = "Вход - GlossClinic";
$cssFile = "auth.css";

// Обработка входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM patient WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['ps'])) {
            $_SESSION['user_id'] = $user['id_patient'];
            $_SESSION['user_name'] = $user['name'] . ' ' . $user['familia'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];

            // Перенаправление в зависимости от прав
            if ($_SESSION['is_admin']) {
                header('Location: admin.php');
            } else {
                header('Location: profile.php');
            }
            exit;
        } else {
            $error = "Неверный телефон или пароль";
        }
    } catch (PDOException $e) {
        $error = "Ошибка базы данных: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="css/<?php echo $cssFile; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<main class="auth-main">
    <section class="hero-section auth-hero">
        <div class="hero-content">
            <div class="auth-container">
                <h1>Вход в аккаунт</h1>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <input type="tel" id="phone" name="phone" required placeholder="Телефон" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <input type="password" id="password" name="password" required placeholder="Пароль">
                    </div>
                    
                    <button type="submit" class="submit-btn">Войти</button>
                    
                    <div class="form-footer">
                        <p>Ещё нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

</body>
</html>