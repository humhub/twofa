<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace twofa;

use humhub\modules\twofa\drivers\EmailDriver;
use humhub\modules\twofa\drivers\GoogleAuthenticatorDriver;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\models\Config;
use tests\codeception\_support\HumHubDbTestCase;

class TwofaTest extends HumHubDbTestCase
{
    public function testEnforcedUsers()
    {
        $this->becomeUser('Admin');
        $this->assertTrue(TwofaHelper::isEnforcedUser());

        $this->becomeUser('User1');
        $this->assertFalse(TwofaHelper::isEnforcedUser());
    }

    public function testSendCheckCode()
    {
        $this->becomeUser('Admin');
        $this->assertTrue(TwofaHelper::enableVerifying());

        $this->becomeUser('User1');
        $this->assertFalse(TwofaHelper::enableVerifying());
    }

    public function testVerifyCode()
    {
        $this->becomeUser('Admin');
        $this->assertTrue(TwofaHelper::enableVerifying());
        $this->assertTrue(TwofaHelper::isVerifyingRequired());
        $this->assertFalse(TwofaHelper::isValidCode('test'));
    }

    public function testDisableVerifying()
    {
        $this->becomeUser('Admin');
        $this->assertTrue(TwofaHelper::enableVerifying());
        $this->assertTrue(TwofaHelper::disableVerifying(true));
        $this->assertNull(TwofaHelper::getCode());
        $this->assertNull(TwofaHelper::getSetting(TwofaHelper::CODE_EXPIRATION_SETTING));
        $this->assertFalse(TwofaHelper::isVerifyingRequired());
    }

    public function testEnforcedMethodMustBeEnabled()
    {
        $this->becomeUser('Admin');
        $config = new Config();

        $config->enabledDrivers = [GoogleAuthenticatorDriver::class];
        $config->enforcedMethod = EmailDriver::class;
        $this->assertFalse($config->validate(['enforcedMethod']));

        $config->enforcedMethod = GoogleAuthenticatorDriver::class;
        $this->assertTrue($config->validate(['enforcedMethod']));

        // No enabled drivers: enforced method is still allowed as fallback for enforced groups
        $config->enabledDrivers = [];
        $config->enforcedMethod = EmailDriver::class;
        $this->assertTrue($config->validate(['enforcedMethod']));
    }
}
