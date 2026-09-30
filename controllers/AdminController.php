<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\content\models\ContentContainerSetting;
use humhub\modules\twofa\Events;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\models\Config;
use humhub\modules\user\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

class AdminController extends Controller
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            ['permission' => ManageModules::class, 'actions' => ['index']],
            ['permission' => ManageUsers::class, 'actions' => ['users', 'reset-user']],
        ];
    }

    public function actionIndex()
    {
        $model = new Config();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
            return $this->refresh();
        }

        $ip = Yii::$app->request->userIP;
        if ($ip !== Yii::$app->request->remoteIP) {
            $ip .= ', ' . Yii::$app->request->remoteIP;
        }

        return $this->render('tabs', [
            'tab' => $this->renderPartial('index', [
                'model' => $model,
                'enforcedMethodName' => TwofaHelper::getDriverByClassName($model->module->getEnforcedMethod())->name,
                'ip' => $ip,
            ]),
        ]);
    }

    public function actionUsers()
    {
        $driverOptions = Yii::$app->getModule('twofa')->getDriversOptions([], true);
        $query = User::find()->where('1=0');

        if (!empty($driverOptions)) {
            $query = User::find()
                ->with('profile')
                ->administrableBy(Yii::$app->user->identity)
                ->innerJoin(
                    ContentContainerSetting::tableName() . ' twofaSetting',
                    'twofaSetting.contentcontainer_id = ' . User::tableName() . '.contentcontainer_id'
                    . ' AND twofaSetting.module_id = :moduleId'
                    . ' AND twofaSetting.name = :settingName',
                    [
                        ':moduleId' => 'user',
                        ':settingName' => TwofaHelper::USER_SETTING,
                    ],
                )
                ->andWhere([User::tableName() . '.status' => User::STATUS_ENABLED])
                ->andWhere(['twofaSetting.value' => array_keys($driverOptions)])
                ->distinct();
        }

        $userDataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['last_login' => SORT_DESC],
                'attributes' => [
                    'username',
                    'email',
                    'last_login',
                    'twofaDriver' => [
                        'asc' => ['twofaSetting.value' => SORT_ASC],
                        'desc' => ['twofaSetting.value' => SORT_DESC],
                        'label' => Yii::t('TwofaModule.base', 'Method'),
                    ],
                ],
            ],
        ]);

        $driverValues = [];
        $contentContainerIds = array_map(
            static fn(User $user) => $user->contentcontainer_id,
            $userDataProvider->getModels(),
        );

        if (!empty($contentContainerIds)) {
            $driverValues = ContentContainerSetting::find()
                ->select(['value'])
                ->where([
                    'module_id' => 'user',
                    'name' => TwofaHelper::USER_SETTING,
                    'contentcontainer_id' => $contentContainerIds,
                ])
                ->indexBy('contentcontainer_id')
                ->column();
        }

        return $this->render('tabs', [
            'tab' => $this->renderPartial('users', [
                'driverOptions' => $driverOptions,
                'driverValues' => $driverValues,
                'userDataProvider' => $userDataProvider,
            ]),
        ]);
    }

    /**
     * Reset stored 2FA settings for a specific user.
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionResetUser($id)
    {
        $this->forcePostRequest();

        $user = User::findOne(['id' => $id]);
        if (!$user instanceof User) {
            throw new NotFoundHttpException(Yii::t('AdminModule.user', 'User not found!'));
        }

        if (!Events::canResetUser($user)) {
            $this->forbidden();
        }

        TwofaHelper::resetUserSettings($user);
        $this->view->success(Yii::t('TwofaModule.base', 'Two-factor authentication has been reset for this user.'));

        $returnUrl = Yii::$app->request->get('returnUrl');
        if (is_string($returnUrl) && str_starts_with($returnUrl, '/')) {
            return $this->redirect($returnUrl);
        }

        return $this->redirect(['/twofa/admin/users']);
    }
}
