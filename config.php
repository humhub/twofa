<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\Application;
use humhub\modules\twofa\Events;
use humhub\modules\user\controllers\AuthController;
use humhub\modules\user\widgets\AccountSettingsMenu;
use yii\web\Controller;

return [
    'id' => 'twofa',
    'class' => 'humhub\modules\twofa\Module',
    'namespace' => 'humhub\modules\twofa',
    'events' => [
        [Application::class, Application::EVENT_BEFORE_REQUEST, [Events::class, 'onBeforeRequest']],
        [AuthController::class, AuthController::EVENT_AFTER_LOGIN, [Events::class, 'onAfterLogin']],
        [Controller::class, Controller::EVENT_BEFORE_ACTION, [Events::class, 'onBeforeAction']],
        [AccountSettingsMenu::class, AccountSettingsMenu::EVENT_INIT, [Events::class, 'onProfileSettingMenuInit']],
    ],
];
