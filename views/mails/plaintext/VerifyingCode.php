<?php

use humhub\modules\user\models\User;
use yii\helpers\Html;

/** @var string $code */
/** @var User $user */

?>
<?= Yii::t('TwofaModule.base', 'Hello {displayName}!', ['{displayName}' => Html::encode($user->displayName)]) ?>


<?= Yii::t('TwofaModule.base', 'In this email you will receive the verification code to continue the current login. This is necessary because your account is additionally secured by two-factor authentication.'); ?>


<?= Yii::t('TwofaModule.base', 'Date and time:'); ?> <?= Yii::$app->formatter->asDatetime(time()); ?>

<?= Yii::t('TwofaModule.base', 'Code:'); ?> <?= $code ?>

