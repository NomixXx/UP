<?php
// API сервер для UpTaxi Portal с поддержкой MySQL базы данных
// Обрабатывает загрузку файлов и сохранение данных в БД

require_once 'database-config.php';

header('Content-Type: application/json');
// Allow both production and development origins
$allowed_origins = [
    'https://portal-uptaxi.duckdns.org',
    'http://localhost:8080',
    'http://127.0.0.1:8080'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost:8080'); // Default for development
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Конфигурация
$config = [
    'upload_dir' => 'uploads/',
    'max_file_size' => 50 * 1024 * 1024, // 50MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar']
];

// Создание директории для загрузок если не существует
if (!file_exists($config['upload_dir'])) {
    mkdir($config['upload_dir'], 0755, true);
}

// Инициализация базы данных
try {
    $db = new DatabaseManager();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit();
}

// Роутинг
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);

// Если путь передан как GET параметр
if (isset($_GET['path'])) {
    $path = $_GET['path'];
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGet($path, $config, $db);
        break;
    case 'POST':
        handlePost($path, $config, $db);
        break;
    case 'PUT':
        handlePut($path, $config, $db);
        break;
    case 'DELETE':
        handleDelete($path, $config, $db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGet($path, $config, $db) {
    if ($path === '/health') {
        echo json_encode(['status' => 'ok', 'timestamp' => time(), 'database' => DatabaseConfig::testConnection()]);
        return;
    }
    
    if (strpos($path, '/data/') === 0) {
        $key = substr($path, 6);
        
        try {
            switch ($key) {
                case 'uptaxi_users':
                    $data = $db->select('SELECT id, username, email, role, avatar_url, created_at, last_login, is_active FROM users WHERE is_active = 1');
                    break;
                    
                case 'uptaxi_news':
                    $data = $db->select('SELECT * FROM news_with_author ORDER BY created_at DESC');
                    break;
                    
                case 'uptaxi_sections':
                    $data = $db->select('SELECT * FROM sections WHERE is_active = 1 ORDER BY order_index');
                    break;
                    
                case 'uptaxi_menu':
                    $data = $db->select('SELECT * FROM active_menu');
                    break;
                    
                case 'uptaxi_settings':
                    $settings = $db->select('SELECT setting_key, setting_value, setting_type FROM settings');
                    $data = [];
                    foreach ($settings as $setting) {
                        $value = $setting['setting_value'];
                        if ($setting['setting_type'] === 'json') {
                            $value = json_decode($value, true);
                        } elseif ($setting['setting_type'] === 'boolean') {
                            $value = $value === 'true';
                        } elseif ($setting['setting_type'] === 'number') {
                            $value = is_numeric($value) ? (float)$value : $value;
                        }
                        $data[$setting['setting_key']] = $value;
                    }
                    break;
                    
                default:
                    // Для совместимости со старыми ключами
                    $data = [];
                    break;
            }
            
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
        return;
    }
    
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}

function handlePost($path, $config, $db) {
    if ($path === '/upload') {
        handleFileUpload($config, $db);
        return;
    }
    
    if ($path === '/data') {
        handleDataSave($config, $db);
        return;
    }
    
    if ($path === '/auth') {
        handleAuth($db);
        return;
    }
    
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}

function handleFileUpload($config, $db) {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded']);
        return;
    }
    
    $file = $_FILES['file'];
    $userId = $_POST['userId'] ?? null;
    
    // Проверка размера файла
    if ($file['size'] > $config['max_file_size']) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large']);
        return;
    }
    
    // Проверка расширения
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $config['allowed_extensions'])) {
        http_response_code(400);
        echo json_encode(['error' => 'File type not allowed']);
        return;
    }
    
    // Генерация уникального имени файла
    $filename = uniqid() . '_' . $file['name'];
    $filepath = $config['upload_dir'] . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        try {
            // Сохранение информации о файле в базу данных
            $uploadData = [
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_path' => $filepath,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'uploaded_by' => $userId,
                'upload_type' => getFileType($extension)
            ];
            
            $uploadId = $db->insert('uploads', $uploadData);
            
            // Логирование активности
            if ($userId) {
                $db->logActivity($userId, 'file_upload', 'upload', $uploadId, ['filename' => $file['name']]);
            }
            
            echo json_encode([
                'success' => true,
                'id' => $uploadId,
                'url' => '/uploads/' . $filename,
                'filename' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type']
            ]);
        } catch (Exception $e) {
            // Удаляем файл если не удалось сохранить в БД
            unlink($filepath);
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save file info to database']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
    }
}

