<?php 
$pageTitle = "Регистрация - GlossClinic";
$cssFile = "auth.css";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="css/<?php echo $cssFile; ?>">
</head>
<body>

<main class="auth-main">
    <section class="hero-section auth-hero">
        <div class="hero-content">
            <div class="auth-container">
                <h1>Регистрация</h1>
                
                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                
                <form action="process_register.php" method="post" class="auth-form">
                    <div class="form-group">
                        <input type="text" id="name" name="name" required placeholder="ФИО">
                    </div>
                    
                    <div class="form-group">
                        <input type="tel" id="phone" name="phone" required placeholder="Телефон">
                    </div>
                    
                    <div class="form-group">
                        <input type="email" id="email" name="email" placeholder="Email (необязательно)">
                    </div>
                    
                    <div class="form-group">
                        <input type="password" id="password" name="password" required placeholder="Пароль">
                    </div>
                    
                    <div class="form-group">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Подтвердите пароль">
                    </div>
                    
                    <button type="submit" class="submit-btn">Зарегистрироваться</button>
                    
                    <div class="form-footer">
                        <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

</body>
</html>