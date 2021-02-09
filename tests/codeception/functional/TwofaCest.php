<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace twofa\functional;

use tests\codeception\_pages\LoginPage;
use tests\codeception\_pages\TwofaAuthPage;
use twofa\FunctionalTester;

class TwofaCest
{

    public function testCheckWrongVerifyingCode(FunctionalTester $I)
    {
        $I->wantTo('check wrong verifying code');
        $loginPage = LoginPage::openBy($I);
        $I->amGoingTo('try to login with admin credentials');
        $loginPage->login('Admin', 'test');
        $I->expectTo('See Two Factor Auth');
        $I->see('Two-factor authentication');

        $twofaAuthPage = TwofaAuthPage::openBy($I);
        $twofaAuthPage->verify('test code');
        $I->expectTo('see wrong verifying code');
        $I->see('Verifying code is not valid!');
    }
}
