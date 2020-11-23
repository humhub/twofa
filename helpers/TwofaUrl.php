<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\helpers;

use yii\helpers\Url;

class TwofaUrl extends Url
{
    /**
     * @var string Route to display a form to check user by two-factor authentication
     */
    const ROUTE_CHECK = '/twofa/check';

    /**
     * @var string Route to configuration by admin
     */
    const ROUTE_CONFIG = '/twofa/config';

    public static function toConfig()
    {
        return static::toRoute(static::ROUTE_CONFIG);
    }
}