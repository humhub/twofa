<?php

/**
 * @var \humhub\components\View $this
 * @var string $tab
 */

use humhub\helpers\ControllerHelper;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\admin\permissions\ManageUsers;
use humhub\widgets\bootstrap\Tabs;

$tabs = [];

if (Yii::$app->user->can(ManageModules::class)) {
    $tabs[] = [
        'label' => Yii::t('TwofaModule.base', 'General'),
        'url' => ['/twofa/admin/index'],
        'active' => ControllerHelper::isActivePath('twofa', 'admin', 'index'),
    ];
}

if (Yii::$app->user->can(ManageUsers::class)) {
    $tabs[] = [
        'label' => Yii::t('TwofaModule.base', 'Users'),
        'url' => ['/twofa/admin/users'],
        'active' => ControllerHelper::isActivePath('twofa', 'admin', 'users'),
    ];
}

?>
<div class="card content-card">
    <div class="card-header">
        <div class="card-title"><?= Yii::t('TwofaModule.base', '<strong>Two-Factor Authentication</strong> administration') ?></div>
    </div>
    <div class="card-body">
        <?= Tabs::widget([
            'renderTabContent' => false,
            'options' => [
                'style' => ['margin-bottom' => 0],
            ],
            'items' => $tabs,
        ]) ?>
        <br/>
        <?= $tab ?>
    </div>
</div>
