<?php

namespace humhub\modules\twofa\events;

use yii\base\Event;

/**
 * This event is triggered before the 2FA check is performed.
 * It allows for custom logic to be executed before the verification process.
 */
class BeforeTwoFaCheck extends Event
{
    public $name = 'beforeTwoFaCheck';
}
