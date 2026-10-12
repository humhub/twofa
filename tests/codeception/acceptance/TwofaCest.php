<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\tests\codeception\acceptance;

use humhub\libs\BasePermission;
use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\twofa\drivers\GoogleAuthenticatorDriver;
use humhub\modules\twofa\Events;
use humhub\modules\twofa\helpers\TwofaHelper;
use humhub\modules\user\components\PermissionManager;
use humhub\modules\user\models\GroupUser;
use humhub\modules\user\models\User;
use PHPUnit\Framework\Assert;
use PragmaRX\Google2FA\Google2FA;
use tests\codeception\_pages\LoginPage;
use twofa\AcceptanceTester;
use Yii;

class TwofaCest
{
    public function testTwoFactorAuthentication(AcceptanceTester $I)
    {
        $I->wantTo('Ensure admin user login with 2FA');
        $loginPage = LoginPage::openBy($I);
        $I->amGoingTo('try to login with admin credentials');
        $loginPage->login('Admin', 'admin&humhub@PASS%worD!');
        $I->expectTo('See Two Factor Auth');
        $I->waitForText('Two-factor authentication');
    }

    public function testNoTwoFactorAuthentication(AcceptanceTester $I)
    {
        $I->wantTo('Ensure regular user login without 2FA');
        $loginPage = LoginPage::openBy($I);
        $I->amGoingTo('try to login with non-admin credentials');
        $loginPage->login('User1', 'user^humhub@PASS%worD!');
        $I->expectTo('see dashboard');
        $I->waitForElementVisible('#wallStream');
        $I->seeCurrentUrlEquals('/dashboard');
    }

    public function testManagerCanResetUserTwoFactorAuthentication(AcceptanceTester $I)
    {
        $I->wantTo('ensure a user manager can reset configured two-factor authentication from the admin user list');

        $user = User::findOne(['username' => 'User1']);
        $manager = User::findOne(['username' => 'User2']);
        $userWithoutTwofa = User::findOne(['username' => 'User3']);

        $this->configureGoogleAuthenticator($user, ['RESETREC01']);
        $this->allowManageUsers($manager);

        Yii::$app->user->logout();

        $loginPage = LoginPage::openBy($I);
        $loginPage->login('User1', 'user^humhub@PASS%worD!');
        $I->waitForText('Two-factor authentication');
        $I->seeCurrentUrlEquals('/twofa/check');
        $I->resetCookie('PHPSESSID');
        $I->executeJS('window.localStorage.clear(); window.sessionStorage.clear();');
        $I->amOnPage('/user/auth/login');
        $I->waitForText('Sign In');

        $I->amUser2();
        $I->amOnPage('/admin/user/list?UserSearch%5BfreeText%5D=User1');
        $I->waitForText($user->displayName);
        $I->see($user->displayName, '.table');
        $I->dontSee($userWithoutTwofa->displayName, '.table');
        $I->click('//tr[contains(., "' . addslashes($user->displayName) . '")]//button[contains(@class, "dropdown-toggle")]');
        $I->waitForElementVisible('//tr[contains(., "' . addslashes($user->displayName) . '")]//a[contains(normalize-space(.), "Reset two-factor authentication")]', 10);
        $I->click('//tr[contains(., "' . addslashes($user->displayName) . '")]//a[contains(normalize-space(.), "Reset two-factor authentication")]');
        $I->waitForElementVisible('#globalModalConfirm button[data-modal-confirm]', 10);
        $I->click('button[data-modal-confirm]', '#globalModalConfirm');
        $I->seeSuccess('Two-factor authentication has been reset for this user.');
        $I->seeInCurrentUrl('/admin/user/list');

        $this->assertUserHasNoTwofaSettings($I, $user);

        $I->logout();

        $loginPage = LoginPage::openBy($I);
        $loginPage->login('User1', 'user^humhub@PASS%worD!');
        $I->waitForElementVisible('#wallStream');
        $I->seeCurrentUrlEquals('/dashboard');
        $I->dontSee('Two-factor authentication');
    }

