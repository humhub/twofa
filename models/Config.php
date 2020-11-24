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

    /**
     * @var array Ids of groups where users are enforced to use 2fa
     */
    public $enforcedGroups;

    /**
     * @var int Length of verifying code
     */
    public $codeLength;

    public function init()
    {
        parent::init();
        $this->module = Yii::$app->getModule('twofa');
        $this->enabledDrivers = $this->module->getEnabledDrivers();
        $this->codeLength = $this->module->getCodeLength();
        $this->enforcedGroups = $this->module->getEnforcedGroups();
    }

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            ['enabledDrivers', 'in', 'range' => array_keys($this->module->getDriversOptions()), 'allowArray' => true],
            ['codeLength', 'integer', 'min' => 4],
            ['enforcedGroups', 'in', 'range' => array_keys($this->module->getGroupsOptions()), 'allowArray' => true]
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
            'enabledDrivers' => Yii::t('TwofaModule.config', 'Enabled drivers'),
            'codeLength' => Yii::t('TwofaModule.config', 'Length of verifying code'),
            'enforcedGroups' => Yii::t('TwofaModule.config', 'Enforced groups to use 2FA'),
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $this->module->settings->set('enabledDrivers', empty($this->enabledDrivers) ? '' : implode(',', $this->enabledDrivers));
        $this->module->settings->set('enforcedGroups', empty($this->enforcedGroups) ? '' : implode(',', $this->enforcedGroups));
        $this->module->settings->set('codeLength', $this->codeLength);
        return true;
    }
}
