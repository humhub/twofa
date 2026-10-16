<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace twofa;

use humhub\modules\twofa\drivers\EmailDriver;
use humhub\modules\twofa\drivers\GoogleAuthenticatorDriver;
use humhub\modules\twofa\Events;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\twofa\models\Config;
use humhub\modules\twofa\models\UserSettings;
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

    public function testLeftoverSecretIsIgnored()
    {
        $this->becomeUser('Admin');
        TwofaHelper::setSetting(GoogleAuthenticatorDriver::SECRET_SETTING, 'TESTSECRET');

        TwofaHelper::setSetting(TwofaHelper::USER_SETTING, GoogleAuthenticatorDriver::class);
        $this->assertSame('TESTSECRET', (new GoogleAuthenticatorDriver())->getSecret());

        // Secret from before switching to another method must not be used anymore
        TwofaHelper::setSetting(TwofaHelper::USER_SETTING, EmailDriver::class);
        $this->assertNull((new GoogleAuthenticatorDriver())->getSecret());
    }

    public function testSwitchingAwayFromTotpDeletesSecret()
    {
        $this->becomeUser('Admin');
        TwofaHelper::setSetting(TwofaHelper::USER_SETTING, GoogleAuthenticatorDriver::class);
        TwofaHelper::setSetting(GoogleAuthenticatorDriver::SECRET_SETTING, 'TESTSECRET');
        TwofaHelper::setSetting(GoogleAuthenticatorDriver::RECOVERY_CODES_SETTING, 'TESTCODES');

        $userSettings = new UserSettings();
        $userSettings->driver = EmailDriver::class;
        $this->assertTrue($userSettings->save());

        $this->assertSame(EmailDriver::class, TwofaHelper::getSetting(TwofaHelper::USER_SETTING));
        $this->assertNull(TwofaHelper::getSetting(GoogleAuthenticatorDriver::SECRET_SETTING));
        $this->assertNull(TwofaHelper::getSetting(GoogleAuthenticatorDriver::RECOVERY_CODES_SETTING));
    }

    public function testTempSecretIsNotRegeneratedOnRepeatedRequests()
    {
        Events::registerAutoloader();
        $this->becomeUser('Admin');
        $driver = new GoogleAuthenticatorDriver();

        $this->assertNull(TwofaHelper::getSetting(GoogleAuthenticatorDriver::SECRET_TEMP_SETTING));

        $secret = $driver->getTempSecretCode();
        $this->assertNotEmpty($secret);
        $this->assertSame($secret, TwofaHelper::getSetting(GoogleAuthenticatorDriver::SECRET_TEMP_SETTING));

        // Reloading the setup page (or any other repeated request) must keep the secret of the displayed QR code
        $this->assertSame($secret, $driver->getTempSecretCode());
        $this->assertSame($secret, (new GoogleAuthenticatorDriver())->getTempSecretCode());
    }

    public function testExistingTempSecretIsReused()
    {
        $this->becomeUser('Admin');
        TwofaHelper::setSetting(GoogleAuthenticatorDriver::SECRET_TEMP_SETTING, 'TESTTEMPSECRET');

        $this->assertSame('TESTTEMPSECRET', (new GoogleAuthenticatorDriver())->getTempSecretCode());
        $this->assertSame('TESTTEMPSECRET', TwofaHelper::getSetting(GoogleAuthenticatorDriver::SECRET_TEMP_SETTING));
    }
}
