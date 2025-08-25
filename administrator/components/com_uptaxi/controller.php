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
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = array())
    {
        $view = $this->input->get('view', 'dashboard');
        $layout = $this->input->get('layout', 'default');
        $id = $this->input->getInt('id');

        // Проверяем права администратора
        $user = JFactory::getUser();
        if (!$user->authorise('core.admin')) {
            $app = JFactory::getApplication();
            $app->enqueueMessage('У вас нет прав для доступа к этой области', 'error');
            $app->redirect('index.php');
            return;
        }

        parent::display($cachable, $urlparams);
    }
}