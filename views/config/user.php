<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/* @var $model UserSettings */

use humhub\modules\twofa\models\UserSettings;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\user\widgets\AccountSettingsMenu;
use humhub\widgets\Button;

?>

<div class="panel-heading">
    <?= Yii::t('TwofaModule.base', '<strong>Two-Factor Authentication</strong> settings'); ?>
</div>

<?= AccountSettingsMenu::widget(); ?>

<div class="panel-body">
    <div class="help-block">
        <?= Yii::t('TwofaModule.base', 'Two-factor authentication (2FA) provides an additional level of security for your account. Once enabled, you will be prompted to enter a code in addition to entering your username and password.'); ?>
    </div>
    <br/>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'driver')->dropDownList($model->getDrivers(), ['data-action-change' => 'twofa.selectDriver']) ?>

    <?php $model->renderDriversFields($form) ?>

    <?= Button::primary(Yii::t('base', 'Save'))->submit() ?>

    <?php ActiveForm::end(); ?>
</div>
