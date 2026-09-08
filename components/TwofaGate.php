<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\components;

use humhub\components\gates\RequestClass;
use humhub\components\gates\UserGate;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\helpers\TwofaUrl;
use Yii;

/**
 * Routes users with pending two-factor verification through the 2FA check page
 * before they can use the platform (see core `docs/develop/user-gates.md`).
 *
 * Replaces the module's former `Controller::EVENT_BEFORE_ACTION` interception.
 *
 * @since 1.4
 */
class TwofaGate extends UserGate
{
    /**
     * @inheritdoc
     */
    public function getId(): string
    {
        return 'twofa';
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return self::SORT_SECOND_FACTOR;
    }

    /**
     * @inheritdoc
     */
    public function isOpen(): bool
    {
        return !Yii::$app->user->isGuest && TwofaHelper::isVerificationPending();
    }

    /**
     * @inheritdoc
     */
    public function getRoute(): array
    {
        return [TwofaUrl::ROUTE_CHECK];
    }

    /**
     * Login/logout must stay reachable while verification is pending; the mobile app
     * updates its push token in the background. Each entry is a deliberate,
     * security-reviewed exemption from the second factor.
     *
     * @inheritdoc
     */
    public function getAllowedRoutes(): array
    {
        return ['user/auth', 'fcm-push/token/update'];
    }

    /**
     * The verification is a session-based, interactive flow, so the gate does not apply
     * to token-authenticated API requests: a REST token is issued through its own flow
     * and stands on its own, and per-request gating a stateless request would only ever
     * report "pending". API authentication is handled by the REST module.
     *
     * @inheritdoc
     */
    public function appliesTo(RequestClass $requestClass): bool
    {
        return $requestClass !== RequestClass::Api;
    }

    /**
     * Whether 2FA is required follows group membership and driver settings, which can
     * change at any time and must take effect instantly — so the gate is evaluated on
     * every request.
     *
     * @inheritdoc
     */
    public function isCacheable(): bool
    {
        return false;
    }

    /**
     * Lazily delivers the verification code (e.g. by mail) when the user is
     * intercepted and no valid code is pending yet.
     *
     * @inheritdoc
     */
    public function onIntercept(): void
    {
        TwofaHelper::sendCodeIfNeeded();
    }
}
