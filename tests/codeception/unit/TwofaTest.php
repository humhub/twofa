<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace twofa;

use humhub\modules\twofa\helpers\TwofaHelper;
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
        $this->assertTrue(TwofaHelper::disableVerifying());
        $this->assertFalse(TwofaHelper::isVerifyingRequired());
    }
}