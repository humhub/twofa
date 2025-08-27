<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\twofa\drivers\GoogleAuthenticatorDriver;
use humhub\modules\twofa\models\GoogleAuthenticatorUserSettings;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;

/* @var $driver GoogleAuthenticatorDriver */
/* @var $form ActiveForm */
/* @var $model GoogleAuthenticatorUserSettings */
/* @var $requirePinCode bool */
?>
<div id="twofaGoogleAuthCode" class="mb-3">
    <?= $driver->getQrCodeSecretKeyFile(['requirePinCode' => $requirePinCode]) ?>
</div>

<div id="twofaGoogleAuthPinCode"<?= $requirePinCode ? '' : ' style="display:none"' ?>>
    <?= $form->field($model, 'pinCode') ?>
    <?= $form->field($model, 'changeSecretCode')->hiddenInput()->label(false) ?>
</div>

<div class="mb-3">
    <?= Button::asLink(Yii::t('TwofaModule.base', 'Request new code'))
        ->icon('fa-qrcode')
        ->right()
        ->action('twofa.callDriverAction', Yii::$app->user->identity->createUrl('/twofa/user-settings/driver-action'))
        ->options([
            'data-driver-class' => get_class($driver),
            'data-driver-action' => 'requestCode',
            'data-driver-confirm' => 1,
            'data-container' => '#twofaGoogleAuthCode',
        ]) ?>
</div>
