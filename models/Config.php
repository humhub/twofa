<?php

namespace humhub\modules\twofa\models;

use humhub\modules\twofa\Module;
use Yii;
use yii\base\Model;

/**
 * This is the form for Module Settings of Two-Factor Authentication
 */
class Config extends Model
{

    /**
     * @var Module
     */
    public $module;

    /**
     * @var array Enabled drivers
     */
    public $enabledDrivers;

    public function init()
    {
        parent::init();
        $this->module = Yii::$app->getModule('twofa');
        $this->enabledDrivers = $this->module->getEnabledDrivers();
    }

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            ['enabledDrivers', 'in', 'range' => array_keys($this->module->getDriversOptions()), 'allowArray' => true],
        ];
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return [
            'enabledDrivers' => Yii::t('MailModule.base', 'Enabled drivers'),
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $this->module->settings->set('enabledDrivers', empty($this->enabledDrivers) ? '' : implode(',', $this->enabledDrivers));
        return true;
    }
}
