<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\twofa\drivers\EmailDriver;
use humhub\modules\user\models\User;
use Yii;

class Module extends ContentContainerModule
{
    /**
     * @var array Drivers
     */
    public $drivers = [
        EmailDriver::class,
    ];

    /**
     * @var string Route to check user for two-factor authentication
     */
    public $checkRoute = '/twofa/check';

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            User::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerConfigUrl(ContentContainerActiveRecord $container)
    {
        return $container->createUrl('/twofa/container-config');
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        if ($container instanceof User) {
            return Yii::t('TwofaModule.base', 'Two-factor authentication for your account.');
        }
    }

    /**
     * @return bool Check if current page is already URL of 2fa
     */
    public function isTwofaCheckUrl()
    {
        return Yii::$app->requestedRoute === trim($this->checkRoute, '/');
    }
}
