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
     * @var string Route to configure general module settings by admin
     */
    const ROUTE_CONFIG = '/twofa/config';

    /**
     * @var string Route to configure user settings by current User
     */
    const ROUTE_USER_SETTINGS = '/twofa/user-settings';

    public static function toCheck()
    {
        return static::toRoute(static::ROUTE_CHECK);
    }

    public static function toConfig()
    {
        return static::toRoute(static::ROUTE_CONFIG);
    }
}