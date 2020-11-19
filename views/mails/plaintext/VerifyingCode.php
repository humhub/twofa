<?php

use humhub\modules\user\models\User;
use yii\helpers\Html;

/** @var string $code */
/** @var User $user */

?>
<?= strip_tags(Yii::t('TwofaModule.base', 'Hello {displayName}', ['{displayName}' => Html::encode($user->displayName)])) ?>


<?= strip_tags(Yii::t('TwofaModule.base', 'Your verifying code: {verifyingCode}', ['{verifyingCode}' => $code])) ?>