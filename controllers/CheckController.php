<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\controllers;

use humhub\components\Controller;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\models\CheckCode;
use Yii;

/**
 * Class CheckController
 * @package humhub\modules\twofa\controllers
 */
class CheckController extends Controller
{
    /**
     * @inheritdoc
     */
    protected $doNotInterceptActionIds = ['*'];

    /**
     * @inheritdoc
     */
    public $layout = "@user/views/layouts/main";

    /**
     * Renders a form to check user after log in by two-factor authentication
     *
     * @return string|\yii\web\Response
     */
    public function actionIndex()
    {
        if (!TwofaHelper::isVerifyingRequired()) {
            return $this->goHome();
        }

        if (isset(Yii::$app->getModule('live')->isActive)) {
            Yii::$app->getModule('live')->isActive = false;
        }

        $model = new CheckCode();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->goHome();
        }

        return $this->render('index', [
            'model' => $model, 'driver' => TwofaHelper::getDriver(),
            'rememberDays' => Yii::$app->getModule('twofa')->getRememberMeDays()
        ]);
    }

}

