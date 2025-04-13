<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa;

use humhub\components\Module as BaseModule;
use humhub\libs\Html;
use humhub\modules\admin\models\forms\UserEditForm;
use humhub\modules\twofa\drivers\EmailDriver;
use humhub\modules\twofa\drivers\GoogleAuthenticatorDriver;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\helpers\TwofaUrl;
use humhub\modules\user\models\Group;
use Yii;

/**
 * @inheritdoc
 */
class Module extends BaseModule
{
    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';

    /**
     * @var string Default Driver, used for Users from enforced Groups by default
     */
    public $defaultDriver = EmailDriver::class;

    /**
     * @var array Drivers
     */
    public $drivers = [
        EmailDriver::class,
        GoogleAuthenticatorDriver::class,
    ];

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return TwofaUrl::toConfig();
    }

    /**
     * @return bool Check if current page is already URL of 2fa
     */
    public function isTwofaCheckUrl()
    {
        return Yii::$app->getRequest()->getUrl() === TwofaUrl::toCheck();
    }

    /**
     * Get available drivers options for the 2fa module settings
     *
     * @param array|null Init options(Key - Driver class name, Value - Drive name), used to init None option and/or forced/default Driver
     * @param bool true - to load only enabled drivers, false - to load all implemented drivers for the module
     * @return array
     */
    public function getDriversOptions($driversOptions = [], $onlyEnabled = false)
    {
        $drivers = $onlyEnabled ? $this->getEnabledDrivers() : $this->drivers;
        foreach ($drivers as $driverClassName) {
            $driversOptions[$driverClassName] = TwofaHelper::getDriverByClassName($driverClassName)->name;
        }
        return $driversOptions;
    }

    /**
     * Callback function to render checkbox element of Driver on backoffice module config form
     *
     * @param $index
     * @param $label
     * @param $name
     * @param $checked
     * @param $value
     * @return string
     */
    public function renderDriverCheckboxItem($index, $label, $name, $checked, $value)
    {
        $options = [
            'label' => Html::encode($label),
            'value' => $value,
            'disabled' => !TwofaHelper::getDriverByClassName($value)->isInstalled(),
        ];

        return '<div class="checkbox">' . Html::checkbox($name, $checked, $options) . '</div>';
    }

    /**
     * Get enabled drivers
     *
     * @param bool $checkActive
     * @return array
     */
    public function getEnabledDrivers(bool $checkActive = true): array
    {
        $enabledDrivers = $this->settings->get('enabledDrivers', implode(',', $this->drivers));

        if (empty($enabledDrivers)) {
            return [];
        }

        // Check if each enabled Driver is properly installed:
        $enabledDrivers = explode(',', $enabledDrivers);
        foreach ($enabledDrivers as $d => $enabledDriverClassName) {
            $enabledDriver = TwofaHelper::getDriverByClassName($enabledDriverClassName);
            if (!$enabledDriver->isInstalled() || ($checkActive && !$enabledDriver->isActive())) {
                unset($enabledDrivers[$d]);
            }
        }

        return $enabledDrivers;
    }

    /**
     * Get length of verifying code
     *
     * @return int
     */
    public function getCodeLength()
    {
        return intval($this->settings->get('codeLength', 6));
    }

    /**
     * Get length of verifying code
     *
     * @return int
     */
    public function getCodeTtl()
    {
        return intval($this->settings->get('codeTtl', 30 * 60));
    }

    /**
     * Get length in days of remember me option
     *
     * @return int
     */
    public function getRememberMeDays()
    {
        return $this->settings->get('rememberMeDays', 7);
    }

    /**
     * Get groups options for the 2fa module settings
     *
     * @return array
     */
    public function getGroupsOptions()
    {
        $groups = Group::find()->all();

        return UserEditForm::getGroupItems($groups);
    }

    /**
     * Get enforced groups to 2fa E-mail driver
     *
     * @return array
     */
    public function getEnforcedGroups()
    {
        $enforcedGroups = $this->settings->get('enforcedGroups');
        if ($enforcedGroups === null) {
            // Enforce all Administrative Groups by default
            return Group::find()->select('id')->where(['is_admin_group' => '1'])->column();
        }

        return empty($enforcedGroups) ? [] : explode(',', $enforcedGroups);
    }

    /**
     * Get default method for the mandatory/enforced groups
     *
     * @return string
     */
    public function getEnforcedMethod(): string
    {
        return $this->settings->get('enforcedMethod', $this->defaultDriver);
    }

    /**
     * @return mixed
     */
    public function getTrustedNetworks()
    {
        return json_decode($this->settings->get('trustedNetworks', '[]'));
    }
}