function handleDataSave($config, $db) {
    // Получение данных из запроса
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['key']) || !isset($data['data'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data format']);
        return;
    }
    
    $key = $data['key'];
    $content = $data['data'];
    $userId = $data['userId'] ?? null;
    
    try {
        switch ($key) {
            case 'uptaxi_users':
                saveUsers($db, $content, $userId);
                break;
                
            case 'uptaxi_news':
                saveNews($db, $content, $userId);
                break;
                
            case 'uptaxi_sections':
                saveSections($db, $content, $userId);
                break;
                
            case 'uptaxi_menu':
                saveMenu($db, $content, $userId);
                break;
                
            case 'uptaxi_settings':
                saveSettings($db, $content, $userId);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Unknown data key']);
                return;
        }
        
        echo json_encode(['success' => true, 'message' => 'Data saved successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save data', 'message' => $e->getMessage()]);
    }
}

function handlePut($path, $config, $db) {
    http_response_code(501);
    echo json_encode(['error' => 'Not implemented']);
}

function handleDelete($path, $config, $db) {
    http_response_code(501);
    echo json_encode(['error' => 'Not implemented']);
}

// Функция аутентификации
function handleAuth($db) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        return;
    }
    
    try {
        $user = $db->selectOne('SELECT * FROM users WHERE username = ? AND is_active = 1', [$data['username']]);
        
        if ($user && password_verify($data['password'], $user['password'])) {
            // Обновляем время последнего входа
            $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            
            // Логируем вход
            $db->logActivity($user['id'], 'login', 'user', $user['id']);
            
            // Возвращаем данные пользователя (без пароля)
            unset($user['password']);
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Authentication error', 'message' => $e->getMessage()]);
    }
}

// Определение типа файла
function getFileType($extension) {
    $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'];
    $documentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'rtf'];
    $videoTypes = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    
    if (in_array($extension, $imageTypes)) {
        return 'image';
    } elseif (in_array($extension, $documentTypes)) {
        return 'document';
    } elseif (in_array($extension, $videoTypes)) {
        return 'video';
    } else {
        return 'other';
    }
}

// Функции сохранения данных
function saveUsers($db, $users, $userId) {
    // Для безопасности, только админы могут изменять пользователей
    if ($userId) {
        $currentUser = $db->selectOne('SELECT role FROM users WHERE id = ?', [$userId]);
        if (!$currentUser || $currentUser['role'] !== 'admin') {
            throw new Exception('Access denied');
        }
    }
    
    foreach ($users as $user) {
        if (isset($user['id']) && $user['id']) {
            // Обновление существующего пользователя
            $updateData = [
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'avatar_url' => $user['avatar_url'] ?? null,
                'is_active' => $user['is_active'] ?? true
            ];
            
            // Обновляем пароль только если он предоставлен
            if (!empty($user['password'])) {
                $updateData['password'] = password_hash($user['password'], PASSWORD_DEFAULT);
            }
            
            $db->update('users', $updateData, 'id = ?', [$user['id']]);
        } else {
            // Создание нового пользователя
            $insertData = [
                'username' => $user['username'],
                'password' => password_hash($user['password'] ?? 'password123', PASSWORD_DEFAULT),
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user',
                'avatar_url' => $user['avatar_url'] ?? null,
                'is_active' => $user['is_active'] ?? true
            ];
            
            $db->insert('users', $insertData);
        }
    }
}

