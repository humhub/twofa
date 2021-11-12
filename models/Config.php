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

    /**
     * @var string of trusted networks
     */
    public $trustedNetworks;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        $this->module = Yii::$app->getModule('twofa');
        $this->enabledDrivers = $this->module->getEnabledDrivers();
        $this->codeLength = $this->module->getCodeLength();
        $this->enforcedGroups = $this->module->getEnforcedGroups();
        $this->trustedNetworks = implode(', ', $this->module->getTrustedNetworks());
    }

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            ['enabledDrivers', 'in', 'range' => array_keys($this->module->getDriversOptions()), 'allowArray' => true],
            ['codeLength', 'integer', 'min' => 4],
            ['enforcedGroups', 'in', 'range' => array_keys($this->module->getGroupsOptions()), 'allowArray' => true],
            ['trustedNetworks', 'string']
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
            'enabledDrivers' => Yii::t('TwofaModule.config', 'Enabled methods'),
            'codeLength' => Yii::t('TwofaModule.config', 'Length of verifying code'),
            'enforcedGroups' => Yii::t('TwofaModule.config', 'Mandatory for the following groups'),
            'trustedNetworks' => Yii::t('TwofaModule.config', 'Trusted networks list'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $this->module->settings->set('enabledDrivers', empty($this->enabledDrivers) ? '' : implode(',', $this->enabledDrivers));
        $this->module->settings->set('enforcedGroups', empty($this->enforcedGroups) ? '' : implode(',', $this->enforcedGroups));
        $this->module->settings->set('codeLength', $this->codeLength);
        $this->module->settings->set('trustedNetworks', json_encode($this->getTrustedNetworksArray()));

        return true;
    }

    /**
     * @return array
     */
    protected function getTrustedNetworksArray()
    {
        if (is_array($this->trustedNetworks)) {
            return $this->trustedNetworks;
        }

        $networks = explode(',', $this->trustedNetworks);
        foreach ($networks as &$network) {
            $this->trimTrustedNetwork($network);
        }

        return $networks;
    }

    /**
     * @param $network
     */
    protected function trimTrustedNetwork(&$network)
    {
        $network = trim($network);
        // perform other actions if required
    }
}
