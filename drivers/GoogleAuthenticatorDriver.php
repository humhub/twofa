<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\drivers;

use humhub\modules\twofa\assets\Assets;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\models\CheckCode;
use humhub\widgets\form\ActiveForm;
use PragmaRX\Google2FA\Google2FA;
use Yii;

class GoogleAuthenticatorDriver extends BaseDriver
{
    /**
     * @var string Setting name for secret code per User
     */
    public const SECRET_SETTING = 'twofaGoogleAuthSecret';
    public const SECRET_TEMP_SETTING = 'twofaGoogleAuthSecretTemp';
    public const RECOVERY_CODES_SETTING = 'twofaRecoveryCodes';

    public static function getUserSettingNames(): array
    {
        return [
            self::SECRET_SETTING,
            self::SECRET_TEMP_SETTING,
            self::RECOVERY_CODES_SETTING,
        ];
    }

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
        return class_exists(Google2FA::class);
    }

    /**
     * @inheritdoc
     */
    public function send()
    {
        if (parent::isActive() && TwofaHelper::isEnforcedUser()) {
            return true;
        }

        if (!$this->beforeSend()) {
            return false;
        }

        $secret = $this->getSecret();
        if (empty($secret)) {
            // If secret code is empty then QR code was not generated,
            // so current User cannot use this Driver for 2FA
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function beforeCheckCodeFormInput(ActiveForm $form, CheckCode $model)
    {
        if ($this->isActive() && $this->getSecret() !== null) {
            parent::beforeCheckCodeFormInput($form, $model);
            if ($this->getRecoveryCodeCount() > 0) {
                echo '<p class="text-body-secondary">'
                    . Yii::t('TwofaModule.base', 'You can also enter one of your recovery codes if you lost access to your authenticator app.')
                    . '</p>';
            }
            return;
        }

        Assets::register(Yii::$app->view);

        $this->generateTempSecretCode();
        echo $this->getQrCodeSecretKeyFile([
            'requirePinCode' => true,
            'columnLeftClass' => 'col-lg-12',
            'columnRightClass' => 'col-lg-12',
            'codeSize' => 370,
        ]);
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
                'confirm.action.header' => Yii::t('TwofaModule.base', '<strong>Request</strong> new code'),
                'confirm.action.question' => Yii::t('TwofaModule.base', 'Do you really want to request a new code?') . '<br><br>'
                    . Yii::t('TwofaModule.base', 'Please <strong>do not forget</strong> to update the code in your authenticator app! If you do not do so, you will not be able to login.'),
                'confirm.action.button' => Yii::t('TwofaModule.base', 'Request new code'),
            ],
        ]);

        $model = $this->getUserSettings();

        if ($this->getSecret() === null) {
            // Display a form to request new code when current user group is forced for this Driver
            $requirePinCode = true;
            $this->generateTempSecretCode();
        } else {
            $requirePinCode = $model->hasErrors('pinCode');
        }

        $this->renderUserSettingsFile(array_merge($params, [
            'driver' => $this,
            'model' => $model,
            'requirePinCode' => $requirePinCode,
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
        $isNewCodeAfterLogin = false;
        if ($correctCode === null) {
            $correctCode = $this->getSecret();
            if ($correctCode === null && TwofaHelper::isEnforcedUser()) {
                $correctCode = TwofaHelper::getSetting(self::SECRET_TEMP_SETTING);
                $isNewCodeAfterLogin = true;
            }
        }

        $result = $this->getGoogleAuthenticator()->verifyKey($correctCode, $verifyingCode, 1);

        if ($result && $isNewCodeAfterLogin) {
            TwofaHelper::setSetting(TwofaHelper::USER_SETTING, self::class);
            TwofaHelper::setSetting(self::SECRET_SETTING, $correctCode);
            TwofaHelper::setSetting(self::SECRET_TEMP_SETTING);
        }

        return $result;
    }

    /**
     * Get the secret of the current User
     *
     * The secret is valid only while this Driver is the stored method of the User.
     * A secret left over from switching to another method is ignored, so a new one must be set up.
     *
     * @return string|null
     */
    public function getSecret(): ?string
    {
        if (TwofaHelper::getSetting(TwofaHelper::USER_SETTING) !== self::class) {
            return null;
        }

        $secret = TwofaHelper::getSetting(self::SECRET_SETTING);

        return empty($secret) ? null : $secret;
    }

    /**
     * Get Google Authenticator
     *
     * @return Google2FA
     */
    protected function getGoogleAuthenticator()
    {
        return new Google2FA();
    }

    /**
     * Generate new secret code and store for current User
     *
     * @return string
     */
    protected function generateTempSecretCode(): string
    {
        $secret = $this->getGoogleAuthenticator()->generateSecretKey(16);

        // Save new generated secret in temporary setting before confirm by pin code:
        TwofaHelper::setSetting(self::SECRET_TEMP_SETTING, $secret);

        return $secret;
    }

    /**
     * Request code by AJAX request on user settings form
     *
     * @return string
     */
    public function actionRequestCode()
    {
        // Save new generated secret in temporary setting before confirm by pin code:
        $this->generateTempSecretCode();

        return $this->getQrCodeSecretKeyFile(['requirePinCode' => true]);
    }

    /**
     * Get file with QR code and secret key
     *
     * @param array $params
     * @return string|void
     * @throws \Throwable
     */
    public function getQrCodeSecretKeyFile($params = [])
    {
        $params = array_merge([
            'requirePinCode' => false,
            'columnLeftClass' => 'col-lg-6',
            'columnRightClass' => 'col-lg-6',
            'codeSize' => 300,
        ], $params);

        $secret = TwofaHelper::getSetting($params['requirePinCode'] ? self::SECRET_TEMP_SETTING : self::SECRET_SETTING);

        if (empty($secret)) {
            return '';
        }

        $params['qrCodeText'] = 'otpauth://totp/' . Yii::$app->request->hostName . ':' . rawurlencode(TwofaHelper::getAccountName()) . '?secret=' . $secret . '&issuer=' . Yii::$app->request->hostName;
        $params['secret'] = $secret;

        return $this->renderFile($params, ['suffix' => 'Code']);
    }

    public function getRecoveryCodeCount(): int
    {
        return count($this->getRecoveryCodeHashes());
    }

    /**
     * @return array|false
     */
    public function generateAndStoreRecoveryCodes()
    {
        $recoveryCodes = [];
        $recoveryCodeHashes = [];

        for ($i = 0; $i < 8; $i++) {
            $recoveryCode = Yii::$app->security->generateRandomString(10);
            $recoveryCodes[] = $recoveryCode;
            $recoveryCodeHashes[] = Yii::$app->security->generatePasswordHash($recoveryCode);
        }

        return TwofaHelper::setSetting(self::RECOVERY_CODES_SETTING, json_encode($recoveryCodeHashes))
            ? $recoveryCodes
            : false;
    }

    public function validateRecoveryCode(string $recoveryCode): bool
    {
        if ($recoveryCode === '') {
            return false;
        }

        foreach ($this->getRecoveryCodeHashes() as $recoveryCodeHash) {
            if (Yii::$app->security->validatePassword($recoveryCode, $recoveryCodeHash)) {
                return true;
            }
        }

        return false;
    }

    public function consumeRecoveryCode(string $recoveryCode): bool
    {
        if ($recoveryCode === '') {
            return false;
        }

        $recoveryCodeHashes = $this->getRecoveryCodeHashes();
        foreach ($recoveryCodeHashes as $index => $recoveryCodeHash) {
            if (!Yii::$app->security->validatePassword($recoveryCode, $recoveryCodeHash)) {
                continue;
            }

            unset($recoveryCodeHashes[$index]);

            return TwofaHelper::setSetting(
                self::RECOVERY_CODES_SETTING,
                empty($recoveryCodeHashes) ? null : json_encode(array_values($recoveryCodeHashes)),
            );
        }

        return false;
    }

    protected function getRecoveryCodeHashes(): array
    {
        $recoveryCodeHashes = json_decode((string) TwofaHelper::getSetting(self::RECOVERY_CODES_SETTING), true);

        if (!is_array($recoveryCodeHashes)) {
            return [];
        }

        return array_values(array_filter($recoveryCodeHashes, 'is_string'));
    }
}
