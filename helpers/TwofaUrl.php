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
    public const ROUTE_CHECK = '/twofa/check';

    /**
     * @var string Route to configure general module settings by admin
     */
    public const ROUTE_CONFIG = '/twofa/admin/index';

    /**
     * @var string Route to configure user settings by current User
     */
    public const ROUTE_USER_SETTINGS = '/twofa/user-settings';

    public static function toCheck()
    {
        return static::toRoute(static::ROUTE_CHECK);
    }

    public static function toConfig()
    {
        return static::toRoute(static::ROUTE_CONFIG);
    }

    /**
     * Checks if the given URL points to the 2FA check page itself.
     *
     * Used to avoid redirecting to the check page after the verification
     * (e.g. when it was stored as a return URL of a guest).
     *
     * @param string|null $url
     * @return bool
     */
    public static function isCheckUrl($url): bool
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $target = parse_url($url);
        $check = parse_url(static::toCheck());
        if ($target === false || $check === false) {
            return false;
        }

        // Without pretty URLs the route is passed in the `r` parameter
        if (isset($check['query'])) {
            parse_str($check['query'], $checkQuery);
            parse_str($target['query'] ?? '', $targetQuery);

            return isset($checkQuery['r'], $targetQuery['r'])
                && trim((string) $targetQuery['r'], '/') === trim((string) $checkQuery['r'], '/');
        }

        return rtrim($target['path'] ?? '', '/') === rtrim($check['path'] ?? '', '/');
    }
}
