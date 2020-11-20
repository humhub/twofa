<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\twofa\models\UserSettings;

/* @var $model UserSettings */

use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\Button;
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('TwofaModule.config', 'User settings for Two-Factor Authentication') ?></div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(); ?>
            <div class="help-block">
                <?= Yii::t('TwofaModule.config', 'Here you can configure two-factor authentication settings for your account.') ?>
            </div>

            <hr>

            <?= $form->field($model, 'driver')->dropDownList($model->getDrivers()) ?>

            <?= Button::primary(Yii::t('base', 'Save'))->submit() ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>