    public function testGeneratesRecoveryCodes(AcceptanceTester $I)
    {
        $I->wantTo('ensure the initial Google Authenticator setup immediately generates recovery codes');

        $user = User::findOne(['username' => 'User1']);
        $this->resetTwofaSettings($user);

        $I->amUser1();
        $I->amOnPage('/twofa/user-settings');
        $I->waitForText('Authentication method');

        Events::registerAutoloader();

        $tempSecret = $this->getUserSetting($user, GoogleAuthenticatorDriver::SECRET_TEMP_SETTING);
        Assert::assertNotEmpty($tempSecret);

        $I->selectOption('#usersettings-driver', GoogleAuthenticatorDriver::class);
        $I->executeJS("$('#usersettings-driver').trigger('change');");
        $I->waitForElementVisible('#googleauthenticatorusersettings-pincode');
        $I->fillField('GoogleAuthenticatorUserSettings[pinCode]', (new Google2FA())->getCurrentOtp($tempSecret));
        $I->scrollTo('button.btn-primary');
        $I->jsClick('button.btn-primary');

        $I->waitForText('These recovery codes are shown only once.');
        $I->see('Download recovery codes');
        Assert::assertSame(8, $this->getRecoveryCodeCount($user));
        Assert::assertSame(GoogleAuthenticatorDriver::class, $this->getUserSetting($user, TwofaHelper::USER_SETTING));
        Assert::assertNotNull($this->getUserSetting($user, GoogleAuthenticatorDriver::SECRET_SETTING));

        $I->amOnPage('/twofa/user-settings');
        $I->waitForText('Authentication method');
        $I->dontSee('These recovery codes are shown only once.');
        $I->dontSee('Download recovery codes');
    }

    private function configureGoogleAuthenticator(User $user, array $recoveryCodes = []): void
    {
        $this->resetTwofaSettings($user);

        $settings = TwofaHelper::getSettings($user);
        $settings->set(TwofaHelper::USER_SETTING, GoogleAuthenticatorDriver::class);
        $settings->set(GoogleAuthenticatorDriver::SECRET_SETTING, 'JBSWY3DPEHPK3PXP');

        if (!empty($recoveryCodes)) {
            $settings->set(
                GoogleAuthenticatorDriver::RECOVERY_CODES_SETTING,
                json_encode(array_map(static fn(string $recoveryCode) => Yii::$app->security->generatePasswordHash($recoveryCode), $recoveryCodes)),
            );
        }

        $this->flushUserSettings($user);
    }

    private function allowManageUsers(User $user): void
    {
        GroupUser::updateAll(['is_group_manager' => 0], ['user_id' => $user->id]);

        (new PermissionManager())->setGroupState(3, new ManageUsers(), BasePermission::STATE_ALLOW);
        Yii::$app->user->permissionManager->clear();
    }

    private function assertUserHasNoTwofaSettings(AcceptanceTester $I, User $user): void
    {
        $this->flushUserSettings($user);

        foreach (TwofaHelper::getUserSettingNames() as $settingName) {
            Assert::assertNull(TwofaHelper::getSettings($user)->get($settingName));
        }
    }

    private function resetTwofaSettings(User $user): void
    {
        Assert::assertTrue(TwofaHelper::resetUserSettings($user));
    }

    private function getUserSetting(User $user, string $name): ?string
    {
        $this->flushUserSettings($user);

        return TwofaHelper::getSettings($user)->get($name);
    }

    private function getRecoveryCodeCount(User $user): int
    {
        $recoveryCodeHashes = json_decode((string) $this->getUserSetting($user, GoogleAuthenticatorDriver::RECOVERY_CODES_SETTING), true);

        return is_array($recoveryCodeHashes) ? count($recoveryCodeHashes) : 0;
    }

    private function flushUserSettings(User $user): void
    {
        Yii::$app->getModule('user')->settings->flushContentContainer($user);
    }
}
