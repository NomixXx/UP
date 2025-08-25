<?php
/**
 * @package     UpTaxi Portal
 * @subpackage  com_uptaxi
 * @copyright   Copyright (C) 2025 UpTaxi. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;
?>

<div class="uptaxi-admin">
    <div class="row-fluid">
        <div class="span12">
            <div class="well">
                <h2>Добро пожаловать в панель управления UpTaxi Portal</h2>
                <p>Здесь вы можете управлять всеми аспектами портала.</p>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span3">
            <div class="well text-center">
                <h3><?php echo $this->stats->sections; ?></h3>
                <p>Разделов</p>
            </div>
        </div>
        <div class="span3">
            <div class="well text-center">
                <h3><?php echo $this->stats->content; ?></h3>
                <p>Контента</p>
            </div>
        </div>
        <div class="span3">
            <div class="well text-center">
                <h3><?php echo $this->stats->documents; ?></h3>
                <p>Документов</p>
            </div>
        </div>
        <div class="span3">
            <div class="well text-center">
                <h3><?php echo $this->stats->activities; ?></h3>
                <p>Активностей</p>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span6">
            <div class="well">
                <h3>Быстрые действия</h3>
                <p><a href="<?php echo JRoute::_('index.php?option=com_uptaxi&view=sections'); ?>" class="btn btn-primary">Управление разделами</a></p>
                <p><a href="<?php echo JRoute::_('index.php?option=com_uptaxi&view=content'); ?>" class="btn btn-primary">Управление контентом</a></p>
                <p><a href="<?php echo JRoute::_('index.php?option=com_uptaxi&view=activities'); ?>" class="btn btn-primary">Просмотр активности</a></p>
            </div>
        </div>
        <div class="span6">
            <div class="well">
                <h3>Информация о системе</h3>
                <p><strong>Версия компонента:</strong> 1.0.0</p>
                <p><strong>Версия Joomla:</strong> <?php echo JVERSION; ?></p>
                <p><strong>Статус:</strong> Активен</p>
                <p><a href="<?php echo JRoute::_('index.php?option=com_uptaxi', false); ?>" target="_blank" class="btn btn-success">Перейти к порталу</a></p>
            </div>
        </div>
    </div>
</div>