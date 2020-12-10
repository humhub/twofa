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
use humhub\modules\twofa\drivers\BaseDriver;
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

        return $this->render('module', [
            'model' => $model,
            'defaultDriverName' => TwofaHelper::getDriverByClassName($model->module->defaultDriver)->name,
        ]);
    }
}
