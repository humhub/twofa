<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\helpers;

use humhub\modules\content\components\ContentContainerSettingsManager;
use humhub\modules\twofa\drivers\BaseDriver;
use humhub\modules\twofa\Module as TwofaModule;
use humhub\modules\user\models\User;
use humhub\modules\user\Module as UserModule;
use Yii;

class TwofaHelper
{
    const USER_SETTING = 'twofaDriver';
    const CODE_SETTING = 'twofaCode';

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
     * Get 2fa Driver for current User
     *
     * @return BaseDriver|false
     */
    public static function getDriver()
    {
        /** @var BaseDriver $driverClass */
        $driverClass = self::getSetting(self::USER_SETTING);
        /** @var TwofaModule $module */
        $module = Yii::$app->getModule('twofa');

        if ($driverClass && in_array($driverClass, $module->getEnabledDrivers())) {
            $driverClass = '\\' . $driverClass;
            return new $driverClass();
        }

        return false;
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
     */
    public static function isVerifyingRequired()
    {
        return self::getDriver() && self::getCode() !== null;
    }

    /**
     * Check the requested code is valid for current User
     *
     * @param string $code Code value
     * @return bool
     */
    public static function isValidCode($code)
    {
        return self::hashCode($code) === self::getCode();
    }
}