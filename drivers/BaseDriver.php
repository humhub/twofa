<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\drivers;

use Yii;

abstract class BaseDriver
{
    /**
     * @var string Last generated code
     */
    private $code;

    /**
     * Return driver name
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Send new generated code
     *
     * @return bool true on success sending
     */
    abstract public function send();

    /**
     * Get code, Generate random code on first call
     *
     * @return string
     */
    public function getCode()
    {
        if (!isset($this->code)) {
            $this->code = Yii::$app->security->generateRandomString(6);
        }

        return $this->code;
    }
}