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
    const USER_SETTING = 'twofaDriver';
    const CODE_SETTING = 'twofaCode';
    const CODE_CHARS = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     * Get settings manager of current User
     *
     * @return ContentContainerSettingsManager|false
     */
    public static function getSettings()
    {
        /** @var User $user */
        $user = Yii::$app->user->getIdentity();
        /** @var UserModule $module */
        $module = Yii::$app->getModule('user');

        return $user ? $module->settings->contentContainer($user) : false;
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

        $driverClassName = '\\' . trim($driverClassName, '\\');

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
     * @return boolean
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
     * Returns a random code
     *
     * @param $len
     * @return string
     */
    public static function generateCode($len)
    {
        // To complex: return Yii::$app->security->generateRandomString($len);
        $noChars = strlen(static::CODE_CHARS);

        $code = '';
        for ($i = 0; $i < $len; $i++) {
            $code .= static::CODE_CHARS[rand(0, $noChars - 1)];
        }
        return $code;
    }

    /**
     * Hash code
     *
     * @param string $code Code value
     * @return string Hashed code
     */
    public static function hashCode($code)
    {
        return md5($code);
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

        // Store the sending verifying code in DB to use this as flag to display a form to check the code
        if (!self::setSetting(self::CODE_SETTING, self::hashCode($driver->getCode()))) {
            return false;
        }

        // TODO: Inform user about way of sending the verifying code

        return true;
    }

    /**
     * Disable verifying by 2fa for current User
     *
     * @return bool true on success disabling
     */
    public static function disableVerifying()
    {
        // Remove the verifying code from DB:
        return self::setSetting(self::CODE_SETTING);
    }

    /**
     * Check if verifying by 2fa is required for current User
     *
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public static function isVerifyingRequired()
    {
        // if impersonate mode of driver is not set up
        if (self::isImpersonateMode() || !self::getDriver()) {
            return false;
        }

        // if code is missing for a user, or user is trusted (ip whitelist)
        if (self::getCode() === null || self::isTrusted()) {
            return false;
        }

        // if user's ticked remember browser
        if (self::isBrowserRemembered()) {
            return false;
        }

        return true;
    }

    /**
     * Check if current User was logged in from administration action "Impersonate"
     *
     * @return bool
     */
    protected static function isImpersonateMode(): bool
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
    public static function isTrusted()
    {
        /** @var TwofaModule $module */
        $module = Yii::$app->getModule('twofa');
        foreach ($module->getTrustedNetworks() as $trustedNet) {
            if (BaseIpHelper::inRange(Yii::$app->request->userIP, $trustedNet)) {
                return true;
            }
            if (Yii::$app->request->userIP !== Yii::$app->request->remoteIP &&
                BaseIpHelper::inRange(Yii::$app->request->remoteIP, $trustedNet)) {
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
        $days = $days ?? Yii::$app->getModule('twofa')->getRememberMeDays();
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
    public static function isBrowserRemembered()
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
