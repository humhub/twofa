<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\twofa\drivers\BaseDriver;
use humhub\modules\twofa\models\CheckCode;
use humhub\widgets\Button;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use humhub\widgets\SiteLogo;

/* @var $model CheckCode */
/* @var $driver BaseDriver */

$this->pageTitle = Yii::t('TwofaModule.base', 'Two-Factor Authentication');
?>
<div class="container" style="text-align: center;">
    <?= SiteLogo::widget(['place' => 'login']); ?>
    <br>

    <div class="row">
        <div id="must-change-password-form" class="panel panel-default animated bounceIn"
             style="max-width: 400px; margin: 0 auto 20px; text-align: left;">
            <div class="panel-heading"><?= Yii::t('TwofaModule.base', '<strong>Two-factor</strong> authentication'); ?></div>
            <div class="panel-body">

                <?php $form = ActiveForm::begin(); ?>

                <?php $driver->beforeCheckCodeFormInput($form, $model); ?>

                <br />

                <?= $form->field($model, 'code')->textInput(); ?>

                <br />

                <?= Button::danger(Yii::t('TwofaModule.base', 'Log out'))->link(Url::toRoute('/user/auth/logout'))->right()->pjax(false) ?>

                <?= Html::submitButton(Yii::t('TwofaModule.base', 'Verify'), ['class' => 'btn btn-primary', 'data-ui-loader' => ""]); ?>

                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>

<script <?= \humhub\libs\Html::nonce() ?>>
    $(function () {
        // set cursor to code field
        $('#checkcode-code').focus();
    });
</script>
