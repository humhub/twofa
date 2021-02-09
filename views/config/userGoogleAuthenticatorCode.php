<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/* @var $qrCodeUrl string */
/* @var $secret string */
/* @var $requirePinCode boolean */

use humhub\libs\Html;
use humhub\modules\twofa\helpers\TwofaHelper;

?>
<p><?= Yii::t('TwofaModule.config', 'Install an application that implements a time-based one-time password (TOTP) algorithm, such as <a{docLinkAttrs}>Google Authenticator</a>, and use it to scan the QR code shown below.',
        ['{docLinkAttrs}' => ' href="https://support.google.com/accounts/answer/1066447" target="_blank"']); ?></p>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <?= Html::img($qrCodeUrl, ['alt' => Yii::t('TwofaModule.config', 'QR Code')]) ?>
            <div class="help-block"></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="alert alert-default">
            <p><strong><?= Yii::t('TwofaModule.config', 'Can\'t scan the code?'); ?></strong></p>
            <br/>
            <p><?= Yii::t('TwofaModule.config', 'To connect the app manually, provide the following details to the TOTP app (e.g. Google Authenticator).'); ?></p>
            <br/>
            <p>
                <?= Yii::t('TwofaModule.config', 'Account:'); ?> <?= TwofaHelper::getAccountName() ?><br>
                <?= Yii::t('TwofaModule.config', 'Secret:'); ?> <?= $secret ?><br>
                <?= Yii::t('TwofaModule.config', 'Time based: Yes'); ?><br>
            </p>
        </div>
        <br/>

    </div>

</div>

<?php if ($requirePinCode) : ?>
<script>
$(document).ready(function(){
    $('#twofaGoogleAuthPinCode').show();
    $('input[name="GoogleAuthenticatorUserSettings[changeSecretCode]"]').val(1);
})
</script>
<?php endif; ?>