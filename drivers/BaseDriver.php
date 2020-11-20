<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\drivers;

use humhub\modules\twofa\helpers\TwofaHelper;
use Yii;
use yii\base\BaseObject;

abstract class BaseDriver extends BaseObject
{
    /**
     * @var string Last generated code
     */
    private $code;

    /**
     * @var string Driver name
     */
    public $name;

    /**
     * @var string Info for user to know where to find a verifying code
     */
    public $info;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->info = Yii::t('TwofaModule.base', 'Please enter your verifying code.');
    }

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
            $this->code = Yii::$app->security->generateRandomString(TwofaHelper::CODE_LENGTH);
        }

        return $this->code;
    }
}