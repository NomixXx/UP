 # UpTaxi Portal

Современный веб-портал для управления услугами UpTaxi с адаптивным дизайном, удобным интерфейсом администрирования и поддержкой MySQL базы данных для синхронизации данных между устройствами.

## Особенности

- 🎨 Современный и адаптивный дизайн
- 👥 Система управления пользователями
- 📰 Управление новостями и контентом
- 🔧 Панель администрирования
- 📱 Мобильная адаптация
- 🔒 Система авторизации с хешированием паролей
- 📁 Загрузка и управление файлами
- ⚙️ Настраиваемые разделы и меню
- 🗄️ **MySQL база данных** для синхронизации между устройствами
- 🔄 **Автоматическая синхронизация** изменений в реальном времени
- 📊 Логирование активности пользователей
- 🛡️ Безопасное хранение данных

## Технологии

- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Backend:** PHP 7.4+ с PDO
- **База данных:** MySQL 5.7+ / MariaDB 10.2+
- **Адаптивная верстка:** Mobile-first подход
- **Современные технологии:** CSS Grid, Flexbox
- **Безопасность:** Password hashing, SQL injection protection

## Новая система базы данных

### Преимущества MySQL интеграции:

✅ **Синхронизация между устройствами** - изменения на одном компьютере автоматически отображаются на других

✅ **Надежное хранение данных** - данные сохраняются в профессиональной СУБД

✅ **Масштабируемость** - поддержка множества пользователей одновременно

✅ **Резервное копирование** - простое создание бэкапов базы данных

✅ **Производительность** - быстрые запросы и индексация

✅ **Безопасность** - защищенное хранение паролей и данных

## Установка

### Требования

- **Веб-сервер:** Apache/Nginx
- **PHP:** 7.4+ с расширениями:
  - PDO
  - pdo_mysql
  - json
  - mbstring
- **База данных:** MySQL 5.7+ или MariaDB 10.2+
- **Браузер:** Современный браузер с поддержкой ES6+

### Быстрый старт

1. **Клонируйте репозиторий:**
```bash
git clone https://github.com/NomixXx/UP.git
cd UP
```

2. **Настройте базу данных:**
```bash
# Создайте базу данных
mysql -u root -p < database.sql

# Или создайте вручную:
mysql -u root -p
CREATE DATABASE uptaxi_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'uptaxi_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON uptaxi_portal.* TO 'uptaxi_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

3. **Настройте подключение к БД:**

Откройте `database-config.php` и обновите настройки:
```php
private static $host = 'localhost';
private static $dbname = 'uptaxi_portal';
private static $username = 'uptaxi_user';
private static $password = 'secure_password';
```

4. **Настройте веб-сервер:**
```bash
# Создайте директории
mkdir uploads
chmod 755 uploads

# Настройте права доступа
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 777 uploads
```

5. **Проверьте установку:**

Откройте в браузере: `http://your-domain.com/server-api.php/health`

Должен вернуться ответ:
```json
{
  "status": "ok",
  "timestamp": 1234567890,
  "database": true
}
```

### Подробная установка на Ubuntu Server

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка Apache, PHP и MySQL
sudo apt install apache2 php libapache2-mod-php php-mysql php-pdo php-json php-mbstring mysql-server -y

# Настройка MySQL
sudo mysql_secure_installation

# Клонирование проекта
cd /var/www/html
sudo rm -rf *
sudo git clone https://github.com/NomixXx/UP.git .

# Создание базы данных
sudo mysql -u root -p < database.sql

# Настройка прав доступа
sudo mkdir -p uploads
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 777 uploads

# Перезапуск служб
sudo systemctl restart apache2
sudo systemctl restart mysql
```

📖 **Подробные инструкции:** См. файл `INSTALL_DATABASE.md`

## Использование

### Вход в систему

**Администратор по умолчанию:**
- Логин: `admin`
- Пароль: `admin123`

**Пользователь по умолчанию:**
- Логин: `user`
- Пароль: `user123`

⚠️ **Важно:** Обязательно измените пароли по умолчанию после первого входа!

### Синхронизация данных

🔄 **Автоматическая синхронизация:**
- Все изменения автоматически сохраняются в базу данных
- Данные синхронизируются между всеми подключенными устройствами
- При потере соединения система работает в автономном режиме
- При восстановлении соединения данные автоматически синхронизируются

### Панель администрирования

Доступна по адресу `/admin.html` для пользователей с правами администратора.

**Новые возможности с базой данных:**
- 👥 Управление пользователями с безопасным хранением паролей
- 📰 Создание и редактирование новостей с отслеживанием авторства
- 🗂️ Настройка разделов портала с сортировкой
- 🧭 Управление меню с иерархической структурой
- 📁 Загрузка файлов с метаданными
- 📊 Просмотр статистики и логов активности
- ⚙️ Настройки системы с типизацией

### API Endpoints

**Основные:**
- `GET /server-api.php/health` - Проверка состояния сервера и БД
- `GET /server-api.php/data/{key}` - Получение данных из БД
- `POST /server-api.php/data` - Сохранение данных в БД
- `POST /server-api.php/upload` - Загрузка файлов с сохранением в БД
- `POST /server-api.php/auth` - Аутентификация пользователей

**Поддерживаемые ключи данных:**
- `uptaxi_users` - Пользователи системы
- `uptaxi_news` - Новости и статьи
- `uptaxi_sections` - Разделы портала
- `uptaxi_menu` - Пункты меню
- `uptaxi_settings` - Настройки системы

## Структура проекта

```
uptaxi-portal/
├── index.html              # Главная страница (логин)
├── menu.html               # Основное меню портала
├── admin.html              # Панель администрирования
├── styles.css              # Стили для главной страницы
├── menu.css                # Стили для меню
├── admin.css               # Стили для админ-панели
├── script.js               # Основная логика
├── menu.js                 # Логика меню
├── admin.js                # Логика админ-панели
├── config.js               # Конфигурация и утилиты
├── server-api.php          # Серверный API с поддержкой БД
├── database-config.php     # 🆕 Конфигурация базы данных
├── database.sql            # 🆕 Схема базы данных
├── INSTALL_DATABASE.md     # 🆕 Инструкции по установке БД
├── uploads/                # Директория для загруженных файлов
└── public/
    └── logo-uptaxi.svg     # Логотип
