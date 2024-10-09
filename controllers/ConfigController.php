<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\twofa\controllers;

use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\admin\components\Controller;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\models\Config;
use Yii;

class ConfigController extends Controller
{
    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [['permissions' => ManageModules::class]];
    }

    /**
     * Configuration action for system admins.
     */
    public function actionIndex()
    {
        $model = new Config();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        $ip = Yii::$app->request->userIP;
        if ($ip !== Yii::$app->request->remoteIP) {
            $ip .= ', ' . Yii::$app->request->remoteIP;
        }

        return $this->render('module', [
            'model' => $model,
            'defaultDriverName' => TwofaHelper::getDriverByClassName($model->module->defaultDriver)->name,
            'ip' => $ip,
        ]);
    }
}
