<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\helpers\Html;
use humhub\modules\twofa\drivers\BaseDriver;
use humhub\modules\twofa\models\CheckCode;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\bootstrap\Link;
use humhub\widgets\form\ActiveForm;
use humhub\widgets\SiteLogo;

/**
 * @var $model CheckCode
 * @var $driver BaseDriver
 * @var $rememberDays string
 * @var $helpText string
 */

$this->pageTitle = Yii::t('TwofaModule.base', 'Two-Factor Authentication');

?>

<div class="container" style="text-align: center;">
    <?= SiteLogo::widget(['place' => 'login']); ?><br>
    <div class="row">
        <div id="must-change-password-form" class="card content-card animated bounceIn"
             style="max-width: 400px; margin: 0 auto 20px; text-align: left;">
            <div class="card-header">
                <div class="card-title"><?= Yii::t('TwofaModule.base', '<strong>Two-factor</strong> authentication'); ?></div>
            </div>
            <div class="card-body">
                <?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>

                <?php $driver->beforeCheckCodeFormInput($form, $model); ?><br/>

                <?php // Not `inputmode="numeric"`: mailed and recovery codes have letters, case-sensitive ?>
                <?= $form->field($model, 'code')->textInput([
                    'autocomplete' => 'one-time-code',
                    'autocapitalize' => 'off',
                    'spellcheck' => 'false',
                ]); ?>

                <?php if ($rememberDays): ?>
                    <?= $form->field($model, 'rememberBrowser')->checkbox()
                        ->label(Yii::t('TwofaModule.base', 'Remember this browser for {0} days', [$rememberDays])) ?>
                <?php endif; ?>

                <?php if ($helpText !== ''): ?>
                    <div class="small text-body-secondary mb-3">
                        <?= nl2br(Html::encode($helpText)) ?>
                    </div>
                <?php endif; ?>

                <br>
                <?= Button::save(Yii::t('TwofaModule.base', 'Verify'))
                    ->id('verify-button')
                    ->submit() ?>

                <?= Link::to(Yii::t('TwofaModule.base', 'Log out'))
                    ->post(['/user/auth/logout'])
                    ->right()
                    ->cssClass('mt-2') ?>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

<script <?= Html::nonce() ?>>
    $(function () {
        // set cursor to code field
        $('#checkcode-code').focus();
    });
</script>
