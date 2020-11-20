<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\models;

use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\Module;
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
        $this->driver = TwofaHelper::getSetting(TwofaHelper::USER_SETTING);
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
            'driver' => Yii::t('TwofaModule.base', 'Driver'),
        ];
    }

    /**
     * Get available drivers for the 2fa module
     *
     * @return array
     */
    public function getDrivers()
    {
        $driverOptions = ['' => Yii::t('TwofaModule.base', 'None')];
        foreach ($this->module->drivers as $driverClassName) {
            $driverOptions[$driverClassName] = (new $driverClassName())->name;
        }

        return $driverOptions;
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
