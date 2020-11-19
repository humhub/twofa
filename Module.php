<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa;

use humhub\modules\twofa\drivers\EmailDriver;
use Yii;

class Module extends \humhub\components\Module
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
     * @return bool Check if current page is already URL of 2fa
     */
    public function isTwofaCheckUrl()
    {
        return Yii::$app->requestedRoute === trim($this->checkRoute, '/');
    }
}
