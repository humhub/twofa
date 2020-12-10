<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/* @var $driver GoogleAuthenticatorDriver */

use humhub\modules\twofa\drivers\GoogleAuthenticatorDriver;
use humhub\widgets\Button;

?>

<div class="form-group">
<?= Button::info(Yii::t('TwofaModule.config', 'Request QR code'))
    ->icon('fa-qrcode')
    ->action('twofa.callDriverAction', Yii::$app->user->identity->createUrl('/twofa/user-settings/driver-action'))
    ->options([
        'data-driver-class' => get_class($driver),
        'data-driver-action' => 'requestCode',
        'data-container' => '#twofaGoogleAuthCode',
    ]) ?>
</div>

<div id="twofaGoogleAuthCode" class="form-group">
    <?= $driver->getQrCodeSecretKeyFile() ?>
</div>