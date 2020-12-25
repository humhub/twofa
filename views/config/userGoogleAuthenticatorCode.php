<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/* @var $qrCodeUrl string */
/* @var $secret string */

use humhub\libs\Html;
?>

<div class="form-group">
    <?= Html::label(Yii::t('TwofaModule.config', 'Scan this QR code in Google Authenticator app:'), '', ['class' => 'control-label']) ?><br>
    <?= Html::img($qrCodeUrl, ['alt' => Yii::t('TwofaModule.config', 'Google Authenticator QR Code')]) ?>
    <div class="help-block">
        <?= Yii::t('TwofaModule.config', 'Please read this <a{docLinkAttrs}>documentaion</a> to know how to download, set up and use the Google Authenticator app.',
            ['{docLinkAttrs}' => ' href="https://support.google.com/accounts/answer/1066447" target="_blank"']) ?>
    </div>
</div>

<div class="form-group">
    <?= Html::label(Yii::t('TwofaModule.config', 'Or use this secret key to add account manually:'), '', ['class' => 'control-label']) ?><br>
    <?= Html::textInput('secret', $secret, ['class' => 'form-control', 'disabled' => 'disabled']) ?>
    <div class="help-block"><?= Yii::t('TwofaModule.config', 'Use your username {username} for account name in Google Authenticator app.', [
            'username' => '<code>' . Yii::$app->user->getIdentity()->username . '</code>'
        ]) ?></div>
</div>