```

## Структура базы данных

### Основные таблицы:

- **users** - Пользователи с хешированными паролями
- **news** - Новости с отслеживанием авторства
- **sections** - Разделы портала с сортировкой
- **menu_items** - Иерархическое меню
- **uploads** - Метаданные загруженных файлов
- **settings** - Типизированные настройки системы
- **activity_logs** - Логи активности пользователей

### Представления (Views):

- **news_with_author** - Новости с информацией об авторе
- **active_menu** - Активные пункты меню
- **portal_stats** - Статистика портала

## Миграция с localStorage

Если у вас уже есть данные в localStorage, используйте этот код для экспорта:

```javascript
// Экспорт данных из localStorage
const exportData = {};
for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    if (key.startsWith('uptaxi_')) {
        exportData[key] = JSON.parse(localStorage.getItem(key));
    }
}
console.log('Данные для миграции:', JSON.stringify(exportData, null, 2));
```

## Безопасность

### Новые функции безопасности:

🔐 **Хеширование паролей** - Все пароли хранятся в зашифрованном виде

🛡️ **Защита от SQL-инъекций** - Использование подготовленных запросов

📝 **Логирование активности** - Отслеживание всех действий пользователей

🔒 **Контроль доступа** - Разграничение прав по ролям

### Рекомендации:

1. **Измените пароли по умолчанию**
2. **Настройте HTTPS** для продакшена
3. **Используйте сильные пароли** для базы данных
4. **Регулярно создавайте резервные копии**
5. **Обновляйте систему** и зависимости
6. **Настройте файрвол** для ограничения доступа к БД

### Резервное копирование:

```bash
# Создание резервной копии
mysqldump -u uptaxi_user -p uptaxi_portal > backup_$(date +%Y%m%d_%H%M%S).sql

# Восстановление из резервной копии
mysql -u uptaxi_user -p uptaxi_portal < backup_file.sql
```

## Устранение неполадок

### База данных

**Проблема:** "Database connection failed"
```bash
# Проверьте статус MySQL
sudo systemctl status mysql

# Проверьте настройки в database-config.php
# Проверьте права пользователя БД
mysql -u uptaxi_user -p
```

**Проблема:** "Table doesn't exist"
```bash
# Импортируйте схему заново
mysql -u uptaxi_user -p uptaxi_portal < database.sql
```

### Файлы и права доступа

**Проблема:** Файлы не загружаются
```bash
# Проверьте права доступа
ls -la uploads/
sudo chmod 777 uploads/
```

**Проблема:** 500 Internal Server Error
```bash
# Проверьте логи
sudo tail -f /var/log/apache2/error.log
# Проверьте PHP расширения
php -m | grep -E 'pdo|mysql'
```

## Мониторинг

### Проверка состояния системы:

```bash
# Статус служб
sudo systemctl status apache2
sudo systemctl status mysql

# Проверка API
curl http://your-domain.com/server-api.php/health

# Размер базы данных
mysql -u uptaxi_user -p -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.tables WHERE table_schema = 'uptaxi_portal' GROUP BY table_schema;"
```

## Changelog

### v2.0.0 - MySQL Integration
- ✨ **Добавлена поддержка MySQL** базы данных
- 🔄 **Синхронизация между устройствами**
- 🔐 **Безопасное хранение паролей** с хешированием
- 📊 **Логирование активности** пользователей
- 🛡️ **Защита от SQL-инъекций**
- 📁 **Улучшенное управление файлами** с метаданными
- ⚙️ **Типизированные настройки** системы
- 🗄️ **Представления базы данных** для оптимизации
- 📖 **Подробная документация** по установке

### v1.0.0
- Первый релиз с localStorage
- Базовая функциональность портала
- Система авторизации
- Панель администрирования

---

**UpTaxi Portal v2.0** - профессиональное решение для управления корпоративным порталом с надежной базой данных MySQL, обеспечивающей синхронизацию данных между всеми устройствами и высокий уровень безопасности.