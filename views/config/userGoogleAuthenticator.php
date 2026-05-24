<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\helpers\Html;
use humhub\modules\twofa\helpers\TwofaUrl;
use humhub\modules\twofa\models\GoogleAuthenticatorUserSettings;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\bootstrap\Link;
use humhub\widgets\form\ActiveForm;
use yii\helpers\Json;

/* @var $driver humhub\modules\twofa\drivers\GoogleAuthenticatorDriver */
/* @var $form ActiveForm */
/* @var $model GoogleAuthenticatorUserSettings */
/* @var $requirePinCode bool */
?>
<div id="twofaGoogleAuthCode" class="mb-3">
    <?= $driver->getQrCodeSecretKeyFile(['requirePinCode' => $requirePinCode]) ?>
</div>

<div id="twofaGoogleAuthPinCode"<?= $requirePinCode ? '' : ' class="d-none"' ?>>
    <?= $form->field($model, 'pinCode') ?>
    <?= $form->field($model, 'changeSecretCode')->hiddenInput()->label(false) ?>
</div>

<div class="mb-3">
    <?= Link::to(Yii::t('TwofaModule.base', 'Request new code'))
        ->icon('fa-qrcode')
        ->right()
        ->action('twofa.callDriverAction', [TwofaUrl::ROUTE_USER_SETTINGS . '/driver-action'])
        ->options([
            'data-driver-class' => $driver::class,
            'data-driver-action' => 'requestCode',
            'data-driver-confirm' => 1,
            'data-container' => '#twofaGoogleAuthCode',
        ]) ?>
</div>

<?php if (!empty($model->generatedRecoveryCodes)): ?>
    <div id="twofaRecoveryCodesGenerated" class="alert alert-warning">
        <p><strong><?= Yii::t('TwofaModule.base', 'These recovery codes are shown only once.') ?></strong></p>
        <p><?= Yii::t('TwofaModule.base', 'Download them now and store them in a safe place. Each code can be used only once.') ?></p>
        <ul class="list-unstyled mb-3">
            <?php foreach ($model->generatedRecoveryCodes as $recoveryCode): ?>
                <li><code><?= Html::encode($recoveryCode) ?></code></li>
            <?php endforeach; ?>
        </ul>
        <?= Button::secondary(Yii::t('TwofaModule.base', 'Download recovery codes'))
            ->loader(false)
            ->options([
                'type' => 'button',
                'id' => 'twofaDownloadRecoveryCodes',
            ]) ?>
    </div>

    <?php
    $downloadData = Json::htmlEncode([
        'fileName' => 'recovery-codes-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) Yii::$app->user->identity->username) . '.txt',
        'content' => implode("\n", array_merge([
            Yii::t('TwofaModule.base', 'Recovery codes for {appName}', ['appName' => Yii::$app->name]),
            Yii::t('TwofaModule.base', 'User: {username}', ['username' => Yii::$app->user->identity->username]),
            '',
            Yii::t('TwofaModule.base', 'Each code can be used only once.'),
            '',
        ], $model->generatedRecoveryCodes)),
    ]);
    $this->registerJs(<<<JS
        $('#twofaDownloadRecoveryCodes').on('click', function () {
            var downloadData = $downloadData;
            var blob = new Blob([downloadData.content], {type: 'text/plain;charset=utf-8'});
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = downloadData.fileName;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(function () {
                URL.revokeObjectURL(url);
            }, 0);
        });
JS);
    ?>
<?php endif; ?>
