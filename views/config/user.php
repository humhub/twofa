<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\twofa\models\UserSettings;

/* @var $model UserSettings */

use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\user\widgets\AccountSettingsMenu;
use humhub\widgets\Button;
?>

<div class="panel-heading">
    <?= Yii::t('TwofaModule.config', '<strong>Two-Factor Authentication</strong> settings'); ?>
</div>

<?= AccountSettingsMenu::widget(); ?>

<div class="panel-body">
    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'driver')->dropDownList($model->getDrivers()) ?>

        <?= Button::primary(Yii::t('base', 'Save'))->submit() ?>

    <?php ActiveForm::end(); ?>
</div>
