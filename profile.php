<?php
$pageTitle = "Личный кабинет - GlossClinic";
$cssFile = "profile.css";

// Функция для преобразования статуса
function getStatusText($status) {
    $statuses = [
        'pending' => 'Ожидает подтверждения',
        'confirmed' => 'Подтверждена',
        'completed' => 'Завершена',
        'cancelled' => 'Отменена'
    ];
    return $statuses[$status] ?? $status;
}

// Старт сессии
session_start();
require_once 'config.php';

// Обработка сообщений
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Загрузка данных пользователя
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM patient WHERE id_patient = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_name'] = $user['name'] . ' ' . $user['familia'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_birthdate'] = $user['date_of_birth'];
        $_SESSION['user_email'] = $user['email'];
    }
    
    // Загрузка записей пользователя
    $stmt = $pdo->prepare("SELECT z.*, u.nazvanie as service_name, d.name as doctor_name, d.familia as doctor_lastname 
                          FROM zapis z 
                          JOIN usluga u ON z.id_usluga = u.id_usluga 
                          JOIN doctor d ON z.id_doctor = d.id_doctor 
                          WHERE z.id_patient = ? 
                          ORDER BY z.data_priema DESC, z.vremya_nachala DESC");
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll();
    
    // Ближайшая запись (первая в списке)
    $_SESSION['next_appointment'] = !empty($appointments) ? $appointments[0] : null;
    $_SESSION['appointments'] = $appointments;
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Ошибка загрузки данных: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/<?php echo $cssFile; ?>">
</head>
<body>
    <!-- Встроенный хедер -->
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

    <main class="profile-main">
        <div class="container">
            <div class="profile-header">
                <div class="avatar">
                    <img src="images/default-avatar.jpg" alt="Аватар" id="user-avatar">
                    <button id="change-avatar">Изменить</button>
                </div>
                
                <div class="profile-info">
                    <h1 id="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Пользователь'); ?></h1>
                    <p id="user-phone"><?php echo htmlspecialchars($_SESSION['user_phone'] ?? '+7 (XXX) XXX-XX-XX'); ?></p>
                </div>
            </div>
            
            <div class="profile-sections">
                <div class="profile-section">
                    <h2>Мои данные</h2>
                    
                    <?php if ($success_message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>
              <form id="profile-form" action="update_profile.php" method="POST">
    <div class="form-group">
        <label for="profile-name">ФИО *</label>
        <input type="text" id="profile-name" name="name" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" required>
    </div>
    
    <div class="form-group">
        <label for="profile-phone">Телефон *</label>
        <input type="tel" id="profile-phone" name="phone" value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" required>
    </div>
    
    <div class="form-group">
        <label for="profile-birthdate">Дата рождения</label>
        <input type="date" id="profile-birthdate" name="birthdate" value="<?php echo htmlspecialchars($_SESSION['user_birthdate'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label for="profile-email">Email</label>
        <input type="email" id="profile-email" name="email" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
    </div>
    
    <button type="submit" class="save-btn">Сохранить изменения</button>
</form>
                </div>
                
<div class="profile-section">
    <h2>Ближайшая запись</h2>
    <div id="next-appointment" class="appointment-card">
        <?php if($_SESSION['next_appointment']): ?>
            <div class="appointment-info">
                <h3><?= htmlspecialchars($_SESSION['next_appointment']['service_name']) ?></h3>
                <p>Врач: <?= htmlspecialchars($_SESSION['next_appointment']['doctor_name'] . ' ' . $_SESSION['next_appointment']['doctor_lastname']) ?></p>
                <p>Дата: <?= date('d.m.Y', strtotime($_SESSION['next_appointment']['data_priema'])) ?></p>
                <p>Время: <?= substr($_SESSION['next_appointment']['vremya_nachala'], 0, 5) ?></p>
                <p>Статус: <?= getStatusText($_SESSION['next_appointment']['status'] ?? 'pending') ?></p>
            </div>
        <?php else: ?>
            <p class="no-appointment">У вас нет ближайших записей</p>
        <?php endif; ?>
    </div>
</div>
                
   <div class="profile-section">
    <h2>История записей</h2>
    <div id="appointments-history">
        <?php if(!empty($_SESSION['appointments'])): ?>
            <div class="appointments-list">
                <?php foreach($_SESSION['appointments'] as $appointment): ?>
                    <div class="appointment-item">
                        <div class="appointment-header">
                            <span class="date"><?= date('d.m.Y', strtotime($appointment['data_priema'])) ?></span>
                            <span class="status <?= $appointment['status'] ?? 'pending' ?>">
                                <?= getStatusText($appointment['status'] ?? 'pending') ?>
                            </span>
                        </div>
                                        <div class="appointment-body">
                                            <p class="service"><?= htmlspecialchars($appointment['service_name']) ?></p>
                                            <p class="doctor"><?= htmlspecialchars($appointment['doctor_name'] . ' ' . $appointment['doctor_lastname']) ?></p>
                                            <p class="time"><?= substr($appointment['vremya_nachala'], 0, 5) ?> - <?= substr($appointment['vremya_okonchaniya'], 0, 5) ?></p>
                                        </div>
                                     </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>У вас пока нет записей</p>
        <?php endif; ?>
    </div>
</div>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-simple">
            <div class="footer-logo">
                <img src="images/logo-footer.png" alt="GlossClinic">
                <div class="footer-copyright">© <?php echo date('Y'); ?>, GlossClinic</div>
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

    <script src="js/profile.js"></script>
</body>
</html>