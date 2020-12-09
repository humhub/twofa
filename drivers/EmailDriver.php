<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\drivers;

use yii\mail\BaseMessage;
use Yii;

class EmailDriver extends BaseDriver
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->name = Yii::t('TwofaModule.base', 'E-mail');
        $this->info = Yii::t('TwofaModule.base', 'Please find a verifying code sent to your email-address.');
    }

    /**
     * @inheritdoc
     */
    public function send()
    {
        if (!$this->beforeSend()) {
            return false;
        }

        /** @var BaseMessage $mail */
        $mail = Yii::$app->mailer->compose([
            'html' => '@twofa/views/mails/VerifyingCode',
            'text' => '@twofa/views/mails/plaintext/VerifyingCode'
        ], [
            'user' => $user,
            'code' => $this->getCode(),
        ]);
        $mail->setTo($user->email);
        $mail->setSubject(Yii::t('TwofaModule.base', 'Two-Factor Authentication'));

        return $mail->send();
    }
}