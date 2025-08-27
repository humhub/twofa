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
use humhub\widgets\form\ActiveForm;
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

    /**
     * @var BaseDriver[]
     */
    protected $driverObjects = [];

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
        return [];
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
            $this->getDriver($driverClassName)->renderUserSettings([
                'form' => $form,
                'activeDriverClassName' => $this->driver,
            ]);
        }
    }

    /**
     * Get driver by class name
     *
     * @param string Driver class name, null - to get current driver
     * @return Model|false
     */
    protected function getDriver($driverClassName = null)
    {
        if ($driverClassName === null) {
            $driverClassName = $this->driver;
        }

        if (!isset($this->driverObjects[$driverClassName])) {
            $this->driverObjects[$driverClassName] = TwofaHelper::getDriverByClassName($driverClassName);
        }

        return $this->driverObjects[$driverClassName];
    }

    /**
     * @return Model|false
     */
    protected function getDriverSettings()
    {
        return $this->getDriver() ? $this->getDriver()->getUserSettings() : false;
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

    /**
     * Save form with validated params loaded from request
     *
     * @return bool
     */
    public function validatedSave()
    {
        return $this->load(Yii::$app->request->post()) &&
            $this->validate() &&
            $this->driverValidatedSave() &&
            $this->save();
    }

    /**
     * Save form with validated params loaded from request per current selected Driver
     *
     * @return bool
     */
    protected function driverValidatedSave()
    {
        $driverSettings = $this->getDriverSettings();

        if (!$driverSettings) {
            return true;
        }

        return $driverSettings->load(Yii::$app->request->post()) &&
            $driverSettings->validate() &&
            $driverSettings->save();
    }

}
