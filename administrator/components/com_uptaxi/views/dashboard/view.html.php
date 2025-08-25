<?php
/**
 * @package     UpTaxi Portal
 * @subpackage  com_uptaxi
 * @copyright   Copyright (C) 2025 UpTaxi. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

class UptaxiViewDashboard extends JViewLegacy
{
    protected $stats;

    public function display($tpl = null)
    {
        $this->stats = $this->getStats();

        // Подключаем CSS
        $document = JFactory::getDocument();
        $document->addStyleSheet(JUri::root() . 'administrator/components/com_uptaxi/assets/css/admin.css');

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar()
    {
        JToolbarHelper::title('UpTaxi Portal - Панель управления', 'generic.png');
        JToolbarHelper::preferences('com_uptaxi');
    }

    protected function getStats()
    {
        $db = JFactory::getDbo();
        
        // Получаем статистику
        $stats = new stdClass();
        
        // Количество разделов
        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
              ->from('#__uptaxi_sections')
              ->where('published = 1');
        $db->setQuery($query);
        $stats->sections = $db->loadResult();
        
        // Количество контента
        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
              ->from('#__uptaxi_content')
              ->where('published = 1');
        $db->setQuery($query);
        $stats->content = $db->loadResult();
        
        // Количество документов
        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
              ->from('#__uptaxi_google_docs')
              ->where('published = 1');
        $db->setQuery($query);
        $stats->documents = $db->loadResult();
        
        // Количество активностей
        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
              ->from('#__uptaxi_activities');
        $db->setQuery($query);
        $stats->activities = $db->loadResult();
        
        return $stats;
    }
}