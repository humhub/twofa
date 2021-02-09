<?php

use humhub\modules\user\models\User;
use yii\helpers\Html;

/** @var string $code */
/** @var User $user */

?>
<?= Yii::t('TwofaModule.base', 'Hello {displayName}!', ['{displayName}' => Html::encode($user->displayName)]) ?>


<?= Yii::t('TwofaModule.base', 'Your account is secured by a two-factor authentication system. Please use the following code to proceed.'); ?>


<?= Yii::t('TwofaModule.base', 'Date and time:'); ?> <?= Yii::$app->formatter->asDatetime(time()); ?>

<?= Yii::t('TwofaModule.base', 'Code:'); ?> <?= $code ?>

