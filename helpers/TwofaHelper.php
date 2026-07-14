<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\helpers;

use DateTime;
use humhub\modules\admin\Module as AdminModule;
use humhub\modules\content\components\ContentContainerSettingsManager;
use humhub\modules\twofa\drivers\BaseDriver;
use humhub\modules\twofa\Module as TwofaModule;
use humhub\modules\user\models\User;
use humhub\modules\user\Module as UserModule;
use Yii;
use yii\helpers\BaseIpHelper;
use yii\web\Cookie;

class TwofaHelper
{
    public const USER_SETTING = 'twofaDriver';
    public const CODE_SETTING = 'twofaCode';
    public const CODE_EXPIRATION_SETTING = 'twofaCodeExpiration';

    private const SESSION_STATUS = 'twofa.status';
    private const SESSION_STATUS_PENDING = 'pending';
    private const SESSION_STATUS_VERIFIED = 'verified';

    /**
     * Get settings manager of User
     *
     * @param User|null $user
     * @return ContentContainerSettingsManager|false
     */
    public static function getSettings(?User $user = null)
    {
        $user ??= Yii::$app->user->getIdentity();
        /** @var UserModule $module */
        $module = Yii::$app->getModule('user');

        return $user instanceof User ? $module->settings->contentContainer($user) : false;
    }

    /**
     * Get setting value of current User
     *
     * @param string $name Setting name
     * @return string|null
     */
    public static function getSetting($name)
    {
        return ($settings = self::getSettings()) ? $settings->get($name) : null;
    }

    /**
     * Get setting value of current User
     *
     * @param string $name Setting name
     * @param string|null $value Setting value, null - to delete the setting
     * @return string|null
     */
    public static function setSetting($name, $value = null)
    {
        if (!($settings = TwofaHelper::getSettings())) {
            return false;
        }

        if (empty($value)) {
            // Remove empty setting from DB
            $settings->delete($name);
        } else {
            $settings->set($name, $value);
        }

        return true;
    }

    /**
     * Reset all stored 2FA settings for the given User.
     *
     * @param User $user
     * @return bool
     */
    public static function resetUserSettings(User $user): bool
    {
        if (!($settings = self::getSettings($user))) {
            return false;
        }

        $modelClass = $settings->modelClass;
        $modelClass::deleteAll([
            'module_id' => 'user',
            'contentcontainer_id' => $user->contentcontainer_id,
            'name' => self::getUserSettingNames(),
        ]);

        Yii::$app->getModule('user')->settings->flushContentContainer($user);

        return true;
    }

    public static function getUserSettingNames(): array
    {
        $settingNames = [
            self::USER_SETTING,
            self::CODE_SETTING,
            self::CODE_EXPIRATION_SETTING,
        ];

        /** @var TwofaModule $module */
        $module = Yii::$app->getModule('twofa');

        foreach ($module->drivers as $driverClassName) {
            if (is_subclass_of($driverClassName, BaseDriver::class)) {
                $settingNames = array_merge($settingNames, $driverClassName::getUserSettingNames());
            }
        }

        return array_values(array_unique($settingNames));
    }

    /**
     * Get Driver setting value of current User
     *
     * @return string|null
     */
    public static function getDriverSetting()
    {
        $driverClass = self::getSetting(self::USER_SETTING);

        /** @var TwofaModule $module */
        $module = Yii::$app->getModule('twofa');

        if (!in_array($driverClass, $module->getEnabledDrivers())) {
            $driverClass = null;
        }

        if (empty($driverClass) && self::isEnforcedUser()) {
            return $module->getEnforcedMethod();
        }

        return $driverClass;
    }

    /**
     * Get 2fa Driver by class name
     *
     * @param string Class name
     * @return BaseDriver|false
     */
    public static function getDriverByClassName($driverClassName)
    {
        if (empty($driverClassName)) {
            return false;
        }

        $driverClassName = '\\' . trim((string) $driverClassName, '\\');

        if (class_exists($driverClassName)) {
            return new $driverClassName();
        }

        return false;
    }

    /**
     * Get 2fa Driver for current User
     *
     * @return BaseDriver|false
     */
    public static function getDriver()
    {
        return self::getDriverByClassName(self::getDriverSetting());
    }

    /**
     * Check if at least one Group of the current User is enforced to 2fa
     *
     * @return bool
     */
    public static function isEnforcedUser()
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        /** @var TwofaModule $module */
        $module = Yii::$app->getModule('twofa');

        $enforcedGroups = $module->getEnforcedGroups();
        if (empty($enforcedGroups)) {
            return false;
        }

        /** @var User $user */
        $user = Yii::$app->user->getIdentity();

