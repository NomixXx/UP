-- UpTaxi Portal Database Schema
-- Создание базы данных для портала UpTaxi

CREATE DATABASE IF NOT EXISTS uptaxi_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uptaxi_portal;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'user', 'moderator') DEFAULT 'user',
    avatar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Таблица новостей
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    summary TEXT,
    author_id INT,
    category VARCHAR(100),
    image_url VARCHAR(255),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
);

-- Таблица разделов/секций
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(20),
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Таблица меню
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    url VARCHAR(255),
    icon VARCHAR(100),
    parent_id INT NULL,
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    role_required ENUM('admin', 'user', 'moderator', 'all') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- Таблица файлов/загрузок
CREATE TABLE IF NOT EXISTS uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    uploaded_by INT,
    upload_type ENUM('image', 'document', 'video', 'other') DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_upload_type (upload_type),
    INDEX idx_uploaded_by (uploaded_by)
);

-- Таблица настроек системы
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Таблица логов активности
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Вставка начальных данных

-- Создание администратора по умолчанию
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@uptaxi.com', 'admin'),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@uptaxi.com', 'user');

-- Создание базовых разделов
INSERT INTO sections (name, description, icon, color, order_index) VALUES 
('Главная', 'Главная страница портала', 'home', '#007bff', 1),
('Новости', 'Новости и объявления', 'newspaper', '#28a745', 2),
('Услуги', 'Наши услуги', 'briefcase', '#ffc107', 3),
('Контакты', 'Контактная информация', 'phone', '#dc3545', 4);

-- Создание базового меню
INSERT INTO menu_items (title, url, icon, order_index, role_required) VALUES 
('Главная', '/', 'home', 1, 'all'),
('Новости', '/news', 'newspaper', 2, 'all'),
('Администрирование', '/admin', 'settings', 3, 'admin'),
('Пользователи', '/users', 'users', 4, 'admin');

-- Базовые настройки
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES 
('site_title', 'UpTaxi Portal', 'string', 'Название сайта'),
('site_description', 'Портал для управления услугами UpTaxi', 'string', 'Описание сайта'),
('max_file_size', '10485760', 'number', 'Максимальный размер файла в байтах'),
('allowed_file_types', '["jpg", "jpeg", "png", "gif", "pdf", "doc", "docx"]', 'json', 'Разрешенные типы файлов'),
('maintenance_mode', 'false', 'boolean', 'Режим обслуживания');

-- Создание индексов для оптимизации
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_news_title ON news(title);
CREATE INDEX idx_sections_order ON sections(order_index);
CREATE INDEX idx_menu_order ON menu_items(order_index);
CREATE INDEX idx_settings_key ON settings(setting_key);

-- Создание представлений для удобства

-- Представление для новостей с информацией об авторе
CREATE VIEW news_with_author AS
SELECT 
    n.*,
    u.username as author_name,
    u.email as author_email
FROM news n
LEFT JOIN users u ON n.author_id = u.id;

-- Представление для активных пунктов меню
CREATE VIEW active_menu AS
SELECT *
FROM menu_items
WHERE is_active = TRUE
ORDER BY order_index;

-- Представление для статистики
CREATE VIEW portal_stats AS
SELECT 
    (SELECT COUNT(*) FROM users WHERE is_active = TRUE) as active_users,
    (SELECT COUNT(*) FROM news WHERE status = 'published') as published_news,
    (SELECT COUNT(*) FROM uploads) as total_uploads,
    (SELECT COUNT(*) FROM sections WHERE is_active = TRUE) as active_sections;

COMMIT;