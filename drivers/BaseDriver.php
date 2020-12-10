<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\drivers;

use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\models\CheckCode;
use humhub\modules\twofa\models\UserSettings;
use humhub\modules\twofa\Module;
use humhub\modules\user\models\User;
use Yii;
use yii\base\BaseObject;
use yii\bootstrap\ActiveForm;

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
     * Check if this Driver is installed successfully and can be used properly
     *
     * @return bool
     */
    public function isInstalled()
    {
        return true;
    }

    /**
     * Action before send/generate code
     *
     * @return bool
     */
    protected function beforeSend()
    {
        /** @var User $user */
        $user = Yii::$app->user->getIdentity();
        if (!$user) {
            return false;
        }

        // Switch to users language - if specified
        if ($user->language !== '') {
            Yii::$app->language = $user->language;
        }

        return true;
    }

    /**
     * Send and/or Generate a new code
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
            /** @var Module $module */
            $module = Yii::$app->getModule('twofa');
            $this->code = Yii::$app->security->generateRandomString($module->getCodeLength());
        }

        return $this->code;
    }

    /**
     * Display additional elements before input of checking code on 2FA form
     *
     * @param ActiveForm $form
     * @param CheckCode $model
     */
    public function beforeCheckCodeFormInput(ActiveForm $form, CheckCode $model)
    {
        echo '<p>' . $this->info . '</p>';
    }

    /**
     * Render additional user settings
     *
     * @param ActiveForm $form
     * @param UserSettings $model
     */
    public function renderUserSettings(ActiveForm $form, UserSettings $model)
    {
    }

    /**
     * Render additional user settings
     *
     * @param array
     */
    public function renderUserSettingsFile($params = [])
    {
        echo '<div data-driver-fields="' . static::class . '"'
            . ( TwofaHelper::getDriverSetting() == static::class ? '' : ' style="display:none"') . '>';

        echo $this->renderFile($params);

        echo '</div>';
    }

    /**
     * Get a rendered custom driver file
     *
     * @param array Params to pass to template
     * @param array Options to init the rendered file
     * @return string The rendered result
     */
    public function renderFile($params = [], $options = [])
    {
        $options = array_merge([
            'prefix' => 'config/user',
            'suffix' => '',
        ], $options );

        $driverName = substr(static::class, strrpos(static::class, '\\') + 1, -6);
        $driverFieldsFileName = '@twofa/views/' . $options['prefix'] . $driverName . $options['suffix'] . '.php';
        if (!file_exists(Yii::getAlias($driverFieldsFileName))) {
            // Skip if this Driver has no file for additional fields:
            return;
        }

        // Render a form file with additional fields for this Driver:
        return Yii::$app->getView()->renderFile($driverFieldsFileName, $params);
    }

    /**
     * Check code
     *
     * @param string Verifying code
     * @return bool
     */
    public function checkCode($code)
    {
        return TwofaHelper::hashCode($code) === TwofaHelper::getCode();
    }

    /**
     * Call driver action
     * Used by AJAX request
     *
     * @param string Action name
     * @param array Params
     * @return mixed
     */
    public function callAction($action, $params = [])
    {
        $methodName = 'action'.ucfirst($action);
        if (empty($action) || !method_exists($this, $methodName)) {
            return;
        }

        return $this->$methodName($params);
    }
}