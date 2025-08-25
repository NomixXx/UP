<?php
/**
 * @package     UpTaxi Portal
 * @subpackage  com_uptaxi
 * @copyright   Copyright (C) 2025 UpTaxi. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;
?>

<div class="uptaxi-portal">
    <div class="app-container">
        <!-- Боковое меню -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="<?php echo JUri::root(); ?>components/com_uptaxi/assets/images/logo-uptaxi.svg" alt="UpTaxi Logo">
                    <div class="logo-text">
                        <span>Портал</span>
                        <h2>UPTAXI</h2>
                    </div>
                </div>
                
                <div class="user-profile">
                    <div class="avatar">
                        <span id="userInitials"><?php echo strtoupper(substr($this->user->name, 0, 1)); ?></span>
                    </div>
                    <div class="user-info">
                        <span class="username"><?php echo $this->user->name; ?></span>
                        <span class="user-role"><?php echo $this->isAdmin ? 'Администратор' : 'Пользователь'; ?></span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <ul>
                        <li class="nav-item active">
                            <a href="#" onclick="showDashboard(); return false;">
                                <span class="icon">≡</span>
                                <b class="menu">Меню</b>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <ul id="dynamic-sections">
                        <!-- Динамические разделы будут загружены здесь -->
                    </ul>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <?php if ($this->isAdmin): ?>
                <div class="admin-section">
                    <a href="<?php echo JRoute::_('index.php?option=com_uptaxi&view=admin'); ?>" class="admin-link">
                        <span class="icon">⚙️</span>
                        <b>Админ-панель</b>
                    </a>
                </div>
                <?php endif; ?>
                
                <a href="<?php echo JRoute::_('index.php?option=com_users&task=user.logout&' . JSession::getFormToken() . '=1'); ?>" class="logout-btn">
                    <span class="icon">🚪</span>
                    <b>Выйти</b>
                </a>
            </div>
        </aside>

        <!-- Основной контент -->
        <main class="main-content">
            <div class="content-header">
                <div class="breadcrumb" id="breadcrumb">
                    <span>Главная</span>
                </div>
            </div>

            <div class="content-area">
                <!-- Дашборд -->
                <div id="dashboard" class="content-section active">
                    <div class="welcome-header">
                        <h1>Добро пожаловать в портал UpTaxi</h1>
                        <p>Выберите раздел для работы или воспользуйтесь быстрыми ссылками</p>
                    </div>
                    <div class="dashboard-cards">
                        <div class="dashboard-card">
                            <h3>Быстрые ссылки</h3>
                            <div class="quick-links" id="quick-links">
                                <!-- Быстрые ссылки будут загружены динамически -->
                            </div>
                        </div>
                        
                        <div class="dashboard-card">
                            <h3>Последние обновления</h3>
                            <div class="recent-activity" id="recent-activity">
                                <!-- Последние активности будут загружены динамически -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Динамический контент разделов -->
                <div id="dynamic-content" class="content-section" style="display: none;">
                    <!-- Контент разделов будет загружен динамически -->
                </div>
            </div>
        </main>
    </div>

    <!-- Модальные окна -->
    <?php if ($this->isAdmin): ?>
    <!-- Добавление контента -->
    <div id="addContentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Добавление информации</h3>
                <button onclick="closeModal('addContentModal')" class="close-btn">✕</button>
            </div>
            <div class="modal-body">
                <form id="addContentForm">
                    <input type="hidden" id="contentSectionId">
                    <input type="hidden" id="contentSubsectionId">
                    <div class="form-group">
                        <label>Заголовок</label>
                        <input type="text" id="contentTitle" required>
                    </div>
                    <div class="form-group">
                        <label>Описание</label>
                        <textarea id="contentDescription" rows="4" required></textarea>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" onclick="closeModal('addContentModal')" class="btn-secondary">Отмена</button>
                        <button type="submit" class="btn-primary">Добавить информацию</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Добавление Google документа -->
    <div id="addDocModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Добавление Google документа</h3>
                <button onclick="closeModal('addDocModal')" class="close-btn">✕</button>
            </div>
            <div class="modal-body">
                <form id="addDocForm">
                    <input type="hidden" id="docSectionId">
                    <input type="hidden" id="docSubsectionId">
                    <div class="form-group">
                        <label>Название документа</label>
                        <input type="text" id="docTitle" required>
                    </div>
                    <div class="form-group">
                        <label>Ссылка на документ</label>
                        <input type="url" id="docUrl" required placeholder="https://docs.google.com/document/...">
                    </div>
                    <button type="submit" class="btn-primary">Добавить документ</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Загрузка файлов -->
    <div id="uploadFileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Загрузка файлов</h3>
                <button onclick="closeModal('uploadFileModal')" class="close-btn">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fileSectionId">
                <input type="hidden" id="fileSubsectionId">
                <div class="upload-area">
                    <input type="file" id="fileUpload" multiple>
                    <label for="fileUpload" class="upload-label">
                        <span class="upload-icon">📁</span>
                        <span>Выберите файлы или перетащите сюда</span>
                    </label>
                </div>
                <button onclick="uploadFiles()" class="btn-primary">Загрузить файлы</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Модальное окно для просмотра Google документов -->
    <div id="googleDocModal" class="modal">
        <div class="modal-content" style="max-width: 95vw; max-height: 95vh; width: 95vw; height: 95vh;">
            <div class="modal-header">
                <h3 id="googleDocTitle">Просмотр документа</h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button onclick="openGoogleDocInNewTab()" class="btn-primary" style="padding: 8px 15px; font-size: 14px;">
                        <span class="icon">🔗</span>
                        Открыть в новой вкладке
                    </button>
                    <button onclick="closeModal('googleDocModal')" class="close-btn">✕</button>
                </div>
            </div>
            <div class="modal-body" style="padding: 0; height: calc(100% - 70px);">
                <iframe id="googleDocFrame" 
                        style="width: 100%; height: 100%; border: none;" 
                        src="">
                </iframe>
            </div>
        </div>
    </div>
</div>