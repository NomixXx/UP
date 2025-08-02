// Система аутентификации
class AuthSystem {
    constructor() {
        this.users = JSON.parse(localStorage.getItem('uptaxi_users')) || [
            { username: 'admin', password: 'admin123', role: 'admin', accessLevel: 10 },
            { username: 'user', password: 'user123', role: 'user', accessLevel: 1 }
        ];
        this.currentUser = JSON.parse(localStorage.getItem('uptaxi_currentUser')) || null;
        
        // Ensure all users have accessLevel set
        let needsSave = false;
        this.users.forEach(user => {
            if (!user.accessLevel) {
                user.accessLevel = user.role === 'admin' ? 10 : 1;
                needsSave = true;
            }
        });
        
        if (needsSave) {
            this.saveUsers();
        }
    }

    saveUsers() {
        localStorage.setItem('uptaxi_users', JSON.stringify(this.users));
        // Сохранение на сервер если доступен
        if (typeof ServerUtils !== 'undefined' && serverAvailable) {
            ServerUtils.saveData('uptaxi_users', this.users);
        }
    }

    async login(username, password) {
        // Синхронизация с сервером перед входом
        if (typeof ServerUtils !== 'undefined') {
            const serverStatus = await ServerUtils.checkServerConnection();
            if (serverStatus) {
                console.log('Синхронизация данных с сервером перед входом...');
                await ServerUtils.syncData();
                // Обновляем список пользователей после синхронизации
                this.users = JSON.parse(localStorage.getItem('uptaxi_users')) || this.users;
            }
        }
        
        const user = this.users.find(u => u.username === username && u.password === password);
        if (user) {
            // Создаем копию пользователя для текущей сессии
            const sessionUser = {...user};
            
            // Убедиться, что у пользователя есть уровень доступа
            if (!sessionUser.accessLevel) {
                sessionUser.accessLevel = sessionUser.role === 'admin' ? 10 : 1;
                // Обновляем также в основном списке пользователей
                user.accessLevel = sessionUser.accessLevel;
                console.log('Установлен уровень доступа по умолчанию:', sessionUser.accessLevel);
                this.saveUsers();
            }
            
            // Автоматически устанавливаем максимальный уровень доступа для админов
            if (sessionUser.role === 'admin' && sessionUser.accessLevel !== 10) {
                sessionUser.accessLevel = 10;
                // Обновляем также в основном списке пользователей
                user.accessLevel = 10;
                console.log('Установлен максимальный уровень для админа:', sessionUser.accessLevel);
                this.saveUsers();
            }
            
            console.log('Пользователь вошел в систему:', sessionUser);
            this.currentUser = sessionUser;
            localStorage.setItem('uptaxi_currentUser', JSON.stringify(sessionUser));
            return true;
        }
        return false;
    }

    logout() {
        this.currentUser = null;
        localStorage.removeItem('uptaxi_currentUser');
        window.location.href = 'index.html';
    }

    isLoggedIn() {
        // Check if currentUser is null and try to load from localStorage
        if (this.currentUser === null) {
            const savedUser = localStorage.getItem('uptaxi_currentUser');
            if (savedUser) {
                this.currentUser = JSON.parse(savedUser);
            }
        }
        return this.currentUser !== null;
    }

    isAdmin() {
        // Make sure currentUser is loaded
        this.isLoggedIn();
        return this.currentUser && this.currentUser.role === 'admin';
    }

    createUser(username, password, role) {
        if (this.users.find(u => u.username === username)) {
            return false;
        }
        // Установить уровень доступа по умолчанию
        const defaultAccessLevel = role === 'admin' ? 10 : 1;
        this.users.push({ username, password, role, accessLevel: defaultAccessLevel });
        this.saveUsers();
        return true;
    }

    updateUser(oldUsername, newUsername, newPassword, newRole, newAccessLevel) {
        const userIndex = this.users.findIndex(u => u.username === oldUsername);
        if (userIndex !== -1) {
            this.users[userIndex] = { 
                username: newUsername, 
                password: newPassword, 
                role: newRole,
                accessLevel: newAccessLevel || (newRole === 'admin' ? 10 : 1)
            };
            this.saveUsers();
            return true;
        }
        return false;
    }

    deleteUser(username) {
        this.users = this.users.filter(u => u.username !== username);
        this.saveUsers();
    }

    getUsers() {
        return this.users;
    }
}

// Глобальный экземпляр системы аутентификации
const auth = new AuthSystem();

// Инициализация серверного подключения
let serverAvailable = false;

// Проверка доступности сервера при загрузке
document.addEventListener('DOMContentLoaded', async function() {
    if (typeof ServerUtils !== 'undefined') {
        serverAvailable = await ServerUtils.checkServerConnection();
        
        // Обработка формы входа
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                const remember = document.getElementById('remember').checked;
                
                // Асинхронный вход с синхронизацией
                const success = await auth.login(username, password);
                
                if (success) {
                    // Перенаправление на страницу меню
                    window.location.href = 'menu.html';
                } else {
                    // Показать сообщение об ошибке
                    const errorMessage = document.getElementById('error-message');
                    if (errorMessage) {
                        errorMessage.textContent = 'Неверный логин или пароль';
                        errorMessage.style.display = 'block';
                    }
                }
            });
        }
        console.log('Сервер доступен:', serverAvailable);
    }
});

// Функция выхода
function logout() {
    auth.logout();
}

// Обработчик формы входа уже добавлен в DOMContentLoaded