function saveNews($db, $news, $userId) {
    foreach ($news as $article) {
        if (isset($article['id']) && $article['id']) {
            // Обновление существующей новости
            $updateData = [
                'title' => $article['title'],
                'content' => $article['content'],
                'summary' => $article['summary'] ?? null,
                'category' => $article['category'] ?? null,
                'image_url' => $article['image_url'] ?? null,
                'status' => $article['status'] ?? 'draft'
            ];
            
            if ($article['status'] === 'published' && !isset($article['published_at'])) {
                $updateData['published_at'] = date('Y-m-d H:i:s');
            }
            
            $db->update('news', $updateData, 'id = ?', [$article['id']]);
        } else {
            // Создание новой новости
            $insertData = [
                'title' => $article['title'],
                'content' => $article['content'],
                'summary' => $article['summary'] ?? null,
                'author_id' => $userId,
                'category' => $article['category'] ?? null,
                'image_url' => $article['image_url'] ?? null,
                'status' => $article['status'] ?? 'draft'
            ];
            
            if ($article['status'] === 'published') {
                $insertData['published_at'] = date('Y-m-d H:i:s');
            }
            
            $db->insert('news', $insertData);
        }
    }
}

function saveSections($db, $sections, $userId) {
    foreach ($sections as $section) {
        if (isset($section['id']) && $section['id']) {
            // Обновление существующего раздела
            $updateData = [
                'name' => $section['name'],
                'description' => $section['description'] ?? null,
                'icon' => $section['icon'] ?? null,
                'color' => $section['color'] ?? null,
                'order_index' => $section['order_index'] ?? 0,
                'is_active' => $section['is_active'] ?? true
            ];
            
            $db->update('sections', $updateData, 'id = ?', [$section['id']]);
        } else {
            // Создание нового раздела
            $insertData = [
                'name' => $section['name'],
                'description' => $section['description'] ?? null,
                'icon' => $section['icon'] ?? null,
                'color' => $section['color'] ?? null,
                'order_index' => $section['order_index'] ?? 0,
                'is_active' => $section['is_active'] ?? true
            ];
            
            $db->insert('sections', $insertData);
        }
    }
}

function saveMenu($db, $menuItems, $userId) {
    foreach ($menuItems as $item) {
        if (isset($item['id']) && $item['id']) {
            // Обновление существующего пункта меню
            $updateData = [
                'title' => $item['title'],
                'url' => $item['url'] ?? null,
                'icon' => $item['icon'] ?? null,
                'parent_id' => $item['parent_id'] ?? null,
                'order_index' => $item['order_index'] ?? 0,
                'is_active' => $item['is_active'] ?? true,
                'role_required' => $item['role_required'] ?? 'all'
            ];
            
            $db->update('menu_items', $updateData, 'id = ?', [$item['id']]);
        } else {
            // Создание нового пункта меню
            $insertData = [
                'title' => $item['title'],
                'url' => $item['url'] ?? null,
                'icon' => $item['icon'] ?? null,
                'parent_id' => $item['parent_id'] ?? null,
                'order_index' => $item['order_index'] ?? 0,
                'is_active' => $item['is_active'] ?? true,
                'role_required' => $item['role_required'] ?? 'all'
            ];
            
            $db->insert('menu_items', $insertData);
        }
    }
}

function saveSettings($db, $settings, $userId) {
    foreach ($settings as $key => $value) {
        $settingType = 'string';
        $settingValue = $value;
        
        if (is_bool($value)) {
            $settingType = 'boolean';
            $settingValue = $value ? 'true' : 'false';
        } elseif (is_numeric($value)) {
            $settingType = 'number';
            $settingValue = (string)$value;
        } elseif (is_array($value) || is_object($value)) {
            $settingType = 'json';
            $settingValue = json_encode($value);
        }
        
        // Проверяем, существует ли настройка
        $existing = $db->selectOne('SELECT id FROM settings WHERE setting_key = ?', [$key]);
        
        if ($existing) {
            // Обновляем существующую настройку
            $db->update('settings', [
                'setting_value' => $settingValue,
                'setting_type' => $settingType
            ], 'setting_key = ?', [$key]);
        } else {
            // Создаем новую настройку
            $db->insert('settings', [
                'setting_key' => $key,
                'setting_value' => $settingValue,
                'setting_type' => $settingType
            ]);
        }
    }
}
?>