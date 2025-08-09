# Инструкция по развертыванию UpTaxi Portal на Apache сервере

## Подготовка сервера

### 1. Настройка базы данных MySQL

```sql
-- Создание базы данных
CREATE DATABASE uptaxi_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Создание пользователя
CREATE USER 'uptaxi_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';

-- Предоставление прав
GRANT ALL PRIVILEGES ON uptaxi_portal.* TO 'uptaxi_user'@'localhost';
FLUSH PRIVILEGES;

-- Импорт структуры базы данных
-- mysql -u uptaxi_user -p uptaxi_portal < database.sql
```

### 2. Настройка Apache

Убедитесь, что включены следующие модули Apache:
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires
sudo a2enmod deflate
sudo systemctl restart apache2
```

### 3. Настройка SSL (для HTTPS)

Для работы с DuckDNS рекомендуется использовать Let's Encrypt:
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d portal-uptaxi.duckdns.org
```

## Развертывание файлов

### 1. Загрузка файлов

Загрузите все файлы проекта в корневую директорию вашего веб-сервера:
```bash
/var/www/html/  # или ваша директория
```

### 2. Настройка прав доступа

```bash
# Установка владельца файлов
sudo chown -R www-data:www-data /var/www/html/

# Установка прав на файлы
sudo chmod -R 644 /var/www/html/
sudo chmod -R 755 /var/www/html/uploads/

# Права на выполнение для PHP файлов
sudo chmod 755 /var/www/html/server-api.php
```

### 3. Создание директории для загрузок

```bash
mkdir -p /var/www/html/uploads
chmod 755 /var/www/html/uploads
chown www-data:www-data /var/www/html/uploads
```

## Настройка конфигурации

### 1. Обновление database-config.php

Откройте файл `database-config.php` и обновите настройки подключения к базе данных:

```php
private static $host = 'localhost';
private static $dbname = 'uptaxi_portal';
private static $username = 'uptaxi_user';
private static $password = 'your_actual_secure_password';
```

### 2. Проверка config.js

Убедитесь, что в `config.js` правильно настроены URL для production:

```javascript
production: {
    apiUrl: 'https://portal-uptaxi.duckdns.org/server-api.php',
    uploadUrl: 'https://portal-uptaxi.duckdns.org/uploads',
    // ...
}
```

## Тестирование

### 1. Проверка подключения к базе данных

Откройте в браузере:
```
https://portal-uptaxi.duckdns.org/server-api.php?path=/health
```

Должен вернуться JSON ответ о состоянии сервера.

### 2. Проверка основного сайта

Откройте:
```
https://portal-uptaxi.duckdns.org/
```

### 3. Проверка синхронизации

1. Войдите в систему (admin/admin123)
2. Откройте консоль разработчика (F12)
3. Проверьте, что нет ошибок CORS
4. Убедитесь, что данные синхронизируются с сервером

## Возможные проблемы и решения

### 1. Ошибки CORS

Если возникают ошибки CORS, проверьте:
- Файл `.htaccess` загружен и активен
- Модуль `headers` включен в Apache
- Правильно указан домен в настройках CORS

### 2. Ошибки подключения к базе данных

- Проверьте настройки в `database-config.php`
- Убедитесь, что пользователь базы данных создан
- Проверьте, что база данных импортирована

### 3. Проблемы с загрузкой файлов

- Проверьте права доступа к директории `uploads/`
- Убедитесь, что размер загружаемых файлов не превышает лимиты PHP

### 4. Настройка PHP лимитов

В файле `php.ini` или `.htaccess`:
```
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
```

## Безопасность

1. Измените пароли по умолчанию в системе
2. Используйте сильные пароли для базы данных
3. Регулярно обновляйте систему
4. Настройте резервное копирование базы данных

## Мониторинг

Для мониторинга работы системы проверяйте:
- Логи Apache: `/var/log/apache2/error.log`
- Логи PHP: `/var/log/php/error.log`
- Консоль браузера для JavaScript ошибок