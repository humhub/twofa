<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\models;

use humhub\modules\twofa\helpers\TwofaHelper;
use Yii;
use yii\base\Model;

/**
 * This is the model class for form to check code of Two-Factor Authentication
 */
class CheckCode extends Model
{
    public $code;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['code', 'required'],
            ['code', 'string'],
            ['code', 'verifyCode'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'code' => Yii::t('TwofaModule.base', 'Code'),
        ];
    }

    /**
     * Verify code
     *
     * @param $attribute
     * @param $params
     */
    public function verifyCode($attribute, $params)
    {
        if (!TwofaHelper::isValidCode($this->code)) {
            $this->addError($attribute, Yii::t('TwofaModule.base', 'Verifying code is not valid!'));
        }
    }

}