        return $user->getGroups()->where(['in', 'id', $enforcedGroups])->exists();
    }

    /**
     * Get verifying code of current User
     *
     * @return string|null
     */
    public static function getCode()
    {
        return self::getSetting(self::CODE_SETTING);
    }

    /**
     * Check if verifying code is expired
     *
     * @return bool
     */
    public static function isCodeExpired()
    {
        return self::getSetting(self::CODE_EXPIRATION_SETTING) < time();
    }

    /**
     * Returns a random code
     *
     * @param $len
     * @return string
     */
    public static function generateCode($len)
    {
        return Yii::$app->security->generateRandomString($len);
    }

    public static function resetSessionStatus(): void
    {
        Yii::$app->session->remove(self::SESSION_STATUS);
    }

    private static function setSessionStatus(?string $status): void
    {
        if ($status === null) {
            self::resetSessionStatus();
            return;
        }

        Yii::$app->session->set(self::SESSION_STATUS, $status);
    }

    private static function isPendingVerification(): bool
    {
        return Yii::$app->session->get(self::SESSION_STATUS) === self::SESSION_STATUS_PENDING;
    }

    private static function isSessionVerified(): bool
    {
        return Yii::$app->session->get(self::SESSION_STATUS) === self::SESSION_STATUS_VERIFIED;
    }

    /**
     * Enable verifying by 2fa for current User
     *
     * @return bool true on success enabling
     */
    public static function enableVerifying()
    {
        $driver = self::getDriver();

        // Send a verifying code to use by driver
        if (!$driver || !$driver->send()) {
            // Impossible to send a verifying code by Driver,
            // because wrong driver OR current User has no enabled 2fa
            return false;
        }

        /** @var TwofaModule $module */
        $module = Yii::$app->getModule('twofa');

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!self::setSetting(self::CODE_SETTING, Yii::$app->security->generatePasswordHash($driver->getCode()))) {
                throw new \RuntimeException();
            }
            if (!self::setSetting(self::CODE_EXPIRATION_SETTING, time() + $module->getCodeTtl())) {
                throw new \RuntimeException();
            }

            $transaction->commit();
        } catch (\Exception) {
            $transaction->rollBack();
            return false;
        }

        self::setSessionStatus(self::SESSION_STATUS_PENDING);

        // TODO: Inform user about way of sending the verifying code

        return true;
    }

    /**
     * Disable verifying by 2fa for current User
     *
     * @return bool true on success disabling
     */
    public static function disableVerifying(bool $isVerified = false)
    {
        if (!self::setSetting(self::CODE_SETTING) || !self::setSetting(self::CODE_EXPIRATION_SETTING)) {
            return false;
        }

        self::setSessionStatus($isVerified ? self::SESSION_STATUS_VERIFIED : null);

        return true;
    }

    /**
     * Check if the current User still has to pass the two-factor verification.
     *
     * Free of side effects — delivering the verification code is handled separately
     * by [[sendCodeIfNeeded()]] (invoked when the TwofaGate intercepts a request).
     *
     * @return bool
     * @since 1.4
     */
    public static function isVerificationPending(): bool
    {
        $driver = self::getDriver();

        return $driver && $driver->canSend() && !self::isSessionVerified();
    }

    /**
     * Delivers a verification code unless a valid one is already pending.
     *
     * @return bool whether a valid code is available (already pending or newly sent)
     * @since 1.4
     */
    public static function sendCodeIfNeeded(): bool
    {
        if (self::isPendingVerification() && self::getCode() !== null) {
            return true;
        }

        return self::enableVerifying();
    }

    /**
     * Check if current User was logged in from administration action "Impersonate"
     *
     * @return bool
     */
    public static function isImpersonateMode(): bool
    {
        $switchedUserId = Yii::$app->session->get('twofa.switchedUserId');
        if (empty($switchedUserId)) {
            return false;
        }

        if (Yii::$app->user->isGuest) {
            return false;
        }

        /* @var $adminModule AdminModule */
        $adminModule = Yii::$app->getModule('admin');
        if (!$adminModule->allowUserImpersonate) {
            return false;
        }

        /* @var $switchedUser User */
        if (!($switchedUser = User::findOne(['id' => $switchedUserId]))) {
            return false;
        }

        return $switchedUser->id != Yii::$app->user->id;
    }

    /**
     * Check the requested code is valid for current User
     *
     * @param string $code Code value
     * @return bool
     */
    public static function isValidCode($code)
    {
        $driver = self::getDriver();

        if (!$driver) {
            // Don't restrict current User if proper Driver is not selected
            return true;
        }

        return $driver->checkCode($code);
    }

    /**
     * Returns the display name for TwoFactor devices/apps
     *
     * @return string
     * @throws \Throwable
     */
    public static function getAccountName()
    {
        return Yii::$app->name . ' - ' . Yii::$app->user->getIdentity()->username;
    }

    /**
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public static function isTrusted(): bool
    {
        /** @var TwofaModule $module */
        $module = Yii::$app->getModule('twofa');
        foreach ($module->getTrustedNetworks() as $trustedNet) {
            if (BaseIpHelper::inRange(Yii::$app->request->userIP, $trustedNet)) {
                return true;
            }
            if (Yii::$app->request->userIP !== Yii::$app->request->remoteIP
                && BaseIpHelper::inRange(Yii::$app->request->remoteIP, $trustedNet)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $days
     */
    public static function rememberBrowser($days = null)
    {
        // calculate expiration date
        $days ??= Yii::$app->getModule('twofa')->getRememberMeDays();
        $expire = (new DateTime())->modify("+$days DAYS")->getTimestamp();

        // calculate array of remembered user's
        $twofaRememberCookie = Yii::$app->request->cookies->get('twofa_remember');
        $value = $twofaRememberCookie instanceof Cookie ? $twofaRememberCookie->value : [];
        $value[] = Yii::$app->user->id;

        // remember browser
        $cookie = new Cookie(['name' => 'twofa_remember', 'value' => $value, 'expire' => $expire]);
        Yii::$app->response->cookies->add($cookie);
    }

    /**
     * @return bool
     */
    public static function isBrowserRemembered(): bool
    {
        if (empty(Yii::$app->getModule('twofa')->getRememberMeDays())) {
            return false;
        }

        if ($cookie = Yii::$app->request->cookies->get('twofa_remember')) {
            return in_array(Yii::$app->user->id, (array)$cookie->value);
        }

        return false;
    }
}
