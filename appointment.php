<?php
session_start();
require_once 'config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT name, familia, phone FROM patient WHERE id_patient = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    die("Пользователь не найден");
}

// Получаем список услуг и врачей
$services = $pdo->query("SELECT * FROM usluga")->fetchAll();
$doctors = $pdo->query("SELECT * FROM doctor")->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    
    // Валидация данных
    $required = ['name', 'phone', 'service', 'doctor', 'date', 'time'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $response['message'] = 'Все поля обязательны для заполнения';
            echo json_encode($response);
            exit;
        }
    }

    try {
        $pdo->beginTransaction();
        
        // Проверка связи врач-услуга
        $stmt = $pdo->prepare("SELECT 1 FROM doctor_usluga WHERE id_doctor = ? AND id_usluga = ?");
        $stmt->execute([$_POST['doctor'], $_POST['service']]);
        if (!$stmt->fetch()) {
            throw new Exception('Этот врач не оказывает выбранную услугу');
        }

        // Проверка доступности времени
        $stmt = $pdo->prepare("SELECT 1 FROM zapis WHERE id_doctor = ? AND data_priema = ? AND vremya_nachala = ?");
        $stmt->execute([$_POST['doctor'], $_POST['date'], $_POST['time']]);
        if ($stmt->fetch()) {
            throw new Exception('Выбранное время уже занято');
        }

        // Получаем длительность услуги
        $stmt = $pdo->prepare("SELECT dlitelnost_min FROM usluga WHERE id_usluga = ?");
        $stmt->execute([$_POST['service']]);
        $duration = $stmt->fetchColumn();
        if (!$duration) {
            throw new Exception('Не удалось определить длительность услуги');
        }

        // Рассчитываем время окончания
        $end_time = date('H:i:s', strtotime($_POST['time'] . " + $duration minutes"));

        // Вставляем запись
        $stmt = $pdo->prepare("INSERT INTO zapis 
            (id_patient, id_doctor, id_usluga, data_priema, vremya_nachala, vremya_okonchaniya, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['doctor'],
            $_POST['service'],
            $_POST['date'],
            $_POST['time'],
            $end_time
        ]);
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = 'Запись успешно создана!';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запись на прием - GlossClinic</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/appointment.css">
    <script src="js/appointment.js" defer></script>
    <style>
        .error-message {
            color: red;
            font-size: 0.8em;
            display: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .success-message {
            color: green;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <a href="index.html" class="logo">
                    <img src="images/logo.png" alt="GlossClinic">
                </a>
                
                <nav class="main-nav">
                        <ul>
                    <li><a href="about.html">О клинике</a></li>
                    <li><a href="services.html">Услуги</a></li>
                    <li><a href="news.html">Новости</a></li>
                </ul>
                </nav>
            </div>
            
            <div class="header-actions">
                <a href="appointment.php" class="btn-appointment">
                    <img src="images/appointment-icon.png" alt="Запись">
                </a>
                <a href="profile.php" class="btn-profile">
                    <i class="fas fa-user"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="appointment-main">
        <div class="container">
            <h1>Запись к специалисту</h1>
            
            <form id="appointment-form" class="appointment-form" method="POST">
                <div class="form-group">
                    <label for="name">ФИО *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?= htmlspecialchars($user['name'] . ' ' . $user['familia']) ?>">
                    <span class="error-message"></span>
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон *</label>
                    <input type="tel" id="phone" name="phone" required 
                           value="<?= htmlspecialchars($user['phone']) ?>">
                    <span class="error-message"></span>
                </div>
                
                <div class="form-group">
                    <label for="service">Услуга *</label>
                    <select id="service" name="service" required>
                        <option value="">-- Выберите услугу --</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= $service['id_usluga'] ?>">
                                <?= htmlspecialchars($service['nazvanie']) ?> 
                                (<?= $service['cena'] ?> руб.)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message"></span>
                </div>
                
                <div class="form-group">
                    <label for="doctor">Специалист *</label>
                    <select id="doctor" name="doctor" required>
                        <option value="">-- Выберите специалиста --</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= $doctor['id_doctor'] ?>">
                                <?= htmlspecialchars($doctor['name'] . ' ' . $doctor['familia']) ?> 
                                (<?= $doctor['specializaciya'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message"></span>
                </div>
                
                <div class="form-group">
                    <label for="date">Дата приема *</label>
                    <input type="date" id="date" name="date" required>
                    <span class="error-message"></span>
                </div>
                
                <div class="form-group">
                    <label for="time">Время приема *</label>
                    <input type="time" id="time" name="time" required>
                    <span class="error-message"></span>
                </div>
                
                <div class="form-notice">
                    <p>Запись через сайт является предварительной. Наш сотрудник свяжется с Вами для подтверждения записи к специалисту.</p>
                </div>
                
                <button type="submit" class="submit-btn">Отправить заявку</button>
            </form>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-simple">
            <div class="footer-logo">
                <img src="images/logo-footer.png" alt="GlossClinic">
                <div class="footer-copyright">© 2025, GlossClinic made by me</div>
            </div>
            
            <div class="footer-content">
                <div class="footer-phone">8 888 888 88 88</div>
                <div class="footer-email">GLOSSCLINIC@GMAIL.COM</div>
                <div class="footer-hours">График: Пн - Вс с 10:00 до 22:00</div>
                
                <div class="footer-social">
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-vk"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-telegram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>