<?php
/**
 * @package     UpTaxi Portal
 * @subpackage  com_uptaxi
 * @copyright   Copyright (C) 2025 UpTaxi. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

class UptaxiViewPortal extends JViewLegacy
{
    protected $user;
    protected $isAdmin;

    public function display($tpl = null)
    {
        $this->user = JFactory::getUser();
        $this->isAdmin = $this->user->authorise('core.admin');

        // Подключаем CSS и JS
        $document = JFactory::getDocument();
        $document->addStyleSheet(JUri::root() . 'components/com_uptaxi/assets/css/portal.css');
        $document->addScript(JUri::root() . 'components/com_uptaxi/assets/js/portal.js');

        // Передаем данные в JavaScript
        $document->addScriptDeclaration('
            var UptaxiConfig = {
                baseUrl: "' . JUri::root() . '",
                ajaxUrl: "' . JRoute::_('index.php?option=com_uptaxi&task=ajax', false) . '",
                user: {
                    id: ' . $this->user->id . ',
                    name: "' . $this->user->name . '",
                    username: "' . $this->user->username . '",
                    isAdmin: ' . ($this->isAdmin ? 'true' : 'false') . '
                }
            };
        ');

        parent::display($tpl);
    }
}