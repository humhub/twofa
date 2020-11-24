<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\controllers;

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

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('@twofa/views/config/user', [
            'model' => $model
        ]);
    }
}
