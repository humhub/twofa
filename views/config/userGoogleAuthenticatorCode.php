<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\libs\Html;
use humhub\modules\twofa\helpers\TwofaHelper;

/* @var $qrCodeText string */
/* @var $secret string */
/* @var $requirePinCode boolean */
/* @var $columnLeftClass string */
/* @var $columnRightClass string */
/* @var $codeSize integer */
?>
<p><?= Yii::t('TwofaModule.base', 'Install an application that implements a time-based one-time password (TOTP) algorithm, such as {googleAuthenticatorLink}, and use it to scan the QR code shown below.',
        ['{googleAuthenticatorLink}' => '<a href="https://support.google.com/accounts/answer/1066447" target="_blank">' . Yii::t('TwofaModule.base', 'Google Authenticator'). '</a>']); ?></p>

<div class="row">
    <div class="<?= $columnLeftClass ?>">
        <div class="form-group">
            <div id="twofa-google-auth-qrcode"></div>
            <div class="help-block"></div>
        </div>
    </div>
    <div class="<?= $columnRightClass ?>">
        <div class="alert alert-default">
            <p><strong><?= Yii::t('TwofaModule.base', 'Can\'t scan the code?'); ?></strong></p>
            <br/>
            <p><?= Yii::t('TwofaModule.base', 'To connect the app manually, provide the following details to the TOTP app (e.g. Google Authenticator).'); ?></p>
            <br/>
            <p>
                <?= Yii::t('TwofaModule.base', 'Account:'); ?> <?= TwofaHelper::getAccountName() ?><br>
                <?= Yii::t('TwofaModule.base', 'Secret:'); ?> <?= $secret ?><br>
                <?= Yii::t('TwofaModule.base', 'Time based: Yes'); ?><br>
            </p>
        </div>
    </div>
</div>

<script <?= Html::nonce() ?>>
$(document).ready(function(){
    if ($('#twofa-google-auth-qrcode').html() === '') {
        new QRCode('twofa-google-auth-qrcode', {
            text: '<?= $qrCodeText ?>',
            width: <?= $codeSize ?>,
            height: <?= $codeSize ?>,
            correctLevel: QRCode.CorrectLevel.L
        });
    }
<?php if ($requirePinCode) : ?>
    $('#twofaGoogleAuthPinCode').show();
    $('input[name="GoogleAuthenticatorUserSettings[changeSecretCode]"]').val(1);
<?php endif; ?>
})
</script>
