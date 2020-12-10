<?php

use humhub\libs\Html;
use humhub\modules\twofa\models\Config;
use humhub\widgets\Button;
use yii\bootstrap\ActiveForm;

/* @var $model Config */
/* @var $defaultDriverName string */
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('TwofaModule.config', '<strong>Two-Factor Authentication</strong> module configuration'); ?></div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form']); ?>

            <div id="disabledDriversInfo" class="alert alert-warning"<?= empty($model->enabledDrivers) ? '' : ' style="display:none"' ?>>
                <i class="fa fa-info-circle"></i> <?= Yii::t('TwofaModule.config', 'This module is disabled because no drivers are selected, however users from the enforced groups always fallback to {defaultDriverName} driver by default.', [
                    'defaultDriverName' => $defaultDriverName
                ]) ?>
            </div>

            <?= $form->field($model, 'enabledDrivers')->checkboxList($model->module->getDriversOptions(), [
                    'item' => array($model->module, 'renderDriverCheckboxItem')
                ]); ?>

            <?= $form->field($model, 'enforcedGroups')->checkboxList($model->module->getGroupsOptions()); ?>
            <div class="help-block"><?= Yii::t('TwofaModule.config', 'Users of the selected groups are enforced to {defaultDriverName} driver by default.', [
                    'defaultDriverName' => $defaultDriverName
                ]) ?></div>

            <?= $form->field($model, 'codeLength'); ?>

            <?= Button::save()->submit() ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>
<?= Html::script(<<<JS
    $('[name="Config[enabledDrivers][]"]').on('click', function() {
        $('#disabledDriversInfo').toggle($('[name="Config[enabledDrivers][]"]:checked').length === 0)
    })
JS
); ?>
