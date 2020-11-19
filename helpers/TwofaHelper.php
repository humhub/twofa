<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\helpers;

use humhub\modules\content\components\ContentContainerSettingsManager;
use humhub\modules\twofa\drivers\BaseDriver;
use humhub\modules\twofa\drivers\EmailDriver;
use humhub\modules\user\models\User;
use humhub\modules\user\Module;
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
        /** @var Module $module */
        $module = Yii::$app->getModule('user');

        return $user ? $module->settings->contentContainer($user) : false;
    }

    /**
     * Get 2fa Driver for current User
     *
     * @return BaseDriver|false
     */
    public static function getDriver()
    {
        /** @var BaseDriver $driverClass */
        // TODO: Implement new user setting who should be checked by 2fa
        // $driverClass = self::getSettings()->get(self::USER_SETTING);
        $driverClass = EmailDriver::class; // Use temporary Email Driver by default enabled for all users

        if (in_array($driverClass, Yii::$app->getModule('twofa')->drivers )) {
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
        return ($settings = self::getSettings()) ? $settings->get(self::CODE_SETTING) : null;
    }

    /**
     * Enable verifying by 2fa for current User
     *
     * @return bool true on success enabling
     */
    public static function enableVerifying()
    {
        $settings = self::getSettings();
        $driver = self::getDriver();

        if (!$settings || !$driver) {
            // Current User may be not logged in
            // OR Wrong driver OR current User has no enabled 2fa
            return false;
        }

        if (!$driver->send()) {
            // Impossible to send a verifying code by Driver
            return false;
        }

        // TODO: Inform user about way of sending the verifying code

        // Store the sending verifying code in DB to use this as flag to display a form to check the code
        $settings->set(self::CODE_SETTING, md5($driver->getCode()));

        return true;
    }

    /**
     * Disable verifying by 2fa for current User
     */
    public static function disableVerifying()
    {
        if ($settings = self::getSettings()) {
            $settings->delete(self::CODE_SETTING);
        }
    }

    /**
     * Check if verifying by 2fa is required for current User
     */
    public static function isVerifyingRequired()
    {
        return self::getCode() !== null;
    }

    /**
     * Check the requested code is valid for current User
     *
     * @param $code
     * @return bool
     */
    public static function isValidCode($code)
    {
        return md5($code) === self::getCode();
    }
}