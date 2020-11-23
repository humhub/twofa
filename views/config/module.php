<?php

use humhub\libs\Html;
use humhub\modules\twofa\models\Config;
use humhub\widgets\Button;
use yii\bootstrap\ActiveForm;

/* @var $model Config */
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('TwofaModule.config', '<strong>Two-Factor Authentication</strong> module configuration'); ?></div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form']); ?>

            <?= $form->field($model, 'enabledDrivers')->checkboxList($model->module->getDriversOptions()); ?>

            <div id="disabledDriversInfo" class="alert alert-warning"<?= empty($model->enabledDrivers) ? '' : ' style="display:none"' ?>>
                <i class="fa fa-info-circle"></i> <?= Yii::t('TwofaModule.config', 'This module is completely disabled because no drivers are selected.') ?>
            </div>

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
