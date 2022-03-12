<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/* @var $driver GoogleAuthenticatorDriver */
/* @var $form ActiveForm */
/* @var $model GoogleAuthenticatorUserSettings */
/* @var $requestPinCode bool */

use humhub\modules\twofa\drivers\GoogleAuthenticatorDriver;
use humhub\modules\twofa\models\GoogleAuthenticatorUserSettings;
use humhub\widgets\Button;
use yii\bootstrap\ActiveForm;

?>
<div id="twofaGoogleAuthCode" class="form-group">
    <?= $driver->getQrCodeSecretKeyFile($requestPinCode) ?>
</div>

<div id="twofaGoogleAuthPinCode"<?= $requestPinCode ? '' : ' style="display:none"' ?>>
    <?= $form->field($model, 'pinCode') ?>
    <?= $form->field($model, 'changeSecretCode')->hiddenInput()->label(false) ?>
</div>

<div class="form-group">
    <?= Button::asLink(Yii::t('TwofaModule.config', 'Request new code'))
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
