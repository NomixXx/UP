// Конфигурация для работы на сервере
const CONFIG = {
    // Настройки для локальной разработки
    development: {
        apiUrl: 'http://localhost:8080/server-api.php',
        uploadUrl: 'http://localhost:8080/uploads',
        maxFileSize: 10 * 1024 * 1024, // 10MB
        allowedFileTypes: ['image/*', 'application/pdf', '.doc', '.docx', '.xls', '.xlsx', '.txt']
    },
    
    // Настройки для продакшена
    production: {
        apiUrl: 'https://portal-uptaxi.duckdns.org/server-api.php',
        uploadUrl: 'https://portal-uptaxi.duckdns.org/uploads',
        maxFileSize: 50 * 1024 * 1024, // 50MB
        allowedFileTypes: ['image/*', 'application/pdf', '.doc', '.docx', '.xls', '.xlsx', '.txt', '.zip', '.rar']
    }
};

// Определение текущей среды
const ENVIRONMENT = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
    ? 'development' 
    : 'production';

// Настройки для удаленного сервера
const REMOTE_SERVER = {
    baseUrl: 'https://portal-uptaxi.duckdns.org',
    apiUrl: 'https://portal-uptaxi.duckdns.org/server-api.php',
    uploadUrl: 'https://portal-uptaxi.duckdns.org/uploads'
};

// Экспорт текущей конфигурации
const CURRENT_CONFIG = CONFIG[ENVIRONMENT];

// Утилиты для работы с сервером
const ServerUtils = {
    // Проверка доступности сервера
    async checkServerConnection() {
        try {
            const response = await fetch(CURRENT_CONFIG.apiUrl + '?path=/health');
            return response.ok;
        } catch (error) {
            console.warn('Сервер недоступен, работаем в автономном режиме');
            return false;
        }
    },
    
    // Синхронизация данных между страницами
    async syncData() {
        // Загрузка всех данных с сервера
        try {
            const keys = ['uptaxi_sections', 'uptaxi_users', 'uptaxi_news', 'uptaxi_menu', 'uptaxi_settings'];
            for (const key of keys) {
                const data = await this.loadData(key);
                if (data) {
                    try {
                        localStorage.setItem(key, JSON.stringify(data));
                        console.log(`Синхронизированы данные: ${key}`);
                    } catch (storageError) {
                        console.warn(`Ошибка записи в localStorage для ${key}:`, storageError);
                    }
                }
            }
            return true;
        } catch (error) {
            console.error('Ошибка синхронизации:', error);
            return false;
        }
    },
    
    // Загрузка файла на сервер
    async uploadFile(file, sectionId, subsectionId) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('sectionId', sectionId);
        formData.append('subsectionId', subsectionId);
        
        try {
            const response = await fetch(CURRENT_CONFIG.apiUrl + '?path=/upload', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                return await response.json();
            } else {
                throw new Error('Ошибка загрузки файла');
            }
        } catch (error) {
            console.error('Ошибка загрузки:', error);
            // Fallback к локальному хранению
            return {
                url: URL.createObjectURL(file),
                filename: file.name,
                size: file.size,
                type: file.type
            };
        }
    },
    
    // Сохранение данных на сервер
    async saveData(key, data) {
        try {
            const response = await fetch(CURRENT_CONFIG.apiUrl + '?path=/data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ key, data })
            });
            
            if (!response.ok) {
                throw new Error('Ошибка сохранения данных');
            }
        } catch (error) {
            console.warn('Не удалось сохранить на сервер, используем localStorage');
            try {
                localStorage.setItem(key, JSON.stringify(data));
            } catch (storageError) {
                console.error('Ошибка записи в localStorage:', storageError);
            }
        }
    },
    
    // Загрузка данных с сервера
    async loadData(key) {
        try {
            const response = await fetch(CURRENT_CONFIG.apiUrl + '?path=/data/' + key);
            if (response.ok) {
                const result = await response.json();
                return result.data;
            }
        } catch (error) {
            console.warn('Не удалось загрузить с сервера, используем localStorage');
        }
        
        // Fallback к localStorage
        try {
            const localData = localStorage.getItem(key);
            return localData ? JSON.parse(localData) : null;
        } catch (error) {
            console.warn('Ошибка чтения localStorage:', error);
            return null;
        }
    }
};