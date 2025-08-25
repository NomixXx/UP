/**
 * UpTaxi Portal JavaScript for Joomla
 */

class UptaxiPortal {
    constructor() {
        this.sections = [];
        this.currentSection = null;
        this.currentSubsection = null;
        this.currentGoogleDocUrl = '';
        
        this.init();
    }

    init() {
        this.loadSections();
        this.loadActivities();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Обработчики форм
        const addContentForm = document.getElementById('addContentForm');
        if (addContentForm) {
            addContentForm.addEventListener('submit', (e) => this.handleAddContent(e));
        }

        const addDocForm = document.getElementById('addDocForm');
        if (addDocForm) {
            addDocForm.addEventListener('submit', (e) => this.handleAddDoc(e));
        }

        // Обработчик загрузки файлов
        const fileUpload = document.getElementById('fileUpload');
        if (fileUpload) {
            fileUpload.addEventListener('change', () => this.handleFileSelect());
        }
    }

    async loadSections() {
        try {
            const response = await fetch(UptaxiConfig.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=getSections'
            });

            const data = await response.json();
            if (data.success) {
                this.sections = data.data;
                this.renderSections();
                this.renderQuickLinks();
            }
        } catch (error) {
            console.error('Error loading sections:', error);
        }
    }

    renderSections() {
        const container = document.getElementById('dynamic-sections');
        if (!container) return;

        let html = '';
        this.sections.forEach(section => {
            if (section.subsections && section.subsections.length > 0) {
                html += `
                    <li class="nav-item">
                        <a href="#" class="section-toggle" onclick="uptaxiPortal.toggleSubmenu(this); return false;">
                            <span class="icon">${section.icon}</span>
                            <span>${section.name}</span>
                            <span class="arrow">▼</span>
                        </a>
                        <ul class="subsection-menu">
                `;
                
                section.subsections.forEach(subsection => {
                    html += `
                        <li><a href="#" onclick="uptaxiPortal.showContent(${section.id}, ${subsection.id}); return false;">${subsection.name}</a></li>
                    `;
                });
                
                html += `
                        </ul>
                    </li>
                `;
            }
        });

        container.innerHTML = html;
    }

    renderQuickLinks() {
        const container = document.getElementById('quick-links');
        if (!container) return;

        let html = '';
        this.sections.forEach(section => {
            if (section.subsections && section.subsections.length > 0) {
                const firstSubsection = section.subsections[0];
                html += `
                    <a href="#" onclick="uptaxiPortal.showContent(${section.id}, ${firstSubsection.id}); return false;" class="quick-link">
                        <span class="icon">${section.icon}</span>
                        <span>${section.name} - ${firstSubsection.name}</span>
                    </a>
                `;
            }
        });

        container.innerHTML = html;
    }

    async loadActivities() {
        try {
            const response = await fetch(UptaxiConfig.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=getActivities'
            });

            const data = await response.json();
            if (data.success) {
                this.renderActivities(data.data);
            }
        } catch (error) {
            console.error('Error loading activities:', error);
        }
    }

    renderActivities(activities) {
        const container = document.getElementById('recent-activity');
        if (!container) return;

        let html = '';
        activities.forEach(activity => {
            const date = new Date(activity.created).toLocaleDateString('ru-RU');
            html += `
                <div class="activity-item">
                    <div class="activity-icon">${activity.icon}</div>
                    <div class="activity-info">
                        <p>${activity.title}</p>
                        <small>${date}</small>
                    </div>
                </div>
            `;
        });

        if (activities.length === 0) {
            html = '<p>Нет последних обновлений</p>';
        }

        container.innerHTML = html;
    }

    toggleSubmenu(element) {
        const menuItem = element.parentElement;
        const isActive = menuItem.classList.contains('active');
        
        // Закрыть все подменю
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Открыть текущее, если оно не было активным
        if (!isActive) {
            menuItem.classList.add('active');
        }
    }

    showDashboard() {
        // Убрать активный класс со всех пунктов меню
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Активировать дашборд
        document.querySelector('.nav-item').classList.add('active');
        
        // Показать дашборд
        document.getElementById('dashboard').style.display = 'block';
        
        // Скрыть контейнер для контента
        const dynamicContent = document.getElementById('dynamic-content');
        if (dynamicContent) {
            dynamicContent.style.display = 'none';
        }
        
        // Обновить хлебные крошки
        const breadcrumb = document.getElementById('breadcrumb');
        if (breadcrumb) {
            breadcrumb.innerHTML = '<span>Главная</span>';
        }
    }

    async showContent(sectionId, subsectionId) {
        this.currentSection = sectionId;
        this.currentSubsection = subsectionId;

        const section = this.sections.find(s => s.id == sectionId);
        const subsection = section?.subsections.find(sub => sub.id == subsectionId);

        if (!section || !subsection) {
            alert('Раздел не найден');
            return;
        }

        // Переключиться на контент
        this.switchToContent();

        // Убрать активный класс со всех пунктов меню
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        // Обновить хлебные крошки
        const breadcrumb = document.getElementById('breadcrumb');
        if (breadcrumb) {
            breadcrumb.innerHTML = `<span>Главная</span><span class="separator">></span><span>${section.name} - ${subsection.name}</span>`;
        }

        try {
            const response = await fetch(UptaxiConfig.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=getContent&section_id=${sectionId}&subsection_id=${subsectionId}`
            });

            const data = await response.json();
            if (data.success) {
                this.renderContent(section, subsection, data.data);
            }
        } catch (error) {
            console.error('Error loading content:', error);
        }
    }

    switchToContent() {
        // Скрыть дашборд
        document.getElementById('dashboard').style.display = 'none';
        
        // Показать контейнер для контента
        const dynamicContent = document.getElementById('dynamic-content');
        if (dynamicContent) {
            dynamicContent.style.display = 'block';
        }
    }

    renderContent(section, subsection, data) {
        const contentArea = document.getElementById('dynamic-content');
        if (!contentArea) return;

        let html = `
            <div class="section-header">
                <h1>${section.name} - ${subsection.name}</h1>
                <p>Информация и документы раздела</p>
                <div class="section-actions" style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                    <button onclick="uptaxiPortal.showDashboard()" class="btn-primary">
                        <span class="icon">🏠</span>
                        Главная
                    </button>
                    ${UptaxiConfig.user.isAdmin ? `
                        <button onclick="uptaxiPortal.openAddContentModal(${section.id}, ${subsection.id})" class="btn-primary">
                            <span class="icon">📝</span>
                            Добавить информацию
                        </button>
                        <button onclick="uptaxiPortal.openAddDocModal(${section.id}, ${subsection.id})" class="btn-primary">
                            <span class="icon">📄</span>
                            Добавить документ
                        </button>
                        <button onclick="uptaxiPortal.openUploadFileModal(${section.id}, ${subsection.id})" class="btn-primary">
                            <span class="icon">📁</span>
                            Загрузить файлы
                        </button>
                    ` : ''}
                </div>
            </div>
        `;

        const content = data.content || [];
        const googleDocs = data.googleDocs || [];
        const totalItems = content.length + googleDocs.length;

        if (totalItems === 0) {
            html += `
                <div class="empty-state">
                    <div class="empty-icon">📁</div>
                    <h3>Раздел пуст</h3>
                    <p>В этом разделе пока нет информации. Обратитесь к администратору для добавления материалов.</p>
                </div>
            `;
        } else {
            html += '<div class="content-grid">';
            
            // Отобразить текстовый контент
            content.forEach(item => {
                const isLongContent = item.description.length > 200;
                const contentId = `content-${item.id}`;
                const date = new Date(item.created).toLocaleDateString('ru-RU');
                
                html += `
                    <div class="content-card">
                        <div class="card-header">
                            <h3>${item.title}</h3>
                            <span class="date">${date}</span>
                        </div>
                        <div class="card-content">
                            <div class="content-text ${isLongContent ? 'collapsed' : ''}" id="${contentId}">
                                ${item.description.replace(/\n/g, '<br>')}
                            </div>
                            ${isLongContent ? `
                                <button class="expand-btn" onclick="uptaxiPortal.toggleContent('${contentId}', this)">
                                    Читать далее
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            // Отобразить Google документы
            googleDocs.forEach(doc => {
                const date = new Date(doc.created).toLocaleDateString('ru-RU');
                html += `
                    <div class="content-card">
                        <div class="card-header">
                            <h3>📄 ${doc.title}</h3>
                            <span class="date">${date}</span>
                        </div>
                        <div class="card-content">
                            <p>Google документ</p>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button onclick="uptaxiPortal.openGoogleDocEmbed('${doc.url}', '${doc.title}')" class="doc-link">
                                    <span class="icon">👁️</span>
                                    Просмотр
                                </button>
                                <a href="${doc.url}" target="_blank" class="doc-link">
                                    <span class="icon">🔗</span>
                                    Открыть в Google
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
        }

        contentArea.innerHTML = html;
    }

    toggleContent(contentId, button) {
        const contentElement = document.getElementById(contentId);
        const isCollapsed = contentElement.classList.contains('collapsed');
        
        if (isCollapsed) {
            contentElement.classList.remove('collapsed');
            button.textContent = 'Свернуть';
        } else {
            contentElement.classList.add('collapsed');
            button.textContent = 'Читать далее';
        }
    }

    openGoogleDocEmbed(url, title) {
        // Преобразовать URL для встраивания
        let embedUrl = url;
        
        if (url.includes('docs.google.com/document/d/')) {
            const docId = url.match(/\/document\/d\/([a-zA-Z0-9-_]+)/);
            if (docId) {
                embedUrl = `https://docs.google.com/document/d/${docId[1]}/preview`;
            }
        } else if (url.includes('docs.google.com/spreadsheets/d/')) {
            const docId = url.match(/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/);
            if (docId) {
                embedUrl = `https://docs.google.com/spreadsheets/d/${docId[1]}/preview`;
            }
        } else if (url.includes('docs.google.com/presentation/d/')) {
            const docId = url.match(/\/presentation\/d\/([a-zA-Z0-9-_]+)/);
            if (docId) {
                embedUrl = `https://docs.google.com/presentation/d/${docId[1]}/preview`;
            }
        }
        
        this.currentGoogleDocUrl = url;
        
        document.getElementById('googleDocTitle').textContent = title;
        document.getElementById('googleDocFrame').src = embedUrl;
        
        this.openModal('googleDocModal');
    }

    openGoogleDocInNewTab() {
        if (this.currentGoogleDocUrl) {
            window.open(this.currentGoogleDocUrl, '_blank');
        }
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    openAddContentModal(sectionId, subsectionId) {
        document.getElementById('contentSectionId').value = sectionId;
        document.getElementById('contentSubsectionId').value = subsectionId;
        this.openModal('addContentModal');
    }

    openAddDocModal(sectionId, subsectionId) {
        document.getElementById('docSectionId').value = sectionId;
        document.getElementById('docSubsectionId').value = subsectionId;
        this.openModal('addDocModal');
    }

    openUploadFileModal(sectionId, subsectionId) {
        document.getElementById('fileSectionId').value = sectionId;
        document.getElementById('fileSubsectionId').value = subsectionId;
        this.openModal('uploadFileModal');
    }

    async handleAddContent(e) {
        e.preventDefault();
        
        const sectionId = document.getElementById('contentSectionId').value;
        const subsectionId = document.getElementById('contentSubsectionId').value;
        const title = document.getElementById('contentTitle').value;
        const description = document.getElementById('contentDescription').value;

        try {
            const response = await fetch(UptaxiConfig.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=saveContent&data=${encodeURIComponent(JSON.stringify({
                    type: 'content',
                    section_id: sectionId,
                    subsection_id: subsectionId,
                    title: title,
                    description: description
                }))}`
            });

            const data = await response.json();
            if (data.success) {
                this.closeModal('addContentModal');
                document.getElementById('addContentForm').reset();
                this.showContent(sectionId, subsectionId);
                this.loadActivities();
                alert('Информация успешно добавлена!');
            }
        } catch (error) {
            console.error('Error adding content:', error);
            alert('Ошибка при добавлении информации');
        }
    }

    async handleAddDoc(e) {
        e.preventDefault();
        
        const sectionId = document.getElementById('docSectionId').value;
        const subsectionId = document.getElementById('docSubsectionId').value;
        const title = document.getElementById('docTitle').value;
        const url = document.getElementById('docUrl').value;

        try {
            const response = await fetch(UptaxiConfig.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=saveContent&data=${encodeURIComponent(JSON.stringify({
                    type: 'google_doc',
                    section_id: sectionId,
                    subsection_id: subsectionId,
                    title: title,
                    url: url
                }))}`
            });

            const data = await response.json();
            if (data.success) {
                this.closeModal('addDocModal');
                document.getElementById('addDocForm').reset();
                this.showContent(sectionId, subsectionId);
                this.loadActivities();
                alert('Документ успешно добавлен!');
            }
        } catch (error) {
            console.error('Error adding document:', error);
            alert('Ошибка при добавлении документа');
        }
    }

    handleFileSelect() {
        const fileInput = document.getElementById('fileUpload');
        const files = fileInput.files;
        
        if (files.length > 0) {
            let fileList = '';
            for (let i = 0; i < files.length; i++) {
                fileList += files[i].name + '\n';
            }
            console.log('Selected files:', fileList);
        }
    }

    async uploadFiles() {
        const fileInput = document.getElementById('fileUpload');
        const files = fileInput.files;
        const sectionId = document.getElementById('fileSectionId').value;
        const subsectionId = document.getElementById('fileSubsectionId').value;
        
        if (files.length === 0) {
            alert('Выберите файлы для загрузки');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'uploadFile');
        formData.append('section_id', sectionId);
        formData.append('subsection_id', subsectionId);
        
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        try {
            const response = await fetch(UptaxiConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                this.closeModal('uploadFileModal');
                fileInput.value = '';
                this.showContent(sectionId, subsectionId);
                this.loadActivities();
                alert('Файлы успешно загружены!');
            }
        } catch (error) {
            console.error('Error uploading files:', error);
            alert('Ошибка при загрузке файлов');
        }
    }
}

// Глобальные функции для совместимости
function showDashboard() {
    uptaxiPortal.showDashboard();
}

function openModal(modalId) {
    uptaxiPortal.openModal(modalId);
}

function closeModal(modalId) {
    uptaxiPortal.closeModal(modalId);
}

function openGoogleDocInNewTab() {
    uptaxiPortal.openGoogleDocInNewTab();
}

function uploadFiles() {
    uptaxiPortal.uploadFiles();
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    window.uptaxiPortal = new UptaxiPortal();
});