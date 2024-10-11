<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\controllers;

use humhub\modules\twofa\assets\Assets;
use humhub\modules\twofa\drivers\BaseDriver;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\models\UserSettings;
use humhub\modules\user\components\BaseAccountController;
use Yii;

class UserSettingsController extends BaseAccountController
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setActionTitles([
            'index' => Yii::t('TwofaModule.config', 'Two-Factor Authentication'),
        ]);
        return parent::init();
    }

    public function actionIndex()
    {
        $model = new UserSettings();

        if ($model->validatedSave()) {
            $this->view->saved();
        }

        Assets::register($this->view);

        return $this->render('@twofa/views/config/user', [
            'model' => $model,
        ]);
    }

    /**
     * Execute specific driver action
     */
    public function actionDriverAction()
    {
        $driver = TwofaHelper::getDriverByClassName(Yii::$app->request->post('driver'));
        if (!$driver || !($driver instanceof BaseDriver)) {
            return;
        }

        return $driver->callAction(Yii::$app->request->post('action'), Yii::$app->request->post());
    }
}
