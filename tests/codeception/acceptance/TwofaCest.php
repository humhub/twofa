<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\tests\codeception\acceptance;

use tests\codeception\_pages\LoginPage;
use twofa\AcceptanceTester;

class TwofaCest
{
    public function testTwoFactorAuthentication(AcceptanceTester $I)
    {
        $I->wantTo('Ensure admin user login with 2FA');
        $loginPage = LoginPage::openBy($I);
        $I->amGoingTo('try to login with admin credentials');
        $loginPage->login('Admin', 'test');
        $I->expectTo('See Two Factor Auth');
        $I->waitForText('Two-factor authentication');
    }

    public function testNoTwoFactorAuthentication(AcceptanceTester $I)
    {
        $I->wantTo('Ensure regular user login without 2FA');
        $loginPage = LoginPage::openBy($I);
        $I->amGoingTo('try to login with non-admin credentials');
        $loginPage->login('User1', '123qwe');
        $I->expectTo('see dashboard');
        $I->waitForText('User 2 Space 2 Post Public');
    }
}
