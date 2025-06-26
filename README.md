# GlossClinic - Система записи в клинику эстетической медицины

1. **Установка зависимостей**:
   ```bash
   npm install

## Database Structure

### Core Tables
-- Создание базы данных
CREATE DATABASE IF NOT EXISTS gloss_clinic;
USE gloss_clinic;

-- Таблица пациентов
CREATE TABLE patient (
    id_patient INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    familia VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100),
    date_of_birth DATE,
    adress TEXT,
    date_registr DATETIME DEFAULT CURRENT_TIMESTAMP,
    ps VARCHAR(255)
);

-- Таблица врачей
CREATE TABLE doctor (
    id_doctor INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    familia VARCHAR(50) NOT NULL,
    specializaciya VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    staj_let INT,
    info TEXT,
    foto VARCHAR(255)
);

-- Таблица услуг
CREATE TABLE usluga (
    id_usluga INT AUTO_INCREMENT PRIMARY KEY,
    nazvanie VARCHAR(100) NOT NULL,
    kategoriya VARCHAR(50),
    tip VARCHAR(50),
    dlitelnost_min INT,
    cena DECIMAL(10,2),
    opisanie TEXT
);

-- Связь врачей и услуг
CREATE TABLE doctor_usluga (
    id_doctor_usluga INT AUTO_INCREMENT PRIMARY KEY,
    id_doctor INT NOT NULL,
    id_usluga INT NOT NULL,
    FOREIGN KEY (id_doctor) REFERENCES doctor(id_doctor),
    FOREIGN KEY (id_usluga) REFERENCES usluga(id_usluga)
);

-- Расписание врачей
CREATE TABLE raspisanie (
    id_raspisanie INT AUTO_INCREMENT PRIMARY KEY,
    id_doctor INT NOT NULL,
    den_nedeli INT NOT NULL, -- 1-пн, 2-вт и т.д.
    vremya_nachala TIME NOT NULL,
    vremya_okonchaniya TIME NOT NULL,
    FOREIGN KEY (id_doctor) REFERENCES doctor(id_doctor)
);

-- Записи на прием
CREATE TABLE zapis (
    id_zapis INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    id_doctor INT NOT NULL,
    id_usluga INT NOT NULL,
    data_priema DATE NOT NULL,
    vremya_nachala TIME NOT NULL,
    vremya_okonchaniya TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    zametki TEXT,
    FOREIGN KEY (id_patient) REFERENCES patient(id_patient),
    FOREIGN KEY (id_doctor) REFERENCES doctor(id_doctor),
    FOREIGN KEY (id_usluga) REFERENCES usluga(id_usluga)
);

-- Транзакции
CREATE TABLE transakciya (
    id_transakciya INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    id_usluga INT NOT NULL,
    id_zapis INT,
    summa DECIMAL(10,2) NOT NULL,
    data_oplaty DATETIME DEFAULT CURRENT_TIMESTAMP,
    metod_oplaty ENUM('cash', 'card', 'online'),
    status ENUM('pending', 'completed', 'failed'),
    FOREIGN KEY (id_patient) REFERENCES patient(id_patient),
    FOREIGN KEY (id_usluga) REFERENCES usluga(id_usluga),
    FOREIGN KEY (id_zapis) REFERENCES zapis(id_zapis)
);

### Database Views
-- 1. Представление для услуг по типу и категории
CREATE VIEW view_services_by_type_category AS
SELECT * FROM usluga
ORDER BY tip, kategoriya;

-- 2. Представление для популярных услуг
CREATE VIEW view_popular_services AS
SELECT u.*, COUNT(z.id_zapis) as appointment_count 
FROM usluga u
LEFT JOIN zapis z ON u.id_usluga = z.id_usluga
GROUP BY u.id_usluga
ORDER BY appointment_count DESC;

-- 3. Представление для пациентов врача по профилю
CREATE VIEW view_patients_by_doctor_specialty AS
SELECT p.*, d.specializaciya as doctor_specialty
FROM patient p
JOIN zapis z ON p.id_patient = z.id_patient
JOIN doctor d ON z.id_doctor = d.id_doctor
GROUP BY p.id_patient, d.specializaciya;

-- 4. Представление для операций врача за период
CREATE VIEW view_operations_by_doctor_period AS
SELECT p.*, d.name as doctor_name, d.familia as doctor_familia, 
       z.vremya_nachala as procedure_time, u.nazvanie as service_name
FROM patient p
JOIN zapis z ON p.id_patient = z.id_patient
JOIN doctor d ON z.id_doctor = d.id_doctor
JOIN usluga u ON z.id_usluga = u.id_usluga
WHERE u.tip = 'operation' AND z.status = 'completed';

-- 5. Представление для новых пациентов
CREATE VIEW view_new_patients AS
SELECT * FROM patient
WHERE date_registr >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY date_registr DESC;

-- 6. Представление для врачей по профилю и стажу
CREATE VIEW view_doctors_by_specialty_experience AS
SELECT *, 
       CONCAT(name, ' ', familia) as full_name,
       staj_let as experience_years
FROM doctor
ORDER BY specializaciya, staj_let DESC;

-- 7. Представление для выработки врачей
CREATE VIEW view_doctor_productivity AS
SELECT d.id_doctor, CONCAT(d.name, ' ', d.familia) as doctor_name,
       d.specializaciya, COUNT(z.id_zapis) as total_appointments,
       COUNT(z.id_zapis) / COUNT(DISTINCT DATE(z.vremya_nachala)) as avg_per_day
FROM doctor d
LEFT JOIN zapis z ON d.id_doctor = z.id_doctor
WHERE z.status = 'completed'
GROUP BY d.id_doctor;

-- 8. Представление для статистики кабинетов
CREATE VIEW view_cabinet_stats AS
SELECT 
    d.id_doctor,
    CONCAT(d.name, ' ', d.familia) as doctor_name,
    COUNT(z.id_zapis) as visits_count,
    MIN(z.vremya_nachala) as first_visit,
    MAX(z.vremya_nachala) as last_visit
FROM doctor d
JOIN zapis z ON d.id_doctor = z.id_doctor
GROUP BY d.id_doctor;

-- 9. Представление для услуг пациента за период
CREATE VIEW view_patient_services AS
SELECT p.id_patient, CONCAT(p.name, ' ', p.familia) as patient_name,
       u.nazvanie as service_name, u.cena, z.vremya_nachala as service_time,
       t.data_oplaty, t.summa, t.status as payment_status
FROM patient p
JOIN zapis z ON p.id_patient = z.id_patient
JOIN usluga u ON z.id_usluga = u.id_usluga
LEFT JOIN transakciya t ON z.id_zapis = t.id_zapis;

-- 10. Представление для заявок на оплату по типу услуги
CREATE VIEW view_payment_requests_by_service_type AS
SELECT t.*, u.nazvanie as service_name, u.tip as service_type,
       CONCAT(p.name, ' ', p.familia) as patient_name
FROM transakciya t
JOIN usluga u ON t.id_usluga = u.id_usluga
JOIN patient p ON t.id_patient = p.id_patient
WHERE t.status = 'pending';

### Running the Project
npm install
npm start "http://localhost:8888/glossclinic/index.html"
