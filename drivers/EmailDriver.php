<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\drivers;

use humhub\modules\user\models\User;
use Yii;
use yii\mail\BaseMessage;
use yii\validators\EmailValidator;

class EmailDriver extends BaseDriver
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->name = Yii::t('TwofaModule.base', 'Email');
        $this->info = Yii::t('TwofaModule.base', 'A confirmation code hast just been sent to your email address. Please enter the code from the email in order to proceed.');
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        return parent::isActive() && $this->isValidUserEmail();
    }

    public function send()
    {
        if (!$this->beforeSend()) {
            return false;
        }

        /** @var User $user */
        $user = Yii::$app->user->getIdentity();

        Yii::$app->i18n->setUserLocale($user);

        /** @var BaseMessage $mail */
        $mail = Yii::$app->mailer->compose([
            'html' => '@twofa/views/mails/VerifyingCode',
            'text' => '@twofa/views/mails/plaintext/VerifyingCode',
        ], [
            'user' => $user,
            'code' => $this->getCode(),
        ]);
        $mail->setTo($user->email);
        $mail->setSubject(Yii::t('TwofaModule.base', 'Your login verification code'));

        return $mail->send();
    }

    protected function isValidUserEmail(): bool
    {
        /* @var User $user */
        $user = Yii::$app->user->getIdentity();

        return !empty($user->email) && (new EmailValidator())->validate($user->email);
    }
}
