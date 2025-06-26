<?php
require_once 'config.php';

// Проверка авторизации
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

// Получаем список всех таблиц и представлений
$tables = [];
$views = [];
try {
    // Получаем таблицы
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Получаем представления
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Ошибка получения списка таблиц: " . $e->getMessage());
}

// Текущая таблица/представление
$current_table = $_GET['table'] ?? '';
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

// Обработка CRUD операций
if ($current_table && in_array($current_table, array_merge($tables, $views))) {
    // Получаем информацию о столбцах таблицы
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $current_table");
        $columns_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Ошибка получения информации о столбцах: " . $e->getMessage());
    }

    // Удаление записи
    if ($action === 'delete' && $id) {
        try {
            $primary_key = get_primary_key($pdo, $current_table);
            $stmt = $pdo->prepare("DELETE FROM $current_table WHERE $primary_key = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Запись успешно удалена";
            header("Location: admin.php?table=$current_table");
            exit;
        } catch (PDOException $e) {
            die("Ошибка при удалении: " . $e->getMessage());
        }
    }

    // Получение данных для редактирования
    $edit_data = [];
    if ($action === 'edit' && $id) {
        try {
            $primary_key = get_primary_key($pdo, $current_table);
            $stmt = $pdo->prepare("SELECT * FROM $current_table WHERE $primary_key = ?");
            $stmt->execute([$id]);
            $edit_data = $stmt->fetch();
        } catch (PDOException $e) {
            die("Ошибка при загрузке данных для редактирования: " . $e->getMessage());
        }
    }

    // Добавление/обновление записи
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $data = $_POST;
            unset($data['submit']);
            
            // Обработка данных перед сохранением
            foreach ($columns_info as $column) {
                $field = $column['Field'];
                if (isset($data[$field])) {
                    // Преобразование данных в соответствии с типом столбца
                    if (strpos($column['Type'], 'int') !== false || strpos($column['Type'], 'decimal') !== false) {
                        $data[$field] = $data[$field] === '' ? null : $data[$field];
                    }
                }
            }
            
            if ($action === 'edit' && $id) {
                // Обновление записи
                $primary_key = get_primary_key($pdo, $current_table);
                $setParts = [];
                $values = [];
                foreach ($data as $key => $value) {
                    $setParts[] = "$key = ?";
                    $values[] = $value;
                }
                $values[] = $id;
                
                $sql = "UPDATE $current_table SET " . implode(', ', $setParts) . " WHERE $primary_key = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                
                $_SESSION['message'] = "Запись успешно обновлена";
            } else {
                // Добавление новой записи
                $columns = implode(', ', array_keys($data));
                $placeholders = implode(', ', array_fill(0, count($data), '?'));
                
                $sql = "INSERT INTO $current_table ($columns) VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($data));
                
                $_SESSION['message'] = "Запись успешно добавлена";
            }
            
            header("Location: admin.php?table=$current_table");
            exit;
        } catch (PDOException $e) {
            $error = "Ошибка при сохранении: " . $e->getMessage();
        }
    }

    // Получаем данные таблицы для отображения
    $table_data = [];
    $columns = array_column($columns_info, 'Field');
    $primary_key = get_primary_key($pdo, $current_table);
    
    try {
        $stmt = $pdo->query("SELECT * FROM $current_table LIMIT 100");
        $table_data = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Ошибка загрузки данных таблицы: " . $e->getMessage());
    }
}

