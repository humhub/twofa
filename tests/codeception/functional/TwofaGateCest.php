<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace twofa\functional;

use tests\codeception\_pages\LoginPage;
use twofa\FunctionalTester;
use Yii;

/**
 * Verifies the interception behavior of the TwofaGate (see core docs/develop/user-gates.md).
 */
class TwofaGateCest
{
    private function loginPendingAdmin(FunctionalTester $I): void
    {
        Yii::$app->installationState->setInstalled();
        $loginPage = LoginPage::openBy($I);
        $loginPage->login('Admin', 'admin&humhub@PASS%worD!');
        $I->see('Two-factor authentication');
    }

    public function testAjaxRequestReceivesGateResponse(FunctionalTester $I)
    {
        $I->wantTo('ensure that AJAX requests receive a machine-readable gate response while 2FA is pending');

        $this->loginPendingAdmin($I);

        $I->sendAjaxGetRequest('/index-test.php?r=dashboard%2Fdashboard');

        $I->seeResponseCodeIs(401);
        $I->see('twofa');
    }

    public function testApiRequestIsNotIntercepted(FunctionalTester $I)
    {
        $I->wantTo('ensure that token-authenticated API requests are not intercepted while 2FA is pending');

        $this->loginPendingAdmin($I);

        // A REST-style request negotiates JSON and is neither a browser navigation nor XHR
        $I->haveHttpHeader('Accept', 'application/json');
        $I->amOnPage('/index-test.php?r=dashboard%2Fdashboard');

        $I->dontSee('twofa');
    }

    public function testAccountDeleteStaysIntercepted(FunctionalTester $I)
    {
        $I->wantTo('ensure that account deletion is not reachable while 2FA is pending');

        $this->loginPendingAdmin($I);

        $I->amOnPage('/index-test.php?r=user%2Faccount%2Fdelete');

        $I->see('Two-factor authentication');
    }

    public function testLogoutStaysReachableWhilePending(FunctionalTester $I)
    {
        $I->wantTo('ensure that logout works while 2FA is pending');

        $this->loginPendingAdmin($I);

        $I->sendAjaxPostRequest('/index-test.php?r=user%2Fauth%2Flogout');

        $I->amOnRoute('/dashboard/dashboard');
        $I->see('Sign in');
    }
}
