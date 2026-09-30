<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\models;

use humhub\modules\twofa\drivers\GoogleAuthenticatorDriver;
use humhub\modules\twofa\helpers\TwofaHelper;
use Yii;
use yii\base\Model;

/**
 * User Settings form for the Driver "GoogleAuthenticator"
 */
class GoogleAuthenticatorUserSettings extends Model
{
    /**
     * @var string Pin code
     */
    public $pinCode;

    /**
     * @var bool Change secret code?
     */
    public $changeSecretCode;

    /**
     * @var string[] Recovery codes shown only in the current response
     */
    public $generatedRecoveryCodes = [];

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            ['pinCode', 'string'],
            ['pinCode', 'verifyPinCode', 'when' => static fn(self $model) => $model->changeSecretCode],
            ['changeSecretCode', 'boolean'],
        ];

        $postParams = Yii::$app->request->post('GoogleAuthenticatorUserSettings');
        if (!empty($postParams['changeSecretCode'])) {
            array_unshift($rules, ['pinCode', 'required']);
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pinCode' => Yii::t('TwofaModule.base', 'Pin code'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function verifyPinCode($attribute, $params)
    {
        $driver = new GoogleAuthenticatorDriver();
        if (!$driver->checkCode($this->pinCode, TwofaHelper::getSetting($driver::SECRET_TEMP_SETTING))) {
            $this->addError($attribute, Yii::t('TwofaModule.base', 'Code is not valid!'));
        }
    }

    /**
     * Save driver settings
     * @return bool
     */
    public function save()
    {
        $generateInitialRecoveryCodes = $this->changeSecretCode
            && (new GoogleAuthenticatorDriver())->getSecret() === null;

        return $this->updateSecretCode()
            && $this->updateRecoveryCodes($generateInitialRecoveryCodes);
    }

    /**
     * Update secret code
     * @return bool
     */
    public function updateSecretCode()
    {
        if (!$this->changeSecretCode) {
            return true;
        }

        $newSecret = TwofaHelper::getSetting(GoogleAuthenticatorDriver::SECRET_TEMP_SETTING);

        if (empty($newSecret)) {
            return false;
        }

        // Save new secret code
        if (TwofaHelper::setSetting(GoogleAuthenticatorDriver::SECRET_SETTING, $newSecret)) {
            // Delete temp data
            $this->pinCode = '';
            $this->changeSecretCode = false;
            return TwofaHelper::setSetting(GoogleAuthenticatorDriver::SECRET_TEMP_SETTING);
        }

        return false;
    }

    protected function updateRecoveryCodes(bool $generateInitialRecoveryCodes): bool
    {
        $this->generatedRecoveryCodes = [];

        if (empty(TwofaHelper::getSetting(GoogleAuthenticatorDriver::SECRET_SETTING)) || !$generateInitialRecoveryCodes) {
            return true;
        }

        $driver = new GoogleAuthenticatorDriver();
        $generatedRecoveryCodes = $driver->generateAndStoreRecoveryCodes();
        if ($generatedRecoveryCodes === false) {
            return false;
        }

        $this->generatedRecoveryCodes = $generatedRecoveryCodes;

        return true;
    }

}
