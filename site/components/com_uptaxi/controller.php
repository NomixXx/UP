<?php
/**
 * @package     UpTaxi Portal
 * @subpackage  com_uptaxi
 * @copyright   Copyright (C) 2025 UpTaxi. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

class UptaxiController extends JControllerLegacy
{
    protected $default_view = 'portal';

    public function display($cachable = false, $urlparams = array())
    {
        $view = $this->input->get('view', 'portal');
        $layout = $this->input->get('layout', 'default');
        $id = $this->input->getInt('id');

        // Проверяем авторизацию
        $user = JFactory::getUser();
        if ($user->guest) {
            $app = JFactory::getApplication();
            $app->redirect(JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode(JUri::getInstance()->toString())));
            return;
        }

        parent::display($cachable, $urlparams);
    }

    public function ajax()
    {
        $app = JFactory::getApplication();
        $input = $app->input;
        $action = $input->get('action', '', 'string');

        header('Content-Type: application/json');

        try {
            switch ($action) {
                case 'getSections':
                    $this->getSections();
                    break;
                case 'getContent':
                    $this->getContent();
                    break;
                case 'saveContent':
                    $this->saveContent();
                    break;
                case 'uploadFile':
                    $this->uploadFile();
                    break;
                case 'getActivities':
                    $this->getActivities();
                    break;
                default:
                    throw new Exception('Unknown action');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }

        $app->close();
    }

    private function getSections()
    {
        $user = JFactory::getUser();
        $userAccessLevel = $this->getUserAccessLevel($user->id);

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        if ($user->authorise('core.admin')) {
            // Админы видят все разделы
            $query->select('s.*, COUNT(sub.id) as subsection_count')
                  ->from('#__uptaxi_sections s')
                  ->leftJoin('#__uptaxi_subsections sub ON s.id = sub.section_id AND sub.published = 1')
                  ->where('s.published = 1')
                  ->group('s.id')
                  ->order('s.ordering ASC');
        } else {
            // Обычные пользователи видят только свой уровень
            $query->select('s.*, COUNT(sub.id) as subsection_count')
                  ->from('#__uptaxi_sections s')
                  ->leftJoin('#__uptaxi_subsections sub ON s.id = sub.section_id AND sub.published = 1 AND sub.access_level = ' . (int)$userAccessLevel)
                  ->where('s.published = 1')
                  ->where('s.access_level = ' . (int)$userAccessLevel)
                  ->group('s.id')
                  ->order('s.ordering ASC');
        }

        $db->setQuery($query);
        $sections = $db->loadObjectList();

        // Получаем подразделы для каждого раздела
        foreach ($sections as &$section) {
            $query = $db->getQuery(true);
            
            if ($user->authorise('core.admin')) {
                $query->select('*')
                      ->from('#__uptaxi_subsections')
                      ->where('section_id = ' . (int)$section->id)
                      ->where('published = 1')
                      ->order('ordering ASC');
            } else {
                $query->select('*')
                      ->from('#__uptaxi_subsections')
                      ->where('section_id = ' . (int)$section->id)
                      ->where('published = 1')
                      ->where('access_level = ' . (int)$userAccessLevel)
                      ->order('ordering ASC');
            }

            $db->setQuery($query);
            $section->subsections = $db->loadObjectList();
        }

        echo json_encode(['success' => true, 'data' => $sections]);
    }

    private function getContent()
    {
        $input = JFactory::getApplication()->input;
        $sectionId = $input->getInt('section_id');
        $subsectionId = $input->getInt('subsection_id');

        $db = JFactory::getDbo();
        
        // Получаем текстовый контент
        $query = $db->getQuery(true);
        $query->select('*')
              ->from('#__uptaxi_content')
              ->where('section_id = ' . (int)$sectionId)
              ->where('subsection_id = ' . (int)$subsectionId)
              ->where('published = 1')
              ->order('created DESC');

        $db->setQuery($query);
        $content = $db->loadObjectList();

        // Получаем Google документы
        $query = $db->getQuery(true);
        $query->select('*')
              ->from('#__uptaxi_google_docs')
              ->where('section_id = ' . (int)$sectionId)
              ->where('subsection_id = ' . (int)$subsectionId)
              ->where('published = 1')
              ->order('created DESC');

        $db->setQuery($query);
        $googleDocs = $db->loadObjectList();

        echo json_encode([
            'success' => true,
            'data' => [
                'content' => $content,
                'googleDocs' => $googleDocs
            ]
        ]);
    }

    private function saveContent()
    {
        $user = JFactory::getUser();
        if (!$user->authorise('core.create', 'com_uptaxi')) {
            throw new Exception('Access denied');
        }

        $input = JFactory::getApplication()->input;
        $data = json_decode($input->getRaw('data'), true);

        $db = JFactory::getDbo();
        
        if ($data['type'] === 'content') {
            $query = $db->getQuery(true);
            $query->insert('#__uptaxi_content')
                  ->columns(['section_id', 'subsection_id', 'title', 'description', 'created_by'])
                  ->values(implode(',', [
                      (int)$data['section_id'],
                      (int)$data['subsection_id'],
                      $db->quote($data['title']),
                      $db->quote($data['description']),
                      (int)$user->id
                  ]));

            $db->setQuery($query);
            $db->execute();

            // Добавляем активность
            $this->addActivity('Добавлен контент: ' . $data['title'], '📝', $user->id);
        } elseif ($data['type'] === 'google_doc') {
            $query = $db->getQuery(true);
            $query->insert('#__uptaxi_google_docs')
                  ->columns(['section_id', 'subsection_id', 'title', 'url', 'created_by'])
                  ->values(implode(',', [
                      (int)$data['section_id'],
                      (int)$data['subsection_id'],
                      $db->quote($data['title']),
                      $db->quote($data['url']),
                      (int)$user->id
                  ]));

            $db->setQuery($query);
            $db->execute();

            // Добавляем активность
            $this->addActivity('Добавлен документ: ' . $data['title'], '📄', $user->id);
        }

        echo json_encode(['success' => true]);
    }

    private function uploadFile()
    {
        $user = JFactory::getUser();
        if (!$user->authorise('core.create', 'com_uptaxi')) {
            throw new Exception('Access denied');
        }

        $input = JFactory::getApplication()->input;
        $files = $input->files->get('files', array(), 'array');
        $sectionId = $input->getInt('section_id');
        $subsectionId = $input->getInt('subsection_id');

        if (empty($files)) {
            throw new Exception('No files uploaded');
        }

        $uploadDir = JPATH_ROOT . '/images/uptaxi/';
        if (!JFolder::exists($uploadDir)) {
            JFolder::create($uploadDir);
        }

        $db = JFactory::getDbo();
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            $filename = JFile::makeSafe($file['name']);
            $filename = uniqid() . '_' . $filename;
            $filepath = $uploadDir . $filename;

            if (JFile::upload($file['tmp_name'], $filepath)) {
                $contentType = strpos($file['type'], 'image/') === 0 ? 'photo' : 'file';

                $query = $db->getQuery(true);
                $query->insert('#__uptaxi_content')
                      ->columns(['section_id', 'subsection_id', 'title', 'description', 'content_type', 'file_path', 'file_size', 'mime_type', 'created_by'])
                      ->values(implode(',', [
                          (int)$sectionId,
                          (int)$subsectionId,
                          $db->quote($file['name']),
                          $db->quote('Загруженный файл'),
                          $db->quote($contentType),
                          $db->quote('images/uptaxi/' . $filename),
                          (int)$file['size'],
                          $db->quote($file['type']),
                          (int)$user->id
                      ]));

                $db->setQuery($query);
                $db->execute();

                $uploadedFiles[] = [
                    'name' => $file['name'],
                    'url' => JUri::root() . 'images/uptaxi/' . $filename,
                    'type' => $file['type']
                ];
            }
        }

        // Добавляем активность
        $this->addActivity('Загружено файлов: ' . count($uploadedFiles), '📁', $user->id);

        echo json_encode(['success' => true, 'files' => $uploadedFiles]);
    }

    private function getActivities()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('a.*, u.name as created_by_name')
              ->from('#__uptaxi_activities a')
              ->leftJoin('#__users u ON a.created_by = u.id')
              ->order('a.created DESC')
              ->setLimit(10);

        $db->setQuery($query);
        $activities = $db->loadObjectList();

        echo json_encode(['success' => true, 'data' => $activities]);
    }

    private function addActivity($title, $icon, $userId)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->insert('#__uptaxi_activities')
              ->columns(['title', 'icon', 'created_by'])
              ->values(implode(',', [
                  $db->quote($title),
                  $db->quote($icon),
                  (int)$userId
              ]));

        $db->setQuery($query);
        $db->execute();
    }

    private function getUserAccessLevel($userId)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('uptaxi_access_level')
              ->from('#__users')
              ->where('id = ' . (int)$userId);

        $db->setQuery($query);
        $accessLevel = $db->loadResult();

        return $accessLevel ?: 1;
    }
}