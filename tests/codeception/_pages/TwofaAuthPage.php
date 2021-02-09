<?php

namespace tests\codeception\_pages;

use tests\codeception\_support\BasePage;

/**
 * Represents 2FA verifying code page
 * @property \AcceptanceTester|\FunctionalTester $actor
 */
class TwofaAuthPage extends BasePage
{

    public $route = 'twofa/check';

    /**
     * @param string $code
     */
    public function verify($code)
    {
        if(method_exists($this->actor, 'waitForText')) {
            $this->actor->waitForText('Two-factor authentication');
        }
        $this->actor->fillField('CheckCode[code]', $code);
        $this->actor->click('#verify-button');
    }

}
