<?php

use humhub\helpers\Html;
use humhub\modules\twofa\drivers\EmailDriver;
use humhub\modules\twofa\models\Config;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;

/**
 * @var $model Config
 * @var $enforcedMethodName string
 * @var $ip string
 */

?>

<?php $form = ActiveForm::begin(['id' => 'configure-form']); ?>

<div id="disabledDriversInfo"
     class="alert alert-warning<?= empty($model->enabledDrivers) ? '' : ' d-none' ?>">
    <i class="fa fa-info-circle" aria-hidden="true"></i>
    <?= Yii::t('TwofaModule.base', 'This module is disabled because no drivers are selected, however users from the enforced groups always fallback to {defaultDriverName} driver by default.', [
        'defaultDriverName' => $enforcedMethodName
    ]) ?>
</div>

<?= $form->field($model, 'enabledDrivers')->checkboxList($model->module->getDriversOptions(), [
    'item' => [$model->module, 'renderDriverCheckboxItem']
]); ?>
<br/>

<?= $form->field($model, 'enforcedGroups')->checkboxList($model->module->getGroupsOptions()); ?>

<?= $form->field($model, 'enforcedMethod')->dropDownList($model->module->getDriversOptions()); ?>

<?= $form->field($model, 'codeLength')->input('number', ['min' => 4, 'max' => 512]); ?>

<?php if (in_array(EmailDriver::class, $model->enabledDrivers)) : ?>
    <?= $form->field($model, 'codeTtl')->input('number', ['min' => 60]); ?>
<?php endif; ?>

<?= $form->field($model, 'rememberMeDays')->input('number', ['min' => 0, 'max' => 365]); ?>
<div class="text-body-secondary">
    <?= Yii::t('TwofaModule.base', 'Leave empty to disable this feature.') ?>
</div>

<?= $form->field($model, 'trustedNetworks')->textarea() ?>
<div class="text-body-secondary">
    <?= Yii::t('TwofaModule.base', 'List of IPs or subnets to whitelist, currently yours is {0}. Use coma separator to create a list, example: "{0}, 127.0.0.1"', [$ip]) ?>
</div>

<?= $form->field($model, 'helpText')->textarea() ?>
<div class="text-body-secondary">
    <?= Yii::t('TwofaModule.base', 'Optional text shown on the two-factor authentication screen.') ?>
</div>

<?= Button::save()->submit() ?>

<?php $form::end(); ?>

<script <?= Html::nonce() ?>>
    function twofaUpdateEnforcedMethodOptions(autoSelect) {
        const enabledDrivers = $('[name="Config[enabledDrivers][]"]:checked').get().map(el => el.value);
        const select = $('[name="Config[enforcedMethod]"]');

        select.find('option').each(function() {
            $(this).prop('disabled', enabledDrivers.length > 0
                && enabledDrivers.indexOf(this.value) === -1
                // Keep the currently selected option on page load, so an invalid stored combination is reported by validation
                && (autoSelect || !this.selected));
        });

        if (autoSelect && select.find('option:selected').prop('disabled')) {
            select.val(select.find('option:not(:disabled)').first().val());
        }
    }

    twofaUpdateEnforcedMethodOptions(false);

    $('[name="Config[enabledDrivers][]"]').on('click', function() {
        $('#disabledDriversInfo').toggle($('[name="Config[enabledDrivers][]"]:checked').length === 0);
        twofaUpdateEnforcedMethodOptions(true);
    })
</script>
