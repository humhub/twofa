<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa;

use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\helpers\TwofaUrl;
use Yii;

class Events
{
    /**
     * Check if current User has been verified by 2fa if it is required
     *
     * @param $event
     * @return false|\yii\console\Response|\yii\web\Response
     */
    public static function onBeforeAction($event)
    {
        if (Yii::$app->request->isAjax) {
            // TODO: maybe it should be restricted better, but we don't need to call this for PollController from live module indeed
            return false;
        }

        if (TwofaHelper::isVerifyingRequired() &&
            !Yii::$app->getModule('twofa')->isTwofaCheckUrl()) {
            return Yii::$app->getResponse()->redirect(TwofaUrl::ROUTE_CHECK);
        }
    }

    /**
     * Set flag after login to user who need 2fa
     *
     * @param $event
     * @throws \Throwable
     */
    public static function onAfterLogin($event)
    {
        TwofaHelper::enableVerifying();
    }
}
