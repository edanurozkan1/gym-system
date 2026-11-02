-- ============================================
-- GYM SYSTEM - Database Schema
-- CPE212 Project - Group 35
-- ============================================

CREATE DATABASE IF NOT EXISTS gym_system CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE gym_system;

-- ============================================
-- ENTITY 1: Users (Üyeler & Adminler)
-- ============================================
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(64)  NOT NULL,  -- SHA256 hash
    phone       VARCHAR(20),
    role        ENUM('member','admin','trainer') DEFAULT 'member',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- ENTITY 2: Memberships (Üyelik Tipleri)
-- ============================================
CREATE TABLE memberships (
    membership_id   INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,       -- Aylık, 3 Aylık, Yıllık
    duration_days   INT NOT NULL,
    price           DECIMAL(10,2) NOT NULL,
    description     TEXT
);

-- ============================================
-- ENTITY 3: Trainers (Antrenörler)
-- ============================================
CREATE TABLE trainers (
    trainer_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    specialty   VARCHAR(150),                   -- Yoga, Pilates, Fitness...
    bio         TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- ENTITY 4: Classes (Ders Türleri)
-- ============================================
CREATE TABLE classes (
    class_id    INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id  INT NOT NULL,
    name        VARCHAR(100) NOT NULL,           -- Sabah Yogası, HIIT...
    description TEXT,
    duration    INT NOT NULL,                   -- Dakika cinsinden
    max_capacity INT NOT NULL DEFAULT 20,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE
);

-- ============================================
-- ENTITY 5: Schedule (Ders Programı)
-- ============================================
CREATE TABLE schedule (
    schedule_id     INT AUTO_INCREMENT PRIMARY KEY,
    class_id        INT NOT NULL,
    day_of_week     ENUM('Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar') NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    room            VARCHAR(50),
    current_enrolled INT DEFAULT 0,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE
);

-- ============================================
-- ENTITY 6: Enrollments (Derse Kayıt)
-- ============================================
CREATE TABLE enrollments (
    enrollment_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    schedule_id     INT NOT NULL,
    enrolled_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('active','cancelled') DEFAULT 'active',
    UNIQUE KEY unique_enrollment (user_id, schedule_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedule(schedule_id) ON DELETE CASCADE
);

-- ============================================
-- ENTITY 7: Payments (Ödemeler)
-- ============================================
CREATE TABLE payments (
    payment_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    membership_id   INT NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    payment_date    DATETIME DEFAULT CURRENT_TIMESTAMP,
    expiry_date     DATE NOT NULL,
    status          ENUM('active','expired','cancelled') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (membership_id) REFERENCES memberships(membership_id)
);

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Admin kullanıcı (password: admin123)
INSERT INTO users (name, email, password, phone, role) VALUES
('Admin', 'admin@gym.com', SHA2('admin123', 256), '05001234567', 'admin');

-- Üyeler (password: 123456)
INSERT INTO users (name, email, password, phone, role) VALUES
('Ahmet Yılmaz', 'ahmet@mail.com', SHA2('123456', 256), '05321234567', 'member'),
('Ayşe Kaya', 'ayse@mail.com', SHA2('123456', 256), '05331234567', 'member'),
('Mehmet Demir', 'mehmet@mail.com', SHA2('123456', 256), '05351234567', 'trainer');

-- Antrenörler
INSERT INTO trainers (user_id, specialty, bio) VALUES
(4, 'Yoga & Pilates', 'Sertifikalı yoga eğitmeni, 5 yıl deneyim.');

-- Üyelik tipleri
INSERT INTO memberships (name, duration_days, price, description) VALUES
('Aylık Üyelik', 30, 299.00, 'Tüm derslere sınırsız erişim'),
('3 Aylık Üyelik', 90, 749.00, 'Tüm derslere sınırsız erişim + %15 indirim'),
('Yıllık Üyelik', 365, 2499.00, 'Tüm derslere sınırsız erişim + %30 indirim');

-- Dersler
INSERT INTO classes (trainer_id, name, description, duration, max_capacity) VALUES
(1, 'Sabah Yogası', 'Güne zinde başlamak için hatha yoga seansı.', 60, 15),
(1, 'Akşam Pilates', 'Core güçlendirme ve esneklik çalışması.', 45, 12),
(1, 'HIIT Cardio', 'Yüksek yoğunluklu interval antrenman.', 45, 20),
(1, 'Meditasyon', 'Zihin ve beden uyumu için rehberli meditasyon.', 30, 10);

-- Ders programı
INSERT INTO schedule (class_id, day_of_week, start_time, end_time, room, current_enrolled) VALUES
(1, 'Pazartesi', '07:00:00', '08:00:00', 'Salon A', 5),
(1, 'Çarşamba', '07:00:00', '08:00:00', 'Salon A', 3),
(2, 'Salı', '18:00:00', '18:45:00', 'Salon B', 8),
(2, 'Perşembe', '18:00:00', '18:45:00', 'Salon B', 6),
(3, 'Pazartesi', '19:00:00', '19:45:00', 'Salon C', 15),
(3, 'Cuma', '19:00:00', '19:45:00', 'Salon C', 10),
(4, 'Cumartesi', '10:00:00', '10:30:00', 'Salon A', 2);
