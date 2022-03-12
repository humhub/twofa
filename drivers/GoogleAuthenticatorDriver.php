<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\drivers;

use humhub\modules\twofa\helpers\TwofaHelper;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Yii;

class GoogleAuthenticatorDriver extends BaseDriver
{
    /**
     * @var string Setting name for secret code per User
     */
    const SECRET_SETTING = 'twofaGoogleAuthSecret';
    const SECRET_TEMP_SETTING = 'twofaGoogleAuthSecretTemp';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->name = Yii::t('TwofaModule.base', 'Time-based one-time passwords (e.g. Google Authenticator)');
        $this->info = Yii::t('TwofaModule.base', 'Open the two-factor authentication app on your device to view your authentication code and verify your identity.');
    }

    /**
     * Check if this Driver is installed successfully and can be used properly
     *
     * @return bool
     */
    public function isInstalled()
    {
        // Google Authenticator library must be installed for work of this Driver:
        return class_exists('\Sonata\GoogleAuthenticator\GoogleAuthenticator') &&
            class_exists('\Sonata\GoogleAuthenticator\GoogleQrUrl');
    }

    /**
     * @inheritdoc
     */
    public function send()
    {
        if (!$this->beforeSend()) {
            return false;
        }

        $secret = TwofaHelper::getSetting(self::SECRET_SETTING);
        if (empty($secret))
        {   // If secret code is empty then QR code was not generated,
            // so current User cannot use this Driver for 2FA
            return false;
        }

        return true;
    }

    /**
     * Render additional user settings
     *
     * @param array Params
     */
    public function renderUserSettings($params)
    {
        Yii::$app->getView()->registerJsConfig('twofa', [
            'text' => [
                'confirm.action.header' => Yii::t('TwofaModule.config', '<strong>Request</strong> new code'),
                'confirm.action.question' => Yii::t('TwofaModule.config', 'Do you really want to request a new code?') . '<br><br>'
                    . Yii::t('TwofaModule.config', 'Please <strong>do not forget</strong> to update the code in your authenticator app! If you do not do so, you will not be able to login.'),
                'confirm.action.button' => Yii::t('TwofaModule.config', 'Request new code'),
            ]
        ]);

        $model = $this->getUserSettings();

        $this->renderUserSettingsFile(array_merge($params, [
            'driver' => $this,
            'model' => $model,
            'requestPinCode' => $model->hasErrors('pinCode')
                || (TwofaHelper::getSetting(GoogleAuthenticatorDriver::SECRET_SETTING) === null),
        ]));
    }

    /**
     * Check code
     *
     * @param string Verifying code
     * @param string Correct code
     * @return bool
     */
    public function checkCode($verifyingCode, $correctCode = null)
    {
        if ($correctCode === null) {
            $correctCode = TwofaHelper::getSetting(self::SECRET_SETTING);
        }

        return $this->getGoogleAuthenticator()->checkCode($correctCode, $verifyingCode);
    }

    /**
     * Get Google Authenticator
     *
     * @return GoogleAuthenticator
     */
    protected function getGoogleAuthenticator()
    {
        // NOTE: Don't try to pass different code length, only default $passCodeLength = 6 can be used here!
        return new GoogleAuthenticator(/* Yii::$app->getModule('twofa')->getCodeLength() */);
    }

    /**
     * Request code by AJAX request on user settings form
     *
     * @param array Params
     * @return string
     */
    public function actionRequestCode($params)
    {
        // Generate new secret code and store for current User:
        $secret = $this->getGoogleAuthenticator()->generateSecret();

        // Save new generated secret in temporary setting before confirm by pin code:
        TwofaHelper::setSetting(self::SECRET_TEMP_SETTING, $secret);

        return $this->getQrCodeSecretKeyFile(true);
    }

    /**
     * Get file with QR code and secret key
     *
     * @param boolean Require pin code?
     * @return string|void
     * @throws \Throwable
     */
    public function getQrCodeSecretKeyFile($requirePinCode = false)
    {
        $secret = TwofaHelper::getSetting($requirePinCode ? self::SECRET_TEMP_SETTING : self::SECRET_SETTING);

        if (empty($secret)) {
            return '';
        }

        return $this->renderFile([
            'qrCodeText' => 'otpauth://totp/' . Yii::$app->request->hostName . ':' . rawurlencode(TwofaHelper::getAccountName()) . '?secret=' . $secret . '&issuer=' . Yii::$app->request->hostName,
            'secret' => $secret,
            'requirePinCode' => $requirePinCode,
        ], ['suffix' => 'Code']);
    }
}
