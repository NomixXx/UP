-- Удаление таблиц UpTaxi Portal

DROP TABLE IF EXISTS `#__uptaxi_content`;
DROP TABLE IF EXISTS `#__uptaxi_google_docs`;
DROP TABLE IF EXISTS `#__uptaxi_activities`;
DROP TABLE IF EXISTS `#__uptaxi_subsections`;
DROP TABLE IF EXISTS `#__uptaxi_sections`;
DROP TABLE IF EXISTS `#__uptaxi_access_levels`;

-- Удаляем добавленное поле из таблицы пользователей
ALTER TABLE `#__users` DROP COLUMN IF EXISTS `uptaxi_access_level`;