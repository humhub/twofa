<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa;

use humhub\helpers\ControllerHelper;
use humhub\modules\admin\controllers\UserController as AdminUserController;
use humhub\modules\admin\grid\UserActionColumn;
use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\twofa\events\BeforeCheck;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\helpers\TwofaUrl;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\controllers\AuthController;
use humhub\modules\user\events\UserEvent;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\AccountMenu;
use humhub\modules\user\widgets\AccountSettingsMenu;
use Yii;
use yii\web\Controller;

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
        if (Yii::$app->user->mustChangePassword()) {
            return false;
        }

        /** @var Controller $controller */
        $controller = $event->sender;

        if (self::isImpersonateAction($controller)) {
            Yii::$app->session->set('twofa.switchedUserId', Yii::$app->user->id);
        }

        if (
            $controller->module->id === 'fcm-push'
            && $controller->id === 'token'
            && $controller->action->id === 'update'
        ) {
            return false;
        }

        $beforeVerifying = new BeforeCheck();
        Yii::$app->trigger($beforeVerifying->name, $beforeVerifying);

        if (!$beforeVerifying->handled && TwofaHelper::isVerifyingRequired() && !Yii::$app->getModule('twofa')->isTwofaCheckUrl()) {
            return Yii::$app->response->redirect(TwofaUrl::toCheck());
        }
    }

    /**
     * Check if currently action "Impersonate" is called
     *
     * @param $controller Controller
     * @return bool
     */
    protected static function isImpersonateAction($controller): bool
    {
        return ($controller instanceof AdminUserController)
            && isset($controller->action)
            && $controller->action->id === 'impersonate'
            && Yii::$app->user->can(ManageUsers::class);
    }

    /**
     * Clear temp user ID which was used for administration action "Impersonate"
     *
     * @param $event
     */
    public static function onAfterAction($event)
    {
        if ($event->sender instanceof AuthController && $event->sender->action->id == 'logout') {
            TwofaHelper::resetSessionStatus();
            Yii::$app->session->remove('twofa.switchedUserId');
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
        TwofaHelper::resetSessionStatus();
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
        $isActiveMenu = ControllerHelper::isActivePath($menuRoute[0], $menuRoute[1]);

        /* @var AccountSettingsMenu $menu */
        $menu = $event->sender;

        $menu->addEntry(new MenuLink([
            'label' => Yii::t('TwofaModule.base', 'Two-Factor Authentication'),
            'url' => Yii::$app->user->identity->createUrl(TwofaUrl::ROUTE_USER_SETTINGS),
            'isActive' => $isActiveMenu,
            'sortOrder' => 300,
        ]));

        if ($isActiveMenu) {
            AccountMenu::markAsActive('account-settings-settings');
        }
    }

    public static function onUserActionColumnAfterInitActions($event): void
    {
        if (!Yii::$app->user->can(ManageUsers::class)) {
            return;
        }

        /** @var UserActionColumn $actionColumn */
        $actionColumn = $event->sender;

        if (empty($actionColumn->actions) || !is_array($actionColumn->actions)) {
            return;
        }

        $resetLabel = Yii::t('TwofaModule.base', 'Reset two-factor authentication');
        if (isset($actionColumn->actions[$resetLabel])) {
            return;
        }

        $actions = [];
        $inserted = false;

        foreach ($actionColumn->actions as $actionTitle => $action) {
            $actions[$actionTitle] = $action;

            if (!$inserted && $actionTitle === Yii::t('base', 'Edit')) {
                $actions[$resetLabel] = [
                    '/twofa/admin/reset-user',
                    'returnUrl' => Yii::$app->request->url,
                    'linkOptions' => [
                        'data-action-method' => 'post',
                        'data-action-confirm-header' => Yii::t('TwofaModule.base', '<strong>Confirm</strong> two-factor authentication reset'),
                        'data-action-confirm' => Yii::t('TwofaModule.base', 'This will remove the current two-factor authentication setup for this user. They will need to configure it again on the next login.'),
                        'data-action-confirm-text' => $resetLabel,
                    ],
                ];
                $inserted = true;
            }
        }

        if ($inserted) {
            $actionColumn->actions = $actions;
        }
    }

    public static function canResetUser(User $user): bool
    {
        return Yii::$app->user->can(ManageUsers::class)
            && (Yii::$app->user->isAdmin() || !$user->isSystemAdmin());
    }
}
