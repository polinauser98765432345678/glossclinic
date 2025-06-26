<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}
// Проверка прав администратора
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_table':
            handleGetTable();
            break;
            
        case 'get_form':
            handleGetForm();
            break;
            
        case 'save':
            handleSave();
            break;
            
        case 'delete':
            handleDelete();
            break;
            
        case 'get_report':
            handleGetReport();
            break;
            
        default:
            echo json_encode(['error' => 'Неизвестное действие']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}

function handleGetTable() {
    global $pdo;
    
    $table = $_GET['table'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $allowedTables = [
        'patient', 'doctor', 'usluga', 'zapis', 'transakciya', 
        'doctor_usluga', 'raspisanie'
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Неверное имя таблицы');
    }
    
    $sql = "SELECT * FROM $table";
    $params = [];
    
    if ($search) {
        $columns = getTableColumns($table);
        $searchConditions = [];
        
        foreach ($columns as $column) {
            if (!in_array($column, ['id', 'password', 'ps'])) {
                $searchConditions[] = "$column LIKE ?";
                $params[] = "%$search%";
            }
        }
        
        if ($searchConditions) {
            $sql .= " WHERE " . implode(" OR ", $searchConditions);
        }
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Для некоторых таблиц добавляем дополнительные данные
    if ($table === 'zapis') {
        foreach ($data as &$row) {
            $row['patient_name'] = getPatientName($row['id_patient']);
            $row['doctor_name'] = getDoctorName($row['id_doctor']);
            $row['service_name'] = getServiceName($row['id_usluga']);
        }
    }
    
    echo json_encode($data);
}

function handleGetForm() {
    global $pdo;
    
    $table = $_GET['table'] ?? '';
    $id = $_GET['id'] ?? null;
    
    $allowedTables = [
        'patient', 'doctor', 'usluga', 'zapis', 'transakciya', 
        'doctor_usluga', 'raspisanie'
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Неверное имя таблицы');
    }
    
    $columns = getTableColumns($table);
    $row = [];
    
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id_$table = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $html = '';
    foreach ($columns as $column) {
        if ($column === 'id_'.$table || $column === 'id') continue;
        
        $html .= renderFormField($table, $column, $row[$column] ?? '');
    }
    
    echo $html;
}

function handleSave() {
    global $pdo;
    
    $table = $_POST['table'] ?? '';
    $id = $_POST['id'] ?? null;
    
    $allowedTables = [
        'patient', 'doctor', 'usluga', 'zapis', 'transakciya', 
        'doctor_usluga', 'raspisanie'
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Неверное имя таблицы');
    }
    
    $columns = getTableColumns($table);
    $data = [];
    
    foreach ($columns as $column) {
        if ($column === 'id_'.$table || $column === 'id') continue;
        if (isset($_POST[$column])) {
            $data[$column] = $_POST[$column];
        }
    }
    
    if ($id) {
        // Обновление существующей записи
        $set = [];
        foreach ($data as $field => $value) {
            $set[] = "$field = :$field";
        }
        
        $sql = "UPDATE $table SET ".implode(', ', $set)." WHERE id_$table = :id";
        $data['id'] = $id;
    } else {
        // Создание новой записи
        $fields = array_keys($data);
        $values = ':' . implode(', :', $fields);
        
        $sql = "INSERT INTO $table (".implode(', ', $fields).") VALUES ($values)";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    
    echo json_encode(['success' => true]);
}

function handleDelete() {
    global $pdo;
    
    $table = $_POST['table'] ?? '';
    $id = $_POST['id'] ?? null;
    
    $allowedTables = [
        'patient', 'doctor', 'usluga', 'zapis', 'transakciya', 
        'doctor_usluga', 'raspisanie'
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Неверное имя таблицы');
    }
    
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id_$table = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
}

function handleGetReport() {
    global $pdo;
    
    $report = $_GET['report'] ?? '';
    $params = $_GET['params'] ?? [];
    
    switch ($report) {
        case 'popular-services':
            $stmt = $pdo->query("
                SELECT u.id_usluga, u.nazvanie, COUNT(z.id_zapis) as appointments_count
                FROM usluga u
                LEFT JOIN zapis z ON u.id_usluga = z.id_usluga
                GROUP BY u.id_usluga
                ORDER BY appointments_count DESC
            ");
            break;
            
        case 'doctor-workload':
            $stmt = $pdo->query("
                SELECT d.id_doctor, CONCAT(d.name, ' ', d.familia) as doctor_name, 
                       COUNT(z.id_zapis) as appointments_count
                FROM doctor d
                LEFT JOIN zapis z ON d.id_doctor = z.id_doctor
                GROUP BY d.id_doctor
                ORDER BY appointments_count DESC
            ");
            break;
            
        case 'financial':
            $stmt = $pdo->query("
                SELECT t.id_transakciya, t.summa, t.data_oplaty, t.status,
                       u.nazvanie as service_name,
                       CONCAT(p.name, ' ', p.familia) as patient_name
                FROM transakciya t
                JOIN usluga u ON t.id_usluga = u.id_usluga
                JOIN patient p ON t.id_patient = p.id_patient
                ORDER BY t.data_oplaty DESC
            ");
            break;
            
        default:
            throw new Exception('Неизвестный отчет');
    }
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);
}

// Вспомогательные функции
function getTableColumns($table) {
    global $pdo;
    
    $stmt = $pdo->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    return $columns;
}

function renderFormField($table, $field, $value) {
    global $pdo;
    
    $html = '<div class="form-group">';
    $html .= '<label for="'.$field.'">'.ucfirst(str_replace('_', ' ', $field)).'</label>';
    
    // Для внешних ключей создаем select
    if (strpos($field, 'id_') === 0 && $field !== 'id_'.$table) {
        $refTable = substr($field, 3);
        $stmt = $pdo->query("SELECT * FROM $refTable");
        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $html .= '<select name="'.$field.'" id="'.$field.'">';
        foreach ($options as $option) {
            $selected = $value == $option['id_'.$refTable] ? 'selected' : '';
            $display = $option['name'] ?? $option['nazvanie'] ?? $option['id_'.$refTable];
            $html .= '<option value="'.$option['id_'.$refTable].'" '.$selected.'>';
            $html .= htmlspecialchars($display);
            $html .= '</option>';
        }
        $html .= '</select>';
    } else {
        // Определяем тип поля
        $type = 'text';
        if (in_array($field, ['email'])) $type = 'email';
        if (in_array($field, ['phone', 'tel'])) $type = 'tel';
        if (in_array($field, ['date', 'data_priema', 'data_oplaty'])) $type = 'date';
        if (in_array($field, ['time', 'vremya_nachala', 'vremya_okonchaniya'])) $type = 'time';
        if (in_array($field, ['summa', 'cena', 'price'])) $type = 'number';
        if (strpos($field, 'password') !== false) $type = 'password';
        
        $html .= '<input type="'.$type.'" name="'.$field.'" id="'.$field.'" value="'.htmlspecialchars($value).'">';
    }
    
    $html .= '</div>';
    return $html;
}

function getPatientName($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name, familia FROM patient WHERE id_patient = ?");
    $stmt->execute([$id]);
    $patient = $stmt->fetch();
    return $patient ? $patient['name'] . ' ' . $patient['familia'] : '';
}

function getDoctorName($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name, familia FROM doctor WHERE id_doctor = ?");
    $stmt->execute([$id]);
    $doctor = $stmt->fetch();
    return $doctor ? $doctor['name'] . ' ' . $doctor['familia'] : '';
}

function getServiceName($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT nazvanie FROM usluga WHERE id_usluga = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();
    return $service ? $service['nazvanie'] : '';
}