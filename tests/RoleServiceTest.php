<?php

namespace Imran\WebShell\Tests;

use Imran\WebShell\Services\Security\RoleService;

class RoleServiceTest extends TestCase
{
    public function test_viewer_cannot_deploy_and_owner_can(): void
    {
        $roles = new RoleService;
        $viewer = (object) ['webshell_role' => 'viewer'];
        $owner = (object) ['webshell_role' => 'owner'];

        $this->assertFalse($roles->can($viewer, 'deploy'));
        $this->assertTrue($roles->can($owner, 'deploy'));
        $this->assertSame('viewer', $roles->role($viewer));
    }
}
