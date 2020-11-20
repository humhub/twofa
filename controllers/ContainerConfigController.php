<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\controllers;

use humhub\modules\twofa\models\UserSettings;
use humhub\modules\wiki\permissions\AdministerPages;
use humhub\modules\content\components\ContentContainerController;
use Yii;

class ContainerConfigController extends ContentContainerController
{
    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [
          ['permission' => [AdministerPages::class]]
        ];
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
