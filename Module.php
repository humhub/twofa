<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\twofa\drivers\EmailDriver;
use humhub\modules\twofa\helpers\TwofaUrl;
use humhub\modules\user\models\User;
use Yii;

class Module extends ContentContainerModule
{
    /**
     * @var array Drivers
     */
    public $drivers = [
        EmailDriver::class,
    ];

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            User::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return TwofaUrl::toConfig();
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerConfigUrl(ContentContainerActiveRecord $container)
    {
        return $container->createUrl('/twofa/container-config');
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        if ($container instanceof User) {
            return Yii::t('TwofaModule.base', 'Two-factor authentication for your account.');
        }
    }

    /**
     * @return bool Check if current page is already URL of 2fa
     */
    public function isTwofaCheckUrl()
    {
        return Yii::$app->requestedRoute === trim(TwofaUrl::ROUTE_CHECK, '/');
    }

    /**
     * Get available drivers options for the 2fa module settings
     *
     * @param array|null None option
     * @param boolean true - to load only enabled drivers, false - to load all implemented drivers for the module
     * @return array
     */
    public function getDriversOptions($noneOption = null, $onlyEnabled = false)
    {
        $driversOptions = [];
        if ($noneOption !== null) {
            $driversOptions[''] = $noneOption;
        }

        $drivers = $onlyEnabled ? $this->getEnabledDrivers() : $this->drivers;

        foreach ($drivers as $driverClassName) {
            $driversOptions[$driverClassName] = (new $driverClassName())->name;
        }

        return $driversOptions;
    }

    /**
     * Get enabled drivers
     *
     * @return array
     */
    function getEnabledDrivers()
    {
        $enabledDrivers = $this->settings->get('enabledDrivers', implode(',', $this->drivers));
        return empty($enabledDrivers) ? [] : explode(',', $enabledDrivers);
    }
}
