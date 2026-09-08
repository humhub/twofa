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

    public function testSessionRequestCannotEscapeViaAcceptHeader(FunctionalTester $I)
    {
        $I->wantTo('ensure a pending session cannot escape the 2FA check by faking a JSON Accept header');

        $this->loginPendingAdmin($I);

        // A cookie-authenticated (session) request that fakes a non-HTML Accept header must
        // stay subject to the gate — the API exemption only covers stateless token requests,
        // which are determined server-side (see core GateFilter::getRequestClass()).
        $I->haveHttpHeader('Accept', 'application/json');
        $I->amOnPage('/index-test.php?r=dashboard%2Fdashboard');

        $I->seeResponseCodeIs(403);
        $I->see('twofa');
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
