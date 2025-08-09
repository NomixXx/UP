// Скрипт для проверки и автоматической синхронизации с сервером
// Подключается на всех страницах для обеспечения актуальности данных

class SyncManager {
    constructor() {
        this.syncInterval = 30000; // 30 секунд
        this.lastSyncTime = 0;
        this.isOnline = navigator.onLine;
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Отслеживание состояния сети
        window.addEventListener('online', () => {
            this.isOnline = true;
            console.log('Соединение восстановлено, запускаем синхронизацию...');
            this.performSync();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            console.log('Соединение потеряно, переходим в автономный режим');
        });

        // Синхронизация при фокусе на окне
        window.addEventListener('focus', () => {
            if (this.shouldSync()) {
                this.performSync();
            }
        });

        // Синхронизация при изменении видимости страницы
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.shouldSync()) {
                this.performSync();
            }
        });
    }

    shouldSync() {
        const now = Date.now();
        return this.isOnline && (now - this.lastSyncTime) > this.syncInterval;
    }

    async performSync() {
        if (!this.isOnline || typeof ServerUtils === 'undefined') {
            return false;
        }

        try {
            console.log('Начинаем синхронизацию данных...');
            
            // Проверяем подключение к серверу
            const serverStatus = await ServerUtils.checkServerConnection();
            if (!serverStatus) {
                console.warn('Сервер недоступен');
                return false;
            }

            // Выполняем синхронизацию
            const syncResult = await ServerUtils.syncData();
            
            if (syncResult) {
                this.lastSyncTime = Date.now();
                console.log('Синхронизация завершена успешно');
                
                // Уведомляем другие компоненты о синхронизации
                window.dispatchEvent(new CustomEvent('dataSync', {
                    detail: { success: true, timestamp: this.lastSyncTime }
                }));
                
                return true;
            } else {
                console.warn('Синхронизация не удалась');
                return false;
            }
        } catch (error) {
            console.error('Ошибка при синхронизации:', error);
            return false;
        }
    }

    // Принудительная синхронизация
    async forceSync() {
        this.lastSyncTime = 0; // Сбрасываем время последней синхронизации
        return await this.performSync();
    }

    // Запуск периодической синхронизации
    startPeriodicSync() {
        setInterval(() => {
            if (this.shouldSync()) {
                this.performSync();
            }
        }, this.syncInterval);
    }

    // Получение статуса синхронизации
    getSyncStatus() {
        return {
            isOnline: this.isOnline,
            lastSyncTime: this.lastSyncTime,
            timeSinceLastSync: Date.now() - this.lastSyncTime
        };
    }
}

// Глобальный экземпляр менеджера синхронизации
const syncManager = new SyncManager();

// Автоматический запуск при загрузке страницы
document.addEventListener('DOMContentLoaded', async function() {
    // Ждем загрузки конфигурации
    if (typeof ServerUtils !== 'undefined') {
        // Выполняем первоначальную синхронизацию
        await syncManager.performSync();
        
        // Запускаем периодическую синхронизацию
        syncManager.startPeriodicSync();
        
        console.log('Менеджер синхронизации инициализирован');
    } else {
        console.warn('ServerUtils не найден, синхронизация отключена');
    }
});

// Экспорт для использования в других скриптах
if (typeof window !== 'undefined') {
    window.syncManager = syncManager;
}