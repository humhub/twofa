<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\Application;
use humhub\components\gates\GateManager;
use humhub\modules\admin\grid\UserActionColumn;
use humhub\modules\twofa\Events;
use humhub\modules\user\controllers\AuthController;
use humhub\modules\user\widgets\SecuritySettingsMenu;
use yii\web\Controller;

return [
    'id' => 'twofa',
    'class' => 'humhub\modules\twofa\Module',
    'namespace' => 'humhub\modules\twofa',
    'events' => [
        [Application::class, Application::EVENT_BEFORE_REQUEST, [Events::class, 'onBeforeRequest']],
        [GateManager::class, GateManager::EVENT_INIT_GATES, [Events::class, 'onGateInit']],
        [AuthController::class, AuthController::EVENT_AFTER_LOGIN, [Events::class, 'onAfterLogin']],
        [Controller::class, Controller::EVENT_BEFORE_ACTION, [Events::class, 'onBeforeAction']],
        [Controller::class, Controller::EVENT_AFTER_ACTION, [Events::class, 'onAfterAction']],
        [SecuritySettingsMenu::class, SecuritySettingsMenu::EVENT_INIT, [Events::class, 'onSecuritySettingsMenuInit']],
        [UserActionColumn::class, UserActionColumn::EVENT_AFTER_INIT_ACTIONS, [Events::class, 'onUserActionColumnAfterInitActions']],
    ],
];
