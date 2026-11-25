<?php

/**
 * @var array $driverValues
 * @var \yii\data\ActiveDataProvider $userDataProvider
 * @var array $driverOptions
 */

use humhub\helpers\Html;
use humhub\modules\user\models\User;
use humhub\modules\user\grid\DisplayNameColumn;
use humhub\modules\user\grid\ImageColumn;
use humhub\widgets\GridView;
use humhub\widgets\bootstrap\Alert;
use humhub\widgets\bootstrap\Button;
use yii\grid\DataColumn;

?>

<h4 class="card-title mt-0"><?= Yii::t('TwofaModule.base', 'Enabled users') ?></h4>
<div class="text-body-secondary mb-3">
    <?= Yii::t('TwofaModule.base', 'This list shows users who currently have two-factor authentication configured.') ?>
</div>

<?php if ($userDataProvider->getTotalCount() === 0) : ?>
    <?= Alert::info(Yii::t('TwofaModule.base', 'No users with configured two-factor authentication found.')) ?>
<?php else : ?>
    <?= GridView::widget([
        'dataProvider' => $userDataProvider,
        'tableOptions' => ['class' => 'table table-hover'],
        'columns' => [
            ['class' => ImageColumn::class],
            [
                'class' => DisplayNameColumn::class,
                'label' => Yii::t('TwofaModule.base', 'User'),
            ],
            [
                'class' => DataColumn::class,
                'attribute' => 'twofaDriver',
                'label' => Yii::t('TwofaModule.base', 'Method'),
                'value' => static fn(User $user) => $driverOptions[$driverValues[$user->contentcontainer_id] ?? null] ?? null,
            ],
            'last_login:datetime',
            [
                'class' => DataColumn::class,
                'label' => Yii::t('TwofaModule.base', 'Actions'),
                'format' => 'raw',
                'contentOptions' => ['class' => 'text-end'],
                'value' => static function (User $user) {
                    $button = Button::danger(Yii::t('TwofaModule.base', 'Reset'))
                        ->outline()
                        ->sm()
                        ->link(['/twofa/admin/reset-user', 'id' => $user->id, 'returnUrl' => Yii::$app->request->url], false)
                        ->confirm(
                            Yii::t('TwofaModule.base', '<strong>Confirm</strong> two-factor authentication reset'),
                            Yii::t('TwofaModule.base', 'This will remove the current two-factor authentication setup for this user. They will need to configure it again on the next login.'),
                            Yii::t('TwofaModule.base', 'Reset two-factor authentication'),
                        );

                    $button->options['data-method'] = 'POST';

                    return $button;
                },
            ],
        ],
    ]) ?>
<?php endif; ?>
