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
        $this->assertTrue(TwofaHelper::isVerificationPending());
        $this->assertFalse(TwofaHelper::isValidCode('test'));
    }

    public function testDisableVerifying()
    {
        $this->becomeUser('Admin');
        $this->assertTrue(TwofaHelper::enableVerifying());
        $this->assertTrue(TwofaHelper::disableVerifying(true));
        $this->assertNull(TwofaHelper::getCode());
        $this->assertNull(TwofaHelper::getSetting(TwofaHelper::CODE_EXPIRATION_SETTING));
        $this->assertFalse(TwofaHelper::isVerificationPending());
    }

    public function testVerificationPendingIsFreeOfSideEffects()
    {
        // 2FA is enforced for the Admin, but no code has been sent yet:
        // the check must report pending without delivering a code itself
        $this->becomeUser('Admin');
        $this->assertTrue(TwofaHelper::isVerificationPending());
        $this->assertNull(TwofaHelper::getCode());

        // sendCodeIfNeeded() delivers once and keeps the pending code afterwards
        $this->assertTrue(TwofaHelper::sendCodeIfNeeded());
        $this->assertNotNull(TwofaHelper::getCode());

        // users without a 2FA requirement are never pending
        $this->becomeUser('User1');
        $this->assertFalse(TwofaHelper::isVerificationPending());
    }
}
