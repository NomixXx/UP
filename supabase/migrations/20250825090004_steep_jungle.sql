-- UpTaxi Portal Database Schema for Joomla

CREATE TABLE IF NOT EXISTS `#__uptaxi_sections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `icon` varchar(100),
    `access_level` int(11) DEFAULT 1,
    `ordering` int(11) DEFAULT 0,
    `published` tinyint(1) DEFAULT 1,
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT 0,
    `modified` datetime DEFAULT NULL,
    `modified_by` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_published` (`published`),
    KEY `idx_access_level` (`access_level`),
    KEY `idx_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__uptaxi_subsections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `section_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` text,
    `access_level` int(11) DEFAULT 1,
    `ordering` int(11) DEFAULT 0,
    `published` tinyint(1) DEFAULT 1,
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT 0,
    `modified` datetime DEFAULT NULL,
    `modified_by` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_section_id` (`section_id`),
    KEY `idx_published` (`published`),
    KEY `idx_access_level` (`access_level`),
    FOREIGN KEY (`section_id`) REFERENCES `#__uptaxi_sections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__uptaxi_content` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `section_id` int(11) NOT NULL,
    `subsection_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` longtext NOT NULL,
    `content_type` enum('text','document','file','photo') DEFAULT 'text',
    `file_path` varchar(500),
    `file_size` int(11),
    `mime_type` varchar(100),
    `published` tinyint(1) DEFAULT 1,
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT 0,
    `modified` datetime DEFAULT NULL,
    `modified_by` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_section_subsection` (`section_id`, `subsection_id`),
    KEY `idx_published` (`published`),
    KEY `idx_content_type` (`content_type`),
    FOREIGN KEY (`section_id`) REFERENCES `#__uptaxi_sections`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subsection_id`) REFERENCES `#__uptaxi_subsections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__uptaxi_google_docs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `section_id` int(11) NOT NULL,
    `subsection_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `url` text NOT NULL,
    `published` tinyint(1) DEFAULT 1,
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT 0,
    `modified` datetime DEFAULT NULL,
    `modified_by` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_section_subsection` (`section_id`, `subsection_id`),
    KEY `idx_published` (`published`),
    FOREIGN KEY (`section_id`) REFERENCES `#__uptaxi_sections`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subsection_id`) REFERENCES `#__uptaxi_subsections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__uptaxi_activities` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `icon` varchar(50) DEFAULT '📝',
    `description` text,
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__uptaxi_access_levels` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `level` int(11) NOT NULL,
    `published` tinyint(1) DEFAULT 1,
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level` (`level`),
    KEY `idx_published` (`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавляем поле access_level к пользователям Joomla
ALTER TABLE `#__users` ADD COLUMN `uptaxi_access_level` int(11) DEFAULT 1;

-- Вставляем начальные данные
INSERT INTO `#__uptaxi_access_levels` (`name`, `description`, `level`, `created_by`) VALUES
('Базовый', 'Базовый уровень доступа', 1, 0),
('Расширенный', 'Расширенный уровень доступа', 2, 0),
('Полный', 'Полный уровень доступа', 3, 0),
('Администратор', 'Административный доступ', 10, 0);

INSERT INTO `#__uptaxi_sections` (`name`, `description`, `icon`, `access_level`, `ordering`, `created_by`) VALUES
('Раздел 1', 'Первый раздел портала', '📁', 1, 1, 0),
('Раздел 2', 'Второй раздел портала', '📂', 2, 2, 0),
('Раздел 3', 'Третий раздел портала', '📋', 3, 3, 0);

INSERT INTO `#__uptaxi_subsections` (`section_id`, `name`, `description`, `access_level`, `ordering`, `created_by`) VALUES
(1, 'Подраздел 1.1', 'Первый подраздел первого раздела', 1, 1, 0),
(1, 'Подраздел 1.2', 'Второй подраздел первого раздела', 1, 2, 0),
(1, 'Подраздел 1.3', 'Третий подраздел первого раздела', 1, 3, 0),
(2, 'Подраздел 2.1', 'Первый подраздел второго раздела', 2, 1, 0),
(2, 'Подраздел 2.2', 'Второй подраздел второго раздела', 2, 2, 0),
(2, 'Подраздел 2.3', 'Третий подраздел второго раздела', 2, 3, 0),
(3, 'Подраздел 3.1', 'Первый подраздел третьего раздела', 3, 1, 0),
(3, 'Подраздел 3.2', 'Второй подраздел третьего раздела', 3, 2, 0),
(3, 'Подраздел 3.3', 'Третий подраздел третьего раздела', 3, 3, 0);

INSERT INTO `#__uptaxi_activities` (`title`, `icon`, `description`, `created_by`) VALUES
('Система инициализирована', '🚀', 'Портал UpTaxi успешно установлен', 0),
('Добро пожаловать!', '👋', 'Добро пожаловать в портал UpTaxi', 0);