// Функция для получения первичного ключа таблицы
function get_primary_key($pdo, $table) {
    $stmt = $pdo->query("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
    $key = $stmt->fetch(PDO::FETCH_ASSOC);
    return $key['Column_name'] ?? 'id';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= APP_NAME ?> - Админ-панель</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1><?= APP_NAME ?> - Админ-панель</h1>
            <nav class="admin-nav">
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </nav>
        </header>

        <main class="admin-main">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="message success"><?= safe_output($_SESSION['message']) ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?= safe_output($error) ?></div>
            <?php endif; ?>

            <div class="admin-sidebar">
                <h3>Таблицы</h3>
                <ul class="table-list">
                    <?php foreach ($tables as $table): ?>
                        <li class="<?= $table === $current_table ? 'active' : '' ?>">
                            <a href="?table=<?= urlencode($table) ?>">
                                <i class="fas fa-table"></i> <?= safe_output($table) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <h3>Представления</h3>
                <ul class="view-list">
                    <?php foreach ($views as $view): ?>
                        <li class="<?= $view === $current_table ? 'active' : '' ?>">
                            <a href="?table=<?= urlencode($view) ?>">
                                <i class="fas fa-eye"></i> <?= safe_output($view) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="admin-content">
                <?php if ($current_table): ?>
                    <div class="table-header">
                        <h2><?= safe_output($current_table) ?></h2>
                        <?php if (in_array($current_table, $tables)): ?>
                            <a href="?table=<?= urlencode($current_table) ?>&action=add" class="btn-add">
                                <i class="fas fa-plus"></i> Добавить
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($action === 'add' || $action === 'edit'): ?>
                        <div class="edit-form">
                            <h3><?= $action === 'add' ? 'Добавление записи' : 'Редактирование записи' ?></h3>
                            
                            <form method="post" action="?table=<?= urlencode($current_table) ?><?= $action === 'edit' ? '&action=edit&id='.$id : '' ?>">
                                <?php foreach ($columns_info as $column): ?>
                                    <?php if ($column['Field'] !== $primary_key || $action === 'add'): ?>
                                        <div class="form-group">
                                            <label for="<?= $column['Field'] ?>"><?= $column['Field'] ?></label>
                                            <?php if (strpos(strtolower($column['Type']), 'text') !== false || 
                                                     strpos(strtolower($column['Field']), 'description') !== false): ?>
                                                <textarea id="<?= $column['Field'] ?>" name="<?= $column['Field'] ?>"><?= 
                                                    safe_output($edit_data[$column['Field']] ?? '') ?></textarea>
                                            <?php elseif (strpos($column['Type'], 'int') !== false || 
                                                        strpos($column['Type'], 'decimal') !== false): ?>
                                                <input type="number" id="<?= $column['Field'] ?>" name="<?= $column['Field'] ?>" 
                                                       value="<?= safe_output($edit_data[$column['Field']] ?? '') ?>">
                                            <?php else: ?>
                                                <input type="text" id="<?= $column['Field'] ?>" name="<?= $column['Field'] ?>" 
                                                       value="<?= safe_output($edit_data[$column['Field']] ?? '') ?>">
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn-save">
                                        <?= $action === 'add' ? 'Добавить' : 'Сохранить' ?>
                                    </button>
                                    <a href="?table=<?= urlencode($current_table) ?>" class="btn-cancel">Отмена</a>
                                </div>
                            </form>
                        </div>
                    <?php elseif (!empty($table_data)): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <?php foreach ($columns as $column): ?>
                                            <th><?= safe_output($column) ?></th>
                                        <?php endforeach; ?>
                                        <?php if (in_array($current_table, $tables)): ?>
                                            <th>Действия</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($table_data as $row): ?>
                                        <tr>
                                            <?php foreach ($columns as $column): ?>
                                                <td><?= safe_output($row[$column] ?? 'NULL') ?></td>
                                            <?php endforeach; ?>
                                            <?php if (in_array($current_table, $tables)): ?>
                                                <td class="actions">
                                                    <a href="?table=<?= urlencode($current_table) ?>&action=edit&id=<?= $row[$primary_key] ?>" 
                                                       class="btn-edit" title="Редактировать">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?table=<?= urlencode($current_table) ?>&action=delete&id=<?= $row[$primary_key] ?>" 
                                                       class="btn-delete" title="Удалить"
                                                       onclick="return confirm('Вы уверены?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>Таблица пуста</p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="welcome-message">
                        <h2>Добро пожаловать в админ-панель</h2>
                        <p>Выберите таблицу или представление из списка слева для работы с данными.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>