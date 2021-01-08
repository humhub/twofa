<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\models;

use humhub\modules\twofa\drivers\BaseDriver;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\Module;
use humhub\modules\ui\form\widgets\ActiveForm;
use Yii;
use yii\base\Model;

/**
 * This is the form for User Settings of Two-Factor Authentication
 */
class UserSettings extends Model
{

    /**
     * @var Module
     */
    public $module;

    /**
     * @var string Class name of Driver: '\humhub\modules\twofa\drivers\EmailDriver'
     */
    public $driver;

    public function init()
    {
        $this->module = Yii::$app->getModule('twofa');
        $this->driver = TwofaHelper::getDriverSetting();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['driver', 'string'],
            ['driver', 'in', 'range' => array_keys($this->getDrivers())],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'driver' => Yii::t('TwofaModule.base', 'Authentication method'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'driver' => Yii::t('TwofaModule.base', 'Choose between different methods which you can use as a second factor to increase your account security.'),
        ];
    }

    /**
     * Get available drivers for current User in the 2fa module
     *
     * @return array
     */
    public function getDrivers()
    {
        if (TwofaHelper::isEnforcedUser()) {
            // User from enforced group should be denied to unselect 2fa driver
            $noneOption = [$this->module->defaultDriver => TwofaHelper::getDriverByClassName($this->module->defaultDriver)->name];
        } else {
            $noneOption = ['' => Yii::t('TwofaModule.base', 'Disable two-factor authentication (not recommended)')];
        }

        return $this->module->getDriversOptions($noneOption, true);
    }

    /**
     * Display additional fields of all enabled drivers
     *
     * @param ActiveForm $form
     */
    public function renderDriversFields($form)
    {
        $drivers = $this->module->getEnabledDrivers();
        foreach ($drivers as $driverClassName) {
            $driver = TwofaHelper::getDriverByClassName($driverClassName);
            $driver->renderUserSettings($form, $this);
        }
    }

    /**
     * Save 2fa settings per current User
     *
     * @return bool
     */
    public function save()
    {
        return TwofaHelper::setSetting(TwofaHelper::USER_SETTING, $this->driver);
    }

}
