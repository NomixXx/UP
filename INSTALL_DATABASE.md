# Установка и настройка базы данных для UpTaxi Portal

Это руководство поможет вам настроить MySQL базу данных для портала UpTaxi, что обеспечит синхронизацию данных между разными компьютерами.

## Требования

- MySQL 5.7+ или MariaDB 10.2+
- PHP 7.4+ с расширениями:
  - PDO
  - pdo_mysql
  - json
- Apache или Nginx веб-сервер

## Шаг 1: Установка MySQL

### На Ubuntu/Debian:
```bash
sudo apt update
sudo apt install mysql-server mysql-client
sudo mysql_secure_installation
```

### На CentOS/RHEL:
```bash
sudo yum install mysql-server mysql
# или для новых версий:
sudo dnf install mysql-server mysql
sudo systemctl start mysqld
sudo systemctl enable mysqld
sudo mysql_secure_installation
```

### На Windows:
1. Скачайте MySQL Installer с официального сайта
2. Установите MySQL Server
3. Запомните пароль root пользователя

## Шаг 2: Создание базы данных

### Вариант 1: Через командную строку MySQL
```bash
mysql -u root -p
```

Выполните команды:
```sql
CREATE DATABASE uptaxi_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'uptaxi_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON uptaxi_portal.* TO 'uptaxi_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Вариант 2: Импорт готовой схемы
```bash
mysql -u root -p < database.sql
```

## Шаг 3: Настройка подключения

Откройте файл `database-config.php` и обновите настройки подключения:

```php
private static $host = 'localhost';        // Адрес сервера БД
private static $dbname = 'uptaxi_portal';  // Имя базы данных
private static $username = 'uptaxi_user';  // Имя пользователя
private static $password = 'your_secure_password'; // Пароль
```

## Шаг 4: Установка PHP расширений

### На Ubuntu/Debian:
```bash
sudo apt install php-mysql php-pdo php-json
sudo systemctl restart apache2
```

### На CentOS/RHEL:
```bash
sudo yum install php-mysql php-pdo php-json
# или
sudo dnf install php-mysql php-pdo php-json
sudo systemctl restart httpd
```

## Шаг 5: Проверка подключения

1. Откройте браузер и перейдите по адресу:
   ```
   http://your-domain.com/server-api.php/health
   ```

2. Вы должны увидеть ответ:
   ```json
   {
     "status": "ok",
     "timestamp": 1234567890,
     "database": true
   }
   ```

## Шаг 6: Миграция данных из localStorage

Если у вас уже есть данные в localStorage, вы можете их перенести:

1. Откройте консоль браузера (F12)
2. Выполните следующий код для экспорта данных:

```javascript
// Экспорт всех данных из localStorage
const exportData = {};
for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    if (key.startsWith('uptaxi_')) {
        exportData[key] = JSON.parse(localStorage.getItem(key));
    }
}
console.log('Экспортированные данные:', JSON.stringify(exportData, null, 2));
```

3. Скопируйте данные и сохраните их в файл
4. Используйте API для импорта данных в базу

## Структура базы данных

### Основные таблицы:

- **users** - Пользователи системы
- **news** - Новости и статьи
- **sections** - Разделы портала
- **menu_items** - Пункты меню
- **uploads** - Загруженные файлы
- **settings** - Настройки системы
- **activity_logs** - Логи активности пользователей

### Представления (Views):

- **news_with_author** - Новости с информацией об авторе
- **active_menu** - Активные пункты меню
- **portal_stats** - Статистика портала

## Безопасность

### Рекомендации:

1. **Используйте сильные пароли** для пользователя базы данных
2. **Ограничьте доступ** к базе данных только с нужных IP-адресов
3. **Регулярно создавайте резервные копии**:
   ```bash
   mysqldump -u uptaxi_user -p uptaxi_portal > backup_$(date +%Y%m%d_%H%M%S).sql
   ```
4. **Обновляйте MySQL** до последних версий
5. **Настройте SSL** для подключений к базе данных

### Настройка файрвола:
```bash
# Разрешить доступ к MySQL только с локального сервера
sudo ufw allow from 127.0.0.1 to any port 3306
```

## Резервное копирование

### Автоматическое резервное копирование:

Создайте скрипт `/home/backup/mysql_backup.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/home/backup/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="uptaxi_portal"
DB_USER="uptaxi_user"
DB_PASS="your_secure_password"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/uptaxi_portal_$DATE.sql

# Удаление старых резервных копий (старше 30 дней)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
```

Добавьте в crontab:
```bash
crontab -e
# Добавьте строку для ежедневного резервного копирования в 2:00
0 2 * * * /home/backup/mysql_backup.sh
```

## Восстановление из резервной копии

```bash
mysql -u uptaxi_user -p uptaxi_portal < backup_file.sql
```

## Мониторинг

### Проверка статуса базы данных:
```bash
sudo systemctl status mysql
mysql -u uptaxi_user -p -e "SHOW PROCESSLIST;"
```

### Проверка размера базы данных:
```sql
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'uptaxi_portal'
GROUP BY table_schema;
```

## Устранение неполадок

### Проблема: "Access denied for user"
```bash
# Сброс пароля root
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'new_password';
FLUSH PRIVILEGES;
EXIT;
```

### Проблема: "Can't connect to MySQL server"
```bash
# Проверка статуса службы
sudo systemctl status mysql
# Запуск службы
sudo systemctl start mysql
# Проверка портов
sudo netstat -tlnp | grep :3306
```

### Проблема: "Table doesn't exist"
```bash
# Повторный импорт схемы
mysql -u uptaxi_user -p uptaxi_portal < database.sql
```

## Производительность

### Оптимизация MySQL:

Добавьте в `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
innodb_buffer_pool_size = 256M
query_cache_size = 64M
query_cache_type = 1
max_connections = 100
```

Перезапустите MySQL:
```bash
sudo systemctl restart mysql
```

## Поддержка

Если у вас возникли проблемы:

1. Проверьте логи MySQL: `/var/log/mysql/error.log`
2. Проверьте логи Apache: `/var/log/apache2/error.log`
3. Проверьте статус подключения через API: `/server-api.php/health`
4. Убедитесь, что все PHP расширения установлены: `php -m | grep -E 'pdo|mysql'`

Теперь ваш портал будет сохранять все данные в базе данных MySQL, что обеспечит синхронизацию между разными компьютерами и устройствами!