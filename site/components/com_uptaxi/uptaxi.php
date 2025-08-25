<?php
/**
 * @package     UpTaxi Portal
 * @subpackage  com_uptaxi
 * @copyright   Copyright (C) 2025 UpTaxi. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

// Подключаем контроллер
$controller = JControllerLegacy::getInstance('Uptaxi');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();