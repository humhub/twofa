<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa;

use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\helpers\TwofaUrl;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\events\UserEvent;
use humhub\modules\user\widgets\AccountMenu;
use Yii;

class Events
{
    /**
     * @inheritdoc
     */
    public static function onBeforeRequest()
    {
        try {
            static::registerAutoloader();
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    /**
     * Register composer autoloader
     */
    public static function registerAutoloader()
    {
        $autoloaderFilePath = Yii::getAlias('@twofa/vendor/autoload.php');
        if (file_exists($autoloaderFilePath)) {
            require $autoloaderFilePath;
        }
    }

    /**
     * Check if current User has been verified by 2fa if it is required
     *
     * @param $event
     * @return false|\yii\console\Response|\yii\web\Response
     */
    public static function onBeforeAction($event)
    {
        if (Yii::$app->request->isAjax) {
            // TODO: maybe it should be restricted better, but we don't need to call this for PollController from live module indeed
            return false;
        }

        if (TwofaHelper::isVerifyingRequired() &&
            !Yii::$app->getModule('twofa')->isTwofaCheckUrl()) {
            return Yii::$app->getResponse()->redirect(TwofaUrl::toCheck());
        }
    }

    /**
     * Set flag after login to user who need 2fa
     *
     * @param $event
     * @throws \Throwable
     */
    public static function onAfterLogin($event)
    {
        TwofaHelper::enableVerifying();
    }

    /**
     * Add menu to edit module setting per current User
     *
     * @param UserEvent $event
     */
    public static function onProfileSettingMenuInit($event)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $menuRoute = explode('/', trim(TwofaUrl::ROUTE_USER_SETTINGS, '/'));
        $isActiveMenu = MenuLink::isActiveState($menuRoute[0], $menuRoute[1]);

        $event->sender->addItem([
            'label' => Yii::t('TwofaModule.base', 'Two-Factor Authentication'),
            'url' => Yii::$app->user->identity->createUrl(TwofaUrl::ROUTE_USER_SETTINGS),
            'isActive' => $isActiveMenu,
            'sortOrder' => 300
        ]);

        if ($isActiveMenu) {
            AccountMenu::markAsActive('account-settings-settings');
        }
    }
}
