<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace twofa;

use humhub\modules\user\models\User;
use tests\codeception\_support\HumHubDbTestCase;

/**
 * "Reset two-factor authentication" on the rows of the administration's user table (HumHub 1.20).
 */
class UserTableActionTest extends HumHubDbTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists('humhub\modules\admin\components\UserTable')) {
            $this->markTestSkipped('The user table exists since HumHub 1.20.');
        }
    }

    public function testTheTableOffersTheResetAfterEdit()
    {
        $this->becomeUser('Admin');

        $row = (new \humhub\modules\admin\components\UserTable())->row(User::findOne(['username' => 'User1']));

        $ids = array_column($row['actions'], 'id');
        $this->assertSame('twofa-reset', $ids[1]);
        $this->assertSame('edit', $ids[0]);
        $this->assertStringContainsString('twofa', $row['actions'][1]['url']);
    }

    public function testNotOfferedWithoutTheUserAdministration()
    {
        $this->becomeUser('User1');

        $row = (new \humhub\modules\admin\components\UserTable())->row(User::findOne(['username' => 'User2']));

        $this->assertNotContains('twofa-reset', array_column($row['actions'], 'id'));
    }
}
